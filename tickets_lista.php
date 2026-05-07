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
$filtro_estado = $_GET['estado'] ?? '';

// --- 1. LÓGICA DE CONTADORES SUPERIORES ---
$query_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'Abierto' THEN 1 ELSE 0 END) as abiertos,
    SUM(CASE WHEN estado = 'En Proceso' THEN 1 ELSE 0 END) as proceso,
    SUM(CASE WHEN estado = 'Mantenimiento' THEN 1 ELSE 0 END) as mantenimiento,
    SUM(CASE WHEN estado = 'Resuelto' THEN 1 ELSE 0 END) as resueltos
    FROM tickets";

if ($rol != 'administrador' && $rol != 'tecnico') {
    $query_stats .= " WHERE solicitante_id = $usuario_id";
}
$stats = $conexion->query($query_stats)->fetch_assoc();

// --- 2. CONSULTA DE TICKETS SEGÚN ROL ---
$sql = ($rol == 'administrador' || $rol == 'tecnico') 
    ? "SELECT t.id, t.asunto, t.descripcion, t.prioridad, t.estado, t.fecha_creacion, 
              t.fecha_mantenimiento, t.detalle_resolucion,
              u_sol.nombre_completo AS solicitante_nombre, u_tec.nombre_completo AS tecnico_nombre,
              t.tecnico_id
       FROM tickets t
       JOIN usuarios u_sol ON t.solicitante_id = u_sol.id
       LEFT JOIN usuarios u_tec ON t.tecnico_id = u_tec.id" 
    : "SELECT t.id, t.asunto, t.descripcion, t.prioridad, t.estado, t.fecha_creacion, 
              u_sol.nombre_completo AS solicitante_nombre 
       FROM tickets t
       JOIN usuarios u_sol ON t.solicitante_id = u_sol.id
       WHERE t.solicitante_id = $usuario_id";

if (!empty($filtro_estado)) {
    $sql .= (strpos($sql, 'WHERE') !== false) ? " AND t.estado = '$filtro_estado'" : " WHERE t.estado = '$filtro_estado'";
}
$sql .= " ORDER BY t.fecha_creacion DESC";
$res = $conexion->query($sql);

