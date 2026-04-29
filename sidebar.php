<?php
// sidebar.php
$rol_actual = $_SESSION['rol'] ?? 'operativo';
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<div class="d-flex flex-column flex-shrink-0 p-3 text-white shadow-lg" style="width: 280px; height: 100vh; position: fixed; background: #0f172a; border-right: 1px solid rgba(255,255,255,0.05);">
    <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <div class="bg-primary p-2 rounded-3 me-2">
            <i class="bi bi-cpu-fill fs-4"></i>
        </div>
        <span class="fs-4 fw-bold">NEO ADMIN</span>
    </a>
    <hr class="opacity-25">
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item mb-2">
            <a href="dashboard.php" class="nav-link text-white p-3 rounded-4 <?php echo $pagina_actual == 'dashboard.php' ? 'active shadow-primary' : 'opacity-75'; ?>" style="<?php echo $pagina_actual == 'dashboard.php' ? 'background: #3b82f6;' : ''; ?>">
                <i class="bi bi-grid-1x2-fill me-2"></i> Inicio
            </a>
        </li>
        <li class="mb-2">
            <a href="tickets_lista.php" class="nav-link text-white p-3 rounded-4 <?php echo $pagina_actual == 'tickets_lista.php' ? 'active shadow-primary' : 'opacity-75'; ?>" style="<?php echo $pagina_actual == 'tickets_lista.php' ? 'background: #3b82f6;' : ''; ?>">
                <i class="bi bi-chat-left-dots-fill me-2"></i> Mesa de Ayuda
            </a>
        </li>
        <?php if ($rol_actual === 'admin'): ?>
        <li class="mb-2">
            <a href="inventario_lista.php" class="nav-link text-white p-3 rounded-4 <?php echo $pagina_actual == 'inventario_lista.php' ? 'active shadow-primary' : 'opacity-75'; ?>" style="<?php echo $pagina_actual == 'inventario_lista.php' ? 'background: #3b82f6;' : ''; ?>">
                <i class="bi bi-pc-display me-2"></i> Inventario
            </a>
        </li>
        <?php endif; ?>
    </ul>
    <hr class="opacity-25">
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle bg-white bg-opacity-10 p-2 rounded-4" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="https://ui-avatars.com/api/?name=<?php echo $_SESSION['usuario']; ?>&background=0D6EFD&color=fff" alt="" width="32" height="32" class="rounded-circle me-2">
            <span class="small fw-bold"><?php echo strtoupper($_SESSION['usuario']); ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow-lg rounded-4" aria-labelledby="dropdownUser1">
            <li><a class="dropdown-item py-2" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
        </ul>
    </div>
</div>

<style>
    .nav-link:hover { background: rgba(255,255,255,0.1) !important; opacity: 1 !important; }
    .shadow-primary { box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4); }
</style>