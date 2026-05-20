<?php
// archivo: views/auth/login.php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: ../admin/dashboard.php");
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="height: 100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="text-center mb-4">Unidad Residencial<br><b>Vivimostodos</b></h3>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger text-center"><?php echo htmlspecialchars($_GET['error']); ?></div>
                    <?php endif; ?>

                    <form action="../../controllers/AuthController.php" method="POST">
                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="correo" name="correo" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button type="button" class="btn btn-outline-secondary" id="btnOjo"
                                        tabindex="-1" title="Mostrar/ocultar contraseña">
                                    <i class="bi bi-eye" id="iconOjo"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const btnOjo   = document.getElementById('btnOjo');
    const inputPwd = document.getElementById('password');
    const iconOjo  = document.getElementById('iconOjo');

    btnOjo.addEventListener('click', function () {
        if (inputPwd.type === 'password') {
            inputPwd.type = 'text';
            iconOjo.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            inputPwd.type = 'password';
            iconOjo.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });
</script>
</body>
</html>
