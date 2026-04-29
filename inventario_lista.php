<?php
session_start();
require_once 'db.php'; 

/**
 * CONTROL DE ACCESO
 */
if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit();
}

$rol = $_SESSION['rol'];
$mostrar_alerta_permiso = false;

if ($rol === 'operativo') {
    $mostrar_alerta_permiso = true;
} 
elseif ($rol !== 'administrador' && $rol !== 'tecnico') {
    header("Location: dashboard.php"); 
    exit();
}

// Consulta de Inventario
$query = "SELECT i.*, c.nombre AS categoria_nombre 
          FROM inventario i 
          INNER JOIN categorias c ON i.tipo_id = c.id 
          ORDER BY i.id DESC";

$resultado = $conexion->query($query);

// Consulta para categorías
$query_cat = "SELECT * FROM categorias ORDER BY nombre ASC";
$res_categorias = $conexion->query($query_cat);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeoAdmin | Inventario</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --bg-dark: #0f172a;
            --card-dark: #1e293b;
            --accent: #38bdf8;
            --accent-soft: rgba(56, 189, 248, 0.1);
            --glass-border: rgba(255, 255, 255, 0.08);
        }

        body {
            background-color: var(--bg-dark);
            font-family: 'Inter', sans-serif;
            color: white;
            min-height: 100vh;
            margin: 0;
            background-image: 
                linear-gradient(rgba(56, 189, 248, 0.02) 1px, transparent 1px), 
                linear-gradient(90deg, rgba(56, 189, 248, 0.02) 1px, transparent 1px);
            background-size: 50px 50px;
        }

        /* Navbar Estilizada */
        .neo-navbar {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--glass-border);
            padding: 12px 30px;
        }

        .nav-link-neo {
            color: white;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
            padding: 8px 15px;
            border-radius: 10px;
            font-size: 0.95rem;
        }

        .nav-link-neo:hover {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .logout-btn {
            color: #f87171;
            border: 1px solid rgba(248, 113, 113, 0.2);
        }

        .logout-btn:hover {
            background: rgba(248, 113, 113, 0.1);
            color: #fca5a5;
        }

        .logo-img {
    width: 45px; /* Ajusta el tamaño a tu gusto */
    height: auto;
    object-fit: contain;
    background-color: transparent !important; /* Fuerza la transparencia del fondo */
    mix-blend-mode: lighten; /* Truco opcional: mezcla el logo con el fondo oscuro si quedan bordes blancos */
    display: block;
}

        .main-content {
            padding: 40px;
            animation: fadeInPage 0.6s ease-out;
        }

        @keyframes fadeInPage {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Buscador */
        .search-container {
            background: var(--card-dark);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .form-control-neo {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--glass-border);
            color: white;
            border-radius: 10px;
        }

        .form-control-neo:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: var(--accent);
            color: white;
            box-shadow: none;
        }

        .asset-card {
            background: var(--card-dark);
            border: 1px solid var(--glass-border);
            border-radius: 22px;
            padding: 25px;
            height: 100%;
            transition: 0.4s;
        }

        .asset-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
        }

        .asset-icon-wrapper {
            width: 50px; height: 50px;
            background: var(--accent-soft);
            color: var(--accent);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
        }

        .btn-add {
            background: var(--accent);
            color: #0f172a; font-weight: 800;
            border-radius: 12px; padding: 10px 20px;
            text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
        }

        .status-pill {
            font-size: 0.65rem; padding: 5px 12px;
            border-radius: 8px; font-weight: 800;
            text-transform: uppercase;
        }
        .status-disponible { background: rgba(74, 222, 128, 0.1); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.3); }
        .status-asignado { background: var(--accent-soft); color: var(--accent); border: 1px solid rgba(56, 189, 248, 0.3); }
        .status-reparacion { background: rgba(248, 113, 113, 0.1); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.3); }
    </style>
