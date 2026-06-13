<?php
require_once '../../config/session.php';

if (isset($_SESSION['tipo_sesion'])) {
    if ($_SESSION['tipo_sesion'] === 'cliente') {
        header('Location: ../../client/index.php');
    } else {
        header('Location: ../../modules/dashboard/dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresa Constructora</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #1a2535 0%, #2C3E50 60%, #0d1520 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                radial-gradient(circle, rgba(52,152,219,0.08) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        .hero {
            text-align: center;
            position: relative;
            z-index: 2;
            padding: 0 20px;
            animation: fadeUp 0.7s cubic-bezier(.22,1,.36,1) both;
        }

        @keyframes fadeUp {
            from { transform: translateY(30px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        .logo-box {
            width: 90px;
            height: 90px;
            border-radius: 24px;
            background: linear-gradient(135deg, #3498DB, #E67E22);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            font-weight: 900;
            color: #fff;
            margin: 0 auto 24px;
            box-shadow: 0 0 60px rgba(52,152,219,0.4);
        }

        .hero-title {
            font-size: 36px;
            font-weight: 900;
            color: #fff;
            letter-spacing: 3px;
        }

        .hero-sub {
            font-size: 13px;
            color: rgba(255,255,255,0.45);
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-top: 6px;
            margin-bottom: 48px;
        }

        .cards-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .access-card {
            background: rgba(255,255,255,0.06);
            border: 1.5px solid rgba(255,255,255,0.12);
            border-radius: 20px;
            padding: 36px 32px;
            width: 220px;
            text-align: center;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.22s, background 0.22s, border-color 0.22s;
            backdrop-filter: blur(8px);
        }

        .access-card:hover {
            transform: translateY(-6px);
            background: rgba(255,255,255,0.11);
            border-color: rgba(255,255,255,0.25);
        }

        .card-icon {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 16px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 6px;
        }

        .card-desc {
            font-size: 12px;
            color: rgba(255,255,255,0.45);
            line-height: 1.5;
        }

        .card-btn {
            display: inline-block;
            margin-top: 18px;
            padding: 9px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: opacity 0.15s;
        }
        .card-btn:hover { opacity: 0.85; }

        /* Barra decorativa */
        .deco-bar {
            width: 48px;
            height: 3px;
            border-radius: 99px;
            background: linear-gradient(90deg, #3498DB, #E67E22);
            margin: 32px auto 0;
            opacity: 0.5;
        }

        @media (max-width: 500px) {
            .access-card { width: 100%; max-width: 280px; }
            .hero-title  { font-size: 28px; }
        }
    </style>
</head>
<body>

<div class="hero">
    <div class="logo-box">EC</div>
    <div class="hero-title">EMPRESA CONSTRUCTORA</div>
    <div class="hero-sub">Sistema de Gestión de Obras</div>

    <div class="cards-row">

        <!-- Empleados -->
        <a href="login.php" class="access-card">
            <div class="card-icon"
                 style="background:rgba(52,152,219,0.18);
                        border:1.5px solid rgba(52,152,219,0.35);">
                👷
            </div>
            <div class="card-title">Empleados</div>
            <div class="card-desc">
                Acceso al sistema interno de gestión y operaciones
            </div>
            <span class="card-btn"
                  style="background:#3498DB;color:#fff;">
                Ingresar
            </span>
        </a>

        <!-- Clientes -->
        <a href="login_cliente.php" class="access-card">
            <div class="card-icon"
                 style="background:rgba(39,174,96,0.18);
                        border:1.5px solid rgba(39,174,96,0.35);">
                🏢
            </div>
            <div class="card-title">Clientes</div>
            <div class="card-desc">
                Seguimiento de proyectos y pagos
            </div>
            <span class="card-btn"
                  style="background:#27AE60;color:#fff;">
                Ingresar
            </span>
        </a>

    </div>

    <div class="deco-bar"></div>
</div>

</body>
</html>