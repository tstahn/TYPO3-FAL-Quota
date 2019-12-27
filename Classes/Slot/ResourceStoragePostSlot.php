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
use TYPO3\CMS\Core\Resource\FolderInterface;

/**
 * Class ResourceStoragePostSlot for Post Slots in file list and FAL handling
 */
class ResourceStoragePostSlot extends AbstractResourceStorageSlot
{
    /**
     * Slot will be called after a file is added. Update quota values.
     *
     * @param FileInterface $file
     * @param Folder $targetFolder
     */
    public function postFileAdd(FileInterface $file, Folder $targetFolder): void
    {
        $this->updateQuotaByFolder($targetFolder);
    }

    /**
     * Slot will be called after a file is copied. Update quota values.
     *
     * @param FileInterface $file
     * @param Folder $targetFolder
     */
    public function postFileCopy(FileInterface $file, Folder $targetFolder): void
    {
        $this->updateQuotaByFolder($targetFolder);
    }

    /**
     * Slot will be called after a file is moved. Update quota values.
     *
     * @param FileInterface $file
     * @param Folder $targetFolder
     * @param FolderInterface $originalFolder
     */
    public function postFileMove(FileInterface $file, Folder $targetFolder, FolderInterface $originalFolder): void
    {
        $this->updateQuotaByFolder($targetFolder);
    }

    /**
     * Slot will be called after a file is renamed. Update quota values.
     *
     * @param FileInterface $file
     * @param string $sanitizedTargetFileName
     */
    public function postFileRename(FileInterface $file, $sanitizedTargetFileName): void
    {
        $this->updateQuotaByFile($file);
    }

    /**
     * Slot will be called after a file is replaces. Update quota values.
     *
     * @param FileInterface $file
     * @param string $localFilePath
     */
    public function postFileReplace(FileInterface $file, $localFilePath): void
    {
        $this->updateQuotaByFile($file);
    }

    /**
     * Slot will be called after a file is created. Update quota values.
     *
     * @param string $newFileIdentifier
     * @param Folder $targetFolder
     */
    public function postFileCreate($newFileIdentifier, Folder $targetFolder): void
    {
        $this->updateQuotaByFolder($targetFolder);
    }

    /**
     * Slot will be called after a file is deleted. Update quota values.
     *
     * @param FileInterface $file
     */
    public function postFileDelete(FileInterface $file): void
    {
        $this->updateQuotaByFile($file);
    }

    /**
     * Slot will be called after the contents of a file object have been set. Update quota values.
     *
     * @param FileInterface $file
     * @param mixed $content
     */
    public function postFileSetContents(FileInterface $file, $content): void
    {
        $this->updateQuotaByFile($file);
    }

    /**
     * Slot will be called after a folder is copied. Update quota values.
     *
     * @param Folder $folder
     * @param Folder $targetFolder
     * @param        $newName
     */
    public function postFolderCopy(Folder $folder, Folder $targetFolder, $newName): void
    {
        $this->updateQuotaByFolder($targetFolder);
    }

    /**
     * Slot will be called after a folder is moved. Update quota values for source and target.
     *
     * @param Folder $folder
     * @param Folder $targetFolder
     * @param string $newName
     * @param Folder $originalFolder
     */
    public function postFolderMove(Folder $folder, Folder $targetFolder, $newName, Folder $originalFolder): void
    {
        $this->updateQuotaByFolder($targetFolder);
        $this->updateQuotaByFolder($originalFolder);
    }

    /**
     * Slot will be called after a folder is deleted. Update quota values.
     *
     * @param Folder $folder
     */
    public function postFolderDelete(Folder $folder): void
    {
        $this->updateQuotaByFolder($folder);
    }
}
