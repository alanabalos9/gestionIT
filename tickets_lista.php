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

// --- 1. LÓGICA DE CONTADORES ---
$query_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'Abierto' OR estado = 'Mantenimiento' THEN 1 ELSE 0 END) as abiertos,
    SUM(CASE WHEN estado = 'En Proceso' THEN 1 ELSE 0 END) as proceso,
    SUM(CASE WHEN estado = 'Mantenimiento' THEN 1 ELSE 0 END) as mantenimiento,
    SUM(CASE WHEN estado = 'Resuelto' THEN 1 ELSE 0 END) as resueltos
    FROM tickets";

if ($rol != 'administrador' && $rol != 'tecnico') {
    $query_stats .= " WHERE solicitante_id = $usuario_id";
}
$stats_res = $conexion->query($query_stats);
$stats = $stats_res->fetch_assoc();

// --- 2. CONSULTA DE TICKETS ---
$sql = ($rol == 'administrador' || $rol == 'tecnico') 
    ? "SELECT t.id, t.asunto, t.descripcion, t.prioridad, t.estado, t.tipo, t.fecha_creacion, t.fecha_limite,
              t.fecha_mantenimiento, t.detalle_resolucion, t.archivo_adjunto,
              u_sol.nombre_completo AS solicitante_nombre, u_tec.nombre_completo AS tecnico_nombre,
              t.tecnico_id
       FROM tickets t
       JOIN usuarios u_sol ON t.solicitante_id = u_sol.id
       LEFT JOIN usuarios u_tec ON t.tecnico_id = u_tec.id" 
    : "SELECT t.id, t.asunto, t.descripcion, t.prioridad, t.estado, t.tipo, t.fecha_creacion, t.fecha_limite,
              t.fecha_mantenimiento, t.detalle_resolucion, t.archivo_adjunto,
              u_sol.nombre_completo AS solicitante_nombre 
       FROM tickets t
       JOIN usuarios u_sol ON t.solicitante_id = u_sol.id
       WHERE t.solicitante_id = $usuario_id";

if (!empty($filtro_estado)) {
    $sql .= (strpos($sql, 'WHERE') !== false) ? " AND t.estado = '$filtro_estado'" : " WHERE t.estado = '$filtro_estado'";
}
$sql .= " ORDER BY t.fecha_creacion DESC";
$res = $conexion->query($sql);

