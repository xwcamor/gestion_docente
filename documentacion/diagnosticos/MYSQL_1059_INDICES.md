# Corrección MySQL 1059 — índices largos

MySQL limita el nombre de cada identificador a 64 caracteres. Laravel, si no
se indica un nombre, construye el nombre del índice a partir de la tabla y sus
columnas. El índice de `curriculum_content_items` superaba ese límite.

## Nombre correcto

- Tabla: `curriculum_content_items`
- Columnas: `curriculum_week_id`, `category`, `approved`
- Nombre explícito: `cci_week_category_approved_idx`

La migración `2026_11_23_000016_create_curriculum_tables.php` y el SQL único
`database/gestion_docente.sql` usan el mismo nombre.

El índice de `generated_documents` también está acortado a
`gd_mod_type_ver_idx`.

## Si la migración ya falló

La creación de tablas e índices en MySQL puede dejar objetos parciales porque
el DDL no siempre se revierte como una transacción. Como esta instalación se
reinicia desde cero, elimina la base `gestion_docente` antes de repetir el
proceso. No vuelvas a ejecutar la migración fallida sobre tablas parciales.

## Instalación recomendada

1. Importar `database/gestion_docente.sql` (un único archivo).
2. El SQL crea todo el esquema y registra las 18 migraciones como aplicadas.
3. Ejecutar `php artisan migrate --seed`: no debe recrear tablas; carga los
   feriados mediante los seeders.
