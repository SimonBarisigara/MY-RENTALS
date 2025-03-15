<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include 'db_connect.php';

// Ensure no output before setting headers
ob_start();

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle AJAX request for last payment date
if (isset($_GET['action']) && $_GET['action'] === 'get_last_payment' && isset($_GET['tenant_id'])) {
    header('Content-Type: application/json'); // Set JSON header
    $tenant_id = filter_var($_GET['tenant_id'], FILTER_VALIDATE_INT);
    if (!$tenant_id) {
        echo json_encode(['error' => 'Invalid tenant ID']);
        exit;
    }

    $stmt = $conn->prepare("SELECT period_end FROM payments WHERE tenant_id = ? ORDER BY period_end DESC LIMIT 1");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    echo json_encode(['last_end_date' => $result ? $result['period_end'] : null]);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment'])) {
    header('Content-Type: application/json'); // Set JSON header
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }

    // Sanitize and validate inputs
    $tenant_id = filter_var($_POST['tenant_id'], FILTER_VALIDATE_INT);
    $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
    $payment_method = isset($_POST['payment_method']) ? htmlspecialchars(trim($_POST['payment_method'])) : '';
    $months_paid = filter_var($_POST['months_paid'], FILTER_VALIDATE_INT);
    $late_fee = filter_var($_POST['late_fee'], FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0.00]]);

    if (!$tenant_id || !$amount || !$payment_method || !$months_paid || $months_paid <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Required fields are missing or invalid']);
        exit;
    }

    // Fetch tenant and house details
    $stmt = $conn->prepare("SELECT t.id, t.house_no, t.start_date, h.price FROM tenants t JOIN houses h ON t.house_no = h.house_no WHERE t.id = ? AND t.status = 1");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $tenant = $stmt->get_result()->fetch_assoc();

    if (!$tenant) {
        echo json_encode(['status' => 'error', 'message' => 'Tenant not found or inactive']);
        exit;
    }

    $house_no = $tenant['house_no'];
    $price = $tenant['price'];
    $initial_start_date = $tenant['start_date'];

    // Determine period start date
    $stmt = $conn->prepare("SELECT period_end FROM payments WHERE tenant_id = ? ORDER BY period_end DESC LIMIT 1");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $last_payment = $stmt->get_result()->fetch_assoc();
    $period_start = $last_payment ? date('Y-m-d', strtotime($last_payment['period_end'] . ' +1 day')) : $initial_start_date;

    // Calculate period end date
    $start_date_obj = new DateTime($period_start);
    $start_date_obj->modify("+{$months_paid} months");
    $start_date_obj->modify('-1 day');
    $period_end = $start_date_obj->format('Y-m-d');

    // Calculate total expected amount
    $total_expected = $price * $months_paid;

    // Calculate total paid amount for the period
    $stmt = $conn->prepare("SELECT SUM(amount) AS total_paid FROM payments WHERE tenant_id = ? AND period_start = ?");
    $stmt->bind_param("is", $tenant_id, $period_start);
    $stmt->execute();
    $paid_amount = $stmt->get_result()->fetch_assoc()['total_paid'] ?? 0;

    // Calculate outstanding balance
    $outstanding_balance = $total_expected + $late_fee - ($paid_amount + $amount);
    $payment_status = ($outstanding_balance <= 0) ? 'paid' : ($outstanding_balance < $total_expected ? 'partial' : 'unpaid');

    // Generate unique invoice number
    $invoice = 'INV-' . date('Ymd') . '-' . str_pad($conn->query("SELECT COUNT(*) FROM payments WHERE DATE(created_at) = CURDATE()")->fetch_row()[0] + 1, 3, '0', STR_PAD_LEFT);

    // Insert payment into the database
    $stmt = $conn->prepare("INSERT INTO payments (tenant_id, house_no, amount, invoice, payment_method, outstanding_balance, payment_status, period_start, period_end, late_fee, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisdssdssd", $tenant_id, $house_no, $amount, $invoice, $payment_method, $outstanding_balance, $payment_status, $period_start, $period_end, $late_fee);

    if ($stmt->execute()) {
        // Update tenant's payment status
        $stmt = $conn->prepare("UPDATE tenants SET payment_status = ? WHERE id = ?");
        $stmt->bind_param("si", $payment_status, $tenant_id);
        $stmt->execute();

        echo json_encode(['status' => 'success', 'message' => 'Payment recorded successfully', 'invoice' => $invoice]);
    } else {
        error_log("SQL Error: " . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Failed to record payment: ' . $stmt->error]);
    }
    exit;
}

