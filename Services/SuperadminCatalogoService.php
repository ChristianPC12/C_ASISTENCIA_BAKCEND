<?php
declare(strict_types=1);

/**
 * Clase SuperadminCatalogoService
 *
 * Logica de negocio para catalogos globales de superadmin.
 */
final class SuperadminCatalogoService
{
    /** @var OrganizacionDAO */
    private OrganizacionDAO $organizacionDAO;

    public function __construct()
    {
        $this->organizacionDAO = new OrganizacionDAO();
    }

    /**
     * Lista catalogo de campos.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarCampos(): array
    {
        $rows = $this->organizacionDAO->listCampos();
        return array_map([$this, 'mapCatalogoItem'], $rows);
    }

    /**
     * Lista catalogo de distritos.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarDistritos(): array
    {
        $rows = $this->organizacionDAO->listDistritos();
        return array_map([$this, 'mapCatalogoItem'], $rows);
    }

    /**
     * Crea un campo.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function crearCampo(array $data): array
    {
        $codigo = (string) $data['codigo'];
        $nombre = (string) $data['nombre'];
        $activo = array_key_exists('activo', $data) && is_bool($data['activo'])
            ? $data['activo']
            : true;

        if ($this->organizacionDAO->findCampoByCodigo($codigo) !== null) {
            throw new RuntimeException('Ya existe un campo con ese codigo.');
        }

        if ($this->existeNombreCatalogo($this->organizacionDAO->listCampos(), $nombre)) {
            throw new RuntimeException('Ya existe un campo con ese nombre.');
        }

        $this->organizacionDAO->insertCampo($codigo, $nombre, $activo);
        $creado = $this->organizacionDAO->findCampoByCodigo($codigo);
        if ($creado === null) {
            throw new RuntimeException('No fue posible recuperar el campo creado.');
        }

        return $this->mapCatalogoItem($creado);
    }

    /**
     * Actualiza nombre/estado de un campo.
     *
     * @param string               $codigo
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function actualizarCampo(string $codigo, array $data): array
    {
        $existente = $this->organizacionDAO->findCampoByCodigo($codigo);
        if ($existente === null) {
            throw new OutOfBoundsException('Campo no encontrado.');
        }

        $nuevoNombre = (string) $data['nombre'];
        $nuevoActivo = array_key_exists('activo', $data) && is_bool($data['activo'])
            ? $data['activo']
            : ((int) ($existente['activo'] ?? 0) === 1);

        if ($this->existeNombreCatalogo($this->organizacionDAO->listCampos(), $nuevoNombre, $codigo)) {
            throw new RuntimeException('Ya existe un campo con ese nombre.');
        }

        $this->organizacionDAO->updateCampoByCodigo($codigo, $nuevoNombre, $nuevoActivo);
        $actualizado = $this->organizacionDAO->findCampoByCodigo($codigo);
        if ($actualizado === null) {
            throw new RuntimeException('No fue posible recuperar el campo actualizado.');
        }

        return $this->mapCatalogoItem($actualizado);
    }

    /**
     * Elimina un campo.
     *
     * @param string $codigo
     * @return void
     */
    public function eliminarCampo(string $codigo): void
    {
        $existente = $this->organizacionDAO->findCampoByCodigo($codigo);
        if ($existente === null) {
            throw new OutOfBoundsException('Campo no encontrado.');
        }

        $enUso = $this->organizacionDAO->countOrganizacionesByCampoCodigo($codigo);
        if ($enUso > 0) {
            throw new RuntimeException('No se puede eliminar el campo porque esta asignado a organizaciones.');
        }

        $this->organizacionDAO->deleteCampoByCodigo($codigo);
    }

