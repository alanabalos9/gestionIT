<?php
session_start();
require_once 'db.php'; // Conexión a la base de datos

$mensaje = "";
$error = "";

// Verificamos que el usuario venga en la URL (simulación de enlace de correo)
if (isset($_GET['user'])) {
    $usuario_target = $_GET['user'];
} else {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nueva_pass = $_POST['nueva_password'];
    $confirmar_pass = $_POST['confirmar_password'];

    if ($nueva_pass === $confirmar_pass) {
        // Encriptar la contraseña antes de guardarla (RECOMENDADO)
        // Asegúrate de que la columna 'password' en tu base de datos sea VARCHAR(255)
        $pass_encriptada = password_hash($nueva_pass, PASSWORD_DEFAULT);

        // Actualizamos la base de datos
        $stmt = $conexion->prepare("UPDATE usuarios SET password = ? WHERE usuario = ?");
        $stmt->bind_param("ss", $pass_encriptada, $usuario_target);
        
        if ($stmt->execute()) {
            $mensaje = "Contraseña actualizada con éxito. Ya puede iniciar sesión.";
        } else {
            $error = "Error al actualizar la contraseña.";
        }
    } else {
        $error = "Las contraseñas no coinciden.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEO ADMIN | Reset Protocol</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Plus+Jakarta+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --neon-blue: #38bdf8;
            --neon-pink: #f472b6;
            --deep-space: #020617;
            --glass: rgba(15, 23, 42, 0.7);
            --success-glow: rgba(16, 185, 129, 0.3);
            --error-glow: rgba(239, 68, 68, 0.3);
        }

        body {
            /* Imagen de fondo solicitada */
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

        /* Overlay oscuro y malla de Matrix sobre la imagen */
        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at center, rgba(2, 6, 23, 0.4) 0%, rgba(2, 6, 23, 0.9) 100%);
            z-index: 0;
        }

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

        .card-reset {
            position: relative;
            z-index: 10;
            background: var(--glass);
            backdrop-filter: blur(15px);
            border: 2px solid rgba(56, 189, 248, 0.3);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 0 50px rgba(56, 189, 248, 0.3);
            animation: fadeIn 0.8s ease;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .card-reset:hover {
            border-color: rgba(56, 189, 248, 0.8);
            box-shadow: 0 0 70px rgba(56, 189, 248, 0.5);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .cyber-title {
            font-family: 'Orbitron', sans-serif;
            letter-spacing: 3px;
            color: var(--neon-blue);
            text-shadow: 0 0 10px rgba(56, 189, 248, 0.7);
            text-transform: uppercase;
        }

        .user-tag {
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 8px;
            padding: 5px 12px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--neon-blue);
            font-size: 0.85rem;
            margin-bottom: 25px;
        }

        .form-label-cyber {
            font-size: 0.75rem;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-left: 5px;
        }

        .form-control-cyber {
            background: rgba(2, 6, 23, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control-cyber:focus {
            background: rgba(2, 6, 23, 1);
            border-color: var(--neon-blue);
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.3);
            color: white;
        }

        .input-group-cyber .input-group-text {
            background: transparent;
            border: none;
            color: #64748b;
            padding-right: 0;
        }

        .input-group-cyber:focus-within .input-group-text {
            color: var(--neon-blue);
        }

        .btn-cyber-primary {
            background: linear-gradient(90deg, #0284c7, var(--neon-blue));
            color: var(--deep-space);
            font-weight: 800;
            border-radius: 10px;
            padding: 14px;
            width: 100%;
            border: none;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: 0.3s;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(2, 132, 199, 0.4);
        }

        .btn-cyber-primary:hover {
            box-shadow: 0 5px 25px rgba(56, 189, 248, 0.6);
            transform: translateY(-2px);
            background: linear-gradient(90deg, #38bdf8, #7dd3fc);
        }

        .status-alert {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid transparent;
        }

        .alert-cyber-success { 
            background: var(--success-glow); 
            color: #34d399; 
            border-color: rgba(16, 185, 129, 0.4);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
        }

        .alert-cyber-danger { 
            background: var(--error-glow); 
            color: #f87171; 
            border-color: rgba(239, 68, 68, 0.4);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.2);
        }

    </style>
</head>
<body>

<div class="card-reset text-center">
    <div class="mb-4">
        <i class="bi bi-shield-lock text-white-50" style="font-size: 2.5rem; filter: drop-shadow(0 0 8px rgba(255,255,255,0.3));"></i>
    </div>
    
    <h3 class="cyber-title mb-1">RESETEAR PROTOCOLO</h3>
    
    <div class="user-tag">
        <i class="bi bi-person-bounding-box"></i>
        <span><?php echo htmlspecialchars($usuario_target); ?></span>
    </div>

    <?php if ($mensaje): ?>
        <div class="status-alert alert-cyber-success">
            <i class="bi bi-shield-check fs-5"></i> <span><?php echo $mensaje; ?></span>
        </div>
        <a href="index.php" class="btn-cyber-primary" style="display: block; text-decoration: none;">IR AL LOGIN</a>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="status-alert alert-cyber-danger">
                <i class="bi bi-exclamation-octagon fs-5"></i> <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4 text-start">
                <label class="form-label-cyber">NUEVA CONTRASEÑA</label>
                <div class="input-group input-group-cyber form-control-cyber">
                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                    <input type="password" name="nueva_password" class="form-control border-0 bg-transparent text-white p-0 ps-3" required placeholder="••••••••">
                </div>
            </div>
            <div class="mb-5 text-start">
                <label class="form-label-cyber">CONFIRMAR CONTRASEÑA</label>
                <div class="input-group input-group-cyber form-control-cyber">
                    <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                    <input type="password" name="confirmar_password" class="form-control border-0 bg-transparent text-white p-0 ps-3" required placeholder="••••••••">
                </div>
            </div>
            <button type="submit" class="btn-cyber-primary">GUARDAR CAMBIOS</button>
        </form>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>