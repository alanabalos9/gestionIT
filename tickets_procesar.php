// Ejemplo rápido de lo que debería procesar en tickets_procesar.php
if ($_POST['accion'] == 'resolver') {
    $id = $_POST['id'];
    $estado = $_POST['estado'];
    $detalle = $_POST['detalle'];
    $stmt = $conexion->prepare("UPDATE tickets SET estado = ?, detalle_resolucion = ? WHERE id = ?");
    $stmt->bind_param("ssi", $estado, $detalle, $id);
    $stmt->execute();
}