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

## Actualizar el sistema y cargar el catálogo SENATI

Cuando ya tienes una base con información que deseas conservar, actualiza el repositorio y ejecuta las migraciones aditivas:

```powershell
git switch main
git pull --ff-only origin main
php artisan migrate
php artisan db:seed --class=SenatiCatalogSeeder
php artisan db:seed --class=SenatiBlackboardSnapshotSeeder
php artisan optimize:clear
```

Estos comandos no eliminan los ciclos, grupos, alumnos ni documentos existentes. Los dos seeders agregan el catálogo institucional SENATI y la instantánea de 47 secciones/NRC observadas en Blackboard. La importación queda en el catálogo de referencia; los NRC todavía deben asociarse a los grupos reales y no genera horarios lectivos ficticios.

Los Excel oficiales TEC/TAL todavía deben cargarse como archivos reales en la configuración de plantillas. No se incluyen archivos de plantilla inventados.

**No ejecutes migrate:fresh en una base que contenga información que debas conservar.**

---

## Configurar el tenant SENATI

Después de actualizar el proyecto, entra con una cuenta administradora a **Administración → Configuración institucional**.

Desde esa pantalla puedes mantener el nombre institucional, país, zona horaria, reglas de duración por carrera, tipos de periodo, unidades académicas y duración/vigencia de cada versión de malla.

Esta pantalla no asigna todavía los NRC a salones ni cambia horarios, ciclos o grupos existentes. La cantidad de semestres se define por versión de carrera, no como un valor único de la institución. Las duraciones permitidas se configuran como una lista separada por comas (por ejemplo, `4, 5, 6, 8`).

La configuración y el catálogo resuelven la institución mediante sus membresías. La compatibilidad automática solo funciona mientras exista un único tenant activo. **La separación de ciclos, grupos, alumnos, horarios y documentos por institución sigue pendiente**; consulta [docs/SAAS_INSTITUTIONAL_MODEL.md](docs/SAAS_INSTITUTIONAL_MODEL.md) antes de habilitar una segunda institución.

Esta actualización incorpora una migración aditiva que identifica el catálogo de referencia de SENATI en la configuración institucional. No elimina ni convierte datos operativos. Ejecuta:

```powershell
git switch main
git pull --ff-only origin main
php artisan migrate
php artisan optimize:clear
```

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

## Flujo principal del docente

El trabajo se organiza alrededor de la carga laboral recibida. El ciclo y sus grupos son el contenedor de la asignación; el trabajo operativo se completa en cada grupo y en cada curso/modalidad.

1. **Recibir e importar la carga laboral:** identificar periodo, grupos, cursos, códigos, NRC y fechas del archivo maestro.
2. **Revisar cursos y horarios:** comprobar las ofertas TEC/TALL y configurar sus días, horas, ambientes y rangos propios.
3. **Cargar la malla curricular:** adjuntar el PDF de cada curso y obtener sus temas semanales desde el Cuadro Programa y la Hoja de Programación.
4. **Preparar los Excel con IA:** organizar por separado Plan de sesión y Control de avance para teoría y taller/práctica.
5. **Registrar las charlas de 5 minutos:** completar la charla correspondiente a cada fecha del grupo cuando ya exista su padrón de alumnos.

### Navegación en árbol

La entrada principal del sistema es un árbol desplegable con esta jerarquía:

- **Ciclo:** periodo académico y valores iniciales de carrera/semestre para los grupos nuevos; permite abrir la administración del ciclo.
- **Grupo:** mantiene su propia carrera y semestre, independientes de los demás grupos. Al crearlo hereda los valores actuales del ciclo, que luego se pueden editar con un clic en el árbol. También da acceso al padrón de alumnos, calendario, cursos y charla de 5 minutos (esta última solo se muestra si ya hay alumnos). Cada grupo nuevo recibe automáticamente un curso provisional llamado **Nuevo curso**, sin código, con TEC y TAL activos por defecto; el docente puede cambiar el nombre y desactivar el componente que no corresponda. Al agregar un curso desde el árbol solo se solicita el nombre y se crean ambos componentes activos. El código se completa después desde la edición en línea. Si el grupo nace de una importación que ya incluye cursos, estos ocupan ese lugar y no se agrega uno ficticio.
- **Curso:** permite editar el nombre, alias y datos de sus modalidades, y subir o actualizar la malla curricular.
- **Modalidad TEC/TALL:** muestra NRC, bloque, referencia, fechas, días, horas, ambiente y documentos generados; abre la pantalla de configuración y exportación de esa modalidad.

