ISDOC files importer From IMAP folder to AbraFlexi
=================================================

![Logo](abraflexi-imap-import.svg?raw=true)

Tool for importing ISDOC/ISDOCx files into AbraFlexi

Features:

 * Create new entries in Addres Book
 * Create new entries in Price List
 * Handle storage moves



[![time tracker](https://wakatime.com/badge/github/VitexSoftware/AbraFlexi-email-importer.svg)](https://wakatime.com/badge/github/VitexSoftware/AbraFlexi-email-importer)


Configuration
-------------

Constants/Environment to set

```shell
export    IMAP_SERVER="string"
export    IMAP_PORT="integer"
export    IMAP_LOGIN="string"
export    IMAP_PASSWORD="password"
export    IMAP_MAILBOX="string"
export    IMAP_OPTIONS="imap/ssl"

export    ABRAFLEXI_URL="https://demo.flexibee.eu:5434"
export    ABRAFLEXI_LOGIN="winstrom"
export    ABRAFLEXI_PASSWORD="winstrom"
export    ABRAFLEXI_COMPANY="demo"
export    ABRAFLEXI_BANK="BANKA"
export    ABRAFLEXI_STORAGE="SKLAD"

```

 * ABRAFLEXI_BANK    - code of default bank account
 * ABRAFLEXI_STORAGE - code of default storage

Imap options described here: https://www.php.net/manual/en/function.imap-open.php


Supported by
------------

 * PureHTML - https://purehtml.com/
 * Spoje.Net - https://spoje.net/

