-- Esquema base de Gestión Docente.
-- Este archivo es consumido por la migración inicial de Laravel.
-- No importar manualmente: ejecutar php artisan migrate:fresh --seed.

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  email_verified_at TIMESTAMP NULL,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(255) NOT NULL DEFAULT 'profesor',
  remember_token VARCHAR(100) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX users_name_idx (name)
) ENGINE=InnoDB;

CREATE TABLE password_reset_tokens (
  email VARCHAR(255) PRIMARY KEY,
  token VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE cache (
  `key` VARCHAR(191) PRIMARY KEY,
  value MEDIUMTEXT NOT NULL,
  expiration INT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE cache_locks (
  `key` VARCHAR(191) PRIMARY KEY,
  owner VARCHAR(191) NOT NULL,
  expiration INT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE sessions (
  id VARCHAR(191) PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  payload LONGTEXT NOT NULL,
  last_activity INT NOT NULL,
  INDEX sessions_user_id_idx (user_id),
  INDEX sessions_last_activity_idx (last_activity),
  CONSTRAINT sessions_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE professors (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  institutional_id VARCHAR(255) NULL,
  full_name VARCHAR(255) NOT NULL,
  school VARCHAR(255) NULL,
  career VARCHAR(255) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT professors_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cycles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  professor_id BIGINT UNSIGNED NOT NULL,
  academic_year SMALLINT UNSIGNED NOT NULL,
  cycle_code VARCHAR(20) NOT NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  status VARCHAR(255) NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX cycles_prof_year_code_idx (professor_id, academic_year, cycle_code),
  CONSTRAINT cycles_professor_fk FOREIGN KEY (professor_id) REFERENCES professors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE teaching_groups (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cycle_id BIGINT UNSIGNED NOT NULL,
  professor_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  assigned_at DATE NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX groups_cycle_prof_idx (cycle_id, professor_id),
  CONSTRAINT groups_cycle_fk FOREIGN KEY (cycle_id) REFERENCES cycles(id) ON DELETE CASCADE,
  CONSTRAINT groups_professor_fk FOREIGN KEY (professor_id) REFERENCES professors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE courses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  professor_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(80) NULL,
  name VARCHAR(255) NOT NULL,
  career VARCHAR(255) NULL,
  module VARCHAR(255) NULL,
  description TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX courses_prof_code_idx (professor_id, code),
  CONSTRAINT courses_professor_fk FOREIGN KEY (professor_id) REFERENCES professors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE curricula (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  professor_id BIGINT UNSIGNED NOT NULL,
  course_id BIGINT UNSIGNED NOT NULL,
  catalog_code VARCHAR(40) NULL,
  module_code VARCHAR(80) NULL,
  module_name VARCHAR(255) NULL,
  course_name VARCHAR(255) NULL,
  career VARCHAR(255) NULL,
  professional_school VARCHAR(255) NULL,
  occupational_module VARCHAR(255) NULL,
  semester VARCHAR(30) NULL,
  general_objective LONGTEXT NULL,
  source_filename VARCHAR(255) NULL,
  source_path VARCHAR(255) NULL,
  source_sha256 VARCHAR(64) NULL,
  extracted_text LONGTEXT NULL,
  source_metadata JSON NULL,
  version INT UNSIGNED NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  status VARCHAR(24) NOT NULL DEFAULT 'imported',
  imported_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX curricula_prof_course_active_idx (professor_id, course_id, is_active),
  CONSTRAINT curricula_professor_fk FOREIGN KEY (professor_id) REFERENCES professors(id) ON DELETE CASCADE,
  CONSTRAINT curricula_course_fk FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  CONSTRAINT curricula_imported_by_fk FOREIGN KEY (imported_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE course_assignments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teaching_group_id BIGINT UNSIGNED NOT NULL,
  course_id BIGINT UNSIGNED NOT NULL,
  curriculum_id BIGINT UNSIGNED NULL,
  assigned_at DATE NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY assignments_group_course_uq (teaching_group_id, course_id),
  INDEX assignments_curriculum_idx (curriculum_id),
  CONSTRAINT assignments_group_fk FOREIGN KEY (teaching_group_id) REFERENCES teaching_groups(id) ON DELETE CASCADE,
  CONSTRAINT assignments_course_fk FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  CONSTRAINT assignments_curriculum_fk FOREIGN KEY (curriculum_id) REFERENCES curricula(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE course_modalities (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_assignment_id BIGINT UNSIGNED NOT NULL,
  modality VARCHAR(8) NOT NULL,
  nrc VARCHAR(30) NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  total_hours DECIMAL(8,2) NULL,
  curriculum_start_week SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  active TINYINT(1) NOT NULL DEFAULT 1,
  period_label VARCHAR(255) NULL,
  semester VARCHAR(255) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY modalities_assignment_type_uq (course_assignment_id, modality),
  INDEX modalities_nrc_idx (nrc),
  CONSTRAINT modalities_assignment_fk FOREIGN KEY (course_assignment_id) REFERENCES course_assignments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE modality_schedules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_modality_id BIGINT UNSIGNED NOT NULL,
  day_of_week TINYINT UNSIGNED NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  classroom VARCHAR(255) NULL,
  block_type VARCHAR(255) NOT NULL DEFAULT 'CLASS',
  repeat_interval_weeks TINYINT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX modality_schedules_modality_idx (course_modality_id),
  CONSTRAINT modality_schedules_modality_fk FOREIGN KEY (course_modality_id) REFERENCES course_modalities(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE holidays (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  professor_id BIGINT UNSIGNED NULL,
  holiday_date DATE NOT NULL,
  name VARCHAR(255) NOT NULL,
  scope VARCHAR(255) NOT NULL DEFAULT 'NATIONAL',
  blocks_classes TINYINT(1) NOT NULL DEFAULT 1,
  source_url VARCHAR(255) NULL,
  source_note TEXT NULL,
  verified TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX holidays_date_blocks_idx (holiday_date, blocks_classes),
  CONSTRAINT holidays_professor_fk FOREIGN KEY (professor_id) REFERENCES professors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE curriculum_tasks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  curriculum_id BIGINT UNSIGNED NOT NULL,
  task_code VARCHAR(30) NOT NULL,
  title VARCHAR(255) NOT NULL,
  position INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY curr_tasks_curr_code_uq (curriculum_id, task_code),
  CONSTRAINT curr_tasks_curriculum_fk FOREIGN KEY (curriculum_id) REFERENCES curricula(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE curriculum_operations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  curriculum_task_id BIGINT UNSIGNED NOT NULL,
  operation_code VARCHAR(30) NOT NULL,
  description TEXT NOT NULL,
  operation_type VARCHAR(24) NOT NULL DEFAULT 'UNSPECIFIED',
  position INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY curr_ops_task_code_uq (curriculum_task_id, operation_code),
  CONSTRAINT curr_ops_task_fk FOREIGN KEY (curriculum_task_id) REFERENCES curriculum_tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE curriculum_weeks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  curriculum_id BIGINT UNSIGNED NOT NULL,
  curriculum_task_id BIGINT UNSIGNED NULL,
  week_number SMALLINT UNSIGNED NOT NULL,
  source_week_number SMALLINT UNSIGNED NULL,
  hours_theory DECIMAL(7,2) NULL,
  hours_practice DECIMAL(7,2) NULL,
  hours_virtual DECIMAL(7,2) NULL,
  is_evaluation TINYINT(1) NOT NULL DEFAULT 0,
  source_pages JSON NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY curr_weeks_curr_no_uq (curriculum_id, week_number),
  INDEX curr_weeks_curr_week_idx (curriculum_id, week_number),
  CONSTRAINT curr_weeks_curriculum_fk FOREIGN KEY (curriculum_id) REFERENCES curricula(id) ON DELETE CASCADE,
  CONSTRAINT curr_weeks_task_fk FOREIGN KEY (curriculum_task_id) REFERENCES curriculum_tasks(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE curriculum_content_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  curriculum_week_id BIGINT UNSIGNED NOT NULL,
  category VARCHAR(32) NOT NULL,
  content LONGTEXT NOT NULL,
  source_kind VARCHAR(24) NOT NULL DEFAULT 'SOURCE',
  approved TINYINT(1) NOT NULL DEFAULT 1,
  position INT UNSIGNED NOT NULL DEFAULT 0,
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX cci_week_category_approved_idx (curriculum_week_id, category, approved),
  CONSTRAINT curr_content_week_fk FOREIGN KEY (curriculum_week_id) REFERENCES curriculum_weeks(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE class_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_modality_id BIGINT UNSIGNED NOT NULL,
  curriculum_week_id BIGINT UNSIGNED NULL,
  curriculum_week_number SMALLINT UNSIGNED NULL,
  content_origin VARCHAR(20) NOT NULL DEFAULT 'manual',
  modality_schedule_id BIGINT UNSIGNED NULL,
  session_date DATE NOT NULL,
  session_number INT UNSIGNED NOT NULL,
  duration_minutes INT UNSIGNED NOT NULL DEFAULT 0,
  status VARCHAR(255) NOT NULL DEFAULT 'scheduled',
  topic VARCHAR(255) NULL,
  objective TEXT NULL,
  activities LONGTEXT NULL,
  evidence TEXT NULL,
  system_note TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY sessions_date_uq (course_modality_id, modality_schedule_id, session_date),
  INDEX sessions_modality_date_status_idx (course_modality_id, session_date, status),
  INDEX sessions_curriculum_week_idx (curriculum_week_id),
  INDEX sessions_schedule_idx (modality_schedule_id),
  CONSTRAINT sessions_modality_fk FOREIGN KEY (course_modality_id) REFERENCES course_modalities(id) ON DELETE CASCADE,
  CONSTRAINT sessions_curriculum_week_fk FOREIGN KEY (curriculum_week_id) REFERENCES curriculum_weeks(id) ON DELETE SET NULL,
  CONSTRAINT sessions_schedule_fk FOREIGN KEY (modality_schedule_id) REFERENCES modality_schedules(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE students (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  professor_id BIGINT UNSIGNED NOT NULL,
  institutional_id VARCHAR(80) NULL,
  username VARCHAR(255) NULL,
  surnames VARCHAR(255) NOT NULL,
  given_names VARCHAR(255) NOT NULL,
  last_access_source TEXT NULL,
  availability_source VARCHAR(255) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX students_prof_inst_idx (professor_id, institutional_id),
  INDEX students_prof_user_idx (professor_id, username),
  CONSTRAINT students_professor_fk FOREIGN KEY (professor_id) REFERENCES professors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE import_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_modality_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  stored_path VARCHAR(255) NOT NULL,
  detected_format VARCHAR(255) NULL,
  total_rows INT UNSIGNED NOT NULL DEFAULT 0,
  created_count INT UNSIGNED NOT NULL DEFAULT 0,
  updated_count INT UNSIGNED NOT NULL DEFAULT 0,
  skipped_count INT UNSIGNED NOT NULL DEFAULT 0,
  errors JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX import_batches_modality_idx (course_modality_id),
  CONSTRAINT import_batches_modality_fk FOREIGN KEY (course_modality_id) REFERENCES course_modalities(id) ON DELETE CASCADE,
  CONSTRAINT import_batches_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE enrollments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_modality_id BIGINT UNSIGNED NOT NULL,
  student_id BIGINT UNSIGNED NOT NULL,
  import_batch_id BIGINT UNSIGNED NULL,
  platform_grade DECIMAL(6,2) NULL,
  platform_last_access TEXT NULL,
  platform_availability VARCHAR(255) NULL,
  status VARCHAR(255) NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY enrollments_mod_student_uq (course_modality_id, student_id),
  INDEX enrollments_import_batch_idx (import_batch_id),
  CONSTRAINT enrollments_modality_fk FOREIGN KEY (course_modality_id) REFERENCES course_modalities(id) ON DELETE CASCADE,
  CONSTRAINT enrollments_student_fk FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT enrollments_import_batch_fk FOREIGN KEY (import_batch_id) REFERENCES import_batches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE evaluations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_modality_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  evaluation_date DATE NULL,
  weight DECIMAL(6,2) NULL,
  maximum_score DECIMAL(6,2) NOT NULL DEFAULT 20,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX evaluations_modality_idx (course_modality_id),
  CONSTRAINT evaluations_modality_fk FOREIGN KEY (course_modality_id) REFERENCES course_modalities(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE grades (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  enrollment_id BIGINT UNSIGNED NOT NULL,
  evaluation_id BIGINT UNSIGNED NOT NULL,
  score DECIMAL(6,2) NULL,
  origin VARCHAR(255) NOT NULL DEFAULT 'manual',
  observations TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY grades_enroll_eval_uq (enrollment_id, evaluation_id),
  INDEX grades_evaluation_idx (evaluation_id),
  CONSTRAINT grades_enrollment_fk FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
  CONSTRAINT grades_evaluation_fk FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE generated_documents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_modality_id BIGINT UNSIGNED NULL,
  teaching_group_id BIGINT UNSIGNED NULL,
  document_type VARCHAR(255) NOT NULL,
  version INT UNSIGNED NOT NULL DEFAULT 1,
  filename VARCHAR(255) NOT NULL,
  stored_path VARCHAR(255) NOT NULL,
  generated_by BIGINT UNSIGNED NOT NULL,
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX gd_mod_type_ver_idx (course_modality_id, document_type, version),
  INDEX gd_group_idx (teaching_group_id),
  CONSTRAINT gd_modality_fk FOREIGN KEY (course_modality_id) REFERENCES course_modalities(id) ON DELETE CASCADE,
  CONSTRAINT gd_group_fk FOREIGN KEY (teaching_group_id) REFERENCES teaching_groups(id) ON DELETE CASCADE,
  CONSTRAINT gd_generated_by_fk FOREIGN KEY (generated_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  queue VARCHAR(255) NOT NULL,
  payload LONGTEXT NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL,
  reserved_at INT UNSIGNED NULL,
  available_at INT UNSIGNED NOT NULL,
  created_at INT UNSIGNED NOT NULL,
  INDEX jobs_queue_idx (queue)
) ENGINE=InnoDB;

CREATE TABLE job_batches (
  id VARCHAR(191) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  total_jobs INT NOT NULL,
  pending_jobs INT NOT NULL,
  failed_jobs INT NOT NULL,
  failed_job_ids LONGTEXT NOT NULL,
  options MEDIUMTEXT NULL,
  cancelled_at INT NULL,
  created_at INT NOT NULL,
  finished_at INT NULL
) ENGINE=InnoDB;

CREATE TABLE failed_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uuid VARCHAR(36) NOT NULL UNIQUE,
  connection TEXT NOT NULL,
  queue TEXT NOT NULL,
  payload LONGTEXT NOT NULL,
  exception LONGTEXT NOT NULL,
  failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
