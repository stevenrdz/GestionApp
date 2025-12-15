FROM php:8.4-fpm 

# 1. Dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libxml2-dev \
    gnupg \
    autoconf \
    build-essential \
    locales \
    apt-transport-https \
    unixodbc-dev \
    wget \
    ca-certificates \
    tzdata \
    && docker-php-ext-install intl opcache \
    && rm -rf /var/lib/apt/lists/*

ENV TZ=America/Guayaquil
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# 2. Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 3. Instalar driver ODBC de Microsoft y extensiones sqlsrv/pdo_sqlsrv
RUN set -eux; \
    apt-get update; \
    wget -qO- https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor > /usr/share/keyrings/microsoft.gpg; \
    echo "deb [signed-by=/usr/share/keyrings/microsoft.gpg arch=amd64] https://packages.microsoft.com/debian/12/prod bookworm main" > /etc/apt/sources.list.d/mssql-release.list; \
    apt-get update; \
    ACCEPT_EULA=Y apt-get install -y msodbcsql18; \
    pecl install sqlsrv pdo_sqlsrv; \
    docker-php-ext-enable sqlsrv pdo_sqlsrv; \
    rm -rf /var/lib/apt/lists/*

# 4. Config PHP
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory_limit.ini \
    && echo "date.timezone=America/Guayaquil" > /usr/local/etc/php/conf.d/timezone.ini

WORKDIR /var/www/app

RUN useradd -m appuser && chown -R appuser:appuser /var/www/app
USER appuser

# 5. EntryPoint: ejecutar migraciones y luego php-fpm
COPY --chown=appuser:appuser entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]


