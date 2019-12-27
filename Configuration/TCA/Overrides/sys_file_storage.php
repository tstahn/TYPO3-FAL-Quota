<?php
declare(strict_types=1);
defined('TYPO3_MODE') or die();

/*
 * 2019 - EXT:fal_quota - Configuration fields for Quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

call_user_func(function () {
    $llFile = 'LLL:EXT:fal_quota/Resources/Private/Language/locallang_tca.xlf';
    $llFormFile = 'LLL:EXT:fal_quota/Resources/Private/Language/locallang_tabs.xlf';

    $tempColumns = [];
    $tempColumns['soft_quota'] = [
        'exclude' => 1,
        'label' => $llFile . ':sys_file_storage.soft_quota',
        'config' => [
            'type' => 'input',
            'size' => '10',
            'max' => '14',
            'eval' => 'int,trim,Mehrwert\\FalQuota\\Evaluation\\StorageQuotaEvaluation',
        ],
    ];
    $tempColumns['hard_limit'] = [
        'exclude' => 1,
        'label' => $llFile . ':sys_file_storage.hard_limit',
        'config' => [
            'type' => 'input',
            'size' => '10',
            'max' => '14',
            'eval' => 'int,trim,Mehrwert\\FalQuota\\Evaluation\\StorageQuotaEvaluation',
        ],
    ];
    $tempColumns['current_usage'] = [
        'exclude' => 1,
        'label' => $llFile . ':sys_file_storage.current_usage',
        'config' => [
            'type' => 'passthrough',
            'size' => '4',
        ],
    ];
    $tempColumns['quota_warning_threshold'] = [
        'exclude' => 1,
        'label' => $llFile . ':sys_file_storage.quota_warning_threshold',
        'config' => [
            'type' => 'input',
            'default' => 75,
            'size' => '4',
            'max' => '3',
            'eval' => 'trim,int',
            'range' => [
                'lower' => 0,
                'upper' => 100,
            ],
            'slider' => [
                'step' => 5,
                'width' => 200,
            ],
        ],
    ];
    $tempColumns['quota_warning_recipients'] = [
        'exclude' => 1,
        'label' => $llFile . ':sys_file_storage.quota_warning_recipients',
        'config' => [
            'type' => 'input',
            'size' => '30',
            'eval' => 'trim',
        ],
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
        'sys_file_storage',
        $tempColumns
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
        'sys_file_storage',
        'quota_limits',
        'soft_quota, hard_limit'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
        'sys_file_storage',
        'notification_settings',
        'quota_warning_threshold, quota_warning_recipients'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'sys_file_storage',
        '--div--;' . $llFormFile . ':tab.fal_quota,
        --palette--;' . $llFormFile . ':palette.quota_limits;quota_limits,
        --palette--;' . $llFormFile . ':palette.notification_settings;notification_settings'
    );
});
