<?php
include('db_connect.php');

$msg = "";
$tenant = [
    'id' => '',
    'firstname' => '',
    'middlename' => '',
    'lastname' => '',
    'email' => '',
    'contact' => '',
    'country_code' => '',
    'house_no' => '',
    'price' => '',
    'start_date' => '',
    'billing_cycle_type' => '',
    'billing_cycle_days' => ''
];

// Fetch all available houses (houses without tenants)
$houses = $conn->query("SELECT h.id, h.house_no, h.price FROM houses h LEFT JOIN tenants t ON h.id = t.house_no WHERE t.house_no IS NULL")->fetch_all(MYSQLI_ASSOC);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_tenant'])) {
    $firstname = mysqli_real_escape_string($conn, trim($_POST['firstname']));
    $middlename = mysqli_real_escape_string($conn, trim($_POST['middlename']));
    $lastname = mysqli_real_escape_string($conn, trim($_POST['lastname']));
    $email = !empty($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : null;
    $country_code = mysqli_real_escape_string($conn, trim($_POST['country_code']));
    $contact = mysqli_real_escape_string($conn, trim($_POST['contact']));
    $full_contact = $country_code . $contact;
    $house_id = intval($_POST['house_id']);
    $start_date = $_POST['start_date'];
    $billing_cycle_type = mysqli_real_escape_string($conn, trim($_POST['billing_cycle_type']));
    $billing_cycle_days = ($billing_cycle_type === 'custom' && !empty($_POST['billing_cycle_days'])) ? intval($_POST['billing_cycle_days']) : null;

    // Fetch house details
    $stmt = $conn->prepare("SELECT house_no, price FROM houses WHERE id = ?");
    $stmt->bind_param("i", $house_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $house = $result->fetch_assoc();
        $house_no = $house['house_no'];
        $price = $house['price'];
    } else {
        $msg = '<div class="alert alert-danger">❌ Invalid house selected. Please select a valid house.</div>';
        $house_no = '';
        $price = 0;
    }

    // Check for duplicate tenant (only if email or contact is provided)
    $duplicateCheckQuery = "SELECT * FROM tenants WHERE contact = ?";
    if ($email) {
        $duplicateCheckQuery .= " OR email = ?";
    }
    $stmt = $conn->prepare($duplicateCheckQuery);
    if ($email) {
        $stmt->bind_param("ss", $full_contact, $email);
    } else {
        $stmt->bind_param("s", $full_contact);
    }
    $stmt->execute();
    $chk = $stmt->get_result()->num_rows;
    if ($chk > 0) {
        $msg = '<div class="alert alert-danger">❌ Tenant with the same email or contact already exists.</div>';
    } elseif ($billing_cycle_type === 'custom' && (!$billing_cycle_days || $billing_cycle_days <= 0)) {
        $msg = '<div class="alert alert-danger">❌ Please enter a valid number of days for custom billing cycle.</div>';
    } else {
        // Insert tenant with prepared statement (added billing cycle fields)
        $stmt = $conn->prepare("INSERT INTO tenants (firstname, middlename, lastname, email, contact, house_no, price, start_date, billing_cycle_type, billing_cycle_days) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssdsis", $firstname, $middlename, $lastname, $email, $full_contact, $house_no, $price, $start_date, $billing_cycle_type, $billing_cycle_days);
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success">✅ Tenant added successfully.</div>';
        } else {
            $msg = '<div class="alert alert-danger">❌ Failed to add tenant: ' . $conn->error . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Tenant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }
        body {
            display: flex;
            flex-direction: column;
            background-color: #f8f9fa;
        }
        .main-content {
            flex: 1 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .card {
            width: 100%;
            max-width: 900px;
            height: auto;
            margin: 0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border: none;
        }
        .card-body {
            padding: 2rem;
        }
        .form-label {
            font-weight: bold;
        }
        .input-group {
            flex-wrap: nowrap;
        }
        .alert {
            margin-bottom: 1.5rem;
        }
        #billing_cycle_days_group {
            display: none;
        }
        @media (max-width: 576px) {
            .card-body {
                padding: 1rem;
            }
            .input-group {
                flex-direction: column;
            }
            .input-group select, .input-group input {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Add Tenant</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($msg)) echo $msg; ?>
                <form method="POST" id="tenantForm" novalidate>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="firstname" value="<?php echo htmlspecialchars($tenant['firstname']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middlename" value="<?php echo htmlspecialchars($tenant['middlename']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="lastname" value="<?php echo htmlspecialchars($tenant['lastname']); ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($tenant['email']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="country_code" id="countryCode" class="form-select select2" required>
                                    <option value="" disabled selected>Loading...</option>
                                </select>
                                <input type="text" class="form-control" name="contact" placeholder="Enter contact number" value="<?php echo htmlspecialchars($tenant['contact']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-home me-1"></i> House <span class="text-danger">*</span></label>
                            <select name="house_id" id="house_id" class="form-select select2" required>
                                <option value="" disabled selected>Select House</option>
                                <?php foreach ($houses as $house): ?>
                                    <option value="<?php echo $house['id']; ?>" data-price="<?php echo $house['price']; ?>">
                                        <?php echo htmlspecialchars($house['house_no']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($tenant['start_date']); ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label class="form-label">Billing Cycle <span class="text-danger">*</span></label>
                            <select name="billing_cycle_type" id="billing_cycle_type" class="form-select" required>
                                <option value="" disabled selected>Select Billing Cycle</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                                <option value="custom">Custom Days</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="billing_cycle_days_group">
                            <label class="form-label">Custom Days <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="billing_cycle_days" id="billing_cycle_days" min="1" placeholder="Enter number of days" value="<?php echo htmlspecialchars($tenant['billing_cycle_days']); ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="submit" name="save_tenant" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
                        <a href="index.php" class="btn btn-secondary"><i class="fas fa-times me-1"></i> Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize Select2 for dropdowns
            $('.select2').select2();

            // Fetch country codes
            fetch('https://restcountries.com/v3.1/all?fields=cca2,idd')
                .then(response => response.json())
                .then(data => {
                    const countryCodeSelect = document.getElementById('countryCode');
                    countryCodeSelect.innerHTML = '<option value="" disabled selected>Select Country Code</option>';
                    data.sort((a, b) => a.cca2.localeCompare(b.cca2)).forEach(country => {
                        if (country.idd?.root && country.idd?.suffixes?.length) {
                            const code = `${country.idd.root}${country.idd.suffixes[0]}`;
                            const option = new Option(`${country.cca2} (${code})`, code);
                            countryCodeSelect.add(option);
                        }
                    });
                    $(countryCodeSelect).select2();
                })
                .catch(error => console.error('Error fetching country codes:', error));

            // Toggle custom days field
            const billingCycleType = document.getElementById('billing_cycle_type');
            const billingCycleDaysGroup = document.getElementById('billing_cycle_days_group');
            const billingCycleDays = document.getElementById('billing_cycle_days');

            billingCycleType.addEventListener('change', function() {
                if (this.value === 'custom') {
                    billingCycleDaysGroup.style.display = 'block';
                    billingCycleDays.required = true;
                } else {
                    billingCycleDaysGroup.style.display = 'none';
                    billingCycleDays.required = false;
                    billingCycleDays.value = '';
                }
            });

            // Form validation
            const form = document.getElementById('tenantForm');
            form.addEventListener('submit', function(e) {
                if (!this.checkValidity()) {
                    e.preventDefault();
                    this.classList.add('was-validated');
                }
            });
        });
    </script>
</body>
</html>