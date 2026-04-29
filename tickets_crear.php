<?php
session_start();
require_once 'db.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$msg = "";
$error = "";

// Lógica para el Bot de Soporte
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_soporte_bot'])) {
    $mail_usuario = $_POST['email_soporte'];
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'tu_servidor_smtp'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'tu_correo@dominio.com';
        $mail->Password = 'tu_password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('sistema@it.com', 'IT System Support');
        $mail->addAddress('tu_correo_admin@dominio.com');
        
        $mail->isHTML(true);
        $mail->Subject = 'Solicitud de ayuda desde el Bot';
        $mail->Body    = "El usuario con correo <b>$mail_usuario</b> solicita asistencia técnica.";

        $mail->send();
        $msg = "Solicitud enviada al equipo técnico.";
    } catch (Exception $e) {
        $error = "No se pudo enviar el aviso: {$mail->ErrorInfo}";
    }
}

// Lógica de creación de ticket
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_crear_ticket'])) {
    $asunto = $_POST['asunto'];
    $descripcion = $_POST['descripcion'];
    $prioridad = $_POST['prioridad'];
    $solicitante_id = $_SESSION['usuario_id'];

    $sql = "INSERT INTO tickets (asunto, descripcion, prioridad, solicitante_id, estado) VALUES (?, ?, ?, ?, 'Abierto')";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssi", $asunto, $descripcion, $prioridad, $solicitante_id);

    if ($stmt->execute()) {
        $msg = "Ticket creado con éxito.";
    } else {
        $error = "Error al crear el ticket.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeoAdmin | Crear Ticket</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
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

        /* Navbar Superior */
        .neo-navbar {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--glass-border);
            padding: 12px 30px;
        }

        .nav-link-neo {
            color: white; text-decoration: none; font-weight: 600;
            display: flex; align-items: center; gap: 8px;
            transition: 0.3s; padding: 8px 15px; border-radius: 10px; font-size: 0.95rem;
        }
        .nav-link-neo:hover { background: var(--accent-soft); color: var(--accent); }

        /* Contenedor del Formulario */
        .form-container {
            max-width: 700px; margin: 50px auto;
            background: var(--card-dark);
            border: 1px solid var(--glass-border);
            border-radius: 25px; padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            animation: fadeInPage 0.6s ease-out;
        }

        @keyframes fadeInPage { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .form-control-neo {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--glass-border);
            color: white !important;
            border-radius: 12px; padding: 12px;
        }

        .input-group-text-neo {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--glass-border);
            color: var(--accent);
            border-radius: 12px 0 0 12px;
        }

        .btn-submit {
            background: var(--accent); color: var(--bg-dark);
            font-weight: 800; border-radius: 12px; padding: 14px;
            border: none; text-transform: uppercase; width: 100%; transition: 0.3s;
        }
        .btn-submit:hover { background: #7dd3fc; transform: translateY(-2px); }

        /* --- EFECTO RADAR DEL BOT (ICONO HEADSET) --- */
        .support-bot-trigger {
            position: fixed; bottom: 30px; right: 30px;
            width: 65px; height: 65px;
            background: var(--accent);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; z-index: 1000;
            color: var(--bg-dark); font-size: 1.8rem;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 0 0 0 rgba(56, 189, 248, 0.7);
            animation: radar-pulse 2s infinite;
        }

        .support-bot-trigger:hover { transform: scale(1.2); }

        @keyframes radar-pulse {
            0% { box-shadow: 0 0 0 0 rgba(56, 189, 248, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(56, 189, 248, 0); }
            100% { box-shadow: 0 0 0 0 rgba(56, 189, 248, 0); }
        }

        #chatBot {
            position: fixed; bottom: 110px; right: 30px;
            width: 320px; background: white; border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5); display: none;
            z-index: 1001; overflow: hidden; color: #1e293b;
        }

        .bot-header { background: #1e293b; color: white; padding: 15px; font-weight: 800; display: flex; justify-content: space-between; }
    </style>
</head>
<body>

    <nav class="neo-navbar d-flex justify-content-between align-items-center sticky-top">
        <div class="d-flex align-items-center gap-3">
            <img src="img/logo_neoadmin.png" alt="Logo" style="width: 45px;">
            <span style="font-family: 'Orbitron'; font-size: 1.2rem; color: var(--accent);">NEO ADMIN</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="dashboard.php" class="nav-link-neo"><i class="bi bi-house-door-fill"></i> Inicio</a>
            <a href="tickets_lista.php" class="nav-link-neo"><i class="bi bi-headset"></i> Mesa de Ayuda</a>
            <div class="vr mx-2 opacity-25" style="height: 20px; align-self: center;"></div>
            <a href="logout.php" class="nav-link-neo text-danger"><i class="bi bi-box-arrow-right"></i> Salir</a>
        </div>
    </nav>

    <main class="container">
        <div class="form-container">
            <div class="text-center mb-4">
                <h2 style="font-family: 'Orbitron'; color: var(--accent);">NUEVO TICKET</h2>
                <p class="text-secondary small">Complete el formulario para recibir asistencia técnica.</p>
            </div>

            <?php if($msg): ?> <div class="alert alert-success bg-success bg-opacity-10 text-success border-0 rounded-4"><?php echo $msg; ?></div> <?php endif; ?>
            <?php if($error): ?> <div class="alert alert-danger bg-danger bg-opacity-10 text-danger border-0 rounded-4"><?php echo $error; ?></div> <?php endif; ?>

            <form action="tickets_crear.php" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">ASUNTO</label>
                    <div class="input-group">
                        <span class="input-group-text input-group-text-neo"><i class="bi bi-chat-left-dots"></i></span>
                        <input type="text" name="asunto" class="form-control form-control-neo" placeholder="Breve descripción del problema" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">PRIORIDAD</label>
                    <div class="input-group">
                        <span class="input-group-text input-group-text-neo" id="priority-icon-container">
                            <i class="bi bi-thermometer-low" id="priority-icon"></i>
                        </span>
                        <select name="prioridad" id="prioritySelect" class="form-select form-control-neo" required onchange="updatePriorityIcon()">
                            <option value="Baja" selected>Baja</option>
                            <option value="Media">Media</option>
                            <option value="Alta">Alta (Urgente)</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-secondary">DESCRIPCIÓN</label>
                    <textarea name="descripcion" class="form-control form-control-neo" rows="5" placeholder="Explique detalladamente lo ocurrido..." required></textarea>
                </div>

                <button type="submit" name="btn_crear_ticket" class="btn-submit shadow">
                    <i class="bi bi-plus-circle me-2"></i> GENERAR TICKET
                </button>
            </form>
        </div>
    </main>

    <div class="support-bot-trigger" onclick="toggleBot()">
        <i class="bi bi-headset"></i>
    </div>

    <div id="chatBot">
        <div class="bot-header">
            <span class="small">SOPORTE EN VIVO</span>
            <i class="bi bi-x-lg" onclick="toggleBot()" style="cursor:pointer"></i>
        </div>
        <div class="p-4">
            <p class="small mb-3">¿Necesitas ayuda urgente? Deja tu correo corporativo aquí.</p>
            <form action="tickets_crear.php" method="POST">
                <input type="email" name="email_soporte" class="form-control form-control-sm mb-3" placeholder="Tu mail" required style="background: #f1f5f9; color: black;">
                <button type="submit" name="btn_soporte_bot" class="btn btn-dark w-100 fw-bold btn-sm shadow-sm">NOTIFICAR TÉCNICO</button>
            </form>
        </div>
    </div>

    <script>
        function toggleBot() {
            const bot = document.getElementById('chatBot');
            bot.style.display = (bot.style.display === 'block') ? 'none' : 'block';
        }

        function updatePriorityIcon() {
            const select = document.getElementById('prioritySelect');
            const icon = document.getElementById('priority-icon');
            const container = document.getElementById('priority-icon-container');
            
            if (select.value === "Baja") {
                icon.className = "bi bi-thermometer-low";
                container.style.color = "#a3e635"; // Verde
            } else if (select.value === "Media") {
                icon.className = "bi bi-thermometer-half";
                container.style.color = "#fbbf24"; // Amarillo
            } else {
                icon.className = "bi bi-thermometer-high";
                container.style.color = "#f87171"; // Rojo
            }
        }
        
        // Inicializar icono al cargar
        window.onload = updatePriorityIcon;
    </script>
</body>
</html>