<?php
declare(strict_types=1);
namespace Mehrwert\FalQuota\Slot;

/*
 * 2019 - EXT:fal_quota - Configuration fields for Quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;

/**
 * Class ResourceStoragePreSlot for Pre Slots in file list and FAL handling
 */
class ResourceStoragePreSlot extends AbstractResourceStorageSlot
{
    /**
     * Simple quota check of target storage
     *
     * @param string $targetFileName
     * @param Folder $targetFolder
     * @param string $sourceFilePath
     * @throws ResourceStorageException
     */
    public function preFileAdd(
        string $targetFileName,
        Folder $targetFolder,
        string $sourceFilePath
    ): void {
        $this->checkQuota($targetFolder, 1576872000);
    }

    /**
     * Simple quota check of target storage
     *
     * @param string $newFileIdentifier
     * @param Folder $targetFolder
     * @throws ResourceStorageException
     */
    public function preFileCreate(
        string $newFileIdentifier,
        Folder $targetFolder
    ): void {
        $this->checkQuota($targetFolder, 1576872001);
    }

    /**
     * Estimate usage after the copy command
     *
     * @param FileInterface $file
     * @param Folder $targetFolder
     * @throws ResourceStorageException
     */
    public function preFileCopy(
        FileInterface $file,
        Folder $targetFolder
    ): void {
        $this->preEstimateUsageAfterCopyCommand($file, $targetFolder, 1576872002);
    }

    /**
     * Estimate usage after the move command (if target storage ≠ current storage)
     *
     * @param FileInterface $file
     * @param Folder $targetFolder
     * @param string $targetFileName
     * @throws ResourceStorageException
     */
    public function preFileMove(
        FileInterface $file,
        Folder $targetFolder,
        string $targetFileName
    ): void {
        $this->preEstimateUsageAfterMoveCommand($file, $targetFolder, 1576872003);
    }

    /**
     * Estimate usage after the replace command
     *
     * @param FileInterface $file
     * @param string $localFilePath
     * @throws ResourceStorageException
     */
    public function preFileReplace(
        FileInterface $file,
        string $localFilePath
    ): void {
        $this->preEstimateUsageAfterReplaceCommand($file, $localFilePath, 1576872004);
    }

    /**
     * Estimate usage after writing new content to file command
     *
     * @param FileInterface $file
     * @param mixed $content
     * @throws ResourceStorageException
     */
    public function preFileSetContents(
        FileInterface $file,
        $content
    ): void {
        $this->preEstimateUsageAfterSetContentCommand($file, $content, 1576872005);
    }

    /**
     * Estimate usage after the copy folder command
     *
     * @param Folder $folder
     * @param Folder $targetFolder
     * @param $newName
     * @throws ResourceStorageException
     */
    public function preFolderCopy(
        Folder $folder,
        Folder $targetFolder,
        $newName
    ): void {
        $this->preEstimateUsageAfterCopyFolderCommand($folder, $targetFolder, 1576872006);
    }
}
