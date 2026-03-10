<?php
declare(strict_types=1);

$base = dirname(__DIR__, 3);

require_once $base . '/Config/Global.php';
require_once $base . '/Config/Conexion.php';
require_once $base . '/Utils/AuthContext.php';

require_once $base . '/Modelo/Usuario/UsuarioDTO.php';
require_once $base . '/Modelo/Usuario/UsuarioMapper.php';
require_once $base . '/Modelo/Usuario/UsuarioDAO.php';
require_once $base . '/Modelo/Token/TokenDTO.php';
require_once $base . '/Modelo/Token/TokenMapper.php';
require_once $base . '/Modelo/Token/TokenDAO.php';
require_once $base . '/Services/UsuarioService.php';

require_once $base . '/Modelo/Presentacion/PresentacionDTO.php';
require_once $base . '/Modelo/Presentacion/PresentacionMapper.php';
require_once $base . '/Modelo/Presentacion/PresentacionDAO.php';

require_once $base . '/Modelo/Asistencia/AsistenciaDTO.php';
require_once $base . '/Modelo/Asistencia/AsistenciaMapper.php';
require_once $base . '/Modelo/Asistencia/AsistenciaDAO.php';

$pdo = Conexion::getConexion();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/**
 * @throws RuntimeException
 */
function must(bool $cond, string $message): void
{
    if (!$cond) {
        throw new RuntimeException($message);
    }
}

/**
 * @throws RuntimeException
 */
function getRolId(PDO $pdo, string $rol): int
{
    $stmt = $pdo->prepare('SELECT id FROM roles WHERE nombre = :rol LIMIT 1');
    $stmt->execute([':rol' => $rol]);
    $id = $stmt->fetchColumn();
    if ($id === false) {
        throw new RuntimeException('Rol no encontrado: ' . $rol);
    }
    return (int) $id;
}

/**
 * @throws RuntimeException
 */
function getCampoId(PDO $pdo): int
{
    $stmt = $pdo->query("SELECT id FROM campos WHERE codigo = 'AN' LIMIT 1");
    $id = $stmt->fetchColumn();
    if ($id === false) {
        $stmtFallback = $pdo->query('SELECT id FROM campos ORDER BY id ASC LIMIT 1');
        $id = $stmtFallback->fetchColumn();
    }
    if ($id === false) {
        throw new RuntimeException('No existe ningun campo para crear organizaciones de prueba.');
    }
    return (int) $id;
}

/**
 * @throws RuntimeException
 */
function getCultoId(PDO $pdo): int
{
    $stmt = $pdo->query('SELECT id FROM cultos ORDER BY id ASC LIMIT 1');
    $id = $stmt->fetchColumn();
    if ($id === false) {
        throw new RuntimeException('No existe ningun culto base para pruebas de aislamiento.');
    }
    return (int) $id;
}

$usuarioService = new UsuarioService();
$presentacionDAO = new PresentacionDAO();
$asistenciaDAO = new AsistenciaDAO();

$adminRolId = getRolId($pdo, 'ADMIN');
$secretarioRolId = getRolId($pdo, 'SECRETARIO');
$campoId = getCampoId($pdo);
$cultoId = getCultoId($pdo);

$sfx = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
$codigoA = 'B7A' . substr($sfx, 0, 6);
$codigoB = 'B7B' . substr($sfx, 0, 6);

$orgA = null;
$orgB = null;

