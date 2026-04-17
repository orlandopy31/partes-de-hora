<?php
require 'config.php';
$pageTitle = "Tarifas por cliente";
$msg  = '';
$tipo = 'info';

// ── GUARDAR / ACTUALIZAR tarifa ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id      = (int)post('cliente_id', 0);
    $precio_hora_pyg = post('precio_hora_pyg', '') === '' ? null : (int)str_replace('.', '', post('precio_hora_pyg', ''));
    $precio_hora_usd = post('precio_hora_usd', '') === '' ? null : (float)post('precio_hora_usd', '');
    $vigente_desde   = post('vigente_desde', date('Y-m-d'));

    if ($cliente_id <= 0) {
        $msg  = "Seleccioná un cliente.";
        $tipo = "warning";
    } elseif ($precio_hora_pyg === null && $precio_hora_usd === null) {
        $msg  = "Ingresá al menos un precio (PYG o USD).";
        $tipo = "warning";
    } else {
        // INSERT o UPDATE si ya existe
        $stmt = $conn->prepare("
            INSERT INTO tarifas (cliente_id, precio_hora_pyg, precio_hora_usd, vigente_desde)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                precio_hora_pyg = VALUES(precio_hora_pyg),
                precio_hora_usd = VALUES(precio_hora_usd),
                vigente_desde   = VALUES(vigente_desde)
        ");
        $stmt->bind_param('idds', $cliente_id, $precio_hora_pyg, $precio_hora_usd, $vigente_desde);
        $stmt->execute();
        $msg  = "Tarifa guardada correctamente.";
        $tipo = "success";
    }
}

// ── ELIMINAR tarifa ──────────────────────────────────────────────────────────
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $conn->prepare("DELETE FROM tarifas WHERE id = ?")->execute([$id]) ;
    $stmt = $conn->prepare("DELETE FROM tarifas WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $msg  = "Tarifa eliminada.";
    $tipo = "danger";
}

$clientes = $conn->query("SELECT id, nombre FROM clientes ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

$tarifas = $conn->query("
    SELECT t.id, c.nombre AS cliente, t.precio_hora_pyg, t.precio_hora_usd, t.vigente_desde
    FROM   tarifas t
    JOIN   clientes c ON c.id = t.cliente_id
    ORDER  BY c.nombre
")->fetch_all(MYSQLI_ASSOC);

require 'header.php';
?>

<div class="page-header">
  <i class="bi bi-currency-dollar"></i>
  <div>
    <h4>Tarifas por cliente</h4>
    <span>Definí el precio por hora en Guaraníes y/o Dólares para cada cliente</span>
  </div>
</div>

<div class="row g-3">

  <!-- ── Formulario ── -->
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle me-1" style="color:#2ecc71"></i> Asignar tarifa</h6>

        <?php if ($msg): ?>
          <div class="alert alert-<?= $tipo ?> py-2"><?= h($msg) ?></div>
        <?php endif; ?>

        <form method="post">
          <div class="mb-3">
            <label class="form-label">Cliente *</label>
            <select class="form-select" name="cliente_id" required>
              <option value="">Seleccionar...</option>
              <?php foreach ($clientes as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= h($c['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">
              <i class="bi bi-cash-coin me-1"></i> Precio/hora en Guaraníes (PYG)
            </label>
            <div class="input-group">
              <span class="input-group-text">₲</span>
              <input class="form-control" type="text" name="precio_hora_pyg"
                     placeholder="Ej: 150000" inputmode="numeric">
            </div>
            <div class="form-text">Dejá vacío si no aplica.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">
              <i class="bi bi-currency-dollar me-1"></i> Precio/hora en Dólares (USD)
            </label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input class="form-control" type="number" name="precio_hora_usd"
                     step="0.01" min="0" placeholder="Ej: 20.00">
            </div>
            <div class="form-text">Dejá vacío si no aplica.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Vigente desde</label>
            <input class="form-control" type="date" name="vigente_desde"
                   value="<?= date('Y-m-d') ?>">
          </div>

          <button class="btn btn-cm w-100">
            <i class="bi bi-floppy me-1"></i> Guardar tarifa
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- ── Lista ── -->
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-list-ul me-1" style="color:#2ecc71"></i> Tarifas registradas</h6>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead style="background:#111;color:#fff">
              <tr>
                <th class="ps-3">Cliente</th>
                <th class="text-end">₲ / hora</th>
                <th class="text-end">$ / hora</th>
                <th class="text-center">Vigente desde</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($tarifas)): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">Sin tarifas registradas.</td></tr>
              <?php else: ?>
                <?php foreach ($tarifas as $t): ?>
                  <tr>
                    <td class="ps-3 fw-semibold"><?= h($t['cliente']) ?></td>
                    <td class="text-end">
                      <?= $t['precio_hora_pyg'] !== null
                          ? '₲ ' . number_format((float)$t['precio_hora_pyg'], 0, ',', '.')
                          : '<span class="text-muted">—</span>' ?>
                    </td>
                    <td class="text-end">
                      <?= $t['precio_hora_usd'] !== null
                          ? '$ ' . number_format((float)$t['precio_hora_usd'], 2, ',', '.')
                          : '<span class="text-muted">—</span>' ?>
                    </td>
                    <td class="text-center text-muted small">
                      <?= date('d/m/Y', strtotime($t['vigente_desde'])) ?>
                    </td>
                    <td class="text-center">
                      <a href="tarifas.php?eliminar=<?= (int)$t['id'] ?>"
                         class="btn btn-sm btn-outline-danger"
                         onclick="return confirm('¿Eliminar tarifa de <?= h(addslashes($t['cliente'])) ?>?')"
                         title="Eliminar">
                        <i class="bi bi-trash"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</div>

<?php require 'footer.php'; ?>