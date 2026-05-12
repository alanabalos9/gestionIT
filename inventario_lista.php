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

// Consulta de Inventario con nombres de usuarios para la búsqueda y visualización
$query = "SELECT i.*, c.nombre AS categoria_nombre, u.nombre_completo AS nombre_usuario
          FROM inventario i 
          INNER JOIN categorias c ON i.tipo_id = c.id 
          LEFT JOIN usuarios u ON i.usuario_asignado_id = u.id
          ORDER BY i.id DESC";

$resultado = $conexion->query($query);

// Consulta para categorías (Filtro)
$res_categorias = $conexion->query("SELECT * FROM categorias ORDER BY nombre ASC");

// Consulta para lista de usuarios (Para el selector del Modal de edición)
$res_usuarios_modal = $conexion->query("SELECT id, nombre_completo FROM usuarios ORDER BY nombre_completo ASC");
$usuarios_opciones = "";
while($u = $res_usuarios_modal->fetch_assoc()){
    $usuarios_opciones .= "<option value='{$u['id']}'>{$u['nombre_completo']}</option>";
}
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

        /* Navbar restaurada con tu diseño original */
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

        .nav-link-neo:hover { background: var(--accent-soft); color: var(--accent); }

        .main-content { padding: 40px; animation: fadeInPage 0.6s ease-out; }

        @keyframes fadeInPage {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

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
            position: relative;
        }

        .asset-card:hover { transform: translateY(-5px); border-color: var(--accent); }

        /* ID Limpio sin el círculo rojo */
        .id-badge {
            position: absolute;
            top: 15px;
            right: 20px;
            color: var(--accent);
            font-size: 0.75rem;
            font-weight: 700;
            font-family: 'Orbitron';
            opacity: 0.8;
        }

        .asset-icon-wrapper {
            width: 50px; height: 50px;
            background: var(--accent-soft);
            color: var(--accent);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
        }

        /* Estilos de Estado para las cards */
        .status-pill {
            font-size: 0.65rem; padding: 4px 10px;
            border-radius: 8px; font-weight: 800;
            text-transform: uppercase;
            display: inline-block;
        }
        .status-disponible { background: rgba(74, 222, 128, 0.1); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.3); }
        .status-asignado { background: var(--accent-soft); color: var(--accent); border: 1px solid rgba(56, 189, 248, 0.3); }
        .status-reparacion { background: rgba(248, 113, 113, 0.1); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.3); }

        .info-row { display: flex; align-items: center; gap: 10px; margin-top: 8px; font-size: 0.85rem; }
        .info-row i { color: var(--accent); width: 16px; }

        .modal-content { background: var(--card-dark); border: 1px solid var(--accent); color: white; border-radius: 20px; }
        .modal-header { border-bottom: 1px solid var(--glass-border); }
    </style>
