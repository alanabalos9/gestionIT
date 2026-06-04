<?php
session_start();
require_once 'db.php';

// Redirigir si no hay sesión iniciada
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];

// --- CAPTURA DE FILTROS Y BÚSQUEDA ---
$filtro_estado = $_GET['estado'] ?? '';
$filtro_depto = $_GET['departamento'] ?? ''; 
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_prioridad = $_GET['prioridad'] ?? '';
$filtro_tecnico = $_GET['tecnico_id'] ?? '';
$filtro_fecha_desde = $_GET['fecha_desde'] ?? '';
$filtro_fecha_hasta = $_GET['fecha_hasta'] ?? '';
$buscar = $_GET['buscar'] ?? '';

// --- 1. LÓGICA DE CONTADORES ---
$query_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'Abierto' OR estado = 'Mantenimiento' THEN 1 ELSE 0 END) as abiertos,
    SUM(CASE WHEN estado = 'En Proceso' THEN 1 ELSE 0 END) as proceso,
    SUM(CASE WHEN estado = 'Mantenimiento' THEN 1 ELSE 0 END) as mantenimiento,
    SUM(CASE WHEN estado = 'Resuelto' THEN 1 ELSE 0 END) as resueltos
    FROM tickets WHERE 1=1";

if ($rol != 'administrador' && $rol != 'tecnico') {
    $query_stats .= " AND solicitante_id = $usuario_id";
}
if (!empty($filtro_fecha_desde)) {
    $query_stats .= " AND DATE(fecha_creacion) >= '" . $conexion->real_escape_string($filtro_fecha_desde) . "'";
}
if (!empty($filtro_fecha_hasta)) {
    $query_stats .= " AND DATE(fecha_creacion) <= '" . $conexion->real_escape_string($filtro_fecha_hasta) . "'";
}

$stats_res = $conexion->query($query_stats);
$stats = $stats_res->fetch_assoc();

// --- 2. CONSULTA DE TICKETS CON FILTROS DINÁMICOS ---
if ($rol == 'administrador' || $rol == 'tecnico') {
    $sql = "SELECT t.id, t.asunto, t.descripcion, t.prioridad, t.estado, t.tipo, t.fecha_creacion, t.fecha_limite,
                  t.fecha_mantenimiento, t.detalle_resolucion, t.archivo_adjunto, t.archivo_nombre, t.archivo_tipo,
                  u_sol.nombre_completo AS solicitante_nombre, u_sol.area AS solicitante_depto, 
                  u_tec.nombre_completo AS tecnico_nombre, t.tecnico_id
           FROM tickets t
           JOIN usuarios u_sol ON t.solicitante_id = u_sol.id
           LEFT JOIN usuarios u_tec ON t.tecnico_id = u_tec.id 
           WHERE 1=1";
} else {
    $sql = "SELECT t.id, t.asunto, t.descripcion, t.prioridad, t.estado, t.tipo, t.fecha_creacion, t.fecha_limite,
                  t.fecha_mantenimiento, t.detalle_resolucion, t.archivo_adjunto, t.archivo_nombre, t.archivo_tipo,
                  u_sol.nombre_completo AS solicitante_nombre, u_sol.area AS solicitante_depto,
                  'N/A' as tecnico_nombre, t.tecnico_id
           FROM tickets t
           JOIN usuarios u_sol ON t.solicitante_id = u_sol.id
           WHERE t.solicitante_id = $usuario_id";
}

if (!empty($filtro_estado)) {
    $sql .= " AND t.estado = '" . $conexion->real_escape_string($filtro_estado) . "'";
}
if (!empty($filtro_depto)) {
    $sql .= " AND u_sol.area = '" . $conexion->real_escape_string($filtro_depto) . "'";
}
if (!empty($filtro_tipo)) {
    $sql .= " AND t.tipo = '" . $conexion->real_escape_string($filtro_tipo) . "'";
}
if (!empty($filtro_prioridad)) {
    $sql .= " AND t.prioridad = '" . $conexion->real_escape_string($filtro_prioridad) . "'";
}
if (!empty($filtro_tecnico)) {
    $sql .= " AND t.tecnico_id = '" . $conexion->real_escape_string($filtro_tecnico) . "'";
}
if (!empty($filtro_fecha_desde)) {
    $sql .= " AND DATE(t.fecha_creacion) >= '" . $conexion->real_escape_string($filtro_fecha_desde) . "'";
}
if (!empty($filtro_fecha_hasta)) {
    $sql .= " AND DATE(t.fecha_creacion) <= '" . $conexion->real_escape_string($filtro_fecha_hasta) . "'";
}
if (!empty($buscar)) {
    $b = $conexion->real_escape_string($buscar);
    $sql .= " AND (t.id LIKE '%$b%' OR t.asunto LIKE '%$b%' OR t.descripcion LIKE '%$b%' OR u_sol.nombre_completo LIKE '%$b%')";
}

