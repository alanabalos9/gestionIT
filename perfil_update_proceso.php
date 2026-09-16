<?php
session_start();
require_once 'db.php';

// Verificar autenticación del usuario
if (!isset($_SESSION['usuario']) && !isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'] ?? null;
$usuario_sesion = $_SESSION['usuario'] ?? null;

// En caso de que sólo esté en sesión el nombre de usuario, recuperar el ID
if (!$usuario_id && $usuario_sesion) {
    $stmt_u = $conexion->prepare("SELECT id FROM usuarios WHERE usuario = ? OR email = ? LIMIT 1");
    $stmt_u->bind_param("ss", $usuario_sesion, $usuario_sesion);
    $stmt_u->execute();
    $res_u = $stmt_u->get_result();
    if ($row_u = $res_u->fetch_assoc()) {
        $usuario_id = $row_u['id'];
        $_SESSION['usuario_id'] = $usuario_id;
    }
}

if (!$usuario_id) {
    header("Location: index.php");
    exit();
}

// 1. Obtener datos actuales del usuario
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

if (!$user_data) {
    header("Location: index.php");
    exit();
}

$es_admin = (isset($user_data['rol']) && strtolower($user_data['rol']) === 'admin') || (isset($_SESSION['rol']) && strtolower($_SESSION['rol']) === 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Cargar variables recibidas por POST
    $nombre_completo = $es_admin ? trim($_POST['nombre_completo'] ?? $user_data['nombre_completo']) : $user_data['nombre_completo'];
    $email = $es_admin ? trim($_POST['email'] ?? $user_data['email']) : $user_data['email'];
    $rol = $es_admin ? trim($_POST['rol'] ?? $user_data['rol']) : ($user_data['rol'] ?? 'usuario');

    $nueva_clave = $_POST['password'] ?? ($_POST['nueva_password'] ?? '');
    $confirmar_clave = $_POST['confirm_password'] ?? ($_POST['confirmar_password'] ?? '');
    $nombre_foto = $user_data['foto_perfil'] ?? '';

    // 2. Validar contraseña (si se envió para cambiar)
    $cambiar_clave = false;
    if (!empty($nueva_clave) || !empty($confirmar_clave)) {
        if ($nueva_clave !== $confirmar_clave) {
            header("Location: perfil.php?error=coincidencia");
            exit();
        }

        // Expresión Regular: 8 a 16 caracteres, 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial
        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&._\-#])[A-Za-z\d@$!%*?&._\-#]{8,16}$/';
        if (!preg_match($pattern, $nueva_clave)) {
            header("Location: perfil.php?error=formato_clave");
            exit();
        }

        $cambiar_clave = true;
    }

    // 3. Procesar subida de Foto de Perfil (soporta 'foto' o 'foto_perfil')
    $file_key = isset($_FILES['foto']) ? 'foto' : (isset($_FILES['foto_perfil']) ? 'foto_perfil' : null);

    if ($file_key && isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES[$file_key]['tmp_name'];
        $fileName      = $_FILES[$file_key]['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadFileDir = 'img/';
            if (!file_exists($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }

            $newFileName = 'user_' . $usuario_id . '_' . time() . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $nombre_foto = $newFileName;
                $_SESSION['foto_perfil'] = $newFileName;
            } else {
                header("Location: perfil.php?error=subida_foto");
                exit();
            }
        } else {
            header("Location: perfil.php?error=formato_foto");
            exit();
        }
    }

    // 4. Actualizar en Base de Datos
    if ($cambiar_clave) {
        $clave_hash = password_hash($nueva_clave, PASSWORD_BCRYPT);
        $fecha_actual = date('Y-m-d H:i:s');

        $sql = "UPDATE usuarios SET nombre_completo = ?, email = ?, rol = ?, foto_perfil = ?, password = ?, ultima_modificacion_pass = ? WHERE id = ?";
        $stmt_up = $conexion->prepare($sql);
        $stmt_up->bind_param("ssssssi", $nombre_completo, $email, $rol, $nombre_foto, $clave_hash, $fecha_actual, $usuario_id);
    } else {
        $sql = "UPDATE usuarios SET nombre_completo = ?, email = ?, rol = ?, foto_perfil = ? WHERE id = ?";
        $stmt_up = $conexion->prepare($sql);
        $stmt_up->bind_param("ssssi", $nombre_completo, $email, $rol, $nombre_foto, $usuario_id);
    }

    if ($stmt_up->execute()) {
        $_SESSION['nombre_completo'] = $nombre_completo;

        if ($cambiar_clave) {
            $_SESSION['forzar_cambio_clave'] = false;
            $_SESSION['mostrar_alerta_clave'] = false;
        }

        header("Location: perfil.php?msg=actualizado");
        exit();
    } else {
        header("Location: perfil.php?error=db");
        exit();
    }
}

header("Location: perfil.php");
exit();