<?php

/**
 * Imap2AbraFlexi Init
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2019 Vitex Software
 */

namespace AbraFlexi\Imap2AF;

require_once '../vendor/autoload.php';

if (file_exists('../client.json')) {
    \Ease\Shared::singleton()->loadConfig('../client.json', true);
}
if(file_exists('../imap2af.json')){
    \Ease\Shared::singleton()->loadConfig('../imap2af.json', true);
}
\Ease\Locale::singleton('cs_CZ', '../i18n', 'imap2af');
\Ease\Logger\Regent::singleton();