    /**
     * Crea un distrito.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function crearDistrito(array $data): array
    {
        $nombre = (string) $data['nombre'];
        $activo = array_key_exists('activo', $data) && is_bool($data['activo'])
            ? $data['activo']
            : true;

        $distritosActuales = $this->organizacionDAO->listDistritos();

        $codigo = is_string($data['codigo'] ?? null) && $data['codigo'] !== ''
            ? (string) $data['codigo']
            : $this->generarCodigoDistrito($nombre, $distritosActuales);

        if ($this->organizacionDAO->findDistritoByCodigo($codigo) !== null) {
            throw new RuntimeException('Ya existe un distrito con ese codigo.');
        }

        if ($this->existeNombreCatalogo($distritosActuales, $nombre)) {
            throw new RuntimeException('Ya existe un distrito con ese nombre.');
        }

        $this->organizacionDAO->insertDistrito($codigo, $nombre, $activo);
        $creado = $this->organizacionDAO->findDistritoByCodigo($codigo);
        if ($creado === null) {
            throw new RuntimeException('No fue posible recuperar el distrito creado.');
        }

        return $this->mapCatalogoItem($creado);
    }

    /**
     * Actualiza nombre/estado de un distrito.
     *
     * @param string               $codigo
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function actualizarDistrito(string $codigo, array $data): array
    {
        $existente = $this->organizacionDAO->findDistritoByCodigo($codigo);
        if ($existente === null) {
            throw new OutOfBoundsException('Distrito no encontrado.');
        }

        $nuevoNombre = (string) $data['nombre'];
        $nuevoActivo = array_key_exists('activo', $data) && is_bool($data['activo'])
            ? $data['activo']
            : ((int) ($existente['activo'] ?? 0) === 1);

        if ($this->existeNombreCatalogo($this->organizacionDAO->listDistritos(), $nuevoNombre, $codigo)) {
            throw new RuntimeException('Ya existe un distrito con ese nombre.');
        }

        $this->organizacionDAO->updateDistritoByCodigo($codigo, $nuevoNombre, $nuevoActivo);
        $actualizado = $this->organizacionDAO->findDistritoByCodigo($codigo);
        if ($actualizado === null) {
            throw new RuntimeException('No fue posible recuperar el distrito actualizado.');
        }

        return $this->mapCatalogoItem($actualizado);
    }

    /**
     * Elimina un distrito.
     *
     * @param string $codigo
     * @return void
     */
    public function eliminarDistrito(string $codigo): void
    {
        $existente = $this->organizacionDAO->findDistritoByCodigo($codigo);
        if ($existente === null) {
            throw new OutOfBoundsException('Distrito no encontrado.');
        }

        $enUso = $this->organizacionDAO->countOrganizacionesByDistritoCodigo($codigo);
        if ($enUso > 0) {
            throw new RuntimeException('No se puede eliminar el distrito porque esta asignado a organizaciones.');
        }

        $this->organizacionDAO->deleteDistritoByCodigo($codigo);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapCatalogoItem(array $row): array
    {
        return [
            'codigo' => (string) ($row['codigo'] ?? ''),
            'nombre' => (string) ($row['nombre'] ?? ''),
            'activo' => (int) ($row['activo'] ?? 0) === 1,
            'creado_en' => isset($row['creado_en']) ? (string) $row['creado_en'] : null,
            'actualizado_en' => isset($row['actualizado_en']) ? (string) $row['actualizado_en'] : null
        ];
    }

    /**
     * Detecta nombre duplicado en un catalogo.
     *
     * @param array<int, array<string, mixed>> $rows
     * @param string                            $nombreBuscado
     * @param string|null                       $excludeCodigo
     * @return bool
     */
    private function existeNombreCatalogo(array $rows, string $nombreBuscado, ?string $excludeCodigo = null): bool
    {
        $objetivo = $this->normalizarNombre($nombreBuscado);
        if ($objetivo === '') {
            return false;
        }

        foreach ($rows as $row) {
            $codigo = strtoupper((string) ($row['codigo'] ?? ''));
            if ($excludeCodigo !== null && $codigo === strtoupper($excludeCodigo)) {
                continue;
            }

            $nombre = $this->normalizarNombre((string) ($row['nombre'] ?? ''));
            if ($nombre !== '' && $nombre === $objetivo) {
                return true;
            }
        }

        return false;
    }

    /**
     * Genera un codigo de distrito unico basado en el nombre.
     *
     * @param string                            $nombre
     * @param array<int, array<string, mixed>> $existentes
     * @return string
     */
    private function generarCodigoDistrito(string $nombre, array $existentes): string
    {
        $codigos = [];
        foreach ($existentes as $item) {
            $codigo = strtoupper((string) ($item['codigo'] ?? ''));
            if ($codigo !== '') {
                $codigos[$codigo] = true;
            }
        }

        $base = $this->normalizarCodigoDistrito($nombre);
        if ($base === '') {
            $base = 'DISTRITO';
        }

        if (!isset($codigos[$base])) {
            return $base;
        }

        for ($i = 2; $i <= 999; $i++) {
            $candidate = $this->normalizarCodigoDistrito($base . '_' . $i);
            if ($candidate !== '' && !isset($codigos[$candidate])) {
                return $candidate;
            }
        }

        throw new RuntimeException('No hay codigos disponibles para crear el distrito.');
    }

    /**
     * @param string $nombre
     * @return string
     */
    private function normalizarNombre(string $nombre): string
    {
        $limpio = trim(preg_replace('/\s+/', ' ', $nombre) ?? '');
        if ($limpio === '') {
            return '';
        }

        $upper = strtoupper($limpio);
        $ascii = function_exists('iconv')
            ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $upper)
            : $upper;

        if (!is_string($ascii) || $ascii === '') {
            $ascii = $upper;
        }

        return trim(preg_replace('/\s+/', ' ', $ascii) ?? '');
    }

    /**
     * @param string $nombre
     * @return string
     */
    private function normalizarCodigoDistrito(string $nombre): string
    {
        $ascii = $this->normalizarNombre($nombre);
        $codigo = preg_replace('/[^A-Z0-9]+/', '_', $ascii) ?? '';
        $codigo = trim($codigo, '_');
        $codigo = preg_replace('/_{2,}/', '_', $codigo) ?? '';
        $codigo = substr($codigo, 0, 24);

        if ($codigo === '' || preg_match('/^[A-Z0-9_]{2,24}$/', $codigo) !== 1) {
            return '';
        }

        return $codigo;
    }
}
