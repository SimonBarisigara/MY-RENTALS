<?php
include 'db_connect.php';
// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle AJAX POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_tenant') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM tenants WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Tenant deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete tenant: ' . $conn->error]);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Tenant Management - Rental Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.datatables.net/2.2.2/css/dataTables.bootstrap5.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.css" rel="stylesheet">
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
            white-space: nowrap; /* Prevent text wrapping */
            overflow: hidden;
            text-overflow: ellipsis; /* Truncate long text with ellipsis */
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
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 1.5rem;
        }
        /* Ensure table fits within container */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin: 10px 0;
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
                    <h1 class="mt-4">Tenant Management</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php?page=home">Dashboard</a></li>
                        <li class="breadcrumb-item active">Tenants</li>
                    </ol>

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-user-friends me-2"></i> List of Tenants</span>
                            <a class="btn btn-primary btn-sm" href="index.php?page=add_tenant" id="new_tenant">
                                <i class="fa fa-plus me-1"></i> New Tenant
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="tenantTable">
                                    <thead>
                                        <tr>
                                            <th class="text-center">#</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Contact</th>
                                            <th>Room No</th>
                                            <th>Price</th>
                                            <th>Start Date</th>
                                            <th>Billing Cycle</th>
                                            <th>Payment Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $i = 1;
                                        $tenant = $conn->query("SELECT 
                                            id, 
                                            CONCAT(firstname, ' ', COALESCE(middlename, ''), ' ', lastname) AS name, 
                                            email, 
                                            contact, 
                                            COALESCE(house_no, 'N/A') AS room_no, 
                                            price, 
                                            start_date, 
                                            billing_cycle_type, 
                                            billing_cycle_days, 
                                            payment_status 
                                        FROM tenants 
                                        ORDER BY house_no DESC");
                                        if ($tenant->num_rows > 0) {
                                            while ($row = $tenant->fetch_assoc()) {
                                                $payment_status = ucfirst($row['payment_status'] ?? 'unpaid');
                                                $badge_class = $payment_status === 'Paid' ? 'bg-success' : 
                                                              ($payment_status === 'Partial' ? 'bg-info' : 'bg-warning');
                                                $billing_cycle = ucfirst($row['billing_cycle_type']) . 
                                                                ($row['billing_cycle_type'] === 'custom' && $row['billing_cycle_days'] ? " ({$row['billing_cycle_days']} days)" : '');
                                        ?>
                                            <tr>
                                                <td class="text-center"><?php echo $i++; ?></td>
                                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($row['contact']); ?></td>
                                                <td><b><?php echo htmlspecialchars($row['room_no']); ?></b></td>
                                                <td><?php echo number_format($row['price'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($row['start_date']); ?></td>
                                                <td><?php echo htmlspecialchars($billing_cycle); ?></td>
                                                <td><span class="badge <?php echo $badge_class; ?>"><?php echo $payment_status; ?></span></td>
                                                <td class="text-center">
                                                    <a href="index.php?page=add_tenant&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                                    <button class="btn btn-sm btn-danger delete_tenant" data-id="<?php echo $row['id']; ?>" title="Delete"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        <?php
                                            }
                                        } else {
                                        ?>
                                            <tr>
                                                <td colspan="10" class="text-center">No tenants found.</td>
                                            </tr>
                                        <?php } ?>
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

    <!-- Toast Container -->
    <div class="toast-container"></div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.bootstrap5.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.js"></script>
    <script src="js/scripts.js"></script> <!-- Assuming SB Admin scripts -->
    <script>
        $(document).ready(function() {
            // Initialize DataTable with Responsive extension
            $('#tenantTable').DataTable({
                responsive: true, // Enable responsive behavior
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                columnDefs: [
                    { orderable: false, targets: 9 }, // Disable sorting on Action column
                    { responsivePriority: 1, targets: 0 }, // High priority for # column
                    { responsivePriority: 2, targets: 1 }, // High priority for Name column
                    { responsivePriority: 3, targets: 9 }  // High priority for Action column
                ]
            });

            // Delete tenant with toast alerts
            $('.delete_tenant').click(function() {
                if (confirm("Are you sure you want to delete this tenant?")) {
                    const tenantId = $(this).data('id');
                    const $toastContainer = $('.toast-container');
                    const $toast = $('<div class="toast" role="alert" aria-live="assertive" aria-atomic="true"></div>')
                        .append('<div class="toast-header"></div>')
                        .append('<div class="toast-body"></div>');

                    $.ajax({
                        url: '',
                        method: 'POST',
                        data: {
                            action: 'delete_tenant',
                            id: tenantId,
                            csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                        },
                        dataType: 'json',
                        success: function(resp) {
                            $toast.find('.toast-header')
                                .addClass(resp.status === 'success' ? 'bg-success text-white' : 'bg-danger text-white')
                                .html(`<strong class="me-auto">${resp.status === 'success' ? 'Success' : 'Error'}</strong>
                                       <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>`);
                            $toast.find('.toast-body').text(resp.message);
                            $toastContainer.append($toast);
                            const toast = new bootstrap.Toast($toast[0], { delay: resp.status === 'success' ? 1500 : 5000 });
                            toast.show();

                            if (resp.status === 'success') {
                                setTimeout(() => location.reload(), 1500);
                            }
                        },
                        error: function(xhr) {
                            const errorMessage = xhr.responseJSON?.message || 'An unexpected error occurred';
                            $toast.find('.toast-header')
                                .addClass('bg-danger text-white')
                                .html('<strong class="me-auto">Error</strong><button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>');
                            $toast.find('.toast-body').text('Error: ' + errorMessage);
                            $toastContainer.append($toast);
                            const toast = new bootstrap.Toast($toast[0], { delay: 5000 });
                            toast.show();
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>