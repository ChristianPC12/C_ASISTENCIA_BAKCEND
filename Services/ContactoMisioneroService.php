<?php
declare(strict_types=1);

/**
 * Servicio compartido para contactos misioneros reutilizables.
 */
final class ContactoMisioneroService
{
    private ContactoMisioneroDAO $contactoDAO;
    private AuditoriaService $auditoriaService;

    public function __construct()
    {
        $this->contactoDAO = new ContactoMisioneroDAO();
        $this->auditoriaService = new AuditoriaService();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function listar(array $filters, int $organizacionId): array
    {
        $items = [];
        foreach ($this->contactoDAO->findAll($filters, $organizacionId) as $dto) {
            $items[] = ContactoMisioneroMapper::toArray($dto);
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    public function obtener(int $id, int $organizacionId): array
    {
        $dto = $this->contactoDAO->findById($id, $organizacionId);
        if ($dto === null) {
            throw new OutOfBoundsException('Contacto misionero no encontrado.');
        }

        return ContactoMisioneroMapper::toArray($dto);
    }

    /**
     * Resuelve un contacto existente por dedupe o crea uno nuevo.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function resolverOCrear(array $data, int $organizacionId, int $usuarioId, string $actorNombre = ''): array
    {
        $payload = $this->normalizarPayload($data, $organizacionId, $usuarioId);
        $nombreNormalizado = $this->normalizarTexto($payload['nombre_completo']);
        $telefonoNormalizado = $payload['telefono_normalizado'] !== '' ? (string) $payload['telefono_normalizado'] : null;

        $existente = $this->contactoDAO->findBestMatch($nombreNormalizado, $telefonoNormalizado, $organizacionId);
        if ($existente !== null) {
            $antes = ContactoMisioneroMapper::toArray($existente);
            $this->contactoDAO->update($existente->id, $payload, $organizacionId);
            $actualizado = $this->contactoDAO->findById($existente->id, $organizacionId);
            if ($actualizado === null) {
                throw new RuntimeException('No fue posible recuperar el contacto actualizado.');
            }

            $despues = ContactoMisioneroMapper::toArray($actualizado);
            if ($antes !== $despues) {
                $this->auditoriaService->registrar(
                    'CONTACTOS_MISIONEROS',
                    'CONTACTO',
                    $actualizado->id,
                    'ACTUALIZAR',
                    'Contacto misionero actualizado por deduplicacion o enriquecimiento.',
                    $organizacionId,
                    $usuarioId,
                    $actorNombre,
                    $antes,
                    $despues,
                    ['origen' => 'resolver_o_crear']
                );
            }

            return $despues;
        }

        $id = $this->contactoDAO->insert($payload);
        $creado = $this->contactoDAO->findById($id, $organizacionId);
        if ($creado === null) {
            throw new RuntimeException('No fue posible recuperar el contacto creado.');
        }

        $resultado = ContactoMisioneroMapper::toArray($creado);
        $this->auditoriaService->registrar(
            'CONTACTOS_MISIONEROS',
            'CONTACTO',
            $creado->id,
            'CREAR',
            'Contacto misionero creado.',
            $organizacionId,
            $usuarioId,
            $actorNombre,
            null,
            $resultado,
            ['origen' => 'resolver_o_crear']
        );

        return $resultado;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function actualizar(int $id, array $data, int $organizacionId, int $usuarioId, string $actorNombre = ''): array
    {
        $existente = $this->contactoDAO->findById($id, $organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('Contacto misionero no encontrado.');
        }

        $payload = $this->normalizarPayload($data, $organizacionId, $usuarioId, $existente);
        $coincidencia = $this->contactoDAO->findBestMatch(
            $this->normalizarTexto($payload['nombre_completo']),
            $payload['telefono_normalizado'] !== '' ? (string) $payload['telefono_normalizado'] : null,
            $organizacionId,
            $id
        );

        if ($coincidencia !== null) {
            throw new RuntimeException('Ya existe otro contacto con esos datos principales.');
        }

        $antes = ContactoMisioneroMapper::toArray($existente);
        $this->contactoDAO->update($id, $payload, $organizacionId);
        $actualizado = $this->contactoDAO->findById($id, $organizacionId);
        if ($actualizado === null) {
            throw new RuntimeException('No fue posible recuperar el contacto actualizado.');
        }

        $despues = ContactoMisioneroMapper::toArray($actualizado);
        $this->auditoriaService->registrar(
            'CONTACTOS_MISIONEROS',
            'CONTACTO',
            $id,
            'ACTUALIZAR',
            'Contacto misionero actualizado manualmente.',
            $organizacionId,
            $usuarioId,
            $actorNombre,
            $antes,
            $despues,
            ['origen' => 'actualizacion_manual']
        );

        return $despues;
    }

    public function eliminar(int $id, int $organizacionId, int $usuarioId, string $actorNombre = ''): void
    {
        $existente = $this->contactoDAO->findById($id, $organizacionId);
        if ($existente === null) {
            throw new OutOfBoundsException('Contacto misionero no encontrado.');
        }

        $antes = ContactoMisioneroMapper::toArray($existente);
        $this->contactoDAO->softDelete($id, $organizacionId, $usuarioId);
        $this->auditoriaService->registrar(
            'CONTACTOS_MISIONEROS',
            'CONTACTO',
            $id,
            'ELIMINAR',
            'Contacto misionero desactivado con soft delete.',
            $organizacionId,
            $usuarioId,
            $actorNombre,
            $antes,
            null,
            ['origen' => 'soft_delete']
        );
    }

    /**
     * @param array<string, mixed>      $data
     * @param ContactoMisioneroDTO|null $base
     * @return array<string, mixed>
     */
    private function normalizarPayload(array $data, int $organizacionId, int $usuarioId, ?ContactoMisioneroDTO $base = null): array
    {
        $nombre = Sanitizer::cleanString((string) ($data['nombre_completo'] ?? ($base?->nombreCompleto ?? '')));
        if ($nombre === '' || strlen($nombre) < 3) {
            throw new InvalidArgumentException('El nombre del contacto es obligatorio.');
        }

        $telefono = isset($data['telefono']) ? Sanitizer::cleanString((string) $data['telefono']) : ($base?->telefono ?? null);
        $telefono = $telefono === '' ? null : $telefono;
        $telefonoNormalizado = $this->normalizarTelefono($telefono);

        $correo = isset($data['correo']) ? Sanitizer::cleanString((string) $data['correo']) : ($base?->correo ?? null);
        $correo = $correo === '' ? null : $correo;
        if ($correo !== null && filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('El correo del contacto no tiene un formato valido.');
        }

        $clasificacion = strtoupper(Sanitizer::cleanString((string) ($data['clasificacion_principal'] ?? ($base?->clasificacionPrincipal ?? 'INTERESADO'))));
        $clasificacionesValidas = [
            'MIEMBRO', 'VISITA', 'INTERESADO', 'AMIGO', 'NINO', 'JOVEN', 'ADULTO', 'LIDER', 'ANFITRION', 'INSTRUCTOR_BIBLICO', 'OTRO'
        ];
        if (!in_array($clasificacion, $clasificacionesValidas, true)) {
            throw new InvalidArgumentException('La clasificacion principal del contacto no es valida.');
        }

        $estado = strtoupper(Sanitizer::cleanString((string) ($data['estado_contacto'] ?? ($base?->estadoContacto ?? 'ACTIVO'))));
        $estadosValidos = ['ACTIVO', 'INACTIVO', 'NO_LOCALIZABLE', 'BAUTIZADO', 'ARCHIVADO'];
        if (!in_array($estado, $estadosValidos, true)) {
            throw new InvalidArgumentException('El estado del contacto no es valido.');
        }

        $fechaPrimerContacto = $this->normalizarFecha($data['fecha_primer_contacto'] ?? ($base?->fechaPrimerContacto ?? null));
        $fechaUltimoContacto = $this->normalizarFecha($data['fecha_ultimo_contacto'] ?? ($base?->fechaUltimoContacto ?? null));

        return [
            'organizacion_id' => $organizacionId,
            'nombre_completo' => $nombre,
            'telefono' => $telefono,
            'telefono_normalizado' => $telefonoNormalizado,
            'correo' => $correo,
            'direccion' => $this->normalizarTextoOpcional($data['direccion'] ?? ($base?->direccion ?? null)),
            'barrio_comunidad' => $this->normalizarTextoOpcional($data['barrio_comunidad'] ?? ($base?->barrioComunidad ?? null)),
            'clasificacion_principal' => $clasificacion,
            'es_miembro' => $this->normalizarBooleano($data['es_miembro'] ?? ($base?->esMiembro ?? false)),
            'estado_contacto' => $estado,
            'fecha_primer_contacto' => $fechaPrimerContacto,
            'fecha_ultimo_contacto' => $fechaUltimoContacto,
            'origen_principal_clave' => $this->normalizarTextoOpcional($data['origen_principal_clave'] ?? ($base?->origenPrincipalClave ?? null), true),
            'modulo_origen' => $this->normalizarTextoOpcional($data['modulo_origen'] ?? ($base?->moduloOrigen ?? null), true),
            'referencia_origen_id' => $this->normalizarEnteroOpcional($data['referencia_origen_id'] ?? ($base?->referenciaOrigenId ?? null)),
            'observaciones_generales' => $this->normalizarTextoOpcional($data['observaciones_generales'] ?? ($base?->observacionesGenerales ?? null)),
            'creado_por' => $base?->creadoPor ?? $usuarioId,
            'actualizado_por' => $usuarioId
        ];
    }

    private function normalizarTexto(string $valor): string
    {
        $trim = trim($valor);
        return function_exists('mb_strtolower') ? mb_strtolower($trim, 'UTF-8') : strtolower($trim);
    }

    private function normalizarTelefono(?string $telefono): string
    {
        if ($telefono === null || $telefono === '') {
            return '';
        }

        $soloDigitos = preg_replace('/\D+/', '', $telefono);
        return is_string($soloDigitos) ? $soloDigitos : '';
    }

    private function normalizarFecha(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (!is_string($valor)) {
            throw new InvalidArgumentException('La fecha del contacto debe ser texto.');
        }

        $fecha = trim($valor);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new InvalidArgumentException('La fecha del contacto debe usar formato YYYY-MM-DD.');
        }

        return $fecha;
    }

    private function normalizarTextoOpcional(mixed $valor, bool $uppercase = false): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $texto = Sanitizer::cleanString((string) $valor);
        if ($texto === '') {
            return null;
        }

        return $uppercase ? strtoupper($texto) : $texto;
    }

    private function normalizarBooleano(mixed $valor): bool
    {
        $parsed = filter_var($valor, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $parsed === null ? false : (bool) $parsed;
    }

    private function normalizarEnteroOpcional(mixed $valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (!is_numeric($valor)) {
            throw new InvalidArgumentException('La referencia del origen debe ser numerica.');
        }

        return (int) $valor;
    }
}
