<?php
defined('TYPO3_MODE') || die();

call_user_func(
    function () {
        $extKey = 'fal_quota';

        if (TYPO3_MODE === 'BE') {

            // Add submodule `falquota`
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                'Mehrwert.FalQuota',
                'tools',
                'falquota',
                'bottom',
                [
                    'Dashboard' => 'index',
                ],
                [
                    'access' => 'systemMaintainer',
                    'icon' => 'EXT:' . $extKey . '/Resources/Public/Images/Icons/module-icon.svg',
                    'labels' => 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_mod.xlf',
                    'navigationComponentId' => '',
                ]
            );
            // Register DatamapDataHandlerHook
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['fal_quota'] =
                \Mehrwert\FalQuota\Hooks\DatamapDataHandlerHook::class;
        }
    }
);
