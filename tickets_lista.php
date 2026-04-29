<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];

$filtro_estado = $_GET['estado'] ?? '';

// Consulta de Tickets: Se agregan campos de mantenimiento y resolución
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
    $sql .= (strpos($sql, 'WHERE') !== false) ? " AND t.estado = ?" : " WHERE t.estado = ?";
}

$sql .= " ORDER BY t.fecha_creacion DESC";
$stmt = $conexion->prepare($sql);

if (!empty($filtro_estado)) {
    $stmt->bind_param("s", $filtro_estado);
}

$stmt->execute();
$resultado = $stmt->get_result();

// Lista de técnicos para el modal
$tecnicos = [];
if ($rol == 'administrador' || $rol == 'tecnico') {
    $res_tec = $conexion->query("SELECT id, nombre_completo FROM usuarios WHERE rol IN ('admin', 'tecnico')");
    while($t = $res_tec->fetch_assoc()) { $tecnicos[] = $t; }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeoAdmin | Mesa de Ayuda</title>
    
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
            --text-main: #f1f5f9;
        }

        body {
            background-color: var(--bg-dark);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            margin: 0;
            background-image: 
                linear-gradient(rgba(56, 189, 248, 0.02) 1px, transparent 1px), 
                linear-gradient(90deg, rgba(56, 189, 248, 0.02) 1px, transparent 1px);
            background-size: 50px 50px;
        }

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
            display: flex; align-items: center; gap: 8px;
            transition: 0.3s; padding: 8px 15px; border-radius: 10px; font-size: 0.95rem;
        }

        .nav-link-neo:hover { background: var(--accent-soft); color: var(--accent); }

        .main-content { padding: 40px; animation: fadeInPage 0.6s ease-out; }
        @keyframes fadeInPage { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .ticket-card {
            background: var(--card-dark);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            transition: 0.3s;
            height: 100%;
            position: relative;
        }

        .ticket-card:hover { border-color: var(--accent); transform: translateY(-5px); }

        .priority-badge { font-size: 0.7rem; font-weight: 800; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; }
        .prio-alta { background: rgba(248, 113, 113, 0.1); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.2); }
        .prio-media { background: rgba(251, 191, 36, 0.1); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.2); }
        .prio-baja { background: rgba(163, 230, 53, 0.1); color: #a3e635; border: 1px solid rgba(163, 230, 53, 0.2); }

        .timer-display {
            font-family: 'Orbitron', sans-serif;
            font-size: 0.8rem;
            color: var(--accent);
            background: rgba(15, 23, 42, 0.5);
            padding: 5px 12px;
            border-radius: 20px;
            border: 1px solid var(--accent-soft);
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-top: 15px;
        }

        .btn-neo-action {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid var(--glass-border);
            color: var(--text-main);
            padding: 10px 5px;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            transition: 0.3s;
        }

        .btn-neo-action i { font-size: 1.2rem; }
        .btn-neo-action:hover { border-color: var(--accent); background: var(--accent-soft); color: var(--accent); }
        
        .mante-info {
            background: rgba(251, 191, 36, 0.05);
            border: 1px dashed rgba(251, 191, 36, 0.3);
            color: #fbbf24;
            padding: 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

    <nav class="neo-navbar d-flex justify-content-between align-items-center sticky-top">
        <div class="d-flex align-items-center gap-3">
            <img src="img/logo_neoadmin.png" alt="Logo" style="width:45px">
            <span style="font-family: 'Orbitron'; font-size: 1.2rem; color: var(--accent);">NEO ADMIN</span>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="dashboard.php" class="nav-link-neo"><i class="bi bi-house-door-fill"></i> Inicio</a>
            <a href="tickets_lista.php" class="nav-link-neo" style="background: var(--accent-soft); color: var(--accent);"><i class="bi bi-headset"></i> Mesa de Ayuda</a>
            <div class="vr mx-2 opacity-25" style="height: 20px; align-self: center;"></div>
            <a href="logout.php" class="nav-link-neo text-danger"><i class="bi bi-box-arrow-right"></i> Salir</a>
        </div>
    </nav>

    <main class="main-content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fw-bold mb-1" style="font-family: 'Orbitron'; letter-spacing: 1px;">MESA DE AYUDA</h1>
                <p class="text-secondary mb-0">Gestión de incidentes y requerimientos técnicos.</p>
            </div>
            <a href="tickets_crear.php" class="btn btn-info fw-bold px-4 py-2" style="border-radius: 12px;">
                <i class="bi bi-plus-lg me-2"></i>NUEVO TICKET
            </a>
        </div>

        <div class="row g-4">
            <?php while($row = $resultado->fetch_assoc()): 
                $prio_class = 'prio-baja';
                if(strtolower($row['prioridad']) == 'alta') $prio_class = 'prio-alta';
                if(strtolower($row['prioridad']) == 'media') $prio_class = 'prio-media';
                
                // Límite de resolución: 24 horas después de la creación
                $fecha_limite = date('Y-m-d H:i:s', strtotime($row['fecha_creacion'] . ' + 24 hours'));
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="ticket-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="priority-badge <?php echo $prio_class; ?>">
                            <?php echo $row['prioridad']; ?>
                        </span>
                        <div class="timer-display" data-deadline="<?php echo $fecha_limite; ?>" data-estado="<?php echo $row['estado']; ?>">
                            00:00:00
                        </div>
                    </div>

                    <h5 class="fw-bold mb-2 text-white"><?php echo htmlspecialchars($row['asunto']); ?></h5>
                    <p class="text-secondary small mb-1">Estado: <span class="badge bg-secondary"><?php echo $row['estado']; ?></span></p>
                    <p class="text-secondary small mb-1">Técnico: <span class="text-info"><?php echo $row['tecnico_nombre'] ?? 'Sin asignar'; ?></span></p>
                    
                    <?php if (!empty($row['fecha_mantenimiento']) && $row['estado'] == 'Mantenimiento'): ?>
                        <div class="mante-info">
                            <i class="bi bi-calendar-event me-1"></i> Programado: <b><?php echo date('d/m/Y', strtotime($row['fecha_mantenimiento'])); ?></b>
                        </div>
                    <?php endif; ?>

                    <div class="pt-3 border-top border-white border-opacity-10">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="small text-secondary"><i class="bi bi-person-circle me-1"></i> <?php echo $row['solicitante_nombre']; ?></span>
                            <a href="ticket_detalle.php?id=<?php echo $row['id']; ?>" class="text-white-50 small text-decoration-none">Detalles <i class="bi bi-chevron-right"></i></a>
                        </div>

                        <?php if ($rol == 'administrador' || $rol == 'tecnico'): ?>
                        <div class="action-grid">
                            <button onclick="asignarTecnico(<?php echo $row['id']; ?>)" class="btn-neo-action">
                                <i class="bi bi-person-plus-fill"></i> Asignar
                            </button>
                            <button onclick="resolverTicket(<?php echo $row['id']; ?>)" class="btn-neo-action">
                                <i class="bi bi-check-circle-fill"></i> Resolver
                            </button>
                            <button onclick="mantenimientoTicket(<?php echo $row['id']; ?>)" class="btn-neo-action">
                                <i class="bi bi-tools"></i> Mante.
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </main>

    <script>
        // --- LÓGICA DEL TEMPORIZADOR ---
        function startTimers() {
            const timers = document.querySelectorAll('.timer-display');
            setInterval(() => {
                const now = new Date().getTime();
                timers.forEach(timer => {
                    const estado = timer.dataset.estado;
                    // Si ya está resuelto, detenemos el reloj
                    if(estado === 'Resuelto' || estado === 'No Resuelto') {
                        timer.innerHTML = `<i class="bi bi-check-all"></i> CERRADO`;
                        timer.style.color = "#a3e635";
                        return;
                    }

                    const deadline = new Date(timer.dataset.deadline).getTime();
                    const distance = deadline - now;

                    if (distance < 0) {
                        timer.innerHTML = "EXPIRADO";
                        timer.style.color = "#f87171";
                    } else {
                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                        timer.innerHTML = `<i class="bi bi-clock-history"></i> ${hours}h ${minutes}m ${seconds}s`;
                    }
                });
            }, 1000);
        }
        startTimers();

        // --- ASIGNAR TÉCNICO ---
        async function asignarTecnico(id) {
            const { value: tecnicoId } = await Swal.fire({
                title: 'ASIGNAR TÉCNICO',
                background: '#1e293b', 
                color: '#fff',
                input: 'select',
                inputOptions: {
                    <?php foreach($tecnicos as $t): ?>
                    '<?php echo $t['id']; ?>': '<?php echo addslashes($t['nombre_completo']); ?>',
                    <?php endforeach; ?>
                },
                inputPlaceholder: 'Seleccione un técnico...',
                showCancelButton: true,
                confirmButtonColor: '#38bdf8',
                cancelButtonColor: '#64748b'
            });
            if (tecnicoId) enviarAccion({ id, tecnico_id: tecnicoId, accion: 'asignar' });
        }

        // --- RESOLVER TICKET (CON DETALLE) ---
        async function resolverTicket(id) {
            const { value: formValues } = await Swal.fire({
                title: 'RESOLUCIÓN DE TICKET',
                background: '#1e293b', 
                color: '#fff',
                html: `
                    <label class="d-block mb-2 text-start small text-secondary">¿Se solucionó el problema?</label>
                    <select id="sw-estado" class="swal2-input m-0 w-100" style="background: #0f172a; color: white; border: 1px solid var(--glass-border);">
                        <option value="Resuelto">SÍ, Resuelto</option>
                        <option value="No Resuelto">NO, Sin resolución</option>
                    </select>
                    <label class="d-block mt-3 mb-2 text-start small text-secondary">Detalle / Motivo:</label>
                    <textarea id="sw-detalle" class="swal2-textarea m-0 w-100" style="background: #0f172a; color: white; border: 1px solid var(--glass-border);" placeholder="Escriba aquí los detalles técnicos o el por qué no se pudo resolver..."></textarea>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'GUARDAR',
                confirmButtonColor: '#38bdf8',
                preConfirm: () => {
                    const detalle = document.getElementById('sw-detalle').value;
                    if (!detalle) {
                        Swal.showValidationMessage('El detalle es obligatorio');
                    }
                    return {
                        estado: document.getElementById('sw-estado').value,
                        detalle: detalle
                    }
                }
            });
            if (formValues) enviarAccion({ ...formValues, id, accion: 'resolver' });
        }

        // --- PROGRAMAR MANTENIMIENTO ---
        async function mantenimientoTicket(id) {
            const { value: formValues } = await Swal.fire({
                title: 'MANTENIMIENTO',
                background: '#1e293b', color: '#fff',
                html: `
                    <input type="date" id="sw-fecha" class="swal2-input" value="<?php echo date('Y-m-d'); ?>">
                    <textarea id="sw-detalle" class="swal2-textarea" placeholder="Tareas a realizar..."></textarea>
                `,
                preConfirm: () => ({
                    fecha: document.getElementById('sw-fecha').value,
                    detalle: document.getElementById('sw-detalle').value,
                    estado: 'Mantenimiento'
                })
            });
            if (formValues) enviarAccion({ ...formValues, id, accion: 'mantenimiento' });
        }

        function enviarAccion(datos) {
            const formData = new FormData();
            for (let key in datos) { formData.append(key, datos[key]); }
            fetch('tickets_procesar.php', { method: 'POST', body: formData })
            .then(() => {
                Swal.fire({ 
                    icon: 'success', 
                    title: 'Actualizado', 
                    background: '#1e293b', 
                    color: '#fff', 
                    timer: 1500, 
                    showConfirmButton: false 
                })
                .then(() => location.reload());
            });
        }
    </script>
</body>
</html>