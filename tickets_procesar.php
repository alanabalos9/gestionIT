<?php
session_start();
require_once 'db.php';

// Establecer el encabezado para respuesta JSON
header('Content-Type: application/json');

// 1. Verificación de seguridad básica (Debe estar logueado)
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado: No autorizado']);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];

// 2. Procesamiento de la solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $accion = $_POST['accion'] ?? null;

    if (!$id || !$accion) {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos para procesar la solicitud']);
        exit();
    }

    // Restricción de acciones administrativas/técnicas
    $acciones_staff = ['asignar', 'extender_tiempo', 'mantenimiento', 'resolver', 'editar_basico', 'editar_ticket_base'];
    if (in_array($accion, $acciones_staff) && $rol !== 'administrador' && $rol !== 'tecnico') {
        echo json_encode(['status' => 'error', 'message' => 'No tiene permisos para realizar esta acción']);
        exit();
    }

    $stmt = null;

    switch ($accion) {
        // ACCIÓN: EDITAR TIPO Y PRIORIDAD ESPECÍFICOS
        case 'editar_ticket_base':
            $tipo = $_POST['tipo'] ?? '';
            $prioridad = $_POST['prioridad'] ?? '';

            if (empty($tipo) || empty($prioridad)) {
                echo json_encode(['status' => 'error', 'message' => 'El tipo de solicitud y la prioridad son obligatorios']);
                exit();
            }

            if ($rol !== 'administrador' && $rol !== 'tecnico') {
                $stmt = $conexion->prepare("UPDATE tickets SET tipo = ?, prioridad = ? WHERE id = ? AND solicitante_id = ?");
                $stmt->bind_param("ssii", $tipo, $prioridad, $id, $usuario_id);
            } else {
                $stmt = $conexion->prepare("UPDATE tickets SET tipo = ?, prioridad = ? WHERE id = ?");
                $stmt->bind_param("ssi", $tipo, $prioridad, $id);
            }
            break;

        // ACCIÓN: EDITAR DATOS BÁSICOS COMPLETOS (ASUNTO, DESCRIPCIÓN, TIPO Y PRIORIDAD)
        case 'editar_basico':
            $asunto = $_POST['asunto'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $tipo = $_POST['tipo'] ?? '';
            $prioridad = $_POST['prioridad'] ?? '';

            if (empty($asunto) || empty($descripcion) || empty($tipo) || empty($prioridad)) {
                echo json_encode(['status' => 'error', 'message' => 'Título, descripción, tipo y prioridad son obligatorios']);
                exit();
            }

            if ($rol !== 'administrador' && $rol !== 'tecnico') {
                $stmt = $conexion->prepare("UPDATE tickets SET asunto = ?, descripcion = ?, tipo = ?, prioridad = ?, fecha_limite = IFNULL(fecha_limite, DATE_ADD(NOW(), INTERVAL 24 HOUR)) WHERE id = ? AND solicitante_id = ?");
                $stmt->bind_param("ssssii", $asunto, $descripcion, $tipo, $prioridad, $id, $usuario_id);
            } else {
                $stmt = $conexion->prepare("UPDATE tickets SET asunto = ?, descripcion = ?, tipo = ?, prioridad = ?, fecha_limite = IFNULL(fecha_limite, DATE_ADD(NOW(), INTERVAL 24 HOUR)) WHERE id = ?");
                $stmt->bind_param("ssssi", $asunto, $descripcion, $tipo, $prioridad, $id);
            }
            break;

        // ACCIÓN: ASIGNAR TÉCNICO
        case 'asignar':
            if (!isset($_POST['tecnico_id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Debe seleccionar un técnico']);
                exit();
            }
            $tecnico_id = $_POST['tecnico_id'];
            
            $stmt = $conexion->prepare("UPDATE tickets SET tecnico_id = ?, estado = 'En Proceso', detalle_resolucion = NULL, fecha_limite = DATE_ADD(NOW(), INTERVAL 48 HOUR) WHERE id = ?");
            $stmt->bind_param("ii", $tecnico_id, $id);
            break;

        // ACCIÓN: EXTENDER TIEMPO
        case 'extender_tiempo':
            $horas = isset($_POST['horas']) ? (int)$_POST['horas'] : 0;
            if ($horas !== 24 && $horas !== 48 && $horas !== 72) {
                echo json_encode(['status' => 'error', 'message' => 'Intervalo de tiempo no válido']);
                exit();
            }
            $stmt = $conexion->prepare("UPDATE tickets SET fecha_limite = DATE_ADD(fecha_limite, INTERVAL ? HOUR) WHERE id = ?");
            $stmt->bind_param("ii", $horas, $id);
            break;

        // ACCIÓN: PROGRAMAR MANTENIMIENTO
        case 'mantenimiento':
            $fecha = $_POST['fecha'] ?? '';
            $detalle = $_POST['detalle'] ?? ''; 
            
            if (empty($fecha) || empty($detalle)) {
                echo json_encode(['status' => 'error', 'message' => 'Fecha y descripción de mantenimiento son obligatorios']);
                exit();
            }

            $stmt = $conexion->prepare("UPDATE tickets SET estado = 'Mantenimiento', fecha_mantenimiento = ?, detalle_resolucion = ? WHERE id = ?");
            $stmt->bind_param("ssi", $fecha, $detalle, $id);
            break;

        // ACCIÓN: RESOLVER TICKET
        case 'resolver':
            $estado = $_POST['estado'] ?? ''; 
            $detalle = $_POST['detalle'] ?? ''; 
            $stmt = $conexion->prepare("UPDATE tickets SET estado = ?, detalle_resolucion = ? WHERE id = ?");
            $stmt->bind_param("ssi", $estado, $detalle, $id);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'La acción solicitada no es válida']);
            exit();
    }

    // 3. Ejecución y respuesta
    if ($stmt && $stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        $errorMsg = $stmt ? $stmt->error : $conexion->error;
        echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $errorMsg]);
    }
    
    if ($stmt) $stmt->close();

} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
?>