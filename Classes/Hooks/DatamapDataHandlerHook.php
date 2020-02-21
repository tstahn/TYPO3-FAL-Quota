<?php
declare(strict_types=1);
namespace Mehrwert\FalQuota\Hooks;

/*
 * 2019 - EXT:fal_quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

use Mehrwert\FalQuota\Utility\QuotaUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class DatamapDataHandlerHook to check and format TCA values for quota fields in sys_file_storage records
 */
class DatamapDataHandlerHook
{
    /**
     * @param DataHandler $tceMain
     * @param mixed $status
     * @param mixed $table
     * @param mixed $id
     * @param mixed $fieldArray
     */
    public function processDatamap_postProcessFieldArray($status, $table, $id, $fieldArray, DataHandler $tceMain): void
    {
        if ($tceMain->bypassFileHandling || empty($tceMain->datamap['sys_file_storage'])) {
            return;
        }
        foreach ($tceMain->datamap['sys_file_storage'] as $storageId => $storage) {
            $this->validateQuotaConfiguration((int)$storageId, $storage, $tceMain);
        }
    }

    /**
     * Validate storage configuration
     *
     * @param int $storageId
     * @param array $storage
     * @param DataHandler $tceMain
     */
    private function validateQuotaConfiguration(int $storageId, array $storage, DataHandler $tceMain): void
    {
        $hardLimit = (int)$storage['hard_limit'] * (1024 ** 2);
        $softQuota = (int)$storage['soft_quota'] * (1024 ** 2);

        if ($hardLimit > 0 && $hardLimit < $softQuota) {
            $label = $this->getLanguageService()->sL('LLL:EXT:fal_quota/Resources/Private/Language/locallang_tce_hook_messages.xlf:' . 'quotaSettingMismatch');
            $message = vsprintf(
                $label,
                [
                    QuotaUtility::numberFormat($softQuota, 'MB'),
                    QuotaUtility::numberFormat($hardLimit, 'MB'),
                ]
            );
            $this->logStorageError($tceMain, $storageId, $message);
        }
        if ($storageId > 0) {
            $availableSize = GeneralUtility::makeInstance(QuotaUtility::class)->getAvailableSpaceOnStorageOnDevice($storageId);
            // Check settings if available size is not -1
            if ($availableSize >= 0) {
                if ($hardLimit > $availableSize || $softQuota > $availableSize) {
                    $label = $this->getLanguageService()->sL('LLL:EXT:fal_quota/Resources/Private/Language/locallang_tce_hook_messages.xlf:' . 'diskspaceWarning');
                    $message = vsprintf(
                        $label,
                        [
                            QuotaUtility::numberFormat($availableSize, 'MB'),
                            QuotaUtility::numberFormat($softQuota, 'MB'),
                            QuotaUtility::numberFormat($hardLimit, 'MB'),
                        ]
                    );
                    $this->logStorageError($tceMain, $storageId, $message);
                }
            }
        }
    }

    /**
     * Log storage errors
     *
     * @param DataHandler $tceMain
     * @param int $storageId
     * @param string $message
     */
    private function logStorageError(DataHandler $tceMain, int $storageId, string $message): void
    {
        $tceMain->log(
            'sys_file_storage',
            $storageId,
            $storageId > 0 ? 2 : 1,
            0,
            1,
            $message,
            0
        );
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
