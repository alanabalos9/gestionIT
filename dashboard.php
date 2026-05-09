<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['usuario'])) { header("Location: index.php"); exit(); }
$nombre = $_SESSION['nombre_completo'] ?? $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeoAdmin | Dashboard</title>
    
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
            --text-muted: #94a3b8;
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

        .logo-img {
            width: 45px;
            height: auto;
            object-fit: contain;
            display: block;
        }

        /* Contenido Principal */
        .main-content {
            padding: 40px;
            animation: fadeInPage 0.6s ease-out;
        }

        @keyframes fadeInPage {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Banner Hero */
        .hero-banner { 
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid var(--glass-border);
            border-radius: 30px; 
            padding: 60px; 
            position: relative;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            margin-bottom: 40px;
        }

        .support-character {
            position: absolute;
            right: 40px;
            bottom: -10px;
            width: 280px;
            height: 280px;
            background: url('https://cdn-icons-png.flaticon.com/512/4333/4333609.png'); 
            background-size: contain;
            background-repeat: no-repeat;
            filter: drop-shadow(0 0 15px var(--accent));
            animation: float 5s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }

        /* Tarjetas Estilo Cyber */
        .card-cyber {
            background: var(--card-dark);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 35px;
            transition: all 0.4s ease;
            height: 100%;
        }

        .card-cyber:hover {
            transform: translateY(-8px);
            border-color: var(--accent);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .icon-box {
            width: 65px; height: 65px;
            background: var(--accent-soft);
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 25px;
            color: var(--accent);
            font-size: 1.8rem;
            border: 1px solid rgba(56, 189, 248, 0.2);
        }

        .btn-cyber {
            border-radius: 14px; padding: 12px;
            font-weight: 700; text-transform: uppercase;
            letter-spacing: 1px; font-size: 0.8rem;
            transition: 0.3s;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .btn-accent { background: var(--accent); color: var(--bg-dark); border: none; }
        .btn-accent:hover { background: #7dd3fc; transform: scale(1.02); }

        .btn-outline-custom { background: transparent; border: 1px solid #334155; color: var(--text-main); }
        .btn-outline-custom:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-soft); }

        /* BOT DE SOPORTE CON EL ICONO DE HEADSET Y ANIMACIONES */
        .support-bot-trigger {
            position: fixed; bottom: 30px; right: 30px;
            width: 70px; height: 70px;
            background: #38bdf8; /* Color sólido similar a tu imagen */
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            z-index: 1000;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 4px solid rgba(15, 23, 42, 0.1);
            animation: pulse-shadow 2s infinite;
        }

        @keyframes pulse-shadow {
            0% { box-shadow: 0 0 0 0 rgba(56, 189, 248, 0.6); }
            70% { box-shadow: 0 0 0 15px rgba(56, 189, 248, 0); }
            100% { box-shadow: 0 0 0 0 rgba(56, 189, 248, 0); }
        }

        .support-bot-trigger:hover {
            transform: scale(1.2); /* Aumenta al acercar el mouse */
            box-shadow: 0 0 30px rgba(56, 189, 248, 0.8);
        }

        .support-bot-trigger i {
            color: #0f172a; /* Icono oscuro para contraste */
            font-size: 2rem;
        }

        #chatBot {
            position: fixed; bottom: 110px; right: 30px;
            width: 320px; background: white; border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5); display: none;
            z-index: 1001; overflow: hidden; color: #1e293b;
        }

        .bot-header { 
            background: var(--accent); 
            padding: 15px; 
            color: var(--bg-dark); 
            font-weight: 800; 
            display: flex; 
            justify-content: space-between; 
        }

        @media (max-width: 992px) {
            .support-character { display: none; }
        }
    </style>
</head>
<body>

    <nav class="neo-navbar d-flex justify-content-between align-items-center sticky-top">
        <div class="d-flex align-items-center gap-3">
            <img src="img/logo_neoadmin.png" alt="Logo" class="logo-img">
            <span style="font-family: 'Orbitron'; font-size: 1.2rem; letter-spacing: 1px; color: var(--accent);">NEO ADMIN</span>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="dashboard.php" class="nav-link-neo" style="background: var(--accent-soft); color: var(--accent);">
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

    <main class="main-content container">
        <div class="hero-banner">
            <div class="hero-text">
                <div class="status-badge" style="background: var(--accent-soft); color: var(--accent); padding: 8px 16px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; margin-bottom: 20px; display: inline-flex; align-items: center;">
                    <i class="bi bi-shield-check me-2"></i> Sistema Protegido
                </div>
                <h1 style="font-family: 'Orbitron'; font-weight: 700; font-size: 2.5rem; margin-bottom: 15px;">Bienvenido, <?php echo htmlspecialchars($nombre); ?></h1>
                <p class="opacity-50 mt-3" style="max-width: 450px;">
                    Consola central activa. Gestione el inventario de hardware y supervise los tickets de soporte desde un solo lugar.
                </p>
            </div>
            <div class="support-character"></div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card-cyber">
                    <div class="icon-box"><i class="bi bi-pc-display-horizontal"></i></div>
                    <h3>INVENTARIO</h3>
                    <p class="opacity-50 mb-4">Control total de activos: servidores, laptops y periféricos asignados a la red.</p>
                    <a href="inventario_lista.php" class="btn-cyber btn-outline-custom">Ver Activos IT</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card-cyber">
                    <div class="icon-box"><i class="bi bi-ticket-perforated-fill"></i></div>
                    <h3>NUEVO TICKET</h3>
                    <p class="opacity-50 mb-4">Inicie un reporte de incidencia técnica. El sistema priorizará la resolución automáticamente.</p>
                    <a href="tickets_crear.php" class="btn-cyber btn-accent">Abrir Ticket</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card-cyber">
                    <div class="icon-box" style="background: rgba(148, 163, 184, 0.05); color: var(--text-muted); border: 1px solid rgba(255,255,255,0.05);">
                        <i class="bi bi-activity"></i>
                    </div>
                    <h3>SOLICITUDES</h3>
                    <p class="opacity-50 mb-4">Siga el progreso de sus requerimientos y revise el feedback del equipo técnico.</p>
                    <a href="tickets_lista.php" class="btn-cyber btn-outline-custom">Historial</a>
                </div>
            </div>
        </div>
    </main>

    <div class="support-bot-trigger" onclick="toggleBot()">
        <i class="bi bi-headset"></i>
    </div>

    <div id="chatBot">
        <div class="bot-header">
            <span>TERMINAL DE AYUDA</span>
            <i class="bi bi-x-lg" style="cursor:pointer" onclick="toggleBot()"></i>
        </div>
        <div class="p-4">
            <p class="small mb-4">¿En que podemos ayudarte? Notifique al administrador de guardia.</p>
            <form action="index.php" method="POST">
                <input type="email" name="email_soporte" class="form-control form-control-sm mb-3" placeholder="Correo corporativo" required>
                <button type="submit" name="btn_soporte_bot" class="btn btn-dark w-100 fw-bold">ENVIAR REPORTE</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleBot() {
            const bot = document.getElementById('chatBot');
            bot.style.display = (bot.style.display === 'block') ? 'none' : 'block';
        }
    </script>
</body>
</html>