-- LP CONSULTING : structure de la base de données (MySQL 5.7+ / MariaDB 10.3+).
-- Les tables et les contenus initiaux (database/seeds/*.json) sont créés par bin/install.php ou /install.

SET NAMES utf8mb4;

-- Textes du site, regroupés par section (site, home, missions, references, legal), au format JSON.
CREATE TABLE IF NOT EXISTS content_sections (
  section_key VARCHAR(40) NOT NULL,
  data        LONGTEXT    NOT NULL,
  updated_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (section_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Projets / références (position = ordre d'affichage).
CREATE TABLE IF NOT EXISTS projects (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug        VARCHAR(80)  NOT NULL,
  category    ENUM('etudes','travaux') NOT NULL DEFAULT 'travaux',
  title       VARCHAR(255) NOT NULL,
  client      VARCHAR(255) NOT NULL DEFAULT '',
  role        VARCHAR(255) NOT NULL DEFAULT '',
  duration    VARCHAR(120) NOT NULL DEFAULT '',
  budget      VARCHAR(60)  NOT NULL DEFAULT '',
  budget_note VARCHAR(255) NOT NULL DEFAULT '',
  status      ENUM('won','lost','works') NOT NULL DEFAULT 'works',
  image       VARCHAR(255) NOT NULL DEFAULT '',
  image_type  ENUM('photo','visual','none') NOT NULL DEFAULT 'photo',
  description TEXT         NOT NULL,
  featured    TINYINT(1)   NOT NULL DEFAULT 0,
  visible     TINYINT(1)   NOT NULL DEFAULT 1,
  position    INT          NOT NULL DEFAULT 0,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_projects_slug (slug),
  KEY ix_projects_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comptes d'administration.
CREATE TABLE IF NOT EXISTS admin_users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email         VARCHAR(190) NOT NULL,
  name          VARCHAR(120) NOT NULL DEFAULT '',
  password_hash VARCHAR(255) NOT NULL,
  last_login_at DATETIME     NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historique des versions (instantané complet après chaque modification).
CREATE TABLE IF NOT EXISTS revisions (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  label      VARCHAR(120) NOT NULL DEFAULT '',
  snapshot   LONGTEXT     NOT NULL,
  user_id    INT UNSIGNED NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_revisions_user (user_id),
  CONSTRAINT fk_revisions_user FOREIGN KEY (user_id) REFERENCES admin_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tentatives de connexion (blocage après plusieurs échecs).
CREATE TABLE IF NOT EXISTS login_attempts (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip           VARCHAR(45)  NOT NULL,
  email        VARCHAR(190) NOT NULL DEFAULT '',
  success      TINYINT(1)   NOT NULL DEFAULT 0,
  attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_attempts_ip (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demandes reçues par le formulaire de contact.
CREATE TABLE IF NOT EXISTS messages (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(120) NOT NULL,
  company    VARCHAR(160) NOT NULL DEFAULT '',
  email      VARCHAR(190) NOT NULL,
  phone      VARCHAR(30)  NOT NULL DEFAULT '',
  mission    VARCHAR(120) NOT NULL DEFAULT '',
  message    TEXT         NOT NULL,
  ip         VARCHAR(45)  NOT NULL DEFAULT '',
  user_agent VARCHAR(255) NOT NULL DEFAULT '',
  mailed     TINYINT(1)   NOT NULL DEFAULT 0,
  read_at    DATETIME     NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_messages_created (created_at),
  KEY ix_messages_ip (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
