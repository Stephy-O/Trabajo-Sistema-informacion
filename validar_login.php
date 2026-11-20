
<?php
session_start();

require_once "conexion.php";

// Comprobar que el formulario se haya enviado por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit();
}

// Recibir los datos del formulario
$correo = trim($_POST["correo"] ?? "");
$clave = $_POST["clave"] ?? "";

// Comprobar que los campos no estén vacíos
if ($correo === "" || $clave === "") {
    $_SESSION["mensaje_error"] = "Debes completar todos los campos.";
    header("Location: login.php");
    exit();
}

// Buscar al usuario por su correo
$sql = "SELECT ID, nombre, correo, clave, estado
        FROM login
        WHERE correo = :correo
        LIMIT 1";

$consulta = $conexion->prepare($sql);

$consulta->execute([
    ":correo" => $correo
]);

$usuario = $consulta->fetch(PDO::FETCH_ASSOC);

// Verificar la contraseña
if ($usuario && password_verify($clave, $usuario["clave"])) {

    // Comprobar si la cuenta está activa
if (strtolower(trim($usuario["estado"])) !== "activo") {
            $_SESSION["mensaje_error"] = "Esta cuenta no está activa.";
        header("Location: login.php");
        exit();
    }

    // Guardar los datos necesarios en la sesión
    session_regenerate_id(true);

    $_SESSION["usuario_id"] = $usuario["ID"];
    $_SESSION["nombre"] = $usuario["nombre"];
    $_SESSION["correo"] = $usuario["correo"];

    // Ir a la página de inicio
    header("Location: inicio.php");
    exit();

} else {

    // Mostrar mensaje si los datos son incorrectos
    $_SESSION["mensaje_error"] = "Correo o contraseña incorrectos.";
    header("Location: login.php");
    exit();
}
?>
