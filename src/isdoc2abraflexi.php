<?php

/**
 * Imap2AbraFlexi
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2019 Vitex Software
 */

namespace AbraFlexi\Imap2AF;

use Ease\Functions;

define('EASE_APPNAME', 'isdoc2AbraFlexi');

require_once __DIR__ . '/init.php';

$imp = new Importer('file');
$imp->logBanner(\Ease\Shared::appName());
if ($imp->checkSetup() === true) {
    $isdocs = [];
    foreach (glob($argv[1]) as $isdocFile) {
        $isdocs[basename($isdocFile)] = $isdocFile;
    }
    $imp->importIsdocFiles($isdocs, []);
}