// Ensure no output before setting headers
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Record Payment - Rental Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container { width: 100% !important; }
        td { vertical-align: middle !important; }
        img { max-width: 100px; max-height: 150px; }
        .alert { margin-top: 20px; }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid">
                    <div class="col-lg-12">
                        <div class="row mb-4 mt-4">
                            <div class="col-md-12">
                                <h1 class="mt-4">Record Payment</h1>
                                <ol class="breadcrumb mb-4">
                                    <li class="breadcrumb-item"><a href="index.php?page=home">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="index.php?page=payments">Payments</a></li>
                                    <li class="breadcrumb-item active">Add Payment</li>
                                </ol>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header"><b>New Payment</b></div>
                                    <div class="card-body">
                                        <div id="alertContainer"></div>
                                        <form id="paymentForm" method="POST" novalidate>
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="save_payment" value="1">

                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6">
                                                    <label for="tenant_id" class="form-label">Tenant <span class="text-danger">*</span></label>
                                                    <select name="tenant_id" id="tenant_id" class="form-select searchable" required onchange="fetchTenantDetails(this.value)">
                                                        <option value="" selected disabled>Select Tenant</option>
                                                        <?php
                                                        $tenants = $conn->query("SELECT t.id, CONCAT(t.lastname, ', ', t.firstname) AS name, t.house_no, t.start_date, h.price FROM tenants t JOIN houses h ON t.house_no = h.house_no WHERE t.status = 1");
                                                        while ($row = $tenants->fetch_assoc()): ?>
                                                            <option value="<?php echo $row['id']; ?>" data-house_no="<?php echo htmlspecialchars($row['house_no']); ?>" data-price="<?php echo $row['price']; ?>" data-start_date="<?php echo $row['start_date']; ?>">
                                                                <?php echo htmlspecialchars($row['name']); ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                    <div class="invalid-feedback">Please select a tenant.</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="house_no" class="form-label">House Number</label>
                                                    <input type="text" name="house_no" id="house_no" class="form-control" readonly>
                                                </div>
                                            </div>

                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6">
                                                    <label for="rent_per_month" class="form-label">Rent Per Month</label>
                                                    <input type="text" id="rent_per_month" class="form-control" readonly>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="amount" class="form-label">Amount Paid <span class="text-danger">*</span></label>
                                                    <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0" required oninput="calculateRentDue()">
                                                    <div class="invalid-feedback">Please enter a valid amount.</div>
                                                </div>
                                            </div>

                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6">
                                                    <label for="months_paid" class="form-label">Months Paid For <span class="text-danger">*</span></label>
                                                    <input type="number" name="months_paid" id="months_paid" class="form-control" min="1" value="1" required oninput="calculatePeriodEnd()">
                                                    <div class="invalid-feedback">Please enter a valid number of months.</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="period_end" class="form-label">Period End Date</label>
                                                    <input type="date" id="period_end" class="form-control" readonly>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="late_fee" class="form-label">Late Fee</label>
                                                    <input type="number" name="late_fee" id="late_fee" class="form-control" step="0.01" min="0" value="0" oninput="calculateRentDue()">
                                            </div>

                                            <div class="mb-3">
                                                <label for="rent_due" class="form-label">Rent Balance</label>
                                                <input type="text" id="rent_due" class="form-control" readonly>
                                            </div>

                                            <div class="d-flex justify-content-end gap-2">
                                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Payment</button>
                                                <a href="index.php?page=payments" class="btn btn-secondary"><i class="fas fa-times me-1"></i> Cancel</a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.searchable').select2({
                placeholder: 'Select Tenant',
                allowClear: true
            });
        });

        let tenantStartDate = null;

        function fetchTenantDetails(tenantId) {
            const selectedOption = document.querySelector(`#tenant_id option[value="${tenantId}"]`);
            if (selectedOption) {
                document.getElementById('house_no').value = selectedOption.dataset.house_no || '';
                document.getElementById('rent_per_month').value = parseFloat(selectedOption.dataset.price || 0).toFixed(2);

                fetch('index.php?page=add_payment&action=get_last_payment&tenant_id=' + tenantId)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            console.error('Server error:', data.error);
                            tenantStartDate = selectedOption.dataset.start_date || null;
                        } else if (data.last_end_date) {
                            const lastEndDate = new Date(data.last_end_date);
                            lastEndDate.setDate(lastEndDate.getDate() + 1);
                            tenantStartDate = lastEndDate.toISOString().split('T')[0];
                        } else {
                            tenantStartDate = selectedOption.dataset.start_date || null;
                        }
                        calculatePeriodEnd();
                        calculateRentDue();
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        tenantStartDate = selectedOption.dataset.start_date || null;
                        calculatePeriodEnd();
                        calculateRentDue();
                    });
            }
        }

        function calculatePeriodEnd() {
            const monthsPaid = parseInt(document.getElementById('months_paid').value) || 0;
            const endDateField = document.getElementById('period_end');
            if (tenantStartDate && monthsPaid > 0) {
                const startDate = new Date(tenantStartDate);
                const endDate = new Date(startDate);
                endDate.setMonth(startDate.getMonth() + monthsPaid);
                endDate.setDate(endDate.getDate() - 1);
                endDateField.value = endDate.toISOString().split('T')[0];
            } else {
                endDateField.value = '';
            }
            calculateRentDue();
        }

        function calculateRentDue() {
            const rentPerMonth = parseFloat(document.getElementById('rent_per_month').value) || 0;
            const monthsPaid = parseInt(document.getElementById('months_paid').value) || 0;
            const amountPaid = parseFloat(document.getElementById('amount').value) || 0;
            const lateFee = parseFloat(document.getElementById('late_fee').value) || 0;
            const totalRent = rentPerMonth * monthsPaid;
            const rentDue = (totalRent + lateFee) - amountPaid;
            document.getElementById('rent_due').value = rentDue.toFixed(2);
        }

        document.getElementById('paymentForm').addEventListener('submit', function (e) {
            e.preventDefault();
            if (this.checkValidity()) {
                const formData = new FormData(this);
                fetch('index.php?page=add_payment', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
                    return response.json();
                })
                .then(data => {
                    const alertContainer = document.getElementById('alertContainer');
                    alertContainer.innerHTML = '';
                    const alert = document.createElement('div');
                    alert.className = `alert alert-${data.status === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
                    alert.innerHTML = `${data.message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
                    alertContainer.appendChild(alert);
                    if (data.status === 'success') {
                        setTimeout(() => location.href = 'index.php?page=payments', 2000);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    const alertContainer = document.getElementById('alertContainer');
                    alertContainer.innerHTML = '';
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger alert-dismissible fade show';
                    alert.innerHTML = `Error: ${error.message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
                    alertContainer.appendChild(alert);
                });
            } else {
                this.classList.add('was-validated');
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('months_paid').value = 1; // Set default value
            calculatePeriodEnd(); // Calculate initial period end
            calculateRentDue();   // Calculate initial rent due
        });
    </script>
</body>
</html>