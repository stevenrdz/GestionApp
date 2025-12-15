#!/usr/bin/env sh
set -e

cd /var/www/app

echo "Esperando a que SQL Server esté listo..."
# Espera simple para dar tiempo a que el contenedor db levante
sleep 15

echo "Ejecutando comandos de Doctrine..."

# Crear BD si no existe (si falla porque ya existe, no rompemos)
php bin/console doctrine:database:create --if-not-exists --no-interaction || true

# Ejecutar migraciones
php bin/console doctrine:migrations:migrate --no-interaction

echo "Iniciando php-fpm..."
exec php-fpm
