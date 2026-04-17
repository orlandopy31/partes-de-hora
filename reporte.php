<?php
require 'config.php';
$pageTitle = "Reportes";

$mes        = (int)($_GET['mes']        ?? date('n'));
$anio       = (int)($_GET['anio']       ?? date('Y'));
$cliente_id = (int)($_GET['cliente_id'] ?? 0);

$clientes = $conn->query("SELECT id, nombre FROM clientes ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Nombres de meses en español
$meses_es = [
    1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
];

// Datos del reporte
$trabajos        = [];
$total_horas     = 0;
$nombre_cliente  = '';

if ($cliente_id > 0) {

    // Nombre del cliente
    $stmtC = $conn->prepare("SELECT nombre FROM clientes WHERE id = ?");
    $stmtC->bind_param('i', $cliente_id);
    $stmtC->execute();
    $stmtC->bind_result($nombre_cliente);
    $stmtC->fetch();
    $stmtC->close();

    // Trabajos del mes/año para ese cliente, incluyendo nombre del técnico
    $sql = "SELECT t.id,
                   t.fecha,
                   t.horas,
                   t.comentario,
                   tc.nombre AS tecnico
            FROM   trabajos t
            JOIN   tecnicos tc ON tc.id = t.tecnico_id
            WHERE  t.cliente_id = ?
              AND  MONTH(t.fecha) = ?
              AND  YEAR(t.fecha)  = ?
            ORDER  BY t.fecha ASC, t.id ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $cliente_id, $mes, $anio);
    $stmt->execute();
    $result   = $stmt->get_result();
    $trabajos = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($trabajos as $t) {
        $total_horas += $t['horas'];
    }
}

require 'header.php';
?>

<!-- ── Formulario de filtros ── -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <h5 class="mb-3">Reporte mensual por cliente</h5>

    <form class="row g-2 align-items-end" method="get">

      <div class="col-md-4">
        <label class="form-label">Cliente</label>
        <select class="form-select" name="cliente_id" required>
          <option value="">Seleccionar...</option>
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
               value="<?= (int)$anio ?>" min="2000" max="2099" />
      </div>

      <div class="col-md-4 d-flex gap-2">
        <button class="btn btn-outline-primary">
          <i class="bi bi-search"></i> Ver
        </button>
        <?php if ($cliente_id > 0): ?>
          <a class="btn btn-primary"
             href="pdf/reporte_cliente_mes.php?cliente_id=<?= (int)$cliente_id ?>&mes=<?= (int)$mes ?>&anio=<?= (int)$anio ?>"
             target="_blank">
            <i class="bi bi-file-earmark-pdf"></i> Descargar PDF
          </a>
        <?php endif; ?>
      </div>

    </form>

    <div class="alert alert-secondary mt-3 mb-0">
      Para que el PDF funcione, ejecutá <strong>composer install</strong> (ver README.txt).
    </div>
  </div>
</div>

<!-- ── Reporte ── -->
<?php if ($cliente_id > 0): ?>

  <!-- Encabezado del período -->
  <div class="card shadow-sm mb-3">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <div class="text-muted small">Cliente</div>
        <strong class="fs-5"><?= h($nombre_cliente) ?></strong>
      </div>
      <div class="text-center">
        <div class="text-muted small">Período</div>
        <strong class="fs-5"><?= $meses_es[$mes] ?> <?= $anio ?></strong>
      </div>
      <div class="text-end">
        <div class="text-muted small">Registros</div>
        <strong class="fs-5"><?= count($trabajos) ?></strong>
      </div>
    </div>
  </div>

  <?php if (empty($trabajos)): ?>

    <div class="alert alert-info">
      No hay trabajos registrados para <strong><?= h($nombre_cliente) ?></strong>
      en <?= $meses_es[$mes] ?> <?= $anio ?>.
    </div>

  <?php else: ?>

    <!-- Tabla de trabajos -->
    <div class="card shadow-sm mb-3">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped mb-0 align-middle">
            <thead class="table-dark">
              <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Técnico</th>
                <th>Comentario</th>
                <th class="text-end">Horas</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($trabajos as $i => $t): ?>
                <tr>
                  <td class="text-muted small"><?= $i + 1 ?></td>
                  <td><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
                  <td><?= h(trim($t['tecnico'])) ?></td>
                  <td><?= h($t['comentario'] ?: '—') ?></td>
                  <td class="text-end fw-semibold">
                    <?= number_format((float)$t['horas'], 2, ',', '.') ?> hs
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-secondary">
              <tr>
                <td colspan="4" class="text-end fw-bold">Total horas:</td>
                <td class="text-end fw-bold">
                  <?= number_format($total_horas, 2, ',', '.') ?> hs
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Resumen -->
    <div class="row g-3 mb-2">
      <div class="col-md-6">
        <div class="card border-0 bg-light text-center p-3">
          <div class="text-muted small mb-1">Total de trabajos</div>
          <div class="fw-bold fs-4"><?= count($trabajos) ?></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-0 bg-primary bg-opacity-10 text-center p-3">
          <div class="text-muted small mb-1">Total de horas</div>
          <div class="fw-bold fs-4 text-primary">
            <?= number_format($total_horas, 2, ',', '.') ?> hs
          </div>
        </div>
      </div>
    </div>

  <?php endif; ?>

<?php endif; ?>

<?php require 'footer.php'; ?>