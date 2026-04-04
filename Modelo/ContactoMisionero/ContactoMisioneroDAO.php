<?php
declare(strict_types=1);

/**
 * Acceso a datos para contactos misioneros compartidos.
 */
final class ContactoMisioneroDAO
{
    private PDO $pdo;

    private const COLUMNS = 'cm.id, cm.organizacion_id, cm.nombre_completo, cm.telefono, cm.telefono_normalizado,
        cm.correo, cm.direccion, cm.barrio_comunidad, cm.clasificacion_principal, cm.es_miembro,
        cm.estado_contacto, cm.fecha_primer_contacto, cm.fecha_ultimo_contacto, cm.origen_principal_clave,
        cm.modulo_origen, cm.referencia_origen_id, cm.observaciones_generales, cm.creado_por,
        cm.actualizado_por, cm.eliminado_por, cm.creado_en, cm.actualizado_en, cm.eliminado_en';

    public function __construct()
    {
        $this->pdo = Conexion::getConexion();
    }

    public function findById(int $id, int $organizacionId): ?ContactoMisioneroDTO
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM contactos_misioneros cm
                WHERE cm.id = :id
                  AND cm.organizacion_id = :organizacion_id
                  AND cm.eliminado_en IS NULL';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':organizacion_id' => $organizacionId
        ]);

        $row = $stmt->fetch();
        return $row === false ? null : ContactoMisioneroMapper::fromRow($row);
    }

    /**
     * @param array<string, mixed> $filters
     * @return ContactoMisioneroDTO[]
     */
    public function findAll(array $filters, int $organizacionId): array
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM contactos_misioneros cm';
        $where = ['cm.organizacion_id = :organizacion_id', 'cm.eliminado_en IS NULL'];
        $params = [':organizacion_id' => $organizacionId];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(cm.nombre_completo LIKE :q OR cm.telefono LIKE :q OR cm.correo LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $estado = trim((string) ($filters['estado'] ?? ''));
        if ($estado !== '') {
            $where[] = 'cm.estado_contacto = :estado';
            $params[':estado'] = $estado;
        }

        $clasificacion = trim((string) ($filters['clasificacion'] ?? ''));
        if ($clasificacion !== '') {
            $where[] = 'cm.clasificacion_principal = :clasificacion';
            $params[':clasificacion'] = $clasificacion;
        }

        $origen = trim((string) ($filters['origen'] ?? ''));
        if ($origen !== '') {
            $where[] = 'cm.origen_principal_clave = :origen';
            $params[':origen'] = $origen;
        }

        $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY cm.nombre_completo ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $items = [];
        while ($row = $stmt->fetch()) {
            $items[] = ContactoMisioneroMapper::fromRow($row);
        }

        return $items;
    }

    public function findBestMatch(string $nombreNormalizado, ?string $telefonoNormalizado, int $organizacionId, ?int $excludeId = null): ?ContactoMisioneroDTO
    {
        $where = ['cm.organizacion_id = :organizacion_id', 'cm.eliminado_en IS NULL'];
        $params = [':organizacion_id' => $organizacionId];

        if ($telefonoNormalizado !== null && $telefonoNormalizado !== '') {
            $where[] = 'cm.telefono_normalizado = :telefono_normalizado';
            $params[':telefono_normalizado'] = $telefonoNormalizado;
        } else {
            $where[] = 'LOWER(TRIM(cm.nombre_completo)) = :nombre_normalizado';
            $params[':nombre_normalizado'] = $nombreNormalizado;
        }

        if ($excludeId !== null) {
            $where[] = 'cm.id <> :exclude_id';
            $params[':exclude_id'] = $excludeId;
        }

        $sql = 'SELECT ' . self::COLUMNS . ' FROM contactos_misioneros cm
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY cm.actualizado_en DESC
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row === false ? null : ContactoMisioneroMapper::fromRow($row);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int
    {
        $sql = 'INSERT INTO contactos_misioneros (
                    organizacion_id, nombre_completo, telefono, telefono_normalizado, correo,
                    direccion, barrio_comunidad, clasificacion_principal, es_miembro,
                    estado_contacto, fecha_primer_contacto, fecha_ultimo_contacto,
                    origen_principal_clave, modulo_origen, referencia_origen_id,
                    observaciones_generales, creado_por, actualizado_por
                ) VALUES (
                    :organizacion_id, :nombre_completo, :telefono, :telefono_normalizado, :correo,
                    :direccion, :barrio_comunidad, :clasificacion_principal, :es_miembro,
                    :estado_contacto, :fecha_primer_contacto, :fecha_ultimo_contacto,
                    :origen_principal_clave, :modulo_origen, :referencia_origen_id,
                    :observaciones_generales, :creado_por, :actualizado_por
                )';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':organizacion_id' => $data['organizacion_id'],
            ':nombre_completo' => $data['nombre_completo'],
            ':telefono' => $data['telefono'],
            ':telefono_normalizado' => $data['telefono_normalizado'],
            ':correo' => $data['correo'],
            ':direccion' => $data['direccion'],
            ':barrio_comunidad' => $data['barrio_comunidad'],
            ':clasificacion_principal' => $data['clasificacion_principal'],
            ':es_miembro' => $data['es_miembro'],
            ':estado_contacto' => $data['estado_contacto'],
            ':fecha_primer_contacto' => $data['fecha_primer_contacto'],
            ':fecha_ultimo_contacto' => $data['fecha_ultimo_contacto'],
            ':origen_principal_clave' => $data['origen_principal_clave'],
            ':modulo_origen' => $data['modulo_origen'],
            ':referencia_origen_id' => $data['referencia_origen_id'],
            ':observaciones_generales' => $data['observaciones_generales'],
            ':creado_por' => $data['creado_por'],
            ':actualizado_por' => $data['actualizado_por']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data, int $organizacionId): bool
    {
        $sql = 'UPDATE contactos_misioneros SET
                    nombre_completo = :nombre_completo,
                    telefono = :telefono,
                    telefono_normalizado = :telefono_normalizado,
                    correo = :correo,
                    direccion = :direccion,
                    barrio_comunidad = :barrio_comunidad,
                    clasificacion_principal = :clasificacion_principal,
                    es_miembro = :es_miembro,
                    estado_contacto = :estado_contacto,
                    fecha_primer_contacto = :fecha_primer_contacto,
                    fecha_ultimo_contacto = :fecha_ultimo_contacto,
                    origen_principal_clave = :origen_principal_clave,
                    modulo_origen = :modulo_origen,
                    referencia_origen_id = :referencia_origen_id,
                    observaciones_generales = :observaciones_generales,
                    actualizado_por = :actualizado_por
                WHERE id = :id
                  AND organizacion_id = :organizacion_id
                  AND eliminado_en IS NULL';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':organizacion_id' => $organizacionId,
            ':nombre_completo' => $data['nombre_completo'],
            ':telefono' => $data['telefono'],
            ':telefono_normalizado' => $data['telefono_normalizado'],
            ':correo' => $data['correo'],
            ':direccion' => $data['direccion'],
            ':barrio_comunidad' => $data['barrio_comunidad'],
            ':clasificacion_principal' => $data['clasificacion_principal'],
            ':es_miembro' => $data['es_miembro'],
            ':estado_contacto' => $data['estado_contacto'],
            ':fecha_primer_contacto' => $data['fecha_primer_contacto'],
            ':fecha_ultimo_contacto' => $data['fecha_ultimo_contacto'],
            ':origen_principal_clave' => $data['origen_principal_clave'],
            ':modulo_origen' => $data['modulo_origen'],
            ':referencia_origen_id' => $data['referencia_origen_id'],
            ':observaciones_generales' => $data['observaciones_generales'],
            ':actualizado_por' => $data['actualizado_por']
        ]);
    }

    public function softDelete(int $id, int $organizacionId, ?int $usuarioId): bool
    {
        $sql = 'UPDATE contactos_misioneros
                SET eliminado_en = CURRENT_TIMESTAMP,
                    eliminado_por = :eliminado_por
                WHERE id = :id
                  AND organizacion_id = :organizacion_id
                  AND eliminado_en IS NULL';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':organizacion_id' => $organizacionId,
            ':eliminado_por' => $usuarioId
        ]);
    }
}
