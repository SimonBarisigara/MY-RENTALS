<?php
// payment_processor.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connect.php';

class PaymentProcessor {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->initializeCsrfToken();
    }
    
    private function initializeCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    
    private function isValidDate($date) {
        return preg_match("/^\d{4}-\d{2}-\d{2}$/", $date) && strtotime($date) <= time();
    }
    
    public function processPayment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['save_payment'])) {
            return;
        }
        
        header('Content-Type: application/json');
        ob_clean();
        
        // CSRF Validation
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $this->sendJsonResponse('error', 'Invalid CSRF token');
            return;
        }
        
        // Input Validation
        $tenant_id = filter_input(INPUT_POST, 'tenant_id', FILTER_VALIDATE_INT);
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        $payment_method = htmlspecialchars(trim($_POST['payment_method'] ?? ''));
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        
        if (!$tenant_id || !$amount || !$payment_method || !$start_date || !$end_date) {
            $this->sendJsonResponse('error', 'All fields are required');
            return;
        }
        
        if (!$this->isValidDate($start_date) || !$this->isValidDate($end_date)) {
            $this->sendJsonResponse('error', 'Invalid or future date provided');
            return;
        }
        
        try {
            $this->conn->begin_transaction();
            
            // Get tenant details
            $tenant = $this->getTenantDetails($tenant_id);
            if (!$tenant) {
                throw new Exception('Tenant not found or inactive');
            }
            
            // Calculate previous payments
            $paid_amount = $this->getPreviousPayments($tenant_id, $start_date, $end_date);
            $outstanding_balance = max(0, $tenant['price'] - ($paid_amount + $amount));
            $payment_status = $outstanding_balance <= 0 ? 'paid' : 'partial';
            
            // Record payment
            $this->insertPayment(
                $tenant_id, 
                $tenant['house_no'], 
                $amount, 
                $payment_method,
                $outstanding_balance, 
                $payment_status, 
                $start_date, 
                $end_date
            );
            
            // Update tenant status
            $this->updateTenantStatus($tenant_id, $payment_status);
            
            $this->conn->commit();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            $this->sendJsonResponse('success', 'Payment recorded successfully', [
                'new_csrf_token' => $_SESSION['csrf_token']
            ]);
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Payment Error: " . $e->getMessage());
            $this->sendJsonResponse('error', 'Failed to record payment: ' . $e->getMessage());
        }
    }
    
    private function getTenantDetails($tenant_id) {
        $stmt = $this->conn->prepare("SELECT t.id, t.house_no, h.price 
                                    FROM tenants t 
                                    JOIN houses h ON t.house_no = h.house_no 
                                    WHERE t.id = ? AND t.status = 1");
        $stmt->bind_param("i", $tenant_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    private function getPreviousPayments($tenant_id, $start_date, $end_date) {
        $stmt = $this->conn->prepare("SELECT SUM(amount) AS total_paid 
                                    FROM payments 
                                    WHERE tenant_id = ? AND period_start = ? AND period_end = ?");
        $stmt->bind_param("iss", $tenant_id, $start_date, $end_date);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total_paid'] ?? 0;
    }
    
    private function insertPayment($tenant_id, $house_no, $amount, $payment_method, 
                                 $outstanding_balance, $payment_status, $start_date, $end_date) {
        $stmt = $this->conn->prepare(
            "INSERT INTO payments (
                tenant_id, house_no, amount, payment_method, 
                outstanding_balance, payment_status, period_start, 
                period_end, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param(
            "iisdssss",
            $tenant_id, $house_no, $amount, $payment_method,
            $outstanding_balance, $payment_status, $start_date, $end_date
        );
        $stmt->execute();
    }
    
    private function updateTenantStatus($tenant_id, $payment_status) {
        $stmt = $this->conn->prepare("UPDATE tenants SET payment_status = ? WHERE id = ?");
        $stmt->bind_param("si", $payment_status, $tenant_id);
        $stmt->execute();
    }
    
    private function sendJsonResponse($status, $message, $additional = []) {
        $response = array_merge(['status' => $status, 'message' => $message], $additional);
        echo json_encode($response);
        exit;
    }
}

$processor = new PaymentProcessor($conn);
$processor->processPayment();
?>

<!-- payment_form.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Management System - Record Payment</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
    <link rel="stylesheet" href="assets/font-awesome/css/all.min.css">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .select2-container { width: 100% !important; }
        .form-control[readonly] { background-color: #e9ecef; }
        .card { box-shadow: 0 4px 8px rgba(0,0,0,0.1); border: none; }
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 1050; }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Record Payment</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php?page=home">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="index.php?page=payments">Payments</a></li>
                        <li class="breadcrumb-item active">Add Payment</li>
                    </ol>

                    <div class="card mb-4">
                        <div class="card-header bg-dark text-white">
                            <i class="fas fa-file-invoice me-2"></i> New Payment
                        </div>
                        <div class="card-body">
                            <form id="paymentForm" method="POST" action="payment_processor.php" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="save_payment" value="1">

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="tenant_id" class="form-label">Tenant <span class="text-danger">*</span></label>
                                        <select name="tenant_id" id="tenant_id" class="form-select select2" required>
                                            <option value="" disabled selected>Select Tenant</option>
                                            <?php
                                            $tenants = $conn->query("SELECT t.id, CONCAT(t.lastname, ', ', t.firstname) AS name, 
                                                                    t.house_no, h.price 
                                                                    FROM tenants t 
                                                                    JOIN houses h ON t.house_no = h.house_no 
                                                                    WHERE t.status = 1");
                                            while ($row = $tenants->fetch_assoc()):
                                            ?>
                                                <option value="<?php echo $row['id']; ?>" 
                                                        data-house="<?php echo htmlspecialchars($row['house_no']); ?>" 
                                                        data-price="<?php echo $row['price']; ?>">
                                                    <?php echo htmlspecialchars($row['name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select a tenant.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="house_no" class="form-label">House Number</label>
                                        <input type="text" id="house_no" class="form-control" readonly>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="rent_per_month" class="form-label">Rent Per Month</label>
                                        <input type="number" id="rent_per_month" class="form-control" step="0.01" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="amount" class="form-label">Amount Paid <span class="text-danger">*</span></label>
                                        <input type="number" name="amount" id="amount" class="form-control" 
                                               step="0.01" min="0" required>
                                        <div class="invalid-feedback">Please enter a valid amount.</div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                        <input type="date" name="start_date" id="start_date" class="form-control" required>
                                        <div class="invalid-feedback">Please select a start date.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                        <input type="date" name="end_date" id="end_date" class="form-control" required>
                                        <div class="invalid-feedback">Please select an end date.</div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                    <select name="payment_method" id="payment_method" class="form-select select2" required>
                                        <option value="" disabled selected>Select Method</option>
                                        <option value="cash">Cash</option>
                                        <option value="mobile_money">Mobile Money</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a payment method.</div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Save Payment
                                    </button>
                                    <a href="index.php?page=payments" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </a>
                                </div>
                            </form>
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

    <div class="toast-container">
        <div id="toastNotification" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Select an option",
                allowClear: true
            });

            $('#tenant_id').on('change', function() {
                const option = $(this).find('option:selected');
                $('#house_no').val(option.data('house') || '');
                $('#rent_per_month').val(parseFloat(option.data('price') || 0).toFixed(2));
            });

            $('#paymentForm').on('submit', function(e) {
                e.preventDefault();
                
                if (!this.checkValidity()) {
                    this.classList.add('was-validated');
                    return;
                }

                const toast = new bootstrap.Toast($('#toastNotification')[0]);
                const toastBody = $('#toastNotification .toast-body');
                const toastHeader = $('#toastNotification .toast-header');

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: new FormData(this),
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(response) {
                        toastHeader.removeClass('bg-danger bg-success')
                            .addClass(response.status === 'success' ? 'bg-success' : 'bg-danger')
                            .find('strong').text(response.status === 'success' ? 'Success' : 'Error');
                        toastBody.text(response.message);
                        toast.show();

                        if (response.status === 'success') {
                            $('input[name="csrf_token"]').val(response.new_csrf_token);
                            setTimeout(() => location.href = 'index.php?page=payments', 2000);
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON || {message: 'An unexpected error occurred'};
                        toastHeader.removeClass('bg-success').addClass('bg-danger')
                            .find('strong').text('Error');
                        toastBody.text(response.message);
                        toast.show();
                    }
                });
            });
        });
    </script>
</body>
</html>