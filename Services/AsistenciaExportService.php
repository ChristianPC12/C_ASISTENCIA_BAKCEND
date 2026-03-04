<?php
declare(strict_types=1);

/**
 * Servicio para generar exportaciones de un registro de asistencia.
 */
final class AsistenciaExportService
{
    /**
     * Genera un archivo HTML compatible con Excel (.xls) con estilo basico.
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
     * Genera un PDF de una pagina con el detalle campo por campo del registro.
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

        foreach ($this->construirFilas($registro, true, true) as [$campo, $valor]) {
            $texto = $campo . ': ' . ($valor === '' ? '-' : $valor);
            foreach ($this->envolverLinea($texto, 96) as $subLinea) {
                $lineas[] = $subLinea;
            }
        }

        $lineas[] = '';
        $lineas[] = 'Generado: ' . date('Y-m-d H:i:s');

        $y = 800;
        $contenido = "BT\n/F1 10 Tf\n";
        foreach ($lineas as $linea) {
            $texto = $this->escaparPdf($this->aWinAnsi($linea));
            $contenido .= "1 0 0 1 45 {$y} Tm\n({$texto}) Tj\n";
            $y -= 15;
            if ($y < 50) {
                break;
            }
        }
        $contenido .= "ET";

        $len = strlen($contenido);

        $o1 = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $o2 = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $o3 = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] "
            . "/Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
        $o4 = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $o5 = "5 0 obj\n<< /Length {$len} >>\nstream\n{$contenido}\nendstream\nendobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        $offsets[] = strlen($pdf);
        $pdf .= $o1;
        $offsets[] = strlen($pdf);
        $pdf .= $o2;
        $offsets[] = strlen($pdf);
        $pdf .= $o3;
        $offsets[] = strlen($pdf);
        $pdf .= $o4;
        $offsets[] = strlen($pdf);
        $pdf .= $o5;

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

    private function aWinAnsi(string $texto): string
    {
        $convertido = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $texto);
        return $convertido === false ? $texto : $convertido;
    }

    private function escaparPdf(string $texto): string
    {
        $t = str_replace('\\', '\\\\', $texto);
        $t = str_replace('(', '\\(', $t);
        return str_replace(')', '\\)', $t);
    }
}
