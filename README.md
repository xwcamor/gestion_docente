# Gestión Docente

Aplicación para organizar documentación docente por **profesor → año/ciclo → grupo → muchos cursos → modalidad TEC/TALL**. Desarrollada con PHP/Laravel y MySQL para ejecutarse localmente en Laragon.

> Una cuenta por profesor. Un ciclo puede tener varios grupos; cada grupo admite una cantidad ilimitada de cursos. Cada curso puede activar TEC, TALL o ambas modalidades. Los documentos Excel se generan por curso, grupo, NRC y modalidad, sin mezclar listas ni evaluaciones.

## Requisitos

- Windows + Laragon.
- PHP 8.3 o superior.
- MySQL 8 / InnoDB.
- Composer.
- Extensiones PHP: pdo_mysql, mbstring, openssl, fileinfo, zip, gd, xml, dom, simplexml, xmlreader, xmlwriter e iconv.

### Activar ZIP en Laragon (paso que tuve que realizar)

1. Abre Laragon.
2. Entra a **Menu / PHP / Extensions**.
3. Activa la extensión **zip**.
4. Reinicia Laragon con **Stop All** y **Start All**.
5. Comprueba desde Laragon Terminal:

   ```bash
   php -m | findstr /I zip
   ```

   Debe aparecer `zip`. Si no aparece, confirma que la terminal usa la misma versión de PHP seleccionada en Laragon.

## Instalación limpia (SQL único)

**IMPORTANTE:** este proyecto usa un único SQL para crear el esquema inicial. El SQL contiene `DROP DATABASE IF EXISTS gestion_docente`; elimina por completo la base anterior. Ejecútalo únicamente cuando quieras empezar desde cero.

1. Descomprime el proyecto en `C:\\laragon\\www\\gestion_docente`.
2. Inicia Laragon con **Start All**.
3. Abre Laragon Terminal:

   ```powershell
   cd C:\\laragon\\www\\gestion_docente
   composer install
   copy .env.example .env
   ```

4. Configura el archivo `.env`:

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

   Si tu MySQL tiene contraseña, introdúcela en `DB_PASSWORD`.

5. Importa **solamente** `database/gestion_docente.sql` desde phpMyAdmin o HeidiSQL. Crea la base y todas las tablas, índices, relaciones y tablas de caché/sesiones/cola.

6. En Laragon Terminal ejecuta:

   ```powershell
   php artisan key:generate
   php artisan db:seed
   php artisan gestion:crear-profesor --admin
   php artisan serve
   ```

7. Abre [http://127.0.0.1:8000/login](http://127.0.0.1:8000/login).

**No ejecutes `php artisan migrate` después de importar el SQL inicial.** Las migraciones se conservan como historial de desarrollo; el procedimiento acordado para instalación limpia es importar un solo archivo SQL.

## Reiniciar desde cero

Cuando quieras reinstalar:

1. Elimina la base de datos `gestion_docente` en phpMyAdmin/HeidiSQL.
2. Importa otra vez `database/gestion_docente.sql`.
3. Conserva `storage/app/plantillas/`; contiene las plantillas Excel maestras.
4. Ejecuta `php artisan db:seed`, `php artisan gestion:crear-profesor --admin` y `php artisan serve`. Si cambiaste de copia del proyecto o borraste el archivo de entorno, repite `composer install`, `copy .env.example .env` y `php artisan key:generate`.

## Funcionalidades

- Login individual por profesor y aislamiento de datos por cuenta.
- Gestión de años, ciclos y múltiples grupos por ciclo.
- Cursos ilimitados por grupo; cada curso activa TEC, TALL o ambas.
- NRC, rango de fechas, horarios, alumnos, sesiones y documentos independientes por modalidad.
- Calendario de sesiones que excluye feriados nacionales de referencia 2027–2031 y permite registrar cierres adicionales.
- Importación del archivo original que exporta el campus. No utiliza una plantilla alternativa: detecta las columnas Apellidos, Nombre, Nombre de usuario, ID de estudiante, Último acceso, Disponibilidad y el encabezado variable que comienza por `Calificación general`. El archivo .xls del ejemplo es TSV UTF-16LE.
- Gestión de evaluaciones y notas por alumno, evaluación y NRC.
- Generación de Excel separados por curso/grupo/NRC/modalidad y tipo de documento.
- Malla curricular asistida por prompt: adjuntar el PDF a ChatGPT, pegar el prompt generado, importar el JSON y revisar las propuestas TEC/TALL.
- Semanas internas consecutivas desde 1; conserva por separado la semana impresa en la malla.
- Historial/versionado de documentos generados.

## Plantillas Excel y documentación de referencia

- `documentacion/formatos_excel_fuente/`: archivos Excel de referencia institucionales y planes de sesión convertidos desde PDF.
- `storage/app/plantillas/`: copias utilizadas por el generador.
- `documentacion/mallas/`: malla PIAD-224 fuente y JSON de ejemplo.
- `documentacion/prompts/`: prompt maestro y flujo de importación curricular.
- `database/gestion_docente.sql`: esquema único para instalación limpia.

## Resolución de errores MySQL

El índice de documentos usa el nombre corto `gd_mod_type_ver_idx`, evitando el error MySQL 1059 por identificadores demasiado largos. El SQL único crea también `cache`, `cache_locks`, `sessions`, `jobs`, `job_batches` y `failed_jobs`, evitando el error de tabla `cache` inexistente.

## Validación

Ejecuta las pruebas locales con:

```bash
php artisan test
```

`vendor/` y `composer.lock` no se incluyen: `composer install` instala las dependencias en Laragon. Antes de utilizar documentos institucionales reales, comprueba los Excel generados frente a las plantillas oficiales y valida las ponderaciones con el área académica.

## Repositorio

https://github.com/xwcamor/gestion_docente
