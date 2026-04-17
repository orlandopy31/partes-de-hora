<?php
require 'config.php';
$pageTitle = "Reporte de montos";

$mes        = (int)($_GET['mes']        ?? date('n'));
$anio       = (int)($_GET['anio']       ?? date('Y'));
$cliente_id = (int)($_GET['cliente_id'] ?? 0);   // 0 = todos
$moneda     = $_GET['moneda'] ?? 'PYG';           // PYG | USD

$meses_es = [
    1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
];

$clientes = $conn->query("SELECT id, nombre FROM clientes ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// ── Consulta principal ───────────────────────────────────────────────────────
$where_cliente = $cliente_id > 0 ? "AND t.cliente_id = $cliente_id" : "";

$sql = "
    SELECT
        c.id                                          AS cliente_id,
        c.nombre                                      AS cliente,
        COALESCE(SUM(t.horas), 0)                     AS total_horas,
        tar.precio_hora_pyg,
        tar.precio_hora_usd,
        COALESCE(SUM(t.horas), 0) * COALESCE(tar.precio_hora_pyg, 0) AS monto_pyg,
        COALESCE(SUM(t.horas), 0) * COALESCE(tar.precio_hora_usd, 0) AS monto_usd
    FROM   clientes c
    LEFT JOIN trabajos t
           ON t.cliente_id = c.id
          AND MONTH(t.fecha) = ?
          AND YEAR(t.fecha)  = ?
    LEFT JOIN tarifas tar ON tar.cliente_id = c.id
    WHERE  1=1 $where_cliente
    GROUP  BY c.id, c.nombre, tar.precio_hora_pyg, tar.precio_hora_usd
    HAVING total_horas > 0
    ORDER  BY c.nombre
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $mes, $anio);
$stmt->execute();
$filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Totales generales
$gran_horas = 0;
$gran_pyg   = 0;
$gran_usd   = 0;
foreach ($filas as $f) {
    $gran_horas += $f['total_horas'];
    $gran_pyg   += $f['monto_pyg'];
    $gran_usd   += $f['monto_usd'];
}

require 'header.php';
?>

<div class="page-header">
  <i class="bi bi-receipt-cutoff"></i>
  <div>
    <h4>Reporte de montos trabajados</h4>
    <span>Horas × tarifa por cliente &mdash; <?= $meses_es[$mes] ?> <?= $anio ?></span>
  </div>
</div>

