<?php
declare(strict_types=1);
namespace Mehrwert\FalQuota\Controller;

/*
 * 2019 - EXT:fal_quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

use Mehrwert\FalQuota\Utility\QuotaUtility;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

class DashboardController extends ActionController
{
    /**
     * FAL Quota utility class
     *
     * @var QuotaUtility
     */
    private $quotaUtility;

    /**
     * Backend Template Container
     *
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * BackendTemplateContainer
     *
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * Initialize action
     */
    protected function initializeAction(): void
    {
        parent::initializeAction();
        $this->quotaUtility = GeneralUtility::makeInstance(QuotaUtility::class);
    }

    /**
     * @inheritDoc
     */
    protected function initializeView(ViewInterface $view)
    {
        if ($view instanceof BackendTemplateView) {
            /** @var BackendTemplateView $view */
            parent::initializeView($view);
            $this->registerDocheaderButtons();
            $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
            $view->assign(
                'localizationFile',
                'LLL:EXT:fal_quota/Resources/Private/Language/locallang_mod.xlf'
            );
        }
    }

    /**
     * Registers the Icons into the docheader
     */
    protected function registerDocHeaderButtons(): void
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $currentRequest = $this->request;
        $moduleName = $currentRequest->getPluginName();
        $getVars = $this->request->getArguments();

        $extensionName = $currentRequest->getControllerExtensionName();
        if (empty($getVars)) {
            $modulePrefix = strtolower('tx_' . $extensionName . '_' . $moduleName);
            $getVars = ['id', 'M', $modulePrefix];
        }

        // SHORTCUT button:
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName($moduleName)
            ->setGetVariables($getVars);
        $buttonBar->addButton($shortcutButton);
    }

    /**
     * Dashboard overview
     */
    public function indexAction(): void
    {
        $storages = GeneralUtility::makeInstance(StorageRepository::class)->findAll();
        $aggregatedStorages = [];

        if (!empty($storages)) {
            foreach ($storages as $storage) {
                $aggregatedStorages[$storage->getUid()] = $this->quotaUtility->getStorageDetails($storage->getUid());
            }
            asort($aggregatedStorages);
        }
        $this->view->assign('storages', $aggregatedStorages);
    }
}
