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
        $doneFolder = \Ease\Shared::cfg('DONE_FOLDER');
        if ($doneFolder) {
            $allFolders = $this->mailbox->getListingFolders();
            if (array_search(str_replace('INBOX', $doneFolder, $this->mailbox->getImapPath()), $allFolders) === false) {
                // Create IMAP folder for done messages DONE_DIR
                if ($this->mailbox->createFolder($doneFolder)) {
                    $this->mailbox->addStatusMessage(sprintf(_('New DONE_FOLDER folder %s created'), $doneFolder), 'success');
                }
            }
        } else {
            $this->addStatusMessage(_('The DONE_FOLDER is not specified. The messages will remain in the same folder.'));
        }
    }

    /**
     * Import isdoc files extracted from mails
     *
     * @return null none
     */
    public function importMails()
    {
        return $this->importIsdocFiles($this->mailbox->saveIsdocs(), $this->mailbox->senders);
    }

    /**
     *
     * @param string $inputFile
     *
     * @return null
     */
    public function moveMessageToDoneFolder($inputFile)
    {
        $this->mailbox->moveMail($this->mailbox->attachmentMailId($inputFile), \Ease\Shared::cfg('DONE_FOLDER'));
        return parent::moveMessageToDoneFolder($inputFile);
    }

    /**
     * @inheritDoc
     */
    public function alreadyKnownInvoice($invoice, $inputFile)
    {
        $result = parent::alreadyKnownInvoice($invoice, $inputFile);
        if (\Ease\Shared::cfg('DONE_FOLDER')) {
            $this->moveMessageToDoneFolder($inputFile);
        }
        return $result;
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
            $status = 1;
        } else {
            $status = 0;
        }
        return $status;
    }
}