try {
    $stmtOrg = $pdo->prepare(
        'INSERT INTO organizaciones (campo_id, codigo_instancia, tipo_organizacion, nombre_organizacion, correo_contacto, activa)
         VALUES (:campo_id, :codigo, :tipo, :nombre, NULL, 1)'
    );

    $stmtOrg->execute([
        ':campo_id' => $campoId,
        ':codigo' => $codigoA,
        ':tipo' => 'IGLESIA',
        ':nombre' => 'ORG_B7_A_' . $sfx
    ]);
    $orgA = (int) $pdo->lastInsertId();

    $stmtOrg->execute([
        ':campo_id' => $campoId,
        ':codigo' => $codigoB,
        ':tipo' => 'GRUPO',
        ':nombre' => 'ORG_B7_B_' . $sfx
    ]);
    $orgB = (int) $pdo->lastInsertId();

    must($orgA > 0 && $orgB > 0, 'No se pudieron crear organizaciones para la suite B7-T01.');

    AuthContext::set(910001, $orgA, $codigoA, 'IGLESIA', 'ORG_B7_A_' . $sfx, 'ADMIN', 'Suite A');
    $secA = $usuarioService->crear([
        'nombre_completo' => 'Suite Secretario A ' . $sfx,
        'usuario' => 'b7_sec_a_' . strtolower($sfx),
        'password' => 'SuiteA#123456789',
        'rol_id' => $secretarioRolId
    ]);

    AuthContext::set(910002, $orgB, $codigoB, 'GRUPO', 'ORG_B7_B_' . $sfx, 'ADMIN', 'Suite B');
    $secB = $usuarioService->crear([
        'nombre_completo' => 'Suite Secretario B ' . $sfx,
        'usuario' => 'b7_sec_b_' . strtolower($sfx),
        'password' => 'SuiteB#123456789',
        'rol_id' => $secretarioRolId
    ]);

    // Aislamiento de usuarios (lista y detalle)
    AuthContext::set(910001, $orgA, $codigoA, 'IGLESIA', 'ORG_B7_A_' . $sfx, 'ADMIN', 'Suite A');
    $listaA = $usuarioService->listar();
    $idsA = array_map(
        static fn(array $item): int => (int) ($item['id'] ?? 0),
        $listaA
    );
    $aislamientoUsuariosLista = !in_array((int) $secB['id'], $idsA, true);

    $aislamientoUsuariosDetalle = false;
    try {
        $usuarioService->obtenerPorId((int) $secB['id']);
    } catch (RuntimeException $e) {
        $aislamientoUsuariosDetalle = true;
    }

    // Aislamiento de presentaciones (misma combinacion periodo/culto en tenants distintos)
    $anio = 2026;
    $mes = 12;
    $cultoCodigo = 'SABADO';

    $presentacionAId = $presentacionDAO->insert([
        'organizacion_id' => $orgA,
        'usuario_id' => (int) $secA['id'],
        'anio' => $anio,
        'mes' => $mes,
        'culto_codigo' => $cultoCodigo,
        'filtros_json' => '{"anio":2026,"mes":"12","culto":"SABADO"}',
        'metricas_json' => '{"resumen":{"total_registros":1}}',
        'prompt_version' => 'v1',
        'prompt_bloqueado' => 'suite',
        'modelo' => 'suite',
        'ia_response_id' => '',
        'presentacion_json' => '{"titulo":"Suite A"}'
    ]);

    $presentacionBId = $presentacionDAO->insert([
        'organizacion_id' => $orgB,
        'usuario_id' => (int) $secB['id'],
        'anio' => $anio,
        'mes' => $mes,
        'culto_codigo' => $cultoCodigo,
        'filtros_json' => '{"anio":2026,"mes":"12","culto":"SABADO"}',
        'metricas_json' => '{"resumen":{"total_registros":1}}',
        'prompt_version' => 'v1',
        'prompt_bloqueado' => 'suite',
        'modelo' => 'suite',
        'ia_response_id' => '',
        'presentacion_json' => '{"titulo":"Suite B"}'
    ]);

    $listaPresentacionesA = $presentacionDAO->findAll([], (int) $secA['id'], true, $orgA, 50, 0);
    $idsPresentacionesA = array_map(
        static fn(PresentacionDTO $item): int => $item->id,
        $listaPresentacionesA
    );
    $aislamientoPresentacionesLista = !in_array($presentacionBId, $idsPresentacionesA, true);

    $presentacionCruzada = $presentacionDAO->findById($presentacionBId, $orgA);
    $aislamientoPresentacionesDetalle = $presentacionCruzada === null;

    // Aislamiento de asistencia: misma fecha/culto permitida entre tenants, bloqueada en mismo tenant.
    $fechaPrueba = '2026-12-27';
    $baseAsistencia = [
        'culto_id' => $cultoId,
        'fecha' => $fechaPrueba,
        'llegaron_antes_hora' => 10,
        'llegaron_despues_hora' => 2,
        'ninos' => 3,
        'jovenes' => 4,
        'total_asistentes' => 12,
        'proc_barrio' => 6,
        'proc_guayabo' => 6,
        'visitas_barrio' => 1,
        'nombres_visitas_barrio' => 'Visita Barrio',
        'visitas_guayabo' => 1,
        'nombres_visitas_guayabo' => 'Visita Guayabo',
        'retiros_antes_terminar' => 0,
        'se_quedaron_todo' => 12,
        'observaciones' => 'Suite B7-T01',
        'metricas_json' => '{}'
    ];

    $asistenciaDAO->insert($baseAsistencia + [
        'organizacion_id' => $orgA,
        'registrado_por' => (int) $secA['id']
    ]);

    $asistenciaDAO->insert($baseAsistencia + [
        'organizacion_id' => $orgB,
        'registrado_por' => (int) $secB['id']
    ]);
    $unicidadAsistenciaCrossTenant = true;

    $bloqueoDuplicadoMismoTenant = false;
    try {
        $asistenciaDAO->insert($baseAsistencia + [
            'organizacion_id' => $orgA,
            'registrado_por' => (int) $secA['id']
        ]);
    } catch (PDOException $e) {
        $bloqueoDuplicadoMismoTenant = true;
    }

    echo 'aislamiento_usuarios_lista=' . ($aislamientoUsuariosLista ? '1' : '0') . PHP_EOL;
    echo 'aislamiento_usuarios_detalle=' . ($aislamientoUsuariosDetalle ? '1' : '0') . PHP_EOL;
    echo 'aislamiento_presentaciones_lista=' . ($aislamientoPresentacionesLista ? '1' : '0') . PHP_EOL;
    echo 'aislamiento_presentaciones_detalle=' . ($aislamientoPresentacionesDetalle ? '1' : '0') . PHP_EOL;
    echo 'unicidad_asistencia_cross_tenant=' . ($unicidadAsistenciaCrossTenant ? '1' : '0') . PHP_EOL;
    echo 'bloqueo_duplicado_mismo_tenant=' . ($bloqueoDuplicadoMismoTenant ? '1' : '0') . PHP_EOL;
    echo 'presentacion_a_id=' . $presentacionAId . PHP_EOL;
    echo 'presentacion_b_id=' . $presentacionBId . PHP_EOL;

    must($aislamientoUsuariosLista, 'Fallo aislamiento usuarios lista.');
    must($aislamientoUsuariosDetalle, 'Fallo aislamiento usuarios detalle.');
    must($aislamientoPresentacionesLista, 'Fallo aislamiento presentaciones lista.');
    must($aislamientoPresentacionesDetalle, 'Fallo aislamiento presentaciones detalle.');
    must($unicidadAsistenciaCrossTenant, 'No se inserto asistencia en tenant cruzado.');
    must($bloqueoDuplicadoMismoTenant, 'No se bloqueo duplicado de asistencia en mismo tenant.');
} finally {
    AuthContext::clear();

    if ($orgA !== null || $orgB !== null) {
        $orgIds = array_values(array_filter([$orgA, $orgB], static fn($v): bool => $v !== null));
        if (count($orgIds) > 0) {
            $in = implode(',', array_fill(0, count($orgIds), '?'));
            $pdo->prepare("DELETE FROM user_tokens WHERE organizacion_id IN ($in)")->execute($orgIds);
            $pdo->prepare("DELETE FROM asistencia_registro WHERE organizacion_id IN ($in)")->execute($orgIds);
            $pdo->prepare("DELETE FROM presentaciones WHERE organizacion_id IN ($in)")->execute($orgIds);
            $pdo->prepare("DELETE FROM usuarios WHERE organizacion_id IN ($in)")->execute($orgIds);
            $pdo->prepare("DELETE FROM organizaciones WHERE id IN ($in)")->execute($orgIds);
        }
    }
}
