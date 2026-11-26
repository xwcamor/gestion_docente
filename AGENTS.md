# Instrucciones para agentes de desarrollo

Antes de modificar una interfaz, lee estos dos documentos:

1. [DESIGN.md](DESIGN.md): sistema visual y patrones propios de Gestión Docente.
2. [Guía UI/UX reutilizable](docs/GUIA_UI_UX_FUTUROS_SISTEMAS.md): método de trabajo y catálogo de skills.

## Contexto técnico obligatorio

- Proyecto: Gestión Docente.
- Backend y vistas: Laravel 13, PHP 8.3, Blade.
- Interactividad: JavaScript del proyecto y componentes Blade.
- Estilos: CSS existente en `resources/views/layouts/app.blade.php` y estilos específicos en vistas.
- Conserva esta arquitectura. No introduzcas React, Tailwind, Bootstrap, shadcn ni nuevas dependencias visuales sin una razón concreta y aprobación del usuario.
- Mantén los cambios directamente en `main`; no crees ramas ni PR salvo instrucción expresa del usuario.

## Reglas de diseño

- Primero entiende la tarea real, el usuario, los datos y la acción principal. No empieces eligiendo colores o tarjetas.
- Reutiliza los tokens y patrones de `DESIGN.md`. No inventes una paleta nueva en cada módulo.
- Usa un solo H1 por pantalla, H2 para secciones y H3 para subsecciones. Los títulos deben explicar qué contiene el área.
- Una tarjeta agrupa información relacionada. No conviertas cada fila, dato o campo en una tarjeta. Para listas densas, usa tablas; para procesos relacionados, acordeones; para formularios extensos, secciones claras.
- Los indicadores numéricos son opcionales: solo muestra métricas que respondan una pregunta o ayuden a tomar una decisión. No inventes cifras.
- Los estados deben ser semánticos y consistentes: completado, en proceso, pendiente, error. No dependas solo del color.
- Un área debe tener una acción principal claramente identificada. Las acciones secundarias deben verse menos prominentes. Las acciones destructivas deben ser inequívocas.
- Los botones deben nombrar la acción y el objeto: “Guardar horario”, no “Aceptar” cuando el contexto no sea obvio.
- Los textos deben ser breves, concretos y orientar al siguiente paso. Los errores deben decir qué ocurrió y cómo corregirlo.
- Respeta los colores de marca configurables en el sistema. Comprueba contraste en modo claro y oscuro.
- Conserva etiquetas, nombres académicos y reglas del dominio. No cambies “TEC”, “TALL”, teoría o práctica sin validar el significado.

## Interacción y accesibilidad

- Cada control debe tener etiqueta, estado visible y respuesta clara.
- Mantén navegación por teclado, foco visible, etiquetas accesibles y áreas táctiles razonables.
- Añade movimiento solo cuando explique un cambio o confirme una acción. Respeta `prefers-reduced-motion`.
- No animes todos los elementos ni ralentices tareas repetitivas.
- Comprueba estados hover, focus, active, disabled, loading, empty, error y success.

## Flujo obligatorio antes de entregar UI

1. Inspecciona la vista, controlador, rutas, componentes y estilos relacionados.
2. Escribe una intención breve: problema, usuario, acción principal y jerarquía de información.
3. Propón el cambio más pequeño que resuelva el problema sin duplicar patrones.
4. Implementa usando el sistema existente.
5. Revisa escritorio y móvil, modo claro y oscuro, teclado y datos largos.
6. Revisa textos, contraste, estados y posibles regresiones.
7. Ejecuta las comprobaciones disponibles y declara expresamente lo que no se pudo probar.

## Selección de skills

No ejecutes las diez skills en cada tarea. Escoge las que resuelvan el problema:

- Auditoría y simplificación: Anti UI Slop + Vercel Web Design Guidelines.
- Interacciones y animaciones: Emil Design Engineering o Make Interfaces Feel Better.
- Dirección visual de una pantalla interna o dashboard: Anthropic Frontend Design o UI/UX Pro Max. Taste Skill se reserva para páginas públicas/marketing; su documentación no la recomienda para dashboards ni tablas.
- Sistema de diseño, componentes y datos: UI/UX Pro Max.
- Revisión de microcopy: Hallmark más la lista de textos de la guía.
- Demostración animada de un recorrido: Stageshow. No uses su kit de UI falsa como lógica de la aplicación real.
- “Think UI” se usa en este repositorio como criterio de presentación de datos descrito en la guía; no afirmes que es una skill instalada mientras no haya una fuente canónica verificable.

No instales paquetes, fuentes, librerías de animación ni scripts externos solo por recomendación de una skill. Verifica primero compatibilidad, licencia, mantenimiento y necesidad real.


## Estándar de interfaz obligatorio para toda página nueva o existente

- Fuente de verdad visual: `DESIGN.md`; implementación CSS: `public/css/ui-standard.css`. La hoja se carga desde `layouts/app.blade.php` después de los estilos de vista.
- Antes de crear o modificar una página, revisa `DESIGN.md` y las clases existentes. No crees otro sistema de tokens ni una segunda librería de tarjetas.
- Usa un solo H1 y respeta la escala H1/H2/H3, eyebrow, subhead y card-description definida en `DESIGN.md`.
- Usa la escala de espaciado 4/8/12/16/20/24/32 px. No introduzcas márgenes y paddings arbitrarios para “alinear” una pantalla.
- Todas las cards principales usan el padding, radio y separación global. No añadas padding local salvo una excepción funcional documentada.
- Toda barra de búsqueda/filtros de una lista o tabla debe llevar `.filter-bar`; agrega la clase propia del módulo solo como modificador. Ordena búsqueda primero, criterios después y botones al final.
- No muestres KPI por decoración. Antes de retirar o agregar un indicador, verifica si ayuda a decidir y si no duplica el total, el filtro o la tabla.
- Elimina instrucciones repetidas, metadatos técnicos sobredimensionados y botones de regreso duplicados. No elimines datos académicos, trazabilidad, advertencias ni estados que cambien decisiones.
- Evita estilos inline para márgenes, padding, ancho, colores o tipografía. Usa clases semánticas y la hoja global.
- En módulos existentes, realiza la normalización de estructura y filtros sin alterar reglas de negocio, nombres del dominio, datos, validaciones, rutas o permisos.
- Revisión obligatoria: 1440 px, 1024 px y 390 px; tema claro y oscuro; foco/teclado; textos y nombres largos; tabla ancha; estados vacío/error/éxito/deshabilitado.
- Antes de entregar, revisa si la vista tiene un H1, subtítulo útil, filtros ordenados, cards con propósito, indicadores no redundantes y padding estándar. Ejecuta las comprobaciones disponibles y di qué no se pudo probar.
