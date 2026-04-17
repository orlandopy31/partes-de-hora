<?php
/**
 * pdf/reporte_montos.php
 * Genera PDF profesional con detalle de trabajos por cliente usando Dompdf.
 * Instalación: composer require dompdf/dompdf
 */
require '../config.php';
require '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$mes        = (int)($_GET['mes']        ?? date('n'));
$anio       = (int)($_GET['anio']       ?? date('Y'));
$cliente_id = (int)($_GET['cliente_id'] ?? 0);
$moneda     = in_array($_GET['moneda'] ?? '', ['PYG','USD','AMBAS'])
              ? $_GET['moneda'] : 'PYG';

$meses_es = [
    1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
];

// ── Resumen por cliente ──────────────────────────────────────────────────────
$where_cliente = $cliente_id > 0 ? "AND t.cliente_id = $cliente_id" : "";
$sql = "
    SELECT c.id AS cliente_id, c.nombre AS cliente,
           COALESCE(SUM(t.horas),0) AS total_horas,
           tar.precio_hora_pyg, tar.precio_hora_usd,
           COALESCE(SUM(t.horas),0)*COALESCE(tar.precio_hora_pyg,0) AS monto_pyg,
           COALESCE(SUM(t.horas),0)*COALESCE(tar.precio_hora_usd,0) AS monto_usd
    FROM clientes c
    LEFT JOIN trabajos t ON t.cliente_id=c.id AND MONTH(t.fecha)=? AND YEAR(t.fecha)=?
    LEFT JOIN tarifas tar ON tar.cliente_id=c.id
    WHERE 1=1 $where_cliente
    GROUP BY c.id,c.nombre,tar.precio_hora_pyg,tar.precio_hora_usd
    HAVING total_horas > 0
    ORDER BY c.nombre
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $mes, $anio);
$stmt->execute();
$resumen = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Detalle de trabajos ──────────────────────────────────────────────────────
$sql2 = "
    SELECT t.cliente_id, t.fecha, t.horas, t.comentario, tc.nombre AS tecnico
    FROM trabajos t
    JOIN tecnicos tc ON tc.id=t.tecnico_id
    WHERE MONTH(t.fecha)=? AND YEAR(t.fecha)=?
    " . ($cliente_id > 0 ? "AND t.cliente_id=$cliente_id" : "") . "
    ORDER BY t.cliente_id, t.fecha ASC
";
$stmt = $conn->prepare($sql2);
$stmt->bind_param('ii', $mes, $anio);
$stmt->execute();
$detalle_rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$detalle = [];
foreach ($detalle_rows as $d) $detalle[$d['cliente_id']][] = $d;

$gran_horas = 0; $gran_pyg = 0; $gran_usd = 0;
foreach ($resumen as $f) {
    $gran_horas += $f['total_horas'];
    $gran_pyg   += $f['monto_pyg'];
    $gran_usd   += $f['monto_usd'];
}

