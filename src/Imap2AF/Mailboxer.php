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

use Ease\Logger\Logging;
use Ease\Shared;
use PhpImap\Exceptions\ConnectionException;
use PhpImap\Mailbox;

/**
 * Description of Mailboxer.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Mailboxer extends Mailbox
{
    use Logging;

    /**
     * List of external IDs.
     *
     * @var array<string>
     */
    public array $extIds = [];

    /**
     * Per invoice sender address list.
     *
     * @var array<string>
     */
    public array $senders = [];

    /**
     * List of files to clean.
     *
     * @var array<string>
     */
    protected array $filesToClean = [];
    private string $mlogin;
    private string $mailbox;
    private string $mport;
    private string $mserver;
    private string $mpassword;
    private string $moptions = 'notls';

    /**
     * List of attachments.
     *
     * @var array<string>
     */
    private array $attachments = [];

    /**
     * Mailbox handler.
     */
    public function __construct()
    {
        $this->setUp(Shared::singleton()->configuration);

        // Create PhpImap\Mailbox instance for all further actions
        parent::__construct(
            '{'.$this->mserver.':'.$this->mport.'/'.$this->moptions.'}'.$this->mailbox, // IMAP server and mailbox folder
            $this->mlogin, // Username for the before configured mailbox
            $this->mpassword, // Password for the before configured username
            sys_get_temp_dir().'/', // Directory, where attachments will be saved (optional)
            'UTF-8', // Server encoding (optional)
        );
    }

    /**
     * Disconnect Nmpap and Clean temporary files.
     */
    public function __destruct()
    {
        parent::__destruct();

        foreach ($this->filesToClean as $fileToClean) {
            if (file_exists($fileToClean)) {
                unlink($fileToClean);
            }
        }
    }

    /**
     * @param array<string> $params
     */
    public function setUp(array $params = []): void
    {
        $this->setupProperty($params, 'msklad', 'ABRAFLEXI_SKLAD');
        $this->setupProperty($params, 'mlogin', 'IMAP_LOGIN');
        $this->setupProperty($params, 'mpassword', 'IMAP_PASSWORD');
        $this->setupProperty($params, 'mport', 'IMAP_PORT');
        $this->setupProperty($params, 'moptions', 'IMAP_OPTIONS');
        $this->setupProperty($params, 'mailbox', 'IMAP_MAILBOX');
        $this->setupProperty($params, 'mserver', 'IMAP_SERVER');
    }

    /**
     * Set up one of properties by 1) array 2) ENV 3) Constant.
     *
     * @param array<string, string> $options  array of given available properties
     * @param string                $name     name of property to set up
     * @param string                $constant load default property value from constant / ENV
     */
    public function setupProperty(array $options, string $name, string $constant = ''): void
    {
        if (\array_key_exists($name, $options)) {
            $this->{$name} = $options[$name];
        } elseif ($constant && \array_key_exists($constant, $options)) {
            $this->{$name} = $options[$constant];
        } else { // If No values specified we must use constants or environment
            if ($constant && (empty(Shared::cfg($constant)) === false)) {
                $this->{$name} = Shared::cfg($constant);
            }
        }
    }

    /**
     * @throws \Exception
     *
     * @return array<string, string>
     */
    public function pullIsdocs()
    {
        $isdocs = [];
        $this->addStatusMessage(_('Connecting to mailbox').' '.$this->imapLogin.' '.$this->imapPath, 'debug');

        try {
            $mailsIds = $this->searchMailbox('ALL');
            $this->addStatusMessage(\count($mailsIds).' '._('messages found'), 'debug');
        } catch (ConnectionException $ex) {
            throw new \Exception('IMAP connection failed: '.$ex);
        }

        $this->attachments = [];

        foreach ($mailsIds as $mailId) {
            $email = $this->getMail(
                $mailId, // ID of the email, you want to get
                false, // Do NOT mark emails as seen (optional)
            );

            if ($email->hasAttachments()) {
                foreach ($email->getAttachments() as $attachmentRaw) {
                    $this->filesToClean[basename($attachmentRaw->filePath)] = $attachmentRaw->filePath;

                    if (strstr(strtolower($attachmentRaw->name), '.isdoc')) {
                        $isdocs[$attachmentRaw->name] = $attachmentRaw;
                        $this->attachments[$attachmentRaw->name] = $mailId;
                        $this->senders[$attachmentRaw->name] = property_exists($email->headers, 'replay_to') ? $email->headers->reply_to[0]->mailbox.'@'.$email->headers->reply_to[0]->host : $email->headers->from[0]->mailbox.'@'.$email->headers->from[0]->host;
                        $this->addStatusMessage(sprintf(_('Isdoc File %s Found in mail from %s '), $attachmentRaw->name, $this->senders[$attachmentRaw->name]));
                    }
                }
            }
        }

        return $isdocs;
    }

    /**
     * Obtain number of mail which is attachment source.
     *
     * @param string $attachment
     *
     * @return int
     */
    public function attachmentMailId($attachment)
    {
        return $this->attachments[$attachment];
    }

    /**
     * Extract ISDOCx from Inbox.
     *
     * @return array<string, string> Isdoc Attachments on disk
     */
    public function saveIsdocs()
    {
        $saved = [];

        foreach ($this->pullIsdocs() as $filename => $attachment) {
            $saved[$filename] = $attachment->saveToDisk() ? $attachment->filePath : false;
        }

        return $saved;
    }

    /**
     * Move mail to another folder.
     *
     * @param string $folderName name of new folder
     *
     * @return bool
     */
    public function createFolder($folderName)
    {
        return imap_createmailbox($this->getImapStream(), imap_utf7_encode(str_replace('INBOX', $folderName, $this->imapPath)));
    }
}
