#!/bin/sh
set -e

cd /var/www/app

echo "⏳ Esperando a que SQL Server esté listo..."
# Espera simple para dar tiempo a que el contenedor db levante
sleep 15

if [ -f bin/console ]; then
  echo "🗄️ Ejecutando comandos de Doctrine..."

  # Crear BD si no existe (si falla porque ya existe, no rompemos)
  php bin/console doctrine:database:create --if-not-exists --no-interaction || true

  # Ejecutar migraciones
  php bin/console doctrine:migrations:migrate --no-interaction || true
else
  echo "⚠️ No se encontró bin/console, se omiten comandos de Doctrine."
fi

echo "🚀 Iniciando PHP-FPM..."
exec "$@"
