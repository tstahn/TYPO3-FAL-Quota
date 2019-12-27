<?php
declare(strict_types=1);
namespace Mehrwert\FalQuota\Utility;

/*
 * 2019 - EXT:fal_quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class QuotaUtility provides utilities to get storage details, quota settings and issue warning mails
 */
final class QuotaUtility
{
    /**
     * @var ConnectionPool
     */
    private $connectionPool;

    /**
     * QuotaUtility constructor.
     */
    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
    }

    /**
     * Aggregate details for a given storage and return as array
     *
     * @param int $storageId
     * @return array
     */
    public function getStorageDetails($storageId): array
    {
        $storage = GeneralUtility::makeInstance(StorageRepository::class)->findByUid($storageId);

        if ($storage !== null) {
            $isOverQuota = false;
            $isOverThreshold = false;
            $currentThreshold = 0;

            if ((int)$storage->getStorageRecord()['soft_quota'] > 0) {
                $currentThreshold = (int)$storage->getStorageRecord()['current_usage'] / (int)$storage->getStorageRecord()['soft_quota'] * 100;
                if ((int)$storage->getStorageRecord()['current_usage'] > (int)$storage->getStorageRecord()['soft_quota']) {
                    $isOverQuota = true;
                }
                if ($currentThreshold >= (int)$storage->getStorageRecord()['quota_warning_threshold']) {
                    $isOverThreshold = true;
                }
            }

            return [
                'uid' => $storage->getUid(),
                'name' => $storage->getName(),
                'driver' => $storage->getDriverType(),
                'over_quota' => $isOverQuota,
                'over_threshold' => $isOverThreshold,
                'current_usage' => self::numberFormat((int)$storage->getStorageRecord()['current_usage'], 'MB'),
                'current_usage_raw' => (int)$storage->getStorageRecord()['current_usage'],
                'soft_quota' => self::numberFormat((int)$storage->getStorageRecord()['soft_quota'], 'MB'),
                'soft_quota_raw' => (int)$storage->getStorageRecord()['soft_quota'],
                'hard_limit' => self::numberFormat((int)$storage->getStorageRecord()['hard_limit'], 'MB'),
                'hard_limit_raw' => (int)$storage->getStorageRecord()['hard_limit'],
                'quota_warning_threshold' => number_format((int)$storage->getStorageRecord()['quota_warning_threshold'], 2, ',', '.'),
                'current_threshold' => number_format($currentThreshold, 2, ',', '.'),
                'quota_warning_recipients' => $storage->getStorageRecord()['quota_warning_recipients'],
            ];
        }

        return [];
    }

    /**
     * Get the total disk space used in a storage by SUM()ing upd all file sizes in this storage
     *
     * @param int $storageId
     * @return int
     */
    public function getTotalDiskSpaceUsedInStorage($storageId): int
    {
        $storage = $this->getStorage($storageId);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file');
        $queryBuilder
            ->addSelectLiteral(
                $queryBuilder->expr()->sum('size', 'current_usage')
            )
            ->from('sys_file')
            ->where(
                $queryBuilder->expr()->eq('storage', $storage->getUid())
            );
        $result = $queryBuilder->execute()->fetchAll();

        return (int)$result[0]['current_usage'];
    }

    /**
     * Calculate and update the current usage for the storage
     *
     * @param int $storageId
     * @return int Current usage in MB
     */
    public function updateStorageUsage($storageId): int
    {
        $currentUsage = $this->getTotalDiskSpaceUsedInStorage($storageId);
        $connection = $this->connectionPool->getConnectionForTable('sys_file_storage');
        $connection->update(
            'sys_file_storage',
            [ 'current_usage' => $currentUsage ],
            [ 'uid' => $storageId ]
        );

        return $currentUsage;
    }

    /**
     * Return the size of a FAL folder by recursively aggregating the files. To speed up, the process can by
     * stopped if the total size is exceeding a given limit.
     *
     * @param Folder $folder
     * @param int $breakAt
     * @return int
     */
    public function getFolderSize(Folder $folder, $breakAt = 0): int
    {
        $folderSize = 0;
        foreach ($folder->getFiles(0, 0, 1, true) as $file) {
            $folderSize += (int)$file->getSize();
            unset($file);
            if ($breakAt > 0 && $folderSize > $breakAt) {
                unset($files);

                return $folderSize;
            }
        }

        return $folderSize;
    }

    /**
     * Returns the available size in bytes on the given storage
     *
     * @param int $storageId
     * @return int Returns -1 if no value could be determined of method not available
     */
    public function getAvailableSpaceOnStorageOnDevice(int $storageId): int
    {
        $availableSize = -1;
        $storage = GeneralUtility::makeInstance(StorageRepository::class)->findByUid($storageId);
        // Check local storage only
        if ($storage !== null && function_exists('disk_free_space') && $storage->getDriverType() === 'Local') {
            $storageConfiguration = $storage->getConfiguration();
            if ($storageConfiguration['pathType'] === 'absolute') {
                $absoluteStoragePath = $storageConfiguration['basePath'];
            } else {
                $absoluteStoragePath = Environment::getPublicPath() . '/' . $storageConfiguration['basePath'];
            }
            if (is_dir($absoluteStoragePath)) {
                $availableSize = disk_free_space($absoluteStoragePath);
            }
        }

        return (int)$availableSize;
    }

    /**
     * Return a formatted number and append the given unit. Uses fallback to number_format() if the PHP extension
     * »intl« is not available. Uses TYPO3 [SYS][systemLocale] if set, falls back to locale_get_default() if empty.
     *
     * @param int $number
     * @param string $unit
     * @param bool $addUnit
     * @return string
     */
    public static function numberFormat(int $number, $unit = '', $addUnit = true): string
    {
        switch ($unit) {
            case 'kB':
                $number /= 1024;
                break;
            case 'MB':
                $number = (int)($number / 1024 ** 2);
                break;
            case 'GB':
                $number = (int)($number / 1024 ** 3);
                break;
            case 'TB':
                $number = (int)($number / 1024 ** 4);
                break;
            default:
        }

        if (extension_loaded('intl') && class_exists('NumberFormatter')) {
            $locale = $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale'];
            if ($locale === '') {
                $locale = locale_get_default();
            }
            $fmt = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
            $formattedNumber = $fmt->format($number);
            if (intl_is_failure($fmt->getErrorCode())) {
                $formattedNumber = number_format($number, 0, '', '.');
            }
        } else {
            $formattedNumber = number_format($number, 0, '', '.');
        }

        return  $formattedNumber . ($addUnit ? ' ' . $unit : '');
    }

    /**
     * Returns a storage object for the given storage id
     *
     * @param int $storageId
     * @return ResourceStorage
     */
    private function getStorage($storageId): ResourceStorage
    {
        return GeneralUtility::makeInstance(StorageRepository::class)->findByUid($storageId);
    }
}
