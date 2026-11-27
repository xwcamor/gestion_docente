# Modelo SaaS institucional y catálogo SENATI

## Objetivo

Separar la plataforma común de la configuración y los datos de cada institución. SENATI es el primer catálogo de referencia; no debe convertirse en una regla fija para institutos o universidades futuras.

## Jerarquía de datos

- Tenant o institución: propietario de catálogos, reglas, plantillas y usuarios miembros.
- Unidad académica: escuela, facultad, área o rótulo de origen. Se conserva el nombre fuente cuando su jerarquía todavía no está confirmada.
- Carrera o programa: pertenece a una institución y, opcionalmente, a una unidad.
- Versión de programa o malla: define su duración en semestres y vigencia. La cantidad de semestres no pertenece al tenant.
- Periodo institucional: conserva el código de matrícula/plataforma, sin asumir que equivale a un semestre de avance de malla.
- Asignatura de catálogo: identificada por código institucional externo; los códigos nuevos se conservan como registros distintos hasta confirmar equivalencias.
- Oferta o sección: combina periodo, asignatura, tipo de impartición y NRC. Cada NRC conserva nombre y estado tal como se observan en Blackboard.
- Grupo docente: representa el grupo real de estudiantes. Una oferta se vincula a un grupo solo cuando el profesor confirma la correspondencia.
- Programación: pertenece a la sección o componente: fechas, días de semana, intervalo de repetición, horas y aula.
- Relación laboral: conecta usuario e institución, con tipo de dedicación y horas contratadas.
- Plantilla institucional: archivo versionado, opcionalmente asociado a un tipo de impartición. No se registra una plantilla activa hasta cargar el Excel real.

## Dónde se define cuántos ciclos tiene una carrera

No existe un único campo de cantidad de ciclos por tenant. La duración se guarda en academic_program_versions.duration_semesters. Para SENATI se registran opciones de referencia de 4, 5, 6 y 8 semestres; el programa y versión concreta determinan cuál aplica. Las carreras de la Escuela de Educación Superior Tecnológica publicadas actualmente se registran con 8 semestres. Para una nueva institución se podrán definir otros sistemas (trimestres, módulos, años, etc.).

No confundir:
- 202620: código de periodo observado en Blackboard.
- Semestre 1..N: avance del alumno dentro de la malla del programa.

## Datos institucionales de SENATI cargados como referencia

### Fuentes y límites

- Reglamento del estudiante: el catálogo institucional debe registrar duración, créditos, certificaciones y titulaciones por carrera. El esquema operativo contempla 4, 5, 6 y 8 semestres.
- Centro de ayuda SENATI: el semestre regular se describe con duración de 17 semanas.
- Escuela Superior Tecnológica: la página de oferta enumera siete carreras de ingeniería y señala una duración de 8 semestres (4 años).
- Memoria institucional 2022: enumera carreras vinculadas a la Escuela de Tecnología de Información y Administración (ETIA). Se cargan solo como referencia histórica y no como oferta vigente confirmada.
- La Memoria Institucional 2025 reporta 91 carreras de formación profesional y 14 escuelas profesionales. Este número no equivale a una lista completa de 91 nombres; no se inventan los nombres que no aparecen en las fuentes consultadas.

### Unidades o rótulos guardados

- Formación Profesional: etiqueta de agrupación observada en las capturas de Blackboard.
- ETI: etiqueta literal observada en el periodo 202601. Su equivalencia institucional actual no se infiere.
- Escuela de Educación Superior Tecnológica: unidad oficial para las siete carreras actuales de ingeniería registradas.
- ETIA: unidad histórica de referencia según la Memoria 2022; no se mezcla automáticamente con la etiqueta ETI de Blackboard.

### Catálogo general 2025 (Formación Profesional)