Los niveles del árbol comienzan contraídos y se despliegan de forma independiente: se pueden mantener abiertos varios grupos y cursos a la vez. Los conectores visuales unen grupo → curso → componentes TEC/TALL. En la vista individual del ciclo se conserva la visualización directa de sus grupos. La malla se muestra bajo el curso porque pertenece al catálogo y puede reutilizarse en varios grupos. Cada asignación de curso a un grupo tiene su propio periodo de inicio y fin; cada modalidad TEC/TALL conserva además sus fechas, NRC, horario y documentos independientes. Al crear un ciclo, el sistema crea su primer grupo y curso inicial y abre directamente el árbol de ese ciclo. El semestre se selecciona en valores romanos **I, II, III, IV, V o VI** tanto al crear/editar ciclos como al editar el semestre particular de un grupo.

**Plantilla de carga laboral:** se descarga desde la pantalla Importar carga laboral y contiene tres hojas. `Cursos` registra curso, código institucional, bloques, NRC y fechas independientes TEC/TALL; `Horarios` registra una fila por día y bloque con CODIGO, modalidad TEC/TALL, NRC opcional, día, horas, tipo, ambiente e INTERVALO SEMANAS opcional (vacío equivale a 1); `Instrucciones` explica el llenado. El importador valida la correspondencia curso/modalidad, cruces de horario e intervalos de 15 minutos. Al confirmar, guarda la repetición semanal, calcula horas promedio por semana y reconstruye las sesiones excluyendo feriados bloqueados. Si una modalidad ya tenía horarios configurados manualmente, se conservan para no sobrescribirlos al reimportar.

### Inicio y administración

- **Dashboard inicial:** muestra indicadores reales de ciclos, grupos, cursos, componentes activos y pendientes, alumnos, próximas clases y feriados; incluye accesos directos a las tareas frecuentes.
- **Gestión de cuentas (administrador):** búsqueda por nombre, correo, ID o escuela; filtros por rol y estado; orden y paginación; edición de perfiles; activación/desactivación; restablecimiento de contraseña con confirmación. Las cuentas con historial académico no se eliminan, y el sistema protege la última cuenta administradora activa.
- **Feriados y cierres:** vista calendario y listado con filtros por año, ámbito y efecto. Cada docente puede administrar fechas personales; solo los administradores pueden crear, editar o eliminar fechas generales compartidas. Al cambiar o quitar una fecha que bloquea clases, el sistema recalcula los calendarios de las modalidades afectadas y marca los Excel anteriores para regeneración. Las fuentes y notas de referencia quedan asociadas al registro.
- **Auditoría:** las acciones administrativas de cuentas y feriados quedan registradas sin guardar contraseñas en el historial.

## Funcionalidades principales

- Acceso individual por profesor y gestión de sus datos académicos.
- Años y ciclos académicos con carrera, semestre, rango de fechas y múltiples grupos por ciclo.
- Cada ciclo se crea con un primer grupo obligatorio y no permite eliminar su último grupo.
- Cursos por grupo y modalidades TEC/TALL independientes.
- Importación de carga laboral con plantilla Excel descargable: hoja Cursos para datos del curso y hoja Horarios para días, horas, tipo de bloque y ambiente; genera las sesiones iniciales con el motor de calendario.
- NRC, fechas, horarios, alumnos, sesiones y evaluaciones por modalidad.
- Importación de listados de alumnos exportados desde el campus.
- Altas y retiros individuales del padrón por grupo, conservando fichas y calificaciones; no se permite retirar al último alumno.
- Gestión de notas y evaluaciones.
- Calendario mensual consolidado por grupo, con clases y feriados; cada bloque conserva la modalidad TEC/TALL y el tipo de enseñanza de su curso.
- Malla curricular: extracción diferenciada del Cuadro Programa (HT/HO, conocimientos y horas) y la Hoja de Programación (tema semanal); el tema oficial se conserva por semana y alimenta el calendario y los Excel de sesión. El ciclo registra carrera y semestre para completar el contexto cuando la fuente no lo indique.
- Charlas de cinco minutos: una por cada fecha disponible del grupo (sin filas sin fecha), excluyendo feriados; el catálogo institucional de 96 temas vuelve al primero cuando se agota. El check marca la charla como realizada; el número de participantes es editable por charla y se inicializa con la cantidad de alumnos del padrón.
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

