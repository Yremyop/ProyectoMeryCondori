#!/usr/bin/env bash
# Arma el paquete para InfinityFree (hosting sin consola ni Composer):
#   salida/1-bordados-app.zip      app + vendor de producción + .env + .htaccess (descomprimir en htdocs)
#   salida/2-fotos-productos.zip   fotos del catálogo (descomprimir en htdocs)
#   salida/bordados_teo.sql        base completa: tablas, demo y catálogo (importar en phpMyAdmin)
#   salida/contrasenas.txt         contraseñas de admin y vendedor en el hosting
# Requiere los contenedores levantados: docker compose up -d
# Uso (desde la raíz del proyecto): bash deploy/infinityfree/empaquetar.sh
set -euo pipefail

cd "$(dirname "$0")/../.."
AQUI=deploy/infinityfree
OUT="$AQUI/salida"
APP="$OUT/htdocs"
BD=hosting_build

docker compose exec -T db mysqladmin -uroot ping >/dev/null

# Se generan una sola vez y se reutilizan: así el hosting no cambia de clave ni de contraseñas en cada paquete.
mkdir -p "$OUT"
if [ ! -f "$OUT/.env.production" ]; then
  KEY=$(docker compose run --rm --no-deps -T app php -r 'echo "base64:".base64_encode(random_bytes(32));')
  cat > "$OUT/.env.production" <<EOF
APP_NAME="Bordados T&O"
APP_ENV=production
APP_KEY=$KEY
APP_DEBUG=false
# Cambia por la dirección que te dio InfinityFree (con https si activaste SSL)
APP_URL=https://TU-SITIO.infinityfreeapp.com
APP_TIMEZONE=America/La_Paz
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=single
LOG_LEVEL=error

# Datos de "MySQL Databases" en el panel de InfinityFree
DB_CONNECTION=mysql
DB_HOST=sqlXXX.infinityfree.com
DB_PORT=3306
DB_DATABASE=if0_XXXXXXXX_bordados
DB_USERNAME=if0_XXXXXXXX
DB_PASSWORD=TU_CONTRASEÑA_DEL_PANEL

SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public
MAIL_MAILER=log
EOF
fi
if [ ! -f "$OUT/contrasenas.txt" ]; then
  for email in admin@bordadosteo.com vendedor@bordadosteo.com; do
    echo "$email $(LC_ALL=C tr -dc 'A-HJ-NP-Za-km-z2-9' </dev/urandom | head -c 14)"
  done > "$OUT/contrasenas.txt"
fi

echo "1/4 Copiando el proyecto (incluye cambios sin commit)…"
rm -rf "$APP" "$OUT"/*.zip "$OUT/bordados_teo.sql"
mkdir -p "$APP"
git ls-files -co --exclude-standard \
  | grep -v -E '^(tests/|deploy/|Dockerfile$|docker-compose\.yml$|phpunit\.xml$|\.env\.example$)' \
  | rsync -a --files-from=- ./ "$APP/"
cp "$AQUI/.htaccess" "$APP/.htaccess"
cp "$OUT/.env.production" "$APP/.env"

# Contenedor de la app trabajando sobre la copia (no sobre el proyecto).
run() { docker compose run --rm --no-deps -T -v "$PWD/$APP":/app -w /app "$@"; }

echo "2/4 Instalando dependencias de producción…"
run app composer install --no-dev --optimize-autoloader --no-interaction --quiet

echo "3/4 Generando la base (migraciones + demo + catálogo con fotos)…"
trap 'docker compose exec -T db mysql -uroot -e "DROP DATABASE IF EXISTS $BD" >/dev/null 2>&1 || true' EXIT
docker compose exec -T db mysql -uroot -e "DROP DATABASE IF EXISTS $BD; CREATE DATABASE $BD CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
run -e DB_HOST=db -e DB_DATABASE=$BD -e DB_USERNAME=root -e DB_PASSWORD= app php artisan migrate --seed --force
# Las contraseñas de demostración (admin123…) están en el README público: se reemplazan.
# Se lee por el descriptor 3: docker compose consume la entrada estándar y cortaría el bucle tras el primer usuario.
while read -r email pw <&3; do
  hash=$(run app php -r 'echo password_hash($argv[1], PASSWORD_BCRYPT, ["cost" => 12]);' "$pw")
  docker compose exec -T db mysql -uroot "$BD" -e "UPDATE usuarios SET password='$hash' WHERE email='$email'"
done 3< "$OUT/contrasenas.txt"
# Verificación: el paquete no sale si algún usuario conserva la contraseña de demostración.
for demo in admin123 vendedor123; do
  hashes=$(docker compose exec -T db mysql -uroot -N "$BD" -e "SELECT password FROM usuarios")
  for h in $hashes; do
    if [ "$(run app php -r 'echo (int) password_verify($argv[1], $argv[2]);' "$demo" "$h" </dev/null)" = 1 ]; then
      echo "ERROR: un usuario sigue con la contraseña '$demo'." >&2; exit 1
    fi
  done
done
docker compose exec -T db mysqldump -uroot --single-transaction --skip-add-locks --no-tablespaces \
  --set-gtid-purged=OFF --default-character-set=utf8mb4 "$BD" > "$OUT/bordados_teo.sql"

echo "4/4 Comprimiendo…"
rm -rf "$APP/database/seeders/catalogo" # en el hosting no se usa: datos en el .sql, fotos en el zip 2
chmod -R u+rwX,go+rX "$APP"
# Dos zips: el File Manager de InfinityFree limita el tamaño de cada archivo subido (~10 MB).
(cd "$APP" \
  && zip -qr -X ../1-bordados-app.zip . -x 'storage/app/public/productos/*' \
  && zip -qr -X ../2-fotos-productos.zip storage/app/public/productos)

ls -lh "$OUT"/*.zip "$OUT/bordados_teo.sql"
echo "Listo. Sigue los pasos de \"Deploy en InfinityFree\" en el README."
