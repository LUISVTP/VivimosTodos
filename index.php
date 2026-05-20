<?php
// Punto de entrada: redirige al login
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: views/auth/login.php");
} else {
    header("Location: views/auth/login.php");
}
exit;
?>
