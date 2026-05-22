<!doctype html>
<html lang="en" data-bs-theme="auto">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Empresa Constructora</title>
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" 
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">  
  <!-- Iconos de bootstrap -->  
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <!-- datatables -->  
  <link rel="stylesheet" href="https://cdn.datatables.net/2.3.8/css/dataTables.dataTables.min.css">
  <!-- Custom styles for this template -->
  <style>
    body {
      height: 100%;
    }

    aside {
      /* border: 1px yellow solid; */
      position: fixed;
      overflow: auto;
      height: calc(100vh - 12px);
      justify-content: flex-start;
      align-self: flex-start;

    }

    nav {
      position: sticky;
    }

    main {
      position: relative;
      overflow: visible;
      margin-left: auto;
      justify-content: flex-end;
      align-self: flex-end;
    }

    #sidebarshow {
      display: none;

    }
    
    .btn-toggle-nav a {
      padding: .1875rem .5rem;
      margin-top: .125rem;
      margin-left: 1.25rem;
    }

    @media screen and (max-width: 575px) {
      #sidebarshow {
        display: inline;
      }

      #sidebartoggle {
        display: none;
      }
    }
  </style>
  <!-- Script de bootstap  -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" 
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  
  <!-- Jquery -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"
    integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <!-- datatables -->
  <script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js" crossorigin="anonymous"></script>
</head>
<!-- Body of dashboard - inside is all content-->
<body class="bg-body-tertiary">
  <!-- Aside menu orginal -->
  <aside class="collapse show collapse-horizontal col-sm-2 p-3 border-end bg-body-tertiary" id="collapseWidthExample">
     <?php require 'modules/layouts/menu.php'; ?>
  </aside>
  <!-- End aside -->
  <!-- Content of the main body - heres the main content like graph, tables, etc... -->
  <main class="col-sm-10 bg-body-tertiary" id="main">

    <!-- Start navbar - here is inside of the main  -->
    <nav class="navbar sticky-top navbar-expand-lg border-bottom bg-body-tertiary">
      <div class="container-fluid">

        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse"
          data-bs-target="#collapseWidthExample" aria-expanded="true" aria-controls="collapseWidthExample"
          style="margin-right: 10px; padding: 0px 5px 0px 5px;" id="sidebartoggle" onclick="changeclass()"> <i
            class="bi bi-arrows-expand-vertical"></i>
        </button>
        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="offcanvas"
          data-bs-target="#offcanvasExample" aria-controls="offcanvasExample"
          style="margin-right: 10px; padding: 2px 6px 2px 6px;" id="sidebarshow">
          <i class="bi bi-arrow-bar-right"></i>
        </button>
        <a class="navbar-brand" href="#">Navbar</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
          aria-controls="navbarSupportedContent" aria-expanded="true" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <a class="nav-link active" aria-current="page" href="#">Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#">Link</a>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="true">
                Dropdown
              </a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Action</a></li>
                <li><a class="dropdown-item" href="#">Another action</a></li>
                <li>
                  <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item" href="#">Something else here</a></li>
              </ul>
            </li>
          </ul>
          <!-- Cambiar Tema -->
          <div class="dropdown-center">
            <button class="btn btn-bd-primary py-2 dropdown-toggle d-flex align-items-center" id="bd-theme"
              type="button" aria-expanded="false" data-bs-toggle="dropdown" aria-label="Toggle theme (auto)">
              <i class="bi bi-circle-half me-2" id="theme-icon-active"></i>
              <span class="visually-hidden" id="bd-theme-text">Cambiar Tema</span>
            </button>
            <ul class="dropdown-menu shadow" aria-labelledby="bd-theme-text">
              <li>
                <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light"
                  data-bs-icon-value="sun-fill" aria-pressed="false">
                  <i class="bi bi-sun-fill me-2"></i>
                  Claro
                </button>
              </li>
              <li>
                <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark"
                  data-bs-icon-value="moon-stars-fill" aria-pressed="false">
                  <i class="bi bi-moon-stars-fill me-2"></i>
                  Oscuro
                </button>
              </li>
              <li>
                <button type="button" class="dropdown-item d-flex align-items-center active" data-bs-theme-value="auto"
                  data-bs-icon-value="circle-half" aria-pressed="true">
                  <i class="bi bi-circle-half me-2"></i>
                  Auto
                </button>
              </li>
            </ul>
          </div>
          <!-- fin Cambiar Tema -->
          <!-- dropdown -->
          <div id="navbarNavDropdown">
            <ul class="navbar-nav ms-auto me-3">
              <!-- Dropdown Item Start -->
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle fw-semibold" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  Dropdown link
                </a>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="#">Config</a></li>
                  <li><a class="dropdown-item" href="#">Perfil</a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item" href="#">Cerrar Sesion</a></li>
                </ul>
              </li>              
            </ul>
          </div>
          <!-- fin dropdown -->
        </div>
      </div>
    </nav>
    <!-- end navbar -->

    <div class="container-fluid">