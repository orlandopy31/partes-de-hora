<?php
require 'config.php';
$pageTitle = "Clientes";
$msg  = "";
$tipo = "info";

// ── ELIMINAR ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('accion','') === 'eliminar') {
    $id = (int)post('id', 0);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $msg  = "Cliente eliminado correctamente.";
        $tipo = "danger";
    }
}

// ── EDITAR ──────────────────────────────────────────────────────────────────
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && post('accion','') === 'editar') {
    $id       = (int)post('id', 0);
    $nombre   = trim(post('nombre',''));
    $ruc      = trim(post('ruc',''));
    $telefono = trim(post('telefono',''));
    $email    = trim(post('email',''));
    $direccion= trim(post('direccion',''));

    if ($nombre === '') {
        $msg  = "El nombre es obligatorio.";
        $tipo = "warning";
    } elseif ($id > 0) {
        $stmt = $conn->prepare(
            "UPDATE clientes SET nombre=?, ruc=?, telefono=?, email=?, direccion=? WHERE id=?"
        );
        $stmt->bind_param('sssssi', $nombre, $ruc, $telefono, $email, $direccion, $id);
        $stmt->execute();
        $msg  = "Cliente actualizado correctamente.";
        $tipo = "success";
    }
}

// ── NUEVO ───────────────────────────────────────────────────────────────────
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && post('accion','') === 'nuevo') {
    $nombre   = trim(post('nombre',''));
    $ruc      = trim(post('ruc',''));
    $telefono = trim(post('telefono',''));
    $email    = trim(post('email',''));
    $direccion= trim(post('direccion',''));

    if ($nombre === '') {
        $msg  = "El nombre es obligatorio.";
        $tipo = "warning";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO clientes (nombre,ruc,telefono,email,direccion) VALUES (?,?,?,?,?)"
        );
        $stmt->bind_param('sssss', $nombre, $ruc, $telefono, $email, $direccion);
        $stmt->execute();
        $msg  = "Cliente guardado correctamente.";
        $tipo = "success";
    }
}

// Cliente a editar (GET)
$editar = null;
if (isset($_GET['editar'])) {
    $eid  = (int)$_GET['editar'];
    $res  = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
    $res->bind_param('i', $eid);
    $res->execute();
    $editar = $res->get_result()->fetch_assoc();
}

$clientes = $conn->query("SELECT * FROM clientes ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

require 'header.php';
?>

<div class="row g-3">

  <!-- ── Formulario ── -->
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">

        <h5 class="mb-3">
          <?= $editar ? 'Editar cliente' : 'Nuevo cliente' ?>
        </h5>

        <?php if ($msg): ?>
          <div class="alert alert-<?= $tipo ?>"><?= h($msg) ?></div>
        <?php endif; ?>

        <form method="post">
          <input type="hidden" name="accion" value="<?= $editar ? 'editar' : 'nuevo' ?>">
          <?php if ($editar): ?>
            <input type="hidden" name="id" value="<?= (int)$editar['id'] ?>">
          <?php endif; ?>

          <div class="mb-2">
            <label class="form-label">Nombre *</label>
            <input class="form-control" name="nombre" required
                   value="<?= $editar ? h($editar['nombre']) : '' ?>">
          </div>
          <div class="mb-2">
            <label class="form-label">RUC</label>
            <input class="form-control" name="ruc"
                   value="<?= $editar ? h($editar['ruc']) : '' ?>">
          </div>
          <div class="mb-2">
            <label class="form-label">Teléfono</label>
            <input class="form-control" name="telefono"
                   value="<?= $editar ? h($editar['telefono']) : '' ?>">
          </div>
          <div class="mb-2">
            <label class="form-label">Email</label>
            <input class="form-control" name="email" type="email"
                   value="<?= $editar ? h($editar['email']) : '' ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Dirección</label>
            <input class="form-control" name="direccion"
                   value="<?= $editar ? h($editar['direccion']) : '' ?>">
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-primary w-100">
              <?= $editar ? 'Actualizar' : 'Guardar' ?>
            </button>
            <?php if ($editar): ?>
              <a href="clientes.php" class="btn btn-outline-secondary w-100">Cancelar</a>
            <?php endif; ?>
          </div>
        </form>

      </div>
    </div>
  </div>

  <!-- ── Lista ── -->
  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="mb-3">Lista de clientes</h5>
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle">
            <thead class="table-dark">
              <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>RUC</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Dirección</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($clientes as $c): ?>
                <tr>
                  <td class="text-muted"><?= (int)$c['id'] ?></td>
                  <td><?= h($c['nombre']) ?></td>
                  <td><?= h($c['ruc']) ?></td>
                  <td><?= h($c['telefono']) ?></td>
                  <td><?= h($c['email']) ?></td>
                  <td><?= h($c['direccion']) ?></td>
                  <td class="text-center" style="white-space:nowrap">

                    <!-- Editar -->
                    <a href="clientes.php?editar=<?= (int)$c['id'] ?>"
                       class="btn btn-sm btn-outline-primary"
                       title="Editar">
                      <i class="bi bi-pencil"></i>
                    </a>

                    <!-- Eliminar -->
                    <form method="post" class="d-inline"
                          onsubmit="return confirm('¿Eliminar a <?= h(addslashes($c['nombre'])) ?>?')">
                      <input type="hidden" name="accion" value="eliminar">
                      <input type="hidden" name="id"     value="<?= (int)$c['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger" title="Eliminar">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>

                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$clientes): ?>
                <tr><td colspan="7" class="text-muted text-center">Sin clientes todavía.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</div>

<?php require 'footer.php'; ?>