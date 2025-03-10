<?php
//session_start();
include('db_connect.php');

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }

    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action == 'edit_tenant') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("SELECT * FROM tenants WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $tenant = $result->fetch_assoc();

            if ($tenant) {
                $form = '
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="firstname" class="form-control" value="' . htmlspecialchars($tenant['firstname']) . '" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middlename" class="form-control" value="' . htmlspecialchars($tenant['middlename']) . '">
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="lastname" class="form-control" value="' . htmlspecialchars($tenant['lastname']) . '" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="' . htmlspecialchars($tenant['email']) . '" required>
                    </div>
                    <div class="form-group">
                        <label>Contact</label>
                        <input type="text" name="contact" class="form-control" value="' . htmlspecialchars($tenant['contact']) . '" required>
                    </div>
                    <div class="form-group">
                        <label>Room No</label>
                        <input type="text" name="house_no" class="form-control" value="' . htmlspecialchars($tenant['house_no']) . '" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Status</label>
                        <select name="payment_status" class="form-control" required>
                            <option value="Paid" ' . ($tenant['payment_status'] == 'Paid' ? 'selected' : '') . '>Paid</option>
                            <option value="Unpaid" ' . ($tenant['payment_status'] == 'Unpaid' ? 'selected' : '') . '>Unpaid</option>
                        </select>
                    </div>
                    <input type="hidden" name="id" value="' . $tenant['id'] . '">
                    <input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">
                ';
                echo $form;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Tenant not found']);
            }
            exit;
        }

        if ($action == 'save_tenant') {
            $id = intval($_POST['id']);
            $firstname = htmlspecialchars($_POST['firstname']);
            $middlename = htmlspecialchars($_POST['middlename']);
            $lastname = htmlspecialchars($_POST['lastname']);
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $contact = htmlspecialchars($_POST['contact']);
            $house_no = htmlspecialchars($_POST['house_no']);
            $payment_status = htmlspecialchars($_POST['payment_status']);

            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
                exit;
            }

            // Update tenant
            $stmt = $conn->prepare("UPDATE tenants SET 
                firstname = ?, 
                middlename = ?, 
                lastname = ?, 
                email = ?, 
                contact = ?, 
                house_no = ?, 
                payment_status = ? 
                WHERE id = ?");
            $stmt->bind_param("sssssssi", $firstname, $middlename, $lastname, $email, $contact, $house_no, $payment_status, $id);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Tenant updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update tenant']);
            }
            exit;
        }

        if ($action == 'delete_tenant') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM tenants WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Tenant deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete tenant']);
            }
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Management</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .form-group { margin-bottom: 1rem; }
        .form-group label { font-weight: bold; }
        .card { max-width: 1000px; margin: 0 auto; }
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
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold">Tenant Management</h4>
            <a href="index.php?page=add_tenant" class="btn btn-primary">
                <i class="fa fa-plus"></i> Add New Tenant
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">List of Tenants</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="tenantTable">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center">#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Room No</th>
                                <th>Payment Status</th>
                                <th class="text-center">Actions</th>
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
                                payment_status 
                            FROM tenants 
                            ORDER BY house_no DESC");

                            if ($tenant->num_rows > 0) {
                                while ($row = $tenant->fetch_assoc()) { 
                                    $payment_status = ($row['payment_status'] == 'Paid') ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-warning">Unpaid</span>';
                            ?>
                                <tr>
                                    <td class="text-center"><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['contact']); ?></td>
                                    <td class="text-center"><b><?php echo htmlspecialchars($row['room_no']); ?></b></td>
                                    <td class="text-center"><?php echo $payment_status; ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-info edit_tenant" data-id="<?php echo $row['id']; ?>" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete_tenant" data-id="<?php echo $row['id']; ?>" title="Delete">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php 
                                } 
                            } else { 
                            ?>
                                <tr>
                                    <td colspan="7" class="text-center text-danger">No tenants found.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tenant Edit Modal -->
    <div class="modal fade" id="editTenantModal" tabindex="-1" aria-labelledby="editTenantModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="editTenantModalLabel">Edit Tenant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editTenantForm">
                        <!-- Form fields for editing tenant details will be loaded here via AJAX -->
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="editTenantForm" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#tenantTable').DataTable();

            // Edit Tenant
            $('.edit_tenant').click(function () {
                let tenantId = $(this).attr('data-id');
                $('#editTenantForm').html('<p class="text-center">Loading...</p>');

                $.ajax({
                    url: '',
                    method: 'POST',
                    data: { action: 'edit_tenant', id: tenantId, csrf_token: '<?php echo $_SESSION['csrf_token']; ?>' },
                    success: function (response) {
                        $('#editTenantForm').html(response);
                    }
                });

                $('#editTenantModal').modal('show');
            });

            // Delete Tenant
            $('.delete_tenant').click(function () {
                let tenantId = $(this).attr('data-id');
                if (confirm("Are you sure you want to delete this tenant?")) {
                    $.ajax({
                        url: '',
                        method: 'POST',
                        data: { action: 'delete_tenant', id: tenantId, csrf_token: '<?php echo $_SESSION['csrf_token']; ?>' },
                        success: function (response) {
                            let data = JSON.parse(response);
                            if (data.status === 'success') {
                                alert(data.message);
                                location.reload();
                            } else {
                                alert("Failed to delete tenant: " + data.message);
                            }
                        }
                    });
                }
            });

            // Save Tenant
            $('#editTenantForm').submit(function (e) {
                e.preventDefault();
                $.ajax({
                    url: '',
                    method: 'POST',
                    data: $(this).serialize() + '&action=save_tenant',
                    success: function (response) {
                        let data = JSON.parse(response);
                        if (data.status === 'success') {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert("Failed to update tenant: " + data.message);
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>