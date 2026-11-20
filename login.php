
<?php
session_start();

// Recuperar el mensaje de error, si existe
$mensaje_error = $_SESSION["mensaje_error"] ?? "";

// Eliminarlo para que no aparezca nuevamente al recargar
unset($_SESSION["mensaje_error"]);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | TecnoStock</title>

    <link rel="stylesheet" href="diseño.css">
</head>
<body>

    <div class="form-container">

        <div class="form-header">
            <h2>Bienvenido a TecnoStock</h2>
            <p>Ingresa tus datos para acceder al sistema.</p>
        </div>

        <form action="validar_login.php" method="POST">

            <div class="form-group">
                <label for="correo">Correo electrónico</label>
                <input
                    type="email"
                    id="correo"
                    name="correo"
                    placeholder="Ej. ana@ejemplo.com"
                    required
                >
            </div>

            <div class="form-group">
                <label for="clave">Contraseña</label>
                <input
                    type="password"
                    id="clave"
                    name="clave"
                    placeholder="Ingresa tu contraseña"
                    required
                >
            </div>

            <?php if ($mensaje_error !== ""): ?>
                <div class="mensaje-error">
                    <?php echo htmlspecialchars($mensaje_error); ?>
                </div>
            <?php endif; ?>

            <button type="submit">Iniciar sesión</button>

        </form>

    </div>

</body>
</html>

