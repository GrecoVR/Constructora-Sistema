<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../utils/fecha.php';

requierePermiso('gestionar_pagos');

$pdo = conectar();

$permisos = $_SESSION['permisos'];
?>

<?php require_once '../../modules/layouts/header.php'; ?>

    <!-- Encabezado -->
    <div class="mb-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="../../modules/dashboard/dashboard.php" class="text-decoration-none">
                        <i class="bi bi-house-door me-1"></i>Dashboard
                    </a>
                </li>
                <li class="breadcrumb-item active">Notificaciones</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-4">
            <i class="bi bi-bell-fill text-warning me-2"></i>Centro de Notificaciones
        </h2>
        <p class="text-muted fs-5 mb-0">Selecciona el tipo de notificación que deseas gestionar.</p>
    </div>

    <!-- Cards -->
    <div class="row g-4 justify-content-center">

        <!-- Empleados -->
        <div class="col-md-5">
            <a href="empleados.php" class="text-decoration-none">
                <div class="card shadow border-0 h-100 text-center p-4" style="transition: transform .18s, box-shadow .18s;"
                     onmouseover="this.style.transform='translateY(-6px)';this.style.boxShadow='0 12px 32px rgba(13,110,253,.18)'"
                     onmouseout="this.style.transform='';this.style.boxShadow=''">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center gap-3 py-4">
                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                             style="width:80px;height:80px;">
                            <i class="bi bi-person-badge-fill text-primary" style="font-size:2.2rem"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1 text-dark">Notificaciones a Empleados</h4>
                            <p class="text-muted mb-0 small">Envía avisos y comunicados al personal activo de la empresa.</p>
                        </div>
                        <span class="btn btn-primary px-4">
                            <i class="bi bi-arrow-right-circle me-1"></i>Ir al módulo
                        </span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Clientes -->
        <div class="col-md-5">
            <a href="clientes.php" class="text-decoration-none">
                <div class="card shadow border-0 h-100 text-center p-4" style="transition: transform .18s, box-shadow .18s;"
                     onmouseover="this.style.transform='translateY(-6px)';this.style.boxShadow='0 12px 32px rgba(25,135,84,.18)'"
                     onmouseout="this.style.transform='';this.style.boxShadow=''">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center gap-3 py-4">
                        <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center"
                             style="width:80px;height:80px;">
                            <i class="bi bi-building-fill-check text-success" style="font-size:2.2rem"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1 text-dark">Notificaciones a Clientes</h4>
                            <p class="text-muted mb-0 small">Envía actualizaciones y comunicados a los clientes del proyecto.</p>
                        </div>
                        <span class="btn btn-success px-4">
                            <i class="bi bi-arrow-right-circle me-1"></i>Ir al módulo
                        </span>
                    </div>
                </div>
            </a>
        </div>

    </div>

<?php require_once '../../modules/layouts/footer.php'; ?>