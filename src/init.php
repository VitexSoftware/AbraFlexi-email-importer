<?php

/**
 * Imap2FlexiBee Init
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2019 Vitex Software
 */

namespace FlexiPeeHP\Imap2FB;

require_once '../vendor/autoload.php';

if (file_exists('../client.json')) {
    \Ease\Shared::singleton()->loadConfig('../client.json', true);
}
if(file_exists('../imap2fb.json')){
    \Ease\Shared::singleton()->loadConfig('../imap2fb.json', true);
}
\Ease\Locale::singleton('cs_CZ', '../i18n', 'imap2fb');
\Ease\Logger\Regent::singleton();

