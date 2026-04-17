<?php if (!isset($pageTitle)) $pageTitle = "Sistema de Trabajos"; ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($pageTitle) ?> · Cybermatica</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root {
      --cm-green:      #2ecc71;
      --cm-green-dark: #27ae60;
      --cm-black:      #111111;
      --cm-dark:       #1a1a1a;
      --cm-card-bg:    #ffffff;
    }

    body {
      background: #f4f6f4;
      font-family: 'Segoe UI', system-ui, sans-serif;
    }

    /* ── Navbar ── */
    .navbar-cm {
      background: var(--cm-black);
      border-bottom: 3px solid var(--cm-green);
      padding: 0.6rem 0;
    }
    .navbar-cm .navbar-brand {
      font-weight: 700;
      font-size: 1.2rem;
      color: #fff;
      letter-spacing: .5px;
      display: flex;
      align-items: center;
      gap: .5rem;
    }
    .navbar-cm .navbar-brand span.dot {
      display: inline-block;
      width: 10px; height: 10px;
      background: var(--cm-green);
      border-radius: 50%;
    }
    .navbar-cm .nav-link {
      color: #ccc !important;
      font-size: .9rem;
      padding: .5rem .9rem !important;
      border-radius: 6px;
      transition: background .2s, color .2s;
    }
    .navbar-cm .nav-link:hover,
    .navbar-cm .nav-link.active {
      color: #fff !important;
      background: var(--cm-green-dark);
    }
    .navbar-cm .nav-link i { margin-right: 4px; }

    /* ── Cards ── */
    .card { border: none; border-radius: 12px; }
    .card.shadow-sm { box-shadow: 0 2px 12px rgba(0,0,0,.08) !important; }

    /* ── Stat cards ── */
    .stat-card {
      border-radius: 12px;
      padding: 1.2rem 1.4rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      background: #fff;
      box-shadow: 0 2px 10px rgba(0,0,0,.07);
    }
    .stat-card .stat-icon {
      width: 52px; height: 52px;
      border-radius: 12px;
      background: var(--cm-green);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem; color: #fff; flex-shrink: 0;
    }
    .stat-card .stat-label { font-size: .8rem; color: #888; margin-bottom: 2px; }
    .stat-card .stat-value { font-size: 1.6rem; font-weight: 700; color: var(--cm-black); line-height: 1; }

    /* ── Buttons ── */
    .btn-cm {
      background: var(--cm-green);
      color: #fff;
      border: none;
      font-weight: 600;
      border-radius: 8px;
    }
    .btn-cm:hover { background: var(--cm-green-dark); color: #fff; }
    .btn-outline-cm {
      border: 2px solid var(--cm-green);
      color: var(--cm-green);
      font-weight: 600;
      border-radius: 8px;
      background: transparent;
    }
    .btn-outline-cm:hover { background: var(--cm-green); color: #fff; }

    /* ── Page header strip ── */
    .page-header {
      background: var(--cm-black);
      color: #fff;
      border-radius: 12px;
      padding: 1rem 1.4rem;
      margin-bottom: 1.4rem;
      display: flex;
      align-items: center;
      gap: .7rem;
    }
    .page-header i { color: var(--cm-green); font-size: 1.3rem; }
    .page-header h4 { margin: 0; font-size: 1.1rem; font-weight: 600; }
    .page-header span { font-size: .85rem; color: #aaa; }

    /* ── Footer ── */
    .footer-cm {
      background: var(--cm-black);
      border-top: 3px solid var(--cm-green);
      color: #888;
      font-size: .82rem;
      padding: 1rem 0;
      margin-top: 2.5rem;
    }
    .footer-cm a { color: var(--cm-green); text-decoration: none; }

    /* ── Tables ── */
    .table thead.thead-cm th {
      background: var(--cm-black);
      color: #fff;
      font-weight: 600;
      font-size: .85rem;
    }

    /* ── Scrollbar ── */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { background: var(--cm-green); border-radius: 4px; }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-cm">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <span class="dot"></span> Cybermatica
      <span style="color:#666;font-weight:400;font-size:.85rem;margin-left:4px">| Partes de Trabajo</span>
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto gap-1">
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='index.php'?'active':'' ?>"
             href="index.php"><i class="bi bi-house"></i>Inicio</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='clientes.php'?'active':'' ?>"
             href="clientes.php"><i class="bi bi-people"></i>Clientes</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='tecnicos.php'?'active':'' ?>"
             href="tecnicos.php"><i class="bi bi-person-badge"></i>Técnicos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='trabajos.php'?'active':'' ?>"
             href="trabajos.php"><i class="bi bi-clipboard2-check"></i>Trabajos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= basename($_SERVER['PHP_SELF'])==='reporte.php'?'active':'' ?>"
             href="reporte.php"><i class="bi bi-file-earmark-bar-graph"></i>Reportes</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container py-4">