# Módulo de licitaciones

Prueba técnica FullStack Suplos: PHP 8.1+ con MVC propio (sin Laravel, CodeIgniter ni Symfony como aplicación), Eloquent independiente y frontend en Vue 3, Axios y Bootstrap 5.

## Qué hace

- Crear una licitación. El consecutivo lo genera el backend con el formato `O-0001-25`.
- Editarla y adjuntar documentos PDF o ZIP. En edición hace falta al menos un documento.
- Listar con filtros por consecutivo, objeto o descripción, y paginación de 10.
- Exportar a Excel desde el backend, con el mismo filtro y sin paginar.
- Ver el detalle en pestañas: información básica, cronograma y documentos.

El estado no tiene un flujo en el enunciado. Se calcula con el cronograma: Programada, En curso o Cerrada. La columna `estado` se actualiza al guardar y la API lo vuelve a calcular al consultar.

La actividad es un buscador sobre la maestra UNSPSC (unas 49.000 filas). Un select con todas congelaría el navegador; el control se habilita cuando responde la primera carga y filtra en el servidor.

## Requisitos

- PHP 8.1 o superior, con `pdo_mysql`, `mbstring`, `fileinfo`, `bcmath` y `zip`.
- Composer.
- MySQL 8 o MariaDB, con el servicio iniciado.
- Node.js 20 o superior.

## Puesta en marcha

Desde la raíz del proyecto, con `php`, `composer` y `mysql` en el PATH:

```powershell
copy .env.example .env
composer install
cmd /c "mysql -uroot --default-character-set=utf8mb4 < bd\schema.sql && mysql -uroot --default-character-set=utf8mb4 < bd\actividades.sql"
php -S 127.0.0.1:8000 -t public public\index.php
```

El servidor de PHP tiene que arrancar en la raíz del proyecto y usar `public\index.php` como enrutador. Sin ese archivo final, las rutas `/api` no responden.

En otra terminal:

```powershell
cd frontend
npm install
npm run dev
```

Abra `http://127.0.0.1:5173`. Vite reenvía `/api` a `http://127.0.0.1:8000`.

Al terminar, el listado abre vacío y la actividad ya puede buscar entre los 49.022 productos UNSPSC. Esos productos viajan en `bd/actividades.sql`; no hay que descargar el Excel. Esos dos SQL se ejecutan una sola vez: si las tablas ya existen, el segundo intento falla.

Si MySQL no usa `root` sin contraseña, cambie `DB_USERNAME` y `DB_PASSWORD` en `.env` y use el mismo usuario en el comando `mysql`. Compruebe la API en `http://127.0.0.1:8000/api/salud`; debe responder `{"ok":true}`.

`bd/importar_actividades.php` solo sirve para volver a leer el Excel oficial si la maestra cambia y todavía no hay ofertas.

En este equipo los programas están en Laragon y no en el PATH:

```powershell
copy .env.example .env
C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe C:\laragon\bin\composer\composer.phar install
cmd /c '"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -uroot --default-character-set=utf8mb4 < bd\schema.sql && "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -uroot --default-character-set=utf8mb4 < bd\actividades.sql'
C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe -S 127.0.0.1:8000 -t public public\index.php
```

Comprobaciones de validación, sin base de datos:

```powershell
php tests\validaciones.php
```

## API

| Método | Ruta | Uso |
| --- | --- | --- |
| GET | `/api/salud` | Comprueba la conexión |
| GET | `/api/actividades?q=` | Busca hasta 30 actividades |
| GET | `/api/ofertas` | Listado paginado |
| GET | `/api/ofertas/exportar` | Excel del filtro actual |
| GET | `/api/ofertas/{id}` | Detalle |
| POST | `/api/ofertas` | Creación en JSON |
| POST | `/api/ofertas/{id}` | Edición multipart, con archivos |
| GET | `/api/documentos/{id}` | Descarga |

La edición usa POST porque PHP solo llena `$_FILES` en POST. Los documentos nuevos se acumulan en pantalla y viajan en ese guardado.

Los archivos quedan en `storage/ofertas`, fuera de `public`, y solo se descargan por la API.

## Decisiones que el enunciado deja abiertas

- Consecutivo: se sigue el ejemplo `O-0001-25`, no el prefijo `PO-` que aparece una sola vez y contradice el ejemplo.
- Tope de archivo: 10 MB. El PDF no lo fija. Se cambia con `UPLOAD_MAX_MB`.
- Zona horaria: `America/Bogota`.
