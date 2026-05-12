<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $sector = $_POST['sector'];
    $usuario_id = !empty($_POST['usuario_id']) ? $_POST['usuario_id'] : "NULL";
    $estado = $_POST['estado'];

    // Evitar errores de FK si usuario_id es NULL
    $usuario_val = ($usuario_id === "NULL") ? "NULL" : "'$usuario_id'";

    $sql = "UPDATE inventario SET 
            sector = '$sector', 
            usuario_asignado_id = $usuario_val, 
            estado = '$estado' 
            WHERE id = $id";

    if ($conexion->query($sql)) {
        echo "success";
    } else {
        echo "error: " . $conexion->error;
    }
}
?>