# syntax=docker/dockerfile:1

FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN --mount=type=secret,id=composer_auth,required=false \
	if [ -f /run/secrets/composer_auth ]; then \
		export COMPOSER_AUTH="$(cat /run/secrets/composer_auth)"; \
	fi \
	&& composer install \
		--no-dev \
		--no-interaction \
		--prefer-dist \
		--optimize-autoloader \
		--no-scripts \
		--ignore-platform-req=ext-pcntl \
		--ignore-platform-req=ext-sockets

COPY . .

RUN --mount=type=secret,id=composer_auth,required=false \
	if [ -f /run/secrets/composer_auth ]; then \
		export COMPOSER_AUTH="$(cat /run/secrets/composer_auth)"; \
	fi \
	&& composer install \
		--no-dev \
		--no-interaction \
		--prefer-dist \
		--optimize-autoloader \
		--ignore-platform-req=ext-pcntl \
		--ignore-platform-req=ext-sockets

FROM php:8.4-cli-bookworm AS assets

RUN apt-get update \
	&& apt-get install -y --no-install-recommends curl git unzip \
	&& curl -fsSL https://bun.sh/install | bash \
	&& rm -rf /var/lib/apt/lists/*

ENV PATH="/root/.bun/bin:${PATH}"

WORKDIR /app

COPY --from=vendor /app /app

RUN APP_ENV=production \
	APP_KEY=base64:dGVtcG9yYXJ5a2V5Zm9yZG9ja2VyYnVpbGRvbmx5Cg== \
	bun install --frozen-lockfile \
	&& APP_ENV=production \
	APP_KEY=base64:dGVtcG9yYXJ5a2V5Zm9yZG9ja2VyYnVpbGRvbmx5Cg== \
	bun run build

FROM php:8.4-fpm-alpine AS production

RUN apk add --no-cache \
	curl \
	fcgi \
	git \
	icu-dev \
	libpng-dev \
	libxml2-dev \
	libzip-dev \
	linux-headers \
	postgresql-dev \
	zip \
	$PHPIZE_DEPS \
	&& docker-php-ext-configure intl \
	&& docker-php-ext-install -j"$(nproc)" \
		bcmath \
		intl \
		opcache \
		pcntl \
		pdo_pgsql \
		sockets \
		zip \
	&& pecl install redis \
	&& docker-php-ext-enable redis \
	&& apk del --no-cache $PHPIZE_DEPS postgresql-dev \
	&& rm -rf /tmp/pear /var/cache/apk/*

COPY --from=caddy:2-alpine /usr/bin/caddy /usr/bin/caddy

WORKDIR /var/www/html

COPY --from=assets /app /var/www/html
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN mv "${PHP_INI_DIR}/php.ini-production" "${PHP_INI_DIR}/php.ini" \
	&& echo "opcache.enable=1" > "${PHP_INI_DIR}/conf.d/opcache.ini" \
	&& echo "opcache.memory_consumption=128" >> "${PHP_INI_DIR}/conf.d/opcache.ini" \
	&& echo "opcache.interned_strings_buffer=8" >> "${PHP_INI_DIR}/conf.d/opcache.ini" \
	&& echo "opcache.max_accelerated_files=10000" >> "${PHP_INI_DIR}/conf.d/opcache.ini" \
	&& echo "opcache.validate_timestamps=0" >> "${PHP_INI_DIR}/conf.d/opcache.ini" \
	&& chmod +x /usr/local/bin/entrypoint.sh \
	&& chown -R www-data:www-data storage bootstrap/cache \
	&& chmod -R ug+rwx storage bootstrap/cache

EXPOSE 3000

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
	CMD curl -f http://127.0.0.1:3000/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
