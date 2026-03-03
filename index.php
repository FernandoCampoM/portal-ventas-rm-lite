

<?php

session_start();
// Incluir configuración
require_once 'config.php';
// Ruta del archivo de configuración
require_once 'setup/config_functions.php';
//validar si existe una configuracion del backend guardada


$config = get_configBackend();
if (!$config) {
    header("Location: setup/setup-backend.php");
    exit;
} 



// 1. Verificar si el usuario ha iniciado sesión
// Se comprueba si la variable de sesión 'loggedin' está establecida y es verdadera
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Si no ha iniciado sesión, lo redirigimos a la página de login
    header('Location: authentication/login.php');
    exit; // Es crucial usar exit() después de una redirección para detener la ejecución del script
}

// 2. Si el usuario ha iniciado sesión, el script continúa
// A partir de aquí, puedes acceder a los datos de la sesión:
$userID = $_SESSION['UserID'];
$username = $_SESSION['Username'];


?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Ventas RM Lite</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/dataTables.bootstrap5.min.css"
        rel="stylesheet">
    <!-- DataTables Buttons CSS -->
    <link href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/datatables.css" />
    <link rel="stylesheet" type="text/css" href="css/movil.css" />
    <link rel="stylesheet" type="text/css" href="css/sidebar.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/maintenance.css" />
    <link rel="stylesheet" type="text/css" href="css/index.css" />
    

</head>

<body>
    <script>
         window.APP_CONFIG = <?= json_encode($config) ?>;
    </script>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Navbar -->
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <!-- Botón toggle para sidebar en dispositivos no móviles -->
            <button class="btn btn-primary d-none d-lg-block" id="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Botón toggle para sidebar en móviles -->
            <button class="btn btn-primary d-lg-none me-2" id="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>

            <a class="navbar-brand" href="#">
                <i class="fas fa-shopping-cart me-2"></i>
                Portal de Ventas RM Lite
            </a>
            <a class="message"> </a>
            <div class="col-md-6">
                <h5 class="navbar-brand">&nbsp;&nbsp;&nbsp;&nbsp; Hola,
                    <strong><?php echo htmlspecialchars($_SESSION['EmployeeName'] ?? 'Invitado/a'); ?></strong>, te
                    damos la Bienvenida!
                </h5>
            </div>
            <!-- Botón toggle para menú de usuario en móviles -->
            <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-user-circle"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle black-text" id="navbarDropdown" role="button"
                            data-bs-toggle="dropdown" >
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['EmployeeName'] ?? 'Invitado/a'); ?>
                            <br><?php
                            $accessText = '';
                            $accessLevel = $_SESSION['AccessLevel'] ?? null; // Obtener el nivel de acceso de la sesión, por defecto null
                            
                            // Usar un switch para determinar el texto según el nivel de acceso
                            switch ($accessLevel) {
                                case 1:
                                    $accessText = 'Administrador';
                                    break;
                                case 2:
                                    $accessText = 'Cajero';
                                    break;
                                case 3:
                                    $accessText = 'None';
                                    break;
                                default:
                                    $accessText = 'Desconocido'; // Texto por defecto si el valor no coincide o no está definido
                                    break;
                            }
                            echo htmlspecialchars($accessText);
                            // Mostrar el texto del nivel de acceso, sanitizando para seguridad
                            ?>
                            <span class="sr-only" id="userIdSpan"><?php echo htmlspecialchars($userID); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user-cog me-2"></i>Perfil</a></li>
                            <li><a id="btn-configuracion" class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Configuración</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="authentication/logout.php"><i
                                        class="fas fa-sign-out-alt me-2"></i>Salir</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar 
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">-->
            <div class="col-md-3 col-lg-2 d-md-block sidebar" id="sidebarTest" >
                <div class="position-sticky pt-3">
                    <!-- Estructura actualizada de enlaces del sidebar -->
                    <ul class="nav flex-column">
                        <!-- Añadir después de la sección de inventario en el sidebar -->
                        <li class="nav-item">
                            <a class="nav-link active"  id="maintenance-link" data-bs-toggle="collapse"
                                data-bs-target="#maintenance-collapse" aria-expanded="false"
                                aria-controls="maintenance-collapse" data-bs-toggle="tooltip" data-bs-placement="right"
                                title="Mantenimiento" >
                                <i class="fas fa-cogs"></i>
                                <span>Mantenimiento</span>
                            </a>
                            <div class="collapse" id="maintenance-collapse" >
                                <ul class="nav flex-column ms-3">
                                    <li class="nav-item">
                                        <a class="nav-link" href="#" id="products-maintenance-link"
                                            data-section="products-maintenance-section" data-bs-toggle="tooltip"
                                            data-bs-placement="right" title="Productos">
                                            <i class="fas fa-box"></i>
                                            <span>Productos</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#" id="clients-link" data-section="clients-section"
                                            data-bs-toggle="tooltip" data-bs-placement="right" title="Clientes">
                                            <i class="fas fa-users"></i>
                                            <span>Clientes</span>
                                        </a>
                                    </li>
                                    
                                </ul>
                            </div>
                        </li>

                    </ul>

                    <div class="text-center mt-4">
                        <button class="btn btn-primary" id="add-product-btn">Agregar Producto</button>
                    </div>

                    <hr class="my-3 bg-light opacity-25">

                    <!-- Información adicional para mostrar en el sidebar -->
                    <div class="company-info mt-4 px-3 text-white">
                        <div class="text-center mb-3">
                            <!--<img src="https://via.placeholder.com/100" alt="Company Logo" class="img-fluid rounded-circle" style="max-width: 80px;">-->
                            <h5 class="mt-3 text-white">
                                <?php echo $_SESSION['InfoCompany']['Name'] ?? 'Nombre de la Empresa'; ?>
                            </h5>
                        </div>
                        <div class="small">
                            <p class="mb-1"><i class="fas fa-clock me-2"></i> Actualizado: <span id="last-update-time">Hoy 15:30</span></p>
                            <p class="mb-0"><i class="fas fa-user me-2"></i> Usuario: <span id="current-user"><a>
                                        <?php echo htmlspecialchars($_SESSION['EmployeeName'] ?? 'Invitado/a'); ?>
                                        <br><?php
                                        $accessText = '';
                                        $accessLevel = $_SESSION['AccessLevel'] ?? null; // Obtener el nivel de acceso de la sesión, por defecto null
                                        
                                        // Usar un switch para determinar el texto según el nivel de acceso
                                        switch ($accessLevel) {
                                            case 1:
                                                $accessText = 'Administrador';
                                                break;
                                            case 2:
                                                $accessText = 'Cajero';
                                                break;
                                            case 3:
                                                $accessText = 'None';
                                                break;
                                            default:
                                                $accessText = 'Desconocido'; // Texto por defecto si el valor no coincide o no está definido
                                                break;
                                        }
                                        echo htmlspecialchars($accessText);
                                        // Mostrar el texto del nivel de acceso, sanitizando para seguridad
                                        ?>
                                    </a></span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-4 ms-sm-auto col-lg-10 px-md-4 py-4 content">
                <!-- Date Range Filter OCULTADO TEMPORALMENTE-->
                <div class="date-filters d-none">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="mb-0"><i class="fas fa-filter me-2"></i> <?php echo $_SESSION['InfoCompany']['Name'] ?? 'Nombre de la Empresa'; ?></h4>
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <span class="input-group-text" onclick="document.getElementById('dateFrom').showPicker()"><i class="fas fa-calendar"></i></span>
                                        <input type="date" class="form-control" id="dateFrom">
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <span class="input-group-text" onclick="document.getElementById('dateTo').showPicker()"><i class="fas fa-calendar"></i></span>
                                        <input type="date" class="form-control" id="dateTo">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary w-100" id="applyDateFilter">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="btn-toolbar mb-1 mb-md-0 d-flex justify-content-between">
                                    <div class="btn-group me-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary active"
                                            id="filterToday">
                                            <i class="fas fa-calendar-day me-1"></i> DIA
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="filterWeek">
                                            <i class="fas fa-calendar-week me-1"></i> SEMANA
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary "
                                            id="filterMonth">
                                            <i class="fas fa-calendar-alt me-1"></i> MES
                                        </button>
                                    </div>

                                    <div class="btn-group me-1 ">
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            id="refreshOverview">
                                            <i class="fas fa-sync-alt me-1"></i> Actualizar
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-file-export me-1"></i> Exportar
                                        </button>
                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>
                </div>
    
                <!-- Products Section -->
                <section id="products-section" class="dashboard-section d-none">
                    <div
                        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                        <h2><i class="fas fa-shopping-basket me-2"></i>Análisis de Productos</h2>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshProducts">
                                    <i class="fas fa-sync-alt me-1"></i> Actualizar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-file-export me-1"></i> Exportar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Row -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <label for="categoryFilter" class="form-label">Categoría</label>
                                            <select class="form-select" id="categoryFilter">
                                                <option value="">Todas las categorías</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="departmentFilter" class="form-label">Departamento</label>
                                            <select class="form-select" id="departmentFilter">
                                                <option value="">Todos los departamentos</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label">&nbsp;</label>
                                            <button class="btn btn-primary w-100" id="applyProductFilters">
                                                Aplicar Filtros
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products -->
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card dashboard-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Productos Más Vendidos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="topProductsTable" class="table table-striped table-hover"
                                            style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Código</th>
                                                    <th>Producto</th>
                                                    <th>Departamento</th>
                                                    <th>Categoría</th>
                                                    <th>Unidades</th>
                                                    <th>Ventas</th>
                                                    <th>Ganancia</th>
                                                    <th>Margen</th>
                                                    <th>Stock</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded dynamically -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Performance Chart -->
                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="card dashboard-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Top 10 Productos por Ventas</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="topProductsChart"></canvas>
                                        <div id="topProductsChartMessage" class="text-center p-2 text-muted"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="card dashboard-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Top 10 Productos por Ganancia</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="topProfitChart"></canvas>
                                        <div id="topProfitChartMessage" class="text-center p-2 text-muted"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card dashboard-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Productos Menos Vendidos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="leastProductsTable" class="table table-striped table-hover"
                                            style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Código</th>
                                                    <th>Producto</th>
                                                    <th>Departamento</th>
                                                    <th>Categoría</th>
                                                    <th>Unidades</th>
                                                    <th>Ventas</th>
                                                    <th>Ganancia</th>
                                                    <th>Precio Prom.</th>
                                                    <th>Margen</th>

                                                    <th>Stock</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded dynamically -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                
                <!-- Añadir antes del footer en el main content -->
                <section id="clients-section" class="dashboard-section d-none">
                    <div
                        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                        <h2><i class="fas fa-users me-2"></i>Gestión de Clientes</h2>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshClients">
                                    <i class="fas fa-sync-alt me-1"></i> Actualizar
                                </button>
                                <button type="button" class="btn btn-sm btn-success" id="addClientBtn">
                                    <i class="fas fa-plus me-1"></i> Nuevo Cliente
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros de Clientes -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <label for="clientNameFilter" class="form-label">Nombre</label>
                                            <input type="text" class="form-control" id="clientNameFilter"
                                                placeholder="Buscar por nombre">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label for="clientCategoryFilter" class="form-label">Categoría</label>
                                            <select class="form-select" id="clientCategoryFilter">
                                                <option value="">Todas las categorías</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label for="clientCityFilter" class="form-label">Ciudad</label>
                                            <input type="text" class="form-control" id="clientCityFilter"
                                                placeholder="Filtrar por ciudad">
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-12 text-end">
                                            <button class="btn btn-primary" id="applyClientFilters">
                                                <i class="fas fa-search me-1"></i> Buscar
                                            </button>
                                            <button class="btn btn-secondary ms-2" id="resetClientFilters">
                                                <i class="fas fa-undo me-1"></i> Limpiar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Clientes -->
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card dashboard-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Listado de Clientes</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="clientsTable" class="table table-striped table-hover"
                                            style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Nombre</th>
                                                    <th>Apellido</th>
                                                    <th>Ciudad</th>
                                                    <th>Teléfono</th>
                                                    <th>Email</th>
                                                    <th>Categoría</th>
                                                    <th>Estado</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Los datos se cargarán dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal para Añadir/Editar Cliente -->
                    <div class="modal fade" id="clientModal" tabindex="-1" aria-labelledby="clientModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="clientModalLabel">Añadir Cliente</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="clientForm">

                                        <input type="hidden" class="form-control" id="clientId" readonly>
                                        <div class="row mb-3">
                                            <div class="col-md-6 ">
                                                <label for="clientName" class="form-label">Nombre</label>
                                                <input type="text" class="form-control" id="clientName" required>
                                            </div>
                                            <div class="col-md-6 d-none" id="apellidoDiv">
                                                <label for="clientLastName" class="form-label ">Apellido</label>
                                                <input type="text" class="form-control" id="clientLastName">
                                            </div>
                                        </div>
                                        <div class="row mb-3 d-none">
                                            <div class="col-md-12">
                                                <label for="clientAddress1" class="form-label ">Dirección 1</label>
                                                <input type="text" class="form-control" id="clientAddress1">
                                            </div>
                                        </div>
                                        <div class="row mb-3 d-none">
                                            <div class="col-md-12">
                                                <label for="clientAddress2" class="form-label ">Dirección 2</label>
                                                <input type="text" class="form-control" id="clientAddress2">
                                            </div>
                                        </div>
                                        <div class="row mb-3 d-none">
                                            <div class="col-md-4">
                                                <label for="clientCity" class="form-label">Ciudad</label>
                                                <input type="text" class="form-control" id="clientCity">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="clientZipCode" class="form-label">Código Postal</label>
                                                <input type="text" class="form-control" id="clientZipCode">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="clientCountry" class="form-label">País</label>
                                                <input type="text" class="form-control" id="clientCountry" value="PR">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="clientPhone" class="form-label">Teléfono</label>
                                                <input type="tel" class="form-control" id="clientPhone">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="clientEmail" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="clientEmail">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="clientCategory" class="form-label">Categoría</label>
                                                <select class="form-select" id="clientCategory">
                                                    <option value="">Seleccionar categoría</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="clientActive" class="form-label">Estado</label>
                                                <select class="form-select" id="clientActive">
                                                    <option value="S">Activo</option>
                                                    <option value="N">Inactivo</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label for="clientCreditLimit" class="form-label">Limite de Credito</label>
                                                <input type="text" class="form-control" id="clientCreditLimit">
                                            </div>
                                            <div class="col-md-4 d-none" id="clientBalanceDiv">
                                                <label for="clientBalance" class="form-label">Balance</label>
                                                <input type="text" class="form-control" id="clientBalance" readonly>
                                            </div>
                                            <div class="col-md-4 d-none" id="clientLastPurchaseDateDiv">
                                                <label for="clientLastPurchaseDate" class="form-label">Última Compra</label>
                                                <input type="text" class="form-control" id="clientLastPurchaseDate" readonly>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cancelar</button>
                                    <button type="button" class="btn btn-primary" id="saveClientBtn">Guardar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Añadir después de la sección de clientes -->
                <section id="products-maintenance-section" class="dashboard-section active row">
                    <div
                        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                        <h2><i class="fas fa-box me-2"></i>Gestión de Productos</h2>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                    id="refreshProductsMaintenance">
                                    <i class="fas fa-sync-alt me-1"></i> Actualizar
                                </button>
                                <button type="button" class="btn btn-sm btn-success" id="addProductBtn">
                                    <i class="fas fa-plus me-1"></i> Nuevo Producto
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros de Productos -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card dashboard-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <label for="productCodeFilter" class="form-label">Código</label>
                                            <input type="text" class="form-control" id="productCodeFilter"
                                                placeholder="Código de producto">
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="productNameFilter" class="form-label">Descripción</label>
                                            <input type="text" class="form-control" id="productNameFilter"
                                                placeholder="Nombre o descripción">
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="productDepartmentFilter" class="form-label">Departamento</label>
                                            <select class="form-select" id="productDepartmentFilter">
                                                <option value="">Todos los departamentos</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="productCategoryFilter" class="form-label">Categoría</label>
                                            <select class="form-select" id="productCategoryFilter">
                                                <option value="">Todas las categorías</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-12 text-end">
                                            <button class="btn btn-primary" id="applyProductFiltersMaintenance">
                                                <i class="fas fa-search me-1"></i> Buscar
                                            </button>
                                            <button class="btn btn-secondary ms-2" id="resetProductFilters">
                                                <i class="fas fa-undo me-1"></i> Limpiar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                                        <!-- El template (oculto por defecto) -->
