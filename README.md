# Gestión Docente

Sistema de gestión académica por profesor, año/ciclo, grupo, curso y modalidad TEC/TALL. Está desarrollado con Laravel 13, PHP 8.3 y MySQL 8, y preparado para ejecutarse en Windows con Laragon.

## Requisitos

- Windows 10/11 y Laragon.
- PHP 8.3 o superior.
- MySQL 8 con InnoDB.
- Composer.
- Extensiones PHP: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip`, `gd`, `xml`, `dom`, `simplexml`, `xmlreader`, `xmlwriter` e `iconv`.

### Activar ZIP en Laragon

1. Abre Laragon.
2. Entra a **Menu → PHP → Extensions** y activa `zip`.
3. Reinicia Laragon con **Stop All** y **Start All**.
4. En Laragon Terminal ejecuta `php -m | findstr /I zip`. Debe aparecer `zip`.

## Instalación inicial

Abre **Laragon Terminal** y ejecuta los comandos en este orden.

### 1. Descargar el proyecto e instalar dependencias

Si todavía no tienes el proyecto:

```powershell
cd C:\laragon\www
git clone https://github.com/xwcamor/gestion_docente.git
cd gestion_docente
```

Si ya tienes el proyecto, entra a su carpeta y actualízalo:

```powershell
cd C:\laragon\www\gestion_docente
git pull origin main
```

Instala las dependencias:

```powershell
composer install
```

### 2. Configurar el entorno

Crea el archivo `.env`:

```powershell
copy .env.example .env
```

Abre `.env` y configura la conexión de MySQL. Ejemplo habitual de Laragon:

```dotenv
APP_NAME="Gestion Docente"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestion_docente
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

Si MySQL tiene contraseña, colócala en `DB_PASSWORD`.

### 3. Crear la base de datos vacía

En phpMyAdmin o HeidiSQL crea una base de datos llamada `gestion_docente`, con cotejamiento `utf8mb4_unicode_ci`.

**Solo crea la base vacía. No importes ningún archivo SQL.** Laravel creará todas las tablas mediante las migraciones.

### 4. Generar la clave de la aplicación

```powershell
php artisan key:generate
```

### 5. Crear todas las tablas y cargar los datos iniciales

```powershell
php artisan migrate:fresh --seed
```

Este es el comando oficial para construir la base desde cero durante el desarrollo y las pruebas. Ejecuta las migraciones desde la primera hasta la última y luego los seeders (incluidos los feriados definidos por el proyecto).

### 6. Crear tu cuenta de administrador

```powershell
php artisan gestion:crear-profesor --admin
```

El comando solicitará nombre, correo, datos institucionales opcionales y contraseña. La contraseña debe tener al menos 10 caracteres.

### 7. Iniciar el sistema

```powershell
php artisan serve
```

Abre http://127.0.0.1:8000/login.

---

## Reconstruir la base de datos durante las pruebas

Cada vez que quieras borrar la información de prueba y generar nuevamente todas las tablas, ejecuta desde la carpeta del proyecto:

```powershell
php artisan migrate:fresh --seed
```

Después vuelve a crear el usuario administrador, porque también se elimina al reconstruir la base:

```powershell
php artisan gestion:crear-profesor --admin
```

No necesitas eliminar la base de datos desde phpMyAdmin ni importar un SQL. `migrate:fresh` elimina las tablas existentes y las vuelve a crear usando las migraciones versionadas del repositorio.

**Advertencia:** `migrate:fresh` elimina todos los datos de la base configurada en `.env`. Úsalo únicamente en desarrollo/pruebas, nunca en una base con información que quieras conservar.

## Actualizar el proyecto sin borrar los datos

Cuando haya cambios nuevos en GitHub:

```powershell
git pull origin main
composer install
php artisan migrate --seed
php artisan optimize:clear
```

En este caso usa `migrate`, no `migrate:fresh`: aplica únicamente las migraciones nuevas pendientes y conserva los datos existentes.

## Comandos útiles

| Comando | Para qué sirve |
|---|---|
| `php artisan migrate:status` | Ver qué migraciones se ejecutaron. |
| `php artisan migrate` | Aplicar migraciones pendientes sin borrar datos. |
| `php artisan migrate:fresh --seed` | Borrar todas las tablas, recrearlas y ejecutar seeders. |
| `php artisan db:seed` | Ejecutar los seeders sin reconstruir las tablas. |
| `php artisan gestion:crear-profesor --admin` | Crear una cuenta administradora con perfil docente. |
| `php artisan optimize:clear` | Limpiar cachés de Laravel. |
| `php artisan test` | Ejecutar las pruebas automatizadas. |

## Estructura de base de datos y migraciones

