<?php
declare(strict_types=1);

/**
 * Logica de negocio del modulo de campanas.
 */
final class CampanaService
{
    private CampanaDAO $campanaDAO;
    private ContactoMisioneroService $contactoService;
    private EstudioBiblicoService $estudioService;
    private AuditoriaService $auditoriaService;

    public function __construct()
    {
        $this->campanaDAO = new CampanaDAO();
        $this->contactoService = new ContactoMisioneroService();
        $this->estudioService = new EstudioBiblicoService();
        $this->auditoriaService = new AuditoriaService();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function listar(array $filters): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        return $this->campanaDAO->findAllAsArray($this->normalizarFiltros($filters), $organizacionId);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function dashboard(array $filters): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        return $this->campanaDAO->getDashboard($this->normalizarFiltros($filters), $organizacionId);
    }

    /**
     * @return array<string, mixed>
     */
    public function obtener(int $id): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $dto = $this->campanaDAO->findById($id, $organizacionId);
        if ($dto === null) {
            throw new OutOfBoundsException('Campana no encontrada.');
        }

        $base = CampanaMapper::toArray($dto);
        $base['sesiones'] = $this->campanaDAO->listSessions($id, $organizacionId);
        $base['asistentes'] = $this->campanaDAO->listAttendees($id, $organizacionId);
        $base['decisiones'] = $this->campanaDAO->listDecisions($id, $organizacionId);
        $base['resumen'] = [
            'total_sesiones' => count($base['sesiones']),
            'total_asistentes' => count($base['asistentes']),
            'total_decisiones' => count($base['decisiones']),
            'total_visitas' => count(array_filter($base['asistentes'], static fn(array $item): bool => in_array((string) ($item['tipo_asistente'] ?? ''), ['VISITA', 'INTERESADO'], true))),
            'total_miembros' => count(array_filter($base['asistentes'], static fn(array $item): bool => (string) ($item['tipo_asistente'] ?? '') === 'MIEMBRO'))
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
        $payload = $this->normalizarCampana($data, $organizacionId, $usuarioId);
        $id = $this->campanaDAO->insert($payload);
        $dto = $this->campanaDAO->findById($id, $organizacionId);
        if ($dto === null) {
            throw new RuntimeException('No fue posible recuperar la campana creada.');
        }

        $item = CampanaMapper::toArray($dto);
        $this->auditoriaService->registrar('CAMPANAS', 'CAMPANA', $id, 'CREAR', 'Campana creada.', $organizacionId, $usuarioId, $actorNombre, null, $item);
        return $item;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function actualizar(int $id, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $existente = $this->campanaDAO->findById($id, $organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('Campana no encontrada.');
        }

        $payload = $this->normalizarCampana($data, $organizacionId, $usuarioId, $existente);
        $antes = CampanaMapper::toArray($existente);
        $this->campanaDAO->update($id, $payload, $organizacionId);
        $dto = $this->campanaDAO->findById($id, $organizacionId);
        if ($dto === null) {
            throw new RuntimeException('No fue posible recuperar la campana actualizada.');
        }

        $item = CampanaMapper::toArray($dto);
        $this->auditoriaService->registrar('CAMPANAS', 'CAMPANA', $id, 'ACTUALIZAR', 'Campana actualizada.', $organizacionId, $usuarioId, $actorNombre, $antes, $item);
        return $item;
    }

    public function eliminar(int $id, int $usuarioId, string $actorNombre): void
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $existente = $this->campanaDAO->findById($id, $organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('Campana no encontrada.');
        }

        $this->campanaDAO->softDelete($id, $organizacionId, $usuarioId);
        $this->auditoriaService->registrar('CAMPANAS', 'CAMPANA', $id, 'ARCHIVAR', 'Campana archivada.', $organizacionId, $usuarioId, $actorNombre, CampanaMapper::toArray($existente), null);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function crearSesion(int $campanaId, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $campana = $this->campanaDAO->findById($campanaId, $organizacionId);
        if ($campana === null) {
            throw new OutOfBoundsException('Campana no encontrada.');
        }

        $payload = $this->normalizarSesion($data, $organizacionId, $campanaId, $usuarioId);
        $id = $this->campanaDAO->insertSession($payload);
        $sesion = $this->campanaDAO->findSessionById($id, $organizacionId);
        if ($sesion === null) {
            throw new RuntimeException('No fue posible recuperar la sesion creada.');
        }

        $this->auditoriaService->registrar('CAMPANAS', 'CAMPANA_SESION', $id, 'CREAR', 'Sesion de campana creada.', $organizacionId, $usuarioId, $actorNombre, null, $sesion, ['campana_id' => $campanaId]);
        return $sesion;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function actualizarSesion(int $sesionId, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $existente = $this->campanaDAO->findSessionById($sesionId, $organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('Sesion no encontrada.');
        }

        $payload = $this->normalizarSesion($data, $organizacionId, (int) $existente['campana_id'], $usuarioId, $existente);
        $this->campanaDAO->updateSession($sesionId, $payload, $organizacionId);
        $sesion = $this->campanaDAO->findSessionById($sesionId, $organizacionId);
        if ($sesion === null) {
            throw new RuntimeException('No fue posible recuperar la sesion actualizada.');
        }

        $this->auditoriaService->registrar('CAMPANAS', 'CAMPANA_SESION', $sesionId, 'ACTUALIZAR', 'Sesion de campana actualizada.', $organizacionId, $usuarioId, $actorNombre, $existente, $sesion, ['campana_id' => (int) $existente['campana_id']]);
        return $sesion;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function crearAsistente(int $campanaId, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $campana = $this->campanaDAO->findById($campanaId, $organizacionId);
        if ($campana === null) {
            throw new OutOfBoundsException('Campana no encontrada.');
        }

        $contacto = $this->resolverContactoParaCampana($data, $usuarioId, $actorNombre);
        $payload = $this->normalizarAsistente($data, $organizacionId, $campanaId, $usuarioId, $contacto);
        $id = $this->campanaDAO->insertAttendee($payload);
        $asistente = $this->campanaDAO->findAttendeeById($id, $organizacionId);
        if ($asistente === null) {
            throw new RuntimeException('No fue posible recuperar el asistente creado.');
        }

        $this->auditoriaService->registrar('CAMPANAS', 'CAMPANA_ASISTENTE', $id, 'CREAR', 'Asistente de campana creado.', $organizacionId, $usuarioId, $actorNombre, null, $asistente, ['campana_id' => $campanaId]);
        return $asistente;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function actualizarAsistente(int $asistenteId, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $existente = $this->campanaDAO->findAttendeeById($asistenteId, $organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('Asistente no encontrado.');
        }

        $contacto = $this->resolverContactoParaCampana($data, $usuarioId, $actorNombre, isset($existente['contacto_id']) ? (int) $existente['contacto_id'] : null);
        $payload = $this->normalizarAsistente($data, $organizacionId, (int) $existente['campana_id'], $usuarioId, $contacto, $existente);
        $this->campanaDAO->updateAttendee($asistenteId, $payload, $organizacionId);
        $asistente = $this->campanaDAO->findAttendeeById($asistenteId, $organizacionId);
        if ($asistente === null) {
            throw new RuntimeException('No fue posible recuperar el asistente actualizado.');
        }

        $this->auditoriaService->registrar('CAMPANAS', 'CAMPANA_ASISTENTE', $asistenteId, 'ACTUALIZAR', 'Asistente de campana actualizado.', $organizacionId, $usuarioId, $actorNombre, $existente, $asistente, ['campana_id' => (int) $existente['campana_id']]);
        return $asistente;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function registrarAsistenciaSesion(int $sesionId, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $sesion = $this->campanaDAO->findSessionById($sesionId, $organizacionId);
        if ($sesion === null) {
            throw new OutOfBoundsException('Sesion no encontrada.');
        }

        $asistenteId = $this->toRequiredInt($data['campana_asistente_id'] ?? null, 'El asistente de campana es obligatorio.');
        $asistente = $this->campanaDAO->findAttendeeById($asistenteId, $organizacionId);
        if ($asistente === null || (int) $asistente['campana_id'] !== (int) $sesion['campana_id']) {
            throw new InvalidArgumentException('El asistente no pertenece a la campana de esa sesion.');
        }

        $payload = [
            'campana_id' => (int) $sesion['campana_id'],
            'campana_asistente_id' => $asistenteId,
            'asistio' => $this->toBool($data['asistio'] ?? true),
            'hora_llegada' => $this->toNullableTime($data['hora_llegada'] ?? null),
            'puntual' => $this->toBool($data['puntual'] ?? false),
            'elegible_premio' => $this->toBool($data['elegible_premio'] ?? false),
            'observaciones' => $this->toNullableText($data['observaciones'] ?? null, 400),
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId
        ];

        $item = $this->campanaDAO->upsertSessionAttendance($sesionId, $organizacionId, $payload);
        if ($item === null) {
            throw new RuntimeException('No fue posible registrar la asistencia de la sesion.');
        }

        $this->auditoriaService->registrar('CAMPANAS', 'CAMPANA_ASISTENCIA_SESION', isset($item['id']) ? (int) $item['id'] : null, 'ACTUALIZAR', 'Asistencia por noche registrada.', $organizacionId, $usuarioId, $actorNombre, null, $item, ['campana_id' => (int) $sesion['campana_id'], 'sesion_id' => $sesionId]);
        return $item;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function crearDecision(int $campanaId, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $campana = $this->campanaDAO->findById($campanaId, $organizacionId);
        if ($campana === null) {
            throw new OutOfBoundsException('Campana no encontrada.');
        }

        $asistenteId = $this->toRequiredInt($data['campana_asistente_id'] ?? null, 'Debe seleccionar un asistente.');
        $asistente = $this->campanaDAO->findAttendeeById($asistenteId, $organizacionId);
        if ($asistente === null || (int) $asistente['campana_id'] !== $campanaId) {
            throw new InvalidArgumentException('El asistente no pertenece a la campana seleccionada.');
        }

        $decisionClave = strtoupper($this->toRequiredText($data['decision_clave'] ?? null, 60, 'La decision es obligatoria.'));
        $decisionEtiqueta = $this->toRequiredText($data['decision_etiqueta'] ?? null, 180, 'La etiqueta de la decision es obligatoria.');
        $fechaDecision = $this->toRequiredDateTime($data['fecha_decision'] ?? null, 'La fecha de decision es obligatoria.');

        $payload = [
            'organizacion_id' => $organizacionId,
            'campana_id' => $campanaId,
            'campana_asistente_id' => $asistenteId,
            'decision_clave' => $decisionClave,
            'decision_etiqueta' => $decisionEtiqueta,
            'fecha_decision' => $fechaDecision,
            'observaciones' => $this->toNullableText($data['observaciones'] ?? null, 600),
            'estudio_biblico_id' => $this->toOptionalInt($data['estudio_biblico_id'] ?? null),
            'seguimiento_tarea_id' => $this->toOptionalInt($data['seguimiento_tarea_id'] ?? null),
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId
        ];

        $id = $this->campanaDAO->insertDecision($payload);
        $decision = null;
        foreach ($this->campanaDAO->listDecisions($campanaId, $organizacionId) as $item) {
            if ((int) ($item['id'] ?? 0) === $id) {
                $decision = $item;
                break;
            }
        }
        if ($decision === null) {
            throw new RuntimeException('No fue posible recuperar la decision creada.');
        }

        $this->auditoriaService->registrar('CAMPANAS', 'CAMPANA_DECISION', $id, 'CREAR', 'Decision de campana registrada.', $organizacionId, $usuarioId, $actorNombre, null, $decision, ['campana_id' => $campanaId]);
        return $decision;
    }

    /**
     * @return array<string, mixed>
     */
    public function convertirAsistenteAEstudio(int $asistenteId, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $asistente = $this->campanaDAO->findAttendeeById($asistenteId, $organizacionId);
        if ($asistente === null) {
            throw new OutOfBoundsException('Asistente no encontrado.');
        }
        if (strtoupper((string) ($asistente['tipo_asistente'] ?? '')) === 'MIEMBRO') {
            throw new InvalidArgumentException('Solo las visitas o interesados pueden convertirse en estudio biblico.');
        }

        $campana = $this->campanaDAO->findById((int) $asistente['campana_id'], $organizacionId);
        if ($campana === null) {
            throw new OutOfBoundsException('Campana no encontrada.');
        }

        $contacto = $this->contactoService->obtener((int) $asistente['contacto_id'], $organizacionId);
        $pdo = Conexion::getConexion();
        $iniciadaAqui = false;

        try {
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $iniciadaAqui = true;
            }

            $estudio = $this->estudioService->crear([
                'persona_nombre' => $contacto['nombre_completo'] ?? ($asistente['nombre_snapshot'] ?? ''),
                'telefono' => $contacto['telefono'] ?? ($asistente['telefono_snapshot'] ?? null),
                'correo' => $contacto['correo'] ?? null,
                'direccion' => $contacto['direccion'] ?? null,
                'barrio_comunidad' => $contacto['barrio_comunidad'] ?? ($asistente['procedencia'] ?? null),
                'origen_clave' => 'CAMPANA',
                'campana_origen_id' => (int) $asistente['campana_id'],
                'pc_origen_id' => null,
                'responsable_usuario_id' => $campana->responsableUsuarioId ?? $usuarioId,
                'modalidad' => 'HOGAR',
                'material_estudio' => null,
                'leccion_actual' => null,
                'total_lecciones_completadas' => 0,
                'fecha_inicio' => date('Y-m-d'),
                'fecha_ultima_sesion' => null,
                'proxima_sesion' => null,
                'estado_general' => 'ASIGNADO',
                'observaciones' => 'Estudio biblico generado desde la campana ' . $campana->nombre . '.',
                'motivo_cierre_pausa' => null
            ], $usuarioId, $actorNombre);

            $this->campanaDAO->updateAttendeeFollowupStatus($asistenteId, $organizacionId, 'ESTUDIO_BIBLICO', $usuarioId);
            $asistenteActualizado = $this->campanaDAO->findAttendeeById($asistenteId, $organizacionId) ?? $asistente;

            $decisionId = $this->campanaDAO->insertDecision([
                'organizacion_id' => $organizacionId,
                'campana_id' => (int) $asistente['campana_id'],
                'campana_asistente_id' => $asistenteId,
                'decision_clave' => 'ACEPTO_ESTUDIO_BIBLICO',
                'decision_etiqueta' => 'Acepto estudio biblico',
                'fecha_decision' => date('Y-m-d H:i:s'),
                'observaciones' => 'Conversion directa a estudio biblico desde la recepcion de campana.',
                'estudio_biblico_id' => (int) ($estudio['id'] ?? 0),
                'seguimiento_tarea_id' => null,
                'creado_por' => $usuarioId,
                'actualizado_por' => $usuarioId
            ]);
            $decision = $this->buscarPorIdEnLista($this->campanaDAO->listDecisions((int) $asistente['campana_id'], $organizacionId), $decisionId);

            $this->auditoriaService->registrar(
                'CAMPANAS',
                'CAMPANA_ASISTENTE',
                $asistenteId,
                'CONVERTIR_ESTUDIO',
                'Asistente convertido a estudio biblico.',
                $organizacionId,
                $usuarioId,
                $actorNombre,
                $asistente,
                $asistenteActualizado,
                ['campana_id' => (int) $asistente['campana_id'], 'estudio_biblico_id' => (int) ($estudio['id'] ?? 0)]
            );
            $this->auditoriaService->registrar(
                'CAMPANAS',
                'CAMPANA_DECISION',
                $decisionId,
                'CREAR',
                'Decision automatica de aceptacion de estudio biblico registrada.',
                $organizacionId,
                $usuarioId,
                $actorNombre,
                null,
                $decision,
                ['campana_id' => (int) $asistente['campana_id'], 'estudio_biblico_id' => (int) ($estudio['id'] ?? 0)]
            );

            if ($iniciadaAqui) {
                $pdo->commit();
            }

            return [
                'estudio' => $estudio,
                'decision' => $decision,
                'asistente' => $asistenteActualizado
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
            'responsable_usuario_id' => $this->toOptionalInt($filters['responsable_usuario_id'] ?? null),
            'fecha_desde' => $this->toNullableDate($filters['fecha_desde'] ?? null),
            'fecha_hasta' => $this->toNullableDate($filters['fecha_hasta'] ?? null)
        ];
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function normalizarCampana(array $data, int $organizacionId, int $usuarioId, ?CampanaDTO $base = null): array
    {
        $nombre = $this->toRequiredText($data['nombre'] ?? ($base?->nombre ?? null), 160, 'El nombre de la campana es obligatorio.');
        $tipo = strtoupper($this->toRequiredText($data['tipo'] ?? ($base?->tipo ?? null), 30, 'El tipo de campana es obligatorio.'));
        $tiposValidos = ['SEMANA_EVANGELISTICA', 'CAMPANA_2_SEMANAS', 'CAMPANA_ESPECIAL', 'SERIE_CORTA'];
        if (!in_array($tipo, $tiposValidos, true)) {
            throw new InvalidArgumentException('El tipo de campana no es valido.');
        }

        $estado = strtoupper($this->toRequiredText($data['estado'] ?? ($base?->estado ?? null), 20, 'El estado de la campana es obligatorio.'));
        $estadosValidos = ['BORRADOR', 'ACTIVA', 'FINALIZADA', 'ARCHIVADA'];
        if (!in_array($estado, $estadosValidos, true)) {
            throw new InvalidArgumentException('El estado de la campana no es valido.');
        }

        $fechaInicio = $this->toRequiredDate($data['fecha_inicio'] ?? ($base?->fechaInicio ?? null), 'La fecha de inicio es obligatoria.');
        $fechaFin = $this->toRequiredDate($data['fecha_fin'] ?? ($base?->fechaFin ?? null), 'La fecha de fin es obligatoria.');
        if ($fechaFin < $fechaInicio) {
            throw new InvalidArgumentException('La fecha final no puede ser menor a la fecha inicial.');
        }

        return [
            'organizacion_id' => $organizacionId,
            'nombre' => $nombre,
            'lema' => $this->toNullableText($data['lema'] ?? ($base?->lema ?? null), 180),
            'tipo' => $tipo,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'lugar' => $this->toNullableText($data['lugar'] ?? ($base?->lugar ?? null), 180),
            'predicador' => $this->toNullableText($data['predicador'] ?? ($base?->predicador ?? null), 160),
            'responsable_usuario_id' => $this->toOptionalInt($data['responsable_usuario_id'] ?? ($base?->responsableUsuarioId ?? null)),
            'descripcion' => $this->toNullableText($data['descripcion'] ?? ($base?->descripcion ?? null), 1200),
            'estado' => $estado,
            'observaciones' => $this->toNullableText($data['observaciones'] ?? ($base?->observaciones ?? null), 1200),
            'creado_por' => $base?->creadoPor ?? $usuarioId,
            'actualizado_por' => $usuarioId
        ];
    }

    /** @param array<string, mixed> $data @param array<string, mixed>|null $base @return array<string, mixed> */
    private function normalizarSesion(array $data, int $organizacionId, int $campanaId, int $usuarioId, ?array $base = null): array
    {
        $estado = strtoupper($this->toRequiredText($data['estado_sesion'] ?? ($base['estado_sesion'] ?? null), 20, 'El estado de la sesion es obligatorio.'));
        if (!in_array($estado, ['PROGRAMADA', 'REALIZADA', 'CANCELADA'], true)) {
            throw new InvalidArgumentException('El estado de la sesion no es valido.');
        }

        return [
            'organizacion_id' => $organizacionId,
            'campana_id' => $campanaId,
            'fecha' => $this->toRequiredDate($data['fecha'] ?? ($base['fecha'] ?? null), 'La fecha de la sesion es obligatoria.'),
            'hora_inicio' => $this->toNullableTime($data['hora_inicio'] ?? ($base['hora_inicio'] ?? null)),
            'tema_titulo' => $this->toRequiredText($data['tema_titulo'] ?? ($base['tema_titulo'] ?? null), 180, 'El tema o titulo es obligatorio.'),
            'predicador_noche' => $this->toNullableText($data['predicador_noche'] ?? ($base['predicador_noche'] ?? null), 160),
            'observaciones' => $this->toNullableText($data['observaciones'] ?? ($base['observaciones'] ?? null), 800),
            'estado_sesion' => $estado,
            'creado_por' => isset($base['creado_por']) ? (int) $base['creado_por'] : $usuarioId,
            'actualizado_por' => $usuarioId
        ];
    }

    /** @param array<string, mixed> $data @param array<string, mixed> $contacto @param array<string, mixed>|null $base @return array<string, mixed> */
    private function normalizarAsistente(array $data, int $organizacionId, int $campanaId, int $usuarioId, array $contacto, ?array $base = null): array
    {
        $tipo = strtoupper($this->toRequiredText($data['tipo_asistente'] ?? ($base['tipo_asistente'] ?? null), 20, 'El tipo de asistente es obligatorio.'));
        if (!in_array($tipo, ['MIEMBRO', 'VISITA', 'INTERESADO'], true)) {
            throw new InvalidArgumentException('El tipo de asistente no es valido.');
        }

        $estadoSeguimiento = strtoupper($this->toRequiredText($data['estado_seguimiento'] ?? ($base['estado_seguimiento'] ?? null), 25, 'El estado de seguimiento es obligatorio.'));
        if (!in_array($estadoSeguimiento, ['PENDIENTE', 'CONTACTADO', 'ESTUDIO_BIBLICO', 'NO_LOCALIZABLE', 'CERRADO'], true)) {
            throw new InvalidArgumentException('El estado de seguimiento no es valido.');
        }

        return [
            'organizacion_id' => $organizacionId,
            'campana_id' => $campanaId,
            'contacto_id' => (int) $contacto['id'],
            'nombre_snapshot' => (string) $contacto['nombre_completo'],
            'telefono_snapshot' => $contacto['telefono'] ?? null,
            'procedencia' => $this->toNullableText($data['procedencia'] ?? ($base['procedencia'] ?? null), 120),
            'tipo_asistente' => $tipo,
            'clasificacion_etaria' => $this->toNullableText($data['clasificacion_etaria'] ?? ($base['clasificacion_etaria'] ?? null), 20, true),
            'invitado_por_contacto_id' => $this->toOptionalInt($data['invitado_por_contacto_id'] ?? ($base['invitado_por_contacto_id'] ?? null)),
            'primera_vez' => $this->toBool($data['primera_vez'] ?? ($base['primera_vez'] ?? true)),
            'observaciones' => $this->toNullableText($data['observaciones'] ?? ($base['observaciones'] ?? null), 600),
            'estado_seguimiento' => $estadoSeguimiento,
            'creado_por' => isset($base['creado_por']) ? (int) $base['creado_por'] : $usuarioId,
            'actualizado_por' => $usuarioId
        ];
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function resolverContactoParaCampana(array $data, int $usuarioId, string $actorNombre, ?int $contactoExistenteId = null): array
    {
        $contactoPayload = [
            'nombre_completo' => $data['nombre_completo'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'correo' => $data['correo'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'barrio_comunidad' => $data['barrio_comunidad'] ?? null,
            'clasificacion_principal' => $this->resolverClasificacionContacto($data['tipo_asistente'] ?? null),
            'es_miembro' => strtoupper((string) ($data['tipo_asistente'] ?? '')) === 'MIEMBRO',
            'estado_contacto' => strtoupper((string) ($data['estado_seguimiento'] ?? 'PENDIENTE')) === 'NO_LOCALIZABLE' ? 'NO_LOCALIZABLE' : 'ACTIVO',
            'origen_principal_clave' => 'CAMPANA',
            'modulo_origen' => 'CAMPANAS',
            'observaciones_generales' => $data['observaciones'] ?? null,
            'fecha_primer_contacto' => date('Y-m-d'),
            'fecha_ultimo_contacto' => date('Y-m-d')
        ];

        if ($contactoExistenteId !== null) {
            return $this->contactoService->actualizar(
                $contactoExistenteId,
                $contactoPayload,
                AuthContext::getOrganizacionId(),
                $usuarioId,
                $actorNombre
            );
        }

        return $this->contactoService->resolverOCrear(
            $contactoPayload,
            AuthContext::getOrganizacionId(),
            $usuarioId,
            $actorNombre
        );
    }

    private function resolverClasificacionContacto(mixed $tipoAsistente): string
    {
        return match (strtoupper(trim((string) $tipoAsistente))) {
            'MIEMBRO' => 'MIEMBRO',
            'VISITA' => 'VISITA',
            default => 'INTERESADO'
        };
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
        if ($value === null || $value === '') {
            throw new InvalidArgumentException($message);
        }
        $dt = trim((string) $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?$/', $dt) !== 1) {
            throw new InvalidArgumentException('La fecha y hora debe usar formato YYYY-MM-DD HH:MM[:SS].');
        }
        return str_replace('T', ' ', $dt);
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
        if ($value === null || $value === '') {
            throw new InvalidArgumentException($message);
        }
        if (!is_numeric($value)) {
            throw new InvalidArgumentException($message);
        }
        return (int) $value;
    }

    private function toBool(mixed $value): bool
    {
        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $parsed === null ? false : (bool) $parsed;
    }
}
