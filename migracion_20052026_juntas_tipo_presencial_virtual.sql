-- Actualiza el tipo de juntas de iglesia a los valores actuales del modulo.
USE `iglesia_asistencia`;

-- En MariaDB/XAMPP:
ALTER TABLE `juntas_iglesia` DROP CONSTRAINT `chk_juntas_iglesia_tipo`;
-- En MySQL 8, si la linea anterior no aplica:
-- ALTER TABLE `juntas_iglesia` DROP CHECK `chk_juntas_iglesia_tipo`;

UPDATE `juntas_iglesia`
SET `tipo` = CASE
  WHEN `tipo` = 'WHATSAPP' THEN 'VIRTUAL'
  ELSE 'PRESENCIAL'
END
WHERE `tipo` IN ('ORDINARIA', 'EXTRAORDINARIA', 'SEGUIMIENTO', 'WHATSAPP', 'CONTINUACION');

ALTER TABLE `juntas_iglesia`
  MODIFY `tipo` varchar(25) NOT NULL DEFAULT 'PRESENCIAL',
  ADD CONSTRAINT `chk_juntas_iglesia_tipo`
    CHECK (`tipo` IN ('PRESENCIAL', 'VIRTUAL'));