La Memoria Institucional 2025 presenta 90 carreras en ejecución, agrupadas en 14 escuelas, y en otra sección muestra la cifra general de 91 carreras de formación profesional. Se conservan ambas cifras por separado, con su contexto documental. La extracción legible de la tabla permitió guardar 85 nombres en 14 agrupaciones: Administración, Agroindustria, Artes Gráficas, Confecciones, Electrotecnia, Hotelería/Turismo/Restaurantes, Industria Alimentaria, Tecnologías de la Información, Joyería/Orfebrería/Platería, Mantenimiento, un grupo de carreras de mecánica/automotores cuyo encabezado oficial quedó pendiente, Metalmecánica, Tecnologías Ambientales y Textil. Cinco nombres no se reconstruyen porque no aparecen legibles en la extracción consultada.

Los 85 nombres se guardan con estado `reference_2025`; no se presentan como una lista vigente definitiva en 2026. La Memoria 2025 no detalla la cantidad de semestres individual para cada nombre, por lo que cada versión queda con duración pendiente hasta asociar su malla. El Reglamento Interno enumera esquemas de 4, 5, 6 y 8 semestres y señala que la duración corresponde al esquema operativo de cada carrera. La página de la Escuela Superior Tecnológica indica ocho semestres para sus siete carreras.

### Carreras verificadas

Se registran con versión oficial 2026 y duración de 8 semestres:
1. Ingeniería de Mantenimiento Industrial.
2. Ingeniería de Tecnologías de la Información y Comunicación.
3. Ingeniería de Ciencia de Datos e Inteligencia Artificial.
4. Ingeniería de Industrias Alimentarias.
5. Ingeniería de Producción Industrial.
6. Ingeniería Mecatrónica.
7. Ingeniería de Sistemas de Información.

Los nombres ETIA recuperados de la Memoria 2022 se conservan con estado historical_reference, sin duración individual inventada. El catálogo de Formación Profesional general sigue pendiente de importación desde el catálogo oficial vigente completo.

## Instantánea Blackboard incorporada

Se crean cinco periodos de referencia: 202510, 202520, 202610, 202620 y 202601.

- 202510: 5 asignaturas, 10 NRC.
- 202520: 6 asignaturas, 12 NRC.
- 202610: 5 asignaturas, 10 NRC.
- 202620: 4 asignaturas, 12 NRC.
- 202601: 3 asignaturas, 3 NRC.
- Total: 23 combinaciones asignatura-periodo, 22 códigos únicos de asignatura y 47 secciones o NRC. PIAD-220 aparece en dos periodos y comparte una sola identidad de catálogo.

Se conservan exactamente los nombres, códigos, NRC, tipo TEC/TAL y estado visible. El docente se guarda como rótulo de origen, no como relación de membresía. El caso PIAD-220 de 202520 conserva el texto MÚLTIPLES PROFESORES.

### Regla crítica de agrupación

En 202620 hay asignaturas con dos NRC TEC y un NRC TAL. La explicación del docente es que un salón puede tener TEC+TAL y otro salón solo TEC; sin embargo, las capturas no identifican qué NRC TEC pertenece al salón que tiene TAL, ni los nombres reales de los salones. Por eso los 47 NRC se cargan con group_mapping_status=pending_review y teaching_group_id=null. No se crean grupos ficticios ni se emparejan NRC por orden.

### Horarios

Las capturas de lista de cursos no muestran horarios semanales reales. La captura de repetición muestra un evento asociado al alias 202601-PCCU-1008-TAL-NRC_4526, con fecha 26/11/2026, de 14:00 a 15:00, repetición semanal y fecha de término 27/11/2026, jueves seleccionado. Es una configuración de evento observada, no evidencia suficiente para convertirla en horario lectivo recurrente del curso. Se registra como ejemplo de interfaz en la configuración, no como programación efectiva.

La programación operativa existente del proyecto ya admite fecha de inicio/fin, varios días seleccionados, hora inicial/final, aula e intervalo semanal. Se conservará como base para las secciones reales.

## Plantillas Excel y charla de cinco minutos

