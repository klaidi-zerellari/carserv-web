<?php
// show errors while we wire things up
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 1);

session_start();
require 'db_connect.php';

// Block direct access if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$admin_name = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Admin';

/* ---------- Queries ---------- */

// Orders table now uses client_id (NOT user_id)
$ordersSql = "
  SELECT
    o.id,
    u.name   AS client_name,
    u.email  AS client_email,
    o.product_name,
    o.quantity,
    o.total_amount,
    o.order_date,
    o.fake_cc_info
  FROM orders o
  LEFT JOIN users u ON o.client_id = u.id
  ORDER BY o.order_date DESC
  LIMIT 200
";
$ordersRes = $conn->query($ordersSql);

// Simple KPIs
$kpiSql = "
  SELECT
    COUNT(*)                         AS total_orders,
    COALESCE(SUM(total_amount), 0.0) AS total_revenue,
    COUNT(DISTINCT client_id)        AS total_clients
  FROM orders
";
$kpi = $conn->query($kpiSql)->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>CarServ Admin Dashboard</title>

  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root { --cs-primary:#0d6efd; --cs-accent:#00d4ff; }
    body { background: linear-gradient(180deg,#f3f8ff, #ffffff); font-family: "Ubuntu", system-ui, sans-serif; }
    .topbar { background: #fff; border-bottom: 1px solid #e9eef8; }
    .brand { color: var(--cs-primary); font-weight:700; }
    .card { border-radius: 12px; box-shadow: 0 8px 24px rgba(13,110,253,.06); }
    .fake-cc { font-family: monospace; color:#6c757d; background: rgba(0,0,0,.03); padding: 4px 8px; border-radius:6px; }
    .kpi { border-radius: 14px; }
  </style>
</head>
<body>
  <nav class="topbar py-2">
    <div class="container d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center">
        <h4 class="brand mb-0"><i class="fa fa-car me-2"></i>CarServ Admin</h4>
        <span class="ms-3 text-muted">Welcome, <?php echo $admin_name; ?></span>
      </div>
      <div>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
      </div>
    </div>
  </nav>

  <main class="container my-4">
    <!-- KPIs -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="card kpi p-3">
          <div class="text-muted">Total Orders</div>
          <div class="fs-3 fw-bold"><?php echo (int)$kpi['total_orders']; ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card kpi p-3">
          <div class="text-muted">Total Revenue</div>
          <div class="fs-3 fw-bold">€ <?php echo number_format((float)$kpi['total_revenue'], 2, '.', ','); ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card kpi p-3">
          <div class="text-muted">Unique Clients</div>
          <div class="fs-3 fw-bold"><?php echo (int)$kpi['total_clients']; ?></div>
        </div>
      </div>
    </div>

    <!-- Orders table -->
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Recent Orders</h5>
        <small class="text-muted">Latest 200</small>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Client</th>
              <th>Product</th>
              <th>Qty</th>
              <th>Total (EUR)</th>
              <th>Order Date</th>
              <th>Payment (TEST/FAKE)</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($ordersRes && $ordersRes->num_rows): ?>
              <?php while ($row = $ordersRes->fetch_assoc()): ?>
                <tr>
                  <td><?php echo (int)$row['id']; ?></td>
                  <td>
                    <div><?php echo htmlspecialchars($row['client_name'] ?? '—'); ?></div>
                    <div class="text-muted small"><?php echo htmlspecialchars($row['client_email'] ?? '—'); ?></div>
                  </td>
                  <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                  <td><?php echo (int)$row['quantity']; ?></td>
                  <td><?php echo number_format((float)$row['total_amount'], 2, '.', ','); ?></td>
                  <td><?php echo htmlspecialchars($row['order_date']); ?></td>
                  <td><span class="fake-cc"><?php echo htmlspecialchars($row['fake_cc_info'] ?: 'FAKE-CC-N/A'); ?></span></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="7" class="text-center text-muted">No orders found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