<template id="modalActualizarProducto">
  <div class="modal-content" style="width: 200px;">
    <input type="text" style="padding: 0; margin: 0; height: 35px; width: 200px; border: 1px solid #a59f9fff; border-radius: 4px; 
                    box-sizing: border-box; font-size: 14px;"  id="datoInput" placeholder="">
    <div class="actions" style="box-shadow: 0 4px 8px rgba(0, 0, 0,0.3); background: #fff;
                border-radius: 4px; height: 30px; width: 200px; 
                display: flex; justify-content: center; align-items: center; gap: 10px;">
      <button class="close-btn"  style="border: none; background: transparent; 
                     height: 25px; width: 25px; display: flex; 
                     justify-content: center; align-items: center; cursor: pointer;">
        <span style="color: red; font-size: 18px;">✖</span></button>
      <button class="save-btn custom-icon-success" style=" padding-left: 10px; border: none; background: transparent; 
                     height: 25px; width: 25px; display: flex; 
                     justify-content: center; align-items: center; cursor: pointer;">
        <span style="color: green; font-size: 18px;">✔</span></button>
    </div>
  </div>
</template>

                    <!-- Tabla de Productos -->
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card dashboard-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Listado de Productos</h5>
                                    
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="productsMaintenanceTable" class="table table-striped table-hover"
                                            style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Código</th>
                                                    <th>Descripción</th>
                                                    <th>Código de Barras</th>
                                                    <th>Precio</th>
                                                    <th>Costo</th>
                                                    <th>Stock</th>
                                                    <th>Departamento</th>
                                                    <th>Categoría</th>
                                                    <!-- <th>Acciones</th> -->
                                                    <!-- <th>Movimiento</th> -->
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Los datos se cargarán dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                </section>


                <!-- Footer -->
                <footer class="pt-4 my-md-5 pt-md-5 border-top">
                    <div class="row">
                        <div class="col-12 col-md text-center">
                            <small class="d-block mb-3 text-muted">© Portal de Ventas RM - 2026</small>
                        </div>
                    </div>
                </footer>
            </main>

        </div>
    </div>
<!-- Modal para Añadir/Editar Producto -->
                    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="productModalLabel">Añadir Producto</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="productForm">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="productCode" class="form-label">Código</label>
                                                <input type="text" class="form-control" id="productCode" name="productCode" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="productBarcode" class="form-label">Código de Barras</label>
                                                <input type="text" class="form-control" id="productBarcode" name="productBarcode">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <label for="productDescription" class="form-label">Descripción</label>
                                                <input type="text" class="form-control" id="productDescription" name="productDescription"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="productCost" class="form-label">Costo</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" step="0.01" class="form-control"
                                                        id="productCost" name="productCost" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="productPrice" class="form-label">Precio</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" step="0.01" class="form-control"
                                                        id="productPrice" name="productPrice" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="productDepartment" class="form-label">Departamento</label>
                                                <select class="form-select" id="productDepartment" name="productDepartment" required>
                                                    <option value="">Seleccionar departamento</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="productCategory" class="form-label">Categoría</label>
                                                <select class="form-select" id="productCategory" name="productCategory" required>
                                                    <option value="">Seleccionar categoría</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="productSupplier" class="form-label">Proveedor</label>
                                                <input type="text" class="form-control" id="productSupplier" name="productSupplier">
                                            </div>
                                            <div class="col-md-6 d-none">
                                                <label for="productLocation" class="form-label">Ubicación</label>
                                                <input type="text" class="form-control" id="productLocation" name="productLocation">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="productTax1" class="form-label">Tax 1</label>
                                                <input type="text" class="form-control" id="productTax1" name="productTax1">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="productTax2" class="form-label">Tax 2</label>
                                                <input type="text" class="form-control" id="productTax2" name="productTax2">
                                            </div>
                                        </div>
                                        <div class="row mb-3 d-none">
                                            <div class="col-md-4">
                                                <label for="productReorderPoint" class="form-label">Punto de
                                                    Reorden</label>
                                                <input type="number" class="form-control" id="productReorderPoint" name="productReorderPoint">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="productReorderQty" class="form-label">Cantidad de
                                                    Reorden</label>
                                                <input type="number" class="form-control" id="productReorderQty" name="productReorderQty">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="productActive" class="form-label">Estado</label>
                                                <select class="form-select" id="productActive" name="productActive">
                                                    <option value="1">Activo</option>
                                                    <option value="0">Inactivo</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mb-3 d-none">
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="productIsFood" name="productIsFood">
                                                    <label class="form-check-label" for="productIsFood">
                                                        Es Alimento
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="productIsWic" name="productIsWic">
                                                    <label class="form-check-label" for="productIsWic">
                                                        Es WIC
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="productIsTouch" name="productIsTouch">
                                                    <label class="form-check-label" for="productIsTouch">
                                                        Es Touch
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="productIsSerie" name="productIsSerie">
                                                    <label class="form-check-label" for="productIsSerie">
                                                        Es Serie
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cancelar</button>
                                    <button type="button" class="btn btn-primary" id="saveProductBtn">Guardar</button>
                                </div>
                            </div>
                        </div>
                    </div>
