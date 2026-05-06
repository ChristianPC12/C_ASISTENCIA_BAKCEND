<?php
declare(strict_types=1);

/**
 * Logica de negocio de estudios biblicos.
 */
final class EstudioBiblicoService
{
    private const INSTRUCTOR_NOMBRE_MAX = 35;
    private const ASIGNAR_OBSERVACIONES_MAX_CARACTERES = 110;
    private const ASIGNAR_OBSERVACIONES_MAX_SALTOS = 3;

    private EstudioBiblicoDAO $estudioDAO;
    private UsuarioDAO $usuarioDAO;
    private UsuarioService $usuarioService;
    private ContactoMisioneroService $contactoService;
    private AuditoriaService $auditoriaService;
    private SeguimientoTareaService $seguimientoService;

    public function __construct()
    {
        $this->estudioDAO = new EstudioBiblicoDAO();
        $this->usuarioDAO = new UsuarioDAO();
        $this->usuarioService = new UsuarioService();
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
        return $this->estudioDAO->findAllAsArray($this->filtrosSegunRol($filters), AuthContext::getOrganizacionId());
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function dashboard(array $filters): array
    {
        return $this->estudioDAO->getDashboard($this->filtrosSegunRol($filters), AuthContext::getOrganizacionId());
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
        $this->assertPuedeVerEstudio($dto);

        $base = EstudioBiblicoMapper::toArray($dto);
        $base['sesiones'] = $this->estudioDAO->listSessions($id, $organizacionId);
        $base['decisiones'] = $this->estudioDAO->listDecisions($id, $organizacionId);
        $base['asignaciones'] = $this->estudioDAO->listAssignments($id, $organizacionId);
        $base['visitas'] = $this->estudioDAO->listStudyVisits($id, $organizacionId);
        $base['responsables'] = $this->estudioDAO->listStudyResponsables($id, $organizacionId);
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
        $this->assertPuedeGestionar();
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
        $this->assertPuedeGestionar();
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
        $this->assertPuedeGestionar();
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
        $this->assertPuedeRegistrarSesion($estudio);

        if (AuthContext::esInstructorBiblico()) {
            $data['responsable_usuario_id'] = $usuarioId;
        }

        $payload = $this->normalizarSesion($data, $organizacionId, $estudioId, $usuarioId);
        $this->validarPeriodoSesion($estudio, $payload, $organizacionId);
        $id = $this->estudioDAO->insertSession($payload);

        $esJustificacion = $this->esSesionJustificada($payload);
        $fechaUltimaSesion = $esJustificacion ? $estudio->fechaUltimaSesion : substr((string) $payload['fecha'], 0, 10);
        $proximaSesion = null;
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
        $this->assertPuedeGestionar();
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
        $this->assertPuedeGestionar();
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
            'visita_asistente_id' => $estudio->visitaAsistenteId,
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
            'frecuencia_periodo' => $estudio->frecuenciaPeriodo,
            'frecuencia_cantidad' => $estudio->frecuenciaCantidad,
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
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function crearDesdeVisita(array $data, int $usuarioId, string $actorNombre): array
    {
        $this->assertPuedeGestionar();
        $organizacionId = AuthContext::getOrganizacionId();
        $visitaIds = $this->normalizarIdsMultiples($data['visita_ids'] ?? null, $data['visita_id'] ?? null);
        if ($visitaIds === []) {
            throw new InvalidArgumentException('Debe seleccionar al menos una visita.');
        }

        $responsableIds = $this->normalizarIdsMultiples($data['responsable_usuario_ids'] ?? null, $data['responsable_usuario_id'] ?? null);
        if ($responsableIds === []) {
            throw new InvalidArgumentException('Debe seleccionar al menos un instructor responsable.');
        }
        foreach ($responsableIds as $responsableId) {
            $this->assertUsuarioEsInstructor($responsableId);
        }

        $visitas = [];
        foreach ($visitaIds as $visitaId) {
            $visita = $this->estudioDAO->findVisitaById($visitaId, $organizacionId);
            if ($visita === null) {
                throw new OutOfBoundsException('Una de las visitas seleccionadas no fue encontrada.');
            }
            $visitas[] = $visita;
        }

        $fechaInicio = $this->toRequiredDate($data['fecha_inicio'] ?? null, 'La fecha de inicio es obligatoria.');
        if ($fechaInicio < date('Y-m-d')) {
            throw new InvalidArgumentException('La fecha de inicio no puede ser anterior a hoy.');
        }
        $frecuenciaPeriodo = strtoupper($this->toRequiredText($data['frecuencia_periodo'] ?? 'SEMANA', 20, 'La frecuencia es obligatoria.'));
        if (!in_array($frecuenciaPeriodo, ['SEMANA', 'MES', 'TRIMESTRE'], true)) {
            throw new InvalidArgumentException('La frecuencia del estudio no es valida.');
        }
        $frecuenciaCantidad = $this->toPositiveInt($data['frecuencia_cantidad'] ?? 1, 1, 31, 'La cantidad de sesiones por periodo no es valida.');
        $this->validarFrecuenciaDisponible($fechaInicio, $frecuenciaPeriodo, $frecuenciaCantidad);

        $visitasDuplicadas = [];
        foreach ($visitas as $visitaSeleccionada) {
            $duplicadoPorVisita = $this->estudioDAO->findOpenByVisitaIds([(int) $visitaSeleccionada['id']], $organizacionId);
            if ($duplicadoPorVisita !== null) {
                $visitasDuplicadas[] = $this->nombreVisitaSeleccionada($visitaSeleccionada);
            }
            if (!empty($visitaSeleccionada['contacto_id'])) {
                $duplicadoPorContacto = $this->estudioDAO->findOpenByContactoId((int) $visitaSeleccionada['contacto_id'], $organizacionId);
                if ($duplicadoPorContacto !== null) {
                    $visitasDuplicadas[] = $this->nombreVisitaSeleccionada($visitaSeleccionada);
                }
            }
        }
        if ($visitasDuplicadas !== []) {
            throw new RuntimeException($this->mensajeVisitasConEstudioActivo($visitasDuplicadas));
        }

        $visita = $visitas[0];
        $observacionesBase = $this->normalizarObservacionesAsignacion($data['observaciones'] ?? null);
        $payloadBase = [
            'persona_nombre' => $visita['nombre_completo'] ?? $visita['nombre_snapshot'] ?? '',
            'telefono' => $visita['telefono'] ?? $visita['telefono_snapshot'] ?? null,
            'correo' => $visita['correo'] ?? null,
            'direccion' => $visita['direccion'] ?? null,
            'barrio_comunidad' => $visita['barrio_comunidad'] ?? null,
            'visita_asistente_id' => $visitaIds[0],
            'origen_clave' => 'VISITA_IGLESIA',
            'campana_origen_id' => $visita['campana_id'] ?? null,
            'responsable_usuario_id' => $responsableIds[0],
            'modalidad' => $data['modalidad'] ?? 'INDIVIDUAL',
            'material_estudio' => $data['material_estudio'] ?? null,
            'leccion_actual' => $data['leccion_actual'] ?? null,
            'total_lecciones_completadas' => 0,
            'fecha_inicio' => $fechaInicio,
            'frecuencia_periodo' => $data['frecuencia_periodo'] ?? 'SEMANA',
            'frecuencia_cantidad' => $data['frecuencia_cantidad'] ?? 1,
            'estado_general' => 'ASIGNADO',
            'observaciones' => $observacionesBase
        ];

        $contactoExistenteId = !empty($visita['contacto_id']) ? (int) $visita['contacto_id'] : null;
        $contacto = $this->resolverContactoPersona($payloadBase, $usuarioId, $actorNombre, $contactoExistenteId);
        $payload = $this->normalizarEstudio($payloadBase, $organizacionId, $usuarioId, $contacto, null, null);

        $duplicado = $this->estudioDAO->findOpenByContactoId((int) $contacto['id'], $organizacionId);
        if ($duplicado !== null) {
            throw new RuntimeException($this->mensajeVisitasConEstudioActivo([$this->nombreVisitaSeleccionada($visita)]));
        }

        $id = $this->estudioDAO->insert($payload);
        $this->registrarAsignacionInicial($id, $payload, $usuarioId, $actorNombre, 'Asignacion inicial desde visitas.');
        foreach ($visitas as $index => $visitaSeleccionada) {
            $this->estudioDAO->insertStudyVisit([
                'organizacion_id' => $organizacionId,
                'estudio_id' => $id,
                'visita_asistente_id' => (int) $visitaSeleccionada['id'],
                'contacto_id' => !empty($visitaSeleccionada['contacto_id']) ? (int) $visitaSeleccionada['contacto_id'] : null,
                'principal' => $index === 0 ? 1 : 0,
                'creado_por' => $usuarioId
            ]);
            $this->estudioDAO->updateVisitaSeguimiento((int) $visitaSeleccionada['id'], $organizacionId, 'ESTUDIO_BIBLICO', $usuarioId);
        }
        foreach ($responsableIds as $index => $responsableId) {
            $this->estudioDAO->insertStudyResponsable([
                'organizacion_id' => $organizacionId,
                'estudio_id' => $id,
                'responsable_usuario_id' => $responsableId,
                'principal' => $index === 0 ? 1 : 0,
                'vigente' => 1,
                'creado_por' => $usuarioId,
                'actualizado_por' => $usuarioId
            ]);
        }
        $item = $this->obtener($id);
        $this->auditoriaService->registrar('ESTUDIOS_BIBLICOS', 'ESTUDIO_BIBLICO', $id, 'CREAR', 'Estudio biblico asignado desde visita.', $organizacionId, $usuarioId, $actorNombre, null, $item);
        return $item;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function listarInstructores(array $filters): array
    {
        $this->assertPuedeGestionar();
        $q = isset($filters['q']) ? Sanitizer::cleanString((string) $filters['q']) : '';
        $items = $this->usuarioDAO->findAllByRolNombreInOrganizacion(AuthContext::getOrganizacionId(), 'INSTRUCTOR_BIBLICO', $q);
        return array_map(static fn(UsuarioDTO $usuario): array => UsuarioMapper::toArray($usuario), $items);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function crearInstructor(array $data): array
    {
        $this->assertPuedeGestionar();
        $this->validarNombreInstructor($data['nombre_completo'] ?? null);
        $rolId = $this->usuarioService->resolverRolIdPorNombre('INSTRUCTOR_BIBLICO');
        $payload = UsuarioValidator::validateCreate([
            'nombre_completo' => $data['nombre_completo'] ?? null,
            'usuario' => $data['usuario'] ?? null,
            'password' => $data['password'] ?? null,
            'cargo' => $data['cargo'] ?? null,
            'rol_id' => $rolId
        ]);
        return $this->usuarioService->crear($payload);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function actualizarInstructor(int $id, array $data): array
    {
        $this->assertPuedeGestionar();
        $this->assertUsuarioEsInstructor($id);
        $this->validarNombreInstructor($data['nombre_completo'] ?? null);
        $rolId = $this->usuarioService->resolverRolIdPorNombre('INSTRUCTOR_BIBLICO');
        $payload = UsuarioValidator::validateUpdate([
            'nombre_completo' => $data['nombre_completo'] ?? null,
            'usuario' => $data['usuario'] ?? null,
            'cargo' => $data['cargo'] ?? null,
            'rol_id' => $rolId,
            'activo' => $data['activo'] ?? true
        ]);
        if (!empty($data['password'])) {
            $payload['password'] = $data['password'];
            $payload = UsuarioValidator::validateUpdate($payload);
        }

        return $this->usuarioService->actualizar($id, $payload);
    }

    public function eliminarInstructor(int $id): void
    {
        $this->assertPuedeGestionar();
        $this->assertUsuarioEsInstructor($id);
        $this->usuarioService->eliminar($id);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function cambiarEstado(int $id, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $estudio = $this->estudioDAO->findById($id, $organizacionId);
        if ($estudio === null) {
            throw new OutOfBoundsException('Estudio biblico no encontrado.');
        }
        $this->assertPuedeRegistrarSesion($estudio);

        $estado = strtoupper($this->toRequiredText($data['estado_general'] ?? null, 25, 'El estado del estudio es obligatorio.'));
        if (!in_array($estado, ['ASIGNADO', 'CONTACTADO', 'EN_PROCESO', 'PAUSADO', 'NO_CONTINUA', 'LISTO_DECISION', 'CANDIDATO_BAUTISMAL', 'BAUTIZADO', 'CERRADO'], true)) {
            throw new InvalidArgumentException('El estado del estudio no es valido.');
        }

        $motivo = $this->toNullableText($data['motivo_cierre_pausa'] ?? null, 1200);
        $antes = EstudioBiblicoMapper::toArray($estudio);
        $this->estudioDAO->updateStudyStatus($id, $organizacionId, $estado, $motivo, $usuarioId);
        $item = $this->obtener($id);
        $this->auditoriaService->registrar('ESTUDIOS_BIBLICOS', 'ESTUDIO_BIBLICO', $id, 'CAMBIAR_ESTADO', 'Estado de estudio biblico actualizado.', $organizacionId, $usuarioId, $actorNombre, $antes, $item);
        return $item;
    }

    private function assertPuedeGestionar(): void
    {
        $rol = AuthContext::getRol();
        if (!in_array($rol, ['ADMIN', 'SECRETARIO', 'MINISTERIO_PERSONAL'], true)) {
            throw new InvalidArgumentException('No tiene permisos para gestionar estudios biblicos.');
        }
    }

    private function assertPuedeVerEstudio(EstudioBiblicoDTO $estudio): void
    {
        if (
            AuthContext::esInstructorBiblico()
            && (int) $estudio->responsableUsuarioId !== AuthContext::getUsuarioId()
            && !$this->estudioDAO->isResponsableAsignado($estudio->id, $estudio->organizacionId, AuthContext::getUsuarioId())
        ) {
            throw new OutOfBoundsException('Estudio biblico no encontrado.');
        }
    }

    private function assertPuedeRegistrarSesion(EstudioBiblicoDTO $estudio): void
    {
        if (AuthContext::esInstructorBiblico()) {
            $this->assertPuedeVerEstudio($estudio);
            return;
        }

        $this->assertPuedeGestionar();
    }

    private function assertUsuarioEsInstructor(int $id): void
    {
        $usuario = $this->usuarioDAO->findByIdInOrganizacion($id, AuthContext::getOrganizacionId());
        if ($usuario === null || strtoupper((string) $usuario->rolNombre) !== 'INSTRUCTOR_BIBLICO') {
            throw new OutOfBoundsException('Instructor no encontrado.');
        }
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
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function filtrosSegunRol(array $filters): array
    {
        $normalizados = $this->normalizarFiltros($filters);
        if (AuthContext::esInstructorBiblico()) {
            $normalizados['responsable_usuario_id'] = AuthContext::getUsuarioId();
        }

        return $normalizados;
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

        $frecuenciaPeriodo = strtoupper($this->toRequiredText($data['frecuencia_periodo'] ?? ($base?->frecuenciaPeriodo ?? 'SEMANA'), 20, 'La frecuencia es obligatoria.'));
        if (!in_array($frecuenciaPeriodo, ['SEMANA', 'MES', 'TRIMESTRE'], true)) {
            throw new InvalidArgumentException('La frecuencia del estudio no es valida.');
        }
        $frecuenciaCantidad = $this->toPositiveInt($data['frecuencia_cantidad'] ?? ($base?->frecuenciaCantidad ?? 1), 1, 31, 'La cantidad de sesiones por periodo no es valida.');

        return [
            'organizacion_id' => $organizacionId,
            'contacto_id' => (int) $contacto['id'],
            'visita_asistente_id' => $this->toOptionalInt($data['visita_asistente_id'] ?? ($base?->visitaAsistenteId ?? null)),
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
            'frecuencia_periodo' => $frecuenciaPeriodo,
            'frecuencia_cantidad' => $frecuenciaCantidad,
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
            'progreso_bautismo' => $this->toNullablePercent($data['progreso_bautismo'] ?? null),
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

    /**
     * @param array<string, mixed> $visita
     */
    private function nombreVisitaSeleccionada(array $visita): string
    {
        $nombre = trim((string) ($visita['nombre_completo'] ?? $visita['nombre_snapshot'] ?? ''));
        if ($nombre !== '') {
            return $nombre;
        }

        $id = isset($visita['id']) ? (int) $visita['id'] : 0;
        return $id > 0 ? 'ID ' . $id : 'sin nombre';
    }

    /**
     * @param array<int, string> $nombres
     */
    private function mensajeVisitasConEstudioActivo(array $nombres): string
    {
        $limpios = array_values(array_unique(array_filter(array_map(
            static fn(string $nombre): string => trim($nombre),
            $nombres
        ))));

        if (count($limpios) === 1) {
            return 'La visita "' . $limpios[0] . '" ya tiene un estudio biblico activo. Quitela para continuar.';
        }

        $citados = array_map(static fn(string $nombre): string => '"' . $nombre . '"', $limpios);
        return 'Estas visitas ya tienen un estudio biblico activo: ' . implode(', ', $citados) . '. Quitelas para continuar.';
    }

    private function validarFrecuenciaDisponible(string $fechaInicio, string $periodo, int $cantidad): void
    {
        $inicio = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaInicio);
        if (!$inicio instanceof DateTimeImmutable || $inicio->format('Y-m-d') !== $fechaInicio) {
            throw new InvalidArgumentException('La fecha de inicio no es valida.');
        }

        $periodoNormalizado = strtoupper($periodo);
        if ($periodoNormalizado === 'SEMANA') {
            $maximo = 7;
        } else {
            $cierre = match ($periodoNormalizado) {
                'MES' => $inicio->modify('last day of this month'),
                'TRIMESTRE' => $this->finTrimestre($inicio),
                default => $inicio->modify('last day of this month'),
            };
            $maximo = min(31, ((int) $inicio->diff($cierre)->format('%a')) + 1);
        }

        if ($cantidad > $maximo) {
            $etiqueta = match ($periodoNormalizado) {
                'MES' => 'mes',
                'TRIMESTRE' => 'trimestre',
                default => 'semana',
            };
            throw new InvalidArgumentException('Para la ' . $etiqueta . ' seleccionada solo quedan ' . $maximo . ' dias disponibles desde la fecha de inicio. Ajuste "Veces" a ' . $maximo . ' o menos.');
        }
    }

    private function finTrimestre(DateTimeImmutable $fecha): DateTimeImmutable
    {
        $mesFinal = (int) (ceil(((int) $fecha->format('n')) / 3) * 3);
        return $fecha->setDate((int) $fecha->format('Y'), $mesFinal, 1)->modify('last day of this month');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validarPeriodoSesion(EstudioBiblicoDTO $estudio, array $payload, int $organizacionId): void
    {
        $periodoSesion = $this->obtenerPeriodoSesion($estudio, (string) $payload['fecha']);
        $esJustificacion = $this->esSesionJustificada($payload);

        try {
            $periodoActual = $this->obtenerPeriodoSesion($estudio, date('Y-m-d H:i:s'));
        } catch (InvalidArgumentException) {
            $periodoActual = null;
        }

        if (!$esJustificacion) {
            if ($periodoActual === null) {
                throw new InvalidArgumentException('Aun no llega el periodo de inicio de este estudio biblico.');
            }
            if ($periodoSesion['indice'] !== $periodoActual['indice']) {
                throw new InvalidArgumentException('Solo puede registrar sesiones del periodo actual. Use justificar para periodos vencidos.');
            }
        }

        if ($esJustificacion && $periodoActual === null) {
            throw new InvalidArgumentException('Aun no llega el periodo de inicio de este estudio biblico.');
        }
        if ($esJustificacion && $periodoActual !== null && $periodoSesion['indice'] > $periodoActual['indice']) {
            throw new InvalidArgumentException('No puede justificar un periodo futuro.');
        }

        $responsableRegistroId = (int) ($payload['responsable_usuario_id'] ?? 0);
        $registrosPeriodo = 0;
        foreach ($this->estudioDAO->listSessions($estudio->id, $organizacionId) as $sesion) {
            if (empty($sesion['fecha'])) {
                continue;
            }

            if ($responsableRegistroId > 0 && (int) ($sesion['responsable_usuario_id'] ?? 0) !== $responsableRegistroId) {
                continue;
            }

            try {
                $fechaSesion = $this->crearDateTimeSesion((string) $sesion['fecha']);
            } catch (InvalidArgumentException) {
                continue;
            }

            if ($fechaSesion >= $periodoSesion['inicio'] && $fechaSesion <= $periodoSesion['fin']) {
                $registrosPeriodo++;
            }
        }

        $maximo = max(1, (int) $estudio->frecuenciaCantidad);
        if ($registrosPeriodo >= $maximo) {
            throw new InvalidArgumentException($this->etiquetaPeriodoServicio($estudio->frecuenciaPeriodo, (int) $periodoSesion['indice']) . ' ya tiene el maximo de registros permitidos.');
        }
    }

    /**
     * @return array{indice:int,inicio:DateTimeImmutable,fin:DateTimeImmutable}
     */
    private function obtenerPeriodoSesion(EstudioBiblicoDTO $estudio, string $fechaSesion): array
    {
        $inicioEstudio = $this->crearDateTimeSesion($estudio->fechaInicio)->setTime(0, 0, 0);
        $fecha = $this->crearDateTimeSesion($fechaSesion);
        if ($fecha < $inicioEstudio) {
            throw new InvalidArgumentException('La fecha seleccionada no pertenece al calendario del estudio.');
        }

        $periodo = strtoupper($estudio->frecuenciaPeriodo ?: 'SEMANA');
        if ($periodo === 'MES' || $periodo === 'TRIMESTRE') {
            $mesesPorPeriodo = $periodo === 'TRIMESTRE' ? 3 : 1;
            $meses = $this->mesesDesdeInicio($inicioEstudio, $fecha);
            $indice = intdiv(max(0, $meses), $mesesPorPeriodo);
            $inicioPeriodo = $this->sumarMesesAnclado($inicioEstudio, $indice * $mesesPorPeriodo);
            $finPeriodo = $this->sumarMesesAnclado($inicioEstudio, ($indice + 1) * $mesesPorPeriodo)->modify('-1 second');
            return ['indice' => $indice, 'inicio' => $inicioPeriodo, 'fin' => $finPeriodo];
        }

        $dias = (int) $inicioEstudio->diff($fecha->setTime(0, 0, 0))->format('%a');
        $indice = intdiv($dias, 7);
        $inicioPeriodo = $inicioEstudio->modify('+' . ($indice * 7) . ' days');
        $finPeriodo = $inicioPeriodo->modify('+7 days')->modify('-1 second');
        return ['indice' => $indice, 'inicio' => $inicioPeriodo, 'fin' => $finPeriodo];
    }

    private function crearDateTimeSesion(string $value): DateTimeImmutable
    {
        $normalizado = trim(str_replace('T', ' ', $value));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalizado) === 1) {
            $normalizado .= ' 00:00:00';
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $normalizado) === 1) {
            $normalizado .= ':00';
        }

        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $normalizado);
        if (!$fecha instanceof DateTimeImmutable) {
            throw new InvalidArgumentException('La fecha y hora no es valida.');
        }

        return $fecha;
    }

    private function sumarMesesAnclado(DateTimeImmutable $fecha, int $meses): DateTimeImmutable
    {
        $anio = (int) $fecha->format('Y');
        $mes = (int) $fecha->format('n') + $meses;
        $anioDestino = $anio + intdiv($mes - 1, 12);
        $mesDestino = (($mes - 1) % 12) + 1;
        $diaAncla = (int) $fecha->format('j');
        $base = $fecha->setDate($anioDestino, $mesDestino, 1);
        $ultimoDia = (int) $base->modify('last day of this month')->format('j');
        return $base->setDate($anioDestino, $mesDestino, min($diaAncla, $ultimoDia));
    }

    private function mesesDesdeInicio(DateTimeImmutable $inicio, DateTimeImmutable $fecha): int
    {
        $meses = (((int) $fecha->format('Y') - (int) $inicio->format('Y')) * 12)
            + ((int) $fecha->format('n') - (int) $inicio->format('n'));
        if ($meses < 0) {
            return -1;
        }

        while ($this->sumarMesesAnclado($inicio, $meses + 1) <= $fecha) {
            $meses++;
        }
        while ($meses > 0 && $this->sumarMesesAnclado($inicio, $meses) > $fecha) {
            $meses--;
        }

        return $meses;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function esSesionJustificada(array $payload): bool
    {
        return strtoupper((string) ($payload['asistencia'] ?? '')) === 'JUSTIFICADA';
    }

    private function etiquetaPeriodoServicio(string $periodo, int $indice): string
    {
        return match (strtoupper($periodo)) {
            'MES' => 'Mes ' . ($indice + 1),
            'TRIMESTRE' => 'Trimestre ' . ($indice + 1),
            default => 'Semana ' . ($indice + 1),
        };
    }

    private function normalizarObservacionesAsignacion(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $texto = Sanitizer::cleanString((string) $value);
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        if ($texto === '') {
            return null;
        }
        if ($this->longitudTexto($texto) > self::ASIGNAR_OBSERVACIONES_MAX_CARACTERES) {
            throw new InvalidArgumentException('Las observaciones no pueden superar ' . self::ASIGNAR_OBSERVACIONES_MAX_CARACTERES . ' caracteres.');
        }
        if (substr_count($texto, "\n") > self::ASIGNAR_OBSERVACIONES_MAX_SALTOS) {
            throw new InvalidArgumentException('Las observaciones no pueden tener mas de ' . self::ASIGNAR_OBSERVACIONES_MAX_SALTOS . ' saltos de linea.');
        }

        return $texto;
    }

    private function validarNombreInstructor(mixed $value): void
    {
        $nombre = Sanitizer::cleanString((string) ($value ?? ''));
        if ($nombre !== '' && $this->longitudTexto($nombre) > self::INSTRUCTOR_NOMBRE_MAX) {
            throw new InvalidArgumentException('El nombre del instructor no puede superar ' . self::INSTRUCTOR_NOMBRE_MAX . ' caracteres.');
        }
    }

    private function longitudTexto(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
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

    /**
     * @return array<int, int>
     */
    private function normalizarIdsMultiples(mixed $values, mixed $fallback = null): array
    {
        $items = [];
        if (is_array($values)) {
            $items = $values;
        } elseif ($values !== null && $values !== '') {
            $items = [$values];
        }

        if ($fallback !== null && $fallback !== '') {
            $items[] = $fallback;
        }

        $ids = [];
        foreach ($items as $item) {
            if ($item === null || $item === '') {
                continue;
            }
            if (!is_numeric($item)) {
                throw new InvalidArgumentException('Uno de los valores numericos no es valido.');
            }
            $id = (int) $item;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
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

    private function toPositiveInt(mixed $value, int $min, int $max, string $message): int
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            throw new InvalidArgumentException($message);
        }

        $numero = (int) $value;
        if ($numero < $min || $numero > $max) {
            throw new InvalidArgumentException($message);
        }

        return $numero;
    }

    private function toNullablePercent(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            throw new InvalidArgumentException('El progreso debe ser numerico.');
        }

        $numero = (int) $value;
        if ($numero < 0 || $numero > 100) {
            throw new InvalidArgumentException('El progreso debe estar entre 0 y 100.');
        }

        return $numero;
    }

    private function toBool(mixed $value): bool
    {
        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $parsed === null ? false : (bool) $parsed;
    }
}
