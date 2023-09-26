<?php

/**
 * Imap2AbraFlexi Init
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2019-2023 Vitex Software
 */

namespace AbraFlexi\Imap2AF;

require_once '../vendor/autoload.php';

\Ease\Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY','ABRAFLEXI_BANK','ABRAFLEXI_STORAGE'], isset($argv[1]) ? $argv[1] : '../.env');
\Ease\Locale::singleton('cs_CZ', '../i18n', 'imap2af');
\Ease\Logger\Regent::singleton();
