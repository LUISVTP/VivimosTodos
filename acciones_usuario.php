<?php
// archivo: acciones_usuario.php
session_start();
require 'conexion.php';

// Medida de seguridad: Solo el admin puede ejecutar estas acciones
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    die("Acceso denegado. Función exclusiva para administradores.");

// 1. ACCIÓN: CREAR USUARIO (Viene del formulario principal)

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'crear') {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $correo = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password']; // Contraseña en texto plano
    $rol_id = (int)$_POST['rol_id'];
    $estado = $_POST['estado'];

    $sql = "INSERT INTO usuarios (nombre_completo, correo, password, rol_id, estado) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssis", $nombre, $correo, $password, $rol_id, $estado);
    
    if($stmt->execute()) {
        header("Location: dashboard.php?mensaje=Usuario creado exitosamente.");
    } else {
        header("Location: dashboard.php?mensaje=Error al crear usuario. El correo podría ya existir.");
    }
    exit();
}


// 2. ACCIÓN: ACTUALIZAR USUARIO (Viene del Modal flotante)

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'actualizar') {
    $id = (int)$_POST['id'];
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $correo = $conn->real_escape_string($_POST['correo']);
    $rol_id = (int)$_POST['rol_id'];

    // Actualizamos solo nombre, correo y rol_id
    $sql = "UPDATE usuarios SET nombre_completo = ?, correo = ?, rol_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $nombre, $correo, $rol_id, $id);
    
    if($stmt->execute()) {
        header("Location: dashboard.php?mensaje=Datos del usuario actualizados correctamente.");
    } else {
        header("Location: dashboard.php?mensaje=Error al actualizar el usuario.");
    }
    exit();
}


// 3 Y 4. ACCIONES GET: ESTADO Y ELIMINAR (Vienen de los botones de la tabla)

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['accion'])) {
    
    // --> Acción: Cambiar Estado (Activar <-> Inactivar)
    if ($_GET['accion'] == 'estado' && isset($_GET['id']) && isset($_GET['estado_actual'])) {
        $id = (int)$_GET['id'];
        $nuevo_estado = ($_GET['estado_actual'] == 'activo') ? 'inactivo' : 'activo';

        // Prevenir que el super admin se desactive a sí mismo por error (ID 1)
        if($id == 1) {
            header("Location: dashboard.php?mensaje=No puedes cambiar el estado del Super Administrador principal.");
            exit();
        }

        $sql = "UPDATE usuarios SET estado = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $nuevo_estado, $id);
        $stmt->execute();
        header("Location: dashboard.php?mensaje=El estado del usuario ha sido cambiado a: " . ucfirst($nuevo_estado));
        exit();
    }

    // --> Acción: Eliminar Usuario
    if ($_GET['accion'] == 'eliminar' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];

        // Prevenir que el super admin se elimine a sí mismo (ID 1)
        if($id == 1) {
            header("Location: dashboard.php?mensaje=Por seguridad, no puedes eliminar al Super Administrador principal.");
            exit();
        }

        $sql = "DELETE FROM usuarios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if($stmt->execute()) {
            header("Location: dashboard.php?mensaje=Usuario eliminado del sistema de forma permanente.");
        } else {
            header("Location: dashboard.php?mensaje=Error al eliminar el usuario. Es posible que tenga registros asociados.");
        }
        exit();
    }
}
?>