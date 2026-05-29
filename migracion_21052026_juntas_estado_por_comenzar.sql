-- Agrega el estado POR_COMENZAR para juntas programadas a futuro.
USE `iglesia_asistencia`;

-- En MariaDB/XAMPP:
ALTER TABLE `juntas_iglesia` DROP CONSTRAINT `chk_juntas_iglesia_estado`;
-- En MySQL 8, si la linea anterior no aplica:
-- ALTER TABLE `juntas_iglesia` DROP CHECK `chk_juntas_iglesia_estado`;

UPDATE `juntas_iglesia`
SET `estado` = 'POR_COMENZAR'
WHERE `fecha` > CURRENT_DATE
  AND `estado` IN ('BORRADOR', 'EN_PROCESO');

UPDATE `juntas_iglesia`
SET `estado` = 'EN_PROCESO'
WHERE `fecha` = CURRENT_DATE
  AND `estado` IN ('BORRADOR', 'POR_COMENZAR');

ALTER TABLE `juntas_iglesia`
  ADD CONSTRAINT `chk_juntas_iglesia_estado`
    CHECK (`estado` IN ('POR_COMENZAR', 'BORRADOR', 'EN_PROCESO', 'CERRADA', 'APROBADA', 'ARCHIVADA'));
