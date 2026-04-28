<?php
// archivo: index.php
//Este será el formulario visual donde los usuarios podrán loguearse. 
//Incluye una validación básica con Bootstrap para la interfaz de usuario (UI).
session_start();
if(isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php"); // Si ya está logueado, lo manda al panel
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - VivimosTodos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="height: 100vh;">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="text-center mb-4">Unidad Residencial<br><b>Vivimostodos</b></h3>
                    
                    <?php
                    // Mostrar errores si los hay
                    if(isset($_GET['error'])) {
                        echo '<div class="alert alert-danger text-center">'.$_GET['error'].'</div>';
                    }
                    ?>

                    <form action="login_process.php" method="POST">
                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="correo" name="correo" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>