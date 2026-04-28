<?php
// archivo: conexion.php
//Este archivo se encargará de comunicar tu código PHP con MySQL.
$host = "localhost";
$usuario = "root"; // Cambia si tu usuario de MySQL es diferente
$password = ""; // Cambia si tienes contraseña en MySQL
$base_datos = "vivimostodos";

$conn = new mysqli($host, $usuario, $password, $base_datos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
// Establecer el conjunto de caracteres a UTF-8
$conn->set_charset("utf8");
?>