</head>
<body>

    <nav class="neo-navbar d-flex justify-content-between align-items-center sticky-top">
    <div class="d-flex align-items-center gap-3">
        <img src="img/logo_neoadmin.png" alt="Logo" class="logo-img" style="background: transparent !important; border: none !important;">
        <span style="font-family: 'Orbitron'; font-size: 1.2rem; letter-spacing: 1px; color: var(--accent);">NEO ADMIN</span>
    </div>

    <div class="d-flex align-items-center gap-2">
        <a href="dashboard.php" class="nav-link-neo">
            <i class="bi bi-house-door-fill"></i> Inicio
        </a>
        <a href="tickets_lista.php" class="nav-link-neo">
            <i class="bi bi-headset"></i> Mesa de Ayuda
        </a>
        <div class="vr mx-2 opacity-25" style="height: 20px; align-self: center;"></div>
        <a href="logout.php" class="nav-link-neo logout-btn">
            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
        </a>
    </div>
</nav>

    <main class="main-content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fw-bold mb-1" style="font-family: 'Orbitron'; letter-spacing: 1px;">INVENTARIO GLOBAL</h1>
                <p class="text-secondary mb-0">Visualización de activos críticos de la empresa.</p>
            </div>
            <?php if ($rol == 'administrador'): ?>
            <a href="inventario_nuevo.php" class="btn-add">
                <i class="bi bi-plus-circle-fill"></i> NUEVO ACTIVO
            </a>
            <?php endif; ?>
        </div>

        <div class="search-container shadow-sm">
            <div class="row g-3">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-0 text-info"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchInput" class="form-control form-control-neo" placeholder="Buscar por marca, modelo o código...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="categoryFilter" class="form-select form-control-neo">
                        <option value="all">Todas las categorías</option>
                        <?php while($cat_row = $res_categorias->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($cat_row['nombre']); ?>"><?php echo htmlspecialchars($cat_row['nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="row g-4" id="inventoryGrid">
            <?php if (!$mostrar_alerta_permiso): ?>
                <?php while($row = $resultado->fetch_assoc()): 
                    $cat_name = $row['categoria_nombre'];
                    $st = strtolower($row['estado']);
                    $st_class = ($st == 'disponible') ? "status-disponible" : (($st == 'asignado') ? "status-asignado" : "status-reparacion");
                ?>
                <div class="col-md-6 col-lg-4 asset-item" data-category="<?php echo $cat_name; ?>" data-search="<?php echo strtolower($row['marca']." ".$row['modelo']." ".$row['codigo_patrimonial']); ?>">
                    <div class="asset-card">
                        <div class="d-flex justify-content-between mb-3">
                            <div class="asset-icon-wrapper">
                                <i class="bi bi-pc-display"></i>
                            </div>
                            <span class="status-pill <?php echo $st_class; ?>"><?php echo $row['estado']; ?></span>
                        </div>
                        <h5 class="fw-bold mb-1"><?php echo $row['marca']." ".$row['modelo']; ?></h5>
                        <p class="small text-secondary">Patrimonio: <span class="text-white"><?php echo $row['codigo_patrimonial']; ?></span></p>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top border-white border-opacity-10">
                            <span class="badge bg-secondary opacity-50"><?php echo $cat_name; ?></span>
                            <a href="inventario_editar.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info border-0"><i class="bi bi-pencil-square"></i></a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Lógica de búsqueda y filtrado
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');
        const items = document.querySelectorAll('.asset-item');

        function filter() {
            const text = searchInput.value.toLowerCase();
            const cat = categoryFilter.value;
            
            items.forEach(item => {
                const isMatch = item.dataset.search.includes(text) && (cat === 'all' || item.dataset.category === cat);
                item.style.display = isMatch ? 'block' : 'none';
            });
        }

        searchInput.addEventListener('input', filter);
        categoryFilter.addEventListener('change', filter);

        <?php if ($mostrar_alerta_permiso): ?>
        Swal.fire({ title: 'ACCESO LIMITADO', text: 'No tienes permisos para ver esta sección.', icon: 'error', background: '#1e293b', color: '#fff' })
        .then(() => { window.location.href = 'dashboard.php'; });
        <?php endif; ?>
    </script>
</body>
</html>