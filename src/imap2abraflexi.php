<?php

/**
 * Imap2AbraFlexi
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2019-2023 Vitex Software
 */

namespace AbraFlexi\Imap2AF;

use Ease\Functions;

define('EASE_APPNAME', 'Imap2AbraFlexi');

require_once __DIR__ . '/init.php';

$imp = new Importer('mail');
$imp->logBanner(\Ease\Shared::appName());
if ($imp->checkSetup() === true) {
    $mailbox = new Mailboxer();
    $imp->importIsdocFiles($mailbox->saveIsdocs(), $mailbox->senders);
}
