<?php
// Debian autoloader for abraflexi-email-importer
// Load dependency autoloaders
require_once '/usr/share/php/AbraFlexi/autoload.php';
require_once '/usr/share/php/AbraFlexiBricks/autoload.php';
require_once '/usr/share/php/PhpImap/autoload.php';
require_once '/usr/share/php/Lightools/Xml/autoload.php';
require_once '/usr/lib/abraflexi-email-importer/Imap2AF/Convertor.php';
require_once '/usr/lib/abraflexi-email-importer/Imap2AF/Importer.php';
require_once '/usr/lib/abraflexi-email-importer/Imap2AF/Mailboxer.php';
require_once '/usr/lib/abraflexi-email-importer/Imap2AF/MailImporter.php';
require_once '/usr/lib/abraflexi-email-importer/Imap2AF/Parser.php';

require_once '/usr/share/php/Composer/InstalledVersions.php';

(function (): void {
    $versions = [];
    foreach (\Composer\InstalledVersions::getAllRawData() as $d) {
        $versions = array_merge($versions, $d['versions'] ?? []);
    }
    $name    = 'unknown';
    $version = '0.0.0';
    $versions[$name] = ['pretty_version' => $version, 'version' => $version,
        'reference' => null, 'type' => 'library', 'install_path' => __DIR__,
        'aliases' => [], 'dev_requirement' => false];
    \Composer\InstalledVersions::reload([
        'root' => ['name' => $name, 'pretty_version' => $version, 'version' => $version,
            'reference' => null, 'type' => 'project', 'install_path' => __DIR__,
            'aliases' => [], 'dev' => false],
        'versions' => $versions,
    ]);
})();
