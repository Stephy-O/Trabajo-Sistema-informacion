<?php
session_start();

// Proteger la ruta: solo usuarios autenticados
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

require_once "conexion.php";

$mensaje_error = "";

// 1. Obtener las categorías activas para el menú desplegable
try {
    $consultaCategorias = $conexion->query("SELECT id_categoria, nombre FROM categoria WHERE estado = 1 ORDER BY nombre ASC");
    $categorias = $consultaCategorias->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensaje_error = "Error al cargar categorías: " . $e->getMessage();
}

// 2. Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $codigo = trim($_POST["codigo"] ?? "");
    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $id_categoria = $_POST["id_categoria"] ?? "";
    $precio = $_POST["precio"] ?? 0;
    $stock_actual = $_POST["stock_actual"] ?? 0;
    $stock_minimo = $_POST["stock_minimo"] ?? 0;

    // Validaciones básicas
    if ($codigo === "" || $nombre === "" || $id_categoria === "") {
        $mensaje_error = "Por favor completa los campos obligatorios (*).";
    } elseif (!is_numeric($precio) || $precio < 0) {
        $mensaje_error = "El precio debe ser un número válido.";
    } elseif (!is_numeric($stock_actual) || $stock_actual < 0 || !is_numeric($stock_minimo) || $stock_minimo < 0) {
        $mensaje_error = "El stock debe ser un número entero mayor o igual a 0.";
    } else {
        try {
            // Verificar que el código del producto no esté duplicado
            $checkSql = "SELECT id_producto FROM producto WHERE codigo = :codigo LIMIT 1";
            $checkStmt = $conexion->prepare($checkSql);
            $checkStmt->execute([":codigo" => $codigo]);

            if ($checkStmt->fetch()) {
                $mensaje_error = "El código '$codigo' ya pertenece a otro producto.";
            } else {
                // Insertar el nuevo producto (con estado = 1 por defecto)
                $sqlInsert = "INSERT INTO producto (id_categoria, codigo, nombre, descripcion, precio, stock_actual, stock_minimo, estado) 
                              VALUES (:id_categoria, :codigo, :nombre, :descripcion, :precio, :stock_actual, :stock_minimo, 1)";
                
                $stmt = $conexion->prepare($sqlInsert);
                $stmt->execute([
                    ":id_categoria" => $id_categoria,
                    ":codigo"       => $codigo,
                    ":nombre"       => $nombre,
                    ":descripcion"  => $descripcion,
                    ":precio"       => $precio,
                    ":stock_actual" => $stock_actual,
                    ":stock_minimo" => $stock_minimo
                ]);

                // Redirigir al inventario para ver el producto recién creado
                header("Location: inicio.php");
                exit();
            }
        } catch (PDOException $e) {
            $mensaje_error = "Error al guardar el producto: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrar Producto - TecnoStock</title>
  <link rel="stylesheet" href="diseño.css">
  <style>
    /* Estilos complementarios para selectores y disposición en grid */
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
      <h2>Registrar Producto</h2>
      <p>Ingresa los detalles para agregar un ítem al inventario.</p>
    </div>

    <?php if (!empty($mensaje_error)): ?>
      <div class="mensaje-error">
        <?= htmlspecialchars($mensaje_error) ?>
      </div>
    <?php endif; ?>

    <form action="crear_producto.php" method="POST">
      <div class="form-row">
        <div class="form-group">
          <label for="codigo">Código *</label>
          <input type="text" id="codigo" name="codigo" placeholder="Ej. TEC-002" required value="<?= htmlspecialchars($_POST['codigo'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label for="id_categoria">Categoría *</label>
          <select id="id_categoria" name="id_categoria" required>
            <option value="">Selecciona...</option>
            <?php foreach ($categorias as $cat): ?>
              <option value="<?= $cat['id_categoria'] ?>" <?= (isset($_POST['id_categoria']) && $_POST['id_categoria'] == $cat['id_categoria']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label for="nombre">Nombre del Producto *</label>
        <input type="text" id="nombre" name="nombre" placeholder="Ej. Teclado Mecánico RGB" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="2" placeholder="Breve descripción opcional..."><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="precio">Precio ($) *</label>
          <input type="number" id="precio" name="precio" step="0.01" min="0" placeholder="0.00" required value="<?= htmlspecialchars($_POST['precio'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label for="stock_actual">Stock Inicial *</label>
          <input type="number" id="stock_actual" name="stock_actual" min="0" placeholder="0" required value="<?= htmlspecialchars($_POST['stock_actual'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label for="stock_minimo">Stock Mínimo (Alerta) *</label>
        <input type="number" id="stock_minimo" name="stock_minimo" min="0" placeholder="Ej. 5" required value="<?= htmlspecialchars($_POST['stock_minimo'] ?? '5') ?>">
      </div>

      <button type="submit">Guardar Producto</button>

      <a href="inicio.php" class="btn-cancelar">Cancelar y volver</a>
    </form>
  </div>

</body>
</html>