<!-- Modal editar backend config Bootstrap -->
<div class="modal fade" id="configModal" tabindex="-1" aria-labelledby="configModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="configModalLabel">Configuración Backend</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">

        <div class="login-container">
            <h2>Configuración de Backend</h2>
            <div class="card card-config p-4">
                <div class="form-group mb-3">
                    <label for="backend-ip" class="form-label text-start d-block">IP del backend:</label>
                    <input type="text" id="backend-ip" class="form-control" placeholder="192.168.0.10" 
                           value="<?= htmlspecialchars($config['backend_ip'] ?? '') ?>">
                </div>
                <div class="form-group mb-3">
                    <label for="backend-port" class="form-label text-start d-block">Puerto del backend:</label>
                    <input type="text" id="backend-port" class="form-control" placeholder="3000" 
                           value="<?= htmlspecialchars($config['backend_port'] ?? '') ?>">
                </div>
                <hr class="mt-4">
                <h4>Backends secundarios</h4>
                <script>
                    let extraBackends = <?= json_encode($config["extra_backends"] ?? [], JSON_UNESCAPED_UNICODE) ?>;
                    function renderBackends(){
                    const container =
                        document.getElementById("extraBackendsContainer");

                    container.innerHTML = "";

                    extraBackends.forEach((b,i)=>{

                        container.innerHTML += `
                        <div class="card p-2 mb-2">

                            <input class="form-control mb-1"
                            placeholder="Nombre"
                            value="${b.name || ''}"
                            onchange="extraBackends[${i}].name=this.value">

                            <input class="form-control mb-1"
                            placeholder="IP"
                            value="${b.ip || ''}"
                            onchange="extraBackends[${i}].ip=this.value">

                            <input class="form-control mb-1"
                            placeholder="Puerto"
                            value="${b.port || ''}"
                            onchange="extraBackends[${i}].port=this.value">

                            <button class="btn btn-sm btn-danger"
                            onclick="removeBackend(${i})">
                            Eliminar
                            </button>

                        </div>
                        `;

                    });
                    }
                    function removeBackend(i){
                    extraBackends.splice(i,1);
                    renderBackends();
                    }                                
                </script>

                <div id="extraBackendsContainer">
                    
                    <?php 
                        $extraBackends = $config['extra_backends'] ?? [];

                        foreach ($extraBackends as $i => $b): 
                        ?>

                        <div class="card p-2 mb-2">

                            <input class="form-control mb-1"
                            placeholder="Nombre"
                            value="<?= htmlspecialchars($b['name'] ?? '') ?>"
                            onchange="extraBackends[<?= $i ?>].name=this.value">

                            <input class="form-control mb-1"
                            placeholder="IP"
                            value="<?= htmlspecialchars($b['ip'] ?? '') ?>"
                            onchange="extraBackends[<?= $i ?>].ip=this.value">

                            <input class="form-control mb-1"
                            placeholder="Puerto"
                            value="<?= htmlspecialchars($b['port'] ?? '') ?>"
                            onchange="extraBackends[<?= $i ?>].port=this.value">

                            <button class="btn btn-sm btn-danger"
                            onclick="removeBackend(<?= $i ?>)">
                            Eliminar
                            </button>

                        </div>

                        <?php endforeach; ?>
                </div>

                <button id="addBackend" class="btn btn-outline-primary mt-2">
                Agregar Backend
                </button>
                <button id="save-config" class="btn btn-primary w-100 mt-3">Guardar</button>
            </div>
            <h2>Configuración de Inventario</h2>
            <div class="card card-config p-4">
                <div class="form-group mb-3">
                    <label for="inventoryMonthsOfCover" class="form-label text-start d-block">Meses de cobertura Orden Sugerida:</label>
                    <input type="number" step="0.1" id="inventoryMonthsOfCover" class="form-control" placeholder="1.35" value="<?= htmlspecialchars($config['inventoryMonthsOfCover'] ?? '') ?>">
                </div>
                
                <button id="save-config-inventario" class="btn btn-primary w-100 mt-3">Guardar</button>
            </div>
        </div>

      </div>
    </div>
  </div>
</div> 
<div id="receiveInventoryContainer"></div>
                              
    <!-- JavaScript Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/numeral.js/2.0.6/numeral.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- DataTables Extensions JS -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

    <!-- Scripts personalizados -->
    <script src="js/config.js"></script>
    <!-- Custom JavaScript -->
    <script type="module">

        import { initClientMaintenance,loadClients } from "./js/clients.js";
        import { initializeProductMaintenance} from "./js/maintenance.js";
       


        // Global variables
        let currentDateFrom = moment().format('YYYY-MM-DD');
        let currentDateTo = moment().format('YYYY-MM-DD');
        let charts = {};
        let tables = {};
        let lastClickedLink = "overview-section";

        // Initialize datepickers with current values
        document.getElementById('dateFrom').value = currentDateFrom;
        document.getElementById('dateTo').value = currentDateTo;

         const _backgroundColor = [
    '#0057b8', // Azul
    '#00a651', // Verde
    '#ffc107', // Amarillo
    '#dc3545', // Rojo
    '#6f42c1', // Morado
    '#fd7e14', // Naranja
    '#20c997', // Verde agua
    '#6c757d', // Gris

    '#ff66b2', // Rosa fuerte
    '#17a2b8', // Azul turquesa
    '#8bc34a', // Verde lima
    '#ff5722', // Naranja rojizo
    '#4caf50', // Verde estándar
    '#673ab7', // Púrpura oscuro
    '#3f51b5', // Azul índigo
    '#e91e63', // Rosa intenso
    '#795548', // Marrón
    '#9e9e9e', // Gris claro
    '#607d8b', // Azul grisáceo
    '#cddc39'  // Amarillo verdoso
];





document.getElementById("addBackend")
.addEventListener("click",()=>{

   extraBackends.push({
      name:"",
      ip:"",
      port:""
   });

   renderBackends();

});

        /**
 * Renderiza los ítems de la lista para la página actual.
 * @param {Array} dataToRender - El subconjunto de datos a mostrar en la página.
 */
function renderListItems(dataToRender) {
    departmentList.innerHTML = '';

    dataToRender.forEach((item, index) => {
        const listItem = document.createElement('div');
        listItem.className = 'list-item d-flex justify-content-between align-items-center mb-2';
        
        const indexToData = currentDataDepartments.findIndex(department => department.DepartmentID === item.DepartmentID);
       
        // Usa el índice y el operador módulo para ciclar a través de los colores sin eliminarlos
        const color = _backgroundColor[indexToData % _backgroundColor.length];
        
        listItem.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="color-box me-2" style="background-color: ${color};width: 16px;
    height: 16px;
    margin-right: 5px;
    border: 1px solid #ccc;"></div>
                <span class="category fw-bold">${item.Department || 'Sin Departamento'}</span>
            </div>
            <span class="value">${formatCurrencyP(item.TotalSales || 0)}</span>
        `;
        departmentList.appendChild(listItem);
    });
}
        /**
 * Actualiza el estado (habilitado/deshabilitado) de los controles de paginación.
 * @param {number} totalPages - El número total de páginas.
 */
function updateNavigationControls(totalPages) {
    if (currentPage === 1) {
        prevBtnLi.classList.add('disabled');
    } else {
        prevBtnLi.classList.remove('disabled');
    }

    if (currentPage === totalPages) {
        nextBtnLi.classList.add('disabled');
    } else {
        nextBtnLi.classList.remove('disabled');
    }
}

/**
 * Maneja la paginación para los ítems de venta.
 * @param {Array} newData - El conjunto completo de datos a paginar.
 */
function setupPagination(newData) {
    currentDataDepartments = newData;
    currentPage = 1; // Reinicia la página a 1 cuando los datos cambian
    
    const totalPages = Math.ceil(currentDataDepartments.length / itemsPerPage);

    function displayCurrentPage() {
        const start = (currentPage - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        const itemsToDisplay = currentDataDepartments.slice(start, end);
        renderListItems(itemsToDisplay);
        updateNavigationControls(totalPages);
    }
    
    // Limpia los listeners para evitar duplicados
    prevBtn.removeEventListener('click', handlePrevClick);
    nextBtn.removeEventListener('click', handleNextClick);

    function handlePrevClick(e) {
        e.preventDefault();
        if (currentPage > 1) {
            currentPage--;
            displayCurrentPage();
        }
    }

    function handleNextClick(e) {
        e.preventDefault();
        if (currentPage < totalPages) {
            currentPage++;
            displayCurrentPage();
        }
    }

    prevBtn.addEventListener('click', handlePrevClick);
    nextBtn.addEventListener('click', handleNextClick);

    // Muestra la primera página al inicializar
    displayCurrentPage();
}
/**
 * Renderiza los ítems de la lista de métodos de pago para la página actual.
 * @param {Array} dataToRender - El subconjunto de datos a mostrar en la página.
 */
function renderPaymentMethodItems(dataToRender) {
    paymentMethodsList.innerHTML = '';
    
    dataToRender.forEach((item, index) => {
        const listItem = document.createElement('div');
        listItem.className = 'list-item d-flex justify-content-between align-items-center mb-2';
        
        // Usa el índice y el operador módulo para ciclar a través de los colores
        
        
        listItem.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="color-box me-2" style="background-color: ${item.color};width: 16px;
    height: 16px;
    margin-right: 5px;
    border: 1px solid #ccc;"></div>
                <span class="category fw-bold">${item.name || 'Sin Método'}</span>
            </div>
            <span class="value">${formatCurrencyP(item.value || 0)}</span>
        `;
        paymentMethodsList.appendChild(listItem);
    });
}
/**
 * Actualiza el estado (habilitado/deshabilitado) de los controles de paginación para los métodos de pago.
 * @param {number} totalPages - El número total de páginas.
 */
function updatePaymentMethodNavigation(totalPages) {
    if (currentPagePaymentMethods === 1) {
        prevBtnLiPaymentMethods.classList.add('disabled');
    } else {
        prevBtnLiPaymentMethods.classList.remove('disabled');
    }

    if (currentPagePaymentMethods === totalPages) {
        nextBtnLiPaymentMethods.classList.add('disabled');
    } else {
        nextBtnLiPaymentMethods.classList.remove('disabled');
    }
}

/**
 * Configura y gestiona la paginación para la lista de métodos de pago.
 * @param {Array} newData - El conjunto completo de datos a paginar.
 */
