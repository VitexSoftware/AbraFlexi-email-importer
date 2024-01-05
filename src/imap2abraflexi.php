<?php

/**
 * Imap2AbraFlexi
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2019-2024 Vitex Software
 */

namespace AbraFlexi\Imap2AF;

require_once '../vendor/autoload.php';

define('EASE_APPNAME', 'Imap2AbraFlexi');

\Ease\Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY', 'ABRAFLEXI_BANK', 'ABRAFLEXI_STORAGE'], isset($argv[1]) ? $argv[1] : '../.env');
\Ease\Locale::singleton('cs_CZ', '../i18n', 'imap2af');
\Ease\Logger\Regent::singleton();

$imp = new Importer('mail');
if (\Ease\Functions::cfg('APP_DEBUG') == 'True') {
    $imp->logBanner(\Ease\Shared::appName() . ' v' . \Ease\Shared::appVersion());
}

if ($imp->checkSetup() === true) {
    $mailbox = new Mailboxer();
    $imp->importIsdocFiles($mailbox->saveIsdocs(), $mailbox->senders);
}
