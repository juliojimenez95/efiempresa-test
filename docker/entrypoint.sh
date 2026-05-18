#!/bin/sh
set -e

echo "========================================================="
echo "🐳 EFIEMPRESA ERP - CONTENEDOR DE APLICACIÓN INICIANDO"
echo "========================================================="

# Esperar a que la base de datos esté lista en el puerto 3306
echo "🔄 Esperando a que el servicio de base de datos (db:3306) esté en línea..."
until nc -z -w 2 db 3306; do
  echo "⏳ Base de datos no disponible aún. Reintentando en 2 segundos..."
  sleep 2
done

echo "✅¡Servicio de base de datos detectado y conectado!"

# Ejecutar el instalador automático de tablas y semillas de prueba
echo "🚀 Inicializando base de datos EFI (Tablas y Semillas)..."
php scripts/install_db.php

# Levantar el servidor embebido de PHP apuntando a public/
echo "🌐 Levantando servidor web embebido en http://0.0.0.0:8000..."
exec php -S 0.0.0.0:8000 -t public/