institution_document_templates permite guardar archivos versionados por institución y tipo de impartición. La migración no crea archivos ficticios. Los formatos TEC/TAL quedan pendientes de que se carguen los Excel reales y se configure su mapa de celdas.

five_minute_talks se configura como una función institucional opcional. SENATI queda inicialmente habilitada; otras instituciones podrán deshabilitarla o definir sus propias reglas.

## Dedicación laboral

employment_relationships guarda la relación usuario-institución, tipo de dedicación (FULL_TIME, PART_TIME, HOURLY u otros valores configurables), horas contratadas semanales y vigencia. No se deduce el tipo de contrato a partir de las horas de clase.

## Límite de esta primera migración

Esta migración añade el catálogo institucional y una importación histórica de Blackboard, sin modificar ni borrar las tablas operativas actuales de ciclos, grupos, cursos o sesiones. Los NRC quedan pendientes de vincular con los grupos existentes. Ya existe un resolvedor de institución activa y se utiliza en la configuración y el catálogo institucional; sin embargo, esto todavía no equivale a aislamiento SaaS completo. Las tablas operativas tradicionales aún no están vinculadas a una institución y sus controladores todavía no aplican autorización transversal por tenant.


## Fase 2: configuración del tenant SENATI

La pantalla administrativa está disponible en **Administración → Configuración institucional**. Usa el contexto de institución activa para cargar y guardar la identidad, reglas, unidades y programas del tenant autorizado. El catálogo histórico de Blackboard y las notas documentales de SENATI solo se muestran cuando la configuración del tenant incluye `catalog_profile=senati_reference`.

Datos administrables:
- Identidad institucional: nombre, razón social, país y zona horaria. El código SENATI y el slug permanecen estables.
- Reglas académicas de referencia: semanas por semestre y opciones de duración disponibles. Las duraciones se escriben separadas por comas (por ejemplo: `4, 5, 6, 8`) y se validan como enteros únicos entre 1 y 30.
- Tipos de periodo: nombre, unidad, patrón de código, semanas de referencia y estado.
- Unidades académicas: nombre visible y estado, conservando código, tipo, rótulo de origen y trazabilidad de importación.
- Versiones de carrera: duración en semestres, vigencia, estado y versión actual.

Regla fundamental:
- La institución no tiene un único número universal de ciclos.
- La duración se define en cada `academic_program_versions.duration_semesters`.
- Las opciones institucionales de duración son una lista orientativa y editable, no la duración efectiva de todas las carreras.
- El código de periodo Blackboard (por ejemplo, 202620) es distinto al número de semestre de avance curricular.

La edición de una unidad o una regla institucional no cambia retroactivamente las fechas de ciclos, horarios, NRC ni grupos ya creados. La modificación de una versión de malla no cambia las demás versiones.

### Checklist de fase 2

- [x] Pantalla de configuración del tenant.
- [x] Edición de identidad y zona horaria.
- [x] Edición de opciones de duración por carrera.
- [x] Edición de tipos de periodos.
- [x] Edición de unidades académicas y rótulos de origen.
- [x] Edición de duración y vigencia por versión de carrera.
- [ ] Verificación funcional en Laragon de guardado y recarga de cada formulario.
- [ ] Confirmación del administrador de que las opciones y valores de SENATI son correctos.

No se considera completada la fase 2 hasta que las casillas manuales pendientes se comprueben en la instalación local.

## Fase 3: contexto institucional y portabilidad inicial

Se agregó `App\\Support\\CurrentInstitution` como punto único de resolución del tenant para los módulos de configuración y catálogo institucional.

Reglas de resolución:
- Si el usuario tiene una sola membresía activa, se utiliza esa institución.
- Si tiene varias membresías activas, se exige que la sesión tenga una institución seleccionada mediante `current_institution_id`; el identificador se valida contra sus propias membresías.
- Si el usuario aún no tiene filas de membresía, se permite compatibilidad temporal únicamente cuando existe una sola institución activa.
- Si hay membresías vencidas, inactivas o asociadas a instituciones deshabilitadas, no se aplica el fallback.
- Si existen varias instituciones activas y el usuario no tiene membresía, se deniega el acceso al catálogo/configuración.

