<?php
require_once "conexion.php";

// Correo del usuario de prueba
$correo = "test@gmail.com";

// Contraseña actual
$clave = "1234";

// Crear el hash
$hash = password_hash($clave, PASSWORD_DEFAULT);

// Actualizar la contraseña en MySQL
$sql = "UPDATE login SET clave = :clave WHERE correo = :correo";

$consulta = $conexion->prepare($sql);

$consulta->execute([
    ":clave" => $hash,
    ":correo" => $correo
]);

if ($consulta->rowCount() > 0) {
    echo "¡Contraseña actualizada correctamente!";
} else {
    echo "No se actualizó ningún registro. Comprueba el correo.";
}
?>
