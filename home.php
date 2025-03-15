<?php
include('db_connect.php');
// Fetch data for dashboard cards
$total_rooms = $conn->query("SELECT COUNT(*) FROM houses")->fetch_row()[0];
$total_tenants = $conn->query("SELECT COUNT(*) FROM tenants WHERE status = 1")->fetch_row()[0];
$total_expenses = $conn->query("SELECT SUM(total_amount) FROM expenses")->fetch_row()[0] ?? 0; // Sum of amounts instead of count
$payment = $conn->query("SELECT SUM(amount) AS paid FROM payments WHERE DATE(created_at) = '" . date('Y-m-d') . "'")->fetch_assoc();
$payments_today = $payment['paid'] ?? 0;

// Monthly payment trend (for area chart)
$monthly_payments = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $result = $conn->query("SELECT SUM(amount) AS total FROM payments WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'")->fetch_assoc();
    $monthly_payments[$month] = $result['total'] ?? 0;
}

// Expense categories (for bar chart)
$expense_categories = [];
$result = $conn->query("SELECT ec.category_name, SUM(e.total_amount) AS total 
                        FROM expenses e 
                        INNER JOIN expense_categories ec ON e.category_id = ec.category_id 
                        GROUP BY ec.category_name 
                        ORDER BY total DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $expense_categories[$row['category_name']] = $row['total'];
}

//Recent payments (for table)
$recent_payments = [];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'header.php'; ?>
    <style>
        .card-hover:hover { transform: scale(1.05); transition: transform 0.3s ease; }
        .chart-container { position: relative; height: 300px; width: 100%; }
        .table-responsive { max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>

    <div id="layoutSidenav">
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Dashboard</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>

                    <!-- Dashboard Cards -->
                        <div class="row g-4">
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-primary text-white mb-4 card-hover">
                                    <div class="card-body d-flex align-items-center">
                                        <div>
                                            <h5 class="mb-0">Total Rooms</h5>
                                            <h3 class="fw-bold"><?php echo $total_rooms; ?></h3>
                                        </div>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="index.php?page=houses">View Details</a>
                                        <i class="fas fa-angle-right"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-warning text-white mb-4 card-hover">
                                    <div class="card-body d-flex align-items-center">
                                        <div>
                                            <h5 class="mb-0">Total Tenants</h5>
                                            <h3 class="fw-bold"><?php echo $total_tenants; ?></h3>
                                        </div>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="index.php?page=tenants">View Details</a>
                                        <i class="fas fa-angle-right"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-success text-white mb-4 card-hover">
                                    <div class="card-body d-flex align-items-center">
                                        <div>
                                            <h5 class="mb-0">Payments Today</h5>
                                            <h3 class="fw-bold"><?php echo number_format($payments_today, 2); ?></h3>
                                        </div>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="index.php?page=payments">View Details</a>
                                        <i class="fas fa-angle-right"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-danger text-white mb-4 card-hover">
                                    <div class="card-body d-flex align-items-center">
                                        <div>
                                            <h5 class="mb-0">Total Expenses</h5>
                                            <h3 class="fw-bold"><?php echo number_format($total_expenses, 2); ?></h3>
                                        </div>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="index.php?page=expenses">View Details</a>
                                        <i class="fas fa-angle-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Charts -->
                        <div class="row g-4 mt-4">
                            <div class="col-xl-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-chart-area me-1"></i> Monthly Payment Trends
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="myAreaChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-chart-bar me-1"></i> Top Expense Categories
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="myBarChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <!-- Recent Payments Table -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i> Recent Payments
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="recentPaymentsTable">
                                    <thead>
                                        <tr>
                                            <th>Tenant</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_payments as $payment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payment['tenant_name']); ?></td>
                                                <td><?php echo number_format($payment['amount'], 2); ?></td>
                                                <td><?php echo date('d M Y, H:i', strtotime($payment['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS and Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js" integrity="sha384-6gSOF0iY9XJOWQPkW9PMPKX7W1fXzX4mNgmD91X/2bM0lL1bkb6K8TJxLERd96nD" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Area Chart (Monthly Payments)
            const areaCtx = document.getElementById('myAreaChart').getContext('2d');
            new Chart(areaCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_keys($monthly_payments)); ?>,
                    datasets: [{
                        label: 'Payments',
                        data: <?php echo json_encode(array_values($monthly_payments)); ?>,
                        fill: true,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });

            // Bar Chart (Expense Categories)
            const barCtx = document.getElementById('myBarChart').getContext('2d');
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_keys($expense_categories)); ?>,
                    datasets: [{
                        label: 'Expenses',
                        data: <?php echo json_encode(array_values($expense_categories)); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });

            // DataTable for Recent Payments
            new simpleDatatables.DataTable('#recentPaymentsTable', {
                searchable: true,
                perPageSelect: [5, 10, 15],
                perPage: 5,
                labels: {
                    placeholder: "Search payments...",
                    perPage: "{select} entries per page",
                    noRows: "No payments found",
                    info: "Showing {start} to {end} of {rows} entries"
                }
            });
        });
    </script>
</body>
</html>