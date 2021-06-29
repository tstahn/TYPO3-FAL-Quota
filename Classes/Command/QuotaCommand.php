<?php
declare(strict_types=1);
namespace Mehrwert\FalQuota\Command;

/*
 * 2019 - EXT:fal_quota
 *
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.md', which is part of this source code package.
 */

use Mehrwert\FalQuota\Utility\QuotaUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Quota notification and update class
 *
 * Use like this:
 *
 * ./htdocs/typo3/sysext/core/bin/typo3 fal_quota:quota:update
 */
final class QuotaCommand extends Command
{
    /**
     * @var ConnectionPool
     */
    private $connectionPool;

    /**
     * @var QuotaUtility
     */
    private $quotaUtility;

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Update Quota Statistics for storages')
            ->addArgument(
                'storage-id',
                InputArgument::OPTIONAL,
                'Id of single storage to update.'
            );
    }

    /**
     * @inheritDoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $this->quotaUtility = GeneralUtility::makeInstance(QuotaUtility::class);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $storageId = (int)$input->getArgument('storage-id');
        if ($storageId > 0) {
            $storages = $storageRepository->findByUid($storageId);
        } else {
            $storages = $storageRepository->findAll();
        }
        if (!empty($storages)) {
            foreach ($storages as $storage) {
                $currentUsage = $this->quotaUtility->updateStorageUsage($storage->getUid());
                $this->checkThreshold($storage, $currentUsage);
            }
        }

        return 0;
    }

    /**
     * Check the threshold and send notifications if exceeding limits
     *
     * @param ResourceStorage $storage
     * @param int $currentUsage
     */
    private function checkThreshold(ResourceStorage $storage, int $currentUsage): void
    {
        $quotaConfiguration = [
            'current_usage' => $currentUsage,
            'soft_quota' => (int)$storage->getStorageRecord()['soft_quota'],
            'hard_limit' => (int)$storage->getStorageRecord()['hard_limit'],
            'quota_warning_threshold' => (int)$storage->getStorageRecord()['quota_warning_threshold'],
            'quota_warning_recipients' => $storage->getStorageRecord()['quota_warning_recipients'],
        ];
        if ($quotaConfiguration['soft_quota'] > 0 && $quotaConfiguration['quota_warning_threshold'] > 0) {
            $currentThreshold = (int)($quotaConfiguration['current_usage'] / $quotaConfiguration['soft_quota'] * 100);
            if (($quotaConfiguration['current_usage'] > $quotaConfiguration['soft_quota']
                    || $currentThreshold >= $quotaConfiguration['quota_warning_threshold'])
                && !empty($quotaConfiguration['quota_warning_recipients'])
            ) {
                $this->sendNotification($storage, $quotaConfiguration, $currentThreshold);
            }
        }
    }

    /**
     * Send the over-quota-notification to all configured recipients
     *
     * @param ResourceStorage $storage
     * @param array $quotaConfiguration
     * @param int $currentThreshold
     * @return int
     */
    private function sendNotification(ResourceStorage $storage, array $quotaConfiguration, int $currentThreshold): int
    {
        $hasRecipients = false;
        $warningRecipients = GeneralUtility::trimExplode(',', $quotaConfiguration['quota_warning_recipients'], true);
        $validRecipientAddresses = [];

        $additionalRecipients = [];

        /** @var Dispatcher $signalSlotDispatcher */
        $signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        $signalArguments = $signalSlotDispatcher->dispatch(
            __CLASS__,
            'addAdditionalRecipients',
            [
                $storage,
                $additionalRecipients,
            ]
        );
        $additionalRecipients = array_pop($signalArguments);

        $recipients = array_unique(array_merge($warningRecipients, $additionalRecipients));

        foreach ($recipients as $recipient) {
            if (GeneralUtility::validEmail($recipient)) {
                $validRecipientAddresses[] = $recipient;
                $hasRecipients = true;
            }
        }

        if ($hasRecipients === true) {
            $senderEmailAddress = !empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'])
                ? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
                : 'no-reply@example.com';
            $senderEmailName = !empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'])
                ? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']
                : 'TYPO3 CMS';

            $subject = 'Warning: Storage »' . $storage->getName() . '« (UID: '
                . $storage->getUid() . ') approaching quota limit in \''
                . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '\'';
            $body = 'Warning: Storage »' . $storage->getName() . '« (UID: ' . $storage->getUid()
                . ') is approaching the configured quota limit of ' . QuotaUtility::numberFormat($quotaConfiguration['soft_quota'], 'MB')
                . ' in \'' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '\' currently using '
                . QuotaUtility::numberFormat($quotaConfiguration['current_usage'], 'MB') . ' (' . $currentThreshold . ' %) of allocated storage.';

            // v10: Use Symfony Mail compatible method
            if (VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) >= 10000000) {
                return $this->sendNotificationWithSymfonyMail($subject, $senderEmailAddress, $senderEmailName, $body, $validRecipientAddresses) ? 1 : 0;
            }
            // v9: Use SwiftMailer
            $mailMessage = GeneralUtility::makeInstance(MailMessage::class);
            foreach ($validRecipientAddresses as $recipient) {
                $mailMessage->addTo($recipient);
            }

            return $mailMessage
                ->setSubject($subject)
                ->addFrom($senderEmailAddress, $senderEmailName)
                ->setBody($body)
                ->send();
        }

        return 0;
    }

    /**
     * Use Symfony Mail compatible MailMessage calls for TYPO3 >= v10
     *
     * @param string $subject
     * @param string $senderEmailAddress
     * @param string $senderEmailName
     * @param string $body
     * @param array $recipients
     * @return bool
     * @since 1.1.0
     */
    private function sendNotificationWithSymfonyMail($subject, $senderEmailAddress, $senderEmailName, $body, $recipients): bool
    {
        $mailMessage = GeneralUtility::makeInstance(MailMessage::class);
        foreach ($recipients as $recipient) {
            $mailMessage->to($recipient);
        }

        return $mailMessage
            ->subject($subject)
            ->from(new \Symfony\Component\Mime\Address($senderEmailAddress, $senderEmailName))
            ->text($body)
            ->send();
    }
}
