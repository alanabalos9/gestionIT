<?php
session_start();
require_once 'db.php';

// Importar clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$error = "";
$success = "";

/**
 * Función auxiliar para configurar y enviar correos
 */
function enviarCorreo($destinatario, $asunto, $cuerpo) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'testadministrador@gmail.com'; 
        $mail->Password   = 'gzzwnfrsslrikscl'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('testadministrador@gmail.com', 'NEO ADMIN SYSTEM');
        $mail->addAddress($destinatario); 

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpo;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// 1. LÓGICA DE LOGIN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_login'])) {
    $user = $_POST['usuario'];
    $pass = $_POST['password'];

    $stmt = $conexion->prepare("SELECT id, usuario, password, rol FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($row = $resultado->fetch_assoc()) {
        if (password_verify($pass, $row['password']) || $pass == $row['password']) {
            $_SESSION['usuario_id'] = $row['id'];
            $_SESSION['usuario'] = $row['usuario'];
            $_SESSION['rol'] = $row['rol'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Acceso denegado: Credenciales inválidas.";
        }
    } else {
        $error = "Usuario no registrado en la red.";
    }
}

// 2. LÓGICA DE RECUPERACIÓN (Notificación Visual Potenciada)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_recuperar'])) {
    $email_rec = $_POST['email_recuperacion'];
    
   // Busca esta sección en tu index.php y reemplázala:
