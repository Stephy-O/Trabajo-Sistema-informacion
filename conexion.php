<?php
$host = "localhost";
$dbname = "test";
$usuario = "root";
$password = "";

try {
    $conexion = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $usuario,
        $password
    );

    $conexion->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {
    error_log($e->getMessage());
    die("No se pudo conectar con la base de datos.");
}
?>
