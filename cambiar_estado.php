<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

require_once "conexion.php";

$id = $_GET["id"] ?? null;

if ($id && is_numeric($id)) {
    // Alterna el estado: si es 1 pasa a 0, si es 0 pasa a 1
    $sql = "UPDATE producto SET estado = IF(estado = 1, 0, 1) WHERE id_producto = :id";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([":id" => $id]);
}

header("Location: inicio.php");
exit();
?>