<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Pagos — Vértice</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

<div class="container py-5">

    <div class="mb-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="../../dashboard.php" class="text-decoration-none">
                        <i class="bi bi-house-door me-1"></i>Dashboard
                    </a>
                </li>
                <li class="breadcrumb-item active">Pagos</li>
            </ol>
        </nav>
        <h1 class="fw-bold mb-1">
            <i class="bi bi-credit-card-fill text-primary me-2"></i>Módulo de Pagos
        </h1>
        <p class="text-muted fs-5 mb-0">Selecciona el tipo de pago que deseas gestionar.</p>
    </div>

    <div class="row g-4 justify-content-center">

        <!-- Pagos a Empleados -->
        <div class="col-md-5">
            <a href="empleados.php" class="text-decoration-none">
                <div class="card shadow border-0 h-100 text-center p-4"
                     style="transition: transform .18s, box-shadow .18s;"
                     onmouseover="this.style.transform='translateY(-6px)';this.style.boxShadow='0 12px 32px rgba(13,110,253,.18)'"
                     onmouseout="this.style.transform='';this.style.boxShadow=''">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center gap-3 py-4">
                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                             style="width:80px;height:80px;">
                            <i class="bi bi-person-badge-fill text-primary" style="font-size:2.2rem"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1 text-dark">Pagos a Empleados</h4>
                            <p class="text-muted mb-0 small">Registra pagos, bonos y deducciones del personal activo.</p>
                        </div>
                        <span class="btn btn-primary px-4">
                            <i class="bi bi-arrow-right-circle me-1"></i>Ir al módulo
                        </span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Pagos de Pedidos -->
        <div class="col-md-5">
            <a href="pedidos.php" class="text-decoration-none">
                <div class="card shadow border-0 h-100 text-center p-4"
                     style="transition: transform .18s, box-shadow .18s;"
                     onmouseover="this.style.transform='translateY(-6px)';this.style.boxShadow='0 12px 32px rgba(25,135,84,.18)'"
                     onmouseout="this.style.transform='';this.style.boxShadow=''">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center gap-3 py-4">
                        <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center"
                             style="width:80px;height:80px;">
                            <i class="bi bi-box-seam-fill text-success" style="font-size:2.2rem"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1 text-dark">Pagos de Pedidos</h4>
                            <p class="text-muted mb-0 small">Gestiona los pagos a proveedores por pedidos de materiales.</p>
                        </div>
                        <span class="btn btn-success px-4">
                            <i class="bi bi-arrow-right-circle me-1"></i>Ir al módulo
                        </span>
                    </div>
                </div>
            </a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