function setupPaymentMethodPagination(newData) {
    currentPaymentMethodsData = newData;
    currentPagePaymentMethods = 1; // Reinicia la página a 1 cuando los datos cambian
    
    const totalPages = Math.ceil(currentPaymentMethodsData.length / itemsPerPagePaymentMethods);

    function displayCurrentPaymentMethodPage() {
        const start = (currentPagePaymentMethods - 1) * itemsPerPagePaymentMethods;
        const end = start + itemsPerPagePaymentMethods;
        const itemsToDisplay = currentPaymentMethodsData.slice(start, end);
        renderPaymentMethodItems(itemsToDisplay);
        updatePaymentMethodNavigation(totalPages);
    }
    
    // Limpia los listeners antiguos para evitar duplicados
    prevBtnPaymentMethods.removeEventListener('click', handlePrevClickPaymentMethods);
    nextBtnPaymentMethods.removeEventListener('click', handleNextClickPaymentMethods);

    function handlePrevClickPaymentMethods(e) {
        e.preventDefault();
        if (currentPagePaymentMethods > 1) {
            currentPagePaymentMethods--;
            displayCurrentPaymentMethodPage();
        }
    }

    function handleNextClickPaymentMethods(e) {
        e.preventDefault();
        if (currentPagePaymentMethods < totalPages) {
            currentPagePaymentMethods++;
            displayCurrentPaymentMethodPage();
        }
    }

    // Agrega los nuevos listeners
    prevBtnPaymentMethods.addEventListener('click', handlePrevClickPaymentMethods);
    nextBtnPaymentMethods.addEventListener('click', handleNextClickPaymentMethods);

    // Muestra la primera página al inicializar
    displayCurrentPaymentMethodPage();
}
/**
 * Renderiza los ítems de la lista de métodos de pago para la página actual.
 * @param {Array} dataToRender - El subconjunto de datos a mostrar en la página.
 */
