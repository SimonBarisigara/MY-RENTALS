<?php
include 'db_connect.php';


// Handle fine application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_fine'])) {
    $tenant_id = intval($_POST['tenant_id']);
    $fine_amount = floatval($_POST['fine_amount']);
    $fine_reason = mysqli_real_escape_string($conn, $_POST['fine_reason']);
    $house_no = mysqli_real_escape_string($conn, $_POST['house_no'] ?? 'N/A');

    // Insert fine into payments table (assuming a new payment record for fines)
    $stmt = $conn->prepare("INSERT INTO payments (tenant_id, house_no, amount, payment_status, fines, payment_notes, created_at, updated_at) 
                            VALUES (?, ?, 0.00, 'unpaid', ?, ?, NOW(), NOW())");
    $stmt->bind_param("isds", $tenant_id, $house_no, $fine_amount, $fine_reason);
    if ($stmt->execute()) {
        $msg = '<div class="alert alert-success alert-dismissible fade show" role="alert">✅ Fine recorded successfully.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    } else {
        $msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">❌ Failed to record fine.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    $stmt->close();
}

// Fetch defaulters
$sql = "SELECT 
    p.id, p.tenant_id, p.house_no, p.amount, p.payment_status, p.outstanding_balance, p.fines, p.due_date, p.payment_notes,
    CONCAT(t.firstname, ' ', COALESCE(t.middlename, ''), ' ', t.lastname) AS tenant_name,
    DATEDIFF(CURDATE(), p.due_date) AS days_overdue
FROM payments p
LEFT JOIN tenants t ON p.tenant_id = t.id
WHERE p.due_date < CURDATE() AND p.payment_status != 'paid' AND p.outstanding_balance > 0
ORDER BY p.due_date ASC";
$defaulters = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$total_outstanding = array_sum(array_column($defaulters, 'outstanding_balance'));
$total_fines = array_sum(array_column($defaulters, 'fines'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Manage Defaulters - Rental Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Arial', sans-serif; }
        .card { box-shadow: 0 2px 5px rgba(0,0,0,0.1); border: none; border-radius: 8px; }
        .card-header { background-color: #fff; border-bottom: 1px solid #e9ecef; padding: 1rem; font-size: 1.25rem; font-weight: 500; }
        .table { margin-bottom: 0; width: 100%; }
        .table th, .table td { vertical-align: middle; padding: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .table thead th { background-color: #f1f3f5; border-bottom: 2px solid #dee2e6; font-weight: 600; }
        .table tbody tr:hover { background-color: #f5f5f5; }
        .badge { padding: 6px 10px; font-size: 0.9rem; }
        .btn-sm { padding: 5px 10px; }
        .breadcrumb { background-color: transparent; padding: 0; margin-bottom: 1.5rem; }
        .summary-box { background-color: #fff; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; text-align: center; }
        @media (max-width: 768px) { .table th, .table td { font-size: 0.9rem; } }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Manage Defaulters</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php?page=home">Dashboard</a></li>
                        <li class="breadcrumb-item active">Manage Defaulters</li>
                    </ol>

                    <!-- Messages -->
                    <?php if (isset($msg)) echo $msg; ?>

                    <!-- Record Fine Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-gavel me-2"></i> Record a Fine
                        </div>
                        <div class="card-body">
                            <form method="POST" class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Tenant <span class="text-danger">*</span></label>
                                    <select name="tenant_id" class="form-select" required>
                                        <option value="">Select Tenant</option>
                                        <?php
                                        $tenants = $conn->query("SELECT id, CONCAT(firstname, ' ', COALESCE(middlename, ''), ' ', lastname) AS name, house_no FROM tenants ORDER BY name");
                                        while ($tenant = $tenants->fetch_assoc()):
                                        ?>
                                            <option value="<?php echo $tenant['id']; ?>" data-house-no="<?php echo htmlspecialchars($tenant['house_no'] ?? 'N/A'); ?>">
                                                <?php echo htmlspecialchars($tenant['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">House No</label>
                                    <input type="text" name="house_no" class="form-control" readonly id="house_no">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Fine Amount <span class="text-danger">*</span></label>
                                    <input type="number" name="fine_amount" class="form-control" step="0.01" min="0" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                                    <textarea name="fine_reason" class="form-control" rows="1" required placeholder="e.g., Violation of terms"></textarea>
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" name="apply_fine" class="btn btn-danger">
                                        <i class="fas fa-plus me-1"></i> Record Fine
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Summary Section -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="summary-box">
                                <h6>Total Outstanding Balance</h6>
                                <p class="fs-4 mb-0"><?php echo number_format($total_outstanding, 2); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="summary-box">
                                <h6>Total Fines</h6>
                                <p class="fs-4 mb-0"><?php echo number_format($total_fines, 2); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Defaulters Table -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-exclamation-triangle me-2"></i> Defaulters List
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="defaultersTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Tenant</th>
                                            <th>House No</th>
                                            <th>Amount Due</th>
                                            <th>Outstanding</th>
                                            <th>Fines</th>
                                            <th>Due Date</th>
                                            <th>Days Overdue</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $i = 1;
                                        foreach ($defaulters as $row):
                                        ?>
                                            <tr>
                                                <td><?php echo $i++; ?></td>
                                                <td><?php echo htmlspecialchars($row['tenant_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($row['house_no'] ?? 'N/A'); ?></td>
                                                <td><?php echo number_format($row['amount'], 2); ?></td>
                                                <td><?php echo number_format($row['outstanding_balance'], 2); ?></td>
                                                <td><?php echo number_format($row['fines'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                                                <td><?php echo $row['days_overdue'] > 0 ? $row['days_overdue'] : 0; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $row['payment_status'] === 'partial' ? 'bg-info' : 'bg-warning'; ?>">
                                                        <?php echo ucfirst($row['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="index.php?page=payments&tenant_id=<?php echo $row['tenant_id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
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
            const dataTable = new simpleDatatables.DataTable('#defaultersTable', {
                searchable: true,
                fixedHeight: true,
                perPage: 10,
                perPageSelect: [5, 10, 15, 20],
                labels: {
                    placeholder: "Search defaulters...",
                    perPage: "{select} defaulters per page",
                    noRows: "No defaulters found",
                    info: "Showing {start} to {end} of {rows} defaulters"
                }
            });

            // Update house number based on tenant selection
            $('select[name="tenant_id"]').on('change', function () {
                const houseNo = $(this).find(':selected').data('house-no');
                $('#house_no').val(houseNo || 'N/A');
            });
        });
    </script>
</body>
</html>