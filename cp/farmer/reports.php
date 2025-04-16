<?php
//session_start();
require_once '../includes/functions.php';

if (!isFarmer()) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$analytics = getFarmerSalesAnalytics($user_id);
$products = getFarmerProducts($user_id);

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    global $pdo;
    
    if ($report_type === 'sales') {
        $stmt = $pdo->prepare("SELECT o.order_date, p.name as product_name, oi.quantity, oi.price, 
                              (oi.quantity * oi.price) as subtotal, oi.status 
                              FROM order_items oi 
                              JOIN products p ON oi.product_id = p.product_id 
                              JOIN orders o ON oi.order_id = o.order_id 
                              WHERE oi.farmer_id = ? 
                              AND o.order_date BETWEEN ? AND ? 
                              ORDER BY o.order_date DESC");
        $stmt->execute([$user_id, $start_date, $end_date]);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $report_title = "Sales Report (" . date('M j, Y', strtotime($start_date)) . " to " . date('M j, Y', strtotime($end_date)) . ")";
    } else {
        $stmt = $pdo->prepare("SELECT p.name, p.price, p.quantity, p.updated_at, 
                              (SELECT SUM(oi.quantity) FROM order_items oi 
                               JOIN orders o ON oi.order_id = o.order_id 
                               WHERE oi.product_id = p.product_id 
                               AND o.order_date BETWEEN ? AND ?) as sold_quantity 
                              FROM products p 
                              WHERE p.farmer_id = ? 
                              ORDER BY sold_quantity DESC");
        $stmt->execute([$start_date, $end_date, $user_id]);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $report_title = "Inventory Report (" . date('M j, Y', strtotime($start_date)) . " to " . date('M j, Y', strtotime($end_date)) . ")";
    }
    
    // For demo purposes, we'll just display the report on the page
    // In a real application, you might generate a PDF or Excel file
}

$page_title = 'Reports';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Sales Reports</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Generate Report</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="report_type" class="form-label">Report Type</label>
                            <select class="form-select" id="report_type" name="report_type" required>
                                <option value="sales">Sales Report</option>
                                <option value="inventory">Inventory Report</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="generate_report" class="btn btn-success">Generate Report</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Quick Stats</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Revenue
                            <span class="badge bg-success rounded-pill">$<?php echo number_format($analytics['total_revenue'], 2); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Active Products
                            <span class="badge bg-primary rounded-pill"><?php echo count($products); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Top Selling Product
                            <span class="text-end">
                                <?php if (!empty($analytics['top_products'])): ?>
                                    <?php echo htmlspecialchars($analytics['top_products'][0]['name']); ?><br>
                                    <small><?php echo $analytics['top_products'][0]['total_sold']; ?> sold</small>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <?php if (isset($report_data)): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $report_title; ?></h5>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Generated on: <?php echo date('F j, Y'); ?></span>
                            <button class="btn btn-sm btn-success" onclick="window.print()">
                                <i class="fas fa-print"></i> Print Report
                            </button>
                        </div>
                        
                        <?php if ($report_type === 'sales'): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Product</th>
                                            <th>Qty</th>
                                            <th>Price</th>
                                            <th>Subtotal</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $item): ?>
                                            <tr>
                                                <td><?php echo date('M j, Y', strtotime($item['order_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                                                <td><?php echo ucfirst($item['status']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-success">
                                            <td colspan="4" class="text-end"><strong>Total</strong></td>
                                            <td colspan="2">
                                                $<?php 
                                                $total = array_reduce($report_data, function($carry, $item) {
                                                    return $carry + $item['subtotal'];
                                                }, 0);
                                                echo number_format($total, 2); 
                                                ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Current Stock</th>
                                            <th>Sold</th>
                                            <th>Last Updated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td><?php echo $item['sold_quantity'] ?? 0; ?></td>
                                                <td><?php echo date('M j, Y', strtotime($item['updated_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
                        <h5>No Report Generated</h5>
                        <p class="text-muted">Select report type and date range to generate a report.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>