$col_pyg = in_array($moneda, ['PYG','AMBAS']);
$col_usd = in_array($moneda, ['USD','AMBAS']);
$moneda_label = $moneda==='AMBAS' ? 'Guaraníes y Dólares'
              : ($moneda==='PYG'  ? 'Guaraníes (PYG)' : 'Dólares (USD)');

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
  @page {
    margin: 22mm 20mm 20mm 20mm;
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 10px;
    color: #2d2d2d;
    background: #fff;
  }

  /* ── Encabezado ── */
  .page-header {
    border-bottom: 3px solid #2ecc71;
    padding: 14px 18px 16px 18px;
    margin-bottom: 20px;
    display: table;
    width: 100%;
    background: #fafafa;
    border-radius: 6px 6px 0 0;
  }
  .header-left  { display:table-cell; vertical-align:middle; }
  .header-right { display:table-cell; text-align:right; vertical-align:middle; width:40%; }
  .brand-name   { font-size:20px; font-weight:bold; color:#111; letter-spacing:1px; }
  .brand-dot    { color:#2ecc71; }
  .brand-sub    { font-size:9px; color:#888; margin-top:2px; }
  .report-title { font-size:13px; font-weight:bold; color:#1a1a1a; }
  .report-meta  { font-size:9px; color:#888; margin-top:3px; }

  /* ── Tarjetas resumen ── */
  .summary-row { display:table; width:100%; border-collapse:separate; border-spacing:8px; margin-bottom:20px; }
  .sum-cell    { display:table-cell; background:#f7f7f7; border:1px solid #e8e8e8; border-radius:5px; padding:10px 16px; }
  .sum-cell.green { border-top:3px solid #2ecc71; }
  .sum-cell.blue  { border-top:3px solid #3498db; }
  .sum-cell.gray  { border-top:3px solid #95a5a6; }
  .sum-label { font-size:8px; color:#999; text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px; }
  .sum-value { font-size:14px; font-weight:bold; color:#111; }
  .sum-value.green { color:#27ae60; }
  .sum-value.blue  { color:#2980b9; }

  /* ── Sección cliente ── */
  .client-block  { margin-bottom:20px; border:1px solid #e0e0e0; border-radius:6px; overflow:hidden; }
  .client-header {
    background: #f0faf4;
    border-bottom: 2px solid #2ecc71;
    padding: 10px 16px;
    display: table;
    width: 100%;
  }
  .ch-left   { display:table-cell; vertical-align:middle; }
  .ch-right  { display:table-cell; text-align:right; vertical-align:middle; }
  .client-name { font-size:11px; font-weight:bold; color:#1a1a1a; }
  .client-tag  { display:inline-block; background:#2ecc71; color:#fff; font-size:8px;
                 padding:2px 7px; border-radius:10px; margin-left:6px; vertical-align:middle; }
  .client-totals { font-size:9px; color:#555; }
  .client-totals strong { color:#27ae60; }
  .client-totals .usd  { color:#2980b9; }

  /* ── Tabla trabajos ── */
  table { width:100%; border-collapse:collapse; }
  thead tr { background:#fafafa; }
  thead th {
    padding: 8px 10px;
    font-size: 8.5px;
    font-weight: bold;
    color: #666;
    text-transform: uppercase;
    letter-spacing: .4px;
    border-bottom: 1px solid #ddd;
    text-align: left;
  }
  thead th.r { text-align:right; }
  tbody tr { border-bottom:1px solid #f0f0f0; }
  tbody tr:nth-child(even) { background:#fafffe; }
  tbody td { padding:7px 10px; font-size:9.5px; color:#333; vertical-align:top; }
  tbody td.r { text-align:right; }
  tbody td.green { color:#27ae60; font-weight:bold; }
  tbody td.blue  { color:#2980b9; font-weight:bold; }
  .comentario { color:#666; font-style:italic; }
  tfoot tr { background:#f0faf4; }
  tfoot td {
    padding: 8px 10px;
    font-size: 9.5px;
    font-weight: bold;
    border-top: 2px solid #2ecc71;
    text-align: right;
  }
  tfoot td.label { text-align:right; color:#555; }
  tfoot td.green { color:#27ae60; }
  tfoot td.blue  { color:#2980b9; }

  /* ── Total general ── */
  .grand-total {
    background:#f0faf4;
    border:2px solid #2ecc71;
    border-radius:6px;
    padding:14px 18px;
    margin-top:18px;
    display:table;
    width:100%;
  }
  .gt-left  { display:table-cell; vertical-align:middle; }
  .gt-right { display:table-cell; text-align:right; vertical-align:middle; }
  .gt-title { font-size:11px; font-weight:bold; color:#1a1a1a; }
  .gt-sub   { font-size:8.5px; color:#888; margin-top:2px; }
  .gt-amounts { font-size:10px; }
  .gt-amounts .green { color:#27ae60; font-weight:bold; margin-left:12px; }
  .gt-amounts .blue  { color:#2980b9; font-weight:bold; margin-left:12px; }

  /* ── Footer ── */
  .pdf-footer {
    margin-top: 22px;
    padding-top: 10px;
    border-top: 1px solid #eee;
    font-size: 8px;
    color: #bbb;
    display: table;
    width: 100%;
  }
  .pf-left  { display:table-cell; }
  .pf-right { display:table-cell; text-align:right; }
  .badge-warn { background:#f39c12; color:#fff; padding:1px 5px; border-radius:3px; font-size:8px; }
</style>
</head>
<body>

<!-- ── Encabezado ── -->
<div class="page-header">
  <div class="header-left">
    <div class="brand-name">&#9632;<span class="brand-dot"> CYBER</span>MATICA</div>
    <div class="brand-sub">Sistema de Partes de Trabajo</div>
  </div>
  <div class="header-right">
    <div class="report-title">Reporte de Montos Trabajados</div>
    <div class="report-meta">
      Período: <strong><?= $meses_es[$mes] ?> <?= $anio ?></strong>
      &nbsp;|&nbsp; Moneda: <strong><?= $moneda_label ?></strong>
      &nbsp;|&nbsp; Generado: <?= date('d/m/Y H:i') ?>
    </div>
  </div>
</div>

<!-- ── Tarjetas resumen ── -->
<div class="summary-row">
  <div class="sum-cell gray">
    <div class="sum-label">Clientes</div>
    <div class="sum-value"><?= count($resumen) ?></div>
  </div>
  <div class="sum-cell gray">
    <div class="sum-label">Total horas</div>
    <div class="sum-value"><?= number_format($gran_horas,2,',','.') ?> h</div>
  </div>
  <?php if ($col_pyg): ?>
  <div class="sum-cell green">
    <div class="sum-label">Total Guaraníes</div>
    <div class="sum-value green">&#8370; <?= number_format($gran_pyg,0,',','.') ?></div>
  </div>
  <?php endif; ?>
  <?php if ($col_usd): ?>
  <div class="sum-cell blue">
    <div class="sum-label">Total Dólares</div>
    <div class="sum-value blue">$ <?= number_format($gran_usd,2,',','.') ?></div>
  </div>
  <?php endif; ?>
</div>

<!-- ── Detalle por cliente ── -->
<?php if (empty($resumen)): ?>
  <p style="text-align:center;color:#999;padding:20px">No hay registros para el período seleccionado.</p>
<?php else: ?>
  <?php foreach ($resumen as $f):
        $trabajos_cliente = $detalle[$f['cliente_id']] ?? [];
  ?>
  <div class="client-block">

    <!-- Cabecera cliente -->
    <div class="client-header">
      <div class="ch-left">
        <span class="client-name"><?= htmlspecialchars($f['cliente']) ?></span>
        <span class="client-tag"><?= count($trabajos_cliente) ?> trabajo<?= count($trabajos_cliente)!==1?'s':'' ?></span>
      </div>
      <div class="ch-right client-totals">
        <strong><?= number_format((float)$f['total_horas'],2,',','.') ?> h</strong>
        <?php if ($col_pyg && $f['precio_hora_pyg']!==null): ?>
          &nbsp;&nbsp; &#8370;<?= number_format((float)$f['precio_hora_pyg'],0,',','.') ?>/h
          &nbsp; <strong>&#8370; <?= number_format((float)$f['monto_pyg'],0,',','.') ?></strong>
        <?php endif; ?>
        <?php if ($col_usd && $f['precio_hora_usd']!==null): ?>
          &nbsp;&nbsp; $<?= number_format((float)$f['precio_hora_usd'],2,',','.') ?>/h
          &nbsp; <strong class="usd">$ <?= number_format((float)$f['monto_usd'],2,',','.') ?></strong>
        <?php endif; ?>
        <?php if ($f['precio_hora_pyg']===null && $f['precio_hora_usd']===null): ?>
          <span class="badge-warn">Sin tarifa</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Tabla de trabajos -->
    <table>
      <thead>
        <tr>
          <th style="width:70px">Fecha</th>
          <th style="width:90px">Técnico</th>
          <th>Trabajo realizado</th>
          <th class="r" style="width:50px">Horas</th>
          <?php if ($col_pyg): ?><th class="r" style="width:80px">Monto &#8370;</th><?php endif; ?>
          <?php if ($col_usd): ?><th class="r" style="width:70px">Monto $</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($trabajos_cliente)): ?>
          <tr><td colspan="<?= 4+($col_pyg?1:0)+($col_usd?1:0) ?>" style="text-align:center;color:#bbb;padding:8px">Sin registros</td></tr>
        <?php else: ?>
          <?php foreach ($trabajos_cliente as $t): ?>
          <tr>
            <td><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
            <td><?= htmlspecialchars($t['tecnico']) ?></td>
            <td class="comentario"><?= htmlspecialchars($t['comentario'] ?: '—') ?></td>
            <td class="r green"><?= number_format((float)$t['horas'],2,',','.') ?></td>
            <?php if ($col_pyg): ?>
              <td class="r green">
                <?= $f['precio_hora_pyg']!==null
                    ? '&#8370; '.number_format($t['horas']*$f['precio_hora_pyg'],0,',','.')
                    : '—' ?>
              </td>
            <?php endif; ?>
            <?php if ($col_usd): ?>
              <td class="r blue">
                <?= $f['precio_hora_usd']!==null
                    ? '$ '.number_format($t['horas']*$f['precio_hora_usd'],2,',','.')
                    : '—' ?>
              </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="3" class="label">Subtotal <?= htmlspecialchars($f['cliente']) ?>:</td>
          <td class="green"><?= number_format((float)$f['total_horas'],2,',','.') ?> h</td>
          <?php if ($col_pyg): ?>
            <td class="green">
              <?= $f['precio_hora_pyg']!==null ? '&#8370; '.number_format((float)$f['monto_pyg'],0,',','.') : '—' ?>
            </td>
          <?php endif; ?>
          <?php if ($col_usd): ?>
            <td class="blue">
              <?= $f['precio_hora_usd']!==null ? '$ '.number_format((float)$f['monto_usd'],2,',','.') : '—' ?>
            </td>
          <?php endif; ?>
        </tr>
      </tfoot>
    </table>

  </div>
  <?php endforeach; ?>

  <!-- ── Total general ── -->
  <div class="grand-total">
    <div class="gt-left">
      <div class="gt-title">TOTAL GENERAL &mdash; <?= $meses_es[$mes] ?> <?= $anio ?></div>
      <div class="gt-sub"><?= count($resumen) ?> cliente<?= count($resumen)!==1?'s':'' ?> &middot; <?= count($detalle_rows) ?> trabajo<?= count($detalle_rows)!==1?'s':'' ?></div>
    </div>
    <div class="gt-right gt-amounts">
      <strong><?= number_format($gran_horas,2,',','.') ?> h</strong>
      <?php if ($col_pyg): ?>
        <strong class="green">&#8370; <?= number_format($gran_pyg,0,',','.') ?></strong>
      <?php endif; ?>
      <?php if ($col_usd): ?>
        <strong class="blue">$ <?= number_format($gran_usd,2,',','.') ?></strong>
      <?php endif; ?>
    </div>
  </div>

<?php endif; ?>

<!-- ── Footer ── -->
<div class="pdf-footer">
  <div class="pf-left">Cybermatica &mdash; Sistema de Partes de Trabajo</div>
  <div class="pf-right">Generado el <?= date('d/m/Y') ?> a las <?= date('H:i') ?></div>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = sprintf('reporte_montos_%s_%04d%02d.pdf', $moneda, $anio, $mes);
$dompdf->stream($filename, ['Attachment' => true]);