<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

require_once "conexion.php";

// Capturar el término de búsqueda por GET
$busqueda = trim($_GET['busqueda'] ?? '');

// Consulta base uniendo producto y categoría
$sql = "SELECT p.id_producto, p.codigo, p.nombre, p.descripcion, p.precio, 
               p.stock_actual, p.stock_minimo, p.estado, c.nombre AS categoria
        FROM producto p
        INNER JOIN categoria c ON p.id_categoria = c.id_categoria";

// Si el usuario escribió algo en el buscador, filtramos por código, nombre o categoría
$parametros = [];
if ($busqueda !== '') {
    $sql .= " WHERE p.codigo LIKE :busqueda 
                 OR p.nombre LIKE :busqueda 
                 OR c.nombre LIKE :busqueda";
    $parametros[':busqueda'] = "%" . $busqueda . "%";
}

$sql .= " ORDER BY p.id_producto DESC";

$consulta = $conexion->prepare($sql);
$consulta->execute($parametros);
$productos = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Productos - TecnoStock</title>
  <link rel="stylesheet" href="diseño.css">
  <style>
    body {
      display: block;
      padding: 2rem;
      min-height: 100vh;
      overflow-y: auto;
    }

    .main-container {
      max-width: 1200px;
      margin: 0 auto;
      background: rgba(30, 41, 59, 0.75);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 2rem;
      box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
    }

    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .top-bar h2 {
      color: #f8fafc;
      font-size: 1.6rem;
    }

    .user-greeting {
      color: #94a3b8;
      font-size: 0.9rem;
    }

    .btn-action {
      display: inline-block;
      text-decoration: none;
      padding: 0.65rem 1.25rem;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 600;
      transition: all 0.2s ease;
    }

    .btn-create {
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      color: #ffffff;
      box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
    }

    .btn-create:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(139, 92, 246, 0.5);
    }

    .btn-history {
      background: rgba(30, 41, 59, 0.9);
      color: #cbd5e1;
      border: 1px solid #475569;
    }

    .btn-history:hover {
      background: rgba(51, 65, 85, 0.9);
      color: #f8fafc;
      border-color: #64748b;
      transform: translateY(-2px);
    }

    /* Barra de Búsqueda con Lupita */
    .search-container {
      margin-bottom: 1.5rem;
      display: flex;
      justify-content: flex-start;
    }

    .search-form {
      display: flex;
      gap: 0.5rem;
      width: 100%;
      max-width: 480px;
    }

    .search-input {
      width: 100%;
      padding: 0.65rem 1rem;
      border: 1.5px solid #334155;
      border-radius: 8px;
      font-size: 0.9rem;
      color: #f1f5f9;
      background-color: rgba(15, 23, 42, 0.85);
      outline: none;
      transition: all 0.25s ease;
    }

    .search-input:focus {
      border-color: #8b5cf6;
      box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.25);
    }

    .btn-search {
      width: auto !important;
      margin-top: 0 !important;
      padding: 0.65rem 1.1rem;
      background: #8b5cf6;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: none;
      animation: none;
    }

    .btn-search:hover {
      background: #7c3aed;
      transform: none;
    }

    .btn-clear {
      display: flex;
      align-items: center;
      padding: 0.65rem 0.9rem;
      background: rgba(51, 65, 85, 0.6);
      color: #cbd5e1;
      text-decoration: none;
      border-radius: 8px;
      font-size: 0.85rem;
      border: 1px solid #475569;
      white-space: nowrap;
    }

    .btn-clear:hover {
      color: #f8fafc;
      background: rgba(71, 85, 105, 0.8);
    }

    .table-wrapper {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }

    th {
      background-color: rgba(15, 23, 42, 0.7);
      color: #94a3b8;
      font-size: 0.82rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding: 1rem;
      border-bottom: 1px solid #334155;
    }

    td {
      padding: 1rem;
      color: #e2e8f0;
      font-size: 0.9rem;
      border-bottom: 1px solid rgba(51, 65, 85, 0.5);
      vertical-align: middle;
    }

    tr:hover td {
      background-color: rgba(51, 65, 85, 0.3);
    }

    .badge {
      display: inline-block;
      padding: 0.25rem 0.65rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .badge-activo {
      background-color: rgba(34, 197, 94, 0.15);
      color: #4ade80;
      border: 1px solid rgba(34, 197, 94, 0.3);
    }

    .badge-inactivo {
      background-color: rgba(239, 68, 68, 0.15);
      color: #f87171;
      border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .badge-agotado {
      background-color: rgba(239, 68, 68, 0.2);
      color: #fca5a5;
      border: 1px solid rgba(239, 68, 68, 0.4);
      display: inline-block;
      margin-top: 0.25rem;
    }

    .badge-alerta {
      background-color: rgba(234, 179, 8, 0.15);
      color: #facc15;
      border: 1px solid rgba(234, 179, 8, 0.35);
      display: inline-block;
      margin-top: 0.25rem;
      font-size: 0.73rem;
      white-space: nowrap;
    }

    .stock-numero {
      font-weight: 700;
      font-size: 1rem;
    }

    .actions-cell {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      white-space: nowrap;
    }

    th:last-child, td:last-child {
      min-width: 270px;
      text-align: center;
    }

    .link-action {
      text-decoration: none;
      font-size: 0.82rem;
      font-weight: 600;
      padding: 0.35rem 0.65rem;
      border-radius: 6px;
      transition: all 0.2s ease;
    }

    .link-edit {
      color: #818cf8;
      background: rgba(129, 140, 248, 0.12);
    }

    .link-mov-in {
      color: #34d399;
      background: rgba(52, 211, 153, 0.12);
    }

    .link-mov-out {
      color: #f87171;
      background: rgba(248, 113, 113, 0.12);
    }

    .link-toggle {
      color: #94a3b8;
      font-size: 0.82rem;
      margin-left: 0.3rem;
    }

    .link-action:hover {
      filter: brightness(1.25);
      transform: translateY(-1px);
    }
  </style>
</head>
<body>

  <div class="main-container">
    <div class="top-bar">
      <div>
        <h2>Inventario TecnoStock</h2>
        <p class="user-greeting">
          Bienvenido(a), <strong><?= htmlspecialchars($_SESSION["nombre"] ?? "Usuario") ?></strong>
        </p>
      </div>
      <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
        <a href="historial_movimientos.php" class="btn-action btn-history">📋 Historial de Movimientos</a>
        <a href="crear_producto.php" class="btn-action btn-create">+ Registrar Producto</a>
      </div>
    </div>

    <!-- Barra de Búsqueda -->
    <div class="search-container">
      <form action="inicio.php" method="GET" class="search-form">
        <input 
          type="text" 
          name="busqueda" 
          class="search-input" 
          placeholder="Buscar por código, nombre o categoría..." 
          value="<?= htmlspecialchars($busqueda) ?>"
        >
        <button type="submit" class="btn-search" title="Buscar">🔍</button>
        <?php if ($busqueda !== ''): ?>
          <a href="inicio.php" class="btn-clear" title="Limpiar filtro">✖ Quitar filtro</a>
        <?php endif; ?>
      </form>
    </div>

    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Código</th>
            <th>Producto</th>
            <th>Categoría</th>
            <th>Precio</th>
            <th>Stock Actual</th>
            <th>Mín.</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($productos)): ?>
            <?php foreach ($productos as $fila): ?>
              <tr>
                <td><strong><?= htmlspecialchars($fila['codigo']) ?></strong></td>
                <td>
                  <div><?= htmlspecialchars($fila['nombre']) ?></div>
                  <small style="color: #64748b;"><?= htmlspecialchars($fila['descripcion']) ?></small>
                </td>
                <td><?= htmlspecialchars($fila['categoria']) ?></td>
                <td>$<?= number_format($fila['precio'], 0, ',', '.') ?> CLP</td>
                <td>
                  <span class="stock-numero"><?= $fila['stock_actual'] ?></span>
                  <br>
                  <?php if ($fila['stock_actual'] == 0): ?>
                    <span class="badge badge-agotado">Sin Stock</span>
                  <?php elseif ($fila['stock_actual'] <= $fila['stock_minimo']): ?>
                    <span class="badge badge-alerta">⚠️ ¡Quedan solo <?= $fila['stock_actual'] ?>!</span>
                  <?php endif; ?>
                </td>
                <td><?= $fila['stock_minimo'] ?></td>
                <td>
                  <?php if ($fila['estado'] == 1): ?>
                    <span class="badge badge-activo">Activo</span>
                  <?php else: ?>
                    <span class="badge badge-inactivo">Inactivo</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="actions-cell">
                    <a href="editar_producto.php?id=<?= $fila['id_producto'] ?>" class="link-action link-edit">Editar</a>

                    <?php if ($fila['estado'] == 1): ?>
                      <a href="movimiento.php?tipo=entrada&id=<?= $fila['id_producto'] ?>" class="link-action link-mov-in">+ Entrada</a>
                      <a href="movimiento.php?tipo=salida&id=<?= $fila['id_producto'] ?>" class="link-action link-mov-out">- Salida</a>
                    <?php else: ?>
                      <span style="color: #64748b; font-size: 0.8rem; font-style: italic;">Sin operaciones</span>
                    <?php endif; ?>

                    <a href="cambiar_estado.php?id=<?= $fila['id_producto'] ?>" class="link-toggle" onclick="return confirm('¿Seguro que deseas cambiar el estado?')">
                      <?= ($fila['estado'] == 1) ? 'Desactivar' : 'Activar' ?>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" style="text-align: center; color: #94a3b8; padding: 2.5rem;">
                <?= ($busqueda !== '') ? 'No se encontraron productos que coincidan con "' . htmlspecialchars($busqueda) . '".' : 'No hay productos registrados en el sistema.' ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>
</html>