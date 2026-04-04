<?php
declare(strict_types=1);

/**
 * Logica de negocio de pequenas congregaciones.
 */
final class PcService
{
    private PcDAO $pcDAO;
    private ContactoMisioneroService $contactoService;
    private AuditoriaService $auditoriaService;
    private EstudioBiblicoDAO $estudioDAO;
    private EstudioBiblicoService $estudioService;

    public function __construct()
    {
        $this->pcDAO = new PcDAO();
        $this->contactoService = new ContactoMisioneroService();
        $this->auditoriaService = new AuditoriaService();
        $this->estudioDAO = new EstudioBiblicoDAO();
        $this->estudioService = new EstudioBiblicoService();
    }

    /** @param array<string, mixed> $filters @return array<int, array<string, mixed>> */
    public function listar(array $filters): array
    {
        return $this->pcDAO->findAllAsArray($this->normalizarFiltros($filters), AuthContext::getOrganizacionId());
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function dashboard(array $filters): array
    {
        return $this->pcDAO->getDashboard($this->normalizarFiltros($filters), AuthContext::getOrganizacionId());
    }

    /** @return array<string, mixed> */
    public function obtener(int $id): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $dto = $this->pcDAO->findById($id, $organizacionId);
        if ($dto === null) {
            throw new OutOfBoundsException('PC no encontrada.');
        }

        $base = PcMapper::toArray($dto);
        $base['participantes'] = $this->pcDAO->listParticipants($id, $organizacionId);
        $base['reuniones'] = $this->pcDAO->listMeetings($id, $organizacionId);
        foreach ($base['reuniones'] as &$reunion) {
            $reunion['asistencia'] = $this->pcDAO->listMeetingAttendance((int) $reunion['id'], $organizacionId);
        }
        unset($reunion);
        $base['resultados'] = $this->pcDAO->listOutcomes($id, $organizacionId);
        $base['liderazgo'] = $this->pcDAO->listLeadershipHistory($id, $organizacionId);
        $base['resumen'] = [
            'total_participantes_activos' => count(array_filter($base['participantes'], static fn(array $item): bool => (string) ($item['estado_participacion'] ?? '') === 'ACTIVO')),
            'total_visitas' => count(array_filter($base['participantes'], static fn(array $item): bool => in_array((string) ($item['clasificacion'] ?? ''), ['VISITA', 'AMIGO_INTERESADO'], true))),
            'total_reuniones' => count($base['reuniones']),
            'total_estudios' => $this->sumarResultados($base['resultados'], 'ESTUDIO_BIBLICO_GENERADO'),
            'total_decisiones' => $this->sumarResultados($base['resultados'], 'DECISION_ESPIRITUAL'),
            'total_bautismos' => $this->sumarResultados($base['resultados'], 'BAUTISMO_RELACIONADO'),
            'ultima_reunion' => $base['reuniones'][0]['fecha'] ?? null
        ];

        return $base;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function crear(array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $payload = $this->normalizarPc($data, $organizacionId, $usuarioId);
        $id = $this->pcDAO->insert($payload);
        $this->sincronizarRolesIniciales($id, $payload, $usuarioId, $actorNombre);
        $item = $this->obtener($id);
        $this->auditoriaService->registrar('PCS', 'PC', $id, 'CREAR', 'PC creada.', $organizacionId, $usuarioId, $actorNombre, null, $item);
        return $item;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function actualizar(int $id, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $existente = $this->pcDAO->findById($id, $organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('PC no encontrada.');
        }

        $payload = $this->normalizarPc($data, $organizacionId, $usuarioId, $existente);
        $antes = PcMapper::toArray($existente);
        $this->pcDAO->update($id, $payload, $organizacionId);
        $this->sincronizarRolSiCambio($id, 'LIDER_PRINCIPAL', $existente->liderPrincipalContactoId, $payload['lider_principal_contacto_id'], $payload['fecha_inicio'], $usuarioId, $actorNombre);
        $this->sincronizarRolSiCambio($id, 'COLIDER', $existente->liderAuxiliarContactoId, $payload['lider_auxiliar_contacto_id'], $payload['fecha_inicio'], $usuarioId, $actorNombre);
        $this->sincronizarRolSiCambio($id, 'ANFITRION', $existente->anfitrionContactoId, $payload['anfitrion_contacto_id'], $payload['fecha_inicio'], $usuarioId, $actorNombre);
        $item = $this->obtener($id);
        $this->auditoriaService->registrar('PCS', 'PC', $id, 'ACTUALIZAR', 'PC actualizada.', $organizacionId, $usuarioId, $actorNombre, $antes, $item);
        return $item;
    }

    public function eliminar(int $id, int $usuarioId, string $actorNombre): void
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $existente = $this->pcDAO->findById($id, $organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('PC no encontrada.');
        }
        $this->pcDAO->softDelete($id, $organizacionId, $usuarioId);
        $this->auditoriaService->registrar('PCS', 'PC', $id, 'ARCHIVAR', 'PC archivada.', $organizacionId, $usuarioId, $actorNombre, PcMapper::toArray($existente), null);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function crearParticipante(int $pcId, array $data, int $usuarioId, string $actorNombre): array
    {
        $this->obtenerPcDto($pcId);
        $contacto = $this->resolverContactoParticipante($data, $usuarioId, $actorNombre);
        $payload = $this->normalizarParticipante($data, $pcId, AuthContext::getOrganizacionId(), $usuarioId, $contacto);
        $this->assertParticipanteUnico($pcId, (int) $contacto['id']);
        $id = $this->pcDAO->insertParticipant($payload);
        $item = $this->buscarPorIdEnLista($this->pcDAO->listParticipants($pcId, AuthContext::getOrganizacionId()), $id);
        $this->auditoriaService->registrar('PCS', 'PC_PARTICIPANTE', $id, 'CREAR', 'Participante registrado en PC.', AuthContext::getOrganizacionId(), $usuarioId, $actorNombre, null, $item, ['pc_id' => $pcId]);
        return $this->obtener($pcId);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function actualizarParticipante(int $participanteId, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $existente = $this->pcDAO->findParticipantById($participanteId, $organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('Participante no encontrado.');
        }
        $contacto = $this->resolverContactoParticipante($data, $usuarioId, $actorNombre, (int) $existente['contacto_id']);
        $payload = $this->normalizarParticipante($data, (int) $existente['pc_id'], $organizacionId, $usuarioId, $contacto, $existente);
        $this->assertParticipanteUnico((int) $existente['pc_id'], (int) $contacto['id'], $participanteId);
        $this->pcDAO->updateParticipant($participanteId, $payload, $organizacionId);
        $item = $this->buscarPorIdEnLista($this->pcDAO->listParticipants((int) $existente['pc_id'], $organizacionId), $participanteId);
        $this->auditoriaService->registrar('PCS', 'PC_PARTICIPANTE', $participanteId, 'ACTUALIZAR', 'Participante de PC actualizado.', $organizacionId, $usuarioId, $actorNombre, $existente, $item, ['pc_id' => (int) $existente['pc_id']]);
        return $this->obtener((int) $existente['pc_id']);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function crearReunion(int $pcId, array $data, int $usuarioId, string $actorNombre): array
    {
        $this->obtenerPcDto($pcId);
        $payload = $this->normalizarReunion($data, $pcId, AuthContext::getOrganizacionId(), $usuarioId);
        $id = $this->pcDAO->insertMeeting($payload);
        $item = $this->buscarPorIdEnLista($this->pcDAO->listMeetings($pcId, AuthContext::getOrganizacionId()), $id);
        $this->auditoriaService->registrar('PCS', 'PC_REUNION', $id, 'CREAR', 'Reunion de PC registrada.', AuthContext::getOrganizacionId(), $usuarioId, $actorNombre, null, $item, ['pc_id' => $pcId]);
        return $this->obtener($pcId);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function actualizarReunion(int $reunionId, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $existente = $this->pcDAO->findMeetingById($reunionId, $organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('Reunion no encontrada.');
        }
        $payload = $this->normalizarReunion($data, (int) $existente['pc_id'], $organizacionId, $usuarioId, $existente);
        $this->pcDAO->updateMeeting($reunionId, $payload, $organizacionId);
        $item = $this->buscarPorIdEnLista($this->pcDAO->listMeetings((int) $existente['pc_id'], $organizacionId), $reunionId);
        $this->auditoriaService->registrar('PCS', 'PC_REUNION', $reunionId, 'ACTUALIZAR', 'Reunion de PC actualizada.', $organizacionId, $usuarioId, $actorNombre, $existente, $item, ['pc_id' => (int) $existente['pc_id']]);
        return $this->obtener((int) $existente['pc_id']);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function registrarAsistenciaReunion(int $reunionId, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $reunion = $this->pcDAO->findMeetingById($reunionId, $organizacionId);
        if ($reunion === null) {
            throw new OutOfBoundsException('Reunion no encontrada.');
        }
        $participanteId = $this->toRequiredInt($data['participante_id'] ?? null, 'Debe seleccionar un participante.');
        $participante = $this->pcDAO->findParticipantById($participanteId, $organizacionId);
        if ($participante === null || (int) $participante['pc_id'] !== (int) $reunion['pc_id']) {
            throw new InvalidArgumentException('El participante no pertenece a esa PC.');
        }
        $item = $this->pcDAO->upsertMeetingAttendance($reunionId, $organizacionId, [
            'contacto_id' => (int) $participante['contacto_id'],
            'asistio' => $this->toBool($data['asistio'] ?? true),
            'clasificacion_dia' => $this->toNullableText($data['clasificacion_dia'] ?? null, 30, true),
            'observaciones' => $this->toNullableText($data['observaciones'] ?? null, 600),
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId
        ]);
        $this->auditoriaService->registrar('PCS', 'PC_REUNION_ASISTENCIA', $item['id'] ?? null, 'ACTUALIZAR', 'Asistencia individual de reunion registrada.', $organizacionId, $usuarioId, $actorNombre, null, $item, ['pc_id' => (int) $reunion['pc_id'], 'reunion_id' => $reunionId]);
        return $this->obtener((int) $reunion['pc_id']);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function crearResultado(int $pcId, array $data, int $usuarioId, string $actorNombre): array
    {
        $pc = $this->obtenerPcDto($pcId);
        $payload = $this->normalizarResultado($data, $pcId, AuthContext::getOrganizacionId(), $usuarioId, null);
        $id = $this->pcDAO->insertOutcome($payload);
        $this->aplicarResultadoSobrePc($pc, $payload, $usuarioId);
        $item = $this->buscarPorIdEnLista($this->pcDAO->listOutcomes($pcId, AuthContext::getOrganizacionId()), $id);
        $this->auditoriaService->registrar('PCS', 'PC_RESULTADO', $id, 'CREAR', 'Resultado de PC registrado.', AuthContext::getOrganizacionId(), $usuarioId, $actorNombre, null, $item, ['pc_id' => $pcId]);
        return $this->obtener($pcId);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function actualizarResultado(int $resultadoId, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $existente = $this->pcDAO->findOutcomeById($resultadoId, $organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('Resultado no encontrado.');
        }
        $payload = $this->normalizarResultado($data, (int) $existente['pc_id'], $organizacionId, $usuarioId, $existente);
        $this->pcDAO->updateOutcome($resultadoId, $payload, $organizacionId);
        $item = $this->buscarPorIdEnLista($this->pcDAO->listOutcomes((int) $existente['pc_id'], $organizacionId), $resultadoId);
        $this->auditoriaService->registrar('PCS', 'PC_RESULTADO', $resultadoId, 'ACTUALIZAR', 'Resultado de PC actualizado.', $organizacionId, $usuarioId, $actorNombre, $existente, $item, ['pc_id' => (int) $existente['pc_id']]);
        return $this->obtener((int) $existente['pc_id']);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function crearLiderazgo(int $pcId, array $data, int $usuarioId, string $actorNombre): array
    {
        $this->obtenerPcDto($pcId);
        $payload = $this->normalizarLiderazgo($data, $pcId, AuthContext::getOrganizacionId(), $usuarioId);
        $this->pcDAO->closeLeadershipByRole($pcId, AuthContext::getOrganizacionId(), (string) $payload['rol_liderazgo'], $usuarioId, $payload['motivo_cambio']);
        $id = $this->pcDAO->insertLeadership($payload);
        $item = $this->buscarPorIdEnLista($this->pcDAO->listLeadershipHistory($pcId, AuthContext::getOrganizacionId()), $id);
        $this->auditoriaService->registrar('PCS', 'PC_LIDERAZGO', $id, 'CREAR', 'Movimiento de liderazgo registrado.', AuthContext::getOrganizacionId(), $usuarioId, $actorNombre, null, $item, ['pc_id' => $pcId]);
        return $this->obtener($pcId);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function actualizarLiderazgo(int $liderazgoId, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $existente = $this->pcDAO->findLeadershipById($liderazgoId, $organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('Movimiento de liderazgo no encontrado.');
        }
        $payload = $this->normalizarLiderazgo($data, (int) $existente['pc_id'], $organizacionId, $usuarioId, $existente);
        $this->pcDAO->updateLeadership($liderazgoId, $payload, $organizacionId);
        $item = $this->buscarPorIdEnLista($this->pcDAO->listLeadershipHistory((int) $existente['pc_id'], $organizacionId), $liderazgoId);
        $this->auditoriaService->registrar('PCS', 'PC_LIDERAZGO', $liderazgoId, 'ACTUALIZAR', 'Movimiento de liderazgo actualizado.', $organizacionId, $usuarioId, $actorNombre, $existente, $item, ['pc_id' => (int) $existente['pc_id']]);
        return $this->obtener((int) $existente['pc_id']);
    }

    /**
     * @return array<string, mixed>
     */
    public function convertirParticipanteAEstudio(int $participanteId, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $participante = $this->pcDAO->findParticipantById($participanteId, $organizacionId);
        if ($participante === null) {
            throw new OutOfBoundsException('Participante no encontrado.');
        }
        if ((bool) ($participante['es_miembro'] ?? false) || strtoupper((string) ($participante['clasificacion'] ?? '')) === 'MIEMBRO_IGLESIA') {
            throw new InvalidArgumentException('Solo los participantes no miembros pueden convertirse en estudio biblico.');
        }

        $pc = $this->obtenerPcDto((int) $participante['pc_id']);
        $contacto = $this->contactoService->obtener((int) $participante['contacto_id'], $organizacionId);
        $pdo = Conexion::getConexion();
        $iniciadaAqui = false;

        try {
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $iniciadaAqui = true;
            }

            $estudio = $this->estudioService->crear([
                'persona_nombre' => $contacto['nombre_completo'] ?? '',
                'telefono' => $contacto['telefono'] ?? null,
                'correo' => $contacto['correo'] ?? null,
                'direccion' => $contacto['direccion'] ?? null,
                'barrio_comunidad' => $contacto['barrio_comunidad'] ?? ($pc->sector ?? null),
                'origen_clave' => 'PC',
                'campana_origen_id' => null,
                'pc_origen_id' => $pc->id,
                'responsable_usuario_id' => $usuarioId,
                'modalidad' => 'HOGAR',
                'material_estudio' => null,
                'leccion_actual' => null,
                'total_lecciones_completadas' => 0,
                'fecha_inicio' => date('Y-m-d'),
                'fecha_ultima_sesion' => null,
                'proxima_sesion' => null,
                'estado_general' => 'ASIGNADO',
                'observaciones' => 'Estudio biblico generado desde la PC ' . $pc->nombrePc . '.',
                'motivo_cierre_pausa' => null
            ], $usuarioId, $actorNombre);

            $resultadoId = $this->pcDAO->insertOutcome([
                'organizacion_id' => $organizacionId,
                'pc_id' => $pc->id,
                'fecha' => date('Y-m-d'),
                'tipo_resultado' => 'ESTUDIO_BIBLICO_GENERADO',
                'contacto_id' => (int) $participante['contacto_id'],
                'estudio_biblico_id' => (int) ($estudio['id'] ?? 0),
                'cantidad' => 1,
                'descripcion' => 'Estudio biblico generado desde participante activo de la PC.',
                'observaciones' => null,
                'creado_por' => $usuarioId,
                'actualizado_por' => $usuarioId
            ]);
            $resultado = $this->buscarPorIdEnLista($this->pcDAO->listOutcomes($pc->id, $organizacionId), $resultadoId);

            $this->auditoriaService->registrar(
                'PCS',
                'PC_PARTICIPANTE',
                $participanteId,
                'CONVERTIR_ESTUDIO',
                'Participante convertido a estudio biblico.',
                $organizacionId,
                $usuarioId,
                $actorNombre,
                $participante,
                $participante,
                ['pc_id' => $pc->id, 'estudio_biblico_id' => (int) ($estudio['id'] ?? 0)]
            );
            $this->auditoriaService->registrar(
                'PCS',
                'PC_RESULTADO',
                $resultadoId,
                'CREAR',
                'Resultado automatico de estudio biblico generado.',
                $organizacionId,
                $usuarioId,
                $actorNombre,
                null,
                $resultado,
                ['pc_id' => $pc->id, 'estudio_biblico_id' => (int) ($estudio['id'] ?? 0)]
            );

            if ($iniciadaAqui) {
                $pdo->commit();
            }

            return [
                'estudio' => $estudio,
                'resultado' => $resultado,
                'participante' => $participante
            ];
        } catch (\Throwable $e) {
            if ($iniciadaAqui && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function normalizarFiltros(array $filters): array
    {
        return [
            'q' => isset($filters['q']) ? trim((string) $filters['q']) : '',
            'estado' => isset($filters['estado']) ? strtoupper(trim((string) $filters['estado'])) : '',
            'sector' => isset($filters['sector']) ? trim((string) $filters['sector']) : '',
            'fecha_desde' => $this->toNullableDate($filters['fecha_desde'] ?? null),
            'fecha_hasta' => $this->toNullableDate($filters['fecha_hasta'] ?? null),
            'sin_reunion_dias' => $this->toOptionalInt($filters['sin_reunion_dias'] ?? null)
        ];
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function normalizarPc(array $data, int $organizacionId, int $usuarioId, ?PcDTO $base = null): array
    {
        $estado = strtoupper($this->toRequiredText($data['estado'] ?? ($base?->estado ?? null), 20, 'El estado de la PC es obligatorio.'));
        if (!in_array($estado, ['ACTIVA', 'INACTIVA', 'MULTIPLICADA', 'CERRADA', 'PAUSADA'], true)) {
            throw new InvalidArgumentException('El estado de la PC no es valido.');
        }
        $anfitrion = $this->resolverContactoRol($data['anfitrion_nombre'] ?? null, $data['anfitrion_telefono'] ?? null, 'ANFITRION', $usuarioId, $base?->anfitrionContactoId);
        $liderPrincipal = $this->resolverContactoRol($data['lider_principal_nombre'] ?? null, $data['lider_principal_telefono'] ?? null, 'LIDER', $usuarioId, $base?->liderPrincipalContactoId);
        $liderAuxiliar = $this->resolverContactoRol($data['lider_auxiliar_nombre'] ?? null, $data['lider_auxiliar_telefono'] ?? null, 'LIDER', $usuarioId, $base?->liderAuxiliarContactoId);
        $fechaInicio = $this->toRequiredDate($data['fecha_inicio'] ?? ($base?->fechaInicio ?? null), 'La fecha de inicio es obligatoria.');
        $fechaFin = $this->toNullableDate($data['fecha_fin'] ?? ($base?->fechaFin ?? null));
        if ($fechaFin !== null && $fechaFin < $fechaInicio) {
            throw new InvalidArgumentException('La fecha final no puede ser menor a la inicial.');
        }
        return [
            'organizacion_id' => $organizacionId,
            'nombre_pc' => $this->toRequiredText($data['nombre_pc'] ?? ($base?->nombrePc ?? null), 160, 'El nombre de la PC es obligatorio.'),
            'sector' => $this->toNullableText($data['sector'] ?? ($base?->sector ?? null), 120),
            'comunidad' => $this->toNullableText($data['comunidad'] ?? ($base?->comunidad ?? null), 120),
            'direccion_reunion' => $this->toNullableText($data['direccion_reunion'] ?? ($base?->direccionReunion ?? null), 220),
            'anfitrion_contacto_id' => $anfitrion['id'] ?? null,
            'lider_principal_contacto_id' => $liderPrincipal['id'] ?? null,
            'lider_auxiliar_contacto_id' => $liderAuxiliar['id'] ?? null,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'dia_reunion' => $this->toOptionalDia($data['dia_reunion'] ?? ($base?->diaReunion ?? null)),
            'hora_reunion' => $this->toNullableTime($data['hora_reunion'] ?? ($base?->horaReunion ?? null)),
            'estado' => $estado,
            'pc_madre_id' => $this->toOptionalInt($data['pc_madre_id'] ?? ($base?->pcMadreId ?? null)),
            'motivo_cierre' => $this->toNullableText($data['motivo_cierre'] ?? ($base?->motivoCierre ?? null), 1200),
            'meta_trimestral' => $this->toNullableText($data['meta_trimestral'] ?? ($base?->metaTrimestral ?? null), 160),
            'observaciones_generales' => $this->toNullableText($data['observaciones_generales'] ?? ($base?->observacionesGenerales ?? null), 1500),
            'creado_por' => $base?->creadoPor ?? $usuarioId,
            'actualizado_por' => $usuarioId
        ];
    }

    /** @param array<string, mixed> $data @param array<string, mixed>|null $base @param array<string, mixed> $contacto @return array<string, mixed> */
    private function normalizarParticipante(array $data, int $pcId, int $organizacionId, int $usuarioId, array $contacto, ?array $base = null): array
    {
        $clasificacion = strtoupper($this->toRequiredText($data['clasificacion'] ?? ($base['clasificacion'] ?? null), 30, 'La clasificacion es obligatoria.'));
        if (!in_array($clasificacion, ['MIEMBRO_IGLESIA', 'VISITA', 'AMIGO_INTERESADO', 'NINO', 'JOVEN', 'ADULTO', 'LIDER', 'ANFITRION', 'INSTRUCTOR_BIBLICO', 'OTRO'], true)) {
            throw new InvalidArgumentException('La clasificacion del participante no es valida.');
        }
        $estado = strtoupper($this->toRequiredText($data['estado_participacion'] ?? ($base['estado_participacion'] ?? null), 20, 'El estado del participante es obligatorio.'));
        if (!in_array($estado, ['ACTIVO', 'PAUSADO', 'RETIRADO'], true)) {
            throw new InvalidArgumentException('El estado del participante no es valido.');
        }
        return [
            'organizacion_id' => $organizacionId,
            'pc_id' => $pcId,
            'contacto_id' => (int) $contacto['id'],
            'clasificacion' => $clasificacion,
            'rol_pc' => $this->toNullableText($data['rol_pc'] ?? ($base['rol_pc'] ?? null), 80),
            'es_miembro' => $this->toBool($data['es_miembro'] ?? ($base['es_miembro'] ?? false)),
            'fecha_ingreso' => $this->toRequiredDate($data['fecha_ingreso'] ?? ($base['fecha_ingreso'] ?? null), 'La fecha de ingreso es obligatoria.'),
            'fecha_salida' => $this->toNullableDate($data['fecha_salida'] ?? ($base['fecha_salida'] ?? null)),
            'motivo_salida' => $this->toNullableText($data['motivo_salida'] ?? ($base['motivo_salida'] ?? null), 800),
            'estado_participacion' => $estado,
            'observaciones' => $this->toNullableText($data['observaciones'] ?? ($base['observaciones'] ?? null), 800),
            'creado_por' => isset($base['creado_por']) ? (int) $base['creado_por'] : $usuarioId,
            'actualizado_por' => $usuarioId
        ];
    }

    /** @param array<string, mixed> $data @param array<string, mixed>|null $base @return array<string, mixed> */
    private function normalizarReunion(array $data, int $pcId, int $organizacionId, int $usuarioId, ?array $base = null): array
    {
        return [
            'organizacion_id' => $organizacionId,
            'pc_id' => $pcId,
            'fecha' => $this->toRequiredDate($data['fecha'] ?? ($base['fecha'] ?? null), 'La fecha de reunion es obligatoria.'),
            'tema_titulo' => $this->toRequiredText($data['tema_titulo'] ?? ($base['tema_titulo'] ?? null), 180, 'El tema o titulo es obligatorio.'),
            'material_usado' => $this->toNullableText($data['material_usado'] ?? ($base['material_usado'] ?? null), 180),
            'hubo_estudio_biblico' => $this->toBool($data['hubo_estudio_biblico'] ?? ($base['hubo_estudio_biblico'] ?? false)),
            'hubo_visita' => $this->toBool($data['hubo_visita'] ?? ($base['hubo_visita'] ?? false)),
            'cantidad_asistentes' => $this->toIntNoNegativo($data['cantidad_asistentes'] ?? ($base['cantidad_asistentes'] ?? 0)),
            'total_miembros' => $this->toIntNoNegativo($data['total_miembros'] ?? ($base['total_miembros'] ?? 0)),
            'total_visitas' => $this->toIntNoNegativo($data['total_visitas'] ?? ($base['total_visitas'] ?? 0)),
            'total_ninos' => $this->toIntNoNegativo($data['total_ninos'] ?? ($base['total_ninos'] ?? 0)),
            'total_jovenes' => $this->toIntNoNegativo($data['total_jovenes'] ?? ($base['total_jovenes'] ?? 0)),
            'total_adultos' => $this->toIntNoNegativo($data['total_adultos'] ?? ($base['total_adultos'] ?? 0)),
            'observacion_reunion' => $this->toNullableText($data['observacion_reunion'] ?? ($base['observacion_reunion'] ?? null), 1200),
            'decisiones_tomadas' => $this->toNullableText($data['decisiones_tomadas'] ?? ($base['decisiones_tomadas'] ?? null), 1200),
            'proximos_pasos' => $this->toNullableText($data['proximos_pasos'] ?? ($base['proximos_pasos'] ?? null), 1200),
            'responsable_seguimiento_usuario_id' => $this->toOptionalInt($data['responsable_seguimiento_usuario_id'] ?? ($base['responsable_seguimiento_usuario_id'] ?? null)),
            'creado_por' => isset($base['creado_por']) ? (int) $base['creado_por'] : $usuarioId,
            'actualizado_por' => $usuarioId
        ];
    }

    /** @param array<string, mixed> $data @param array<string, mixed>|null $base @return array<string, mixed> */
    private function normalizarResultado(array $data, int $pcId, int $organizacionId, int $usuarioId, ?array $base = null): array
    {
        $tipo = strtoupper($this->toRequiredText($data['tipo_resultado'] ?? ($base['tipo_resultado'] ?? null), 35, 'El tipo de resultado es obligatorio.'));
        if (!in_array($tipo, ['ESTUDIO_BIBLICO_GENERADO', 'INTERESADO_NUEVO', 'DECISION_ESPIRITUAL', 'BAUTISMO_RELACIONADO', 'MIEMBRO_REACTIVADO', 'MULTIPLICACION', 'CIERRE', 'OTRO'], true)) {
            throw new InvalidArgumentException('El tipo de resultado no es valido.');
        }
        $contacto = $this->resolverContactoOpcional($data['contacto_nombre'] ?? null, $data['contacto_telefono'] ?? null, 'INTERESADO', $usuarioId, isset($base['contacto_id']) ? (int) $base['contacto_id'] : null);
        $estudioId = $this->toOptionalInt($data['estudio_biblico_id'] ?? ($base['estudio_biblico_id'] ?? null));
        if ($estudioId !== null && $this->estudioDAO->findById($estudioId, $organizacionId) === null) {
            throw new InvalidArgumentException('El estudio biblico asociado no existe.');
        }
        return [
            'organizacion_id' => $organizacionId,
            'pc_id' => $pcId,
            'fecha' => $this->toRequiredDate($data['fecha'] ?? ($base['fecha'] ?? null), 'La fecha del resultado es obligatoria.'),
            'tipo_resultado' => $tipo,
            'contacto_id' => $contacto['id'] ?? null,
            'estudio_biblico_id' => $estudioId,
            'cantidad' => max(1, $this->toIntNoNegativo($data['cantidad'] ?? ($base['cantidad'] ?? 1))),
            'descripcion' => $this->toNullableText($data['descripcion'] ?? ($base['descripcion'] ?? null), 1200),
            'observaciones' => $this->toNullableText($data['observaciones'] ?? ($base['observaciones'] ?? null), 1200),
            'creado_por' => isset($base['creado_por']) ? (int) $base['creado_por'] : $usuarioId,
            'actualizado_por' => $usuarioId
        ];
    }

    /** @param array<string, mixed> $data @param array<string, mixed>|null $base @return array<string, mixed> */
    private function normalizarLiderazgo(array $data, int $pcId, int $organizacionId, int $usuarioId, ?array $base = null): array
    {
        $rol = strtoupper($this->toRequiredText($data['rol_liderazgo'] ?? ($base['rol_liderazgo'] ?? null), 30, 'El rol de liderazgo es obligatorio.'));
        if (!in_array($rol, ['LIDER_PRINCIPAL', 'COLIDER', 'ANFITRION', 'INSTRUCTOR_ASOCIADO'], true)) {
            throw new InvalidArgumentException('El rol de liderazgo no es valido.');
        }
        $contacto = $this->resolverContactoOpcional($data['nombre'] ?? null, $data['telefono'] ?? null, $rol === 'ANFITRION' ? 'ANFITRION' : 'LIDER', $usuarioId, isset($base['contacto_id']) ? (int) $base['contacto_id'] : null);
        if ($contacto === null) {
            throw new InvalidArgumentException('Debe indicar el nombre de la persona.');
        }
        return [
            'organizacion_id' => $organizacionId,
            'pc_id' => $pcId,
            'contacto_id' => (int) $contacto['id'],
            'rol_liderazgo' => $rol,
            'fecha_inicio' => $this->toRequiredDate($data['fecha_inicio'] ?? ($base['fecha_inicio'] ?? null), 'La fecha de inicio es obligatoria.'),
            'fecha_fin' => $this->toNullableDate($data['fecha_fin'] ?? ($base['fecha_fin'] ?? null)),
            'motivo_cambio' => $this->toNullableText($data['motivo_cambio'] ?? ($base['motivo_cambio'] ?? null), 800),
            'observaciones' => $this->toNullableText($data['observaciones'] ?? ($base['observaciones'] ?? null), 800),
            'creado_por' => isset($base['creado_por']) ? (int) $base['creado_por'] : $usuarioId,
            'actualizado_por' => $usuarioId
        ];
    }

    private function sincronizarRolesIniciales(int $pcId, array $payload, int $usuarioId, string $actorNombre): void
    {
        $this->registrarRolInicial($pcId, 'LIDER_PRINCIPAL', $payload['lider_principal_contacto_id'], $payload['fecha_inicio'], $usuarioId, $actorNombre);
        $this->registrarRolInicial($pcId, 'COLIDER', $payload['lider_auxiliar_contacto_id'], $payload['fecha_inicio'], $usuarioId, $actorNombre);
        $this->registrarRolInicial($pcId, 'ANFITRION', $payload['anfitrion_contacto_id'], $payload['fecha_inicio'], $usuarioId, $actorNombre);
    }

    private function registrarRolInicial(int $pcId, string $rol, ?int $contactoId, string $fechaInicio, int $usuarioId, string $actorNombre): void
    {
        if ($contactoId === null) {
            return;
        }
        $id = $this->pcDAO->insertLeadership([
            'organizacion_id' => AuthContext::getOrganizacionId(),
            'pc_id' => $pcId,
            'contacto_id' => $contactoId,
            'rol_liderazgo' => $rol,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => null,
            'motivo_cambio' => 'Registro inicial de la PC.',
            'observaciones' => null,
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId
        ]);
        $item = $this->buscarPorIdEnLista($this->pcDAO->listLeadershipHistory($pcId, AuthContext::getOrganizacionId()), $id);
        $this->auditoriaService->registrar('PCS', 'PC_LIDERAZGO', $id, 'CREAR', 'Rol inicial de liderazgo registrado.', AuthContext::getOrganizacionId(), $usuarioId, $actorNombre, null, $item, ['pc_id' => $pcId]);
    }

    private function sincronizarRolSiCambio(int $pcId, string $rol, ?int $anterior, ?int $nuevo, string $fechaBase, int $usuarioId, string $actorNombre): void
    {
        if ($anterior === $nuevo) {
            return;
        }
        $this->pcDAO->closeLeadershipByRole($pcId, AuthContext::getOrganizacionId(), $rol, $usuarioId, 'Cambio de rol en la ficha de la PC.');
        if ($nuevo !== null) {
            $this->registrarRolInicial($pcId, $rol, $nuevo, $fechaBase, $usuarioId, $actorNombre);
        }
    }

    private function aplicarResultadoSobrePc(PcDTO $pc, array $payload, int $usuarioId): void
    {
        if (!in_array((string) $payload['tipo_resultado'], ['MULTIPLICACION', 'CIERRE'], true)) {
            return;
        }
        $nuevoEstado = $payload['tipo_resultado'] === 'MULTIPLICACION' ? 'MULTIPLICADA' : 'CERRADA';
        $this->pcDAO->update($pc->id, [
            'nombre_pc' => $pc->nombrePc,
            'sector' => $pc->sector,
            'comunidad' => $pc->comunidad,
            'direccion_reunion' => $pc->direccionReunion,
            'anfitrion_contacto_id' => $pc->anfitrionContactoId,
            'lider_principal_contacto_id' => $pc->liderPrincipalContactoId,
            'lider_auxiliar_contacto_id' => $pc->liderAuxiliarContactoId,
            'fecha_inicio' => $pc->fechaInicio,
            'fecha_fin' => $pc->fechaFin ?? $payload['fecha'],
            'dia_reunion' => $pc->diaReunion,
            'hora_reunion' => $pc->horaReunion,
            'estado' => $nuevoEstado,
            'pc_madre_id' => $pc->pcMadreId,
            'motivo_cierre' => $payload['descripcion'] ?? $pc->motivoCierre,
            'meta_trimestral' => $pc->metaTrimestral,
            'observaciones_generales' => $pc->observacionesGenerales,
            'actualizado_por' => $usuarioId
        ], AuthContext::getOrganizacionId());
    }

    private function obtenerPcDto(int $id): PcDTO
    {
        $dto = $this->pcDAO->findById($id, AuthContext::getOrganizacionId());
        if ($dto === null) {
            throw new OutOfBoundsException('PC no encontrada.');
        }
        return $dto;
    }

    private function assertParticipanteUnico(int $pcId, int $contactoId, ?int $excludeId = null): void
    {
        foreach ($this->pcDAO->listParticipants($pcId, AuthContext::getOrganizacionId()) as $item) {
            if ((int) ($item['contacto_id'] ?? 0) === $contactoId && (int) ($item['id'] ?? 0) !== (int) ($excludeId ?? 0)) {
                throw new InvalidArgumentException('Ese contacto ya esta registrado en la PC.');
            }
        }
    }

    /** @return array<string, mixed> */
    private function resolverContactoParticipante(array $data, int $usuarioId, string $actorNombre, ?int $existenteId = null): array
    {
        $payload = [
            'nombre_completo' => $this->toRequiredText($data['nombre'] ?? null, 160, 'El nombre es obligatorio.'),
            'telefono' => $this->toNullableText($data['telefono'] ?? null, 30),
            'clasificacion_principal' => $this->mapearClasificacionContacto((string) ($data['clasificacion'] ?? 'OTRO')),
            'es_miembro' => $this->toBool($data['es_miembro'] ?? false),
            'estado_contacto' => 'ACTIVO',
            'origen_principal_clave' => 'PC',
            'modulo_origen' => 'PCS',
            'fecha_primer_contacto' => date('Y-m-d'),
            'fecha_ultimo_contacto' => date('Y-m-d')
        ];
        if ($existenteId !== null) {
            return $this->contactoService->actualizar($existenteId, $payload, AuthContext::getOrganizacionId(), $usuarioId, $actorNombre);
        }
        return $this->contactoService->resolverOCrear($payload, AuthContext::getOrganizacionId(), $usuarioId, $actorNombre);
    }

    /** @return array<string, mixed>|null */
    private function resolverContactoRol(mixed $nombre, mixed $telefono, string $clasificacion, int $usuarioId, ?int $existenteId = null): ?array
    {
        return $this->resolverContactoOpcional($nombre, $telefono, $clasificacion, $usuarioId, $existenteId);
    }

    /** @return array<string, mixed>|null */
    private function resolverContactoOpcional(mixed $nombre, mixed $telefono, string $clasificacion, int $usuarioId, ?int $existenteId = null): ?array
    {
        $nombreLimpio = $this->toNullableText($nombre, 160);
        if ($nombreLimpio === null) {
            return null;
        }
        $payload = [
            'nombre_completo' => $nombreLimpio,
            'telefono' => $this->toNullableText($telefono, 30),
            'clasificacion_principal' => $clasificacion,
            'estado_contacto' => 'ACTIVO',
            'origen_principal_clave' => 'PC',
            'modulo_origen' => 'PCS',
            'fecha_primer_contacto' => date('Y-m-d'),
            'fecha_ultimo_contacto' => date('Y-m-d')
        ];
        if ($existenteId !== null) {
            return $this->contactoService->actualizar($existenteId, $payload, AuthContext::getOrganizacionId(), $usuarioId, '');
        }
        return $this->contactoService->resolverOCrear($payload, AuthContext::getOrganizacionId(), $usuarioId, '');
    }

    private function mapearClasificacionContacto(string $clasificacion): string
    {
        return match (strtoupper($clasificacion)) {
            'MIEMBRO_IGLESIA' => 'MIEMBRO',
            'VISITA' => 'VISITA',
            'AMIGO_INTERESADO' => 'INTERESADO',
            'NINO' => 'NINO',
            'JOVEN' => 'JOVEN',
            'ADULTO' => 'ADULTO',
            'LIDER' => 'LIDER',
            'ANFITRION' => 'ANFITRION',
            'INSTRUCTOR_BIBLICO' => 'INSTRUCTOR_BIBLICO',
            default => 'OTRO'
        };
    }

    /** @param array<int, array<string, mixed>> $items @return array<string, mixed> */
    private function buscarPorIdEnLista(array $items, int $id): array
    {
        foreach ($items as $item) {
            if ((int) ($item['id'] ?? 0) === $id) {
                return $item;
            }
        }
        throw new RuntimeException('No fue posible recuperar el registro guardado.');
    }

    /** @param array<int, array<string, mixed>> $resultados */
    private function sumarResultados(array $resultados, string $tipo): int
    {
        $total = 0;
        foreach ($resultados as $item) {
            if ((string) ($item['tipo_resultado'] ?? '') === $tipo) {
                $total += (int) ($item['cantidad'] ?? 0);
            }
        }
        return $total;
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

    private function toNullableTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $time = trim((string) $value);
        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time) !== 1) {
            throw new InvalidArgumentException('La hora debe usar formato HH:MM o HH:MM:SS.');
        }
        return strlen($time) === 5 ? $time . ':00' : $time;
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

    private function toRequiredInt(mixed $value, string $message): int
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            throw new InvalidArgumentException($message);
        }
        return (int) $value;
    }

    private function toIntNoNegativo(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Uno de los valores numericos no es valido.');
        }
        return max(0, (int) $value);
    }

    private function toOptionalDia(mixed $value): ?int
    {
        $dia = $this->toOptionalInt($value);
        if ($dia === null) {
            return null;
        }
        if ($dia < 1 || $dia > 7) {
            throw new InvalidArgumentException('El dia de reunion debe ir de 1 a 7.');
        }
        return $dia;
    }

    private function toBool(mixed $value): bool
    {
        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $parsed === null ? false : (bool) $parsed;
    }
}
