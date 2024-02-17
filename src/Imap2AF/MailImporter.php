<?php

declare(strict_types=1);

/**
 *
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2024 Vitex Software
 */

namespace AbraFlexi\Imap2AF;

/**
 * Description of MailImporter
 *
 * @author vitex
 */
class MailImporter extends Importer
{
    /**
     *
     * @var Mailboxer
     */
    private $mailbox;

    /**
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct('mail', $options);
        $this->mailbox = new Mailboxer();

        if (\Ease\Shared::cfg('DONE_FOLDER')) {
            $allFolders = $this->mailbox->getListingFolders();
            if (array_search($this->mailbox->getImapPath() . '/' . \Ease\Shared::cfg('DONE_FOLDER'), $allFolders) === false) {
                // Create IMAP folder for done messages DONE_DIR
                if ($this->mailbox->createFolder('/' . \Ease\Shared::cfg('DONE_FOLDER'))) {
                    $this->mailbox->addStatusMessage(sprintf(_('New DONE_FOLDER folder %s created'), \Ease\Shared::cfg('DONE_FOLDER')), 'success');
                }
            }
        }
    }

    /**
     *
     */
    public function importMails()
    {
        $this->importIsdocFiles($this->mailbox->saveIsdocs(), $this->mailbox->senders);
    }

    /**
     *
     * @param string $inputFile
     *
     * @return type
     */
    public function moveMessageToDoneFolder($inputFile)
    {
        $this->mailbox->moveMail($this->mailbox->attachmentMailId($inputFile), \Ease\Shared::cfg('IMAP_MAILBOX') . '/' . \Ease\Shared::cfg('DONE_FOLDER'));
        return parent::moveMessageToDoneFolder($inputFile);
    }

    public function alreadyKnownInvoice($invoice, $inputFile)
    {
        parent::alreadyKnownInvoice($invoice, $inputFile);
        if (\Ease\Shared::cfg('DONE_FOLDER')) {
            $this->moveMessageToDoneFolder($inputFile);
        }
    }

    /**
     * CleanUP processed inputfile and move mail to DONE_FOLDER
     *
     * @param array $invoiceFiles
     */
    public function cleanUp($invoiceFiles)
    {
        parent::cleanUp($invoiceFiles);
        if (\Ease\Shared::cfg('DONE_FOLDER')) {
            $this->moveMessageToDoneFolder(basename($invoiceFiles[0]));
        }
    }
}
