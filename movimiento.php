<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

require_once "conexion.php";

$mensaje_error = "";
$id_producto = $_GET["id"] ?? null;
$tipo = strtolower(trim($_GET["tipo"] ?? "entrada"));

// Validar que el tipo sea únicamente entrada o salida
if (!in_array($tipo, ["entrada", "salida"]) || !$id_producto || !is_numeric($id_producto)) {
    header("Location: inicio.php");
    exit();
}

// 1. Obtener la información del producto
try {
    $stmt = $conexion->prepare("SELECT * FROM producto WHERE id_producto = :id LIMIT 1");
    $stmt->execute([":id" => $id_producto]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        header("Location: inicio.php");
        exit();
    }

    // Si el producto está inactivo (estado == 0), no permitir movimientos
    if ($producto["estado"] == 0) {
        die("<div style='background:#0f172a; color:#f87171; min-height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center; font-family:sans-serif;'>
                <h2>Producto Desactivado</h2>
                <p style='color:#cbd5e1; margin-top:0.5rem;'>Este producto se encuentra inactivo y no permite registrar entradas ni ventas.</p>
                <a href='inicio.php' style='color:#818cf8; margin-top:1.5rem; text-decoration:none; font-weight:600;'>← Volver al Inventario</a>
             </div>");
    }

} catch (PDOException $e) {
    die("Error al consultar el producto: " . $e->getMessage());
}

// 2. Procesar el movimiento
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $cantidad = filter_var($_POST["cantidad"] ?? 0, FILTER_VALIDATE_INT);
    $id_usuario = $_SESSION["usuario_id"];

    if ($cantidad === false || $cantidad <= 0) {
        $mensaje_error = "La cantidad debe ser un número entero mayor a 0.";
    } elseif ($tipo === "salida" && $cantidad > $producto["stock_actual"]) {
        // Regla: no dejar el stock en negativo
        $mensaje_error = "Stock insuficiente. Solo dispones de " . $producto["stock_actual"] . " unidades para salida.";
    } else {
        try {
            $conexion->beginTransaction();

            // A) Registrar en la tabla movimiento
            $sqlMov = "INSERT INTO movimiento (id_producto, id_usuario, fecha, tipo, cantidad) 
                       VALUES (:id_producto, :id_usuario, NOW(), :tipo, :cantidad)";
            $stmtMov = $conexion->prepare($sqlMov);
            $stmtMov->execute([
                ":id_producto" => $id_producto,
                ":id_usuario"  => $id_usuario,
                ":tipo"        => $tipo,
                ":cantidad"    => $cantidad
            ]);

            // B) Actualizar el stock_actual en la tabla producto
            if ($tipo === "entrada") {
                $sqlStock = "UPDATE producto SET stock_actual = stock_actual + :cantidad WHERE id_producto = :id";
            } else {
                $sqlStock = "UPDATE producto SET stock_actual = stock_actual - :cantidad WHERE id_producto = :id";
            }

            $stmtStock = $conexion->prepare($sqlStock);
            $stmtStock->execute([
                ":cantidad" => $cantidad,
                ":id"       => $id_producto
            ]);

            $conexion->commit();

            header("Location: inicio.php");
            exit();

        } catch (PDOException $e) {
            $conexion->rollBack();
            $mensaje_error = "Error al procesar el movimiento: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= ucfirst($tipo) ?> de Stock - TecnoStock</title>
  <link rel="stylesheet" href="diseño.css">
  <style>
    .form-container {
      max-width: 480px;
    }

    .info-card {
      background: rgba(15, 23, 42, 0.7);
      border: 1px solid #334155;
      border-radius: 10px;
      padding: 1rem;
      margin-bottom: 1.5rem;
    }

    .info-card p {
      margin: 0.3rem 0;
      font-size: 0.9rem;
      color: #cbd5e1;
    }

    .stock-badge-val {
      font-weight: 700;
      color: <?= ($producto['stock_actual'] == 0) ? '#f87171' : (($producto['stock_actual'] <= $producto['stock_minimo']) ? '#facc15' : '#4ade80') ?>;
    }

    .badge-tipo {
      display: inline-block;
      padding: 0.25rem 0.65rem;
      border-radius: 9999px;
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .badge-in {
      background: rgba(52, 211, 153, 0.2);
      color: #34d399;
      border: 1px solid rgba(52, 211, 153, 0.35);
    }

    .badge-out {
      background: rgba(248, 113, 113, 0.2);
      color: #f87171;
      border: 1px solid rgba(248, 113, 113, 0.35);
    }

    .btn-cancelar {
      display: block;
      text-align: center;
      margin-top: 1rem;
      color: #94a3b8;
      font-size: 0.9rem;
      text-decoration: none;
      font-weight: 500;
    }

    .btn-cancelar:hover {
      color: #f8fafc;
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <div class="form-container">
    <div class="form-header">
      <h2>
        <span class="badge-tipo <?= ($tipo === 'entrada') ? 'badge-in' : 'badge-out' ?>">
          <?= ($tipo === 'entrada') ? '+ Entrada de Stock' : '- Salida de Stock' ?>
        </span>
      </h2>
      <p style="margin-top: 0.5rem;">
        <?= ($tipo === 'entrada') ? 'Añade unidades al inventario.' : 'Registra retiro o venta de unidades.' ?>
      </p>
    </div>

    <div class="info-card">
      <p><strong>Producto:</strong> <?= htmlspecialchars($producto['nombre']) ?></p>
      <p><strong>Código:</strong> <?= htmlspecialchars($producto['codigo']) ?></p>
      <p><strong>Stock Disponible:</strong> <span class="stock-badge-val"><?= $producto['stock_actual'] ?> unidades</span></p>
      <p><strong>Responsable:</strong> <?= htmlspecialchars($_SESSION["nombre"] ?? "Usuario") ?></p>
    </div>

    <?php if (!empty($mensaje_error)): ?>
      <div class="mensaje-error">
        <?= htmlspecialchars($mensaje_error) ?>
      </div>
    <?php endif; ?>

    <form action="movimiento.php?tipo=<?= $tipo ?>&id=<?= $producto['id_producto'] ?>" method="POST">
      <div class="form-group">
        <label for="cantidad">Cantidad a <?= ($tipo === 'entrada') ? 'ingresar' : 'retirar' ?> *</label>
        <input 
          type="number" 
          id="cantidad" 
          name="cantidad" 
          min="1" 
          <?= ($tipo === 'salida') ? 'max="' . $producto['stock_actual'] . '"' : '' ?>
          placeholder="Ej. 5" 
          required 
          value="<?= htmlspecialchars($_POST['cantidad'] ?? '') ?>"
        >
      </div>

      <button type="submit">
        <?= ($tipo === 'entrada') ? 'Confirmar Entrada' : 'Confirmar Salida' ?>
      </button>

      <a href="inicio.php" class="btn-cancelar">Cancelar y volver</a>
    </form>
  </div>

</body>
</html>