$tecnicos_res = $conexion->query("SELECT id, nombre_completo FROM usuarios WHERE rol = 'tecnico'");
$tecnicos = [];
while ($t = $tecnicos_res->fetch_assoc()) { $tecnicos[] = $t; }
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

        .swal2-select {
            background-color: #0f172a !important;
            color: white !important;
            border: 1px solid rgba(255,255,255,0.2) !important;
        }
        .swal2-select option {
            background-color: #0f172a !important;
            color: white !important;
        }

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
            <h2 style="font-family: 'Orbitron'; font-weight: bold;">TICKETS</h2>
            <a href="tickets_crear.php" class="btn btn-info fw-bold" style="border-radius: 12px; background: var(--accent); border:none;">+ NUEVO TICKET</a>
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

                // ARREGLO FINAL: Creamos un JSON ultra seguro escapando apóstrofes y comillas en hexadecimal
                $ticketArray = [
                    'id' => $row['id'],
                    'asunto' => $row['asunto'],
                    'descripcion' => $row['descripcion'],
                    'prioridad' => $row['prioridad'],
                    'tipo' => $row['tipo'],
                    'adjunto' => $row['archivo_adjunto'] ?? ''
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
                    <p class="text-secondary small mb-3">Técnico: <span class="text-white"><?php echo $row['tecnico_nombre'] ?? 'Pendiente'; ?></span></p>
                    
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-secondary text-truncate" style="max-width: 70%;"><i class="bi bi-person me-1"></i><?php echo htmlspecialchars($row['solicitante_nombre']); ?></span>
                        
                        <a href="javascript:void(0)" 
                           onclick="verDetalle(this)" 
                           data-ticket="<?php echo $ticketJsonSeguro; ?>"
                           class="text-info small text-decoration-none fw-bold">
                           Detalles >
                        </a>
                    </div>

                    <?php if ($rol == 'administrador' || $rol == 'tecnico'): ?>
                    <div class="btn-action-group">
                        <div onclick="asignarTicket(<?php echo $row['id']; ?>)" class="btn-action-card">
                            <i class="bi bi-person-plus text-info"></i><span>Asignar</span>
                        </div>
                        <div onclick="extenderTiempo(<?php echo $row['id']; ?>)" class="btn-action-card">
                            <i class="bi bi-clock-history text-primary"></i><span>+Tiempo</span>
                        </div>
                        <div onclick="resolverTicket(<?php echo $row['id']; ?>)" class="btn-action-card">
                            <i class="bi bi-check2-circle text-success"></i><span>Resolver</span>
                        </div>
                        <div onclick="mantenimientoTicket(<?php echo $row['id']; ?>)" class="btn-action-card">
                            <i class="bi bi-tools text-warning"></i><span>Mante.</span>
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

                if (estado === 'Resuelto' || estado === 'No Resuelto') {
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

        // --- DETALLE CON PREVISUALIZACIÓN DE ARCHIVOS TOTALMENTE SEGURO ---
        function verDetalle(elemento) {
            let ticket = {};
            try {
                // Recuperar y parsear el JSON de forma nativa sin romper el DOM
                ticket = JSON.parse(elemento.getAttribute('data-ticket'));
            } catch (e) {
                console.error("Error al procesar los datos del ticket", e);
                return;
            }

            let adjuntoHtml = `
                <div class="py-2 text-muted small">
                    <i class="bi bi-paperclip me-1" style="font-size: 1.2rem;"></i> Sin archivos adjuntos.
                </div>`;

            if (ticket.adjunto && ticket.adjunto.trim() !== "") {
                const ext = ticket.adjunto.split('.').pop().toLowerCase();
                const ruta = 'uploads/' + ticket.adjunto;
                
                if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                    adjuntoHtml = `
                        <img src="${ruta}" alt="Adjunto" class="img-fluid rounded-2 mb-2" style="max-height: 160px; object-fit: contain; border: 1px solid rgba(255,255,255,0.1);">
                        <br>
                        <a href="${ruta}" target="_blank" class="btn btn-sm btn-outline-info fw-bold mt-1" style="font-size: 0.75rem; border-radius: 8px;">
                            <i class="bi bi-eye me-1"></i> Ver Imagen Completa
                        </a>`;
                } else if (ext === 'pdf') {
                    adjuntoHtml = `
                        <div class="py-2">
                            <i class="bi bi-file-earmark-pdf text-danger mb-2" style="font-size: 2.5rem;"></i>
                            <p class="small text-white mb-2 text-truncate px-3">${ticket.adjunto}</p>
                            <a href="${ruta}" target="_blank" class="btn btn-sm btn-outline-danger fw-bold" style="font-size: 0.75rem; border-radius: 8px;">
                                <i class="bi bi-file-earmark-arrow-down me-1"></i> Abrir PDF
                            </a>
                        </div>`;
                } else {
                    adjuntoHtml = `
                        <div class="py-2">
                            <i class="bi bi-file-earmark-text text-info mb-2" style="font-size: 2.5rem;"></i>
                            <p class="small text-white mb-2 text-truncate px-3">${ticket.adjunto}</p>
                            <a href="${ruta}" target="_blank" class="btn btn-sm btn-outline-info fw-bold" style="font-size: 0.75rem; border-radius: 8px;">
                                <i class="bi bi-download me-1"></i> Descargar Archivo
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
                    <form id="formEditarTicketBase">
                        <input type="hidden" name="id" value="${ticket.id}">
                        <input type="hidden" name="accion" value="editar_ticket_base">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Archivo Adjunto Cargado</label>
                            <div class="p-3 text-center rounded-3" style="background: #0b0f1a; border: 1px solid rgba(255,255,255,0.05);">
                                ${adjuntoHtml}
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">TÍTULO / ASUNTO</label>
                            <input type="text" class="form-control form-control-neo" value="${ticket.asunto}" readonly style="opacity: 0.7; background: #0b0f1a !important;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">DESCRIPCIÓN</label>
                            <textarea class="form-control form-control-neo" rows="3" readonly style="opacity: 0.7; background: #0b0f1a !important; font-size:0.9rem;">${ticket.descripcion}</textarea>
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
                            <button type="button" onclick="guardarCambiosTicketBase()" class="btn btn-info fw-bold w-100" style="border-radius: 12px; background: var(--accent); border: none; color: #0b0f1a; padding: 12px;">
                                GUARDAR CAMBIOS
                            </button>
                            <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal" style="border-radius: 12px; background: transparent; border: 1px solid #475569; color: #94a3b8; padding: 12px;">
                                CANCELAR
                            </button>
                        </div>
                    </form>
                </div>
            `;
            
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
            const fd = new FormData(document.getElementById('formEditarTicketBase'));
            fetch('tickets_procesar.php', { method: 'POST', body: fd })
            .then(r => r.json()).then(d => { 
                if(d.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Ticket Actualizado', background: '#161c2d', color: '#fff', showConfirmButton: false, timer: 1200 })
                    .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: d.message, background: '#161c2d', color: '#fff' });
                }
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
                html: '<select id="s-est" class="swal2-select w-100 mb-3"><option value="Resuelto">Resuelto</option><option value="No Resuelto">No Resuelto</option></select><textarea id="s-det" class="swal2-textarea w-100" style="background:#0b0f1a; color:white;" placeholder="Detalles de la solución..."></textarea>',
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
                    Swal.fire({ icon: 'success', title: 'Ticket Actualizado', background: '#161c2d', color: '#fff', showConfirmButton: false, timer: 1200 })
                    .then(() => location.reload());
                } 
            });
        }
    </script>
</body>
</html>