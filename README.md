ISDOC files importer From IMAP folder to FlexiBee
=================================================

![Logo](flexibee-imap-import.svg?raw=true)

Tool for importing ISDOC/ISDOCx files into FlexiBee

Features:

 * Create new entries in Addres Book
 * Create new entries in Price List
 * Handle storage moves



[![time tracker](https://wakatime.com/badge/github/Vitexus/ISDOC-via-IMAP-to-FlexiBee.svg)](https://wakatime.com/badge/github/Vitexus/ISDOC-via-IMAP-to-FlexiBee)


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

export    FLEXIBEE_URL="https://demo.flexibee.eu:5434"
export    FLEXIBEE_LOGIN="winstrom"
export    FLEXIBEE_PASSWORD="winstrom"
export    FLEXIBEE_COMPANY="demo"
export    FLEXIBEE_BANK="BANKA"

```

Imap options described here: https://www.php.net/manual/en/function.imap-open.php


Supported by
------------

 * PureHTML - https://purehtml.com/
 * Spoje.Net - https://spoje.net/