</head>
<body>

    <nav class="neo-navbar d-flex justify-content-between align-items-center sticky-top">
        <div class="d-flex align-items-center gap-3">
            <img src="img/logo_neoadmin.png" alt="Logo" style="height: 40px;">
            <span style="font-family: 'Orbitron'; font-size: 1.2rem; color: var(--accent);">NEO ADMIN</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="dashboard.php" class="nav-link-neo"><i class="bi bi-house-door-fill"></i> Inicio</a>
            <a href="tickets_lista.php" class="nav-link-neo"><i class="bi bi-headset"></i> Mesa de Ayuda</a>
            <a href="logout.php" class="nav-link-neo text-danger"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a>
        </div>
    </nav>

    <main class="main-content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h1 class="fw-bold mb-1" style="font-family: 'Orbitron';">INVENTARIO</h1>
        </div>

        <div class="search-container">
            <div class="row g-3">
                <div class="col-md-9">
                    <input type="text" id="searchInput" class="form-control form-control-neo" placeholder="Buscar por marca, modelo, sector...">
                </div>
                <div class="col-md-3">
                    <select id="categoryFilter" class="form-select form-control-neo">
                        <option value="all">Todas las categorías</option>
                        <?php while($cat_row = $res_categorias->fetch_assoc()): ?>
                            <option value="<?php echo $cat_row['nombre']; ?>"><?php echo $cat_row['nombre']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="row g-4" id="inventoryGrid">
            <?php while($row = $resultado->fetch_assoc()): 
                // Lógica de colores por estado
                $st = mb_strtolower($row['estado'], 'UTF-8');
                $st_class = "status-reparacion"; 
                if ($st == 'disponible') $st_class = "status-disponible";
                if ($st == 'asignado') $st_class = "status-asignado";

                $search_data = strtolower($row['marca']." ".$row['modelo']." ".$row['codigo_patrimonial']." ".$row['sector']." ".$row['nombre_usuario']);
            ?>
            <div class="col-md-6 col-lg-4 asset-item" data-category="<?php echo $row['categoria_nombre']; ?>" data-search="<?php echo $search_data; ?>">
                <div class="asset-card">
                    <span class="id-badge">ID #<?php echo $row['id']; ?></span>

                    <div class="d-flex justify-content-between mb-3">
                        <div class="asset-icon-wrapper"><i class="bi bi-pc-display"></i></div>
                        <span class="status-pill <?php echo $st_class; ?>"><?php echo $row['estado']; ?></span>
                    </div>

                    <h5 class="fw-bold mb-1"><?php echo $row['marca']." ".$row['modelo']; ?></h5>
                    <p class="small text-secondary mb-3">Patrimonio: <span class="text-white"><?php echo $row['codigo_patrimonial']; ?></span></p>

                    <div class="info-row"><i class="bi bi-geo-alt"></i><span>Sector: <strong><?php echo $row['sector'] ?: 'No definido'; ?></strong></span></div>
                    <div class="info-row"><i class="bi bi-person-badge"></i><span>Asignado: <strong><?php echo $row['nombre_usuario'] ?: 'Sin asignar'; ?></strong></span></div>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top border-white border-opacity-10">
                        <span class="badge bg-secondary opacity-50"><?php echo $row['categoria_nombre']; ?></span>
                        <button onclick='abrirModalEditar(<?php echo json_encode($row); ?>)' class="btn btn-sm btn-outline-info border-0">
                            <i class="bi bi-pencil-square fs-5"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </main>

    <div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" style="font-family: 'Orbitron';">EDITAR ASIGNACIÓN</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formUpdateInventario">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="small text-secondary mb-1">SECTOR</label>
                            <select name="sector" id="edit_sector" class="form-select form-control-neo">
                                <option value="">Seleccione un sector</option>
                                <option value="Administración">Administración</option>
                                <option value="Finanzas">Finanzas</option>
                                <option value="Legales">Legales</option>
                                <option value="Gerencia">Gerencia</option>
                                <option value="Sistemas">Sistemas</option>
                                <option value="Operaciones">Operaciones</option>
                                <option value="Recursos Humanos">Recursos Humanos</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="small text-secondary mb-1">USUARIO ASIGNADO</label>
                            <select name="usuario_id" id="edit_usuario" class="form-select form-control-neo">
                                <option value="">Sin asignar</option>
                                <?php echo $usuarios_opciones; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="small text-secondary mb-1">ESTADO</label>
                            <select name="estado" id="edit_estado" class="form-select form-control-neo">
                                <option value="Disponible">Disponible</option>
                                <option value="Asignado">Asignado</option>
                                <option value="Reparación">Reparación</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-info w-100 fw-bold mt-2">GUARDAR CAMBIOS</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modalEdit = new bootstrap.Modal(document.getElementById('modalEditar'));

        function abrirModalEditar(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_sector').value = data.sector || '';
            document.getElementById('edit_usuario').value = data.usuario_asignado_id || '';
            document.getElementById('edit_estado').value = data.estado;
            modalEdit.show();
        }

        // Búsqueda y filtrado
        function filter() {
            const text = document.getElementById('searchInput').value.toLowerCase();
            const cat = document.getElementById('categoryFilter').value;
            document.querySelectorAll('.asset-item').forEach(item => {
                const match = item.dataset.search.includes(text) && (cat === 'all' || item.dataset.category === cat);
                item.style.display = match ? 'block' : 'none';
            });
        }
        document.getElementById('searchInput').addEventListener('input', filter);
        document.getElementById('categoryFilter').addEventListener('change', filter);

        // Envío AJAX corregido para la actualización
        document.getElementById('formUpdateInventario').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('inventario_update_proceso.php', { method: 'POST', body: formData })
            .then(response => response.text())
            .then(() => {
                Swal.fire({
                    title: '¡Actualizado!',
                    text: 'Los cambios se guardaron correctamente.',
                    icon: 'success',
                    background: '#1e293b', color: '#fff', confirmButtonColor: '#38bdf8'
                }).then(() => location.reload());
            });
        };
    </script>
</body>
</html>