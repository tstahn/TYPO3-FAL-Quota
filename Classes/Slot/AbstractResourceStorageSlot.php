<?php
declare(strict_types=1);
namespace Mehrwert\FalQuota\Slot;

/*
 * 2019 - EXT:fal_quota - Configuration fields for Quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

use InvalidArgumentException;
use Mehrwert\FalQuota\Utility\QuotaUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ResourceStorageSlot for Signal/Slots in file list and FAL handling
 */
abstract class AbstractResourceStorageSlot
{
    /**
     * Configured soft quota for the current storage
     * @var float
     */
    private $softQuota;

    /**
     * Current usage of the current storage
     * @var float
     */
    private $currentUsage;

    /**
     * Update the storage quota usage where the file resides in
     *
     * @param FileInterface $file
     */
    protected function updateQuotaByFile(FileInterface $file): void
    {
        try {
            $this->updateQuotaByFolder(
                $file->getStorage()->getFolder(
                    $file->getStorage()->getFolderIdentifierFromFileIdentifier(
                        $file->getIdentifier()
                    )
                )
            );
        } catch (InsufficientFolderAccessPermissionsException | \Exception $e) {
            // Just catch the exception
        }
    }

    /**
     * Update the storage quota usage
     *
     * @param Folder $folder
     */
    protected function updateQuotaByFolder(Folder $folder): void
    {
        GeneralUtility::makeInstance(QuotaUtility::class)->updateStorageUsage($folder->getStorage()->getUid());
    }

    /**
     * General quota check using the values in the storage
     *
     * @param FolderInterface $targetFolder
     * @param int $code
     * @param string $action
     * @param string $file
     */
    protected function checkQuota(FolderInterface $targetFolder, $code, $action = '', $file = ''): void
    {
        if ($this->isOverQuota($targetFolder->getStorage()->getUid()) === true) {
            $message = $this->getLocalizedMessage('over_quota', [$this->currentUsage, $this->softQuota]);
            $this->addMessageToFlashMessageQueue($message);
            throw new ResourceStorageException($message, $code);
        }
    }

    /**
     * Estimate the result size of the copy folder command
     *
     * @param Folder $folder
     * @param Folder $targetFolder
     * @param int $code
     */
    protected function preEstimateUsageAfterCopyFolderCommand(Folder $folder, Folder $targetFolder, $code): void
    {
        $quotaUtility = GeneralUtility::makeInstance(QuotaUtility::class);
        // Use MB as unit for all numeric operations
        $storageDetails = $quotaUtility->getStorageDetails($targetFolder->getStorage()->getUid());
        // Check if quota has been set
        if ($storageDetails['soft_quota'] > 0) {
            $folderSize = $quotaUtility->getFolderSize($folder, $storageDetails['current_usage_raw'] * 1024 * 1024);
            if ($folderSize > $storageDetails['current_usage_raw'] * 1024 * 1024) {
                $message = $this->getLocalizedMessage(
                    'copy_folder_result_will_exceed_quota',
                    [
                        $storageDetails['soft_quota'],
                    ]
                );
                $this->addMessageToFlashMessageQueue($message);
                throw new ResourceStorageException($message, $code);
            }
        }
    }

    /**
     * Estimate the file size with the new content
     *
     * @param FileInterface $file
     * @param mixed $content
     * @param int $code
     */
    protected function preEstimateUsageAfterSetContentCommand(FileInterface $file, $content, $code): void
    {
        // Use MB as unit for all numeric operations
        $contentSize = strlen($content) / 1024 / 1024;
        $storageDetails = GeneralUtility::makeInstance(QuotaUtility::class)->getStorageDetails($file->getStorage()->getUid());
        // Check if quota has been set
        if ($storageDetails['soft_quota'] > 0) {
            // Estimate new usage
            $estimatedUsage = $storageDetails['current_usage'] + $contentSize;
            // Result would exceed quota
            if ($estimatedUsage >= $storageDetails['soft_quota']) {
                $message = $this->getLocalizedMessage(
                    'result_will_exceed_quota',
                    [
                        number_format($estimatedUsage, 2, ',', '.'),
                        $storageDetails['soft_quota'],
                    ]
                );
                $this->addMessageToFlashMessageQueue($message);
                throw new ResourceStorageException($message, $code);
            }
        }
    }

