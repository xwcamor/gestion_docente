# Guía UI/UX para futuros sistemas

Esta guía convierte una colección de skills de diseño en un método práctico para construir aplicaciones más claras, profesionales y fáciles de usar. Sirve para Gestión Docente y como plantilla de criterio para nuevos sistemas Laravel, SaaS, paneles administrativos y aplicaciones web.

**No significa que todas las skills estén instaladas en el entorno de desarrollo.** Son referencias y flujos que se seleccionan según la tarea. Las skills de agentes se instalan en el agente de programación, no como dependencias de Laravel.

## 1. Las referencias y cómo utilizarlas

| Skill / criterio | Cuándo usarlo | Qué revisar o mejorar |
|---|---|---|
| **Anti UI Slop** | Auditoría, simplificación, rediseño y revisión antes de entregar | Evitar interfaces genéricas, tarjetas innecesarias, adornos sin propósito y estados incompletos. Empezar por el usuario, la tarea y los datos reales. |
| **Think UI — presentación de datos** | Dashboards, reportes, tablas, indicadores y calendarios | Ordenar los datos para responder una pregunta: qué pasó, qué falta, qué está fuera de rango y qué acción sigue. En este proyecto es un criterio de trabajo; no se ha identificado una skill pública canónica con ese nombre exacto. |
| **Taste Skill** | Sitios públicos, landing pages, portfolios y páginas de producto | Definir dirección visual, composición, tipografía, fotografías y revisar el resultado en navegador. La documentación actual indica expresamente que no es para dashboards, tablas ni interfaces internas de producto. |
| **Emil Design Engineering** | Componentes, feedback, microinteracciones y animación | Decidir qué merece movimiento, cómo responde un botón, cómo se abre un panel y cómo se conserva la continuidad sin ralentizar el trabajo. |
| **Vercel Web Design Guidelines** | Revisión de interfaces web antes de entregar | Contraste, teclado, foco, etiquetas, formularios, navegación, estados, responsive, movimiento reducido y claridad del contenido. |
| **Anthropic Frontend Design** | Crear una pantalla o renovar la dirección visual de una interfaz | Definir intención visual, tipografía, color, espacio y jerarquía; evitar la apariencia de una plantilla genérica. |
| **Hallmark** | Auditoría de diseño, rediseño y revisión del contenido visible | Revisar si el diseño y el texto parecen hechos para el producto. Aplicar una revisión de microcopy: etiquetas, instrucciones, confirmaciones, errores y estados vacíos. No sustituye la validación funcional ni el criterio del negocio. |
| **Make Interfaces Feel Better** | Pulido final de componentes | Bordes, sombras, radios, alineación óptica, iconos, estados hover/active, áreas de interacción y pequeños detalles que mejoran la sensación de calidad. |
| **UI/UX Pro Max** | Definir o revisar un sistema de diseño completo | Recomendar estilos, paletas, combinaciones tipográficas, patrones de producto, gráficos y guías por stack. Para Laravel, indicar explícitamente que el stack es Blade/CSS/JS. |
| **Stageshow** | Explicar un recorrido o crear una demostración visual animada | Construir una historia con cursor, ventanas y escenas simplificadas para enseñar un flujo. Es para demos o walkthroughs; no sustituye los controles reales ni la lógica de una aplicación. |

### Fuentes principales