- `database/migrations/`: historial de migraciones de Laravel. Es la fuente oficial para crear y modificar la estructura.
- `database/schema/gestion_docente.sql`: definición SQL de las tablas base utilizada por la migración inicial. No se importa manualmente.
- `database/seeders/`: datos iniciales del sistema.
- `database/factories/`: fábricas para pruebas, cuando correspondan.

La migración inicial crea las tablas de usuarios, profesores, ciclos, grupos, cursos, asignaciones, modalidades, horarios, feriados, sesiones, alumnos, matrículas, evaluaciones, notas, documentos, colas y malla curricular. Las modificaciones posteriores deben agregarse como nuevas migraciones; no se debe editar una migración ya aplicada en una instalación que se quiera conservar.

## Funcionalidades principales

- Acceso individual por profesor y gestión de sus datos académicos.
- Años, ciclos y múltiples grupos por ciclo.
- Cada ciclo se crea con un primer grupo obligatorio y no permite eliminar su último grupo.
- Cursos por grupo y modalidades TEC/TALL independientes.
- NRC, fechas, horarios, alumnos, sesiones y evaluaciones por modalidad.
- Importación de listados de alumnos exportados desde el campus.
- Altas y retiros individuales del padrón por grupo, conservando fichas y calificaciones; no se permite retirar al último alumno.
- Gestión de notas y evaluaciones.
- Calendario mensual consolidado por grupo, con clases y feriados; cada bloque conserva la modalidad TEC/TALL y el tipo de enseñanza de su curso.
- Malla curricular y propuestas de contenido TEC/TALL.
- Generación y versionado de documentos Excel.

## Solución de problemas

### Composer indica que falta `bootstrap/cache`

Desde la raíz del proyecto ejecuta:

```powershell
New-Item -ItemType Directory -Force bootstrap\cache
New-Item -ItemType Directory -Force storage\framework\cache\data
New-Item -ItemType Directory -Force storage\framework\sessions
New-Item -ItemType Directory -Force storage\framework\views
New-Item -ItemType Directory -Force storage\logs
composer install
```

### MySQL no encuentra la base de datos

Comprueba que MySQL esté iniciado en Laragon y que exista la base vacía `gestion_docente`. Revisa `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` en `.env`.

### Error de índice demasiado largo (MySQL 1059)

La estructura usa nombres explícitos y cortos para los índices que podrían superar el límite de 64 caracteres de MySQL, incluido `cci_week_category_approved_idx` para el contenido de la malla curricular.

### Error de tabla `cache` inexistente

La migración inicial también crea `cache`, `cache_locks`, `sessions`, `jobs`, `job_batches` y `failed_jobs`. Si acabas de actualizar el repositorio, ejecuta:

```powershell
php artisan migrate
```

## Estándar de diseño UI/UX

El sistema sigue un estándar visual y funcional común para sus módulos. No se agregan tarjetas, indicadores ni animaciones por decoración: cada elemento debe facilitar una tarea o una decisión.

Antes de diseñar o modificar pantallas, consulta:

- **[AGENTS.md](AGENTS.md):** instrucciones que debe seguir el agente de programación.
- **[DESIGN.md](DESIGN.md):** colores, tipografía, jerarquía, tarjetas, botones, tablas y patrones visuales de Gestión Docente.
- **[Guía UI/UX para futuros sistemas](docs/GUIA_UI_UX_FUTUROS_SISTEMAS.md):** cuándo utilizar Anti UI Slop, Think UI, Taste Skill, Emil Design Engineering, Vercel Guidelines, Anthropic Frontend Design, Hallmark, Make Interfaces Feel Better, UI/UX Pro Max y Stageshow. Incluye checklist e instalación selectiva.

Al reutilizar la guía en otro proyecto, copia `AGENTS.md`, `DESIGN.md` y la guía UI/UX. Actualiza los tokens de `DESIGN.md` según la marca y tecnología del nuevo sistema; no copies automáticamente los colores de Gestión Docente.

## Repositorio

https://github.com/xwcamor/gestion_docente


## Importar cursos desde Excel institucional

En **Ciclos y cursos → Importar carga Excel**, sube uno de los formatos institucionales que contenga la pestaña **Cursos**. El importador lee las columnas CURSO, CODIGO, BLOQUE TEC, NRC TEC, BLOQUE TALLER, BLOQUE, NRC TALLE y FECHA, y muestra una vista previa antes de guardar.

La importación crea o reutiliza el ciclo y el grupo indicado, registra cada curso y mantiene TEC/TALL como modalidades independientes con su NRC y periodo. Se puede volver a importar el mismo archivo al mismo grupo sin duplicar asignaciones ni modalidades.

**Límite de la fuente:** la pestaña Cursos no contiene días, horas ni ambientes. Por eso el importador no inventa horarios ni crea sesiones. Después de confirmar, abre cada modalidad y completa su horario; el calendario podrá calcular las sesiones con los feriados configurados.
