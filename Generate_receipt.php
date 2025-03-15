<?php
include('db_connect.php');

// Fetch payment details
if (isset($_GET['id'])) {
    $payment_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if (!$payment_id) {
        die("Invalid payment ID!");
    }

    $stmt = $conn->prepare("
        SELECT p.*, 
               CONCAT(t.lastname, ', ', t.firstname, ' ', COALESCE(t.middlename, '')) AS tenant_name,
               h.house_no
        FROM payments p
        INNER JOIN tenants t ON p.tenant_id = t.id
        LEFT JOIN houses h ON t.house_no = h.house_no
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if (!$payment) {
        die("Payment not found!");
    }
} else {
    die("Invalid request!");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Payment Receipt - Rental Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous">
    <link href="css/styles.css" rel="stylesheet"> <!-- Assuming SB Admin styles -->
    <style>
        .receipt-container { max-width: 600px; margin: 0 auto; padding: 2rem; background: white; border-radius: 15px; box-shadow: 0 8px 30px rgba(0,0,0,0.1); }
        .receipt-header { text-align: center; padding-bottom: 1.5rem; border-bottom: 2px solid #e9ecef; }
        .receipt-header img { max-width: 120px; margin-bottom: 1rem; }
        .receipt-details { padding: 1.5rem 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px dashed #dee2e6; }
        .detail-label { font-weight: 600; color: #495057; }
        .detail-value { color: #212529; }
        .status-badge { font-size: 0.9rem; padding: 0.5em 1em; }
        .print-btn { margin-top: 2rem; }
        @media print {
            .print-btn, .no-print { display: none; }
            .receipt-container { box-shadow: none; margin: 0; padding: 0; width: 100%; }
            body { margin: 0; }
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'navbar.php'; // Assuming SB Admin navbar and sidebar ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4 no-print">
                    <h1 class="mt-4">Payment Receipt</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php?page=home">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="index.php?page=payments">Payments</a></li>
                        <li class="breadcrumb-item active">Receipt</li>
                    </ol>
                </div>

                <div class="receipt-container">
                    <div class="receipt-header">
                        <!-- Replace with your logo -->
                        <h2 class="fw-bold text-dark">Payment Receipt</h2>
                        <p class="text-muted mb-0">Rental Management System</p>
                    </div>

                    <div class="receipt-details">
                        <div class="detail-row">
                            <span class="detail-label">Invoice Number:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($payment['invoice']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Tenant Name:</span>
                            <span class="detail-value"><?php echo ucwords(htmlspecialchars($payment['tenant_name'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">House Number:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($payment['house_no'] ?: 'N/A'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Amount Paid:</span>
                            <span class="detail-value"><?php echo number_format($payment['amount'], 2); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Payment Method:</span>
                            <span class="detail-value"><?php echo ucwords(htmlspecialchars($payment['payment_method'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Date Paid:</span>
                            <span class="detail-value"><?php echo date("M d, Y H:i", strtotime($payment['created_at'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Outstanding Balance:</span>
                            <span class="detail-value"><?php echo number_format($payment['outstanding_balance'], 2); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Late Fee:</span>
                            <span class="detail-value"><?php echo number_format($payment['late_fee'], 2); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Payment Status:</span>
                            <span class="detail-value">
                                <span class="badge bg-<?php echo $payment['payment_status'] === 'paid' ? 'success' : ($payment['payment_status'] === 'partial' ? 'warning' : 'danger'); ?>">
                                    <?php echo ucfirst(htmlspecialchars($payment['payment_status'])); ?>
                                </span>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Payment Period:</span>
                            <span class="detail-value">
                                <?php echo date("M d, Y", strtotime($payment['period_start'])) . " - " . date("M d, Y", strtotime($payment['period_end'])); ?>
                            </span>
                        </div>
                    </div>

                    <div class="text-center print-btn no-print">
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i> Print Receipt
                        </button>
                        <a href="index.php?page=payments" class="btn btn-secondary ms-2">
                            <i class="fas fa-arrow-left me-2"></i> Back to Payments
                        </a>
                    </div>
                </div>
            </main>
            <footer class="py-4 bg-light mt-auto no-print">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script> <!-- Assuming SB Admin scripts -->
</body>
</html>