<?php include('db_connect.php'); ?>

<?php
// Fetch payment details based on the ID from the URL
if (isset($_GET['id'])) {
    $payment_id = $_GET['id'];
    $payment = $conn->query("
        SELECT p.*, 
               CONCAT(t.lastname, ', ', t.firstname, ' ', t.middlename) AS tenant_name,
               h.house_no
        FROM payments p
        INNER JOIN tenants t ON p.tenant_id = t.id
        LEFT JOIN houses h ON t.house_id = h.id
        WHERE p.id = $payment_id
    ")->fetch_assoc();

    if (!$payment) {
        echo "<script>alert('Payment not found!'); window.location='index.php?page=payments';</script>";
        exit();
    }
} else {
    echo "<script>alert('Invalid request!'); window.location='index.php?page=payments';</script>";
    exit();
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold">Payment Details</h4>
        <a href="index.php?page=payments" class="btn btn-secondary">Back to Payments</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Payment Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Tenant Details -->
                <div class="col-md-6">
                    <h6>Tenant Information</h6>
                    <ul class="list-group">
                        <li class="list-group-item">
                            <strong>Tenant Name:</strong> <?php echo ucwords($payment['tenant_name']) ?>
                        </li>
                        <li class="list-group-item">
                            <strong>House Number:</strong> <?php echo $payment['house_no'] ? $payment['house_no'] : 'N/A'; ?>
                        </li>
                    </ul>
                </div>

                <!-- Payment Details -->
                <div class="col-md-6">
                    <h6>Payment Information</h6>
                    <ul class="list-group">
                        <li class="list-group-item">
                            <strong>Amount Paid:</strong> <?php echo number_format($payment['amount'], 2); ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Invoice Number:</strong> <?php echo $payment['invoice']; ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Payment Method:</strong> <?php echo ucwords($payment['payment_method']); ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Date Paid:</strong> <?php echo date("M d, Y H:i:s", strtotime($payment['date_paid'])); ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Outstanding Balance:</strong> <?php echo number_format($payment['outstanding_balance'], 2); ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Late Fee:</strong> <?php echo number_format($payment['late_fee'], 2); ?>
                        </li>
                        <li class="list-group-item">
                            <strong>Payment Status:</strong>
                            <span class="badge bg-<?php echo ($payment['payment_status'] == 'Paid') ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($payment['payment_status']); ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Payment Period -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h6>Payment Period</h6>
                    <ul class="list-group">
                        <li class="list-group-item">
                            <strong>Start Date:</strong> <?php echo date("M d, Y", strtotime($payment['period_start'])); ?>
                        </li>
                        <li class="list-group-item">
                            <strong>End Date:</strong> <?php echo date("M d, Y", strtotime($payment['period_end'])); ?>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <a href="generate_receipt.php?id=<?php echo $payment['id'] ?>" class="btn btn-primary">Generate Receipt</a>
            </div>
        </div>
    </div>
</div>