    /**
     * Estimate the storage utilization after the file has been copied
     *
     * @param FileInterface $file
     * @param Folder $targetFolder
     * @param int $code
     */
    protected function preEstimateUsageAfterCopyCommand(FileInterface $file, Folder $targetFolder, $code): void
    {
        // Use MB as unit for all numeric operations
        $copiedFileSize = $file->getSize() / 1024 / 1024;
        $storageDetails = GeneralUtility::makeInstance(QuotaUtility::class)->getStorageDetails($targetFolder->getStorage()->getUid());
        // Estimate new usage
        $estimatedUsage = $storageDetails['current_usage'] + $copiedFileSize;
        // Result would exceed quota
        if ($estimatedUsage >= $storageDetails['soft_quota']) {
            $message = $this->getLocalizedMessage(
                'result_will_exceed_quota',
                [
                    number_format($estimatedUsage, 2, ',', '.'),
                    $storageDetails['soft_quota'],
                ]
            );
            $this->addMessageToFlashMessageQueue($message);
            throw new ResourceStorageException($message, $code);
        }
    }

    /**
     * Estimate the utilization of the the target storage after the file would have been moved
     *
     * @param FileInterface $file
     * @param Folder $targetFolder
     * @param int $code
     */
    protected function preEstimateUsageAfterMoveCommand(FileInterface $file, Folder $targetFolder, $code): void
    {
        // Use MB as unit for all numeric operations
        $movedFileSize = $file->getSize();
        $storageDetails = GeneralUtility::makeInstance(QuotaUtility::class)->getStorageDetails($targetFolder->getStorage()->getUid());
        // Check if quota has been set
        if ($storageDetails['soft_quota'] > 0) {
            // Estimate new usage
            $estimatedUsage = $storageDetails['current_usage_raw'] * 1024 * 1024 + $movedFileSize;
            // Result would exceed quota
            if ($estimatedUsage >= $storageDetails['soft_quota_raw']) {
                $message = $this->getLocalizedMessage(
                    'result_will_exceed_quota',
                    [
                        number_format($estimatedUsage / 1024 / 1024, 2, ',', '.'),
                        $storageDetails['soft_quota'],
                    ]
                );
                $this->addMessageToFlashMessageQueue($message);
                throw new ResourceStorageException($message, $code);
            }
        }
    }

    /**
     * Estimate the utilization after the file would have been replaced with a smaller/bigger file
     *
     * @param FileInterface $file
     * @param string $localFilePath
     * @param int $code
     */
    protected function preEstimateUsageAfterReplaceCommand(FileInterface $file, $localFilePath, $code): void
    {
        if (is_file($localFilePath)) {
            // Use MB as unit for all numeric operations
            $newFileSize = filesize($localFilePath) / 1024 / 1024;
            $currentFileSize = $file->getSize() / 1024 / 1024;
            $storageDetails = GeneralUtility::makeInstance(QuotaUtility::class)->getStorageDetails($file->getStorage()->getUid());
            // Check if quota has been set
            if ($storageDetails['soft_quota'] > 0) {
                // Estimate new usage
                $estimatedUsage = ($storageDetails['current_usage'] - $currentFileSize) + $newFileSize;
                // Result would exceed quota
                if ($estimatedUsage >= $storageDetails['soft_quota']) {
                    $message = $this->getLocalizedMessage(
                        'result_will_exceed_quota',
                        [
                            number_format($estimatedUsage, 2, ',', '.'),
                            $storageDetails['soft_quota'],
                        ]
                    );
                    $this->addMessageToFlashMessageQueue($message);
                    throw new ResourceStorageException($message, $code);
                }
            }
        }
    }

    /**
     * Check if storage is over quota
     *
     * @param int $storageId
     * @return bool
     */
    protected function isOverQuota($storageId): bool
    {
        $storageDetails = GeneralUtility::makeInstance(QuotaUtility::class)->getStorageDetails($storageId);
        $this->softQuota = $storageDetails['soft_quota'];
        $this->currentUsage = $storageDetails['current_usage'];

        return $storageDetails['over_quota'];
    }

    /**
     * Get a localized message for quota warnings
     *
     * @param string $localizationKey
     * @param array $replaceMarkers
     * @return string
     */
    protected function getLocalizedMessage($localizationKey, array $replaceMarkers = []): string
    {
        $label = $this->getLanguageService()->sL('LLL:EXT:fal_quota/Resources/Private/Language/locallang_resource_storage_messages.xlf:' . $localizationKey);

        return vsprintf($label, $replaceMarkers);
    }

    /**
     * Adds a localized FlashMessage to the message queue
     *
     * @param string $message
     * @param int $severity
     * @throws InvalidArgumentException
     */
    protected function addMessageToFlashMessageQueue($message, $severity = FlashMessage::ERROR): void
    {
        if (TYPO3_MODE !== 'BE' || Environment::isCli()) {
            return;
        }
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            '',
            $severity,
            true
        );
        try {
            $this->addFlashMessage($flashMessage);
        } catch (Exception $e) {
            // Just catch the exception
        }
    }

    /**
     * Add flash message to message queue
     *
     * @param FlashMessage $flashMessage
     * @throws Exception
     */
    protected function addFlashMessage(FlashMessage $flashMessage): void
    {
        /** @var FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);

        /** @var FlashMessageQueue $defaultFlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * Returns LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
