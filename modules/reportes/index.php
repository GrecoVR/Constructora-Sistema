<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro de Reportes — Vértice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark:    #0d1117;
            --bg-card:    #161b22;
            --bg-hover:   #1c2333;
            --accent-1:   #2ea043;
            --accent-2:   #1f6feb;
            --accent-3:   #e3b341;
            --border:     rgba(255,255,255,0.08);
            --text-main:  #e6edf3;
            --text-muted: #7d8590;
        }

        * { box-sizing: border-box; }

        body {
            background: var(--bg-dark);
            font-family: 'DM Sans', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── fondo de cuadrícula sutil ── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(var(--border) 1px, transparent 1px),
                linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
            z-index: 0;
        }

        .page-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px 24px;
        }

        /* ── cabecera ── */
        .hero {
            text-align: center;
            margin-bottom: 64px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(46,160,67,.15);
            border: 1px solid rgba(46,160,67,.35);
            color: var(--accent-1);
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: 5px 14px;
            border-radius: 20px;
            margin-bottom: 20px;
        }

        .hero h1 {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(2.4rem, 5vw, 3.6rem);
            line-height: 1.1;
            margin-bottom: 14px;
            background: linear-gradient(135deg, #e6edf3 30%, #7d8590);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            color: var(--text-muted);
            font-size: 1.05rem;
            max-width: 480px;
            margin: 0 auto;
        }

        /* ── tarjetas ── */
        .report-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 40px 32px;
            text-decoration: none;
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            gap: 20px;
            transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .report-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 70% 0%, var(--card-glow) 0%, transparent 60%);
            opacity: 0;
            transition: opacity .35s ease;
            pointer-events: none;
        }

        .report-card:hover { transform: translateY(-6px); color: var(--text-main); }
        .report-card:hover::after { opacity: 1; }

        .card-green  { --card-glow: rgba(46,160,67,.18); }
        .card-blue   { --card-glow: rgba(31,111,235,.18); }
        .card-yellow { --card-glow: rgba(227,179,65,.18); }

        .card-green:hover  { border-color: rgba(46,160,67,.5);  box-shadow: 0 20px 60px rgba(46,160,67,.12); }
        .card-blue:hover   { border-color: rgba(31,111,235,.5); box-shadow: 0 20px 60px rgba(31,111,235,.12); }
        .card-yellow:hover { border-color: rgba(227,179,65,.5); box-shadow: 0 20px 60px rgba(227,179,65,.12); }

        .card-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
        }

        .card-green  .card-icon { background: rgba(46,160,67,.15);  color: var(--accent-1); }
        .card-blue   .card-icon { background: rgba(31,111,235,.15); color: var(--accent-2); }
        .card-yellow .card-icon { background: rgba(227,179,65,.15); color: var(--accent-3); }

        .card-title {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1.25rem;
            margin: 0;
        }

        .card-desc {
            color: var(--text-muted);
            font-size: .9rem;
            line-height: 1.55;
            margin: 0;
            flex-grow: 1;
        }

        .card-arrow {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: .82rem;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
            opacity: .55;
            transition: opacity .2s, gap .2s;
        }

        .report-card:hover .card-arrow { opacity: 1; gap: 10px; }

        .card-green  .card-arrow { color: var(--accent-1); }
        .card-blue   .card-arrow { color: var(--accent-2); }
        .card-yellow .card-arrow { color: var(--accent-3); }

        /* ── footer ── */
        .page-footer {
            text-align: center;
            margin-top: 60px;
            color: var(--text-muted);
            font-size: .82rem;
        }

        .page-footer a { color: var(--text-muted); text-decoration: underline; }
        .page-footer a:hover { color: var(--text-main); }
    </style>
</head>
<body>

<div class="page-wrapper">

    <!-- CABECERA -->
    <div class="hero">
        <div class="hero-badge">
            <i class="bi bi-bar-chart-fill"></i> Sistema Vértice
        </div>
        <h1>Centro de Reportes</h1>
        <p>Accede a los informes operativos, financieros y de inventario de la empresa.</p>
    </div>

    <!-- TARJETAS -->
    <div class="container-lg px-0">
        <div class="row g-4 justify-content-center">

            <!-- Resumen General -->
            <div class="col-12 col-md-6 col-lg-4">
                <a href="dashboard.php" class="report-card card-blue">
                    <div class="card-icon"><i class="bi bi-speedometer2"></i></div>
                    <div>
                        <p class="card-title">Resumen General</p>
                        <p class="card-desc">Vista consolidada de proyectos, balance financiero, materiales principales y cotizaciones por estado.</p>
                    </div>
                    <div class="card-arrow">
                        Ver reporte <i class="bi bi-arrow-right"></i>
                    </div>
                </a>
            </div>

            <!-- Reporte Financiero -->
            <div class="col-12 col-md-6 col-lg-4">
                <a href="financiero.php" class="report-card card-green">
                    <div class="card-icon"><i class="bi bi-cash-coin"></i></div>
                    <div>
                        <p class="card-title">Reporte Financiero</p>
                        <p class="card-desc">Ingresos por proyecto, gastos de obra, pagos a empleados por mes y pagos pendientes de clientes.</p>
                    </div>
                    <div class="card-arrow">
                        Ver reporte <i class="bi bi-arrow-right"></i>
                    </div>
                </a>
            </div>

            <!-- Reporte Inventario -->
            <div class="col-12 col-md-6 col-lg-4">
                <a href="inventario.php" class="report-card card-yellow">
                    <div class="card-icon"><i class="bi bi-boxes"></i></div>
                    <div>
                        <p class="card-title">Reporte de Inventario</p>
                        <p class="card-desc">Stock por almacén, materiales bajo mínimo, ítems agotados y los últimos movimientos registrados.</p>
                    </div>
                    <div class="card-arrow">
                        Ver reporte <i class="bi bi-arrow-right"></i>
                    </div>
                </a>
            </div>

        </div>
    </div>

    <!-- FOOTER -->
    <div class="page-footer">
        <a href="../../dashboard.php"><i class="bi bi-arrow-left me-1"></i>Volver al panel principal</a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
