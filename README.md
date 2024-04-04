ISDOC files importer From IMAP folder to AbraFlexi
==================================================

![Logo](abraflexi-imap-import.svg?raw=true)

Tool for importing ISDOC/ISDOCx files into AbraFlexi

Features:

 * Create new entries in Addres Book
 * Create new entries in Price List
 * Handle storage moves

[![time tracker](https://wakatime.com/badge/github/VitexSoftware/AbraFlexi-email-importer.svg)](https://wakatime.com/badge/github/VitexSoftware/AbraFlexi-email-importer)

Installation
------------


Method 1) for developers:

```shell
git clone git@github.com:VitexSoftware/AbraFlexi-email-importer.git
cd AbraFlexi-email-importer.git
composer install
```

Method 2) for users:

```shell
wget https://github.com/VitexSoftware/AbraFlexi-email-importer/archive/main.zip
unzip AbraFlexi-email-importer-main.zip
cd AbraFlexi-email-importer-main
composer.phar install
```

Method 3) For admins. Debian and Ubuntu based distros can use our repository to 
install latest version by this commands:

```shell
sudo apt install lsb-release wget
echo "deb http://repo.vitexsoftware.cz $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list
sudo wget -O /etc/apt/trusted.gpg.d/vitexsoftware.gpg http://repo.vitexsoftware.cz/keyring.gpg
sudo apt update
sudo apt install abraflexi-email-importer
```


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

export    FORCE_INCOMING_INVOICE_TYPE="code:Faktura přijatá"
export    ACCEPT_PROVIDER_IDS="ico1,ico2,ico3"
export    DENY_PROVIDER_IDS="icoA,icoB,icoC"

```

 * ABRAFLEXI_BANK      - code of default bank account
 * ABRAFLEXI_STORAGE   - code of default storage
 * ACCEPT_PROVIDER_IDS - Always accept invoice from this IDs
 * DENY_PROVIDER_IDS   - Always denz invoice from this IDs
 * DONE_FOLDER         - Move processed mails here

Imap options described here: https://www.php.net/manual/en/function.imap-open.php

you can also create file /etc/profile.d/abraflexi-email-importer.sh with export
definitions to be ready system wide.


Usage
-----

run command **bin/imap2abraflexi** by hand or periodically to check & import of
new invoices in your mailbox.



Supported by
------------

 * PureHTML - https://purehtml.com/
 * Spoje.Net - https://spoje.net/

MultiFlexi
----------

AbraFlexi eMail importer is ready for run as [MultiFlexi](https://multiflexi.eu) application.
See the full list of ready-to-run applications within the MultiFlexi platform on the [application list page](https://www.multiflexi.eu/apps.php).

[![MultiFlexi App](https://github.com/VitexSoftware/MultiFlexi/blob/main/doc/multiflexi-app.svg)](https://www.multiflexi.eu/apps.php)
