<?php
// archivo: conexion.php
// Conexión a Supabase (PostgreSQL) usando PDO

$host      = 'aws-1-us-west-2.pooler.supabase.com';
$puerto    = '5432';
$basedatos = 'postgres';
$usuario   = 'postgres.afckeqbqrsccxukgnclr';
$password  = '14deJULIO200'; // ← Solo reemplaza esto

try {
    $dsn  = "pgsql:host=$host;port=$puerto;dbname=$basedatos;sslmode=require";
    $conn = new PDO($dsn, $usuario, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión con Supabase: " . $e->getMessage());
}
?>