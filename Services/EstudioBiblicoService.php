<?php
declare(strict_types=1);

/**
 * Logica de negocio de estudios biblicos.
 */
final class EstudioBiblicoService
{
    private EstudioBiblicoDAO $estudioDAO;
    private ContactoMisioneroService $contactoService;
    private AuditoriaService $auditoriaService;
    private SeguimientoTareaService $seguimientoService;

    public function __construct()
    {
        $this->estudioDAO = new EstudioBiblicoDAO();
        $this->contactoService = new ContactoMisioneroService();
        $this->auditoriaService = new AuditoriaService();
        $this->seguimientoService = new SeguimientoTareaService();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function listar(array $filters): array
    {
        return $this->estudioDAO->findAllAsArray($this->normalizarFiltros($filters), AuthContext::getOrganizacionId());
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function dashboard(array $filters): array
    {
        return $this->estudioDAO->getDashboard($this->normalizarFiltros($filters), AuthContext::getOrganizacionId());
    }

    /**
     * @return array<string, mixed>
     */
    public function obtener(int $id): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $dto = $this->estudioDAO->findById($id, $organizacionId);
        if ($dto === null) {
            throw new OutOfBoundsException('Estudio biblico no encontrado.');
        }

        $base = EstudioBiblicoMapper::toArray($dto);
        $base['sesiones'] = $this->estudioDAO->listSessions($id, $organizacionId);
        $base['decisiones'] = $this->estudioDAO->listDecisions($id, $organizacionId);
        $base['asignaciones'] = $this->estudioDAO->listAssignments($id, $organizacionId);
        $base['resumen'] = [
            'total_sesiones' => count($base['sesiones']),
            'total_decisiones' => count($base['decisiones']),
            'ultima_sesion' => $base['sesiones'][0]['fecha'] ?? null,
            'ultima_decision' => $base['decisiones'][0]['fecha_decision'] ?? null
        ];

        return $base;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function crear(array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $contacto = $this->resolverContactoPersona($data, $usuarioId, $actorNombre);
        $principal = $this->resolverInstructor($data, 'instructor_principal', $usuarioId, $actorNombre);
        $secundario = $this->resolverInstructor($data, 'instructor_secundario', $usuarioId, $actorNombre);
        $payload = $this->normalizarEstudio($data, $organizacionId, $usuarioId, $contacto, $principal, $secundario);

        $duplicado = $this->estudioDAO->findOpenByContactoId((int) $contacto['id'], $organizacionId);
        if ($duplicado !== null) {
            throw new RuntimeException('La persona ya tiene un estudio biblico activo.');
        }

        $id = $this->estudioDAO->insert($payload);
        $item = $this->obtener($id);
        $this->registrarAsignacionInicial($id, $payload, $usuarioId, $actorNombre, 'Asignacion inicial del estudio.');
        $this->auditoriaService->registrar('ESTUDIOS_BIBLICOS', 'ESTUDIO_BIBLICO', $id, 'CREAR', 'Estudio biblico creado.', $organizacionId, $usuarioId, $actorNombre, null, $item);
        return $this->obtener($id);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function actualizar(int $id, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $existente = $this->estudioDAO->findById($id, $organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('Estudio biblico no encontrado.');
        }

        $contacto = $this->resolverContactoPersona($data, $usuarioId, $actorNombre, $existente->contactoId);
        $principal = $this->resolverInstructor($data, 'instructor_principal', $usuarioId, $actorNombre, $existente->instructorPrincipalContactoId);
        $secundario = $this->resolverInstructor($data, 'instructor_secundario', $usuarioId, $actorNombre, $existente->instructorSecundarioContactoId);
        $payload = $this->normalizarEstudio($data, $organizacionId, $usuarioId, $contacto, $principal, $secundario, $existente);

        $duplicado = $this->estudioDAO->findOpenByContactoId((int) $contacto['id'], $organizacionId, $id);
        if ($duplicado !== null) {
            throw new RuntimeException('La persona ya tiene otro estudio biblico activo.');
        }

        $antes = EstudioBiblicoMapper::toArray($existente);
        $this->estudioDAO->update($id, $payload, $organizacionId);

        if (
            $existente->instructorPrincipalContactoId !== $payload['instructor_principal_contacto_id']
            || $existente->instructorSecundarioContactoId !== $payload['instructor_secundario_contacto_id']
            || $existente->responsableUsuarioId !== $payload['responsable_usuario_id']
        ) {
            $this->registrarReasignacion($id, $payload, (string) ($data['motivo_reasignacion'] ?? 'Reasignacion manual.'), $usuarioId, $actorNombre);
        }

        $item = $this->obtener($id);
        $this->auditoriaService->registrar('ESTUDIOS_BIBLICOS', 'ESTUDIO_BIBLICO', $id, 'ACTUALIZAR', 'Estudio biblico actualizado.', $organizacionId, $usuarioId, $actorNombre, $antes, $item);
        return $item;
    }

    public function eliminar(int $id, int $usuarioId, string $actorNombre): void
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $existente = $this->estudioDAO->findById($id, $organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('Estudio biblico no encontrado.');
        }

        $this->estudioDAO->softDelete($id, $organizacionId, $usuarioId);
        $this->auditoriaService->registrar('ESTUDIOS_BIBLICOS', 'ESTUDIO_BIBLICO', $id, 'ARCHIVAR', 'Estudio biblico archivado.', $organizacionId, $usuarioId, $actorNombre, EstudioBiblicoMapper::toArray($existente), null);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function crearSesion(int $estudioId, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $estudio = $this->estudioDAO->findById($estudioId, $organizacionId);
        if ($estudio === null) {
            throw new OutOfBoundsException('Estudio biblico no encontrado.');
        }

        $payload = $this->normalizarSesion($data, $organizacionId, $estudioId, $usuarioId);
        $id = $this->estudioDAO->insertSession($payload);

        $fechaUltimaSesion = substr((string) $payload['fecha'], 0, 10);
        $proximaSesion = $payload['proxima_fecha_sugerida'];
        $estado = in_array($estudio->estadoGeneral, ['PAUSADO', 'NO_CONTINUA', 'BAUTIZADO', 'CERRADO'], true)
            ? $estudio->estadoGeneral
            : 'EN_PROCESO';
        $this->estudioDAO->updateStudyDates($estudioId, $organizacionId, $fechaUltimaSesion, $proximaSesion, $estado, $usuarioId);

        $sesion = $this->buscarPorIdEnLista($this->estudioDAO->listSessions($estudioId, $organizacionId), $id);
        $this->auditoriaService->registrar('ESTUDIOS_BIBLICOS', 'ESTUDIO_SESION', $id, 'CREAR', 'Sesion de estudio biblico creada.', $organizacionId, $usuarioId, $actorNombre, null, $sesion, ['estudio_id' => $estudioId]);
        return $this->obtener($estudioId);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function crearDecision(int $estudioId, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $estudio = $this->estudioDAO->findById($estudioId, $organizacionId);
        if ($estudio === null) {
            throw new OutOfBoundsException('Estudio biblico no encontrado.');
        }

        $payload = $this->normalizarDecision($data, $organizacionId, $estudioId, $usuarioId);
        if ($payload['requiere_seguimiento']) {
            $tarea = $this->seguimientoService->crear([
                'modulo' => 'ESTUDIOS_BIBLICOS',
                'entidad_tipo' => 'ESTUDIO_BIBLICO',
                'entidad_id' => $estudioId,
                'contacto_id' => $estudio->contactoId,
                'titulo' => $payload['seguimiento_titulo'] ?: ('Seguimiento de estudio: ' . $payload['decision_etiqueta']),
                'descripcion' => $payload['seguimiento_descripcion'] ?: $payload['observaciones'],
                'responsable_usuario_id' => $payload['seguimiento_responsable_usuario_id'] ?: $estudio->responsableUsuarioId,
                'fecha_programada' => $payload['seguimiento_fecha_limite'],
                'fecha_limite' => $payload['seguimiento_fecha_limite'],
                'prioridad' => $payload['prioridad']
            ], $organizacionId, $usuarioId, $actorNombre);
            $payload['seguimiento_tarea_id'] = $tarea['id'];
        }

        $id = $this->estudioDAO->insertDecision($payload);
        $estado = $this->resolverEstadoPorDecision($payload['decision_clave'], $estudio->estadoGeneral);
        if ($estado !== $estudio->estadoGeneral) {
            $this->estudioDAO->updateStudyDates($estudioId, $organizacionId, $estudio->fechaUltimaSesion, $estudio->proximaSesion, $estado, $usuarioId);
        }

        $decision = $this->buscarPorIdEnLista($this->estudioDAO->listDecisions($estudioId, $organizacionId), $id);
        $this->auditoriaService->registrar('ESTUDIOS_BIBLICOS', 'ESTUDIO_DECISION', $id, 'CREAR', 'Decision del estudio biblico registrada.', $organizacionId, $usuarioId, $actorNombre, null, $decision, ['estudio_id' => $estudioId]);
        return $this->obtener($estudioId);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function crearAsignacion(int $estudioId, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $estudio = $this->estudioDAO->findById($estudioId, $organizacionId);
        if ($estudio === null) {
            throw new OutOfBoundsException('Estudio biblico no encontrado.');
        }

        $principal = $this->resolverInstructor($data, 'instructor_principal', $usuarioId, $actorNombre);
        $secundario = $this->resolverInstructor($data, 'instructor_secundario', $usuarioId, $actorNombre);

        $payload = [
            'organizacion_id' => $organizacionId,
            'estudio_id' => $estudioId,
            'instructor_principal_contacto_id' => $principal['id'] ?? null,
            'instructor_secundario_contacto_id' => $secundario['id'] ?? null,
            'responsable_usuario_id' => $this->toOptionalInt($data['responsable_usuario_id'] ?? null),
            'fecha_asignacion' => date('Y-m-d H:i:s'),
            'fecha_fin' => null,
            'motivo_cambio' => $this->toNullableText($data['motivo_cambio'] ?? null, 800),
            'vigente' => 1,
            'observaciones' => $this->toNullableText($data['observaciones'] ?? null, 800),
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId
        ];

        $this->estudioDAO->closeAssignments($estudioId, $organizacionId, $usuarioId, $payload['motivo_cambio']);
        $id = $this->estudioDAO->insertAssignment($payload);
        $this->estudioDAO->update($estudioId, [
            'contacto_id' => $estudio->contactoId,
            'origen_clave' => $estudio->origenClave,
            'campana_origen_id' => $estudio->campanaOrigenId,
            'pc_origen_id' => $estudio->pcOrigenId,
            'instructor_principal_contacto_id' => $payload['instructor_principal_contacto_id'],
            'instructor_secundario_contacto_id' => $payload['instructor_secundario_contacto_id'],
            'responsable_usuario_id' => $payload['responsable_usuario_id'],
            'modalidad' => $estudio->modalidad,
            'material_estudio' => $estudio->materialEstudio,
            'leccion_actual' => $estudio->leccionActual,
            'total_lecciones_completadas' => $estudio->totalLeccionesCompletadas,
            'fecha_inicio' => $estudio->fechaInicio,
            'fecha_ultima_sesion' => $estudio->fechaUltimaSesion,
            'proxima_sesion' => $estudio->proximaSesion,
            'estado_general' => $estudio->estadoGeneral,
            'observaciones' => $estudio->observaciones,
            'motivo_cierre_pausa' => $estudio->motivoCierrePausa,
            'actualizado_por' => $usuarioId
        ], $organizacionId);

        $asignacion = $this->buscarPorIdEnLista($this->estudioDAO->listAssignments($estudioId, $organizacionId), $id);
        $this->auditoriaService->registrar('ESTUDIOS_BIBLICOS', 'ESTUDIO_ASIGNACION', $id, 'CREAR', 'Asignacion de estudio actualizada.', $organizacionId, $usuarioId, $actorNombre, null, $asignacion, ['estudio_id' => $estudioId]);
        return $this->obtener($estudioId);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function normalizarFiltros(array $filters): array
    {
        return [
            'q' => isset($filters['q']) ? trim((string) $filters['q']) : '',
            'estado_general' => isset($filters['estado_general']) ? strtoupper(trim((string) $filters['estado_general'])) : '',
            'origen_clave' => isset($filters['origen_clave']) ? strtoupper(trim((string) $filters['origen_clave'])) : '',
            'responsable_usuario_id' => $this->toOptionalInt($filters['responsable_usuario_id'] ?? null),
            'fecha_desde' => $this->toNullableDate($filters['fecha_desde'] ?? null),
            'fecha_hasta' => $this->toNullableDate($filters['fecha_hasta'] ?? null)
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $contacto
     * @param array<string, mixed>|null $principal
     * @param array<string, mixed>|null $secundario
     * @return array<string, mixed>
     */
    private function normalizarEstudio(
        array $data,
        int $organizacionId,
        int $usuarioId,
        array $contacto,
        ?array $principal,
        ?array $secundario,
        ?EstudioBiblicoDTO $base = null
    ): array {
        $modalidad = strtoupper($this->toRequiredText($data['modalidad'] ?? ($base?->modalidad ?? null), 20, 'La modalidad es obligatoria.'));
        if (!in_array($modalidad, ['INDIVIDUAL', 'CLASE_BIBLICA', 'HOGAR', 'TEMPLO', 'VIRTUAL', 'OTRO'], true)) {
            throw new InvalidArgumentException('La modalidad no es valida.');
        }

        $estado = strtoupper($this->toRequiredText($data['estado_general'] ?? ($base?->estadoGeneral ?? null), 25, 'El estado del estudio es obligatorio.'));
        if (!in_array($estado, ['NUEVO', 'ASIGNADO', 'CONTACTADO', 'EN_PROCESO', 'PAUSADO', 'NO_CONTINUA', 'LISTO_DECISION', 'CANDIDATO_BAUTISMAL', 'BAUTIZADO', 'CERRADO'], true)) {
            throw new InvalidArgumentException('El estado del estudio no es valido.');
        }

        return [
            'organizacion_id' => $organizacionId,
            'contacto_id' => (int) $contacto['id'],
            'origen_clave' => $this->toNullableText($data['origen_clave'] ?? ($base?->origenClave ?? null), 50, true),
            'campana_origen_id' => $this->toOptionalInt($data['campana_origen_id'] ?? ($base?->campanaOrigenId ?? null)),
            'pc_origen_id' => $this->toOptionalInt($data['pc_origen_id'] ?? ($base?->pcOrigenId ?? null)),
            'instructor_principal_contacto_id' => $principal['id'] ?? $base?->instructorPrincipalContactoId,
            'instructor_secundario_contacto_id' => $secundario['id'] ?? $base?->instructorSecundarioContactoId,
            'responsable_usuario_id' => $this->toOptionalInt($data['responsable_usuario_id'] ?? ($base?->responsableUsuarioId ?? null)),
            'modalidad' => $modalidad,
            'material_estudio' => $this->toNullableText($data['material_estudio'] ?? ($base?->materialEstudio ?? null), 160),
            'leccion_actual' => $this->toNullableText($data['leccion_actual'] ?? ($base?->leccionActual ?? null), 80),
            'total_lecciones_completadas' => $this->toNonNegativeInt($data['total_lecciones_completadas'] ?? ($base?->totalLeccionesCompletadas ?? 0)),
            'fecha_inicio' => $this->toRequiredDate($data['fecha_inicio'] ?? ($base?->fechaInicio ?? null), 'La fecha de inicio es obligatoria.'),
            'fecha_ultima_sesion' => $this->toNullableDate($data['fecha_ultima_sesion'] ?? ($base?->fechaUltimaSesion ?? null)),
            'proxima_sesion' => $this->toNullableDateTime($data['proxima_sesion'] ?? ($base?->proximaSesion ?? null)),
            'estado_general' => $estado,
            'observaciones' => $this->toNullableText($data['observaciones'] ?? ($base?->observaciones ?? null), 2000),
            'motivo_cierre_pausa' => $this->toNullableText($data['motivo_cierre_pausa'] ?? ($base?->motivoCierrePausa ?? null), 1200),
            'creado_por' => $base?->creadoPor ?? $usuarioId,
            'actualizado_por' => $usuarioId
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizarSesion(array $data, int $organizacionId, int $estudioId, int $usuarioId): array
    {
        return [
            'organizacion_id' => $organizacionId,
            'estudio_id' => $estudioId,
            'fecha' => $this->toRequiredDateTime($data['fecha'] ?? null, 'La fecha de la sesion es obligatoria.'),
            'tema_leccion' => $this->toRequiredText($data['tema_leccion'] ?? null, 180, 'El tema o leccion es obligatorio.'),
            'resumen_breve' => $this->toNullableText($data['resumen_breve'] ?? null, 1200),
            'dudas_surgidas' => $this->toNullableText($data['dudas_surgidas'] ?? null, 1200),
            'asistencia' => $this->toNullableText($data['asistencia'] ?? null, 20, true),
            'percepcion_avance' => $this->toNullableText($data['percepcion_avance'] ?? null, 20, true),
            'proxima_accion' => $this->toNullableText($data['proxima_accion'] ?? null, 1200),
            'proxima_fecha_sugerida' => $this->toNullableDateTime($data['proxima_fecha_sugerida'] ?? null),
            'responsable_usuario_id' => $this->toOptionalInt($data['responsable_usuario_id'] ?? null),
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizarDecision(array $data, int $organizacionId, int $estudioId, int $usuarioId): array
    {
        return [
            'organizacion_id' => $organizacionId,
            'estudio_id' => $estudioId,
            'decision_clave' => strtoupper($this->toRequiredText($data['decision_clave'] ?? null, 60, 'La decision es obligatoria.')),
            'decision_etiqueta' => $this->toRequiredText($data['decision_etiqueta'] ?? null, 180, 'La etiqueta de la decision es obligatoria.'),
            'fecha_decision' => $this->toRequiredDateTime($data['fecha_decision'] ?? null, 'La fecha de decision es obligatoria.'),
            'observaciones' => $this->toNullableText($data['observaciones'] ?? null, 1200),
            'requiere_seguimiento' => $this->toBool($data['requiere_seguimiento'] ?? false),
            'seguimiento_tarea_id' => null,
            'seguimiento_fecha_limite' => $this->toNullableDateTime($data['seguimiento_fecha_limite'] ?? null),
            'seguimiento_titulo' => $this->toNullableText($data['seguimiento_titulo'] ?? null, 180),
            'seguimiento_descripcion' => $this->toNullableText($data['seguimiento_descripcion'] ?? null, 1200),
            'seguimiento_responsable_usuario_id' => $this->toOptionalInt($data['seguimiento_responsable_usuario_id'] ?? null),
            'prioridad' => strtoupper((string) ($data['prioridad'] ?? 'MEDIA')),
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function resolverContactoPersona(array $data, int $usuarioId, string $actorNombre, ?int $contactoId = null): array
    {
        $payload = [
            'nombre_completo' => $data['persona_nombre'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'correo' => $data['correo'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'barrio_comunidad' => $data['barrio_comunidad'] ?? null,
            'clasificacion_principal' => 'INTERESADO',
            'es_miembro' => false,
            'estado_contacto' => 'ACTIVO',
            'origen_principal_clave' => $data['origen_clave'] ?? 'ESTUDIO_BIBLICO',
            'modulo_origen' => 'ESTUDIOS_BIBLICOS',
            'observaciones_generales' => $data['observaciones'] ?? null,
            'fecha_primer_contacto' => date('Y-m-d'),
            'fecha_ultimo_contacto' => date('Y-m-d')
        ];

        if ($contactoId !== null) {
            return $this->contactoService->actualizar($contactoId, $payload, AuthContext::getOrganizacionId(), $usuarioId, $actorNombre);
        }

        return $this->contactoService->resolverOCrear($payload, AuthContext::getOrganizacionId(), $usuarioId, $actorNombre);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    private function resolverInstructor(array $data, string $prefijo, int $usuarioId, string $actorNombre, ?int $contactoId = null): ?array
    {
        $nombre = $data[$prefijo . '_nombre'] ?? null;
        if ($nombre === null || trim((string) $nombre) === '') {
            return null;
        }

        $payload = [
            'nombre_completo' => $nombre,
            'telefono' => $data[$prefijo . '_telefono'] ?? null,
            'clasificacion_principal' => 'INSTRUCTOR_BIBLICO',
            'es_miembro' => true,
            'estado_contacto' => 'ACTIVO',
            'origen_principal_clave' => 'ESTUDIO_BIBLICO',
            'modulo_origen' => 'ESTUDIOS_BIBLICOS',
            'fecha_primer_contacto' => date('Y-m-d'),
            'fecha_ultimo_contacto' => date('Y-m-d')
        ];

        if ($contactoId !== null) {
            return $this->contactoService->actualizar($contactoId, $payload, AuthContext::getOrganizacionId(), $usuarioId, $actorNombre);
        }

        return $this->contactoService->resolverOCrear($payload, AuthContext::getOrganizacionId(), $usuarioId, $actorNombre);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function registrarAsignacionInicial(int $estudioId, array $payload, int $usuarioId, string $actorNombre, string $mensaje): void
    {
        if (
            $payload['instructor_principal_contacto_id'] === null
            && $payload['instructor_secundario_contacto_id'] === null
            && $payload['responsable_usuario_id'] === null
        ) {
            return;
        }

        $id = $this->estudioDAO->insertAssignment([
            'organizacion_id' => $payload['organizacion_id'],
            'estudio_id' => $estudioId,
            'instructor_principal_contacto_id' => $payload['instructor_principal_contacto_id'],
            'instructor_secundario_contacto_id' => $payload['instructor_secundario_contacto_id'],
            'responsable_usuario_id' => $payload['responsable_usuario_id'],
            'fecha_asignacion' => date('Y-m-d H:i:s'),
            'fecha_fin' => null,
            'motivo_cambio' => $mensaje,
            'vigente' => 1,
            'observaciones' => null,
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId
        ]);

        $asignacion = $this->buscarPorIdEnLista($this->estudioDAO->listAssignments($estudioId, $payload['organizacion_id']), $id);
        $this->auditoriaService->registrar('ESTUDIOS_BIBLICOS', 'ESTUDIO_ASIGNACION', $id, 'CREAR', $mensaje, $payload['organizacion_id'], $usuarioId, $actorNombre, null, $asignacion, ['estudio_id' => $estudioId]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function registrarReasignacion(int $estudioId, array $payload, string $motivo, int $usuarioId, string $actorNombre): void
    {
        $this->estudioDAO->closeAssignments($estudioId, $payload['organizacion_id'], $usuarioId, $motivo);
        $this->registrarAsignacionInicial($estudioId, $payload, $usuarioId, $actorNombre, $motivo);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, mixed>
     */
    private function buscarPorIdEnLista(array $items, int $id): array
    {
        foreach ($items as $item) {
            if ((int) ($item['id'] ?? 0) === $id) {
                return $item;
            }
        }
        return ['id' => $id];
    }

    private function resolverEstadoPorDecision(string $decisionClave, string $estadoActual): string
    {
        return match (strtoupper($decisionClave)) {
            'ACEPTO_CONTINUAR_ESTUDIANDO' => 'EN_PROCESO',
            'ACEPTO_ASISTIR_IGLESIA' => 'CONTACTADO',
            'ACEPTO_PREPARACION_BAUTISMAL', 'LISTO_PARA_DECISION' => 'LISTO_DECISION',
            'DECISION_BAUTISMO', 'CANDIDATO_BAUTISMAL' => 'CANDIDATO_BAUTISMAL',
            'BAUTIZADO' => 'BAUTIZADO',
            'NO_CONTINUA' => 'NO_CONTINUA',
            default => $estadoActual
        };
    }

    private function toRequiredText(mixed $value, int $max, string $message): string
    {
        $text = Sanitizer::cleanString((string) ($value ?? ''));
        if ($text === '') {
            throw new InvalidArgumentException($message);
        }
        if (strlen($text) > $max) {
            throw new InvalidArgumentException('Uno de los textos excede el maximo permitido.');
        }
        return $text;
    }

    private function toNullableText(mixed $value, int $max, bool $uppercase = false): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $text = Sanitizer::cleanString((string) $value);
        if ($text === '') {
            return null;
        }
        if (strlen($text) > $max) {
            throw new InvalidArgumentException('Uno de los textos excede el maximo permitido.');
        }
        return $uppercase ? strtoupper($text) : $text;
    }

    private function toRequiredDate(mixed $value, string $message): string
    {
        $date = $this->toNullableDate($value);
        if ($date === null) {
            throw new InvalidArgumentException($message);
        }
        return $date;
    }

    private function toNullableDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $date = trim((string) $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            throw new InvalidArgumentException('La fecha debe usar formato YYYY-MM-DD.');
        }
        return $date;
    }

    private function toRequiredDateTime(mixed $value, string $message): string
    {
        $dateTime = $this->toNullableDateTime($value);
        if ($dateTime === null) {
            throw new InvalidArgumentException($message);
        }
        return $dateTime;
    }

    private function toNullableDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $dt = trim((string) $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?$/', $dt) !== 1) {
            throw new InvalidArgumentException('La fecha y hora debe usar formato YYYY-MM-DD HH:MM[:SS].');
        }
        return str_replace('T', ' ', $dt);
    }

    private function toOptionalInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Uno de los valores numericos no es valido.');
        }
        return (int) $value;
    }

    private function toNonNegativeInt(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (!is_numeric($value) || (int) $value < 0) {
            throw new InvalidArgumentException('El total de lecciones debe ser un numero no negativo.');
        }
        return (int) $value;
    }

    private function toBool(mixed $value): bool
    {
        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $parsed === null ? false : (bool) $parsed;
    }
}