- [Anti UI Slop](https://github.com/minagishl/anti-ui-slop)
- [Taste Skill](https://github.com/Leonxlnx/taste-skill)
- [Emil Kowalski — Skills](https://github.com/emilkowalski/skills)
- [Vercel — Agent Skills / Web Design Guidelines](https://github.com/vercel-labs/agent-skills)
- [Vercel — Web Interface Guidelines](https://github.com/vercel-labs/web-interface-guidelines)
- [Anthropic — Skills](https://github.com/anthropics/skills)
- [Hallmark](https://github.com/Nutlope/hallmark)
- [Make Interfaces Feel Better](https://github.com/jakubkrehel/make-interfaces-feel-better)
- [UI/UX Pro Max](https://github.com/nextlevelbuilder/ui-ux-pro-max-skill)
- [Stageshow](https://github.com/benjitaylor/skills/tree/main/stageshow)

## 2. No se deben instalar las diez para cada trabajo

Varias referencias se solapan. Instalar o invocar todo a la vez puede producir instrucciones contradictorias y trabajo innecesario.

### Combinaciones recomendadas

| Trabajo | Combinación |
|---|---|
| Mejorar una aplicación administrativa existente | Anti UI Slop + Vercel Guidelines + Emil Design Engineering |
| Pulir tarjetas, títulos, botones y controles | Make Interfaces Feel Better + Vercel Guidelines |
| Crear una identidad visual nueva | Anthropic Frontend Design + UI/UX Pro Max |
| Rediseñar una interfaz que parece genérica | Taste Skill o Hallmark, junto con una revisión responsive |
| Mejorar tablas, indicadores y reportes | Think UI (criterio de datos) + UI/UX Pro Max + Vercel Guidelines |
| Enseñar cómo funciona un flujo | Stageshow |
| Revisar microcopy y textos de interfaz | Hallmark + checklist de UX writing de esta guía |

Para este proyecto se usa como base: **Anti UI Slop, Vercel Guidelines y Emil Design Engineering**. Make Interfaces Feel Better se usa en el pulido. Las demás se activan cuando la tarea lo justifica.

## 3. Método de trabajo para cualquier pantalla

### Paso 1 — Entender el trabajo

Antes de cambiar el diseño, responde:
- ¿Quién usa esta pantalla?
- ¿Qué necesita conseguir?
- ¿Cuál es la acción principal?
- ¿Qué información necesita antes de actuar?
- ¿Qué errores o dudas son probables?

### Paso 2 — Think UI: presentar la información para decidir

No empieces dibujando tarjetas. Primero organiza la información:
- **Contexto:** en qué módulo, periodo o registro se encuentra la persona.
- **Situación:** qué está completo, pendiente, vencido o fuera de rango.
- **Comparación:** qué dato se debe comparar y con qué referencia.
- **Acción:** qué debe hacer a continuación.
- **Detalle:** el registro, tabla o calendario que explica el resultado.

Reglas:
- Métricas solo cuando ayudan a decidir; no son obligatorias en todos los módulos.
- Los valores calculados se distinguen de los valores ingresados.
- No mezclar asignado, programado, ejecutado y pendiente.
- Unidades junto a las cifras; porcentajes con su denominador cuando sea relevante.
- Tablas para muchos registros comparables, calendario para tiempo, acordeón para bloques independientes y tarjetas para agrupaciones pequeñas.
- Nunca inventar actividad, progreso, datos, porcentajes o resultados para que una pantalla parezca llena.

### Paso 3 — Definir jerarquía

Cada pantalla debe tener:
1. Un título H1 que identifique la tarea o el objeto.
2. Un contexto breve cuando sea necesario.
3. Una acción principal.
4. Secciones H2 con nombres concretos.
5. Subtítulos H3 solo si organizan contenido real.

No uses títulos genéricos como “Información”, “Datos” o “Gestión” cuando se puede nombrar el objeto: “Horario semanal”, “Padrón de alumnos”, “Notas de evaluación”.

### Paso 4 — Elegir el patrón visual correcto

- **Card:** agrupa información relacionada; evita una card por campo o fila.
- **Tabla:** muchos registros homogéneos, búsqueda, filtros y paginación.
- **Acordeón:** secciones independientes que pueden abrirse sin abandonar la página.
- **Modal:** una tarea corta y enfocada. Si requiere contexto, calendario completo o varios pasos, utiliza página completa.
- **Drawer:** edición lateral cuando se necesita conservar visible el contenido de fondo.
- **Calendario:** horarios, fechas y eventos, con una acción clara para añadir o editar.
- **Formulario:** campos agrupados por tarea y orden de uso, no por tablas de la base de datos.

### Paso 5 — Definir acciones y estados

- Una acción primaria por zona de trabajo.
- Botones secundarios menos prominentes.
- Acciones destructivas claras y separadas.
- Etiquetas de estado consistentes y con texto, no solo color.
- Cada formulario contempla validación, envío, bloqueo de doble envío, éxito y error.
- Los estados vacíos explican qué falta y cómo empezar.

### Paso 6 — Aplicar detalles, movimiento y accesibilidad

- Hover, focus, active, disabled y loading deben verse distintos.
- Movimiento breve solo si explica un cambio o confirma una acción.
- Respetar `prefers-reduced-motion`.
- Contraste legible en tema claro y oscuro.
- Navegación por teclado y foco visible.
- Controles táctiles cómodos.
- No depender del color como única forma de comunicar estado.

## 4. Revisión de textos de interfaz

Usa Hallmark como una pasada de crítica del producto y revisa manualmente:

| Elemento | Pregunta de revisión |
|---|---|
| Título | ¿Identifica exactamente qué se administra? |
| Botón | ¿Dice qué hará y sobre qué objeto? |
| Etiqueta | ¿Se entiende sin leer un párrafo de ayuda? |
| Instrucción | ¿Explica lo mínimo para completar el campo? |
| Error | ¿Explica qué está mal y cómo corregirlo? |
| Confirmación | ¿Indica qué se guardó, eliminó o modificó? |
| Estado vacío | ¿Explica por qué no hay datos y cómo agregarlos? |
| Ayuda | ¿Resuelve una duda real o repite lo que ya se ve? |

Evita textos de marketing en herramientas de trabajo, tecnicismos innecesarios y explicaciones extensas dentro de formularios.

## 5. Checklist de entrega

Antes de cerrar un cambio de interfaz, revisa:

- [ ] La jerarquía visual deja clara la tarea principal.
- [ ] Los títulos y nombres de sección son concretos.
- [ ] Las tarjetas agrupan contenido y no duplican tablas.
- [ ] Los indicadores tienen utilidad y datos reales.
- [ ] Los botones tienen prioridad y textos inequívocos.
- [ ] Los filtros están junto al contenido que modifican.
- [ ] Hay paginación cuando el volumen lo exige.
- [ ] Se revisaron estados vacío, error, éxito, carga y deshabilitado.
- [ ] Contraste y foco son visibles.
- [ ] Tema claro y oscuro son legibles.
- [ ] La vista funciona a escritorio, tablet y móvil.
- [ ] Los nombres largos, cifras grandes y tablas anchas no rompen el diseño.
- [ ] El movimiento respeta la preferencia de movimiento reducido.
- [ ] No se cambió lógica de negocio ni se inventaron datos para mejorar la apariencia.
- [ ] Las comprobaciones funcionales disponibles se ejecutaron y se informó lo que no se pudo probar.

## 6. Instalación para agentes de programación

Las skills se instalan en el entorno del agente (Codex, Claude Code, Cursor, etc.), no en Composer ni en la base de datos Laravel. Ejecuta únicamente las que vayas a utilizar. La CLI solicita dónde instalarlas y para qué agente; después inicia una nueva sesión del agente para que detecte las skills.

Ejemplos de instalación con el CLI `npx skills` (revisa siempre la guía upstream antes de instalar o actualizar):

```bash
# Anti UI Slop
npx skills add https://github.com/minagishl/anti-ui-slop --skill anti-ui-slop

# Emil Design Engineering
npx skills add https://github.com/emilkowalski/skills --skill emil-design-eng

# Vercel Web Design Guidelines
npx skills add https://github.com/vercel-labs/agent-skills --skill web-design-guidelines

# Anthropic Frontend Design
npx skills add anthropics/skills --skill frontend-design

# Taste Skill (Node 22+ y Chrome/Edge/Chromium)
npx skills add https://github.com/Leonxlnx/taste-skill --skill "design-taste-frontend"

# Hallmark
npx skills add https://github.com/nutlope/hallmark --skill hallmark

# Make Interfaces Feel Better
npx skills add jakubkrehel/make-interfaces-feel-better --skill make-interfaces-feel-better

# UI/UX Pro Max
npx skills add nextlevelbuilder/ui-ux-pro-max-skill --skill ui-ux-pro-max

# Stageshow
npx skills add benjitaylor/skills --skill stageshow
```

**Think UI** se conserva como un criterio de presentación de datos en esta guía; no se encontró una skill pública canónica con ese nombre exacto, por lo que no se incluye un comando de instalación inventado.

### Cuáles aplicar en Gestión Docente

Para este panel administrativo, usa como núcleo Anti UI Slop + Vercel Web Design Guidelines + Emil Design Engineering. Para pulir bordes, tipografía, iconos y áreas táctiles añade Make Interfaces Feel Better. Usa UI/UX Pro Max cuando diseñes o reorganices un sistema de componentes o necesites comparar patrones.

**Taste Skill tiene una limitación importante:** su documentación actual dice que está orientada a sitios públicos (landing, marketing, portfolio y páginas de producto), no a dashboards, tablas ni interfaces administrativas. No la ejecutes en las pantallas internas de Gestión Docente; resérvala para una futura web pública o página de presentación. Para el trabajo de textos, Hallmark puede criticar consistencia y contenido visible, pero combina su revisión con la lista de microcopy de esta guía y la validación de términos del negocio. Stageshow sirve para demostraciones y recorridos simulados, no para controlar la aplicación real.

### Requisitos y precauciones

- Taste Skill requiere Node 22+ y Chrome, Edge o Chromium. Su herramienta está orientada a páginas públicas; no es una dependencia del runtime Laravel.
- UI/UX Pro Max utiliza Python 3 para su buscador local y admite contexto específico de Laravel/Blade.
- Stageshow produce demos de UI simulada con React y CSS. No lo introduzcas en una aplicación Blade solo para decorar formularios reales.
- No instales librerías de animación, fuentes ni frameworks CSS como efecto secundario de una recomendación. Evalúa primero la necesidad y compatibilidad con el stack existente.
- Las skills son instrucciones para el agente, no garantía de una interfaz correcta. El resultado debe revisarse visual y funcionalmente.
- El usuario debe revisar las licencias, dependencias y scripts de cada skill antes de instalarla en un entorno de trabajo.

## 7. Cómo reutilizar esta guía en otro proyecto

Copia estos archivos al nuevo repositorio:
- `AGENTS.md`: reglas para el agente que modifica el código.
- `DESIGN.md`: tokens y patrones específicos del producto nuevo.
- `docs/GUIA_UI_UX_FUTUROS_SISTEMAS.md`: método de trabajo y referencias de skills.

En cada nuevo sistema, reemplaza los tokens y patrones de `DESIGN.md` después de inspeccionar su marca y su stack. No copies automáticamente la paleta verde de Gestión Docente.