La pantalla de configuración ya no fija las opciones de duración en el código. Cada institución guarda sus propias semanas de referencia y su lista de semestres permitidos en `institutions.settings`. La actualización de una versión de malla valida la duración contra esa lista. El catálogo de referencia SENATI se activa por el valor configurable `catalog_profile`; el controlador no necesita comparar el código del tenant.

### Fase 4: aislamiento de datos operativos y selección de institución

Se incorporó el primer límite transversal de tenant en las entidades operativas. La migración `2026_11_26_000005_add_operational_tenant_scopes.php` agrega `institution_id` a ciclos, cursos de catálogo, alumnos, feriados, temas de charla y auditoría. Los registros que ya existían se asignan a la única institución activa detectada (SENATI en la instalación actual), sin cambiar sus IDs ni sus relaciones.

Los registros hijos heredan el tenant a través de sus relaciones:
- Ciclo → grupos → asignaciones → modalidades → horarios y sesiones.
- Curso → mallas → tareas → semanas → operaciones y contenidos.
- Modalidad → matrículas → evaluaciones → calificaciones.
- Grupo/ciclo/modalidad → importaciones y documentos generados.
- Ciclo/grupo → charlas programadas.

Los modelos Eloquent aplican un alcance global de institución en las consultas autenticadas y asignan la institución activa al crear registros raíz. El identificador recibido desde un formulario no puede cambiar el tenant de creación. Las cuentas existentes se vinculan al tenant actual durante la migración; las cuentas nuevas creadas desde el módulo o comando de administración reciben una membresía institucional.

También se agregó:
- `/instituciones/seleccionar`: selector de institución disponible para cuentas con más de una membresía activa.
- Cambio de institución desde el menú de cuenta.
- Validación de membresía en el middleware que protege los módulos autenticados.
- Gestión de acceso por membresía institucional en lugar de activar/desactivar la identidad global de una persona.

La selección de institución y el aislamiento por modelos están implementados, pero todavía requieren pruebas funcionales en Laragon. No se debe habilitar una segunda institución para operación real hasta terminar la administración de tenants/membresías y comprobar accesos cruzados.

### Lo que todavía falta para SaaS completo

- Pantalla administrativa de alta/edición/suspensión de instituciones.
- Flujo para invitar una cuenta ya existente a otra institución y asignar su rol de miembro.
- Administración de administradores por institución y permisos de plataforma separados.
- Revisar los campos del perfil docente que aún son globales (escuela, carrera e identificador institucional) para permitir valores distintos por institución.
- Revisión final de consultas SQL directas y pruebas automatizadas de separación entre dos tenants.
- Configurar plantillas Excel, módulos habilitados y branding para cada institución.

### Checklist de portabilidad

- [x] Resolver la institución desde las membresías activas del usuario.
- [x] Mantener compatibilidad temporal con la instalación actual de un solo tenant.
- [x] Aplicar el contexto a configuración y catálogo institucional.
- [x] Evitar mostrar las notas históricas de SENATI en catálogos de otras instituciones.
- [x] Permitir configurar duraciones académicas sin defaults codificados para nuevos tenants.
- [x] Añadir `institution_id` a las entidades operativas raíz y migrar sus registros actuales a la única institución activa.
- [x] Aplicar alcances institucionales a modelos operativos y relaciones hijas.
- [x] Asignar membresías a las cuentas existentes y a las nuevas cuentas.
- [x] Añadir selector/cambio de institución para usuarios con varias membresías.
- [ ] Implementar administración de instituciones y asignación de membresías a otras cuentas.
- [ ] Verificar en Laragon los datos actuales y las acciones principales del sistema.
- [ ] Crear dos tenants de prueba y demostrar que usuarios de uno no pueden consultar ni modificar los registros del otro.
- [ ] Automatizar pruebas de autorización y aislamiento SaaS.

