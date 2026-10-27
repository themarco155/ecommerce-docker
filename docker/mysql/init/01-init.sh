#!/bin/sh
# Script de inicialización de MySQL.
# Se ejecuta UNA sola vez: cuando el volumen de datos está vacío (primer arranque).
# La imagen oficial ya creó la base MYSQL_DATABASE y el usuario MYSQL_USER.
set -e

mysql --protocol=socket -uroot -p"$MYSQL_ROOT_PASSWORD" <<-EOSQL
  -- Juego de caracteres completo (acentos, ñ y emojis)
  ALTER DATABASE \`${MYSQL_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

  -- Mínimo privilegio: el usuario de la API solo administra SU base de datos
  REVOKE ALL PRIVILEGES, GRANT OPTION FROM '${MYSQL_USER}'@'%';
  GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES, LOCK TABLES
    ON \`${MYSQL_DATABASE}\`.* TO '${MYSQL_USER}'@'%';
  FLUSH PRIVILEGES;
EOSQL

echo "Base ${MYSQL_DATABASE} lista (utf8mb4) y permisos de ${MYSQL_USER} ajustados."
