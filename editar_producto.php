<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

require_once "conexion.php";

$mensaje_error = "";
$id_producto = $_GET["id"] ?? null;

if (!$id_producto || !is_numeric($id_producto)) {
    header("Location: inicio.php");
    exit();
}

// 1. Obtener las categorías activas
try {
    $consultaCategorias = $conexion->query("SELECT id_categoria, nombre FROM categoria WHERE estado = 1 ORDER BY nombre ASC");
    $categorias = $consultaCategorias->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensaje_error = "Error al cargar categorías: " . $e->getMessage();
}

// 2. Procesar la actualización si viene por POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $id_categoria = $_POST["id_categoria"] ?? "";
    $precio = $_POST["precio"] ?? 0;
    $stock_actual = $_POST["stock_actual"] ?? 0;
    $stock_minimo = $_POST["stock_minimo"] ?? 0;

    if ($nombre === "" || $id_categoria === "") {
        $mensaje_error = "Completa los campos obligatorios (*).";
    } elseif (!is_numeric($precio) || $precio < 0) {
        $mensaje_error = "El precio debe ser un número entero mayor o igual a 0.";
    } elseif (!is_numeric($stock_actual) || $stock_actual < 0 || !is_numeric($stock_minimo) || $stock_minimo < 0) {
        $mensaje_error = "El stock no puede ser un número negativo.";
    } else {
        try {
            $sqlUpdate = "UPDATE producto 
                          SET id_categoria = :id_categoria,
                              nombre = :nombre,
                              descripcion = :descripcion,
                              precio = :precio,
                              stock_actual = :stock_actual,
                              stock_minimo = :stock_minimo
                          WHERE id_producto = :id_producto";

            $stmtUpdate = $conexion->prepare($sqlUpdate);
            $stmtUpdate->execute([
                ":id_categoria" => $id_categoria,
                ":nombre"       => $nombre,
                ":descripcion"  => $descripcion,
                ":precio"       => $precio,
                ":stock_actual" => $stock_actual,
                ":stock_minimo" => $stock_minimo,
                ":id_producto"  => $id_producto
            ]);

            header("Location: inicio.php");
            exit();
        } catch (PDOException $e) {
            $mensaje_error = "Error al actualizar: " . $e->getMessage();
        }
    }
}

// 3. Consultar datos actuales del producto a editar
try {
    $stmt = $conexion->prepare("SELECT * FROM producto WHERE id_producto = :id LIMIT 1");
    $stmt->execute([":id" => $id_producto]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        header("Location: inicio.php");
        exit();
    }
} catch (PDOException $e) {
    die("Error al consultar el producto: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar Producto - TecnoStock</title>
  <link rel="stylesheet" href="diseño.css">
  <style>
    select {
      width: 100%;
      padding: 0.8rem 1rem;
      border: 1.5px solid #334155;
      border-radius: 10px;
      font-size: 0.95rem;
      color: #f1f5f9;
      background-color: rgba(15, 23, 42, 0.85);
      outline: none;
      transition: all 0.25s ease;
      cursor: pointer;
    }

    select:focus {
      border-color: #8b5cf6;
      box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.25);
    }

    select option {
      background-color: #0f172a;
      color: #f1f5f9;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    .form-container {
      max-width: 520px;
    }

    .input-disabled {
      background-color: #1e293b !important;
      color: #64748b !important;
      cursor: not-allowed;
      border-color: #334155 !important;
    }

    .btn-cancelar {
      display: block;
      text-align: center;
      margin-top: 1rem;
      color: #94a3b8;
      font-size: 0.9rem;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s ease;
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
      <h2>Modificar Producto</h2>
      <p>Actualiza la categoría, precio o stock de este registro.</p>
    </div>

    <?php if (!empty($mensaje_error)): ?>
      <div class="mensaje-error">
        <?= htmlspecialchars($mensaje_error) ?>
      </div>
    <?php endif; ?>

    <form action="editar_producto.php?id=<?= $producto['id_producto'] ?>" method="POST">
      <div class="form-row">
        <div class="form-group">
          <label for="codigo">Código (Fijo)</label>
          <input type="text" id="codigo" class="input-disabled" value="<?= htmlspecialchars($producto['codigo']) ?>" disabled>
        </div>

        <div class="form-group">
          <label for="id_categoria">Categoría *</label>
          <select id="id_categoria" name="id_categoria" required>
            <?php foreach ($categorias as $cat): ?>
              <option value="<?= $cat['id_categoria'] ?>" <?= ($producto['id_categoria'] == $cat['id_categoria']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label for="nombre">Nombre del Producto *</label>
        <input type="text" id="nombre" name="nombre" required value="<?= htmlspecialchars($producto['nombre']) ?>">
      </div>

      <div class="form-group">
        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="2"><?= htmlspecialchars($producto['descripcion']) ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="precio">Precio (CLP $) *</label>
          <input type="number" id="precio" name="precio" step="1" min="0" required value="<?= htmlspecialchars(round($producto['precio'])) ?>">
        </div>

        <div class="form-group">
          <label for="stock_actual">Stock Actual *</label>
          <input type="number" id="stock_actual" name="stock_actual" min="0" required value="<?= htmlspecialchars($producto['stock_actual']) ?>">
        </div>
      </div>

      <div class="form-group">
        <label for="stock_minimo">Stock Mínimo (Alerta) *</label>
        <input type="number" id="stock_minimo" name="stock_minimo" min="0" required value="<?= htmlspecialchars($producto['stock_minimo']) ?>">
      </div>

      <button type="submit">Guardar Cambios</button>
      <a href="inicio.php" class="btn-cancelar">Cancelar y volver</a>
    </form>
  </div>

</body>
</html>