<?php
// Habilitar errores para depuración (opcional, quitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'config.php';

$pageTitle = "Trabajos";
$msg = "";
$edit_id = 0;

$mes = (int)($_GET['mes'] ?? date('n'));
$anio = (int)($_GET['anio'] ?? date('Y'));

if ($mes < 1 || $mes > 12) $mes = (int)date('n');
if ($anio < 2000 || $anio > 2100) $anio = (int)date('Y');

// Procesar eliminación
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM trabajos WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $msg = "Trabajo eliminado correctamente.";
        header("Location: trabajos.php?mes=" . $mes . "&anio=" . $anio . "&deleted=1");
        exit;
    } else {
        $msg = "Error al eliminar: " . $stmt->error;
    }
    $stmt->close();
}

// Verificar si estamos editando
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
}

// Procesar formulario (insert o update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = (int)post('cliente_id', 0);
    $tecnico_id = (int)post('tecnico_id', 0);
    $fecha = post('fecha', date('Y-m-d'));
    $horas = (float)post('horas', 0);
    $comentario = trim(post('comentario', ''));
    $trabajo_id = (int)post('trabajo_id', 0);

    if ($cliente_id <= 0 || $tecnico_id <= 0 || $comentario === '' || $horas <= 0) {
        $msg = "Completa cliente, técnico, horas (>0) y comentario.";
    } else {
        if ($trabajo_id > 0) {
            // UPDATE
            $stmt = $conn->prepare("UPDATE trabajos SET cliente_id=?, tecnico_id=?, fecha=?, horas=?, comentario=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param("iisdsi", $cliente_id, $tecnico_id, $fecha, $horas, $comentario, $trabajo_id);
                if ($stmt->execute()) {
                    $msg = "Trabajo actualizado correctamente.";
                    header("Location: trabajos.php?mes=" . $mes . "&anio=" . $anio . "&updated=1");
                    exit;
                } else {
                    $msg = "Error al actualizar: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $msg = "Error en la preparación de la consulta: " . $conn->error;
            }
        } else {
            // INSERT
            $stmt = $conn->prepare("INSERT INTO trabajos (cliente_id, tecnico_id, fecha, horas, comentario) VALUES (?,?,?,?,?)");
            if ($stmt) {
                $stmt->bind_param("iisds", $cliente_id, $tecnico_id, $fecha, $horas, $comentario);
                if ($stmt->execute()) {
                    $msg = "Trabajo registrado correctamente.";
                    header("Location: trabajos.php?mes=" . $mes . "&anio=" . $anio . "&success=1");
                    exit;
                } else {
                    $msg = "Error al registrar: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $msg = "Error en la preparación de la consulta: " . $conn->error;
            }
        }
    }
}

// Verificar mensajes por GET
if (isset($_GET['success']) && $_GET['success'] == 1 && empty($msg)) {
    $msg = "Trabajo registrado correctamente.";
}
if (isset($_GET['updated']) && $_GET['updated'] == 1 && empty($msg)) {
    $msg = "Trabajo actualizado correctamente.";
}
if (isset($_GET['deleted']) && $_GET['deleted'] == 1 && empty($msg)) {
    $msg = "Trabajo eliminado correctamente.";
}

// Obtener datos del trabajo si estamos editando
$edit_data = null;
if ($edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM trabajos WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$edit_data) {
        $edit_id = 0;
        $msg = "El trabajo que intentas editar no existe.";
    }
}

// Obtener clientes y técnicos
$clientes = $conn->query("SELECT id, nombre FROM clientes ORDER BY nombre");
if (!$clientes) {
    die("Error al obtener clientes: " . $conn->error);
}
$clientes = $clientes->fetch_all(MYSQLI_ASSOC);

$tecnicos = $conn->query("SELECT id, nombre FROM tecnicos WHERE activo=1 ORDER BY nombre");
if (!$tecnicos) {
    die("Error al obtener técnicos: " . $conn->error);
}
$tecnicos = $tecnicos->fetch_all(MYSQLI_ASSOC);

