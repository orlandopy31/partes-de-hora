<?php
require 'config.php';
$pageTitle = "Técnicos";
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = trim(post('nombre',''));
  $telefono = trim(post('telefono',''));
  $email = trim(post('email',''));

  if ($nombre === '') $msg = "El nombre es obligatorio.";
  else {
    $stmt = $conn->prepare("INSERT INTO tecnicos (nombre,telefono,email) VALUES (?,?,?)");
    $stmt->bind_param("sss", $nombre, $telefono, $email);
    $stmt->execute();
    $msg = "Técnico guardado.";
  }
}

$tecnicos = $conn->query("SELECT * FROM tecnicos ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

require 'header.php';
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="mb-3">Nuevo técnico</h5>
        <?php if ($msg): ?><div class="alert alert-info"><?= h($msg) ?></div><?php endif; ?>
        <form method="post">
          <div class="mb-2">
            <label class="form-label">Nombre *</label>
            <input class="form-control" name="nombre" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Teléfono</label>
            <input class="form-control" name="telefono">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input class="form-control" name="email" type="email">
          </div>
          <button class="btn btn-primary w-100">Guardar</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="mb-3">Lista de técnicos</h5>
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle">
            <thead>
              <tr><th>ID</th><th>Nombre</th><th>Teléfono</th><th>Email</th></tr>
            </thead>
            <tbody>
              <?php foreach ($tecnicos as $t): ?>
              <tr>
                <td><?= (int)$t['id'] ?></td>
                <td><?= h($t['nombre']) ?></td>
                <td><?= h($t['telefono']) ?></td>
                <td><?= h($t['email']) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$tecnicos): ?>
                <tr><td colspan="4" class="text-muted">Sin técnicos todavía.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require 'footer.php'; ?>