### Error SQLSTATE[42S22]: columna `slug` desconocida

Las migraciones correctivas `2026_11_24_000008_repair_curricula_slug_column.php` y `2026_11_24_000009_repair_missing_slug_columns.php` reparan columnas slug faltantes. Las migraciones `2026_11_24_000010_repair_class_sessions_slug_column.php` y `2026_11_24_000011_repair_generated_documents_slug_column.php` refuerzan la reparación de sesiones y documentos generados. La migración `2026_11_24_000012_add_weekly_repeat_interval_to_modality_schedules.php` incorpora el intervalo de repetición semanal con valor predeterminado de 1, por lo que los horarios existentes conservan su comportamiento. La migración `2026_11_25_000001_archive_curricula_without_course_assignments.php` archiva las mallas activas cuyos cursos ya no tienen asignaciones en ningún grupo; conserva los documentos y todas las versiones históricas. La migración `2026_11_25_000006_add_sidebar_color_to_users.php` agrega una preferencia personal de color para el menú lateral, con valor inicial `blue`; no altera el color institucional ni los datos académicos. La migración `2026_11_25_000007_add_course_period_to_assignments.php` agrega las fechas de inicio y fin propias de cada curso dentro de su grupo; no reemplaza las fechas independientes de TEC/TALL.

Actualiza el repositorio y ejecuta:

```powershell
git pull origin main
php artisan migrate
php artisan optimize:clear
```

No ejecutes `migrate:fresh`: borraría los datos de la instalación.

Los archivos **Plan de sesión** y **Control de avance** solo se habilitan cuando la malla vinculada contiene temas semanales oficiales (o títulos HT válidos de una malla anterior). El **Formato de evaluación** no depende de la malla.

### Error de tabla `cache` inexistente

La migración inicial también crea `cache`, `cache_locks`, `sessions`, `jobs`, `job_batches` y `failed_jobs`. Si acabas de actualizar el repositorio, ejecuta:

```powershell
php artisan migrate
```

## Estándar de diseño UI/UX

El sistema sigue un estándar visual y funcional común para sus módulos. No se agregan tarjetas, indicadores ni animaciones por decoración: cada elemento debe facilitar una tarea o una decisión.

Antes de diseñar o modificar pantallas, consulta:

- **[AGENTS.md](AGENTS.md):** instrucciones que debe seguir el agente de programación.
- **[DESIGN.md](DESIGN.md):** colores, tipografía, jerarquía, tarjetas, botones, tablas, escala de espaciado, subtítulos y filtros obligatorios.
- **[Guía UI/UX para futuros sistemas](docs/GUIA_UI_UX_FUTUROS_SISTEMAS.md):** selección de referencias, checklist de revisión, estándar de consistencia y reglas para nuevos módulos.



La implementación CSS del estándar está centralizada en `public/css/ui-standard.css`, cargada automáticamente por `layouts/app.blade.php` después de los estilos de cada vista. Los módulos autenticados deben reutilizar esta hoja; no agregues otra capa global ni dupliques sus reglas.

Al reutilizar la guía en otro proyecto, copia `AGENTS.md`, `DESIGN.md` y la guía UI/UX. Actualiza los tokens de `DESIGN.md` según la marca y tecnología del nuevo sistema; no copies automáticamente los colores de Gestión Docente.

### Normalización de datos académicos

Los valores académicos visibles se guardan en **MAYÚSCULAS**, tanto si se escriben como si se pegan: carrera, grupos, cursos, códigos, NRC, bloques, aulas y nombres/apellidos del padrón. La interfaz transforma los campos marcados en tiempo real; los modelos Laravel aplican la misma normalización como respaldo para formularios, importaciones y otros procesos. Correos, usuarios y contraseñas no se alteran.

