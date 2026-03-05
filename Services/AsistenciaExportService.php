<?php
declare(strict_types=1);

/**
 * Servicio para generar exportaciones de asistencia.
 */
final class AsistenciaExportService
{
    /**
     * Genera un archivo HTML compatible con Excel (.xls) para un solo registro.
     *
     * @param array<string, mixed> $registro
     * @return string
     */
    public function generarExcel(array $registro): string
    {
        $titulo = 'Registro de Asistencia - ' . ($registro['culto_nombre'] ?? 'Culto');
        $fecha  = (string) ($registro['fecha'] ?? '');
        $filas = $this->construirFilas($registro, false, false);

        $rowsHtml = '';
        foreach ($filas as [$campo, $valor]) {
            $rowsHtml .= '<tr>'
                . '<td class="label">' . htmlspecialchars($campo, ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td class="value">' . nl2br(htmlspecialchars($valor, ENT_QUOTES, 'UTF-8')) . '</td>'
                . '</tr>';
        }

        return '<html><head><meta charset="UTF-8">'
            . '<style>'
            . 'body{font-family:Calibri,Arial,sans-serif;background:#f5f8fa;margin:24px;}'
            . 'h1{margin:0 0 8px 0;color:#0f2f4f;font-size:20px;}'
            . 'p{margin:0 0 16px 0;color:#4b5563;font-size:12px;}'
            . 'table{border-collapse:collapse;width:100%;background:#fff;}'
            . 'th,td{border:1px solid #cfd8e3;padding:8px;vertical-align:top;}'
            . '.label{background:#eef4fb;font-weight:700;color:#0f2f4f;width:35%;}'
            . '.value{color:#1f2937;}'
            . '</style></head><body>'
            . '<h1>' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h1>'
            . '<p>Fecha del registro: ' . htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<table><tbody>' . $rowsHtml . '</tbody></table>'
            . '</body></html>';
    }

    /**
     * Genera un PDF de una página con el detalle de un solo registro.
     *
     * @param array<string, mixed> $registro
     * @return string
     */
    public function generarPdf(array $registro): string
    {
        $lineas = [
            'Registro de Asistencia',
            'Culto: ' . (string) ($registro['culto_nombre'] ?? $registro['culto_codigo'] ?? ''),
            'Fecha del registro: ' . (string) ($registro['fecha'] ?? ''),
            ''
        ];

        foreach ($this->construirFilas($registro, false, false) as [$campo, $valor]) {
            $texto = $campo . ': ' . ($valor === '' ? '-' : $valor);
            foreach ($this->envolverLinea($texto, 96) as $subLinea) {
                $lineas[] = $subLinea;
            }
        }

        return $this->crearPdfDesdeLineas($lineas, 10, 45, 15, 50);
    }

    /**
     * Genera informe en Excel con lista de registros y detalles opcionales.
     *
     * @param array<int, array<string, mixed>> $registros
     * @param array<string, mixed> $filtros
     * @return string
     */
    public function generarInformeExcel(array $registros, array $filtros): string
    {
        $titulo = 'Informe de Registros de Asistencia';
        $subtitulo = $this->descripcionFiltros($filtros);

        $rowsHtml = '';
        foreach ($registros as $reg) {
            $rowsHtml .= '<tr>'
                . '<td>' . htmlspecialchars((string) ($reg['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars((string) ($reg['culto_nombre'] ?? $reg['culto_codigo'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . (int) ($reg['total_asistentes'] ?? 0) . '</td>'
                . '<td>' . (int) ($reg['ninos'] ?? 0) . '</td>'
                . '<td>' . (int) ($reg['jovenes'] ?? 0) . '</td>'
                . '<td>' . (int) ($reg['llegaron_antes_hora'] ?? 0) . '</td>'
                . '<td>' . (int) ($reg['llegaron_despues_hora'] ?? 0) . '</td>'
                . '<td>' . (int) ($reg['proc_barrio'] ?? 0) . '</td>'
                . '<td>' . (int) ($reg['proc_guayabo'] ?? 0) . '</td>'
                . '<td>' . (int) ($reg['visitas_barrio'] ?? 0) . '</td>'
                . '<td>' . (int) ($reg['visitas_guayabo'] ?? 0) . '</td>'
                . '<td>' . (int) ($reg['retiros_antes_terminar'] ?? 0) . '</td>'
                . '<td>' . (int) ($reg['se_quedaron_todo'] ?? 0) . '</td>'
                . '</tr>';

            $detalles = [];
            if ((int) ($reg['visitas_barrio'] ?? 0) > 0) {
                $detalles[] = 'Visitas del Barrio: ' . ((string) ($reg['nombres_visitas_barrio'] ?? '') ?: '-');
            }
            if ((int) ($reg['visitas_guayabo'] ?? 0) > 0) {
                $detalles[] = 'Visitas de Guayabo: ' . ((string) ($reg['nombres_visitas_guayabo'] ?? '') ?: '-');
            }
            if (!empty($reg['observaciones'])) {
                $detalles[] = 'Observaciones: ' . (string) $reg['observaciones'];
            }

            if (!empty($detalles)) {
                $rowsHtml .= '<tr class="detalle"><td colspan="13">'
                    . nl2br(htmlspecialchars(implode("\n", $detalles), ENT_QUOTES, 'UTF-8'))
                    . '</td></tr>';
            }
        }

        if ($rowsHtml === '') {
            $rowsHtml = '<tr><td colspan="13" class="vacio">Sin registros para los filtros seleccionados.</td></tr>';
        }

        return '<html><head><meta charset="UTF-8">'
            . '<style>'
            . 'body{font-family:Calibri,Arial,sans-serif;background:#f5f8fa;margin:24px;}'
            . 'h1{margin:0 0 8px 0;color:#0f2f4f;font-size:20px;}'
            . 'p{margin:0 0 16px 0;color:#4b5563;font-size:12px;}'
            . 'table{border-collapse:collapse;width:100%;background:#fff;font-size:12px;}'
            . 'th,td{border:1px solid #cfd8e3;padding:6px;vertical-align:top;}'
            . 'th{background:#0f2f4f;color:#fff;}'
            . '.detalle td{background:#f8fafc;color:#1f2937;}'
            . '.vacio{text-align:center;color:#6b7280;}'
            . '</style></head><body>'
            . '<h1>' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h1>'
            . '<p>' . htmlspecialchars($subtitulo, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<table><thead><tr>'
            . '<th>Fecha</th><th>Culto</th><th>Total</th><th>Niños</th><th>Jóvenes</th>'
            . '<th>Antes</th><th>Después</th><th>Barrio</th><th>Guayabo</th>'
            . '<th>Visitas B.</th><th>Visitas G.</th><th>Retiros</th><th>Quedaron</th>'
            . '</tr></thead><tbody>' . $rowsHtml . '</tbody></table>'
            . '</body></html>';
    }

    /**
     * Genera informe en PDF con lista de registros y detalles opcionales.
     *
     * @param array<int, array<string, mixed>> $registros
     * @param array<string, mixed> $filtros
     * @return string
     */
    public function generarInformePdf(array $registros, array $filtros): string
    {
        $lineas = [
            'Informe de Registros de Asistencia',
            $this->descripcionFiltros($filtros),
            ''
        ];

        if (empty($registros)) {
            $lineas[] = 'Sin registros para los filtros seleccionados.';
        } else {
            foreach ($registros as $i => $reg) {
                $lineas[] = sprintf(
                    '%d) %s | %s | Total:%d | N:%d J:%d | A:%d D:%d | B:%d G:%d | VB:%d VG:%d | R:%d Q:%d',
                    $i + 1,
                    (string) ($reg['fecha'] ?? ''),
                    (string) ($reg['culto_nombre'] ?? $reg['culto_codigo'] ?? ''),
                    (int) ($reg['total_asistentes'] ?? 0),
                    (int) ($reg['ninos'] ?? 0),
                    (int) ($reg['jovenes'] ?? 0),
                    (int) ($reg['llegaron_antes_hora'] ?? 0),
                    (int) ($reg['llegaron_despues_hora'] ?? 0),
                    (int) ($reg['proc_barrio'] ?? 0),
                    (int) ($reg['proc_guayabo'] ?? 0),
                    (int) ($reg['visitas_barrio'] ?? 0),
                    (int) ($reg['visitas_guayabo'] ?? 0),
                    (int) ($reg['retiros_antes_terminar'] ?? 0),
                    (int) ($reg['se_quedaron_todo'] ?? 0)
                );

                if ((int) ($reg['visitas_barrio'] ?? 0) > 0) {
                    $lineas[] = '   Visitas del Barrio: ' . ((string) ($reg['nombres_visitas_barrio'] ?? '') ?: '-');
                }
                if ((int) ($reg['visitas_guayabo'] ?? 0) > 0) {
                    $lineas[] = '   Visitas de Guayabo: ' . ((string) ($reg['nombres_visitas_guayabo'] ?? '') ?: '-');
                }
                if (!empty($reg['observaciones'])) {
                    $lineas[] = '   Observaciones: ' . (string) $reg['observaciones'];
                }
                $lineas[] = '';
            }
        }

        return $this->crearPdfDesdeLineas($lineas, 9, 30, 12, 40, 115);
    }

    /**
     * @param array<string, mixed> $registro
     * @return array<int, array{0:string,1:string}>
     */
    private function construirFilas(array $registro, bool $incluirId, bool $incluirActualizado): array
    {
        $filas = [];

        if ($incluirId) {
            $filas[] = ['ID del registro', (string) ($registro['id'] ?? '')];
        }

        $filas = array_merge($filas, [
            ['Culto', (string) ($registro['culto_nombre'] ?? $registro['culto_codigo'] ?? '')],
            ['Código del culto', (string) ($registro['culto_codigo'] ?? '')],
            ['Fecha', (string) ($registro['fecha'] ?? '')],
            ['Año', (string) ($registro['anio'] ?? '')],
            ['Trimestre', (string) ($registro['trimestre'] ?? '')],
            ['Llegaron antes de la hora', (string) ($registro['llegaron_antes_hora'] ?? '0')],
            ['Llegaron después de la hora', (string) ($registro['llegaron_despues_hora'] ?? '0')],
            ['Niños', (string) ($registro['ninos'] ?? '0')],
            ['Jóvenes', (string) ($registro['jovenes'] ?? '0')],
            ['Total de asistentes', (string) ($registro['total_asistentes'] ?? '0')],
            ['Procedentes del barrio', (string) ($registro['proc_barrio'] ?? '0')],
            ['Procedentes de Guayabo', (string) ($registro['proc_guayabo'] ?? '0')],
            ['Visitas del barrio', (string) ($registro['visitas_barrio'] ?? '0')],
            ['Nombres de visitas del barrio', (string) ($registro['nombres_visitas_barrio'] ?? '')],
            ['Visitas de Guayabo', (string) ($registro['visitas_guayabo'] ?? '0')],
            ['Nombres de visitas de Guayabo', (string) ($registro['nombres_visitas_guayabo'] ?? '')],
            ['Retiros antes de terminar', (string) ($registro['retiros_antes_terminar'] ?? '0')],
            ['Se quedaron todo el culto', (string) ($registro['se_quedaron_todo'] ?? '0')],
            ['Observaciones', (string) ($registro['observaciones'] ?? '')],
            ['Registrado por', (string) ($registro['registrado_por_nombre'] ?? '')],
            ['Creado en', (string) ($registro['creado_en'] ?? '')]
        ]);

        if ($incluirActualizado) {
            $filas[] = ['Actualizado en', (string) ($registro['actualizado_en'] ?? '')];
        }

        return $filas;
    }

    /**
     * @return array<int, string>
     */
    private function envolverLinea(string $texto, int $maxLen): array
    {
        $texto = trim(preg_replace('/\s+/', ' ', $texto) ?? '');
        if ($texto === '') {
            return [''];
        }

        $resultado = [];
        while (strlen($texto) > $maxLen) {
            $corte = strrpos(substr($texto, 0, $maxLen + 1), ' ');
            if ($corte === false) {
                $corte = $maxLen;
            }
            $resultado[] = trim(substr($texto, 0, $corte));
            $texto = trim(substr($texto, $corte));
        }
        $resultado[] = $texto;

        return $resultado;
    }

    /**
     * @param array<string> $lineas
     */
    private function crearPdfDesdeLineas(array $lineas, int $fontSize, int $x, int $lineStep, int $yMin, int $wrapLen = 96): string
    {
        $y = 800;
        $contenido = "BT\n/F1 {$fontSize} Tf\n";

        foreach ($lineas as $linea) {
            foreach ($this->envolverLinea($linea, $wrapLen) as $subLinea) {
                $texto = $this->escaparPdf($this->aWinAnsi($subLinea));
                $contenido .= "1 0 0 1 {$x} {$y} Tm\n({$texto}) Tj\n";
                $y -= $lineStep;
                if ($y < $yMin) {
                    break 2;
                }
            }
        }

        $contenido .= "ET";
        $len = strlen($contenido);

        $o1 = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $o2 = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $o3 = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] "
            . "/Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
        $o4 = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\nendobj\n";
        $o5 = "5 0 obj\n<< /Length {$len} >>\nstream\n{$contenido}\nendstream\nendobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        $offsets[] = strlen($pdf); $pdf .= $o1;
        $offsets[] = strlen($pdf); $pdf .= $o2;
        $offsets[] = strlen($pdf); $pdf .= $o3;
        $offsets[] = strlen($pdf); $pdf .= $o4;
        $offsets[] = strlen($pdf); $pdf .= $o5;

        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private function aWinAnsi(string $texto): string
    {
        $convertido = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $texto);
        return $convertido === false ? $texto : $convertido;
    }

    /**
     * @param array<string, mixed> $filtros
     */
    private function descripcionFiltros(array $filtros): string
    {
        $partes = [];
        if (!empty($filtros['anio'])) {
            $partes[] = 'Año: ' . (string) $filtros['anio'];
        }
        if (!empty($filtros['trimestre'])) {
            $partes[] = 'Trimestre: ' . (string) $filtros['trimestre'];
        }
        if (!empty($filtros['mes'])) {
            $partes[] = 'Mes: ' . str_pad((string) $filtros['mes'], 2, '0', STR_PAD_LEFT);
        }
        if (!empty($filtros['culto'])) {
            $partes[] = 'Culto: ' . (string) $filtros['culto'];
        }
        if (!empty($filtros['buscar_culto'])) {
            $partes[] = 'Búsqueda: ' . (string) $filtros['buscar_culto'];
        }

        if (empty($partes)) {
            return 'Filtros: todos los registros.';
        }

        return 'Filtros: ' . implode(' | ', $partes);
    }

    private function escaparPdf(string $texto): string
    {
        $t = str_replace('\\', '\\\\', $texto);
        $t = str_replace('(', '\\(', $t);
        return str_replace(')', '\\)', $t);
    }
}
