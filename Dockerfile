# the different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target

# "php" stage
FROM php:8.1-fpm-alpine

# persistent / runtime deps
RUN apk add --no-cache \
        gcompat \
        acl \
		fcgi \
		file \
		gettext \
		git \
		gnu-libiconv \
        php-pear \
        autoconf \
        gcc \
        make \
        g++ \
    	libffi-dev \
    	libc-dev \
        zlib-dev \
    	libxml2-dev \
    	unixodbc-dev \
    	curl \
    	gnupg \
    ;

# Download the desired package(s)
#RUN curl -O https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/msodbcsql17_17.6.1.1-1_amd64.apk
#RUN curl -O https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/mssql-tools_17.6.1.1-1_amd64.apk

#RUN curl https://packages.microsoft.com/keys/microsoft.asc  | gpg --import -
#RUN gpg --verify msodbcsql17_17.6.1.1-1_amd64.sig msodbcsql17_17.6.1.1-1_amd64.apk
#RUN gpg --verify mssql-tools_17.6.1.1-1_amd64.sig mssql-tools_17.6.1.1-1_amd64.apk

# Install the package(s)
#RUN apk add --allow-untrusted msodbcsql17_17.6.1.1-1_amd64.apk
#RUN apk add --allow-untrusted mssql-tools_17.6.1.1-1_amd64.apk

RUN curl -O https://download.microsoft.com/download/3/5/5/355d7943-a338-41a7-858d-53b259ea33f5/msodbcsql18_18.3.2.1-1_amd64.apk
RUN curl -O https://download.microsoft.com/download/3/5/5/355d7943-a338-41a7-858d-53b259ea33f5/mssql-tools18_18.3.1.1-1_amd64.apk


#(Optional) Verify signature, if 'gpg' is missing install it using 'apk add gnupg':
RUN curl -O https://download.microsoft.com/download/3/5/5/355d7943-a338-41a7-858d-53b259ea33f5/msodbcsql18_18.3.2.1-1_amd64.sig
RUN curl -O https://download.microsoft.com/download/3/5/5/355d7943-a338-41a7-858d-53b259ea33f5/mssql-tools18_18.3.1.1-1_amd64.sig

RUN curl https://packages.microsoft.com/keys/microsoft.asc  | gpg --import -
RUN gpg --verify msodbcsql18_18.3.2.1-1_amd64.sig msodbcsql18_18.3.2.1-1_amd64.apk
RUN gpg --verify mssql-tools18_18.3.1.1-1_amd64.sig mssql-tools18_18.3.1.1-1_amd64.apk


#Install the package(s)
RUN apk add --allow-untrusted msodbcsql18_18.3.2.1-1_amd64.apk
RUN apk add --allow-untrusted mssql-tools18_18.3.1.1-1_amd64.apk

# install gnu-libiconv and set LD_PRELOAD env to make iconv work fully on Alpine image.
# see https://github.com/docker-library/php/issues/240#issuecomment-763112749
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so
ARG APCU_VERSION=5.1.21

RUN set -eux; \
	apk add --no-cache --virtual .build-deps \
		$PHPIZE_DEPS \
		icu-dev \
		libzip-dev \
		postgresql-dev \
		rabbitmq-c-dev \
        freetype-dev \
        libpng-dev \
        jpeg-dev \
        libjpeg-turbo-dev \
    ; \
	\
	docker-php-ext-configure zip; \
    docker-php-ext-configure gd --with-jpeg; \
	docker-php-ext-install -j$(nproc) \
		intl \
		zip \
		bcmath \
		sockets \
        pdo \
        pdo_mysql \
        pdo_pgsql \
    	gd \
    	soap \
    ; \
	pecl install \
		apcu-${APCU_VERSION} \
        amqp \
    	sqlsrv \
    	pdo_sqlsrv \
    ; \
	pecl clear-cache; \
	docker-php-ext-enable \
		apcu \
		opcache \
        amqp \
    	sqlsrv \
    	pdo_sqlsrv \
    ; \
	\
	runDeps="$( \
		scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
			| tr ',' '\n' \
			| sort -u \
			| awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
	)"; \
	apk add --no-cache --virtual .api-phpexts-rundeps $runDeps; \
	\
	apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

# build for production
ARG APP_ENV=dev

WORKDIR /nelsys-api

ENV SYMFONY_PHPUNIT_VERSION=9
CMD ["php-fpm"]

