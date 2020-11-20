<?php

/**
 * Imap2AbraFlexi MailBox handler
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2019-2020 Vitex Software
 */

namespace AbraFlexi\Imap2AF;

use Ease\Logger\Logging;
use Ease\Shared;
use Exception;
use PhpImap\Exceptions\ConnectionException;
use PhpImap\Mailbox;

/**
 * Description of Mailboxer
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Mailboxer extends Mailbox {

    use Logging;

    /**
     *
     * @var array 
     */
    public $extIds = [];

    /**
     * Per invoice sender address list
     * @var array 
     */
    public $senders;
    private $mlogin;
    private $mailbox;
    private $mport;
    private $mserver;
    private $mpassword;
    private $moptions = 'notls';

    /**
     * 
     * @param array $params
     */
    public function setUp($params = []) {
        $this->setupProperty($params, 'msklad', 'ABRAFLEXI_SKLAD');
        $this->setupProperty($params, 'mlogin', 'IMAP_LOGIN');
        $this->setupProperty($params, 'mpassword', 'IMAP_PASSWORD');
        $this->setupProperty($params, 'mport', 'IMAP_PORT');
        $this->setupProperty($params, 'moptions', 'IMAP_OPTIONS');
        $this->setupProperty($params, 'mailbox', 'IMAP_MAILBOX');
        $this->setupProperty($params, 'mserver', 'IMAP_SERVER');
    }

    /**
     * Set up one of properties by 1) array 2) ENV 3) Constant
     *
     * @param array  $options  array of given availble properties
     * @param string $name     name of property to set up
     * @param string $constant load default property value from constant / ENV
     */
    public function setupProperty($options, $name, $constant = null) {
        if (array_key_exists($name, $options)) {
            $this->$name = $options[$name];
        } elseif (array_key_exists($constant, $options)) {
            $this->$name = $options[$constant];
        } else { // If No values specified we must use constants or environment
            if (property_exists($this, $name) && !empty($constant) && defined($constant)) {
                $this->$name = constant($constant);
            } elseif (property_exists($this, $name) && ($env = getenv($constant)) && !empty($env)) {
                $this->$name = getenv($constant);
            }
        }
    }

    /**
     * Mailbox handler
     */
    public function __construct() {
        $this->setUp(Shared::singleton()->configuration);

// Create PhpImap\Mailbox instance for all further actions
        parent::__construct(
                '{' . $this->mserver . ':' . $this->mport . '/' . $this->moptions . '}' . $this->mailbox, // IMAP server and mailbox folder
                $this->mlogin, // Username for the before configured mailbox
                $this->mpassword, // Password for the before configured username
                sys_get_temp_dir() . '/', // Directory, where attachments will be saved (optional)
                'UTF-8' // Server encoding (optional)
        );
    }

    /**
     * 
     * @return array
     * 
     * @throws Exception 
     */
    public function pullIsdocs() {
        $isdocs = [];
        $this->addStatusMessage(_('Connecting to mailbox') . ' ' . $this->imapLogin . ' ' . $this->imapPath, 'debug');
        try {
            $mailsIds = $this->searchMailbox('ALL');
        } catch (ConnectionException $ex) {
            throw new Exception("IMAP connection failed: " . $ex);
        }

        foreach ($mailsIds as $mailId) {
            $email = $this->getMail(
                    $mailId, // ID of the email, you want to get
                    false // Do NOT mark emails as seen (optional)
            );

            if ($email->hasAttachments()) {
                foreach ($email->getAttachments() as $attachmentRaw) {
                    if (strstr(strtolower($attachmentRaw->name), '.isdoc')) {
                        $isdocs[$attachmentRaw->name] = $attachmentRaw;
                        $this->senders[$attachmentRaw->name] = property_exists($email->headers, 'replay_to') ? $email->headers->reply_to[0]->mailbox . '@' . $email->headers->reply_to[0]->host : $email->headers->from[0]->mailbox . '@' . $email->headers->from[0]->host;
                        $this->addStatusMessage(sprintf(_('Isdoc File %s Found in mail from %s '), $attachmentRaw->name, $this->senders[$attachmentRaw->name]));
                    }
                }
            }
        }
        return $isdocs;
    }

    /**
     * Extract ISDOCx from Inbox
     * 
     * @return array Isdoc Attachments on disk
     */
    public function saveIsdocs() {
        $saved = [];
        foreach ($this->pullIsdocs() as $filename => $attachment) {
            $saved[$filename] = $attachment->saveToDisk() ? $attachment->filePath : false;
        }
        return $saved;
    }

}
