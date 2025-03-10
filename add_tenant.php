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
    'country_code' => ''
];

// Fetch all available houses (houses without tenants)
$houses = $conn->query("SELECT h.id, h.house_no FROM houses h LEFT JOIN tenants t ON h.id = t.house_no WHERE t.house_no IS NULL")->fetch_all(MYSQLI_ASSOC);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        // Fetch the house_no of the selected house
        $house_query = $conn->query("SELECT house_no FROM houses WHERE id = '$house_id'");
        if ($house_query->num_rows > 0) {
            $house = $house_query->fetch_assoc();
            $house_no = $house['house_no'];
        } else {
            $msg = '<div class="alert alert-danger">❌ Invalid house selected. Please select a valid house.</div>';
            $house_no = ''; // Default house_no if house is invalid
        }

        // Check for duplicate tenant
        $chk = $conn->query("SELECT * FROM tenants WHERE email = '$email' OR contact = '$full_contact'")->num_rows;
        if ($chk > 0) {
            $msg = '<div class="alert alert-danger">❌ Tenant with the same email or contact already exists.</div>';
        } else {
            // Insert tenant
            $save = $conn->query("INSERT INTO tenants (firstname, middlename, lastname, email, contact, house_no) 
                VALUES ('$firstname', '$middlename', '$lastname', '$email', '$full_contact', '$house_no')");
            
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
        .form-row { display: flex; gap: 15px; flex-wrap: wrap; }
        .form-row .form-group { flex: 1; min-width: 250px; }
        .card { max-width: 800px; margin: 0 auto; }
        .input-group-text { background-color: #e9ecef; border: 1px solid #ced4da; }
        .navbar-brand { font-size: 1.5rem; }
        .container { padding-top: 2rem; }
        .card-header { display: flex; justify-content: space-between; align-items: center; }
        .card-header h5 { margin: 0; }
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
    <div class="container">
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
        });
    </script>
</body>
</html>