La migración `2026_11_25_000008_uppercase_academic_text_fields.php` convierte los registros académicos existentes una sola vez. Para aplicarla en desarrollo ejecuta `php artisan migrate`.

### Layout del árbol y selector de semestre

- El ancho real del sidebar coincide con el espacio reservado en la grilla; su relleno no aumenta el ancho visual ni tapa el contenido. En tablet y móvil el menú pasa a una fila horizontal.
- Al guardar un ciclo nuevo, se abre directamente su árbol con el primer grupo y curso inicial creados.
- El semestre es un selector cerrado con valores romanos I, II, III, IV, V y VI. Se aplica al crear/editar ciclos y al editar cada grupo desde el árbol.

### Navegación y apariencia personal

- En escritorio, el sidebar permanece fijo debajo del topbar y tiene su propio desplazamiento; la altura del contenido de una página, incluido el árbol de trabajo, no lo empuja hacia abajo.
- En pantallas estrechas, el sidebar cambia a una navegación horizontal.
- El topbar mantiene el acceso al menú y al tema. Las acciones de cuenta se agrupan en un desplegable con perfil, ajustes, cuentas (administradores), ayuda y cierre de sesión.
- En **Ajustes de interfaz** se puede seleccionar una de cinco paletas personales para el sidebar: **Azul, Verde, Violeta, Ámbar y Grafito**. Esta paleta solo colorea el menú; el color de botones y enlaces principales se configura por separado.
- Para aplicar la nueva preferencia en una instalación existente, ejecuta `php artisan migrate`; no uses `migrate:fresh`.

## Repositorio

https://github.com/xwcamor/gestion_docente


## Importar cursos desde Excel institucional

En **Cargas laborales → Importar carga laboral**, descarga la plantilla y completa las hojas **Cursos** y **Horarios**. En Cursos utiliza CURSO, CODIGO, BLOQUE TEC, NRC TEC, BLOQUE TALLER, BLOQUE, NRC TALLER, FECHA TEC y FECHA TALLER. Cada modalidad conserva su propio rango de fechas. Los archivos anteriores con una sola columna FECHA siguen siendo compatibles. En Horarios utiliza CODIGO, MODALIDAD, NRC, DIA, DESDE, HASTA, TIPO, AMBIENTE e INTERVALO SEMANAS (opcional: 1 = cada semana, 2 = cada dos semanas; vacío = 1).

La importación crea o reutiliza el ciclo y el grupo indicado, registra cada curso, conserva TEC/TALL como modalidades independientes y asocia cada bloque horario mediante CODIGO + MODALIDAD. Se puede volver a importar el mismo archivo al mismo grupo sin duplicar asignaciones ni modalidades; los horarios manuales que ya existían se conservan.

El ciclo se reconoce desde el patrón `202620-PIAD-...` en CODIGO (año + ciclo). Las horas usan formato HH:MM y bloques de 15 minutos. La vista previa muestra los bloques detectados; si hay errores, no permite confirmar hasta corregir el archivo. Al confirmar, calcula horas semanales y genera sesiones con los feriados bloqueados excluidos.

Al retirar un curso de un grupo, el curso y las mallas históricas del catálogo no se eliminan. Si ya no existe ninguna asignación de ese curso en otros grupos, su versión activa se archiva (`is_active=false`) para que una futura alta del mismo código no la vincule automáticamente. Si el curso sigue asignado en otro grupo, la malla permanece disponible. Al eliminar un grupo se aplica la misma regla a cada curso que contenía. La migración correctiva también archiva los casos que ya estaban sin asignaciones antes de actualizar el sistema.


## Registro simplificado de cursos

La carga rápida utiliza una fila por curso y permite pegar alias como `202620-PIAD-224-TEC-NRC_11523: SISTEMAS OPERATIVOS Y COMUNICACIÓN DE DATOS`. El sistema detecta nombre, código base, modalidad y NRC. Cada modalidad puede conservar fechas, código de bloque, referencia de bloque y horario independientes. El horario se configura por bloques con días, horas, tipo, ambiente e intervalo de repetición de 1 a 4 semanas. Desde el grupo, la acción **Editar curso y horario** abre el mismo formulario con los datos existentes. El nombre se comparte con otros grupos que reutilizan el curso del catálogo; el código base no se cambia desde esa pantalla porque identifica el curso compartido.