function renderInventoryItems(dataToRender) {
   inventoryList.innerHTML = '';

    dataToRender.forEach((item, index) => {
        const listItem = document.createElement('div');
        listItem.className = 'list-item d-flex justify-content-between align-items-center mb-2';
        
        // Usa el índice y el operador módulo para ciclar a través de los colores
        
        
        listItem.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="color-box me-2" style="background-color: ${item[5]};width: 16px;
    height: 16px;
    margin-right: 5px;
    border: 1px solid #ccc;"></div>
                <span class="category fw-bold">${item[0] || 'Sin Método'}</span>
            </div>
            <span class="value">${item[1] || 0}</span>
        `;
        inventoryList.appendChild(listItem);
    });
}
/**
 * Actualiza el estado (habilitado/deshabilitado) de los controles de paginación para los métodos de pago.
 * @param {number} totalPages - El número total de páginas.
 */
function updateInventoryNavigation(totalPages) {
    if (currentPageInventory === 1) {
        prevBtnLiInventory.classList.add('disabled');
    } else {
        prevBtnLiInventory.classList.remove('disabled');
    }

    if (currentPageInventory === totalPages) {
        nextBtnLiInventory.classList.add('disabled');
    } else {
        nextBtnLiInventory.classList.remove('disabled');
    }
}

/**
 * Configura y gestiona la paginación para la lista de métodos de pago.
 * @param {Array} newData - El conjunto completo de datos a paginar.
 */
function setupInventoryPagination(newData) {
    currentInventoryData = newData;
    currentPageInventory = 1; // Reinicia la página a 1 cuando los datos cambian

    const totalPages = Math.ceil(currentInventoryData.length / itemsPerPageInventory);

    function displayCurrentInventoryPage() {
        const start = (currentPageInventory - 1) * itemsPerPageInventory;
        const end = start + itemsPerPageInventory;
        const itemsToDisplay = currentInventoryData.slice(start, end);
        renderInventoryItems(itemsToDisplay);
        updateInventoryNavigation(totalPages);
    }
    
    // Limpia los listeners antiguos para evitar duplicados
    prevBtnInventory.removeEventListener('click', handlePrevClickInventory);
    nextBtnInventory.removeEventListener('click', handleNextClickInventory);

    function handlePrevClickInventory(e) {
        e.preventDefault();
        if (currentPageInventory > 1) {
            currentPageInventory--;
            displayCurrentInventoryPage();
        }
    }

    function handleNextClickInventory(e) {
        e.preventDefault();
        if (currentPageInventory < totalPages) {
            currentPageInventory++;
            displayCurrentInventoryPage();
        }
    }

    // Agrega los nuevos listeners
    prevBtnInventory.addEventListener('click', handlePrevClickInventory);
    nextBtnInventory.addEventListener('click', handleNextClickInventory);

    // Muestra la primera página al inicializar
    displayCurrentInventoryPage();
}
document.addEventListener("DOMContentLoaded", () => {
  // Seleccionamos todos los enlaces del menú
  const menuLinks = document.querySelectorAll(".nav-link");

  // Añadimos evento de clic a cada uno
  menuLinks.forEach(link => {
    link.addEventListener("click", async  (event) => {
      event.preventDefault();
                                               
      // 1️⃣ Obtenemos el id de la sección del atributo data-section
      const sectionId = link.getAttribute("data-section");
      
       if(sectionId){
           // 2️⃣ Ocultamos todas las secciones
        document.querySelectorAll(".dashboard-section").forEach(sec => {
            sec.classList.add("d-none");
            sec.classList.remove("active");
        }); 

        // 3️⃣ Mostramos la sección correspondiente
        const sectionToShow = document.getElementById(sectionId);
        if (sectionToShow) {
            sectionToShow.classList.remove("d-none");
            sectionToShow.classList.add("active");
        } 

        // 4️⃣ Opcional: marcar el botón activo visualmente
        menuLinks.forEach(item => item.classList.remove("active"));
        link.classList.add("active");
        lastClickedLink = sectionId;  
        // 5️⃣ Aquí puedes cargar los datos específicos según la sección
        await cargarDatosSeccion(sectionId);
       }                                     
      
    });
  });
});

// Función que decide qué cargar según la sección
async function cargarDatosSeccion(sectionId) {
  switch (sectionId) {
    case "inventory-section":
        toggleLoading(true);
        document.getElementById("refreshOverview").classList.add("d-none");
      console.log("🏷 Cargando datos de inventario...");
      await Promise.all([ loadInventoryValue(),
                 loadLowLevelItems()
      ])
      toggleLoading(false);
      break;
    
    case "clients-section":
      console.log("👥 Cargando datos de clientes...");
      toggleLoading(true);
      document.getElementById("refreshOverview").classList.add("d-none");
      await initClientMaintenance();
      toggleLoading(false);
      break;
    case "products-maintenance-section":
      console.log("🛠 Cargando datos de mantenimiento de productos...");
      toggleLoading(true);
      document.getElementById("refreshOverview").classList.add("d-none");
        await initializeProductMaintenance();
        console.log("Productos cargados para mantenimiento.");
      toggleLoading(false);
      break;
    default:
        console.log("Id sección: ", sectionId);
      console.log("📊 Mostrando vista general...");
      break;
  }
}
document.getElementById("save-config").addEventListener("click", function () {
    const backend_ip = document.getElementById("backend-ip").value.trim();
    const backend_port = document.getElementById("backend-port").value.trim();
        
    if (backend_ip && backend_port) {
        const config = { backend_ip, backend_port,
            extra_backends: extraBackends
         };

        fetch("setup/save_config.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(config)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "ok") {
                alert("Configuración guardada correctamente.");
                bootstrap.Modal.getInstance(document.getElementById('configModal')).hide();
                window.location.href='authentication/logout.php'; // Recarga la página para aplicar cambios
            } else {
                alert("Error al guardar la configuración en el servidor.");
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("Ocurrió un error de red al intentar guardar la configuración.");
        });
    } else {
        alert("Por favor ingresa IP y puerto.");
    }
});
document.getElementById("save-config-inventario").addEventListener("click", function () {
    const monthsOfCover = document.getElementById("inventoryMonthsOfCover").value.trim();

    if (monthsOfCover) {
        const config = { inventoryMonthsOfCover: monthsOfCover };

        fetch("setup/save_config.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(config)
        })
        .then(res => {
            console.log("Raw response:", res); // Depuración
            return res.json();
        })
        .then(data => {
            console.log("Response data:", data); // Depuración
            if (data.status === "ok") {
                alert("Configuración guardada correctamente.");
                bootstrap.Modal.getInstance(document.getElementById('configModal')).hide();
            } else {
                alert("Error al guardar la configuración en el servidor.");
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("Ocurrió un error de red al intentar guardar la configuración.");
        });
    } else {
        alert("Por favor ingresa meses de cobertura.");
    }
});
document.getElementById("btn-configuracion").addEventListener("click", function (e) {
    e.preventDefault(); // Evita recargar la página

    // Usar la API de Bootstrap para abrir el modal
    var configModal = new bootstrap.Modal(document.getElementById('configModal'));
    configModal.show();
});


        // Show/Hide loading overlay
        function toggleLoading(show = true) {
            console.log('toggleLoading called with show =', show);
            const loader = document.getElementById('loadingOverlay');
            if (show) {
                loader.style.display = 'flex';
            } else {
                loader.style.display = 'none';
            }
        }

        // Format currency
        function formatCurrencyP(value) {
           const n = Number(value) || 0;
    const formatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    });
    return formatter.format(n);
        }

        // Format percentage
        function formatPercentage(value) {
            numeral.locale('en');
            return numeral(value / 100).format('0.0%');
        }

        // Format number with commas
        function formatNumber(value) {
            return numeral(value).format('0,0');
        }

        // Safely convert to locale string
        function safeToLocaleString(value) {
            if (value === null || value === undefined || isNaN(value)) {
                return '0';
            }
            return formatNumber(value);
        }
        
        // Switch between dashboard sections
        function switchSection(sectionId) {
            document.querySelectorAll('.dashboard-section').forEach(section => {
                section.classList.add('d-none');
            });
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });

            const targetSection = document.getElementById(sectionId);
            if (targetSection) {
                targetSection.classList.remove('d-none');
            }

            const targetLink = document.querySelector(`[data-section="${sectionId}"]`);
            if (targetLink) {
                targetLink.classList.add('active');
            }
        }

        // Navigation event listeners
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const sectionId = this.getAttribute('data-section');
                if(sectionId){
                    switchSection(sectionId);
                }
                
            });
        });

        // Apply date filter
        document.getElementById('applyDateFilter').addEventListener('click', function () {
            const newDateFrom = document.getElementById('dateFrom').value;
            const newDateTo = document.getElementById('dateTo').value;

            if (newDateFrom && newDateTo) {
                currentDateFrom = newDateFrom;
                currentDateTo = newDateTo;
                updateDateInputs();
                // Refresh all data
                console.log("Applying date filter:", lastClickedLink, currentDateFrom, currentDateTo);
                cargarDatosSeccion(lastClickedLink);
            } else {
                alert('Por favor seleccione un rango de fechas válido.');
            }
        });
        // Helper para formatear una fecha en el formato YYYY/MM/DD
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0'); // getMonth() es 0-indexado
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // **NUEVA FUNCIÓN:** Actualiza los campos de input de fecha en el HTML
        function updateDateInputs() {
            
            const fromInput = document.getElementById('dateFrom');
            const toInput = document.getElementById('dateTo');

            if (fromInput) {
                fromInput.value = formatDateToInput(currentDateFrom);
            }
            if (toInput) {
                toInput.value = formatDateToInput(currentDateTo);
            }
        }
        /**
         * Convierte un objeto Date a la cadena "yyyy-MM-dd" requerida por los inputs HTML.
         * @param {Date} dateObj - El objeto Date a formatear.
         * @returns {string} La fecha formateada o cadena vacía si no es un objeto Date válido.
         */
        function formatDateToInput(dateObj) {
            if (!(dateObj instanceof Date) || isNaN(dateObj)) {
                return dateObj.toString().substring(0,10);
            }

            // 1. Obtiene el año (yyyy)
            const year = dateObj.getFullYear();
            
            // 2. Obtiene el mes (MM) y añade un cero inicial si es necesario
            // getMonth() devuelve 0 para Enero, por eso se suma 1.
            const month = String(dateObj.getMonth() + 1).padStart(2, '0');
            
            // 3. Obtiene el día (dd) y añade un cero inicial si es necesario
            const day = String(dateObj.getDate()).padStart(2, '0');

            // 4. Combina en el formato yyyy-MM-dd
            return `${year}-${month}-${day}`;
        }
        /**
         * Convierte un objeto Date a la cadena "yyyy/MM/dd" requerida por los inputs HTML.
         * @param {Date} dateObj - El objeto Date a formatear.
         * @returns {string} La fecha formateada o cadena vacía si no es un objeto Date válido.
         */
        function formatDateToAPI(dateObj) {
            if (!(dateObj instanceof Date) || isNaN(dateObj)) {
                return dateObj.toString().substring(0,10);
            }

            // 1. Obtiene el año (yyyy)
            const year = dateObj.getFullYear();
            
            // 2. Obtiene el mes (MM) y añade un cero inicial si es necesario
            // getMonth() devuelve 0 para Enero, por eso se suma 1.
            const month = String(dateObj.getMonth() + 1).padStart(2, '0');
            
            // 3. Obtiene el día (dd) y añade un cero inicial si es necesario
            const day = String(dateObj.getDate()).padStart(2, '0');

            // 4. Combina en el formato yyyy-MM-dd
            return `${year}-${month}-${day}`;
        }


        // **NUEVA FUNCIÓN:** Gestiona el estado 'active' de los botones de filtro
        function setActiveFilterButton(activeButtonId) {
            // Obtén todos los botones de filtro por sus IDs
            const filterButtons = [
                document.getElementById('filterToday'),
                document.getElementById('filterWeek'),
                document.getElementById('filterMonth')
                // Agrega aquí IDs de otros botones de filtro si los tienes, por ejemplo, 'filterYear'
            ];

            filterButtons.forEach(button => {
                if (button) { // Asegúrate de que el botón existe
                    if (button.id === activeButtonId) {
                        button.classList.add('active'); // Añade la clase 'active' al botón clickeado/activo
                    } else {
                        button.classList.remove('active'); // Remueve la clase 'active' de los demás
                    }
                }
            });
        }

        // Función para cargar datos del DÍA (fecha de hoy)
        function loadTodayData() {
            setActiveFilterButton('filterToday'); // Establece 'DIA' como el botón activo
            const today = new Date();
            currentDateFrom = formatDate(today);
            currentDateTo = formatDate(today);
            updateDateInputs(); // Actualiza los campos de fecha en el HTML
            loadAllData();

            // Aquí puedes llamar a tu función principal para cargar los datos con estas fechas
            // Por ejemplo: loadYourActualDataFunction(currentDateFrom, currentDateTo);
        }

        // Función para cargar datos de la SEMANA (desde el lunes de esta semana hasta hoy)
        function loadWeekData() {
            setActiveFilterButton('filterWeek'); // Establece 'SEMANA' como el botón activo
            const today = new Date();
            const dayOfWeek = today.getDay(); // 0 = Domingo, 1 = Lunes, ..., 6 = Sábado

            // Calcula cuántos días retroceder para llegar al lunes de esta semana
            const diffToMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;

            const firstDayOfWeek = new Date(today);
            firstDayOfWeek.setDate(today.getDate() - diffToMonday);

            currentDateFrom = formatDate(firstDayOfWeek);
            currentDateTo = formatDate(today); // La fecha de fin es siempre hoy
            updateDateInputs(); // Actualiza los campos de fecha en el HTML
            loadAllData();
            // Aquí puedes llamar a tu función principal para cargar los datos
            // Por ejemplo: loadYourActualDataFunction(currentDateFrom, currentDateTo);
        }

        // Función para cargar datos del MES (desde el primer día de este mes hasta hoy)
        function loadMonthData() {
            setActiveFilterButton('filterMonth'); // Establece 'MES' como el botón activo
            const today = new Date();
            // Obtiene el primer día del mes actual
            const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);

            currentDateFrom = formatDate(firstDayOfMonth);
            currentDateTo = formatDate(today); // La fecha de fin es siempre hoy
            updateDateInputs(); // Actualiza los campos de fecha en el HTML
            loadAllData();
            // Aquí puedes llamar a tu función principal para cargar los datos
            // Por ejemplo: loadYourActualDataFunction(currentDateFrom, currentDateTo);
        }


        // Función mejorada para crear DataTables con estilo mejorado - versión corregida
        function createDataTable(tableId, data, columns, order = [[0, 'desc']]) {

            // Verificar que los datos son válidos
            if (!Array.isArray(data)) {
                console.error(`Los datos para la tabla '${tableId}' no son un array válido`);
                return null;
            }

            try {
                // Obtener el contenedor padre de la tabla actual
                const tableElement = document.getElementById(tableId);
                if (!tableElement) {
                    console.error(`Tabla con ID '${tableId}' no encontrada en el DOM`);
                    return null;
                }

                const parentElement = tableElement.parentNode;
                if (!parentElement) {
                    console.error(`No se pudo encontrar el elemento padre para la tabla '${tableId}'`);
                    return null;
                }

                // ENFOQUE RADICAL: Eliminar completamente la tabla existente
                // y crear una nueva desde cero

                // 1. Crear una nueva tabla con el mismo ID
                const newTable = document.createElement('table');
                newTable.id = tableId;
                newTable.className = 'table table-striped table-hover';
                newTable.style.width = '100%';

                // 2. Crear estructura básica de la tabla
                const thead = document.createElement('thead');
                const headerRow = document.createElement('tr');

                // Añadir encabezados de columna
                columns.forEach(col => {
                    const th = document.createElement('th');
                    th.textContent = col.title;
                    headerRow.appendChild(th);
                });

                thead.appendChild(headerRow);
                newTable.appendChild(thead);

                // Añadir tbody vacío
                const tbody = document.createElement('tbody');
                newTable.appendChild(tbody);

                // 3. Reemplazar la tabla antigua con la nueva
                parentElement.innerHTML = ''; // Limpiar todo el contenido
                parentElement.appendChild(newTable);

                // Usar jQuery para seleccionar la nueva tabla
                const $newTable = $(`#${tableId}`);

                // Inicializar con opciones mejoradas
                const dataTableInstance = $newTable.DataTable({
                    data: data,
                    columns: columns,
                    order: order,
                    pageLength: 10,
                    lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
                    // Modificar el DOM para tener búsqueda junto a los botones
                    dom: '<"row mb-3"<"col-md-6"B><"col-md-6 d-flex justify-content-end"f>>rt<"row mt-3"<"col-md-5"i><"col-md-7"p>>',
                    buttons: [
                        {
                            extend: 'excel',
                            text: '<i class="fas fa-file-excel me-1"></i> Excel',
                            className: 'btn btn-sm btn-success me-2'
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="fas fa-file-pdf me-1"></i> PDF',
                            className: 'btn btn-sm btn-danger me-2',
                            // Configuración para PDF con vista previa
                            title: function () {
                                return 'Reporte - ' + tableId;
                            },
                            // Orientation fija a landscape para tablas con muchas columnas
                            orientation: 'landscape',
                            // Usar exportOptions para controlar qué datos se exportan
                            exportOptions: {
                                columns: ':visible',
                                format: {
                                    header: function (data, columnIdx) {
                                        return columns[columnIdx].title || data;
                                    },
                                    body: function (data, row, column, node) {
                                        // Asegurar que los datos sean strings
                                        return data !== null ? String(data) : '';
                                    }
                                }
                            },
                            customize: function (doc) {
                                // Determinar cantidad de columnas
                                let colCount = 0;
                                if (doc.content[1] && doc.content[1].table && doc.content[1].table.body && doc.content[1].table.body[0]) {
                                    colCount = doc.content[1].table.body[0].length;
                                }

                                // Ajustar orientación según número de columnas
                                const hasManyColumns = colCount > 5;
                                if (!hasManyColumns) {
                                    doc.pageOrientation = 'portrait';
                                }

                                // Personalizar PDF - tamaño de fuente adaptativo
                                doc.defaultStyle.fontSize = hasManyColumns ? 8 : 10;
                                doc.styles.tableHeader.fontSize = hasManyColumns ? 9 : 11;
                                doc.styles.tableHeader.fillColor = '#4CAF50';
                                doc.styles.tableHeader.color = '#FFFFFF';

                                // Configurar alineación para los encabezados
                                doc.styles.tableHeader.alignment = 'center';

                                // Ajustar márgenes según orientación
                                doc.pageMargins = hasManyColumns ? [10, 20, 10, 20] : [20, 25, 20, 25];

                                // Definir anchos específicos para cada columna
                                if (doc.content[1] && doc.content[1].table) {
                                    // Crear un array de anchos
                                    let columnWidths = [];

                                    // Si el número de columnas es conocido, asignar anchos específicos
                                    if (colCount > 0) {
                                        for (let i = 0; i < colCount; i++) {
                                            // Calcular el ancho basado en el tipo de columna
                                            const columnTitle = (columns[i]?.title || '').toLowerCase();

                                            if (hasManyColumns) {
                                                // Para muchas columnas, optimizar el espacio
                                                if (columnTitle.includes('producto') || columnTitle.includes('descripción')) {
                                                    columnWidths.push('auto');
                                                } else if (columnTitle.includes('código')) {
                                                    columnWidths.push('10%');
                                                } else if (columnTitle.includes('venta') || columnTitle.includes('ganancia') ||
                                                    columnTitle.includes('precio') || columnTitle.includes('costo')) {
                                                    columnWidths.push('12%');
                                                } else if (columnTitle.includes('unidades') || columnTitle.includes('stock') ||
                                                    columnTitle.includes('cantidad')) {
                                                    columnWidths.push('8%');
                                                } else {
                                                    columnWidths.push('*');
                                                }
                                            } else {
                                                // Para pocas columnas, distribución más equitativa
                                                if (columnTitle.includes('producto') || columnTitle.includes('descripción')) {
                                                    columnWidths.push('30%'); // Dar más espacio para textos
                                                } else if (columnTitle.includes('código')) {
                                                    columnWidths.push('15%');
                                                } else {
                                                    columnWidths.push('*');
                                                }
                                            }
                                        }

                                        doc.content[1].table.widths = columnWidths;
                                    } else {
                                        // Si no podemos determinar el número exacto, usar auto para todos
                                        doc.content[1].table.widths = Array(colCount).fill('auto');
                                    }
                                }

                                // Ajustar el estilo de las celdas para que el texto se ajuste
                                doc.styles.tableBodyEven.fontSize = doc.defaultStyle.fontSize;
                                doc.styles.tableBodyOdd.fontSize = doc.defaultStyle.fontSize;

                                // Alineación personalizada para cada columna
                                if (doc.content[1] && doc.content[1].table && doc.content[1].table.body) {
                                    // Alineación de encabezados
                                    if (doc.content[1].table.body[0]) {
                                        for (let i = 0; i < doc.content[1].table.body[0].length; i++) {
                                            // Asegurarse de que el encabezado exista
                                            if (doc.content[1].table.body[0][i]) {
                                                // Centrar todos los encabezados
                                                doc.content[1].table.body[0][i].alignment = 'center';

                                                // Aplicar estilo a celdas para evitar que se corten
                                                doc.content[1].table.body[0][i].noWrap = false;
                                            }
                                        }
                                    }

                                    // Alineación de contenido
                                    for (let row = 1; row < doc.content[1].table.body.length; row++) {
                                        for (let col = 0; col < doc.content[1].table.body[row].length; col++) {
                                            const cell = doc.content[1].table.body[row][col];

                                            // Asegurarse de que la celda exista
                                            if (cell) {
                                                // Permitir ajuste de texto
                                                cell.noWrap = false;

                                                // Determinar tipo de columna para alineación
                                                const columnTitle = (columns[col]?.title || '').toLowerCase();

                                                if (columnTitle.includes('código')) {
                                                    // Códigos centrados
                                                    cell.alignment = 'center';
                                                } else if (columnTitle.includes('venta') ||
                                                    columnTitle.includes('ganancia') ||
                                                    columnTitle.includes('precio') ||
                                                    columnTitle.includes('costo') ||
                                                    columnTitle.includes('unidades') ||
                                                    columnTitle.includes('stock') ||
                                                    columnTitle.includes('cantidad')) {
                                                    // Valores numéricos a la derecha
                                                    cell.alignment = 'right';
                                                } else {
                                                    // Texto normal a la izquierda
                                                    cell.alignment = 'left';
                                                }
                                            }
                                        }
                                    }
                                }

                                // Mejorar la definición de la tabla
                                if (doc.content[1] && doc.content[1].table) {
                                    // Añadir bordes a la tabla
                                    doc.content[1].layout = {
                                        hLineWidth: function (i, node) { return 0.5; },
                                        vLineWidth: function (i, node) { return 0.5; },
                                        hLineColor: function (i, node) { return '#aaa'; },
                                        vLineColor: function (i, node) { return '#aaa'; },
                                        paddingLeft: function (i, node) { return 4; },
                                        paddingRight: function (i, node) { return 4; },
                                        paddingTop: function (i, node) { return 3; },
                                        paddingBottom: function (i, node) { return 3; }
                                    };
                                }

                            },
                            // Usar el método de abrir en una nueva ventana directamente
                            action: function (e, dt, button, config) {
                                // Prevenir comportamiento por defecto
                                e.preventDefault();

                                // Usar el método nativo de pdfMake para abrir en ventana
                                $.fn.dataTable.ext.buttons.pdfHtml5.action.call(
                                    this,
                                    e,
                                    dt,
                                    button,
                                    $.extend(true, {}, config, {
                                        download: 'open' // Esto hará que se abra en una nueva ventana
                                    })
                                );
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="fas fa-print me-1"></i> Imprimir',
                            className: 'btn btn-sm btn-info me-2'
                        },
                        // Añadir botón para mostrar/ocultar columnas
                        {
                            extend: 'colvis',
                            text: '<i class="fas fa-columns me-1"></i> Columnas',
                            className: 'btn btn-sm btn-primary',
                            postfixButtons: ['colvisRestore'],
                            columns: ':not(:first-child)' // Opcional: evitar ocultar la primera columna
                        }
                    ],
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Buscar...",
                        lengthMenu: "Mostrar _MENU_ registros",
                        info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                        infoEmpty: "Mostrando 0 a 0 de 0 registros",
                        infoFiltered: "(filtrado de _MAX_ registros totales)",
                        zeroRecords: "No se encontraron registros coincidentes",
                        emptyTable: "No hay datos disponibles en la tabla",
                        paginate: {
                            first: '<i class="fas fa-angle-double-left"></i>',
                            previous: '<i class="fas fa-angle-left"></i>',
                            next: '<i class="fas fa-angle-right"></i>',
                            last: '<i class="fas fa-angle-double-right"></i>'
                        },
                        // Añadir traducciones para colvis (mostrar/ocultar columnas)
                        buttons: {
                            colvis: "Mostrar/Ocultar",
                            colvisRestore: "Restaurar columnas"
                        }
                    },
                    pagingType: "full_numbers",
                    // Estilo personalizado para componentes
                    initComplete: function () {
                        // Agregar clases adicionales a los elementos de DataTables

                        // Mejorar la caja de búsqueda
                        $('.dataTables_filter input').addClass('form-control form-control-sm');
                        $('.dataTables_filter input').css({
                            'min-width': '250px',
                            'display': 'inline-block'
                        });

                        // Mejorar el selector de registros por página
                        $('.dataTables_length select').addClass('form-select form-select-sm');
                        $('.dataTables_length select').css({
                            'padding-right': '25px',
                            'background-position': 'right 0.5rem center'
                        });

                        // Mejorar la paginación
                        $('.dataTables_paginate').addClass('pagination-container');
                        $('.dataTables_paginate .paginate_button').addClass('btn btn-sm');

                        // Estilizar los botones de paginación
                        $('.dataTables_paginate .paginate_button:not(.disabled)').css({
                            'border-radius': '4px',
                            'margin': '0 2px',
                            'border': '1px solid #dee2e6',
                            'background-color': '#fff',
                            'cursor': 'pointer',
                            'color': '#0d6efd',
                            'padding': '0.25rem 0.5rem',
                            'font-size': '0.875rem'
                        });

                        // Estilo para el botón de página actual
                        $('.dataTables_paginate .paginate_button.current').css({
                            'background-color': '#0d6efd',
                            'color': '#fff',
                            'border-color': '#0d6efd',
                            'font-weight': 'bold'
                        });

                        // Estilo para botones deshabilitados
                        $('.dataTables_paginate .paginate_button.disabled').css({
                            'color': '#6c757d',
                            'cursor': 'not-allowed',
                            'background-color': '#fff',
                            'border-color': '#dee2e6'
                        });

                        // Efectos hover para botones de paginación
                        $('.dataTables_paginate .paginate_button:not(.current):not(.disabled)').hover(
                            function () {
                                $(this).css({
                                    'background-color': '#f8f9fa',
                                    'color': '#0a58ca'
                                });
                            },
                            function () {
                                $(this).css({
                                    'background-color': '#fff',
                                    'color': '#0d6efd'
                                });
                            }
                        );

                        // Alinear información y paginación
                        $('.dataTables_info').css({
                            'padding-top': '0.5rem',
                            'margin-bottom': '0'
                        });

                        // Ajustar el container de la información
                        $('.dataTables_info').parent().addClass('d-flex align-items-center');

                        // Ajustar espaciado general
                        $('.dataTables_wrapper').css({
                            'padding': '0',
                            'margin-bottom': '1rem'
                        });

                        // Mejorar la apariencia de los botones de acción
                        $('.dt-buttons .btn').css({
                            'box-shadow': 'none'
                        });

                        // Aplicar estilos a la tabla en sí
                        $(this.api().table().node()).addClass('table-bordered');

                        // Mejorar los encabezados de las columnas
                        $(this.api().table().header()).css({
                            'background-color': '#f8f9fa',
                            'font-weight': 'bold'
                        });

                        // Ajustar los botones de columnas visibles
                        $('.buttons-colvis').css({
                            'position': 'relative'
                        });

                        // Añadir animaciones a las filas cuando se filtran
                        this.api().on('draw', function () {
                            $('.dataTable tbody tr').css({
                                'transition': 'background-color 0.3s'
                            });
                        });

                        // Mejorar interacción al hover en filas
                        $('.dataTable tbody tr').hover(
                            function () {
                                $(this).css({
                                    'background-color': 'rgba(13, 110, 253, 0.05)'
                                });
                            },
                            function () {
                                $(this).css({
                                    'background-color': ''
                                });
                            }
                        );

                        // Ajustar la apariencia del menú de columnas visibles
                        $('.dt-button-collection').css({
                            'border-radius': '4px',
                            'border': '1px solid rgba(0,0,0,.15)',
                            'padding': '0.5rem 0',
                            'box-shadow': '0 .5rem 1rem rgba(0,0,0,.175)',
                            'background-color': '#fff'
                        });

                        // Mejorar los botones del menú de columnas
                        $('.dt-button-collection .dt-button').css({
                            'display': 'block',
                            'padding': '0.25rem 1.5rem',
                            'clear': 'both',
                            'font-weight': '400',
                            'color': '#212529',
                            'text-align': 'inherit',
                            'white-space': 'nowrap',
                            'background-color': 'transparent',
                            'border': '0'
                        });

                        // Hover para los botones del menú de columnas
                        $('.dt-button-collection .dt-button').hover(
                            function () {
                                $(this).css({
                                    'color': '#16181b',
                                    'background-color': '#f8f9fa'
                                });
                            },
                            function () {
                                $(this).css({
                                    'color': '#212529',
                                    'background-color': 'transparent'
                                });
                            }
                        );

                        // Mejorar el aspecto del botón de restaurar columnas
                        $('.dt-button-collection .buttons-colvisRestore').css({
                            'font-weight': 'bold',
                            'border-top': '1px solid #e9ecef',
                            'margin-top': '0.25rem'
                        });
                    }
                });
return dataTableInstance;

            } catch (error) {
                console.error(`Error al crear DataTable para '${tableId}':`, error);

                // Como último recurso, mostrar los datos en formato HTML básico
                try {
                    const tableElement = document.getElementById(tableId);
                    if (tableElement) {
                        // Limpiar la tabla
                        tableElement.innerHTML = '';

                        // Crear encabezados
                        const thead = document.createElement('thead');
                        const headerRow = document.createElement('tr');

                        columns.forEach(col => {
                            const th = document.createElement('th');
                            th.textContent = col.title;
                            headerRow.appendChild(th);
                        });

                        thead.appendChild(headerRow);
                        tableElement.appendChild(thead);

                        // Crear cuerpo de la tabla
                        const tbody = document.createElement('tbody');

                        data.forEach(rowData => {
                            const row = document.createElement('tr');

                            rowData.forEach(cellData => {
                                const cell = document.createElement('td');
                                cell.innerHTML = cellData;
                                row.appendChild(cell);
                            });

                            tbody.appendChild(row);
                        });

                        tableElement.appendChild(tbody);

                    }
                } catch (fallbackError) {
                    console.error(`Error al mostrar datos básicos para '${tableId}':`, fallbackError);
                }

                return null;
            }
        }

        // Load company information
        async function loadCompanyInfo() {
            try {
                
                //const response = await fetch('api_proxy.php?endpoint=InfoCompany');
                const response = await  fetchData('InfoCompany');
                const data = response;

                if (data && data.Name) {
                    document.getElementById('companyName').textContent = data.Name;
                }
            } catch (error) {
                console.error('Error loading company info:', error);
            }
        }
        
        function fillMissingMonths(yearData, year) {
            // 1. Definir la secuencia completa (Enero a Diciembre)
            // Nota: JavaScript usa 0 para Enero y 11 para Diciembre.
            const MONTH_NAMES = [
                1, 2, 3, 4, 5, 6,
                7, 8, 9, 10, 11, 12
            ];
            const MONTH_LABELS = [
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
            ];

            // 2. Mapear los datos existentes para facilitar la búsqueda
            // Creamos un mapa: {"01/2025": {TotalSales: 100, TotalProfit: 50}, ...}
            const dataMap = yearData.reduce((acc, item) => {
                acc[item.Month] = item;
                return acc;
            }, {});

            // Arrays de salida que contendrán el año completo
            const filledLabels = [];
            const filledSales = [];
            const filledProfit = [];

            // 3. Construir el array completo
            MONTH_NAMES.forEach(month => {
                
                

                // Buscar el dato en el mapa
                const item = dataMap[month];
                
                if (item) {
                    // Si el mes existe, usa los valores reales
                    filledLabels.push(item.monthYear);
                    filledSales.push(item.TotalSales);
                    filledProfit.push(item.TotalProfit);
                } else {
                    // Si el mes NO existe, rellena con 0
                    filledSales.push(0);
                    filledProfit.push(0);
                    filledLabels.push(MONTH_LABELS[month-1]+' '+year);
                }
            });

            return {
                labels: filledLabels,
                sales: filledSales,
                profit: filledProfit
            };
        }
        // Load sales totals
        async function loadSalesTotals() {
            try {
                toggleLoading(true);

                
                const data = await fetchData('SalesTotals', { DateFrom: currentDateFrom, DateTo: currentDateTo });
                // Crear un objeto moment para "hoy" al inicio del día
                const todayMoment = moment().startOf('day');
                // Crear un objeto moment para la fecha "hasta" al inicio del día
                const dateToMoment = moment(currentDateTo).startOf('day');
                const dateFromMoment = moment(currentDateFrom).startOf('day');
                if (dateToMoment.isSame(todayMoment, 'day') && dateFromMoment.isSame(todayMoment, 'day')) {
                    const yesterdayMoment = moment().subtract(1, 'days').format('YYYY-MM-DD');

                    const yesterdayData = await fetchData('SalesTotals', { DateFrom: yesterdayMoment, DateTo: yesterdayMoment });
                    
                    if (yesterdayData && yesterdayData[0] && data && data[0]) {
                        // Asegurarse de que el elemento existe antes de actualizarlo
                        const trendElement = document.getElementById('salesTrend');
                        const yesterdaySalesElement = document.getElementById('yesterdaySales');
                        const yesterdaySales = yesterdayData[0].TotalSales;
                        const todaySales = data[0].TotalSales;
                        if (trendElement) {
                            const porcentajeCambio = ((todaySales - yesterdaySales) / yesterdaySales) * 100;
                            const salesChange = todaySales - yesterdaySales;
                            if (porcentajeCambio >= 0) {
                                yesterdaySalesElement.innerHTML = `<span class="trend-indicator trend-up" id="salesTrend">
                                                        <i class="col fas fa-long-arrow-alt-up"></i> </strong>
                                                        </span>${formatCurrencyP(yesterdaySales)}`;

                            } else {
                                yesterdaySalesElement.innerHTML = `<span class="trend-indicator trend-down" id="salesTrend">
                                                        <i class="col fas fa-long-arrow-alt-down"></i> </strong>
                                                        </span>${formatCurrencyP(yesterdaySales)}`;
                            }
                            //yesterdaySalesElement.textContent = formatCurrencyP(yesterdaySales);
                        } else {
                            console.warn("Could not retrieve sales data for today or yesterday to calculate trend.");
                            // If data isn't available, also hide the elements
                            const trendElement = document.getElementById('salesTrend');
                            const yesterdaySalesElement = document.getElementById('yesterdaySales');
                            const yesterdaySalesLabelElement = document.getElementById('yesterdaySalesLabel');

                            if (trendElement) trendElement.style.display = 'none';
                            if (yesterdaySalesElement) yesterdaySalesElement.style.display = 'none';
                            if (yesterdaySalesLabelElement) yesterdaySalesLabelElement.style.display = 'none';
                        }
                    } else {
                        console.warn("Could not retrieve sales data for today or yesterday to calculate trend.");
                        // If data isn't available, also hide the elements
                        const trendElement = document.getElementById('salesTrend');
                        const yesterdaySalesElement = document.getElementById('yesterdaySales');
                        const yesterdaySalesLabelElement = document.getElementById('yesterdaySalesLabel');

                        if (trendElement) trendElement.style.display = 'none';
                        if (yesterdaySalesElement) yesterdaySalesElement.style.display = 'none';
                        if (yesterdaySalesLabelElement) yesterdaySalesLabelElement.style.display = 'none';
                    }
                } else {

                    // If data isn't available, also hide the elements
                    const trendElement = document.getElementById('salesTrend');
                    const yesterdaySalesElement = document.getElementById('yesterdaySales');
                    const yesterdaySalesLabelElement = document.getElementById('yesterdaySalesLabel');

                    if (trendElement) trendElement.style.display = 'none';
                    if (yesterdaySalesElement) yesterdaySalesElement.style.display = 'none';
                    if (yesterdaySalesLabelElement) yesterdaySalesLabelElement.style.display = 'none';
                }

                if (data && data[0]) {
                    const salesData = data[0];
                    salesData.TotalSales = salesData.TotalSales - salesData.TotalStateTax - salesData.TotalCityTax;
                    // Update KPI cards
                    const totalProfit = salesData.TotalSales - salesData.TotalCost;
                   
                    document.getElementById('totalProfit').textContent = formatCurrencyP(totalProfit);
                    // Update KPI cards with sales data
                    document.getElementById('totalCost').textContent = formatCurrencyP(salesData.TotalCost);
                    document.getElementById('stateTax').textContent = formatCurrencyP(salesData.TotalStateTax);
                    document.getElementById('municipalTax').textContent = formatCurrencyP(salesData.TotalCityTax);
                    document.getElementById('totalTax').textContent = formatCurrencyP(salesData.TotalStateTax + salesData.TotalCityTax);
                    document.getElementById('totalSales').textContent = formatCurrencyP(salesData.TotalSales);
                    
                    document.getElementById('totalCost').textContent = formatCurrencyP(salesData.TotalCost);
                    //TODO: document.getElementById('soldItemsLabel').textContent = formatCurrencyP(salesData.);

             

                    
                    // Calculate and display profit margin
                    const profitMargin = (salesData.TotalSales > 0) ? ((totalProfit / salesData.TotalSales) * 100) : 0;
                    document.getElementById('profitMargin').textContent = numeral(profitMargin / 100).format('0.0%');
                    
                    //DATOS PARA LA SECCION DE ESTADISTICAS
                    document.getElementById('totalTransactions').textContent = formatNumber(salesData.TransactionCount);
                    
                    document.getElementById('averageSalePerTransaction').textContent = formatCurrencyP(salesData.AverageTicketAmount);
                    document.getElementById('avgProfitPerTransaction').textContent = formatCurrencyP(salesData.AverageProfitPerTransaction);

                }
            } catch (error) {
                console.error('Error loading sales totals:', error);
            } finally {
            }
        }

       
        async function loadAllData() {
            toggleLoading(true);
            
            try {
                const lastUpdateTime = document.getElementById('last-update-time');
                lastUpdateTime.textContent = `Hoy ${new Date().toLocaleTimeString()}`;
                
                // Load overview section data
                await Promise.all([
                    loadCompanyInfo(),
                    cargarDatosSeccion('products-maintenance-section')
                    ]);
                toggleLoading(false);

                
            } catch (error) {
                console.error('Error loading all data:', error);
            } finally {
                toggleLoading(false);
            }
        }

        // Load overview data only
       
        // Load products data only
        async function loadProductsData() {
            toggleLoading(true);

            try {
                await loadTopProducts();
            } catch (error) {
                console.error('Error loading products data:', error);
            } finally {
                toggleLoading(false);
            }
        }

        // Load inventory data only
        async function loadInventoryData() {
            toggleLoading(true);

            try {
                await loadInventoryValue();
                await loadLowLevelItems();
            } catch (error) {
                console.error('Error loading inventory data:', error);
            } finally {
                toggleLoading(false);
            }
        }
       
        // Initialize the dashboard - ¡ESTA FUNCIÓN SE MODIFICÓ!
        async function initDashboard() {
            try{
                
                const sidebarTest = document.getElementById('sidebarTest');
                sidebarTest.style.position = 'static';
                
            }catch(error){
                console.error('Error al fijar la posición de la barra lateral:', error);
            }
            
            try {

                // Cargar todos los datos
                await loadAllData();
                document.getElementById('filterToday').addEventListener('click', function () {
                    loadTodayData();
                });
                

                
                document.getElementById('refreshClients').addEventListener('click', async function () {
                    
                    toggleLoading(true);
                    await new Promise(resolve => requestAnimationFrame(resolve));
                    await new Promise(requestAnimationFrame);
                    await loadClients();
                    toggleLoading(false);
                });
                document.getElementById('refreshProducts').addEventListener('click', async function () {
                    toggleLoading(true);
                    await loadTopProducts();
                    toggleLoading(false);
                });
                document.getElementById('refreshProductsMaintenance').addEventListener('click', async function () {
                    toggleLoading(true);
                    await initializeProductMaintenance();
                   toggleLoading(false);
                });
                

                // Apply product filters
                document.getElementById('applyProductFilters').addEventListener('click', function () {
                    loadTopProducts();
                });
            } catch (error) {
                console.error('Error initializing dashboard:', error);
            } finally {
                toggleLoading(false);
            }
        }

        // Initialize the dashboard when the page loads
        document.addEventListener('DOMContentLoaded', initDashboard);
        // Función para llenar los filtros dinámicamente
function llenarFiltros(productos) {
    const categoryFilterObj = document.getElementById('categoryFilter');
const departmentFilterObj = document.getElementById('departmentFilter');
  const categorias = [...new Set(productos.map(p => p.Category))];
  const departamentos = [...new Set(productos.map(p => p.Department))];
  const proveedores = [...new Set(productos.map(p => p.ProviderName))];

  // Llenar categorías
  categorias.forEach(cat => {
    const option = document.createElement('option');
    option.value = cat;
    option.textContent = cat;
    categoryFilterObj.appendChild(option);
  });

  // Llenar departamentos
  departamentos.forEach(dep => {
    const option = document.createElement('option');
    option.value = dep;
    option.textContent = dep;
    departmentFilterObj.appendChild(option);
  });

  
}

    </script>
    <script>

        /**
     * Esta función se encarga de manejar el evento resize de los gráficos de Chart.js y DataTables
     * cuando cambia el estado del sidebar.
     * Se debe incluir al final del archivo principal de JavaScript.
     */

        // Función para optimizar los contenedores y elementos después de cambios en el sidebar
        function optimizeDashboardLayout() {
            // Identificar si estamos en modo expandido
            const isExpanded = document.querySelector('.content').classList.contains('expanded');
            
            // 1. Redimensionar todos los gráficos de Chart.js
            function resizeCharts() {
                
                if (typeof charts === 'undefined') return;

                Object.values(charts).forEach(chart => {
                    if (!chart) return;

                    try {
                        // Para Chart.js, asegurar que usa el ancho completo del contenedor
                        if (chart.canvas) {
                            // Forzar recálculo del tamaño del contenedor
                            const parent = chart.canvas.parentNode;
                            if (parent) {
                                parent.style.height = isExpanded ? '320px' : '300px';

                                // Reajustar el canvas
                                const rect = parent.getBoundingClientRect();
                                chart.canvas.style.width = rect.width + 'px';
                                chart.canvas.style.maxWidth = '100%';

                                // Actualizar dimensiones y renderizar
                                chart.resize();
                                chart.update('none'); // Usar 'none' para mejor rendimiento
                            }
                        }
                    } catch (error) {
                        console.warn('Error al redimensionar gráfico:', error);
                    }
                });
            }

            // 2. Redimensionar todas las tablas DataTables
            function resizeTables() {
                if (typeof tables === 'undefined' || typeof $.fn.DataTable === 'undefined') return;

                Object.values(tables).forEach(table => {
                    if (!table || !table.columns) return;

                    try {
                        // Ajustar solo el ancho de las columnas sin redibujar toda la tabla
                        table.columns.adjust().draw(false);

                        // También ajustar la altura de la tabla si es necesario
                        const tableNode = table.table().node();
                        if (tableNode) {
                            const wrapper = $(tableNode).closest('.dataTables_wrapper');
                            if (wrapper.length) {
                                wrapper.css('width', '100%');
                            }
                        }
                    } catch (error) {
                        console.warn('Error al redimensionar tabla:', error);
                    }
                });
            }

            // 3. Ajustar KPI cards para mejor visualización
            function optimizeKPICards() {
                const kpiCards = document.querySelectorAll('#kpi-cards .dashboard-card');
                kpiCards.forEach(card => {
                    // Añadir clase para indicar el estado expandido/contraído
                    if (isExpanded) {
                        card.classList.add('layout-expanded');
                    } else {
                        card.classList.remove('layout-expanded');
                    }
                });
            }

            // 4. Ejecutar todos los ajustes con un pequeño retraso para permitir que terminen las transiciones CSS
            setTimeout(() => {
                resizeCharts();
                resizeTables();
                optimizeKPICards();

                // Log para confirmar que se ejecutó la optimización
            }, 350); // 350ms para dar tiempo a que terminen las transiciones CSS
        }

        // Asignar la función a eventos clave
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Al cambiar el estado del sidebar
            const menuToggle = document.getElementById('menu-toggle');
            if (menuToggle) {
                menuToggle.addEventListener('click', optimizeDashboardLayout);
            }

            // 2. Al cambiar entre secciones del dashboard
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', function () {
                    // Pequeño retraso para asegurar que la sección ya esté visible
                    setTimeout(optimizeDashboardLayout, 200);
                });
            });

            // 3. Al cambiar el tamaño de la ventana
            let resizeTimer;
            window.addEventListener('resize', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(optimizeDashboardLayout, 250);
            });

            // 4. Al cargar inicialmente la página
            setTimeout(optimizeDashboardLayout, 500);
        });

    </script>

    <script>
        // Script simple para manejar cambios del sidebar
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.querySelector('.sidebar');
            const content = document.querySelector('.content');
            const menuToggle = document.getElementById('menu-toggle');

            // Estado inicial - verificar localStorage
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('collapsed');
                content.classList.add('expanded');
                menuToggle.innerHTML = '<i class="fas fa-expand"></i>';
            }

            // Toggle del sidebar
            menuToggle.addEventListener('click', function () {
                sidebar.classList.toggle('collapsed');
                content.classList.toggle('expanded');

                // Actualizar icono
                if (sidebar.classList.contains('collapsed')) {
                    menuToggle.innerHTML = '<i class="fas fa-expand"></i>';
                    localStorage.setItem('sidebarCollapsed', 'true');
                } else {
                    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                    localStorage.setItem('sidebarCollapsed', 'false');
                }

                // Forzar redimensionamiento de los charts después de la transición
                setTimeout(function () {
                    if (typeof charts !== 'undefined') {
                    for (const chartId in charts) {
                        if (charts[chartId] && typeof charts[chartId].update === 'function') {
                            charts[chartId].update();
                        }
                    }
    }               
    if(typeof tables !== 'undefined') {
                    // Ajustar tablas DataTables
                    for (const tableId in tables) {
                        if (tables[tableId] && tables[tableId].columns) {
                            tables[tableId].columns.adjust().draw(false);
                        }
                    }
    }
                }, 350);
            });
        });
    </script>

    <script>
        // Mejorar comportamiento mobile
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.querySelector('.sidebar');
            const content = document.querySelector('.content');
            const menuToggle = document.getElementById('menu-toggle');
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');

            // Crear overlay para móvil si no existe
            if (!document.querySelector('.sidebar-overlay')) {
                const overlay = document.createElement('div');
                overlay.classList.add('sidebar-overlay');
                document.body.appendChild(overlay);

                overlay.addEventListener('click', function () {
                    sidebar.classList.remove('active');
                    this.classList.remove('active');
                });
            }

            const overlay = document.querySelector('.sidebar-overlay');

            // Estado inicial - verificar localStorage
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('collapsed');
                content.classList.add('expanded');

                // Actualizar iconos de ambos botones
                document.querySelectorAll('#menu-toggle, #mobile-menu-toggle').forEach(btn => {
                    btn.innerHTML = '<i class="fas fa-expand"></i>';
                });
            }

            // Toggle del sidebar para desktop
            if (menuToggle) {
                menuToggle.addEventListener('click', toggleSidebar);
            }

            // Toggle del sidebar para móvil
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function (e) {
                    e.preventDefault();

                    // En móvil simplemente mostramos/ocultamos el sidebar
                    sidebar.classList.toggle('active');

                    // Mostrar/ocultar overlay
                    if (overlay) {
                        overlay.classList.toggle('active');
                    }
                });
            }

            function toggleSidebar() {
                const isCurrentlyCollapsed = sidebar.classList.contains('collapsed');

                // Guardar preferencia
                localStorage.setItem('sidebarCollapsed', !isCurrentlyCollapsed);

                // Toggle classes
                sidebar.classList.toggle('collapsed');
                content.classList.toggle('expanded');

                // Actualizar iconos de ambos botones
                const newIcon = isCurrentlyCollapsed ? '<i class="fas fa-bars"></i>' : '<i class="fas fa-expand"></i>';
                document.querySelectorAll('#menu-toggle, #mobile-menu-toggle').forEach(btn => {
                    btn.innerHTML = newIcon;
                });

                // Redimensionar elementos después de la transición
                setTimeout(function () {
                    if(typeof charts !== 'undefined') {
                    for (const chartId in charts) {
                        if (charts[chartId] && typeof charts[chartId].update === 'function') {
                            charts[chartId].update();
                        }
                    }
    }
                    if(typeof tables !== 'undefined') {
                    // Ajustar tablas DataTables
                    for (const tableId in tables) {
                        if (tables[tableId] && tables[tableId].columns) {
                            tables[tableId].columns.adjust().draw(false);
                        }
                    }
    }
                }, 350);
            }

            // Cerrar sidebar en móviles al hacer clic en un enlace
            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.addEventListener('click', function () {
                    if (window.innerWidth < 992) { // Bootstrap lg breakpoint
                        sidebar.classList.remove('active');
                        if (overlay) {
                            overlay.classList.remove('active');
                        }
                    }
                });
            });
        });
    </script>

    <script>
        // Script para inicializar tooltips solo cuando el sidebar está contraído
        document.addEventListener('DOMContentLoaded', function () {
            // Función para inicializar o reinicializar los tooltips
            function updateTooltips() {
                // Primero destruimos los tooltips existentes
                const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                tooltipTriggerList.forEach(tooltipTriggerEl => {
                    const tooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                    if (tooltip) {
                        tooltip.dispose();
                    }
                });

                // Solo inicializamos los tooltips si el sidebar está contraído
                if (document.querySelector('.sidebar').classList.contains('collapsed')) {
                    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                    tooltipTriggerList.forEach(tooltipTriggerEl => {
                        new bootstrap.Tooltip(tooltipTriggerEl, {
                            trigger: 'hover',
                            container: 'body'
                        });
                    });
                } else {
                }
            }

            // Activar tooltips al cargar la página
            updateTooltips();

            // Actualizar tooltips cuando cambia el estado del sidebar
            const menuToggle = document.getElementById('menu-toggle');
            if (menuToggle) {
                menuToggle.addEventListener('click', function () {
                    // Esperar a que termine la transición
                    setTimeout(updateTooltips, 350);
                });
            }

            // Actualizar tooltips cuando cambia el tamaño de la ventana
            window.addEventListener('resize', function () {
                clearTimeout(window.resizeTimer);
                window.resizeTimer = setTimeout(updateTooltips, 300);
            });

            // Ocultar tooltip al hacer clic en un enlace del menu
            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.addEventListener('click', function () {
                    const tooltip = bootstrap.Tooltip.getInstance(this);
                    if (tooltip) {
                        tooltip.hide();
                    }
                });
            });
        });
        
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.datatables.net/2.3.2/js/dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>
    <!--<script src="js/clients.js"></script>-->
    <!--<script src="js/maintenance.js"></script>-->
    <script src="../js/sidebar.js"></script>
    <?php include 'scripts.php'; ?>
    
    
     
    
</body>

</html>