// Obtener técnicos para el modal de asignación
$tecnicos_res = $conexion->query("SELECT id, nombre_completo FROM usuarios WHERE rol = 'tecnico'");
$tecnicos = [];
while ($t = $tecnicos_res->fetch_assoc()) { $tecnicos[] = $t; }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesa de Ayuda - NeoAdmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@300;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0b0f1a;
            --card-bg: #161c2d;
            --accent: #38bdf8;
            --accent-soft: rgba(56, 189, 248, 0.15);
            --text-gray: #94a3b8;
            --logout-red: #f87171;
            --logout-soft: rgba(248, 113, 113, 0.1);
        }

        body { 
            background-color: var(--bg-dark); 
            color: #f8fafc; 
            font-family: 'Inter', sans-serif; 
        }

        /* --- NUEVA NAVBAR ESTILO NEO --- */
        .neo-navbar {
            background: rgba(22, 28, 45, 0.8);
            backdrop-filter: blur(10px);
            padding: 0.75rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 2rem;
        }

        .logo-img { height: 35px; width: auto; }

        .nav-link-neo {
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-gray);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .nav-link-neo:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
        }

        .nav-link-neo i { font-size: 1.1rem; }

        .logout-btn {
            color: var(--logout-red);
            border: 1px solid var(--logout-soft);
        }

        .logout-btn:hover {
            background: var(--logout-soft);
            color: var(--logout-red);
        }

        /* --- CONTADORES --- */
        .card-stat { 
            background: var(--card-bg); 
            border-radius: 15px; 
            border: 1px solid rgba(255,255,255,0.05); 
            padding: 15px; 
            text-align: center; 
        }
        .card-stat h6 { font-family: 'Orbitron'; font-weight: bold; margin-bottom: 5px; font-size: 1.2rem; }
        .card-stat small { color: #64748b; font-weight: bold; letter-spacing: 1px; font-size: 0.7rem; }

        /* --- CARDS DE TICKETS --- */
        .card-ticket { 
            background: var(--card-bg); 
            border-radius: 20px; 
            border: 1px solid rgba(255,255,255,0.05); 
            padding: 1.5rem;
            height: 100%;
            transition: all 0.3s ease;
        }
        .card-ticket:hover { border-color: var(--accent); transform: translateY(-5px); }
        
        .badge-prioridad { background: rgba(255,193,7,0.1); color: #ffc107; font-size: 0.7rem; border-radius: 8px; font-weight: bold; }
        .badge-expirado { background: rgba(239,68,68,0.1); color: #ef4444; font-size: 0.7rem; border-radius: 8px; font-weight: bold; }
        
        /* --- BOTONES DE ACCIÓN GRID --- */
        .btn-action-group {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-top: 15px;
        }

        .btn-action-card {
            background: #0f172a;
            border: 1px solid rgba(255,255,255,0.05);
            color: white;
            padding: 10px 5px;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-action-card:hover {
            background: var(--accent-soft);
            border-color: var(--accent);
        }

        .btn-action-card i { font-size: 1.2rem; margin-bottom: 4px; }
        .btn-action-card span { 
            font-size: 9px; 
            text-transform: uppercase; 
            color: var(--text-gray); 
            font-weight: 700;
        }
        .btn-action-card:hover span { color: #fff; }

        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-thumb { background: var(--card-bg); border-radius: 10px; }
    </style>
</head>
<body>

    <nav class="neo-navbar d-flex justify-content-between align-items-center sticky-top">
        <div class="d-flex align-items-center gap-3">
            <img src="img/logo_neoadmin.png" alt="Logo" class="logo-img">
            <span style="font-family: 'Orbitron'; font-size: 1.1rem; letter-spacing: 1px; color: var(--accent); font-weight: bold;">NEO ADMIN</span>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="dashboard.php" class="nav-link-neo">
                <i class="bi bi-house-door"></i> Inicio
            </a>
            <a href="tickets_lista.php" class="nav-link-neo" style="background: var(--accent-soft); color: var(--accent);">
                <i class="bi bi-headset"></i> Mesa de Ayuda
            </a>
            <div class="vr mx-2 opacity-25" style="height: 20px; align-self: center; background-color: white;"></div>
            <a href="logout.php" class="nav-link-neo logout-btn">
                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
            </a>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 style="font-family: 'Orbitron'; font-weight: bold; margin: 0; letter-spacing: 1px;">TICKETS</h2>
                <p class="text-secondary small">Panel de control y seguimiento técnico.</p>
            </div>
            <a href="nuevo_ticket.php" class="btn btn-info fw-bold px-4 py-2" style="border-radius: 12px; background: var(--accent); color:#000; border:none;">
                <i class="bi bi-plus-lg me-1"></i> NUEVO TICKET
            </a>
        </div>

        <div class="row g-3 mb-5">
            <div class="col-md"><div class="card-stat"><h6><?php echo $stats['total']; ?></h6><small>TOTAL</small></div></div>
            <div class="col-md"><div class="card-stat"><h6 class="text-warning"><?php echo $stats['abiertos']; ?></h6><small>ABIERTOS</small></div></div>
            <div class="col-md"><div class="card-stat"><h6 class="text-info"><?php echo $stats['proceso']; ?></h6><small>PROCESO</small></div></div>
            <div class="col-md"><div class="card-stat"><h6 class="text-primary"><?php echo $stats['mantenimiento']; ?></h6><small>MANTE.</small></div></div>
            <div class="col-md"><div class="card-stat"><h6 class="text-success"><?php echo $stats['resueltos']; ?></h6><small>RESUELTOS</small></div></div>
        </div>

        <div class="row g-4">
            <?php while($row = $res->fetch_assoc()): ?>
            <div class="col-md-4">
                <div class="card-ticket">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="badge badge-prioridad text-uppercase px-2 py-1"><?php echo $row['prioridad']; ?></span>
                        <span class="badge badge-expirado text-uppercase px-2 py-1">EXPIRADO</span>
                    </div>
                    
                    <h5 class="fw-bold text-white mb-2"><?php echo htmlspecialchars($row['asunto']); ?></h5>
                    <p class="text-secondary small mb-1">Estado: <span class="text-info fw-bold"><?php echo $row['estado']; ?></span></p>
                    <p class="text-secondary small mb-3">Técnico: <span class="text-white"><?php echo $row['tecnico_nombre'] ?? 'Pendiente'; ?></span></p>
                    
                    <hr class="border-secondary opacity-25">
                    
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-secondary"><i class="bi bi-person me-1"></i><?php echo htmlspecialchars($row['solicitante_nombre']); ?></span>
                        <a href="javascript:void(0)" onclick="verDetalle(<?php echo $row['id']; ?>)" class="text-info small text-decoration-none fw-bold">Detalles <i class="bi bi-chevron-right"></i></a>
                    </div>

                    <?php if ($rol == 'administrador' || $rol == 'tecnico'): ?>
                    <div class="btn-action-group">
                        <div onclick="asignarTicket(<?php echo $row['id']; ?>)" class="btn-action-card">
                            <i class="bi bi-person-plus text-info"></i>
                            <span>Asignar</span>
                        </div>
                        <div onclick="resolverTicket(<?php echo $row['id']; ?>)" class="btn-action-card">
                            <i class="bi bi-check2-circle text-success"></i>
                            <span>Resolver</span>
                        </div>
                        <div onclick="mantenimientoTicket(<?php echo $row['id']; ?>)" class="btn-action-card">
                            <i class="bi bi-tools text-warning"></i>
                            <span>Mante.</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="background: var(--card-bg); border-radius: 20px;">
                <div id="modalContent"></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let miModal;

        function verDetalle(id) {
            $('#modalContent').load('ticket_detalle.php?id=' + id, function() {
                miModal = new bootstrap.Modal(document.getElementById('modalDetalle'));
                miModal.show();
            });
        }

        async function asignarTicket(id) {
            const { value: tecnicoId } = await Swal.fire({
                title: 'ASIGNAR TÉCNICO',
                input: 'select',
                inputOptions: {
                    <?php foreach($tecnicos as $t): ?>
                    '<?php echo $t['id']; ?>': '<?php echo addslashes($t['nombre_completo']); ?>',
                    <?php endforeach; ?>
                },
                inputPlaceholder: 'Seleccione profesional',
                background: '#161c2d', color: '#fff',
                showCancelButton: true,
                confirmButtonColor: '#38bdf8'
            });
            if (tecnicoId) enviarAccion({ id, tecnico_id: tecnicoId, accion: 'asignar' });
        }

        async function resolverTicket(id) {
            const { value: formValues } = await Swal.fire({
                title: 'RESOLVER TICKET',
                background: '#161c2d', color: '#fff',
                html: `
                    <select id="sw-estado" class="swal2-select m-0 mb-3 w-100" style="background: #0b0f1a; color: white;">
                        <option value="Resuelto">Resuelto</option>
                        <option value="No Resuelto">No Resuelto</option>
                    </select>
                    <textarea id="sw-detalle" class="swal2-textarea m-0 w-100" style="background: #0b0f1a; color: white;" placeholder="Solución..."></textarea>
                `,
                preConfirm: () => ({
                    estado: document.getElementById('sw-estado').value,
                    detalle: document.getElementById('sw-detalle').value
                })
            });
            if (formValues) enviarAccion({ ...formValues, id, accion: 'resolver' });
        }

        async function mantenimientoTicket(id) {
            const { value: formValues } = await Swal.fire({
                title: 'PROGRAMAR MANTENIMIENTO',
                background: '#161c2d', color: '#fff',
                html: `
                    <input type="date" id="sw-fecha" class="swal2-input m-0 mb-3 w-100" value="<?php echo date('Y-m-d'); ?>" style="background: #0b0f1a; color: white;">
                    <textarea id="sw-detalle-mante" class="swal2-textarea m-0 w-100" style="background: #0b0f1a; color: white;" placeholder="Tareas..."></textarea>
                `,
                preConfirm: () => ({
                    fecha: document.getElementById('sw-fecha').value,
                    detalle: document.getElementById('sw-detalle-mante').value
                })
            });
            if (formValues) enviarAccion({ ...formValues, id, accion: 'mantenimiento' });
        }

        function enviarAccion(datos) {
            const formData = new FormData();
            for (let key in datos) { formData.append(key, datos[key]); }
            
            fetch('tickets_procesar.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') location.reload();
                else Swal.fire({ icon: 'error', title: 'Error', text: data.message, background: '#161c2d', color: '#fff' });
            });
        }
    </script>
</body>
</html>