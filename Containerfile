# abraflexi-email-importer

FROM php:8.2-cli
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && install-php-extensions gettext intl zip imap
COPY src /usr/src/abraflexi-email-importer/src
RUN sed -i -e 's/..\/.env//' /usr/src/abraflexi-email-importer/src/*.php
COPY composer.json /usr/src/abraflexi-email-importer
WORKDIR /usr/src/abraflexi-email-importer
RUN curl -s https://getcomposer.org/installer | php
RUN ./composer.phar install
WORKDIR /usr/src/abraflexi-email-importer/src
CMD [ "php", "./abraflexi-email-importer.php" ]
