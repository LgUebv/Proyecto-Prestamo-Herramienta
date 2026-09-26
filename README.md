# SISTEMA DE GESTIÓN PARA PRÉSTAMO DE HERRAMIENTAS

Proyecto desarrollado por:

- Luis Julián Hernández Trejo
- José Manuel Vega Torres

## Pasos para crear el entorno de desarrollo

1.Clonar el repositorio: git clone https://github.com/LgUebv/Proyecto-Prestamo-Herramienta

1. Descargar e instalar dependencias que el proyecto necesita para funcionar: composer install

2. Copiar y crear un nuevo .env:

Para Linux: cp .env.example .env

5. Generar la llave: php artisan key:generate

6. Probar si puede arrancar el servidor: php artisan serve

7. las credenciales necesarias son: 

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=herramientas_db
DB_USERNAME=
DB_PASSWORD=

Para la ejecución del proyecto en entornos de desarrollo se recomienda usar los siguientes comandos en terminales diferentes:

- `php artisan serve`
- `npm install` y luego: `npm run dev`

### DEPENDENCIAS PARA EL PROYECTO

Para que el generador de códigos QR para las herramientas se necesita la herramienta **php-imagick**. Puedes instalarla
usando el siguiente comando (Fedora):

```bash
sudo dnf install php-imagick
```

## Ejecución con Docker

El proyecto incluye dos contenedores definidos en `compose.yaml`:

| Servicio | Imagen | Descripción |
|----------|--------|-------------|
| `app` | Construida con el `Dockerfile` (PHP 8.4 + Apache) | Aplicación Laravel con los assets de Vite ya compilados. Incluye las extensiones `pdo_mysql`, `gd`, `zip`, `intl`, `bcmath`, `imagick` y `opcache`. |
| `db`  | `mysql:8.4` | Base de datos MySQL. Los datos persisten en el volumen `db-data`. |

> El flujo de desarrollo local (`php artisan serve` + `npm run dev`) no cambia: Docker usa su propio archivo `.env.docker` y no lee ni modifica tu `.env`.

### Requisitos

- Docker Engine 24+ con el plugin Docker Compose v2 (`docker compose version`).

### Primera ejecución

1. Crear el archivo de configuración para Docker:

   ```bash
   cp .env.docker.example .env.docker
   ```

2. Editar `.env.docker` y cambiar las contraseñas (`DB_PASSWORD`/`MYSQL_PASSWORD` deben ser iguales, y también `MYSQL_ROOT_PASSWORD`).

3. Construir la imagen y generar la llave de la aplicación:

   ```bash
   docker compose build
   docker compose run --rm --no-deps app php artisan key:generate --show
   ```

   Copiar el valor que se imprime (`base64:...`) en `APP_KEY` dentro de `.env.docker`.

4. Levantar los contenedores:

   ```bash
   docker compose up -d
   ```

   Al arrancar, el contenedor `app` espera a que MySQL esté listo, ejecuta las migraciones y genera las cachés de Laravel.

5. Abrir <http://localhost:8000>.

### Comandos útiles

```bash
docker compose ps                         # Estado de los contenedores
docker compose logs -f app                # Ver logs de la aplicación
docker compose exec app php artisan ...   # Ejecutar comandos de Artisan
docker compose exec db mysql -u herramientas -p herramientas_db   # Consola de MySQL
docker compose down                       # Detener (los datos se conservan)
docker compose down -v                    # Detener y BORRAR la base de datos
```

Después de modificar el código, reconstruir y reiniciar con:

```bash
docker compose up -d --build
```

### Configuración adicional

- **Puerto de la app**: por defecto `8000`. Para cambiarlo: `APP_PORT=8080 docker compose up -d` (y actualizar `APP_URL` en `.env.docker`).
- **Acceso a MySQL desde el equipo** (DBeaver, Workbench, etc.): `127.0.0.1:3307`. Se usa 3307 para no chocar con un MySQL local; se puede cambiar con `DB_FORWARD_PORT`.
- **Migraciones automáticas**: se desactivan con `RUN_MIGRATIONS=false` en `.env.docker`.
- **Importar una base existente**: `docker compose exec -T db mysql -u root -p"<MYSQL_ROOT_PASSWORD>" herramientas_db < respaldo.sql`