$asunto = "🔐 Recuperacion de Acceso - NEO ADMIN";
$cuerpo = "<div style='font-family: sans-serif; padding: 20px; background: #0f172a; color: white; border-radius: 15px;'>
            <h2 style='color: #38bdf8;'>NEO ADMIN</h2>
            <p>Se ha solicitado un enlace de recuperación para esta cuenta.</p>
            <a href='http://localhost/gestionIT/restablecer.php?user=$email_rec' style='display: inline-block; padding: 10px 20px; background: #38bdf8; color: #0f172a; text-decoration: none; border-radius: 5px; font-weight: bold;'>RESTABLECER CLAVE</a>
           </div>";

    if (enviarCorreo($email_rec, $asunto, $cuerpo)) {
        $success = "Protocolo de recuperación enviado con éxito. Revisa tu correo.";
    } else {
        $error = "Error de protocolo: Fallo en la conexión con el servidor de seguridad.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEO ADMIN | Secure Access</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Plus+Jakarta+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --neon-blue: #38bdf8;
            --deep-space: #020617;
            --glass: rgba(15, 23, 42, 0.85); /* Un poco más de opacidad para legibilidad */
            --success-glow: rgba(16, 185, 129, 0.2);
            --error-glow: rgba(239, 68, 68, 0.2);
        }

        body {
            /* Configuración de la imagen de fondo */
            background-image: url('img/neoadmin.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: white;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
            position: relative;
        }

        /* Overlay oscuro para resaltar el contenido sobre la imagen */
        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at center, rgba(2, 6, 23, 0.4) 0%, rgba(2, 6, 23, 0.9) 100%);
            z-index: 0;
        }

        /* Malla Estilo Matrix sobrepuesta a la imagen */
        body::after {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: linear-gradient(rgba(56, 189, 248, 0.05) 1px, transparent 1px), 
                              linear-gradient(90deg, rgba(56, 189, 248, 0.05) 1px, transparent 1px);
            background-size: 45px 45px;
            z-index: 1;
            pointer-events: none;
        }

        .login-card {
            position: relative;
            z-index: 10;
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 28px;
            padding: 50px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8);
            animation: fadeIn 0.8s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-text {
            font-family: 'Orbitron', sans-serif;
            letter-spacing: 5px;
            color: var(--neon-blue);
            text-shadow: 0 0 15px rgba(56, 189, 248, 0.6);
        }

        .form-control {
            background: rgba(2, 6, 23, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: white;
            border-radius: 14px;
            padding: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(2, 6, 23, 0.95);
            border-color: var(--neon-blue);
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.2);
            color: white;
        }

        .btn-neon {
            background: var(--neon-blue);
            color: var(--deep-space);
            font-weight: 800;
            border-radius: 14px;
            padding: 15px;
            width: 100%;
            border: none;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: 0.4s;
            box-shadow: 0 5px 20px rgba(56, 189, 248, 0.4);
        }

        .btn-neon:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(56, 189, 248, 0.6);
            background: #7dd3fc;
        }

        .status-alert {
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid transparent;
        }

        .alert-success { 
            background: var(--success-glow); 
            color: #34d399; 
            border-color: rgba(16, 185, 129, 0.3);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.1);
        }

        .alert-danger { 
            background: var(--error-glow); 
            color: #f87171; 
            border-color: rgba(239, 68, 68, 0.3);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.1);
        }

        .support-bot-trigger {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 65px;
            height: 65px;
            background: linear-gradient(135deg, var(--neon-blue), #0284c7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(56, 189, 248, 0.4);
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1000;
            border: 2px solid rgba(255,255,255,0.2);
        }

        .support-bot-trigger:hover {
            transform: scale(1.15) rotate(10deg);
        }

        .support-bot-trigger i {
            color: var(--deep-space);
            font-size: 30px;
        }

        #chatBot {
            position: fixed;
            bottom: 110px;
            right: 30px;
            width: 320px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            display: none;
            z-index: 1001;
            overflow: hidden;
        }

        .bot-header { 
            background: var(--neon-blue); 
            padding: 15px 20px; 
            color: var(--deep-space); 
            font-weight: 800;
            display: flex; 
            justify-content: space-between;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="text-center mb-5">
            <h1 class="logo-text h2 mb-1">NEO ADMIN</h1>
            <p class="text-white-50 small text-uppercase fw-light" style="letter-spacing: 2px;">Protocolo de Autenticación</p>
        </div>

        <?php if($error): ?>
            <div class="status-alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="status-alert alert-success">
                <i class="bi bi-shield-check fs-5"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <div class="mb-4">
                <label class="form-label small text-white-50 fw-bold ms-1">USUARIO</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-0 text-white-50"><i class="bi bi-person-badge"></i></span>
                    <input type="text" name="usuario" class="form-control" placeholder="ID de Usuario" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label small text-white-50 fw-bold ms-1">CONTRASEÑA</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-0 text-white-50"><i class="bi bi-shield-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" name="btn_login" class="btn-neon mt-2">ACCEDER AL SISTEMA</button>
            
            <div class="text-center mt-4">
                <a href="#" class="text-decoration-none small text-white-50" style="transition:0.3s" onmouseover="this.style.color='#38bdf8'" onmouseout="this.style.color='rgba(255,255,255,0.5)'" data-bs-toggle="modal" data-bs-target="#modalRecuperar">Sincronizar nueva credencial</a>
            </div>
        </form>
    </div>

    <div class="modal fade" id="modalRecuperar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: #0f172a; border: 1px solid var(--neon-blue); border-radius: 25px; box-shadow: 0 0 40px rgba(56, 189, 248, 0.4);">
                <div class="modal-body p-5 text-center">
                    <div class="mb-4">
                        <i class="bi bi-envelope-at text-info" style="font-size: 3.5rem;"></i>
                    </div>
                    <h4 class="text-white fw-bold mb-3">RECUPERAR IDENTIDAD</h4>
                    <p class="text-white-50 small mb-4">Se enviará un paquete de datos con el enlace de recuperación a su casilla corporativa.</p>
                    
                    <form action="index.php" method="POST">
                        <input type="email" name="email_recuperacion" class="form-control mb-4 text-center py-3" placeholder="email@dominio.com" required style="border-radius: 15px;">
                        <button type="submit" name="btn_recuperar" class="btn btn-info w-100 py-3 fw-bold shadow-sm" style="border-radius: 15px;">INICIAR TRANSFERENCIA</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="support-bot-trigger" onclick="toggleBot()">
        <i class="bi bi-headset"></i>
    </div>

    <div id="chatBot">
        <div class="bot-header">
            <span>TERMINAL DE AYUDA</span>
            <i class="bi bi-x-lg" style="cursor:pointer" onclick="toggleBot()"></i>
        </div>
        <div class="p-4" style="color: #475569;">
            <p class="small mb-4">¿En que podemos ayudarte? Notifique al administrador de guardia de inmediato.</p>
            <form action="index.php" method="POST">
                <input type="email" name="email_soporte" class="form-control form-control-sm mb-3" placeholder="Tu correo para contacto" required style="background: #f1f5f9; color: black; border: 1px solid #cbd5e1;">
                <button type="submit" name="btn_soporte_bot" class="btn btn-dark w-100 fw-bold">ENVIAR REPORTE</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleBot() {
            const bot = document.getElementById('chatBot');
            if(bot.style.display === 'block') {
                bot.style.display = 'none';
            } else {
                bot.style.display = 'block';
            }
        }
    </script>
</body>
</html>