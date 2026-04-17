<?php
require 'config.php';
$pageTitle = "Inicio";
require 'header.php';

// Contadores
$clientes = $conn->query("SELECT COUNT(*) AS c FROM clientes")->fetch_assoc()['c'] ?? 0;
$tecnicos = $conn->query("SELECT COUNT(*) AS c FROM tecnicos")->fetch_assoc()['c'] ?? 0;
$trabajos = $conn->query("SELECT COUNT(*) AS c FROM trabajos")->fetch_assoc()['c'] ?? 0;

$mes  = (int)date('n');
$anio = (int)date('Y');

// Horas del mes actual
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(horas), 0) AS total_horas
    FROM trabajos
    WHERE MONTH(fecha) = ? AND YEAR(fecha) = ?
");
$stmt->bind_param('ii', $mes, $anio);
$stmt->execute();
$totalMes = (float)($stmt->get_result()->fetch_assoc()['total_horas'] ?? 0);

// Últimos 5 trabajos
$ultimos = $conn->query("
    SELECT t.fecha, t.horas, t.comentario,
           c.nombre AS cliente,
           tc.nombre AS tecnico
    FROM trabajos t
    JOIN clientes c  ON c.id  = t.cliente_id
    JOIN tecnicos tc ON tc.id = t.tecnico_id
    ORDER BY t.id DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$meses_es = [
    1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
];
?>

<!-- ── Page header ── -->
<div class="page-header">
  <i class="bi bi-speedometer2"></i>
  <div>
    <h4>Panel de control</h4>
    <span>Bienvenido al sistema de partes de trabajo &mdash; <?= $meses_es[$mes] ?> <?= $anio ?></span>
  </div>
</div>

<!-- ── Stat cards ── -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
      <div>
        <div class="stat-label">Clientes</div>
        <div class="stat-value"><?= (int)$clientes ?></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-person-badge-fill"></i></div>
      <div>
        <div class="stat-label">Técnicos</div>
        <div class="stat-value"><?= (int)$tecnicos ?></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-clipboard2-check-fill"></i></div>
      <div>
        <div class="stat-label">Trabajos totales</div>
        <div class="stat-value"><?= (int)$trabajos ?></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
      <div>
        <div class="stat-label">Horas este mes</div>
        <div class="stat-value"><?= number_format($totalMes, 1) ?><small style="font-size:.9rem;color:#888"> h</small></div>
      </div>
    </div>
  </div>
</div>

<!-- ── Acciones rápidas + últimos trabajos ── -->
<div class="row g-3">

  <!-- Acciones -->
  <div class="col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-body d-flex flex-column gap-2">
        <h6 class="fw-bold mb-2" style="color:#111">
          <i class="bi bi-lightning-charge-fill" style="color:#2ecc71"></i> Acciones rápidas
        </h6>
        <a href="trabajos.php" class="btn btn-cm w-100 text-start">
          <i class="bi bi-plus-circle me-2"></i> Registrar trabajo
        </a>
        <a href="clientes.php" class="btn btn-outline-cm w-100 text-start">
          <i class="bi bi-people me-2"></i> Administrar clientes
        </a>
        <a href="tecnicos.php" class="btn btn-outline-cm w-100 text-start">
          <i class="bi bi-person-badge me-2"></i> Administrar técnicos
        </a>
        <a href="reporte.php?mes=<?= $mes ?>&anio=<?= $anio ?>" class="btn btn-outline-cm w-100 text-start">
          <i class="bi bi-file-earmark-bar-graph me-2"></i> Ver reportes del mes
        </a>
        <a href="tarifas.php" class="btn btn-outline-cm w-100 text-start">
          <i class="bi bi-currency-dollar me-2"></i> Tarifas por cliente
        </a>
        <a href="reporte_montos.php?mes=<?= $mes ?>&anio=<?= $anio ?>" class="btn btn-outline-cm w-100 text-start">
          <i class="bi bi-receipt-cutoff me-2"></i> Reporte de montos
        </a>

        <hr class="my-1">
        <div class="alert alert-secondary mb-0 py-2 px-3" style="font-size:.83rem">
          <i class="bi bi-info-circle me-1"></i>
          Cargá primero <strong>Clientes</strong> y <strong>Técnicos</strong> para poder registrar trabajos.
        </div>
      </div>
    </div>
  </div>

  <!-- Últimos trabajos -->
  <div class="col-lg-8">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h6 class="fw-bold mb-3" style="color:#111">
          <i class="bi bi-clock me-1" style="color:#2ecc71"></i> Últimos trabajos registrados
        </h6>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead>
              <tr style="background:#111;color:#fff">
                <th class="ps-3">Fecha</th>
                <th>Cliente</th>
                <th>Técnico</th>
                <th>Comentario</th>
                <th class="text-end pe-3">Horas</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($ultimos)): ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-3">
                    Aún no hay trabajos registrados.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($ultimos as $u): ?>
                  <tr>
                    <td class="ps-3"><?= date('d/m/Y', strtotime($u['fecha'])) ?></td>
                    <td><?= h($u['cliente']) ?></td>
                    <td><?= h($u['tecnico']) ?></td>
                    <td class="text-muted small"><?= h($u['comentario'] ?: '—') ?></td>
                    <td class="text-end pe-3 fw-semibold">
                      <?= number_format((float)$u['horas'], 2, ',', '.') ?> h
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="mt-3 text-end">
          <a href="trabajos.php" class="btn btn-sm btn-outline-cm">
            Ver todos <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
  </div>

</div>

<?php require 'footer.php'; ?>