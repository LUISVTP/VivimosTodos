<?php
// archivo: login_process.php
//Este archivo procesa los datos enviados por el formulario, verifica en la base de datos y valida que el usuario esté "activo"
session_start();
require 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $conn->real_escape_string($_POST['correo']);
    $password = $_POST['password']; // Texto plano

    // Buscar al usuario
    $sql = "SELECT id, nombre_completo, password, rol_id, estado FROM usuarios WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows == 1) {
        $usuario = $resultado->fetch_assoc();

        // Verificar si está activo
        if ($usuario['estado'] !== 'activo') {
            header("Location: index.php?error=Tu cuenta está inactiva. Contacta al administrador.");
            exit();
        }

        // Verificar contraseña
        if ($password === $usuario['password']) {
            
            // Crear variables de sesión
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre_completo'];
            $_SESSION['rol_id'] = $usuario['rol_id'];

            // === EL SEMÁFORO: Redirigir según el rol ===
            if ($usuario['rol_id'] == 1) {
                header("Location: dashboard.php"); // Administrador
            } elseif ($usuario['rol_id'] == 2) {
                header("Location: residente.php"); // Residente
            } elseif ($usuario['rol_id'] == 3) {
                header("Location: supervisor.php"); // Supervisor
            }
            exit();

        } else {
            header("Location: index.php?error=Contraseña incorrecta.");
            exit();
        }
    } else {
        header("Location: index.php?error=El correo no existe.");
        exit();
    }
}
?>
    }
}
?>