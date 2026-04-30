<?php
session_start();
require_once 'db.php';

// 1. Verificación de seguridad y permisos
// Solo permite el acceso si el usuario está logueado y tiene rol de administrador o técnico
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['administrador', 'tecnico'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado: No autorizado']);
    exit();
}

// 2. Procesamiento de la solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $accion = $_POST['accion'] ?? null;

    // Validación de datos mínimos requeridos
    if (!$id || !$accion) {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos para procesar la solicitud']);
        exit();
    }

    switch ($accion) {
        // ACCIÓN: ASIGNAR TÉCNICO
        case 'asignar':
            if (!isset($_POST['tecnico_id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Debe seleccionar un técnico']);
                exit();
            }
            $tecnico_id = $_POST['tecnico_id'];
            // Al asignar, el estado cambia automáticamente a 'En Proceso'
            $stmt = $conexion->prepare("UPDATE tickets SET tecnico_id = ?, estado = 'En Proceso' WHERE id = ?");
            $stmt->bind_param("ii", $tecnico_id, $id);
            break;

        // ACCIÓN: PROGRAMAR MANTENIMIENTO
        case 'mantenimiento':
            $fecha = $_POST['fecha'];
            $detalle = $_POST['detalle']; // Tareas a realizar descritas en el modal
            // Se actualiza el estado, la fecha específica y se guardan las tareas en detalle_resolucion
            $stmt = $conexion->prepare("UPDATE tickets SET estado = 'Mantenimiento', fecha_mantenimiento = ?, detalle_resolucion = ? WHERE id = ?");
            $stmt->bind_param("ssi", $fecha, $detalle, $id);
            break;

        // ACCIÓN: RESOLVER O CERRAR TICKET
        case 'resolver':
            $estado = $_POST['estado']; // 'Resuelto' o 'No Resuelto'
            $detalle = $_POST['detalle']; // Explicación de la solución o motivo del cierre
            $stmt = $conexion->prepare("UPDATE tickets SET estado = ?, detalle_resolucion = ? WHERE id = ?");
            $stmt->bind_param("ssi", $estado, $detalle, $id);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'La acción solicitada no es válida']);
            exit();
    }

    // 3. Ejecución y respuesta al frontend
    if ($stmt->execute()) {
        // Envía respuesta exitosa para que el SweetAlert en tickets_lista.php se cierre y recargue
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $conexion->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método de solicitud no permitido']);
}
?>