AbraFlexi Email Importer
=======================

![Logo](social-preview.svg?raw=true)

Tool for importing ISDOC/ISDOCx invoice files from IMAP email attachments into AbraFlexi accounting system.

Features:

 * Automatically monitors IMAP mailbox for new invoices
 * Supports both ISDOC and ISDOCx file formats
 * Creates new entries in Address Book for unknown suppliers
 * Creates new entries in Price List for unknown items
 * Handles storage moves and inventory management
 * Moves processed emails to designated folder
 * Comprehensive logging and result reporting
 * MultiFlexi platform ready

[![time tracker](https://wakatime.com/badge/github/VitexSoftware/AbraFlexi-email-importer.svg)](https://wakatime.com/badge/github/VitexSoftware/AbraFlexi-email-importer)

Configuration
-------------

Constants/Environment variables to set:

### IMAP Configuration
```shell
export IMAP_SERVER="your.imap.server.com"    # IMAP server hostname
export IMAP_PORT=993                          # IMAP server port (integer)
export IMAP_LOGIN="your.email@domain.com"    # IMAP login/email
export IMAP_PASSWORD="your_password"         # IMAP password
export IMAP_MAILBOX="INBOX"                  # IMAP mailbox to monitor
export IMAP_OPTIONS="imap/ssl"               # IMAP connection options
```

### AbraFlexi Configuration
```shell
export ABRAFLEXI_URL="https://demo.flexibee.eu:5434"  # AbraFlexi server URL
export ABRAFLEXI_LOGIN="winstrom"                     # AbraFlexi username
export ABRAFLEXI_PASSWORD="winstrom"                  # AbraFlexi password
export ABRAFLEXI_COMPANY="demo_de"                    # AbraFlexi company database
export ABRAFLEXI_STORAGE="SKLAD"                      # Default storage code
export ABRAFLEXI_DOCTYPE="FAKTURA"                    # Document type code
```

### Processing Configuration
```shell
export DONE_FOLDER="ImportedInvoices"        # Move processed emails here
export RESULT_FILE="result.txt"              # File with import results
export EASE_LOGGER="imap2abraflexi.log"      # Log file name
```

### Optional Filters
```shell
export ACCEPT_PROVIDER_IDS="ico1,ico2,ico3"  # Always accept invoices from these IDs
export DENY_PROVIDER_IDS="icoA,icoB,icoC"    # Always deny invoices from these IDs
```

### Environment Variable Details:
 * **IMAP_PORT** - Must be an integer (e.g., 993 for SSL, 143 for standard)
 * **ABRAFLEXI_STORAGE** - Code of default storage for imported items
 * **ABRAFLEXI_DOCTYPE** - Code of the document type for received invoices
 * **DONE_FOLDER** - IMAP folder where processed emails are moved
 * **RESULT_FILE** - File path where import results are logged
 * **ACCEPT_PROVIDER_IDS** - Comma-separated list of provider IDs to always accept
 * **DENY_PROVIDER_IDS** - Comma-separated list of provider IDs to always deny

IMAP options are described here: https://www.php.net/manual/en/function.imap-open.php

You can also create file `/etc/profile.d/abraflexi-email-importer.sh` with export
definitions to be ready system-wide.

Requirements
------------

### System Requirements
- PHP 8.1 or higher
- IMAP extension
- ZIP extension  
- Access to AbraFlexi server

### Supported File Formats
- **ISDOC** (.isdoc) - XML-based invoice format
- **ISDOCx** (.isdocx) - Compressed ISDOC format with attachments

### Email Processing
- Monitors specified IMAP mailbox for new emails
- Extracts ISDOC/ISDOCx attachments from emails
- Processes invoices and imports them to AbraFlexi
- Moves processed emails to designated folder


### Command Line Usage


Usage
-----

### Command Line Usage

Run the email importer manually:
```bash
bin/imap2abraflexi
```

Import a specific ISDOC file:
```bash
bin/isdoc2abraflexi /path/to/invoice.isdoc
```

### Automated Usage

For periodic imports, set up a cron job:
```bash
# Check for new invoices every 15 minutes
*/15 * * * * /usr/bin/imap2abraflexi
```

### MultiFlexi Usage

When deployed via MultiFlexi, the application can be configured through the web interface with all necessary environment variables.

Supported by
------------

 * PureHTML - https://purehtml.com/
 * Spoje.Net - https://spoje.net/

MultiFlexi
----------

AbraFlexi Email importer is ready to run as [MultiFlexi](https://multiflexi.eu) application.
The application includes two MultiFlexi configurations:

1. **Email Importer** (`isdoc_email_importer`) - Imports ISDOC files from IMAP mailbox
2. **File Importer** (`isdoc_file_importer`) - Imports individual ISDOC files

Both configurations are fully compliant with MultiFlexi application schema v1.19.

See the full list of ready-to-run applications within the MultiFlexi platform on the [application list page](https://www.multiflexi.eu/apps.php).

[![MultiFlexi App](https://github.com/VitexSoftware/MultiFlexi/blob/main/doc/multiflexi-app.svg)](https://www.multiflexi.eu/apps.php)

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
