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

La Memoria Institucional 2025 presenta 90 carreras en ejecución, agrupadas en 14 escuelas, y en otra sección muestra la cifra general de 91 carreras de formación profesional. Se conservan ambas cifras por separado, con su contexto documental. La extracción legible de la tabla permitió guardar 85 nombres en 14 agrupaciones: Administración, Agroindustria, Artes Gráficas, Confecciones, Electrotecnia, Hotelería/Turismo/Restaurantes, Industria Alimentaria, Tecnologías de la Información, Joyería/Orfebrería/Platería, Mantenimiento, Mecánica Automotriz, Metalmecánica, Tecnologías Ambientales y Textil. Cinco nombres no se reconstruyen porque no aparecen legibles en la extracción consultada.

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

Esta migración añade el catálogo institucional y una importación histórica de Blackboard, sin modificar ni borrar las tablas operativas actuales de ciclos, grupos, cursos o sesiones. Los NRC quedan pendientes de vincular con los grupos existentes. La separación SaaS requiere después integrar el contexto de institución activa, membresías y autorización por tenant en todos los controladores; no debe declararse completa hasta terminar ese trabajo y probar aislamiento entre instituciones.
