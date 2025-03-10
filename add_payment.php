<?php include('db_connect.php'); ?>

<?php
if (isset($_POST['save_payment'])) {
    $tenant_id = $_POST['tenant_id'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $currency = $_POST['currency'];
    $transaction_id = $_POST['transaction_id'];
    $bank_name = $_POST['bank_name'];
    $reference_number = $_POST['reference_number'];
    $collector_name = $_POST['collector_name'];
    $house_no = $_POST['house_no']; // Fetch house_no from the form
    $billing_cycle_id = $_POST['billing_cycle_id']; // Fetch billing cycle ID
    $late_fee = $_POST['late_fee']; // Fetch late fee from the form

    // Fetch billing cycle details
    $billing_cycle = $conn->query("SELECT start_date, end_date FROM billing_cycles WHERE id = $billing_cycle_id")->fetch_assoc();
    $period_start = $billing_cycle['start_date'];
    $period_end = $billing_cycle['end_date'];

    // Fetch tenant details
    $tenant = $conn->query("SELECT id, house_no, price FROM tenants WHERE id = $tenant_id")->fetch_assoc();
    $price = $tenant['price'];

    // Calculate total paid amount
    $paid_amount = $conn->query("SELECT SUM(amount) AS total_paid FROM payments WHERE tenant_id = $tenant_id")->fetch_assoc()['total_paid'];

    // Calculate outstanding balance
    $outstanding_balance = $price - ($paid_amount + $amount);
    $payment_status = ($outstanding_balance <= 0) ? 'paid' : 'partial';

    // Generate invoice number
    $invoice = 'INV-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);

    // Insert payment
    $stmt = $conn->prepare("INSERT INTO payments (tenant_id, house_id, amount, invoice, payment_method, currency, transaction_id, bank_name, reference_number, collector_name, outstanding_balance, payment_status, period_start, period_end, late_fee, billing_cycle_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iidsssssssssssdi", $tenant_id, $house_no, $amount, $invoice, $payment_method, $currency, $transaction_id, $bank_name, $reference_number, $collector_name, $outstanding_balance, $payment_status, $period_start, $period_end, $late_fee, $billing_cycle_id);
    $stmt->execute();

    // Update tenant payment status (without updating rent_due)
    $conn->query("UPDATE tenants SET payment_status = '$payment_status' WHERE id = $tenant_id");

    echo "<script>alert('Payment recorded successfully!'); window.location='index.php?page=payments';</script>";
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold">New Payment</h4>
        <a href="index.php?page=payments" class="btn btn-secondary">Back to Payments</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Record Payment</h5>
        </div>
        <div class="card-body">
            <form method="POST" id="paymentForm">
                <!-- Tenant and House Details -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Tenant</label>
                        <div class="input-group">
                            <select name="tenant_id" id="tenant_id" class="form-select" required onchange="fetchTenantDetails(this.value)">
                                <option value="" selected disabled>Select Tenant</option>
                                <?php 
                                $tenants = $conn->query("SELECT id, CONCAT(lastname, ', ', firstname) AS name, house_no, price FROM tenants WHERE status = 1");
                                while ($row = $tenants->fetch_assoc()): ?>
                                    <option value="<?php echo $row['id'] ?>" data-house_no="<?php echo $row['house_no'] ?>" data-price="<?php echo $row['price'] ?>">
                                        <?php echo $row['name'] ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <a href="index.php?page=add_tenant" class="btn btn-outline-primary"><i class="fa fa-plus"></i></a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">House Number</label>
                        <input type="text" name="house_no" id="house_no" class="form-control" readonly>
                    </div>
                </div>

                <!-- Rent Details -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Rent Per Month</label>
                        <input type="text" name="rent_per_month" id="rent_per_month" class="form-control" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Amount Paid</label>
                        <input type="number" name="amount" class="form-control" required min="0">
                    </div>
                </div>

                <!-- Billing Cycle -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Billing Cycle <span class="text-danger">*</span></label>
                        <select name="billing_cycle_id" id="billing_cycle_id" class="form-select" required onchange="fetchBillingCycleDates(this.value)">
                            <option value="" selected disabled>Select Billing Cycle</option>
                            <?php 
                            $billing_cycles = $conn->query("SELECT * FROM billing_cycles ORDER BY start_date DESC");
                            while ($row = $billing_cycles->fetch_assoc()): ?>
                                <option value="<?php echo $row['id'] ?>" data-start_date="<?php echo $row['start_date'] ?>" data-end_date="<?php echo $row['end_date'] ?>">
                                    <?php echo $row['cycle_name'] ?> (<?php echo $row['start_date'] ?> to <?php echo $row['end_date'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rent Due</label>
                        <input type="text" name="rent_due" id="rent_due" class="form-control" readonly>
                    </div>
                </div>

                <!-- Payment Period (Auto-filled based on billing cycle) -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Start Date</label>
                        <input type="text" name="period_start" id="period_start" class="form-control" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">End Date</label>
                        <input type="text" name="period_end" id="period_end" class="form-control" readonly>
                    </div>
                </div>

                <!-- Late Fee -->
                <div class="mb-3">
                    <label class="form-label">Late Fee</label>
                    <input type="number" name="late_fee" id="late_fee" class="form-control" min="0" value="0">
                </div>

                <!-- Payment Method -->
                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" id="payment_method" class="form-select" required>
                        <option value="cash">Cash</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>

                <!-- Payment Details (Dynamic Fields) -->
                <div id="payment_details">
                    <!-- Transaction ID (for Mobile Money) -->
                    <div class="mb-3" id="transaction_id_field" style="display: none;">
                        <label class="form-label">Transaction ID</label>
                        <input type="text" name="transaction_id" class="form-control">
                    </div>

                    <!-- Bank Name and Reference Number (for Bank Transfer) -->
                    <div class="mb-3" id="bank_fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Bank Name</label>
                                <input type="text" name="bank_name" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reference Number</label>
                                <input type="text" name="reference_number" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Collector Name (for Cash) -->
                    <div class="mb-3" id="collector_name_field" style="display: none;">
                        <label class="form-label">Collector Name</label>
                        <input type="text" name="collector_name" class="form-control">
                    </div>
                </div>

                <!-- Currency -->
                <div class="mb-3">
                    <label class="form-label">Currency</label>
                    <select name="currency" class="form-select" required>
                        <option value="UGX">UGX</option>
                        <option value="USD">USD</option>
                    </select>
                </div>

                <!-- Form Actions -->
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" name="save_payment" class="btn btn-primary">Save Payment</button>
                    <a href="index.php?page=payments" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Fetch tenant details when a tenant is selected
    function fetchTenantDetails(tenantId) {
        const selectedOption = document.querySelector(`#tenant_id option[value="${tenantId}"]`);
        const houseNo = selectedOption.getAttribute('data-house_no');
        const rentPerMonth = selectedOption.getAttribute('data-price');

        // Display house_no and rent_per_month
        document.getElementById('house_no').value = houseNo;
        document.getElementById('rent_per_month').value = rentPerMonth;

        // Recalculate rent due
        calculateRentDue();
    }

    // Fetch billing cycle dates when a billing cycle is selected
    function fetchBillingCycleDates(billingCycleId) {
        const selectedOption = document.querySelector(`#billing_cycle_id option[value="${billingCycleId}"]`);
        const startDate = selectedOption.getAttribute('data-start_date');
        const endDate = selectedOption.getAttribute('data-end_date');

        // Display start and end dates
        document.getElementById('period_start').value = startDate;
        document.getElementById('period_end').value = endDate;

        // Recalculate rent due
        calculateRentDue();
    }

    // Calculate rent due based on rent per month and billing cycle
    function calculateRentDue() {
        const rentPerMonth = parseFloat(document.getElementById('rent_per_month').value);
        const startDate = document.getElementById('period_start').value;
        const endDate = document.getElementById('period_end').value;

        if (!isNaN(rentPerMonth) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)); // Calculate days between dates
            const rentDue = (rentPerMonth / 30) * days; // Calculate rent due
            document.getElementById('rent_due').value = rentDue.toFixed(2); // Display rent due
        }
    }

    // Show/hide fields based on payment method
    document.getElementById('payment_method').addEventListener('change', function () {
        const paymentMethod = this.value;
        document.getElementById('transaction_id_field').style.display = paymentMethod === 'mobile_money' ? 'block' : 'none';
        document.getElementById('bank_fields').style.display = paymentMethod === 'bank_transfer' ? 'block' : 'none';
        document.getElementById('collector_name_field').style.display = paymentMethod === 'cash' ? 'block' : 'none';
    });

    // Initialize visibility on page load
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('payment_method').dispatchEvent(new Event('change'));
    });
</script>