# Bordados T&O — Sistema de Gestión de Ventas (Laravel 12)

Conversión del proyecto PHP **BORDADOSTEO** a Laravel. Mantiene el mismo diseño (CSS/JS originales),
los mismos módulos y las mismas reglas de negocio, con las correcciones que se listan abajo.

## Puesta en marcha

Requisitos: **PHP 8.2+** (con extensiones `mbstring`, `pdo_mysql`, `fileinfo`), **Composer 2** y MySQL/MariaDB (XAMPP sirve).
Comprueba la versión con `php -v`: si tu XAMPP trae PHP 8.1 o menor, actualízalo.

```bash
composer install
cp .env.example .env            # en Windows: copy .env.example .env
php artisan key:generate
# En .env: DB_DATABASE=bordados_teo  DB_USERNAME=root  DB_PASSWORD=   (crea la base vacía antes)
php artisan migrate --seed      # crea las tablas y datos de demostración
php artisan storage:link        # para ver las imágenes de productos
php artisan serve               # http://localhost:8000
```

### Con Docker

Solo necesitas Docker Desktop (no hace falta PHP ni MySQL instalados).

```bash
cp .env.example .env            # en Windows: copy .env.example .env
# En .env cambia DB_HOST=127.0.0.1 por DB_HOST=db
docker compose up -d --build    # la primera vez instala las dependencias (tarda unos minutos)
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
# Abre http://localhost:8000
```

Pruebas: `docker compose exec app php artisan test`. Para detenerlo: `docker compose down`
(los datos quedan en el volumen `db-data`; `docker compose down -v` los borra).

Usuarios de demostración (**cámbialos o bórralos antes de publicar**):
`admin@bordadosteo.com` / `admin123` (Administrador) · `vendedor@bordadosteo.com` / `vendedor123` (Vendedor).

Pruebas automáticas: `php artisan test` (login, bloqueo, permisos, ventas, pedidos, inventario, reportes).

## Si ya tienes datos en tu base `bordados_teo1`

1. Crea una base nueva y ejecuta `php artisan migrate` (sin `--seed`).
2. Exporta solo los datos de la base vieja (`mysqldump --no-create-info --complete-insert bordados_teo1 > datos.sql`) e impórtalos en la nueva.
3. Si algún usuario tenía la contraseña en texto plano: `php artisan usuarios:hashear-legacy` (reemplaza a `setup_hash_passwords.php`).
4. Copia tus imágenes a `storage/app/public/productos/` (ya vienen las 20 del proyecto original).
5. La tabla `intentos_login` ya no existe: el bloqueo por fuerza bruta usa el caché de Laravel.

## Equivalencias

| PHP original | Laravel |
|---|---|
| `login.php`, `logout.php`, `includes/auth.php` | `Auth\LoginController` (sesión regenerada al entrar, logout por POST) |
| `index.php` | `DashboardController` + `resources/views/dashboard.blade.php` |
| `clientes.php`, `productos.php`, `inventario.php` | `ClienteController`, `ProductoController`, `InventarioController` |
| `ventas.php`, `factura.php` | `VentaController`, `FacturaController` (+ `app/Support/Pdfs.php`, `SimplePdf.php`) |
| `pedidos.php` | `PedidoController` |
| `reportes.php`, `usuarios.php`, `buscar.php` | `ReporteController`, `UsuarioController`, `BusquedaController` (el cotizador se retiró) |
| `includes/seguridad.php` | CSRF de Laravel, `SecurityHeaders` (CSP, X-Frame-Options…), `throttle` (límite de peticiones), `RateLimiter` (bloqueo de login), bitácora `log_seguridad`, middleware `admin` |
| `includes/ui.php` (`icono`, `dinero`, `fechaCorta`…) | `app/helpers.php` |
| consultas PDO | Eloquent / Query Builder (consultas parametrizadas) |
| `database/bordados_teo.sql` | `database/migrations/` + `database/seeders/` |

Ajustes de seguridad en `.env`: `LIMITE_PETICIONES_MINUTO`, `LIMITE_LOGIN_MINUTO`, `MAX_INTENTOS_LOGIN`, `BLOQUEO_LOGIN_SEGUNDOS`.

## Roles

- **Administrador:** todo el sistema; además Reportes, Usuarios (alta, activar/desactivar, cambiar rol), alta/edición/desactivación de productos, stock y precios (en Inventario), eliminar clientes y cambiar el estado de pedidos.
- **Vendedor:** Dashboard, Clientes (alta y edición), Catálogo e Inventario en solo lectura, Ventas y Pedidos.

## Correcciones respecto al original

- **Esquema completo.** El `.sql` no tenía columnas que el código usaba (`productos.codigo/color/imagen/activo`, `clientes.documento`, `pedidos.total_pedido`, tabla `pedido_items`, estado `Cancelado`). La migración las incluye.
- **Filtros de Catálogo e Inventario.** El `execute($params)` estaba comentado: los filtros no se aplicaban. Ahora funcionan.
- **Reportes.** `SUM(v.total)` sobre un JOIN con el detalle multiplicaba los importes de ventas con varias líneas; los totales por cliente y por periodo ahora son exactos. Se arreglaron también la lista de colores (variable sin definir) y el filtro "hasta" (excluía el último día en algunos motores).
- **Pedidos.** El catálogo del formulario no se cargaba (consulta comentada). Un pedido ya **Entregado o Cancelado ya no puede venderse otra vez** (antes se podía facturar y descontar stock dos veces).
- **Ventas.** Ya no se pueden vender productos desactivados. La validación de stock suma las líneas repetidas del mismo producto (antes podía quedar stock negativo) y bloquea las filas en la transacción.
- **Eliminar cliente** es ahora una petición `DELETE` con CSRF (antes era un enlace GET) y, si tiene ventas o pedidos, muestra un mensaje en vez de un error fatal. **Desactivar producto** igual.
- **Código de producto automático** y consecutivo por categoría + color (ej. `EAROJ001`); no cambia al editar. Las imágenes se guardan con nombre único (antes un nombre repetido mostraba la imagen equivocada).
- Pedidos: el resumen se recorta a 200 caracteres (la columna es `varchar(200)`; antes fallaba con muchos productos). "Saldo por cobrar" del dashboard ya no cuenta pedidos cancelados.
- **PDF** con tildes/ñ correctas (WinAnsi) y varias páginas (el original desbordaba la hoja).

## Cosas a tener en cuenta

- El `config.php` original tenía, comentada, una **contraseña de la base en InfinityFree**. No se copió aquí; como estaba en el ZIP, cámbiala en tu hosting.
- Se omitió el alta rápida de cliente desde Ventas (`guardar_cliente`): el código existía pero la pantalla nunca tuvo ese formulario. Los clientes se crean en *Clientes*.
- El límite de peticiones es ahora uno general por usuario (60/min) y otro para login (7/min); antes cada página tenía su propio tope.
- Producción: `APP_ENV=production`, `APP_DEBUG=false`, y apunta el servidor web a la carpeta `public/`.
