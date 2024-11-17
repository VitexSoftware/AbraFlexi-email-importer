<?php

declare(strict_types=1);

/**
 * This file is part of the Imap2AbraFlexi package
 *
 * https://github.com/VitexSoftware/AbraFlexi-email-importer
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AbraFlexi\Imap2AF;

/**
 * Description of MailImporter.
 *
 * @author vitex
 */
class MailImporter extends Importer
{
    private Mailboxer $mailbox;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct('mail', $options);
        $this->mailbox = new Mailboxer();
        $doneFolder = \Ease\Shared::cfg('DONE_FOLDER');

        if ($doneFolder) {
            $allFolders = $this->mailbox->getListingFolders();

            if (array_search(str_replace('INBOX', $doneFolder, $this->mailbox->getImapPath()), $allFolders, true) === false) {
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
     * Import isdoc files extracted from mails.
     */
    public function importMails()
    {
        return $this->importIsdocFiles($this->mailbox->saveIsdocs(), $this->mailbox->senders);
    }

    public function moveMessageToDoneFolder(string $inputFile): bool
    {
        $this->mailbox->moveMail($this->mailbox->attachmentMailId($inputFile), \Ease\Shared::cfg('DONE_FOLDER'));

        return parent::moveMessageToDoneFolder($inputFile);
    }

    /**
     * {@inheritDoc}
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
     * CleanUP processed input files and move mail to DONE_FOLDER.
     */
    public function cleanUp(array $invoiceFiles): bool
    {
        parent::cleanUp($invoiceFiles);

        if (\Ease\Shared::cfg('DONE_FOLDER', false)) {
            $this->moveMessageToDoneFolder(basename($invoiceFiles[0]));
            $status = true;
        } else {
            $status = false;
        }

        return $status;
    }
}
