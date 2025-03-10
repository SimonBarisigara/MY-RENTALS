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
    'payment_status' => 0
];

// Fetch all billing cycles
$billing_cycles = $conn->query("SELECT * FROM billing_cycles")->fetch_all(MYSQLI_ASSOC);

// Fetch all houses
$houses = $conn->query("SELECT id, house_no, price FROM houses")->fetch_all(MYSQLI_ASSOC);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['fetch_price'])) {
        // Fetch price for the selected house
        $house_id = intval($_POST['house_id']);
        $query = $conn->query("SELECT price FROM houses WHERE id = '$house_id'");
        if ($query->num_rows > 0) {
            $house = $query->fetch_assoc();
            echo json_encode(['success' => true, 'price' => $house['price']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid house selected.']);
        }
        exit; // Stop further execution for AJAX request
    }

    if (isset($_POST['save_tenant'])) {
        // Save tenant data
        $firstname = mysqli_real_escape_string($conn, trim($_POST['firstname']));
        $middlename = mysqli_real_escape_string($conn, trim($_POST['middlename']));
        $lastname = mysqli_real_escape_string($conn, trim($_POST['lastname']));
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        $country_code = mysqli_real_escape_string($conn, trim($_POST['country_code']));
        $contact = mysqli_real_escape_string($conn, trim($_POST['contact']));
        $full_contact = $country_code . $contact; // Combine country code & number
        $house_id = intval($_POST['house_id']);
        $billing_cycle_id = intval($_POST['billing_cycle_id']);
        $payment_status = intval($_POST['payment_status']);

        // Fetch the price of the selected house
        $house_query = $conn->query("SELECT price FROM houses WHERE id = '$house_id'");
        if ($house_query->num_rows > 0) {
            $house = $house_query->fetch_assoc();
            $price = $house['price'];
        } else {
            $msg = '<div class="alert alert-danger">❌ Invalid house selected. Please select a valid house.</div>';
            $price = 0; // Default price if house is invalid
        }

        // Check for duplicate tenant
        $chk = $conn->query("SELECT * FROM tenants WHERE email = '$email' OR contact = '$full_contact'")->num_rows;
        if ($chk > 0) {
            $msg = '<div class="alert alert-danger">❌ Tenant with the same email or contact already exists.</div>';
        } else {
            // Insert tenant
            $save = $conn->query("INSERT INTO tenants (firstname, middlename, lastname, email, contact, house_id, billing_cycle_id, price, payment_status) 
                VALUES ('$firstname', '$middlename', '$lastname', '$email', '$full_contact', $house_id, $billing_cycle_id, $price, $payment_status)");
            
            if ($save) {
                $tenant_id = $conn->insert_id;
                $msg = '<div class="alert alert-success">✅ Tenant added successfully.</div>';
            } else {
                $msg = '<div class="alert alert-danger">❌ Failed to add tenant. Try again.</div>';
            }
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
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .form-group { margin-bottom: 1rem; }
        .form-group label { font-weight: bold; }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        .card { max-width: 800px; margin: 0 auto; }
        .input-group-text { background-color: #e9ecef; border: 1px solid #ced4da; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Room Management</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Add Tenant</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($msg)) echo $msg; ?>
                <form method="POST" id="tenantForm">
                    <!-- Personal Information -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="firstname" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" class="form-control" name="middlename">
                        </div>
                        <div class="form-group">
                            <label>Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="lastname" required>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="form-group">
                            <label>Contact <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="country_code" id="countryCode" class="form-select" required>
                                    <option value="" disabled selected>Loading...</option>
                                </select>
                                <input type="text" class="form-control" name="contact" placeholder="Enter contact number" required>
                            </div>
                        </div>
                    </div>

                    <!-- House Information -->
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-home"></i> House <span class="text-danger">*</span></label>
                            <select name="house_id" id="house_id" class="form-select" required>
                                <option value="" disabled selected>Select House</option>
                                <?php foreach ($houses as $house): ?>
                                    <option value="<?= $house['id'] ?>"><?= $house['house_no'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Price (UGX)</label>
                            <input type="text" class="form-control" id="price" readonly>
                        </div>
                    </div>

                    <!-- Billing Cycle -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Billing Cycle <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="billing_cycle_id" class="form-select" required>
                                    <option value="" disabled selected>Select Billing Cycle</option>
                                    <?php foreach ($billing_cycles as $cycle): ?>
                                        <option value="<?= $cycle['id'] ?>"><?= $cycle['cycle_name'] ?> (<?= $cycle['start_date'] ?> to <?= $cycle['end_date'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="billing_cycle.php" class="btn btn-outline-secondary" title="Add Billing Cycle">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Status -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Payment Status</label>
                            <select name="payment_status" class="form-select">
                                <option value="0">Unpaid</option>
                                <option value="1">Paid</option>
                            </select>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="text-end mt-4">
                        <button type="submit" name="save_tenant" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                        <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Fetch country codes
            fetch('https://restcountries.com/v3.1/all?fields=cca2,idd')
                .then(response => response.json())
                .then(data => {
                    const countryCodeSelect = document.getElementById('countryCode');
                    countryCodeSelect.innerHTML = '';
                    data.forEach(country => {
                        if (country.idd?.root && country.idd?.suffixes?.length) {
                            const countryCode = `${country.idd.root}${country.idd.suffixes[0]}`;
                            countryCodeSelect.innerHTML += `<option value="${countryCode}">${country.cca2} (${countryCode})</option>`;
                        }
                    });
                })
                .catch(error => console.error('Error fetching country codes:', error));

            // Fetch price when house is selected
            $('#house_id').on('change', function () {
                const house_id = $(this).val();
                if (house_id) {
                    $.ajax({
                        url: '', // Submit to the same page
                        method: 'POST',
                        data: { fetch_price: true, house_id: house_id },
                        success: function(response) {
                            const result = JSON.parse(response);
                            if (result.success) {
                                $('#price').val(result.price);
                            } else {
                                $('#price').val('');
                                alert(result.message || 'Invalid house selected.');
                            }
                        },
                        error: function() {
                            alert('Error fetching price. Please try again.');
                        }
                    });
                } else {
                    $('#price').val('');
                }
            });
        });
    </script>
</body>
</html>