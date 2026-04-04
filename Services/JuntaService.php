<?php
declare(strict_types=1);

/**
 * Logica de negocio de juntas de iglesia.
 */
final class JuntaService
{
    private JuntaDAO $juntaDAO;
    private AuditoriaService $auditoriaService;

    public function __construct()
    {
        $this->juntaDAO = new JuntaDAO();
        $this->auditoriaService = new AuditoriaService();
    }

    /** @param array<string, mixed> $filters @return array<int, array<string, mixed>> */
    public function listar(array $filters): array
    {
        return $this->juntaDAO->findAllAsArray($this->normalizarFiltros($filters), AuthContext::getOrganizacionId());
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function dashboard(array $filters): array
    {
        return $this->juntaDAO->getDashboard($this->normalizarFiltros($filters), AuthContext::getOrganizacionId());
    }

    /** @param array<string, mixed> $filters @return array<int, array<string, mixed>> */
    public function pendientes(array $filters): array
    {
        return $this->juntaDAO->listPendingPoints($this->normalizarFiltros($filters), AuthContext::getOrganizacionId());
    }

    /** @return array<string, mixed> */
    public function obtener(int $id): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $dto = $this->juntaDAO->findById($id, $organizacionId);
        if ($dto === null) {
            throw new OutOfBoundsException('Junta no encontrada.');
        }

        $base = JuntaMapper::toArray($dto);
        $puntos = $this->juntaDAO->listAgendaItems($id, $organizacionId);
        $votaciones = $this->juntaDAO->listVotesByJunta($id, $organizacionId);
        $votosPorPunto = [];
        foreach ($votaciones as $voto) {
            $votosPorPunto[(int) $voto['punto_agenda_id']][] = $voto;
        }
        foreach ($puntos as &$punto) {
            $punto['votaciones'] = $votosPorPunto[(int) $punto['id']] ?? [];
        }
        unset($punto);

        $base['puntos'] = $puntos;
        $base['votaciones'] = $votaciones;
        $base['pendientes_relacionados'] = $this->juntaDAO->listPendingPoints(
            ['excluir_junta_id' => $id],
            $organizacionId
        );
        $base['resumen'] = $this->construirResumen($puntos);
        $base['timeline'] = $this->construirTimeline($base, $puntos, $votaciones);
        $base['acta'] = $this->construirActa($base, $puntos);

        return $base;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function crear(array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $payload = $this->normalizarJunta($data, $organizacionId, $usuarioId);
        $id = $this->juntaDAO->insert($payload);
        $item = $this->obtener($id);
        $this->auditoriaService->registrar('JUNTAS', 'JUNTA', $id, 'CREAR', 'Junta creada.', $organizacionId, $usuarioId, $actorNombre, null, $item);
        return $item;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function actualizar(int $id, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $existente = $this->juntaDAO->findById($id, $organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('Junta no encontrada.');
        }

        $antes = JuntaMapper::toArray($existente);
        $payload = $this->normalizarJunta($data, $organizacionId, $usuarioId, $existente);
        $this->juntaDAO->update($id, $payload, $organizacionId);
        $item = $this->obtener($id);
        $this->auditoriaService->registrar('JUNTAS', 'JUNTA', $id, 'ACTUALIZAR', 'Junta actualizada.', $organizacionId, $usuarioId, $actorNombre, $antes, $item);
        return $item;
    }

    public function eliminar(int $id, int $usuarioId, string $actorNombre): void
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $existente = $this->juntaDAO->findById($id, $organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('Junta no encontrada.');
        }
        $this->juntaDAO->softDelete($id, $organizacionId, $usuarioId);
        $this->auditoriaService->registrar('JUNTAS', 'JUNTA', $id, 'ARCHIVAR', 'Junta archivada.', $organizacionId, $usuarioId, $actorNombre, JuntaMapper::toArray($existente), null);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function crearPunto(int $juntaId, array $data, int $usuarioId, string $actorNombre): array
    {
        $this->obtenerJuntaDto($juntaId);
        $payload = $this->normalizarPunto($data, $juntaId, AuthContext::getOrganizacionId(), $usuarioId);
        $this->assertNumeroOrdenDisponible($juntaId, (int) $payload['numero_orden']);
        $id = $this->juntaDAO->insertPoint($payload);
        $item = $this->buscarPorIdEnLista($this->juntaDAO->listAgendaItems($juntaId, AuthContext::getOrganizacionId()), $id);
        $this->auditoriaService->registrar('JUNTAS', 'JUNTA_PUNTO', $id, 'CREAR', 'Punto de agenda creado.', AuthContext::getOrganizacionId(), $usuarioId, $actorNombre, null, $item, ['junta_id' => $juntaId]);
        return $this->obtener($juntaId);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function actualizarPunto(int $puntoId, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $existente = $this->juntaDAO->findPointById($puntoId, $organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('Punto de agenda no encontrado.');
        }

        $payload = $this->normalizarPunto($data, (int) $existente['junta_id'], $organizacionId, $usuarioId, $existente);
        $this->assertNumeroOrdenDisponible((int) $existente['junta_id'], (int) $payload['numero_orden'], $puntoId);
        $this->juntaDAO->updatePoint($puntoId, $payload, $organizacionId);
        $item = $this->buscarPorIdEnLista($this->juntaDAO->listAgendaItems((int) $existente['junta_id'], $organizacionId), $puntoId);
        $this->auditoriaService->registrar('JUNTAS', 'JUNTA_PUNTO', $puntoId, 'ACTUALIZAR', 'Punto de agenda actualizado.', $organizacionId, $usuarioId, $actorNombre, $existente, $item, ['junta_id' => (int) $existente['junta_id']]);
        return $this->obtener((int) $existente['junta_id']);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function crearVotacion(int $puntoId, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $punto = $this->juntaDAO->findPointById($puntoId, $organizacionId);
        if ($punto === null) {
            throw new OutOfBoundsException('Punto de agenda no encontrado.');
        }

        $payload = $this->normalizarVotacion($data, $puntoId, $organizacionId, $usuarioId);
        $id = $this->juntaDAO->insertVote($payload);
        $this->sincronizarPuntoDespuesDeVotacion($punto, $data, $payload, $usuarioId);
        $item = $this->buscarPorIdEnLista($this->juntaDAO->listVotesByJunta((int) $punto['junta_id'], $organizacionId), $id);
        $this->auditoriaService->registrar('JUNTAS', 'JUNTA_VOTACION', $id, 'CREAR', 'Votacion registrada.', $organizacionId, $usuarioId, $actorNombre, null, $item, ['junta_id' => (int) $punto['junta_id'], 'punto_id' => $puntoId]);
        return $this->obtener((int) $punto['junta_id']);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function actualizarVotacion(int $votacionId, array $data, int $usuarioId, string $actorNombre): array
    {
        $organizacionId = AuthContext::getOrganizacionId();
        $existente = $this->juntaDAO->findVoteById($votacionId, $organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('Votacion no encontrada.');
        }
        $punto = $this->juntaDAO->findPointById((int) $existente['punto_agenda_id'], $organizacionId);
        if ($punto === null) {
            throw new OutOfBoundsException('Punto de agenda no encontrado.');
        }

        $payload = $this->normalizarVotacion($data, (int) $existente['punto_agenda_id'], $organizacionId, $usuarioId, $existente);
        $this->juntaDAO->updateVote($votacionId, $payload, $organizacionId);
        $this->sincronizarPuntoDespuesDeVotacion($punto, $data, $payload, $usuarioId);
        $item = $this->buscarPorIdEnLista($this->juntaDAO->listVotesByJunta((int) $punto['junta_id'], $organizacionId), $votacionId);
        $this->auditoriaService->registrar('JUNTAS', 'JUNTA_VOTACION', $votacionId, 'ACTUALIZAR', 'Votacion actualizada.', $organizacionId, $usuarioId, $actorNombre, $existente, $item, ['junta_id' => (int) $punto['junta_id'], 'punto_id' => (int) $punto['id']]);
        return $this->obtener((int) $punto['junta_id']);
    }

    private function obtenerJuntaDto(int $id): JuntaDTO
    {
        $dto = $this->juntaDAO->findById($id, AuthContext::getOrganizacionId());
        if ($dto === null) {
            throw new OutOfBoundsException('Junta no encontrada.');
        }
        return $dto;
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function normalizarFiltros(array $filters): array
    {
        return [
            'q' => $this->toNullableText($filters['q'] ?? null, 120, true) ?? '',
            'estado' => $this->toEnum($filters['estado'] ?? null, ['BORRADOR', 'EN_PROCESO', 'CERRADA', 'APROBADA', 'ARCHIVADA'], true),
            'tipo' => $this->toEnum($filters['tipo'] ?? null, ['ORDINARIA', 'EXTRAORDINARIA', 'SEGUIMIENTO', 'WHATSAPP', 'CONTINUACION'], true),
            'departamento_origen' => $this->toNullableText($filters['departamento_origen'] ?? null, 120, true) ?? '',
            'responsable_usuario_id' => $this->toNullableInt($filters['responsable_usuario_id'] ?? null),
            'fecha_desde' => $this->toNullableDate($filters['fecha_desde'] ?? null),
            'fecha_hasta' => $this->toNullableDate($filters['fecha_hasta'] ?? null),
            'excluir_junta_id' => $this->toNullableInt($filters['excluir_junta_id'] ?? null)
        ];
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function normalizarJunta(array $data, int $organizacionId, int $usuarioId, ?JuntaDTO $existente = null): array
    {
        $fecha = $this->toRequiredDate($data['fecha'] ?? null, 'La fecha de la junta es obligatoria.');
        $horaInicio = $this->toNullableTime($data['hora_inicio'] ?? null);
        $horaFin = $this->toNullableTime($data['hora_fin'] ?? null);
        if ($horaInicio !== null && $horaFin !== null && strcmp($horaFin, $horaInicio) < 0) {
            throw new InvalidArgumentException('La hora final no puede ser menor que la hora inicial.');
        }

        $juntaAnteriorId = $this->toNullableInt($data['junta_anterior_id'] ?? null);
        if ($juntaAnteriorId !== null) {
            $anterior = $this->juntaDAO->findById($juntaAnteriorId, $organizacionId);
            if ($anterior === null) {
                throw new InvalidArgumentException('La junta anterior indicada no existe.');
            }
            if ($existente !== null && $juntaAnteriorId === $existente->id) {
                throw new InvalidArgumentException('Una junta no puede apuntarse a si misma como junta anterior.');
            }
        }

        return [
            'organizacion_id' => $organizacionId,
            'fecha' => $fecha,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'tipo' => $this->toEnum($data['tipo'] ?? null, ['ORDINARIA', 'EXTRAORDINARIA', 'SEGUIMIENTO', 'WHATSAPP', 'CONTINUACION'], false, 'Debe indicar un tipo valido de junta.'),
            'moderador' => $this->toNullableText($data['moderador'] ?? null, 160),
            'secretario' => $this->toNullableText($data['secretario'] ?? null, 160),
            'estado' => $this->toEnum($data['estado'] ?? null, ['BORRADOR', 'EN_PROCESO', 'CERRADA', 'APROBADA', 'ARCHIVADA'], false, 'Debe indicar un estado valido de junta.'),
            'observaciones_generales' => $this->toNullableText($data['observaciones_generales'] ?? null, 5000),
            'resumen_general' => $this->toNullableText($data['resumen_general'] ?? null, 5000),
            'quorum_texto' => $this->toNullableText($data['quorum_texto'] ?? null, 180),
            'junta_anterior_id' => $juntaAnteriorId,
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId
        ];
    }

    /** @param array<string, mixed> $data @param array<string, mixed>|null $existente @return array<string, mixed> */
    private function normalizarPunto(array $data, int $juntaId, int $organizacionId, int $usuarioId, ?array $existente = null): array
    {
        $numeroOrden = $this->toNullableInt($data['numero_orden'] ?? null) ?? $this->juntaDAO->getNextOrder($juntaId, $organizacionId);
        if ($numeroOrden <= 0) {
            throw new InvalidArgumentException('El numero de orden debe ser mayor que cero.');
        }

        $puntoAnteriorId = $this->toNullableInt($data['punto_anterior_id'] ?? null);
        if ($puntoAnteriorId !== null) {
            $puntoAnterior = $this->juntaDAO->findPointById($puntoAnteriorId, $organizacionId);
            if ($puntoAnterior === null) {
                throw new InvalidArgumentException('El punto anterior indicado no existe.');
            }
            if ($existente !== null && $puntoAnteriorId === (int) $existente['id']) {
                throw new InvalidArgumentException('Un punto no puede enlazarse a si mismo como antecedente.');
            }
        }

        return [
            'organizacion_id' => $organizacionId,
            'junta_id' => $juntaId,
            'numero_orden' => $numeroOrden,
            'titulo' => $this->toRequiredText($data['titulo'] ?? null, 180, 'Debe indicar el titulo del punto.'),
            'departamento_origen' => $this->toNullableText($data['departamento_origen'] ?? null, 120),
            'presentado_por' => $this->toNullableText($data['presentado_por'] ?? null, 160),
            'tipo_punto' => $this->toEnum($data['tipo_punto'] ?? null, ['INFORMATIVO', 'VOTACION', 'SEGUIMIENTO', 'PENDIENTE_ANTERIOR', 'APROBADO_WHATSAPP', 'NUEVO'], false, 'Debe indicar un tipo valido de punto.'),
            'descripcion_base' => $this->toNullableText($data['descripcion_base'] ?? null, 5000),
            'observacion_secretaria' => $this->toNullableText($data['observacion_secretaria'] ?? null, 5000),
            'discusion_resumen' => $this->toNullableText($data['discusion_resumen'] ?? null, 5000),
            'decision_final' => $this->toNullableText($data['decision_final'] ?? null, 5000),
            'estado' => $this->toEnum($data['estado'] ?? null, ['PENDIENTE', 'DISCUTIDO', 'VOTADO', 'APROBADO', 'RECHAZADO', 'POSPUESTO', 'TRASLADADO', 'EJECUTADO', 'RESUELTO_WHATSAPP'], false, 'Debe indicar un estado valido para el punto.'),
            'prioridad' => $this->toEnum($data['prioridad'] ?? null, ['BAJA', 'MEDIA', 'ALTA'], false, 'Debe indicar una prioridad valida.'),
            'confidencial' => $this->toBool($data['confidencial'] ?? false),
            'responsable_seguimiento_usuario_id' => $this->toNullableInt($data['responsable_seguimiento_usuario_id'] ?? null),
            'fecha_limite' => $this->toNullableDate($data['fecha_limite'] ?? null),
            'punto_anterior_id' => $puntoAnteriorId,
            'pasar_proxima_junta' => $this->toBool($data['pasar_proxima_junta'] ?? false),
            'referencia_modulo' => $this->toEnum($data['referencia_modulo'] ?? null, ['CAMPANAS', 'ESTUDIOS_BIBLICOS', 'PC', 'ASISTENCIA', 'OTRO'], true),
            'referencia_entidad_id' => $this->toNullableInt($data['referencia_entidad_id'] ?? null),
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId
        ];
    }

    /** @param array<string, mixed> $data @param array<string, mixed>|null $existente @return array<string, mixed> */
    private function normalizarVotacion(array $data, int $puntoId, int $organizacionId, int $usuarioId, ?array $existente = null): array
    {
        return [
            'organizacion_id' => $organizacionId,
            'punto_agenda_id' => $puntoId,
            'requirio_voto' => $this->toBool($data['requirio_voto'] ?? true),
            'tipo_voto' => $this->toEnum($data['tipo_voto'] ?? null, ['UNANIME', 'MAYORIA', 'CONSENSO', 'SOLO_INFORMADO'], false, 'Debe indicar un tipo valido de votacion.'),
            'texto_voto' => $this->toNullableText($data['texto_voto'] ?? null, 5000),
            'votos_favor' => $this->toNullableInt($data['votos_favor'] ?? null),
            'votos_contra' => $this->toNullableInt($data['votos_contra'] ?? null),
            'abstenciones' => $this->toNullableInt($data['abstenciones'] ?? null),
            'fecha_voto' => $this->toNullableDateTime($data['fecha_voto'] ?? null),
            'observacion' => $this->toNullableText($data['observacion'] ?? null, 5000),
            'creado_por' => $usuarioId,
            'actualizado_por' => $usuarioId
        ];
    }

    /** @param array<string, mixed> $punto @param array<string, mixed> $data @param array<string, mixed> $payload */
    private function sincronizarPuntoDespuesDeVotacion(array $punto, array $data, array $payload, int $usuarioId): void
    {
        $estadoResultante = $this->toEnum($data['estado_resultante'] ?? null, ['PENDIENTE', 'DISCUTIDO', 'VOTADO', 'APROBADO', 'RECHAZADO', 'POSPUESTO', 'TRASLADADO', 'EJECUTADO', 'RESUELTO_WHATSAPP'], true);
        $nuevoEstado = $estadoResultante ?? ($payload['requirio_voto'] ? 'APROBADO' : 'DISCUTIDO');

        $actualizacion = [
            'organizacion_id' => (int) $punto['organizacion_id'],
            'junta_id' => (int) $punto['junta_id'],
            'numero_orden' => (int) $punto['numero_orden'],
            'titulo' => (string) $punto['titulo'],
            'departamento_origen' => $punto['departamento_origen'] ?? null,
            'presentado_por' => $punto['presentado_por'] ?? null,
            'tipo_punto' => (string) $punto['tipo_punto'],
            'descripcion_base' => $punto['descripcion_base'] ?? null,
            'observacion_secretaria' => $punto['observacion_secretaria'] ?? null,
            'discusion_resumen' => $punto['discusion_resumen'] ?? null,
            'decision_final' => $payload['texto_voto'] ?? ($punto['decision_final'] ?? null),
            'estado' => $nuevoEstado,
            'prioridad' => (string) $punto['prioridad'],
            'confidencial' => (int) $punto['confidencial'] === 1,
            'responsable_seguimiento_usuario_id' => isset($punto['responsable_seguimiento_usuario_id']) ? (int) $punto['responsable_seguimiento_usuario_id'] : null,
            'fecha_limite' => $punto['fecha_limite'] ?? null,
            'punto_anterior_id' => isset($punto['punto_anterior_id']) ? (int) $punto['punto_anterior_id'] : null,
            'pasar_proxima_junta' => (int) $punto['pasar_proxima_junta'] === 1,
            'referencia_modulo' => $punto['referencia_modulo'] ?? null,
            'referencia_entidad_id' => isset($punto['referencia_entidad_id']) ? (int) $punto['referencia_entidad_id'] : null,
            'actualizado_por' => $usuarioId
        ];
        $this->juntaDAO->updatePoint((int) $punto['id'], $actualizacion, (int) $punto['organizacion_id']);
    }

    private function assertNumeroOrdenDisponible(int $juntaId, int $numeroOrden, ?int $omitId = null): void
    {
        $puntos = $this->juntaDAO->listAgendaItems($juntaId, AuthContext::getOrganizacionId());
        foreach ($puntos as $punto) {
            if ((int) $punto['numero_orden'] === $numeroOrden && (int) $punto['id'] !== (int) $omitId) {
                throw new InvalidArgumentException('El numero de orden ya esta usado dentro de esa junta.');
            }
        }
    }

    /** @param array<int, array<string, mixed>> $puntos @return array<string, int> */
    private function construirResumen(array $puntos): array
    {
        $resumen = [
            'total_puntos' => count($puntos),
            'pendientes' => 0,
            'aprobados' => 0,
            'rechazados' => 0,
            'trasladados' => 0,
            'resueltos_whatsapp' => 0,
            'vencidos' => 0
        ];

        foreach ($puntos as $punto) {
            $estado = (string) ($punto['estado'] ?? '');
            if (in_array($estado, ['PENDIENTE', 'DISCUTIDO', 'VOTADO', 'POSPUESTO', 'TRASLADADO'], true)) {
                $resumen['pendientes']++;
            }
            if ($estado === 'APROBADO') {
                $resumen['aprobados']++;
            }
            if ($estado === 'RECHAZADO') {
                $resumen['rechazados']++;
            }
            if ($estado === 'TRASLADADO') {
                $resumen['trasladados']++;
            }
            if ($estado === 'RESUELTO_WHATSAPP') {
                $resumen['resueltos_whatsapp']++;
            }
            if (!empty($punto['fecha_limite']) && $estado !== 'EJECUTADO' && $estado !== 'APROBADO' && $estado !== 'RECHAZADO' && $estado !== 'RESUELTO_WHATSAPP') {
                if (strtotime((string) $punto['fecha_limite']) < strtotime(date('Y-m-d'))) {
                    $resumen['vencidos']++;
                }
            }
        }

        return $resumen;
    }

    /** @param array<string, mixed> $junta @param array<int, array<string, mixed>> $puntos @param array<int, array<string, mixed>> $votaciones @return array<int, array<string, mixed>> */
    private function construirTimeline(array $junta, array $puntos, array $votaciones): array
    {
        $timeline = [];

        if (!empty($junta['creado_en'])) {
            $timeline[] = [
                'fecha' => $junta['creado_en'],
                'tipo' => 'JUNTA',
                'titulo' => 'Junta registrada',
                'detalle' => $junta['resumen_general'] ?: $junta['observaciones_generales'] ?: null
            ];
        }

        foreach ($puntos as $punto) {
            if (!empty($punto['creado_en'])) {
                $timeline[] = [
                    'fecha' => $punto['creado_en'],
                    'tipo' => 'PUNTO',
                    'titulo' => sprintf('Punto %s agregado', (string) ($punto['numero_orden'] ?? '-')),
                    'detalle' => $punto['titulo'] ?? null
                ];
            }
            if (!empty($punto['actualizado_en']) && $punto['actualizado_en'] !== ($punto['creado_en'] ?? null)) {
                $timeline[] = [
                    'fecha' => $punto['actualizado_en'],
                    'tipo' => 'PUNTO',
                    'titulo' => sprintf('Punto %s actualizado', (string) ($punto['numero_orden'] ?? '-')),
                    'detalle' => $punto['estado'] ?? null
                ];
            }
        }

        foreach ($votaciones as $votacion) {
            $timeline[] = [
                'fecha' => $votacion['fecha_voto'] ?: $votacion['creado_en'],
                'tipo' => 'VOTACION',
                'titulo' => sprintf('Votacion del punto %s', (string) ($votacion['numero_orden'] ?? '-')),
                'detalle' => $votacion['texto_voto'] ?? null
            ];
        }

        usort($timeline, static function (array $a, array $b): int {
            return strcmp((string) ($b['fecha'] ?? ''), (string) ($a['fecha'] ?? ''));
        });

        return array_slice($timeline, 0, 40);
    }

    /** @param array<string, mixed> $junta @param array<int, array<string, mixed>> $puntos @return array<string, mixed> */
    private function construirActa(array $junta, array $puntos): array
    {
        $items = [];
        foreach ($puntos as $punto) {
            $items[] = [
                'numero_orden' => (int) ($punto['numero_orden'] ?? 0),
                'titulo' => $punto['titulo'] ?? '',
                'tipo_punto' => $punto['tipo_punto'] ?? '',
                'estado' => $punto['estado'] ?? '',
                'discusion_resumen' => $punto['discusion_resumen'] ?? null,
                'decision_final' => $punto['decision_final'] ?? null
            ];
        }

        return [
            'encabezado' => [
                'fecha' => $junta['fecha'] ?? null,
                'tipo' => $junta['tipo'] ?? null,
                'moderador' => $junta['moderador'] ?? null,
                'secretario' => $junta['secretario'] ?? null,
                'quorum_texto' => $junta['quorum_texto'] ?? null,
                'resumen_general' => $junta['resumen_general'] ?? null
            ],
            'items' => $items
        ];
    }

    /** @param array<int, array<string, mixed>> $items @return array<string, mixed> */
    private function buscarPorIdEnLista(array $items, int $id): array
    {
        foreach ($items as $item) {
            if ((int) ($item['id'] ?? 0) === $id) {
                return $item;
            }
        }
        return [];
    }

    private function toRequiredText(mixed $value, int $max, string $message): string
    {
        $clean = $this->toNullableText($value, $max);
        if ($clean === null || $clean === '') {
            throw new InvalidArgumentException($message);
        }
        return $clean;
    }

    private function toNullableText(mixed $value, int $max, bool $trimSoft = false): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }
        if ($trimSoft) {
            $text = Sanitizer::cleanString($text);
        }
        if (mb_strlen($text) > $max) {
            $text = mb_substr($text, 0, $max);
        }
        return $text;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Debe indicar un valor numerico valido.');
        }
        return (int) $value;
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value === 1;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'si', 'yes'], true);
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
        $text = substr(trim((string) $value), 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $text)) {
            throw new InvalidArgumentException('Formato de fecha invalido.');
        }
        return $text;
    }

    private function toNullableTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $text = substr(trim((string) $value), 0, 5);
        if (!preg_match('/^\d{2}:\d{2}$/', $text)) {
            throw new InvalidArgumentException('Formato de hora invalido.');
        }
        return $text;
    }

    private function toNullableDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $text = str_replace('T', ' ', trim((string) $value));
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $text)) {
            return $text . ':00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $text)) {
            return $text;
        }
        throw new InvalidArgumentException('Formato de fecha y hora invalido.');
    }

    /** @param array<int, string> $allowed */
    private function toEnum(mixed $value, array $allowed, bool $nullable = false, string $message = 'Valor invalido.'): ?string
    {
        if ($value === null || $value === '') {
            if ($nullable) {
                return null;
            }
            throw new InvalidArgumentException($message);
        }
        $text = strtoupper(trim((string) $value));
        if (!in_array($text, $allowed, true)) {
            throw new InvalidArgumentException($message);
        }
        return $text;
    }
}
