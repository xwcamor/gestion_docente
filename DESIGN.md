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
