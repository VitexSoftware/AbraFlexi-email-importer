<?php
/**
 * Imap2FlexiBee
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2019 Vitex Software
 */

namespace FlexiPeeHP\Imap2FB;

define('EASE_APPNAME', 'Imap2FlexiBee');
define('EASE_LOGGER','syslog|console');

require_once __DIR__.'/init.php';


$imp = new Importer();
$imp->logBanner(constant('EASE_APPNAME'));
$mailbox = new Mailboxer();

$imp->importIsdocFiles( $mailbox->saveIsdocs(), $mailbox->senders );