// Obtener trabajos del mes
$stmt = $conn->prepare("
    SELECT tr.*, c.nombre AS cliente, t.nombre AS tecnico
    FROM trabajos tr
    JOIN clientes c ON c.id = tr.cliente_id
    JOIN tecnicos t ON t.id = tr.tecnico_id
    WHERE MONTH(tr.fecha) = ? AND YEAR(tr.fecha) = ?
    ORDER BY tr.fecha DESC, tr.id DESC
");
if (!$stmt) {
    die("Error al preparar consulta de trabajos: " . $conn->error);
}

$stmt->bind_param("ii", $mes, $anio);
$stmt->execute();
$trabajos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalHoras = 0;
foreach ($trabajos as $x) {
    $totalHoras += (float)$x['horas'];
}

require 'header.php';
?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">
                    <?= $edit_id > 0 ? 'Editar trabajo' : 'Registrar trabajo' ?>
                    <?php if ($edit_id > 0): ?>
                        <a href="trabajos.php?mes=<?= $mes ?>&anio=<?= $anio ?>" class="btn btn-sm btn-secondary float-end">Nuevo</a>
                    <?php endif; ?>
                </h5>
                
                <?php if ($msg): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?= h($msg) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="trabajo_id" value="<?= $edit_id ?>">
                    
                    <div class="mb-2">
                        <label class="form-label">Cliente</label>
                        <select class="form-select" name="cliente_id" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($clientes as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" 
                                    <?= ($edit_data && $edit_data['cliente_id'] == $c['id']) ? 'selected' : '' ?>>
                                    <?= h($c['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Técnico</label>
                        <select class="form-select" name="tecnico_id" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($tecnicos as $t): ?>
                                <option value="<?= (int)$t['id'] ?>"
                                    <?= ($edit_data && $edit_data['tecnico_id'] == $t['id']) ? 'selected' : '' ?>>
                                    <?= h($t['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Fecha</label>
                        <input class="form-control" type="date" name="fecha" 
                               value="<?= $edit_data ? h($edit_data['fecha']) : h(date('Y-m-d')) ?>" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Horas trabajadas</label>
                        <input class="form-control" type="number" step="0.25" min="0.25" name="horas" 
                               value="<?= $edit_data ? h($edit_data['horas']) : '' ?>" required>
                        <div class="form-text">Ej: 1.5, 2, 0.75</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Comentario del trabajo</label>
                        <textarea class="form-control" name="comentario" rows="4" required><?= $edit_data ? h($edit_data['comentario']) : '' ?></textarea>
                    </div>

                    <button class="btn btn-primary w-100">
                        <?= $edit_id > 0 ? 'Actualizar' : 'Guardar' ?>
                    </button>
                    
                    <?php if ($edit_id > 0): ?>
                        <a href="trabajos.php?mes=<?= $mes ?>&anio=<?= $anio ?>" class="btn btn-secondary w-100 mt-2">Cancelar</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 align-items-end justify-content-between">
                    <div>
                        <h5 class="mb-1">Trabajos del mes</h5>
                        <div class="text-muted">Total horas: <strong><?= number_format($totalHoras, 2) ?></strong></div>
                    </div>

                    <form class="d-flex gap-2" method="get">
                        <select class="form-select" name="mes" style="width: auto;">
                            <?php for($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m === $mes ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <input class="form-control" style="width:110px" type="number" name="anio" value="<?= (int)$anio ?>" min="2000" max="2100">
                        <button class="btn btn-outline-primary">Filtrar</button>
                    </form>
                </div>

                <hr>

                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Técnico</th>
                                <th class="text-end">Horas</th>
                                <th>Comentario</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trabajos as $tr): ?>
                            <tr>
                                <td><?= h($tr['fecha']) ?></td>
                                <td><?= h($tr['cliente']) ?></td>
                                <td><?= h($tr['tecnico']) ?></td>
                                <td class="text-end"><?= number_format((float)$tr['horas'], 2) ?></td>
                                <td><?= h($tr['comentario']) ?></td>
                                <td class="text-center">
                                    <a href="trabajos.php?edit=<?= $tr['id'] ?>&mes=<?= $mes ?>&anio=<?= $anio ?>" 
                                       class="btn btn-sm btn-warning" 
                                       title="Editar">
                                        ✏️ Editar
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger" 
                                            title="Eliminar"
                                            onclick="confirmDelete(<?= $tr['id'] ?>, '<?= h(addslashes($tr['cliente'])) ?>', '<?= h($tr['fecha']) ?>')">
                                        🗑️ Eliminar
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (!$trabajos): ?>
                                <tr>
                                    <td colspan="6" class="text-muted text-center">No hay trabajos cargados para este mes.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a class="btn btn-success" href="reporte.php?mes=<?= (int)$mes ?>&anio=<?= (int)$anio ?>">
                        Ver Reporte (PDF)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                ¿Estás seguro de que deseas eliminar este trabajo?
                <br><br>
                <strong>Cliente:</strong> <span id="deleteCliente"></span><br>
                <strong>Fecha:</strong> <span id="deleteFecha"></span>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="deleteLink" class="btn btn-danger">Eliminar</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, cliente, fecha) {
    document.getElementById('deleteCliente').textContent = cliente;
    document.getElementById('deleteFecha').textContent = fecha;
    document.getElementById('deleteLink').href = 'trabajos.php?delete=' + id + '&mes=<?= $mes ?>&anio=<?= $anio ?>';
    var myModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    myModal.show();
}
</script>

<?php require 'footer.php'; ?>