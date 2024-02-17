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

\Ease\Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY', 'ABRAFLEXI_BANK', 'ABRAFLEXI_STORAGE', 'ABRAFLEXI_DOCTYPE'], isset($argv[1]) ? $argv[1] : '../.env');
\Ease\Locale::singleton('cs_CZ', '../i18n', 'abraflexi-email-importer');
\Ease\Logger\Regent::singleton();

$imp = new MailImporter();
if (\Ease\Shared::cfg('APP_DEBUG') == 'True') {
    $imp->logBanner();
}

if ($imp->checkSetup() === true) {
    $imp->importMails();
}
