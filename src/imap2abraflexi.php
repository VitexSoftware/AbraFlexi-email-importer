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

use Ease\Shared;

require_once '../vendor/autoload.php';

\define('EASE_APPNAME', 'Imap2AbraFlexi');

/**
 * Get today's Statements list.
 */
$options = getopt('o::e::', ['output::environment::']);
Shared::init(
    ['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY', 'ABRAFLEXI_BANK', 'ABRAFLEXI_STORAGE', 'ABRAFLEXI_DOCTYPE'],
    \array_key_exists('environment', $options) ? $options['environment'] : (\array_key_exists('e', $options) ? $options['e'] : '../.env'),
);
$destination = \array_key_exists('o', $options) ? $options['o'] : (\array_key_exists('output', $options) ? $options['output'] : \Ease\Shared::cfg('RESULT_FILE', 'php://stdout'));
$exitcode = 0;

\Ease\Locale::singleton('cs_CZ', '../i18n', 'abraflexi-email-importer');
\Ease\Logger\Regent::singleton();

$imp = new MailImporter();

if (Shared::cfg('APP_DEBUG') === 'True') {
    $imp->logBanner();
}

if ($imp->checkSetup() === true) {
    $report = $imp->importMails();
} else {
    $exitcode = 1;
}

$report['exitcode'] = $exitcode;
$written = file_put_contents($destination, json_encode($report, Shared::cfg('DEBUG') ? \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE : 0));
$imp->addStatusMessage(sprintf(_('Saving result to %s'), $destination), $written ? 'success' : 'error');

exit($exitcode);
