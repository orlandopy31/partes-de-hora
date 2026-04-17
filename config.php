<?php
// config.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

date_default_timezone_set('America/Asuncion');

$DB_HOST = "127.0.0.1";
$DB_USER = "root";
$DB_PASS = "Orlando4375820321";
$DB_NAME = "servicios";

try {
  $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
  $conn->set_charset("utf8mb4");
} catch (Exception $e) {
  http_response_code(500);
  die("Error de conexión a la base de datos.");
}

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function post($key, $default = null) { return $_POST[$key] ?? $default; }
