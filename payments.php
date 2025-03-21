<?php
include 'db_connect.php';

// Filter variables
$date_filter = isset($_POST['date_filter']) ? $_POST['date_filter'] : null;
$status_filter = isset($_POST['status_filter']) ? $_POST['status_filter'] : 'all';

// Build the payments query with filters
$query = "
    SELECT p.id, p.tenant_id, p.amount, p.payment_method, 
           p.date_paid, p.payment_status, p.outstanding_balance, p.late_fee,
           CONCAT(t.lastname, ', ', t.firstname, ' ', COALESCE(t.middlename, '')) AS tenant_name,
           h.house_no
    FROM payments p
    INNER JOIN tenants t ON p.tenant_id = t.id
    LEFT JOIN houses h ON t.house_no = h.house_no
    WHERE 1=1";

if ($date_filter) {
    $query .= " AND DATE(p.date_paid) = ?";
}
if ($status_filter !== 'all') {
    $query .= " AND p.payment_status = ?";
}
$query .= " ORDER BY p.date_paid DESC";

if ($date_filter || $status_filter !== 'all') {
    $stmt = $conn->prepare($query);
    if ($date_filter && $status_filter !== 'all') {
        $stmt->bind_param("ss", $date_filter, $status_filter);
    } elseif ($date_filter) {
        $stmt->bind_param("s", $date_filter);
    } else {
        $stmt->bind_param("s", $status_filter);
    }
    $stmt->execute();
    $payments = $stmt->get_result();
} else {
    $payments = $conn->query($query);
}

// Total payments summary
$total_paid = $conn->query("SELECT SUM(amount) FROM payments WHERE payment_status = 'paid'")->fetch_row()[0] ?? 0;
$total_outstanding = $conn->query("SELECT SUM(outstanding_balance) FROM payments WHERE payment_status = 'pending'")->fetch_row()[0] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Rental Management System - Payments" />
    <meta name="author" content="" />
    <title>Payments - Rental Management</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous" />
    <link href="css/styles.css" rel="stylesheet" /> <!-- Assuming SB Admin styles -->
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .card-hover:hover { transform: scale(1.03); transition: transform 0.2s ease; }
        .filter-form { display: flex; gap: 10px; align-items: center; }
        .table-responsive { max-height: 500px; overflow-y: auto; }
        .badge { font-size: 0.9em; }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'navbar.php'; // Assuming navbar.php exists for SB Admin layout ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Rent Payments</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php?page=home">Dashboard</a></li>
                        <li class="breadcrumb-item active">Payments</li>
                    </ol>

                    <!-- Filters and Add Payment -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div><i class="fas fa-filter me-1"></i> Filter Payments</div>
                            <a class="btn btn-primary" href="index.php?page=add_payment"><i class="fas fa-plus me-1"></i> Add Payment</a>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="filter-form">
                                <div class="mb-3">
                                    <label for="date_filter" class="form-label">Date</label>
                                    <input type="date" id="date_filter" name="date_filter" value="<?php echo htmlspecialchars($date_filter ?? ''); ?>" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label for="status_filter" class="form-label">Status</label>
                                    <select id="status_filter" name="status_filter" class="form-select">
                                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                                        <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary mt-4">Filter</button>
                                <a href="index.php?page=payments" class="btn btn-secondary mt-4">Clear</a>
                            </form>
                        </div>
                    </div>

                    <!-- Payments Table -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i> List of Payments
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="paymentsTable" class="table table-bordered table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Tenant</th>
                                            <th>House #</th>
                                            <th>Amount</th>
                                            <th>Outstanding</th>
                                            <th>Status</th>
                                            <th>Date Paid</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $i = 1;
                                        while ($row = $payments->fetch_assoc()):
                                        ?>
                                            <tr>
                                                <td><?php echo $i++; ?></td>
                                                <td><?php echo ucwords(htmlspecialchars($row['tenant_name'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['house_no'] ?: 'N/A'); ?></td>
                                                <td class="text-end"><?php echo number_format($row['amount'], 2); ?></td>
                                                <td class="text-end"><?php echo number_format($row['outstanding_balance'], 2); ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-<?php echo $row['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($row['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d M Y', strtotime($row['date_paid'])); ?></td>
                                                <td class="text-center">
                                                    <div class="btn-group">
                                                        <a href="index.php?page=view_payment&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                        <a href="generate_receipt.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-secondary">Receipt</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script> <!-- Assuming SB Admin scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dataTable = new simpleDatatables.DataTable('#paymentsTable', {
                searchable: true,
                perPageSelect: [10, 25, 50, 100],
                perPage: 10,
                labels: {
                    placeholder: "Search payments...",
                    perPage: "{select} entries per page",
                    noRows: "No payments found",
                    info: "Showing {start} to {end} of {rows} entries"
                },
                columns: [
                    { select: 0, sort: "asc" }, // Sort by # column
                    { select: 6, sort: "desc" } // Sort by Date Paid
                ]
            });

            // Sidebar toggle (from SB Admin)
            const sidebarToggle = document.getElementById('sidebarToggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', event => {
                    event.preventDefault();
                    document.body.classList.toggle('sb-sidenav-toggled');
                    localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
                });
            }
        });
    </script>
</body>
</html>