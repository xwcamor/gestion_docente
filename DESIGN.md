# DESIGN.md — Gestión Docente

Este archivo es la fuente de verdad visual del sistema. Las pantallas deben sentirse parte del mismo producto, aunque cada módulo tenga necesidades diferentes.

## Dirección visual

**Profesional, académico, claro y operativo.** La interfaz debe ayudar a docentes y administradores a completar tareas, revisar información académica y detectar pendientes sin ruido visual.

No buscamos una landing de marketing ni un dashboard lleno de adornos. Priorizamos la información real, la legibilidad y las acciones correctas.

## Colores y superficies

- El color principal se configura desde los ajustes del sistema y llega a la vista mediante `--accent` y `--accent-hover`. No lo reemplaces por un color fijo.
- Texto principal: `--ink` / `--gd-ink`.
- Texto secundario: `--muted` / `--gd-muted`.
- Bordes: `--line` / `--gd-border`.
- Fondo de aplicación: `--canvas`.
- Superficie de contenido: `--surface` / `--gd-surface`.
- Fondo secundario: `--surface-soft` / `--gd-surface-soft`.
- Estados semánticos: éxito, advertencia, error e información. Usa los mismos significados en todas las páginas.

El color de marca identifica acciones y orientación. No uses colores intensos para decorar tarjetas ni pintes cada indicador con un color diferente sin significado.

## Tipografía y jerarquía

- H1: título único de la página, aproximadamente 25–31 px, peso alto y línea compacta.
- H2: título de sección, aproximadamente 15–16 px.
- H3: subsección o título de elemento, aproximadamente 13–14 px.
- Texto auxiliar: 11–13 px según densidad.
- Números: usa cifras tabulares cuando se comparan horas, cantidades, fechas o porcentajes.
- Mantén títulos a la izquierda y evita que las etiquetas compitan con el título.

Patrones existentes:
- `.page-head`, `.eyebrow`, `.subhead`.
- `.card-head`, `.card-description`.
- `.stat-card` y `.stat`.
- `.breadcrumbs`.

## Espaciado y forma

Usa una escala coherente basada en 4, 8, 12, 16, 20 y 24 px. No introduzcas valores arbitrarios si uno existente funciona.

- Controles: esquinas cercanas a 8 px.
- Tarjetas principales: radio cercano a 12 px.
- Bordes finos y suaves.
- Sombras discretas solo para separar superficies, no para crear profundidad ficticia.
- Deja espacio alrededor de grupos de información; no agrandes el espacio entre datos que necesitan compararse.

## Tarjetas

Una tarjeta debe cumplir al menos una función: agrupar información relacionada, separar una tarea o dar contexto a una acción.

- No uses tarjetas para cada fila de una tabla.
- No anides tarjetas dentro de tarjetas por decoración.
- Usa encabezado de tarjeta con título claro y una descripción corta cuando aporte contexto.
- Los indicadores se muestran dentro de una tarjeta compacta y solo cuando son útiles.
- Las tarjetas clicables necesitan estados hover y focus; las tarjetas estáticas no deben aparentar que se pueden abrir.
- En móvil, las tarjetas se apilan y no deben forzar columnas estrechas.

## Estructura de página

1. Breadcrumbs cuando exista jerarquía de navegación.
2. Título, contexto breve y acción principal.
3. Resumen numérico opcional, solo si responde una pregunta útil.
4. Búsqueda y filtros juntos, cerca del contenido que filtran.
5. Contenido principal como tabla, calendario, acordeón, lista o formulario según la tarea.
6. Paginación y estados vacíos junto al contenido.

No todas las páginas necesitan indicadores. Un formulario normalmente no necesita tarjetas de métricas.

## Botones y acciones

- Primario: la acción principal de la pantalla.
- Secundario: acción alternativa o navegación.
- Terciario/ghost: acción de bajo peso visual.
- Destructivo: eliminar, anular o acciones irreversibles; nunca lo presentes como acción primaria rutinaria.
- El texto debe incluir un verbo y el objeto cuando sea posible: “Guardar cambios”, “Agregar curso”, “Exportar Excel”.
- Los iconos acompañan el texto; un botón solo con icono necesita etiqueta accesible y tooltip cuando corresponda.

## Datos y estados

- Presenta los datos alrededor de la decisión que debe tomar la persona.
- Usa tablas para comparar registros homogéneos; listas para contenido corto; acordeones para secciones independientes; calendarios para horarios y eventos.
- Diferencia datos asignados, calculados, pendientes y ejecutados. No los mezcles bajo un mismo rótulo.
- Cada estado debe tener texto además de color.
- No inventes indicadores, porcentajes ni progresos para llenar espacio.

## Formularios

- Agrupa campos según la tarea, no según la estructura de la base de datos.
- Etiquetas visibles y específicas; no dependas del placeholder.
- Muestra validación junto al campo que la origina.
- Los botones de guardar indican qué guardan.
- Evita mostrar opciones técnicas al usuario final si puede elegir mediante términos de negocio.

## Movimiento

- Anima únicamente feedback, apertura/cierre, cambio de estado y continuidad espacial.
- Prefiere transiciones breves y específicas.
- No animes números sin una razón funcional.
- Respeta `prefers-reduced-motion`.
- Nunca sacrifiques respuesta rápida o accesibilidad por un efecto.

## Modo oscuro y responsive

