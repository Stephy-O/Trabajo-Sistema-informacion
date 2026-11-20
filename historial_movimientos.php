<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

require_once "conexion.php";

// Consulta SQL uniendo movimiento con producto y con login (para sacar el usuario responsable)
$sql = "SELECT m.id_movimiento, 
               m.fecha, 
               m.tipo, 
               m.cantidad, 
               p.codigo AS codigo_producto, 
               p.nombre AS nombre_producto, 
               u.nombre AS nombre_usuario, 
               u.correo AS correo_usuario
        FROM movimiento m
        INNER JOIN producto p ON m.id_producto = p.id_producto
        INNER JOIN login u ON m.id_usuario = u.ID
        ORDER BY m.fecha DESC, m.id_movimiento DESC";

$consulta = $conexion->prepare($sql);
$consulta->execute();
$movimientos = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historial de Movimientos - TecnoStock</title>
  <link rel="stylesheet" href="diseño.css">
  <style>
    body {
      display: block;
      padding: 2rem;
      min-height: 100vh;
      overflow-y: auto;
    }

    .main-container {
      max-width: 1180px;
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
      margin-bottom: 2rem;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .top-bar h2 {
      color: #f8fafc;
      font-size: 1.6rem;
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

    .btn-back {
      background: rgba(51, 65, 85, 0.6);
      color: #f1f5f9;
      border: 1px solid #475569;
    }

    .btn-back:hover {
      background: rgba(71, 85, 105, 0.8);
      transform: translateY(-2px);
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
      padding: 0.3rem 0.7rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .badge-entrada {
      background-color: rgba(34, 197, 94, 0.15);
      color: #4ade80;
      border: 1px solid rgba(34, 197, 94, 0.35);
    }

    .badge-salida {
      background-color: rgba(239, 68, 68, 0.15);
      color: #f87171;
      border: 1px solid rgba(239, 68, 68, 0.35);
    }

    .cantidad-txt {
      font-weight: 700;
      font-size: 1rem;
    }

    .cantidad-entrada {
      color: #4ade80;
    }

    .cantidad-salida {
      color: #f87171;
    }
  </style>
</head>
<body>

  <div class="main-container">
    <div class="top-bar">
      <div>
        <h2>Historial de Movimientos</h2>
        <p style="color: #94a3b8; font-size: 0.9rem;">Auditoría cronológica de entradas y salidas de inventario</p>
      </div>
      <div>
        <a href="inicio.php" class="btn-action btn-back">← Volver al Inventario</a>
      </div>
    </div>

    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Fecha y Hora</th>
            <th>Tipo</th>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Usuario Responsable</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($movimientos)): ?>
            <?php foreach ($movimientos as $mov): ?>
              <tr>
                <!-- Formato claro de fecha y hora: DD/MM/AAAA HH:MM:SS -->
                <td>
                  <strong><?= date("d/m/Y", strtotime($mov['fecha'])) ?></strong>
                  <div style="color: #94a3b8; font-size: 0.8rem;"><?= date("H:i:s", strtotime($mov['fecha'])) ?> hrs</div>
                </td>
                <td>
                  <?php if (strtolower($mov['tipo']) === 'entrada'): ?>
                    <span class="badge badge-entrada">+ Entrada</span>
                  <?php else: ?>
                    <span class="badge badge-salida">- Salida</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div><strong><?= htmlspecialchars($mov['nombre_producto']) ?></strong></div>
                  <small style="color: #818cf8;"><?= htmlspecialchars($mov['codigo_producto']) ?></small>
                </td>
                <td>
                  <span class="cantidad-txt <?= (strtolower($mov['tipo']) === 'entrada') ? 'cantidad-entrada' : 'cantidad-salida' ?>">
                    <?= (strtolower($mov['tipo']) === 'entrada') ? '+' : '-' ?><?= $mov['cantidad'] ?> u.
                  </span>
                </td>
                <td>
                  <div><?= htmlspecialchars($mov['nombre_usuario']) ?></div>
                  <small style="color: #64748b;"><?= htmlspecialchars($mov['correo_usuario']) ?></small>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="text-align: center; color: #94a3b8; padding: 2.5rem;">
                Aún no hay movimientos registrados en el sistema.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>
</html>