<?php
defined('TYPO3_MODE') or die();

/*
 * 2019 - EXT:fal_quota -FAL Quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

call_user_func(
    function ($extKey) {
        /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
        $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class
        );
        $preSlotNames = [
            'preFileAdd',
            'preFileCreate',
            'preFileCopy',
            'preFileMove',
            'preFileReplace',
            'preFileSetContents',
            'preFolderCopy',
        ];
        $postSlotNames = [
            'postFileAdd',
            'postFileCreate',
            'postFileCopy',
            'postFileDelete',
            'postFileRename',
            'postFileReplace',
            'postFileSetContents',
            'postFolderCopy',
            'postFolderMove',
            'postFolderDelete',
            'postFolderRename',
        ];
        foreach ($preSlotNames as $preSlotName) {
            $signalSlotDispatcher->connect(
                \TYPO3\CMS\Core\Resource\ResourceStorage::class,
                $preSlotName,
                \Mehrwert\FalQuota\Slot\ResourceStoragePreSlot::class,
                $preSlotName
            );
        }
        foreach ($postSlotNames as $postSlotName) {
            $signalSlotDispatcher->connect(
                \TYPO3\CMS\Core\Resource\ResourceStorage::class,
                $postSlotName,
                \Mehrwert\FalQuota\Slot\ResourceStoragePostSlot::class,
                $postSlotName
            );
        }
        // Register the class to be available in 'eval' of TCA
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][\Mehrwert\FalQuota\Evaluation\StorageQuotaEvaluation::class] = '';
    },
    $_EXTKEY
);
