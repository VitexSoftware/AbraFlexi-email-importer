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

require_once '../vendor/autoload.php';

\define('EASE_APPNAME', 'isdoc2AbraFlexi');

\Ease\Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY', 'ABRAFLEXI_BANK', 'ABRAFLEXI_STORAGE'], $argv[1] ?? '../.env');
\Ease\Locale::singleton('cs_CZ', '../i18n', 'abraflexi-email-importer');
\Ease\Logger\Regent::singleton();

$imp = new Importer('file');

if (\Ease\Shared::cfg('APP_DEBUG') === 'True') {
    $imp->logBanner(\Ease\Shared::appName().' v'.\Ease\Shared::appVersion());
}

if ($imp->checkSetup() === true) {
    $isdocs = [];

    foreach (glob($argv[1]) as $isdocFile) {
        $isdocs[basename($isdocFile)] = $isdocFile;
    }

    $imp->importIsdocFiles($isdocs, []);
}
