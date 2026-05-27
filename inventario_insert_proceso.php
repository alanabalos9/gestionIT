<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'tecnico')) {
    exit("Error: No autorizado");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_id = intval($_POST['tipo_id']);
    $marca = mysqli_real_escape_string($conexion, trim($_POST['marca']));
    $modelo = mysqli_real_escape_string($conexion, trim($_POST['modelo']));
    $codigo = mysqli_real_escape_string($conexion, trim($_POST['codigo_patrimonial']));

    // 1. Validar si ya existe el código patrimonial
    $check = $conexion->query("SELECT id FROM inventario WHERE codigo_patrimonial = '$codigo'");
    
    if ($check->num_rows > 0) {
        echo "Error: El código patrimonial ya está registrado.";
        exit();
    }

    // 2. Insertar si no existe
    $sql = "INSERT INTO inventario (tipo_id, marca, modelo, codigo_patrimonial, estado) 
            VALUES ($tipo_id, '$marca', '$modelo', '$codigo', 'Disponible')";

    if ($conexion->query($sql)) {
        echo "success";
    } else {
        echo "Error al guardar: " . $conexion->error;
    }
}
?>