Cada cambio visual debe revisar:
- Tema claro y oscuro.
- Escritorio y móvil.
- Títulos largos y nombres extensos.
- Tablas anchas y filtros.
- Focus visible y navegación por teclado.
- Estados vacío, error, éxito, carga y deshabilitado.

## Componentes y clases compartidas

Antes de crear otra clase, revisa si se puede reutilizar:
- `.page-head`
- `.card`, `.card-head`, `.card-description`
- `.stat-card`
- `.filter-bar`, `.accounts-toolbar`, `.course-toolbar`
- `.table-wrap`
- `.section-status`
- `.btn` y sus variantes existentes

Si agregas un patrón reutilizable, documenta su propósito y úsalo al menos en el contexto para el que se creó. No generes una segunda librería visual en paralelo.


## Estándar obligatorio de página (implementación)

El archivo `public/css/ui-standard.css` implementa este estándar y se carga desde `layouts/app.blade.php` después de los estilos de las vistas. Es la capa compartida de presentación para todas las páginas autenticadas.

### Contenedor y espaciado

- Usa el ancho disponible sin provocar desbordamiento. El contenedor de contenido tiene un máximo de 1680 px y se centra cuando la pantalla es más ancha.
- Gutter de página: `clamp(16px, 2vw, 30px)` en escritorio; 16 px en tablet; 12 px en móvil.
- Escala permitida de espacio: 4, 8, 12, 16, 20, 24 y 32 px. Usa esa escala para separación, padding y márgenes.
- Cards principales: 20 px de padding en escritorio, 16 px en tablet y 14 px en móvil; radio 12 px y separación vertical 16 px.
- No agregues un contenedor o card adicional si el contenido ya está agrupado en una sección clara. Los paneles desplegables estructurales conservan su composición propia.

### Títulos y subtítulos

- Un único H1 por página: 25–30 px en escritorio y 24 px en móvil; alineado a la izquierda.
- Eyebrow: 10 px, mayúsculas y peso 800. Solo clasifica el módulo o contexto; no repite el H1.
- Subtítulo de página: 12.5 px, máximo aproximado de 70 caracteres por línea, una o dos líneas y solo aporta contexto o explica la tarea.
- H2: 16 px para secciones; H3: 13.5 px para subsecciones.
- Descripción de card: 12 px, máximo 76 caracteres por línea. Texto de ayuda: 11 px.
- No uses `.page-head` dentro de cada fila, semana o tarjeta. Para encabezados repetibles usa un patrón específico de subsección.
- No repitas el mismo destino en breadcrumb y botón “Volver”, salvo que la jerarquía no sea visible.

### Tarjetas, indicadores y contenido

- Una card debe agrupar información relacionada, una tarea o un bloque funcional.
- Los indicadores son opcionales. No repitas en métricas lo que ya muestra el encabezado, el filtro o el listado.
- No uses tarjetas grandes para metadatos de procedencia como versión de prompt, hora de generación o formato. Colócalos en una fila compacta de contexto.
- Mantén métricas que permitan decidir o actuar: conflictos de horario, pendientes reales, cantidad por ámbito o fechas próximas.
- Evita contadores redundantes, etiquetas que solo llenan espacio e instrucciones que repiten el subtítulo.
- El contenido denso se presenta en tablas, listas o acordeones, no en una sucesión de tarjetas anidadas.

### Filtros y búsqueda

- Toda barra de filtros de una lista o tabla debe incluir la clase `.filter-bar`. Puede conservar una clase de módulo como modificador: `class="filter-bar holiday-filters"`.
- Orden por defecto: búsqueda primero; filtros de contexto después; filtros de estado/fecha a continuación; acción Filtrar y acción Limpiar al final.
- En escritorio los controles se alinean en una sola fila flexible si hay espacio. En tablet pasan a dos columnas; en móvil se apilan en una columna.
- El campo de búsqueda recibe el mayor espacio. Los selects no deben encogerse hasta ocultar su texto.
- Limpiar solo aparece cuando hay filtros aplicados. La paginación permanece junto al contenido filtrado.
- No crees una clase nueva de toolbar sin antes revisar `.filter-bar` y los modificadores existentes.

### Formularios, botones y tablas

- Controles de formulario: altura mínima de 40 px y radio 8 px; en móvil los botones importantes tienen área táctil de al menos 44 px.
- En formularios, la etiqueta visible está encima del control. Los campos relacionados comparten fila en escritorio y se apilan en móvil.
- Tablas anchas se desplazan dentro de `.table-wrap`; nunca deben ampliar el ancho de toda la página.
- Evita estilos de layout inline (`style="margin..."`, `style="padding..."`, `style="width..."`). Crea una clase con nombre semántico y úsala desde la hoja compartida cuando el patrón sea reutilizable.

### Responsive, tema y excepciones

- Cada vista debe revisarse a 1440 px, 1024 px y 390 px, en tema claro y oscuro.
- Los nombres largos, formularios, filtros, acciones y tablas deben conservar el ancho disponible sin solaparse.
- Las vistas con calendarios, árbol, editor de horarios o wizard pueden tener reglas específicas, pero no deben redefinir el layout global, la escala de página, el H1, el card base ni el toolbar de filtros.
- Añade una excepción local solo cuando resuelva una necesidad de dominio real. Documenta la clase y evita duplicar reglas ya definidas en `public/css/ui-standard.css`.