<!-- ── Filtros ── -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <form class="row g-2 align-items-end" method="get">

      <div class="col-md-3">
        <label class="form-label">Cliente</label>
        <select class="form-select" name="cliente_id">
          <option value="0">Todos los clientes</option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= (int)$c['id'] ?>"
              <?= $cliente_id === (int)$c['id'] ? 'selected' : '' ?>>
              <?= h($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">Mes</label>
        <select class="form-select" name="mes">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $m === $mes ? 'selected' : '' ?>>
              <?= $meses_es[$m] ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">Año</label>
        <input class="form-control" type="number" name="anio"
               value="<?= $anio ?>" min="2000" max="2099">
      </div>

      <div class="col-md-2">
        <label class="form-label">Moneda</label>
        <select class="form-select" name="moneda">
          <option value="PYG" <?= $moneda === 'PYG' ? 'selected' : '' ?>>₲ Guaraníes (PYG)</option>
          <option value="USD" <?= $moneda === 'USD' ? 'selected' : '' ?>>$ Dólares (USD)</option>
          <option value="AMBAS" <?= $moneda === 'AMBAS' ? 'selected' : '' ?>>Ambas monedas</option>
        </select>
      </div>

      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-cm w-100">
          <i class="bi bi-search me-1"></i> Ver reporte
        </button>
        <?php if (!empty($filas)): ?>
          <a class="btn btn-outline-cm"
             href="pdf/reporte_montos.php?cliente_id=<?= $cliente_id ?>&mes=<?= $mes ?>&anio=<?= $anio ?>&moneda=<?= $moneda ?>"
             target="_blank" title="Descargar PDF">
            <i class="bi bi-file-earmark-pdf"></i>
          </a>
        <?php endif; ?>
      </div>

    </form>
  </div>
</div>

<!-- ── Resumen general ── -->
<?php if (!empty($filas)): ?>

<div class="row g-3 mb-4">
  <div class="col-sm-4">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
      <div>
        <div class="stat-label">Clientes con horas</div>
        <div class="stat-value"><?= count($filas) ?></div>
      </div>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
      <div>
        <div class="stat-label">Total horas</div>
        <div class="stat-value"><?= number_format($gran_horas, 2, ',', '.') ?><small style="font-size:.9rem;color:#888"> h</small></div>
      </div>
    </div>
  </div>
  <?php if ($moneda === 'PYG' || $moneda === 'AMBAS'): ?>
  <div class="col-sm-4">
    <div class="stat-card">
      <div class="stat-icon" style="background:#27ae60"><i class="bi bi-cash-stack"></i></div>
      <div>
        <div class="stat-label">Total en Guaraníes</div>
        <div class="stat-value" style="font-size:1.2rem">₲ <?= number_format($gran_pyg, 0, ',', '.') ?></div>
      </div>
    </div>
  </div>
  <?php endif; ?>
  <?php if ($moneda === 'USD' || $moneda === 'AMBAS'): ?>
  <div class="col-sm-4">
    <div class="stat-card">
      <div class="stat-icon" style="background:#2980b9"><i class="bi bi-currency-dollar"></i></div>
      <div>
        <div class="stat-label">Total en Dólares</div>
        <div class="stat-value" style="font-size:1.2rem">$ <?= number_format($gran_usd, 2, ',', '.') ?></div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ── Tabla detalle ── -->
<div class="card shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead style="background:#111;color:#fff">
          <tr>
            <th class="ps-3">Cliente</th>
            <th class="text-end">Horas trabajadas</th>
            <?php if ($moneda === 'PYG' || $moneda === 'AMBAS'): ?>
              <th class="text-end">Tarifa ₲/h</th>
              <th class="text-end">Monto en ₲</th>
            <?php endif; ?>
            <?php if ($moneda === 'USD' || $moneda === 'AMBAS'): ?>
              <th class="text-end">Tarifa $/h</th>
              <th class="text-end">Monto en $</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($filas as $f): ?>
            <tr>
              <td class="ps-3 fw-semibold"><?= h($f['cliente']) ?></td>
              <td class="text-end"><?= number_format((float)$f['total_horas'], 2, ',', '.') ?> h</td>

              <?php if ($moneda === 'PYG' || $moneda === 'AMBAS'): ?>
                <td class="text-end text-muted small">
                  <?= $f['precio_hora_pyg'] !== null
                      ? '₲ ' . number_format((float)$f['precio_hora_pyg'], 0, ',', '.')
                      : '<span class="badge bg-warning text-dark">Sin tarifa</span>' ?>
                </td>
                <td class="text-end fw-bold" style="color:#27ae60">
                  <?= $f['precio_hora_pyg'] !== null
                      ? '₲ ' . number_format((float)$f['monto_pyg'], 0, ',', '.')
                      : '—' ?>
                </td>
              <?php endif; ?>

              <?php if ($moneda === 'USD' || $moneda === 'AMBAS'): ?>
                <td class="text-end text-muted small">
                  <?= $f['precio_hora_usd'] !== null
                      ? '$ ' . number_format((float)$f['precio_hora_usd'], 2, ',', '.')
                      : '<span class="badge bg-warning text-dark">Sin tarifa</span>' ?>
                </td>
                <td class="text-end fw-bold" style="color:#2980b9">
                  <?= $f['precio_hora_usd'] !== null
                      ? '$ ' . number_format((float)$f['monto_usd'], 2, ',', '.')
                      : '—' ?>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <!-- Totales -->
        <tfoot style="background:#f8f8f8;font-weight:700">
          <tr>
            <td class="ps-3">TOTAL</td>
            <td class="text-end"><?= number_format($gran_horas, 2, ',', '.') ?> h</td>
            <?php if ($moneda === 'PYG' || $moneda === 'AMBAS'): ?>
              <td></td>
              <td class="text-end" style="color:#27ae60">
                ₲ <?= number_format($gran_pyg, 0, ',', '.') ?>
              </td>
            <?php endif; ?>
            <?php if ($moneda === 'USD' || $moneda === 'AMBAS'): ?>
              <td></td>
              <td class="text-end" style="color:#2980b9">
                $ <?= number_format($gran_usd, 2, ',', '.') ?>
              </td>
            <?php endif; ?>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

<?php elseif (isset($_GET['mes'])): ?>
  <div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    No hay trabajos registrados para el período seleccionado.
  </div>
<?php endif; ?>

<?php require 'footer.php'; ?>