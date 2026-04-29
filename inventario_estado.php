<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'tecnico')) {
    exit("Acceso denegado");
}

if (isset($_GET['id']) && isset($_GET['nuevo_estado'])) {
    $id = intval($_GET['id']);
    $estado = $_GET['nuevo_estado'];
    
    $stmt = $conexion->prepare("UPDATE inventario SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $estado, $id);
    
    if ($stmt->execute()) {
        header("Location: inventario_lista.php?success=1");
    }
}
exit();