<?php
include 'db_connect.php';

// Initialize variables
$filters = [
    'start_date' => isset($_GET['start_date']) ? $_GET['start_date'] : '',
    'end_date' => isset($_GET['end_date']) ? $_GET['end_date'] : '',
    'payment_status' => isset($_GET['payment_status']) ? $_GET['payment_status'] : '',
    'tenant_id' => isset($_GET['tenant_id']) ? $_GET['tenant_id'] : ''
];

// Build SQL query with filters
$sql = "SELECT p.*, CONCAT(t.firstname, ' ', COALESCE(t.middlename, ''), ' ', t.lastname) AS tenant_name 
        FROM payments p 
        LEFT JOIN tenants t ON p.tenant_id = t.id 
        WHERE 1=1";
$params = [];
$types = "";

if (!empty($filters['start_date'])) {
    $sql .= " AND p.date_paid >= ?";
    $params[] = $filters['start_date'];
    $types .= "s";
}
if (!empty($filters['end_date'])) {
    $sql .= " AND p.date_paid <= ?";
    $params[] = $filters['end_date'];
    $types .= "s";
}
if (!empty($filters['payment_status'])) {
    $sql .= " AND p.payment_status = ?";
    $params[] = $filters['payment_status'];
    $types .= "s";
}
if (!empty($filters['tenant_id'])) {
    $sql .= " AND p.tenant_id = ?";
    $params[] = $filters['tenant_id'];
    $types .= "i";
}

$sql .= " ORDER BY p.date_paid DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$payments = $result->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$total_amount = array_sum(array_column($payments, 'amount'));
$total_outstanding = array_sum(array_column($payments, 'outstanding_balance'));
$total_late_fee = array_sum(array_column($payments, 'late_fee'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Payment Reports - Rental Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous">
    <link href="css/styles.css" rel="stylesheet"> <!-- Assuming SB Admin styles -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .card {
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border: none;
            border-radius: 8px;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem;
            font-size: 1.25rem;
            font-weight: 500;
        }
        .table {
            margin-bottom: 0;
            width: 100%;
        }
        .table th, .table td {
            vertical-align: middle;
            padding: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .table thead th {
            background-color: #f1f3f5;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }
        .table tbody tr:hover {
            background-color: #f5f5f5;
        }
        .badge {
            padding: 6px 10px;
            font-size: 0.9rem;
        }
        .btn-sm {
            padding: 5px 10px;
        }
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 1.5rem;
        }
        .summary-box {
            background-color: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        @media (max-width: 768px) {
            .table th, .table td {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Payment Reports</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php?page=home">Dashboard</a></li>
                        <li class="breadcrumb-item active">Payment Reports</li>
                    </ol>

                    <!-- Filter Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-filter me-2"></i> Filter Payments
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($filters['start_date']); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($filters['end_date']); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Payment Status</label>
                                    <select name="payment_status" class="form-select">
                                        <option value="">All</option>
                                        <option value="paid" <?php echo $filters['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                        <option value="partial" <?php echo $filters['payment_status'] === 'partial' ? 'selected' : ''; ?>>Partial</option>
                                        <option value="unpaid" <?php echo $filters['payment_status'] === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tenant</label>
                                    <select name="tenant_id" class="form-select">
                                        <option value="">All Tenants</option>
                                        <?php
                                        $tenants = $conn->query("SELECT id, CONCAT(firstname, ' ', COALESCE(middlename, ''), ' ', lastname) AS name FROM tenants ORDER BY name");
                                        while ($tenant = $tenants->fetch_assoc()):
                                        ?>
                                            <option value="<?php echo $tenant['id']; ?>" <?php echo $filters['tenant_id'] == $tenant['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tenant['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> Apply Filters</button>
                                    <button type="button" id="exportCsv" class="btn btn-success"><i class="fas fa-download me-1"></i> Export to CSV</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Summary Section -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="summary-box">
                                <h6>Total Amount</h6>
                                <p class="fs-4 mb-0"><?php echo number_format($total_amount, 2); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="summary-box">
                                <h6>Outstanding Balance</h6>
                                <p class="fs-4 mb-0"><?php echo number_format($total_outstanding, 2); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="summary-box">
                                <h6>Total Late Fees</h6>
                                <p class="fs-4 mb-0"><?php echo number_format($total_late_fee, 2); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Payments Table -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-2"></i> Payment Records
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="paymentsTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Tenant</th>
                                            <th>House No</th>
                                            <th>Amount</th>
                                            <th>Payment Method</th>
                                            <th>Date Paid</th>
                                            <th>Payment Status</th>
                                            <th>Outstanding Balance</th>
                                            <th>Late Fee</th>
                                            <th>Due Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $i = 1;
                                        foreach ($payments as $row):
                                        ?>
                                            <tr>
                                                <td><?php echo $i++; ?></td>
                                                <td><?php echo htmlspecialchars($row['tenant_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($row['house_no'] ?? 'N/A'); ?></td>
                                                <td><?php echo number_format($row['amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($row['payment_method'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($row['date_paid'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $row['payment_status'] === 'paid' ? 'bg-success' : ($row['payment_status'] === 'partial' ? 'bg-info' : 'bg-warning'); ?>">
                                                        <?php echo ucfirst($row['payment_status'] ?? 'unpaid'); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($row['outstanding_balance'], 2); ?></td>
                                                <td><?php echo number_format($row['late_fee'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($row['due_date'] ?? 'N/A'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright © Rental Management System <?php echo date('Y'); ?></div>
                        <div>
                            <a href="#">Privacy Policy</a> · <a href="#">Terms & Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dataTable = new simpleDatatables.DataTable('#paymentsTable', {
                searchable: true,
                fixedHeight: true,
                perPage: 10,
                perPageSelect: [5, 10, 15, 20],
                labels: {
                    placeholder: "Search payments...",
                    perPage: "{select} payments per page",
                    noRows: "No payments found",
                    info: "Showing {start} to {end} of {rows} payments"
                }
            });

            // Export to CSV
            document.getElementById('exportCsv').addEventListener('click', function () {
                const csv = [];
                const headers = Array.from(document.querySelectorAll('#paymentsTable thead th')).map(th => th.textContent);
                csv.push(headers.join(','));

                const rows = document.querySelectorAll('#paymentsTable tbody tr');
                rows.forEach(row => {
                    const cols = Array.from(row.querySelectorAll('td')).map(td => `"${td.textContent.trim().replace(/"/g, '""')}"`);
                    csv.push(cols.join(','));
                });

                const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'payment_report.csv';
                link.click();
            });
        });
    </script>
</body>
</html>