$sql .= " ORDER BY t.fecha_creacion DESC";
$res = $conexion->query($sql);

$tecnicos_res = $conexion->query("SELECT id, nombre_completo FROM usuarios WHERE rol = 'tecnico'");
$tecnicos = [];
while ($t = $tecnicos_res->fetch_assoc()) { $tecnicos[] = $t; }

$deptos_res = $conexion->query("SELECT DISTINCT area FROM usuarios WHERE area IS NOT NULL AND area != ''");
$departamentos = [];
if($deptos_res) {
    while ($d = $deptos_res->fetch_assoc()) { $departamentos[] = $d['area']; }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets - NeoAdmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@300;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0b0f1a;
            --card-bg: #161c2d;
            --accent: #38bdf8;
            --accent-soft: rgba(56, 189, 248, 0.15);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-gray: #94a3b8;
            --warning-alert: #fbbf24;
            --danger-alert: #ef4444;
            --mante-color: #8b5cf6;
        }
        body { background-color: var(--bg-dark); color: #f8fafc; font-family: 'Inter', sans-serif; }
        .neo-navbar { background: rgba(22, 28, 45, 0.8); backdrop-filter: blur(10px); padding: 0.75rem 2rem; border-bottom: 1px solid rgba(255, 255, 255, 0.05); margin-bottom: 2rem; }
        .logo-img { height: 35px; }
        .nav-link-neo { text-decoration: none; padding: 8px 16px; border-radius: 10px; font-size: 0.9rem; color: var(--text-gray); display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .nav-link-neo:hover { color: #fff; background: rgba(255, 255, 255, 0.05); }
        
        .card-stat { background: var(--card-bg); border-radius: 15px; border: 1px solid rgba(255,255,255,0.05); padding: 15px; text-align: center; text-decoration: none; display: block; transition: 0.2s; }
        .card-stat:hover { border-color: var(--accent); transform: translateY(-2px); }
        .card-stat h6 { font-family: 'Orbitron'; font-weight: bold; font-size: 1.2rem; margin: 0; }
        .card-stat small { color: #64748b; font-size: 0.7rem; font-weight: 700; letter-spacing: 1px; }
        
        .card-ticket { background: var(--card-bg); border-radius: 20px; border: 1px solid rgba(255,255,255,0.05); padding: 1.5rem; height: 100%; transition: all 0.3s ease; position: relative; border-left: 5px solid transparent; }
        .card-normal { border-left-color: var(--accent); }
        .card-warning { border-left-color: var(--warning-alert); background: rgba(251, 191, 36, 0.03); }
        .card-expired { border-left-color: var(--danger-alert); background: rgba(239, 68, 68, 0.05); animation: pulse-red 2s infinite; }
        .card-mantenimiento { border-left-color: var(--mante-color); background: rgba(139, 92, 246, 0.05); }
        
        .form-control-neo { background: rgba(15, 23, 42, 0.8) !important; border: 1px solid var(--glass-border) !important; color: #ffffff !important; border-radius: 12px; padding: 12px; }
        .form-control-neo:focus { box-shadow: 0 0 0 2px var(--accent-soft); border-color: var(--accent) !important; }
        
        .form-select-neo { background: rgba(15, 23, 42, 0.8) url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") no-repeat right 0.75rem center/16px 12px !important; border: 1px solid var(--glass-border) !important; color: #ffffff !important; border-radius: 12px; padding: 10px; font-size: 0.85rem; }
        .form-select-neo:focus { box-shadow: 0 0 0 2px var(--accent-soft); border-color: var(--accent) !important; }
        .form-select-neo option { background-color: #0f172a !important; color: white !important; }

        .search-wrapper-neo { position: relative; }
        .search-wrapper-neo i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-gray); }
        .search-wrapper-neo input { padding-left: 38px !important; }
        .swal2-select { background-color: #0f172a !important; color: white !important; border: 1px solid rgba(255,255,255,0.2) !important; }
        .swal2-select option { background-color: #0f172a !important; color: white !important; }

        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.2); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .timer-display { font-family: 'Orbitron'; font-size: 0.95rem; font-weight: bold; }
        .btn-action-group { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-top: 15px; }
        .btn-action-card { background: #0f172a; border: 1px solid rgba(255,255,255,0.05); color: white; padding: 8px 2px; border-radius: 10px; display: flex; flex-direction: column; align-items: center; cursor: pointer; transition: 0.2s; }
        .btn-action-card:hover { background: var(--accent-soft); border-color: var(--accent); }
        .btn-action-card i { font-size: 1.1rem; margin-bottom: 3px; }
        .btn-action-card span { font-size: 8px; text-transform: uppercase; color: var(--text-gray); font-weight: 700; }
        
        .label-date-neo { font-size: 0.75rem; color: var(--text-gray); font-weight: 600; margin-bottom: 4px; display: block; padding-left: 4px; }
    </style>
</head>
<body>

    <nav class="neo-navbar d-flex justify-content-between align-items-center sticky-top">
        <div class="d-flex align-items-center gap-3">
            <img src="img/logo_neoadmin.png" alt="Logo" class="logo-img">
            <span style="font-family: 'Orbitron'; font-size: 1.1rem; color: var(--accent); font-weight: bold;">NEO ADMIN</span>
        </div>
        <div class="d-flex gap-2">
            <a href="dashboard.php" class="nav-link-neo"><i class="bi bi-house-door"></i> Inicio</a>
            <a href="tickets_lista.php" class="nav-link-neo" style="background: var(--accent-soft); color: var(--accent);"><i class="bi bi-headset"></i> Mesa de Ayuda</a>
            <a href="logout.php" class="nav-link-neo text-danger opacity-75"><i class="bi bi-box-arrow-right"></i> Salir</a>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 style="font-family: 'Orbitron'; font-weight: bold;">TICKETS <?php echo !empty($filtro_estado) ? ' - '.strtoupper($filtro_estado) : ''; ?></h2>
            <div class="d-flex gap-2">
                <button onclick="solicitarReporte()" class="btn btn-outline-light fw-bold" style="border-radius: 12px; border: 1px solid var(--glass-border);">
                    <i class="bi bi-download me-1"></i> REPORTES
                </button>
                <a href="tickets_crear.php" class="btn btn-info fw-bold" style="border-radius: 12px; background: var(--accent); border:none;">+ NUEVO TICKET</a>
            </div>
        </div>

        <div class="p-4 mb-4 rounded-4" style="background: var(--card-bg); border: 1px solid rgba(255,255,255,0.05);">
            <form method="GET" action="tickets_lista.php" id="formFiltros" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <span class="label-date-neo">Término de búsqueda</span>
                    <div class="search-wrapper-neo">
                        <i class="bi bi-search"></i>
                        <input type="text" name="buscar" class="form-control form-control-neo" placeholder="Buscar ID, asunto..." value="<?php echo htmlspecialchars($buscar); ?>">
                    </div>
                </div>

                <div class="col-md-2">
                    <span class="label-date-neo">Filtrar Estado</span>
                    <select name="estado" class="form-select form-select-neo">
                        <option value="">[Todos los Estados]</option>
                        <option value="Nuevo" <?php echo $filtro_estado === 'Nuevo' ? 'selected' : ''; ?>>Nuevo</option>
                        <option value="Abierto" <?php echo $filtro_estado === 'Abierto' ? 'selected' : ''; ?>>Abierto</option>
                        <option value="En Proceso" <?php echo $filtro_estado === 'En Proceso' ? 'selected' : ''; ?>>En Proceso</option>
                        <option value="Pendiente" <?php echo $filtro_estado === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="Mantenimiento" <?php echo $filtro_estado === 'Mantenimiento' ? 'selected' : ''; ?>>Mantenimiento</option>
                        <option value="Resuelto" <?php echo $filtro_estado === 'Resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                        <option value="Cerrado" <?php echo $filtro_estado === 'Cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <span class="label-date-neo">Área / Departamento</span>
                    <select name="departamento" class="form-select form-select-neo">
                        <option value="">[Área / Depto]</option>
                        <?php foreach($departamentos as $d): ?>
                            <option value="<?php echo htmlspecialchars($d); ?>" <?php echo $filtro_depto === $d ? 'selected' : ''; ?>><?php echo htmlspecialchars($d); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <span class="label-date-neo">Tipo Incidencia</span>
                    <select name="tipo" class="form-select form-select-neo">
                        <option value="">[Incidencia / Tipo]</option>
                        <option value="Incidencia" <?php echo $filtro_tipo === 'Incidencia' ? 'selected' : ''; ?>>Incidencia</option>
                        <option value="Solicitud" <?php echo $filtro_tipo === 'Solicitud' ? 'selected' : ''; ?>>Solicitud</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <span class="label-date-neo">Prioridad</span>
                    <select name="prioridad" class="form-select form-select-neo">
                        <option value="">[Prioridad]</option>
                        <option value="Baja" <?php echo $filtro_prioridad === 'Baja' ? 'selected' : ''; ?>>Baja</option>
                        <option value="Media" <?php echo $filtro_prioridad === 'Media' ? 'selected' : ''; ?>>Media</option>
                        <option value="Alta" <?php echo $filtro_prioridad === 'Alta' ? 'selected' : ''; ?>>Alta</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <span class="label-date-neo">Fecha Desde</span>
                    <input type="date" name="fecha_desde" class="form-control form-control-neo py-2" value="<?php echo htmlspecialchars($filtro_fecha_desde); ?>">
                </div>

                <div class="col-md-3">
                    <span class="label-date-neo">Fecha Hasta</span>
                    <input type="date" name="fecha_hasta" class="form-control form-control-neo py-2" value="<?php echo htmlspecialchars($filtro_fecha_hasta); ?>">
                </div>

                <?php if ($rol == 'administrador' || $rol == 'tecnico'): ?>
                <div class="col-md-3">
                    <span class="label-date-neo">Técnico Asignado</span>
                    <select name="tecnico_id" class="form-select form-select-neo">
                        <option value="">[Técnico Asignado]</option>
                        <?php foreach($tecnicos as $t): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo $filtro_tecnico == $t['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['nombre_completo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-md d-flex gap-2">
                    <button type="submit" class="btn btn-info w-100 fw-bold" style="border-radius:12px; background: var(--accent); border:none; height:45px;">Filtrar</button>
                    <a href="tickets_lista.php" class="btn btn-secondary fw-bold d-flex align-items-center justify-content-center" style="border-radius:12px; background:rgba(255,255,255,0.05); border:1px solid var(--glass-border); width:50px; height:45px;" title="Limpiar Filtros">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
            </form>
        </div>

        <div class="row g-3 mb-5">
            <div class="col-md"><a href="tickets_lista.php" class="card-stat"><h6><?php echo $stats['total']; ?></h6><small>TOTAL</small></a></div>
            <div class="col-md"><a href="tickets_lista.php?estado=Abierto" class="card-stat"><h6 class="text-warning"><?php echo $stats['abiertos']; ?></h6><small>ABIERTOS</small></a></div>
            <div class="col-md"><a href="tickets_lista.php?estado=En Proceso" class="card-stat"><h6 class="text-info"><?php echo $stats['proceso']; ?></h6><small>PROCESO</small></a></div>
            <div class="col-md"><a href="tickets_lista.php?estado=Mantenimiento" class="card-stat"><h6 style="color: var(--mante-color);"><?php echo $stats['mantenimiento']; ?></h6><small>MANTE.</small></a></div>
            <div class="col-md"><a href="tickets_lista.php?estado=Resuelto" class="card-stat"><h6 class="text-success"><?php echo $stats['resueltos']; ?></h6><small>RESUELTOS</small></a></div>
        </div>

        <div class="row g-4">
            <?php if ($res->num_rows === 0): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-folder-x text-secondary" style="font-size: 3rem;"></i>
                    <p class="text-secondary mt-2">No se encontraron registros de tickets para este criterio.</p>
                </div>
            <?php endif; ?>

            <?php while($row = $res->fetch_assoc()): 
                $isMantenimiento = ($row['estado'] === 'Mantenimiento');
                $displayTitle = $isMantenimiento ? ($row['detalle_resolucion'] ?: 'Mantenimiento Programado') : $row['asunto'];
                $displayDeadline = $isMantenimiento ? $row['fecha_mantenimiento'] : $row['fecha_limite'];

                if(empty($displayDeadline) && !$isMantenimiento){
                    $displayDeadline = date('Y-m-d H:i:s', strtotime($row['fecha_creacion'] . ' + 48 hours'));
                }

                $ticketArray = [
                    'id' => $row['id'],
                    'asunto' => $row['asunto'],
                    'descripcion' => $row['descripcion'],
                    'prioridad' => $row['prioridad'],
                    'tipo' => $row['tipo'],
                    'adjunto' => !empty($row['archivo_adjunto']) ? true : false,
                    'archivo_nombre' => $row['archivo_nombre'] ?? '',
                    'archivo_tipo' => $row['archivo_tipo'] ?? ''
                ];
                $ticketJsonSeguro = htmlspecialchars(json_encode($ticketArray, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
            ?>
            <div class="col-md-4">
                <div class="card-ticket <?php echo $isMantenimiento ? 'card-mantenimiento' : ''; ?>" 
                     id="card-<?php echo $row['id']; ?>" 
                     data-deadline="<?php echo $displayDeadline; ?>" 
                     data-estado="<?php echo $row['estado']; ?>">
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span class="badge <?php echo $isMantenimiento ? 'bg-primary' : 'bg-secondary'; ?> text-uppercase px-2 py-1">
                            <?php echo $isMantenimiento ? 'Mantenimiento' : $row['prioridad']; ?>
                        </span>
                        <div id="timer-<?php echo $row['id']; ?>" class="timer-display">Cargando...</div>
                    </div>
                    
                    <h5 class="fw-bold text-white mb-2 text-truncate"><?php echo htmlspecialchars($displayTitle); ?></h5>
                    <p class="text-secondary small mb-1">Estado: <span class="text-info fw-bold"><?php echo $row['estado']; ?></span></p>
                    <p class="text-secondary small mb-1">Técnico: <span class="text-white"><?php echo $row['tecnico_nombre'] ?? 'Pendiente'; ?></span></p>
                    <p class="text-secondary small mb-3" style="font-size:0.75rem;">Área: <span class="text-info"><?php echo htmlspecialchars($row['solicitante_depto'] ?? 'General'); ?></span></p>
                    
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-secondary text-truncate" style="max-width: 70%;"><i class="bi bi-person me-1"></i><?php echo htmlspecialchars($row['solicitante_nombre']); ?></span>
                        <a href="javascript:void(0)" onclick="verDetalle(this)" data-ticket="<?php echo $ticketJsonSeguro; ?>" class="text-info small text-decoration-none fw-bold">Detalles ></a>
                    </div>

                    <?php if ($rol == 'administrador' || $rol == 'tecnico'): ?>
                    <div class="btn-action-group">
                        <div onclick="asignarTicket(<?php echo $row['id']; ?>)" class="btn-action-card"><i class="bi bi-person-plus text-info"></i><span>Asignar</span></div>
                        <div onclick="extenderTiempo(<?php echo $row['id']; ?>)" class="btn-action-card"><i class="bi bi-clock-history text-primary"></i><span>+Tiempo</span></div>
                        <div onclick="resolverTicket(<?php echo $row['id']; ?>)" class="btn-action-card"><i class="bi bi-check2-circle text-success"></i><span>Resolver</span></div>
                        <div onclick="mantenimientoTicket(<?php echo $row['id']; ?>)" class="btn-action-card"><i class="bi bi-tools text-warning"></i><span>Mante.</span></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: var(--card-bg); border-radius: 20px; border: 1px solid rgba(255,255,255,0.1);">
                <div id="modalContent"></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function updateTimers() {
            const now = new Date().getTime();
            document.querySelectorAll('.card-ticket').forEach(card => {
                const deadlineStr = card.getAttribute('data-deadline');
                const estado = card.getAttribute('data-estado');
                const display = card.querySelector('.timer-display');

                if (estado === 'Resuelto' || estado === 'No Resuelto' || estado === 'Cerrado') {
                    display.innerHTML = "FINALIZADO";
                    display.style.color = "#10b981";
                    card.classList.remove('card-expired', 'card-warning');
                    card.classList.add('card-normal');
                    return;
                }

                if (!deadlineStr) {
                    display.innerHTML = "--:--:--";
                    return;
                }

                const countDate = new Date(deadlineStr).getTime();
                const diff = countDate - now;

                if (diff <= 0) {
                    display.innerHTML = (estado === 'Mantenimiento') ? "EN CURSO" : "EXPIRADO";
                    display.style.color = (estado === 'Mantenimiento') ? '#8b5cf6' : '#ef4444';
                    if(estado !== 'Mantenimiento') card.className = 'card-ticket card-expired';
                } else {
                    const hours = Math.floor(diff / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                    display.innerHTML = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

                    if (estado === 'Mantenimiento') {
                        display.style.color = '#8b5cf6';
                    } else {
                        if (hours < 3) {
                            card.className = 'card-ticket card-warning';
                            display.style.color = '#fbbf24';
                        } else {
                            card.className = 'card-ticket card-normal';
                            display.style.color = '#38bdf8';
                        }
                    }
                }
            });
        }
        setInterval(updateTimers, 1000);
        updateTimers();

        function verDetalle(elemento) {
            let ticket = {};
            try {
                ticket = JSON.parse(elemento.getAttribute('data-ticket'));
            } catch (e) {
                console.error("Error al procesar los datos del ticket", e);
                return;
            }

            let adjuntoHtml = `<div class="py-2 text-muted small"><i class="bi bi-paperclip me-1" style="font-size: 1.2rem;"></i> Sin archivos adjuntos.</div>`;

            if (ticket.adjunto) {
                const rutaControlador = `ver_adjunto.php?id=${ticket.id}`;
                const tipoMime = ticket.archivo_tipo.toLowerCase();
                const nombreArchivo = ticket.archivo_nombre;

                if (tipoMime.includes('image/png') || tipoMime.includes('image/jpeg') || tipoMime.includes('image/jpg')) {
                    adjuntoHtml = `
                        <img src="${rutaControlador}" alt="Adjunto" class="img-fluid rounded-2 mb-2" style="max-height: 180px; object-fit: contain; border: 1px solid rgba(255,255,255,0.1);"><br>
                        <p class="small text-muted mb-1 text-truncate px-2">${nombreArchivo}</p>
                        <a href="${rutaControlador}" target="_blank" class="btn btn-sm btn-outline-info fw-bold mt-1" style="font-size: 0.75rem; border-radius: 8px;">
                            <i class="bi bi-eye me-1"></i> Ver Imagen Completa
                        </a>`;
                } else if (tipoMime.includes('pdf')) {
                    adjuntoHtml = `
                        <div class="py-2">
                            <i class="bi bi-file-earmark-pdf text-danger mb-2" style="font-size: 2.5rem;"></i>
                            <p class="small text-white mb-2 text-truncate px-3">${nombreArchivo}</p>
                            <a href="${rutaControlador}" target="_blank" class="btn btn-sm btn-outline-danger fw-bold" style="font-size: 0.75rem; border-radius: 8px;">
                                <i class="bi bi-file-earmark-arrow-down me-1"></i> Abrir / Ver PDF
                            </a>
                        </div>`;
                } else {
                    adjuntoHtml = `
                        <div class="py-2">
                            <i class="bi bi-file-earmark-text text-info mb-2" style="font-size: 2.5rem;"></i>
                            <p class="small text-white mb-2 text-truncate px-3">${nombreArchivo}</p>
                            <a href="${rutaControlador}" download="${nombreArchivo}" class="btn btn-sm btn-outline-info fw-bold" style="font-size: 0.75rem; border-radius: 8px;">
                                <i class="bi bi-download me-1"></i> Descargar Archivo Real
                            </a>
                        </div>`;
                }
            }

            const htmlModal = `
                <div class="modal-header border-bottom-0 pb-0" style="padding: 25px 25px 0 25px;">
                    <h5 class="modal-title fw-bold text-white" style="font-family: 'Orbitron'; font-size: 1.1rem;">EDITAR TICKET #${ticket.id}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 25px;">
                    <form id="formEditarTicketBase" onsubmit="event.preventDefault(); guardarCambiosTicketBase();">
                        <input type="hidden" name="id" value="${ticket.id}">
                        <input type="hidden" name="accion" value="editar_basico">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Archivo Adjunto Cargado</label>
                            <div class="p-3 text-center rounded-3" style="background: #0b0f1a; border: 1px solid rgba(255,255,255,0.05);">
                                ${adjuntoHtml}
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">TÍTULO / ASUNTO <span class="text-danger">*</span></label>
                            <input type="text" name="asunto" class="form-control form-control-neo" value="${ticket.asunto}" required placeholder="Escriba el asunto...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">DESCRIPCIÓN <span class="text-danger">*</span></label>
                            <textarea name="descripcion" class="form-control form-control-neo" rows="3" required placeholder="Escriba el detalle de la incidencia..." style="font-size:0.9rem;">${ticket.descripcion}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">TIPO DE SOLICITUD</label>
                            <select name="tipo" class="form-select form-control-neo" required style="background-color: #0f172a !important; color: white !important;">
                                <option value="Incidencia" ${ticket.tipo === 'Incidencia' ? 'selected' : ''}>Incidencia (Algo falló)</option>
                                <option value="Solicitud" ${ticket.tipo === 'Solicitud' ? 'selected' : ''}>Solicitud (Nuevo requerimiento)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">PRIORIDAD</label>
                            <div class="input-group">
                                <span class="input-group-text input-group-text-neo" id="modal-termometro-icon" style="background: rgba(15, 23, 42, 0.9); border: 1px solid rgba(255,255,255,0.08);">
                                    <i class="bi bi-thermometer-half"></i>
                                </span>
                                <select name="prioridad" id="modalPrioridadSelect" class="form-select form-control-neo" required onchange="actualizarColorPrioridadModal()" style="background-color: #0f172a !important; color: white !important;">
                                    <option value="Baja" ${ticket.prioridad === 'Baja' ? 'selected' : ''}>Baja</option>
                                    <option value="Media" ${ticket.prioridad === 'Media' ? 'selected' : ''}>Media</option>
                                    <option value="Alta" ${ticket.prioridad === 'Alta' ? 'selected' : ''}>Alta (Crítica)</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-info fw-bold w-100" style="border-radius: 12px; background: var(--accent); border: none; color: #0b0f1a; padding: 12px;"> GUARDAR CAMBIOS </button>
                            <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal" style="border-radius: 12px; background: transparent; border: 1px solid #475569; color: #94a3b8; padding: 12px;"> CANCELAR </button>
                        </div>
                    </form>
                </div>`;
            
            $('#modalContent').html(htmlModal);
            (new bootstrap.Modal(document.getElementById('modalDetalle'))).show();
            actualizarColorPrioridadModal();
        }

        function actualizarColorPrioridadModal() {
            const select = document.getElementById('modalPrioridadSelect');
            const icon = document.querySelector('#modal-termometro-icon i');
            if(!select || !icon) return;
            const valor = select.value;
            icon.style.color = (valor === 'Baja') ? '#10b981' : (valor === 'Media') ? '#f59e0b' : '#ef4444';
        }

        function guardarCambiosTicketBase() {
            const formulario = document.getElementById('formEditarTicketBase');
            
            if (!formulario.reportValidity()) {
                return;
            }

            const fd = new FormData(formulario);
            fetch('tickets_procesar.php', { method: 'POST', body: fd })
            .then(r => r.json()).then(d => { 
                if(d.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Ticket Actualizado', background: '#161c2d', color: '#fff', showConfirmButton: false, timer: 1200 }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: d.message, background: '#161c2d', color: '#fff' });
                }
            }).catch(e => {
                console.error("Error en la solicitud:", e);
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo procesar la solicitud en el servidor.', background: '#161c2d', color: '#fff' });
            });
        }

        async function extenderTiempo(id) {
            const { value: horas } = await Swal.fire({
                title: 'EXTENDER PLAZO',
                input: 'select',
                inputOptions: { '24': '+24 Horas', '48': '+48 Horas', '72': '+72 Horas' },
                background: '#161c2d', color: '#fff', confirmButtonColor: '#38bdf8', showCancelButton: true
            });
            if (horas) enviarAccion({ id, horas, accion: 'extender_tiempo' });
        }

        async function asignarTicket(id) {
            const { value: tId } = await Swal.fire({
                title: 'ASIGNAR TÉCNICO',
                input: 'select',
                inputOptions: { <?php foreach($tecnicos as $t) echo "'{$t['id']}': '".addslashes($t['nombre_completo'])."',"; ?> },
                background: '#161c2d', color: '#fff', confirmButtonColor: '#38bdf8', showCancelButton: true
            });
            if (tId) enviarAccion({ id, tecnico_id: tId, accion: 'asignar' });
        }

        async function resolverTicket(id) {
            const { value: f } = await Swal.fire({
                title: 'RESOLVER TICKET',
                background: '#161c2d', color: '#fff',
                html: '<select id="s-est" class="swal2-select w-100 mb-3"><option value="Resuelto">Resuelto</option><option value="No Resuelto">No Resuelto</option><option value="Cerrado">Cerrado</option></select><textarea id="s-det" class="swal2-textarea w-100" style="background:#0b0f1a; color:white;" placeholder="Detalles de la solución..."></textarea>',
                preConfirm: () => ({ estado: document.getElementById('s-est').value, detalle: document.getElementById('s-det').value })
            });
            if (f) enviarAccion({ ...f, id, accion: 'resolver' });
        }

        async function mantenimientoTicket(id) {
            const { value: f } = await Swal.fire({
                title: 'PROGRAMAR MANTENIMIENTO',
                background: '#161c2d', color: '#fff',
                html: '<label class="small text-secondary d-block mb-1">Fecha y Hora</label><input type="datetime-local" id="s-fec" class="swal2-input w-100 mb-3" value="<?php echo date('Y-m-d\TH:i'); ?>"><textarea id="s-det-m" class="swal2-textarea w-100" style="background:#0b0f1a; color:white;" placeholder="Ej: Cambio de Disco Duro / Limpieza..."></textarea>',
                preConfirm: () => ({ fecha: document.getElementById('s-fec').value, detalle: document.getElementById('s-det-m').value })
            });
            if (f) enviarAccion({ ...f, id, accion: 'mantenimiento' });
        }

        function enviarAccion(datos) {
            const fd = new FormData();
            for (let k in datos) fd.append(k, datos[k]);
            fetch('tickets_procesar.php', { method: 'POST', body: fd })
            .then(r => r.json()).then(d => { 
                if(d.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Ticket Actualizado', background: '#161c2d', color: '#fff', showConfirmButton: false, timer: 1200 }).then(() => location.reload());
                } 
            });
        }

        function solicitarReporte() {
            Swal.fire({
                title: 'EXPORTAR REPORTE',
                text: '¿En qué formato deseas descargar el listado con tus filtros actuales?',
                icon: 'question',
                background: '#161c2d', color: '#fff',
                showCancelButton: true, showDenyButton: true,
                confirmButtonColor: '#10b981', denyButtonColor: '#38bdf8', cancelButtonColor: '#475569',
                confirmButtonText: '<i class="bi bi-file-earmark-excel"></i> Excel',
                denyButtonText: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                let formato = '';
                if (result.isConfirmed) formato = 'excel';
                else if (result.isDenied) formato = 'pdf';
                else return;

                const formElement = document.getElementById('formFiltros');
                const formData = new FormData(formElement);
                const params = new URLSearchParams(formData);
                params.append('formato', formato);

                window.location.href = `tickets_reporte.php?${params.toString()}`;
            });
        }
    </script>
</body>
</html>