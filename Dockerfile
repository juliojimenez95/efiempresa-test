FROM php:8.2-cli-alpine

# Instalar dependencias necesarias para verificar conexiones de red
RUN apk add --no-cache \
    netcat-openbsd \
    bash

# Instalar extensiones de PDO de PHP para MySQL nativo
RUN docker-php-ext-install pdo pdo_mysql

# Definir directorio de trabajo dentro del contenedor
WORKDIR /app

# Copiar todo el código de la aplicación
COPY . /app

# Sanitizar activamente saltos de línea por si el archivo entra en formato CRLF
RUN sed -i 's/\r$//' /app/docker/entrypoint.sh

# Otorgar permisos de ejecución al entrypoint
RUN chmod +x /app/docker/entrypoint.sh

# Exponer el puerto para el servidor web embebido de PHP
EXPOSE 8000

# Punto de entrada por defecto
ENTRYPOINT ["/app/docker/entrypoint.sh"]
