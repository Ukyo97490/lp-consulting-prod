-- LP CONSULTING : migration SEO, à exécuter dans phpMyAdmin (onglet SQL) AVANT l'envoi des fichiers.
-- MariaDB 10.3+ (o2switch). Aucune donnée n'est supprimée. Peut être relancée sans risque.

SET NAMES utf8mb4;

-- 1. Nouvelles structures
ALTER TABLE projects ADD COLUMN IF NOT EXISTS image_alt VARCHAR(255) NOT NULL DEFAULT '' AFTER image;
ALTER TABLE projects MODIFY status ENUM('won','lost','works','prep') NOT NULL DEFAULT 'works';

CREATE TABLE IF NOT EXISTS image_meta (
  path       VARCHAR(255) NOT NULL,
  width      INT UNSIGNED NOT NULL DEFAULT 0,
  height     INT UNSIGNED NOT NULL DEFAULT 0,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Textes alternatifs des projets (un texte saisi dans l'administration n'est pas remplacé)
UPDATE projects SET image_alt = 'Visuel du centre pénitentiaire de Mayotte (44 M€)' WHERE slug = 'centre-penitentiaire-mayotte' AND image_alt IN ('', 'Chantier du centre pénitentiaire de Mayotte (44 M€)');
UPDATE projects SET image_alt = 'Projet du nouvel aérogare de Mayotte (50 M€)' WHERE slug = 'aerogare-mayotte' AND image_alt = '';
UPDATE projects SET image_alt = 'Projet du Centre des Arts et de la Culture de Pointe-à-Pitre (31 M€)' WHERE slug = 'centre-arts-pointe-a-pitre' AND image_alt = '';
UPDATE projects SET image_alt = 'Perspective de l’opération CHAM, 14 logements à Saint-Denis' WHERE slug = 'operation-cham-saint-denis' AND image_alt = '';
UPDATE projects SET image_alt = 'Extension G1-G2 du CHU de Saint-Pierre' WHERE slug = 'chu-saint-pierre' AND image_alt = '';
UPDATE projects SET image_alt = 'Projet du nouveau quai du port de Longoni à Mayotte (73 M€)' WHERE slug = 'port-longoni' AND image_alt IN ('', 'Projet du port de Longoni à Mayotte (73 M€)');
UPDATE projects SET image_alt = 'Chantier de l’échangeur M24 de la Nouvelle Route du Littoral, La Possession' WHERE slug = 'nrl-m24-possession' AND image_alt IN ('', 'Échangeur M24 de la Nouvelle Route du Littoral, La Possession');
UPDATE projects SET image_alt = 'Visuel du TCSP de l’Étang-Salé (6 M€)' WHERE slug = 'tcsp-etang-sale' AND image_alt IN ('', 'Projet TCSP de l’Étang-Salé (6 M€)');

-- 3. Mail du client : les 2 opérations en cours pour NEXUS HABITAT, en tête de la rubrique travaux
UPDATE projects SET
  client = 'NEXUS HABITAT', role = 'Direction de travaux', budget = '950 k€ HT', status = 'prep',
  description = 'Construction de 14 logements. Lots Gros-Œuvre / Charpente Couverture / Étanchéité. Travaux en cours de préparation de chantier.'
WHERE slug = 'operation-cham-saint-denis';

UPDATE projects SET
  title = 'CHU de Saint-Pierre : extension G1-G2', client = 'NEXUS HABITAT', role = 'Direction de travaux', budget = '530 k€ HT', status = 'prep',
  description = 'Mission de direction de travaux en cours sur le chantier d’extension du CHU de Saint-Pierre. Lots Terrassement / VRD / Gros-Œuvre.'
WHERE slug = 'chu-saint-pierre';

UPDATE projects SET position = CASE slug
  WHEN 'operation-cham-saint-denis' THEN 3 WHEN 'chu-saint-pierre' THEN 4 WHEN 'port-longoni' THEN 5
  WHEN 'nrl-m24-possession' THEN 6 WHEN 'tcsp-etang-sale' THEN 7 ELSE position END
WHERE category = 'travaux';

-- 4. Titres et descriptions des pages
UPDATE content_sections SET data = JSON_SET(data,
  '$.seo_title', 'LP CONSULTING | Assistance et conseil BTP à La Réunion',
  '$.seo_description', 'LP CONSULTING accompagne les entreprises du BTP à La Réunion : études techniques, appels d’offres, préparation de chantier et direction de travaux.'
) WHERE section_key = 'home';

UPDATE content_sections SET data = JSON_SET(data,
  '$."etudes-techniques".intro_title', 'Études techniques et appels d’offres BTP à La Réunion',
  '$."etudes-techniques".seo_title', 'Études techniques et appels d’offres BTP | LP CONSULTING',
  '$."etudes-techniques".seo_description', 'Mémoires techniques, études de prix (DQE, BPE), sous-traitants et documents QSE : LP CONSULTING sécurise vos appels d’offres BTP à La Réunion.',
  '$."preparation-chantier".intro_title', 'Préparation et organisation de chantier à La Réunion',
  '$."preparation-chantier".seo_title', 'Préparation de chantier BTP à La Réunion | LP CONSULTING',
  '$."direction-travaux".intro_title', 'Direction de travaux et pilotage de chantier à La Réunion',
  '$."direction-travaux".seo_title', 'Direction de travaux BTP à La Réunion | LP CONSULTING',
  '$."direction-travaux".seo_description', 'Coordination des équipes, suivi technique, pilotage des délais et OPC, suivi budgétaire : LP CONSULTING dirige vos chantiers BTP à La Réunion.'
) WHERE section_key = 'missions';

UPDATE content_sections SET data = JSON_SET(data,
  '$.seo_title', 'Références BTP : études et chantiers | LP CONSULTING',
  '$.seo_description', 'Références de LP CONSULTING : conception-réalisation à Mayotte et en Guadeloupe, direction de travaux à La Réunion (NRL, TCSP d’Étang-Salé, CHU).'
) WHERE section_key = 'references';

UPDATE content_sections SET data = JSON_SET(data,
  '$.seo_title', 'Mentions légales et confidentialité | LP CONSULTING',
  '$.seo_description', 'Mentions légales et politique de confidentialité du site LP CONSULTING : éditeur, hébergeur et données personnelles.'
) WHERE section_key = 'legal';
