<?php
/**
 * pdf/reporte_cliente_mes.php
 * Reporte mensual por cliente — generado con dompdf
 * Uso: pdf/reporte_cliente_mes.php?cliente_id=X&mes=Y&anio=Z
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Parámetros
$cliente_id = (int)($_GET['cliente_id'] ?? 0);
$mes        = (int)($_GET['mes']        ?? date('n'));
$anio       = (int)($_GET['anio']       ?? date('Y'));

if ($cliente_id <= 0) {
    http_response_code(400);
    exit('Parámetro cliente_id requerido.');
}

// Datos del cliente
$stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->bind_param('i', $cliente_id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();

if (!$cliente) {
    http_response_code(404);
    exit('Cliente no encontrado.');
}

// Trabajos del mes
$stmt2 = $conn->prepare("
    SELECT t.fecha, t.horas, t.comentario,
           tc.nombre AS tecnico
    FROM   trabajos t
    LEFT JOIN tecnicos tc ON tc.id = t.tecnico_id
    WHERE  t.cliente_id = ?
      AND  MONTH(t.fecha) = ?
      AND  YEAR(t.fecha)  = ?
    ORDER  BY t.fecha
");
$stmt2->bind_param('iii', $cliente_id, $mes, $anio);
$stmt2->execute();
$rows = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

$totalHoras = array_sum(array_column($rows, 'horas'));

$nombreMes = [
    1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
][$mes] ?? $mes;

// Logo en base64
$logoPath = __DIR__ . '/../image/1.png';
$logoTag  = '';
if (file_exists($logoPath)) {
    $logoData = base64_encode(file_get_contents($logoPath));
    $logoMime = mime_content_type($logoPath);
    $logoTag  = "<img src='data:{$logoMime};base64,{$logoData}' style='height:48px;'>";
}

// Filas de la tabla
$filasHTML = '';
if (empty($rows)) {
    $filasHTML = "<tr><td colspan='4' style='text-align:center;color:#888;padding:12px;'>Sin trabajos registrados para este período.</td></tr>";
} else {
    $alt = false;
    foreach ($rows as $r) {
        $bg      = $alt ? '#f1f8f1' : '#ffffff';
        $fecha   = date('d/m/Y', strtotime($r['fecha']));
        $horas   = number_format((float)$r['horas'], 2, ',', '.');
        $coment  = htmlspecialchars($r['comentario'] ?? '—', ENT_QUOTES, 'UTF-8');
        $tecnico = htmlspecialchars($r['tecnico']    ?? '—', ENT_QUOTES, 'UTF-8');
        $filasHTML .= "
        <tr style='background:{$bg};'>
            <td style='padding:6px 8px;border:1px solid #c8e0c8;text-align:center;'>{$fecha}</td>
            <td style='padding:6px 8px;border:1px solid #c8e0c8;'>{$tecnico}</td>
            <td style='padding:6px 8px;border:1px solid #c8e0c8;'>{$coment}</td>
            <td style='padding:6px 8px;border:1px solid #c8e0c8;text-align:center;font-weight:bold;'>{$horas}</td>
        </tr>";
        $alt = !$alt;
    }
}

$totalFormateado = number_format($totalHoras, 2, ',', '.');
$fechaGenerado   = date('d/m/Y H:i');

$nombreCliente = htmlspecialchars($cliente['nombre']    ?? '', ENT_QUOTES, 'UTF-8');
$rucCliente    = htmlspecialchars($cliente['ruc']       ?? '—', ENT_QUOTES, 'UTF-8');
$telCliente    = htmlspecialchars($cliente['telefono']  ?? '—', ENT_QUOTES, 'UTF-8');
$emailCliente  = htmlspecialchars($cliente['email']     ?? '—', ENT_QUOTES, 'UTF-8');
$dirCliente    = htmlspecialchars($cliente['direccion'] ?? '—', ENT_QUOTES, 'UTF-8');

$html = '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size:11px; color:#1e1e1e; }
  .header-wrap { background:#eeeeee; padding:10px 14px; width:100%; }
  .empresa     { font-size:14px; font-weight:bold; color:#2e7d32; padding-left:10px; }
  .contacto    { font-size:9px; color:#555555; padding-left:10px; margin-top:3px; }
  .header-line { height:3px; background:#66bb6a; margin-bottom:12px; }
  .titulo      { text-align:center; margin:10px 0 12px; }
  .titulo h1   { font-size:15px; color:#2e7d32; font-weight:bold; }
  .titulo p    { font-size:10px; color:#666; margin-top:2px; }
  .tarjeta     { border:1.5px solid #2e7d32; border-radius:4px; margin-bottom:14px; }
  .tarjeta-header { background:#2e7d32; color:#fff; font-weight:bold; font-size:10px; padding:5px 10px; letter-spacing:1px; }
  .tarjeta-body   { background:#f1f8f1; padding:10px 14px; }
  .etq  { font-weight:bold; color:#1b5e20; font-size:10px; display:inline-block; width:72px; }
  .val  { font-weight:bold; color:#1e1e1e; font-size:11px; }
  .val-nombre { font-weight:bold; color:#2e7d32; font-size:13px; text-transform:uppercase; }
  .dato { margin-bottom:5px; }
  table.trab { width:100%; border-collapse:collapse; margin-bottom:10px; }
  table.trab thead tr { background:#2e7d32; color:#fff; }
  table.trab thead th { padding:7px 8px; border:1px solid #1b5e20; font-size:10px; text-align:center; }
  .fila-total td { background:#1b5e20; color:#fff; font-weight:bold; padding:6px 8px; border:1px solid #145214; font-size:11px; }

</style>
</head>
<body>

<div class="header-wrap">
  <table style="width:100%;">
    <tr>
      <td style="width:60px;">' . $logoTag . '</td>
      <td>
        <div class="empresa">Cybermatica E.A.S Soluciones Tecnologicas</div>
        <div class="contacto">Tel: 0217289200 &nbsp;|&nbsp; Email: info@cybermatica.com.py</div>
      </td>
    </tr>
  </table>
</div>
<div class="header-line"></div>

<div class="titulo">
  <h1>Reporte Mensual de Trabajos</h1>
  <p>' . $nombreMes . ' ' . $anio . '</p>
</div>

<div class="tarjeta">
  <div class="tarjeta-header">DATOS DEL CLIENTE</div>
  <div class="tarjeta-body">
    <table style="width:100%;">
      <tr>
        <td style="width:50%;vertical-align:top;">
          <div class="dato"><span class="etq">Nombre:</span> <span class="val-nombre">' . $nombreCliente . '</span></div>
          <div class="dato"><span class="etq">RUC:</span> <span class="val">' . $rucCliente . '</span></div>
          <div class="dato"><span class="etq">Teléfono:</span> <span class="val">' . $telCliente . '</span></div>
        </td>
        <td style="width:50%;vertical-align:top;">
          <div class="dato"><span class="etq">Email:</span> <span class="val">' . $emailCliente . '</span></div>
          <div class="dato"><span class="etq">Dirección:</span> <span class="val">' . $dirCliente . '</span></div>
          <div class="dato"><span class="etq">Período:</span> <span class="val">' . $nombreMes . ' ' . $anio . '</span></div>
        </td>
      </tr>
    </table>
  </div>
</div>

<table class="trab">
  <thead>
    <tr>
      <th style="width:14%">FECHA</th>
      <th style="width:22%">TÉCNICO</th>
      <th style="width:49%">COMENTARIO / DESCRIPCIÓN</th>
      <th style="width:15%">HORAS</th>
    </tr>
  </thead>
  <tbody>
    ' . $filasHTML . '
    <tr class="fila-total">
      <td colspan="3" style="text-align:right;letter-spacing:1px;">TOTAL DE HORAS</td>
      <td style="text-align:center;">' . $totalFormateado . '</td>
    </tr>
  </tbody>
</table>

</body>
</html>';

// Generar PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$nombreArchivo = 'reporte_'
    . preg_replace('/[^a-z0-9]/i', '_', strtolower($cliente['nombre']))
    . '_' . $mes . '_' . $anio . '.pdf';

$dompdf->stream($nombreArchivo, ['Attachment' => true]);