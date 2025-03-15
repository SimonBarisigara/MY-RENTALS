<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch all houses
$houses_query = $conn->query("SELECT id, house_no, price FROM houses");
$houses = $houses_query->fetch_all(MYSQLI_ASSOC);

// Fetch all billing cycles
$billing_cycles_query = $conn->query("SELECT * FROM billing_cycles ORDER BY start_date DESC");
$billing_cycles = $billing_cycles_query->fetch_all(MYSQLI_ASSOC);

// Handle form submissions via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    error_log("Received CSRF: " . ($_POST['csrf_token'] ?? 'not set'));
    error_log("Session CSRF: " . ($_SESSION['csrf_token'] ?? 'not set'));

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        ob_end_flush();
        exit;
    }
    // Helper function to calculate end date
    function calculateEndDate($start_date, $cycle_type, $custom_days = 0) {
        $start_date_obj = new DateTime($start_date);
        $end_date_obj = clone $start_date_obj;

        switch ($cycle_type) {
            case 'monthly':
                $end_date_obj->modify('+1 month')->modify('-1 day');
                break;
            case 'weekly':
                $end_date_obj->modify('+6 days');
                break;
            case 'custom':
                if ($custom_days <= 0) return false;
                $end_date_obj->modify("+$custom_days days")->modify('-1 day');
                break;
        }
        return $end_date_obj->format('Y-m-d');
    }

    // Create new billing cycle
    if (isset($_POST['create_cycle'])) {
        $cycle_type = htmlspecialchars(trim($_POST['cycle_name']));
        $start_date = $_POST['start_date'];
        $custom_days = $cycle_type === 'custom' ? (int)$_POST['custom_days'] : 0;

        if (empty($cycle_type) || empty($start_date)) {
            echo json_encode(['status' => 'error', 'message' => 'All required fields must be filled']);
            ob_end_flush();
            exit;
        }

        $end_date = calculateEndDate($start_date, $cycle_type, $custom_days);
        if ($end_date === false) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid custom days value']);
            ob_end_flush();
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO billing_cycles (cycle_name, start_date, end_date, custom_days) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $cycle_type, $start_date, $end_date, $custom_days);
        
        echo json_encode($stmt->execute() ?
            ['status' => 'success', 'message' => 'Billing cycle created successfully'] :
            ['status' => 'error', 'message' => 'Failed to create billing cycle: ' . $conn->error]);
        $stmt->close();
        ob_end_flush();
        exit;
    }

    // Update billing cycle
    if (isset($_POST['update_cycle'])) {
        $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        $cycle_type = htmlspecialchars(trim($_POST['cycle_name']));
        $start_date = $_POST['start_date'];
        $custom_days = $cycle_type === 'custom' ? (int)$_POST['custom_days'] : 0;

        if (!$id || empty($cycle_type) || empty($start_date)) {
            echo json_encode(['status' => 'error', 'message' => 'All required fields must be filled']);
            ob_end_flush();
            exit;
        }

        $end_date = calculateEndDate($start_date, $cycle_type, $custom_days);
        if ($end_date === false) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid custom days value']);
            ob_end_flush();
            exit;
        }

        $stmt = $conn->prepare("UPDATE billing_cycles SET cycle_name = ?, start_date = ?, end_date = ?, custom_days = ? WHERE id = ?");
        $stmt->bind_param("sssii", $cycle_type, $start_date, $end_date, $custom_days, $id);
        
        echo json_encode($stmt->execute() ?
            ['status' => 'success', 'message' => 'Billing cycle updated successfully'] :
            ['status' => 'error', 'message' => 'Failed to update billing cycle: ' . $conn->error]);
        $stmt->close();
        ob_end_flush();
        exit;
    }

    // Delete billing cycle
    if (isset($_POST['delete_cycle'])) {
        $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid billing cycle ID']);
            ob_end_flush();
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM billing_cycles WHERE id = ?");
        $stmt->bind_param("i", $id);
        echo json_encode($stmt->execute() ?
            ['status' => 'success', 'message' => 'Billing cycle deleted successfully'] :
            ['status' => 'error', 'message' => 'Failed to delete billing cycle: ' . $conn->error]);
        $stmt->close();
        ob_end_flush();
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Billing Cycles - Rental Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        .card { box-shadow: 0 4px 8px rgba(0,0,0,0.1); border: none; }
        .alert { position: fixed; top: 20px; right: 20px; z-index: 1050; min-width: 300px; }
        .table-responsive { max-height: 400px; overflow-y: auto; }
        .modal-body .form-label { font-weight: 500; }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Billing Cycles</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php?page=home">Dashboard</a></li>
                        <li class="breadcrumb-item active">Billing Cycles</li>
                    </ol>

                    <!-- Create New Billing Cycle -->
                    <div class="card mb-4">
                        <div class="card-header bg-dark text-white">
                            <i class="fas fa-calendar-plus me-2"></i> Create New Billing Cycle
                        </div>
                        <div class="card-body">
                            <form id="createCycleForm" method="POST" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="create_cycle" value="1">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="cycle_name" class="form-label">Cycle Type <span class="text-danger">*</span></label>
                                        <select class="form-select" id="cycle_name" name="cycle_name" required>
                                            <option value="" disabled selected>Select Type</option>
                                            <option value="monthly">Monthly</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="custom">Custom</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a cycle type.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                                        <div class="invalid-feedback">Please select a start date.</div>
                                    </div>
                                    <div class="col-md-4" id="custom_days_container" style="display: none;">
                                        <label for="custom_days" class="form-label">Custom Days <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="custom_days" name="custom_days" min="1">
                                        <div class="invalid-feedback">Please enter valid days.</div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save me-2"></i> Create Cycle</button>
                            </form>
                        </div>
                    </div>

                    <!-- Billing Cycles List -->
                    <div class="card mb-4">
                        <div class="card-header bg-dark text-white">
                            <i class="fas fa-list me-2"></i> Existing Billing Cycles
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="billingCyclesTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Cycle Type</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Custom Days</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($billing_cycles as $cycle): ?>
                                            <tr>
                                                <td><?php echo $cycle['id']; ?></td>
                                                <td><?php echo ucfirst(htmlspecialchars($cycle['cycle_name'])); ?></td>
                                                <td><?php echo $cycle['start_date']; ?></td>
                                                <td><?php echo $cycle['end_date']; ?></td>
                                                <td><?php echo $cycle['custom_days'] ?: '-'; ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary edit-cycle" 
                                                        data-id="<?php echo $cycle['id']; ?>" 
                                                        data-name="<?php echo htmlspecialchars($cycle['cycle_name']); ?>" 
                                                        data-start="<?php echo $cycle['start_date']; ?>" 
                                                        data-days="<?php echo $cycle['custom_days']; ?>">Edit</button>
                                                    <button class="btn btn-sm btn-outline-danger delete-cycle" data-id="<?php echo $cycle['id']; ?>">Delete</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editCycleModal" tabindex="-1" aria-labelledby="editCycleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCycleModalLabel">Edit Billing Cycle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editCycleForm" method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="update_cycle" value="1">
                        <input type="hidden" name="id" id="edit_cycle_id">
                        <div class="mb-3">
                            <label for="edit_cycle_name" class="form-label">Cycle Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_cycle_name" name="cycle_name" required>
                                <option value="" disabled>Select Type</option>
                                <option value="monthly">Monthly</option>
                                <option value="weekly">Weekly</option>
                                <option value="custom">Custom</option>
                            </select>
                            <div class="invalid-feedback">Please select a cycle type.</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                            <div class="invalid-feedback">Please select a start date.</div>
                        </div>
                        <div class="mb-3" id="edit_custom_days_container" style="display: none;">
                            <label for="edit_custom_days" class="form-label">Custom Days <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_custom_days" name="custom_days" min="1">
                            <div class="invalid-feedback">Please enter valid days.</div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"></script>
    <script src="js/scripts.js"></script>
    <script>
    // Toggle custom days field
    function toggleCustomDays(selectId, containerId) {
        const select = document.getElementById(selectId);
        const container = document.getElementById(containerId);
        select.addEventListener('change', () => {
            container.style.display = select.value === 'custom' ? 'block' : 'none';
            const input = container.querySelector('input');
            if (select.value === 'custom') input.setAttribute('required', 'required');
            else input.removeAttribute('required');
        });
    }

    toggleCustomDays('cycle_name', 'custom_days_container');
    toggleCustomDays('edit_cycle_name', 'edit_custom_days_container');

    // Edit Billing Cycle
    document.querySelectorAll('.edit-cycle').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = new bootstrap.Modal(document.getElementById('editCycleModal'));
            document.getElementById('edit_cycle_id').value = btn.dataset.id;
            document.getElementById('edit_cycle_name').value = btn.dataset.name;
            document.getElementById('edit_start_date').value = btn.dataset.start;
            const daysContainer = document.getElementById('edit_custom_days_container');
            const daysInput = document.getElementById('edit_custom_days');
            daysContainer.style.display = btn.dataset.name === 'custom' ? 'block' : 'none';
            daysInput.value = btn.dataset.days || '';
            if (btn.dataset.name === 'custom') daysInput.setAttribute('required', 'required');
            else daysInput.removeAttribute('required');
            modal.show();
        });
    });

    // AJAX Form Submission
    function submitForm(formId) {
        const form = document.getElementById(formId);
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (this.checkValidity()) {
                const formData = new FormData(this);
                console.log('Form Data:');
                for (let [key, value] of formData.entries()) {
                    console.log(`${key}: ${value}`);
                }
                fetch('billing_cycles.php', { method: 'POST', body: formData })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        const alert = document.createElement('div');
                        alert.className = `alert alert-${data.status} alert-dismissible fade show`;
                        alert.innerHTML = `${data.message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
                        document.body.appendChild(alert);
                        if (data.status === 'success') setTimeout(() => location.reload(), 1500);
                    })
                    .catch(error => {
                        console.error('Fetch Error:', error);
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-danger alert-dismissible fade show';
                        alert.innerHTML = `Error: ${error.message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
                        document.body.appendChild(alert);
                    });
            } else {
                this.classList.add('was-validated');
            }
        });
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        submitForm('createCycleForm');
        submitForm('editCycleForm');

        new simpleDatatables.DataTable('#billingCyclesTable', {
            searchable: true,
            perPageSelect: [5, 10, 20],
            perPage: 5,
            labels: {
                placeholder: "Search billing cycles...",
                perPage: "{select} entries per page",
                noRows: "No billing cycles found",
                info: "Showing {start} to {end} of {rows} entries"
            }
        });

        document.querySelectorAll('.delete-cycle').forEach(btn => {
            btn.addEventListener('click', () => {
                if (confirm('Are you sure you want to delete this billing cycle?')) {
                    const formData = new FormData();
                    formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
                    formData.append('delete_cycle', '1');
                    formData.append('id', btn.dataset.id);
                    fetch('billing_cycles.php', { method: 'POST', body: formData })
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(data => {
                            const alert = document.createElement('div');
                            alert.className = `alert alert-${data.status} alert-dismissible fade show`;
                            alert.innerHTML = `${data.message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
                            document.body.appendChild(alert);
                            if (data.status === 'success') setTimeout(() => location.reload(), 1500);
                        })
                        .catch(error => {
                            console.error('Fetch Error:', error);
                            const alert = document.createElement('div');
                            alert.className = 'alert alert-danger alert-dismissible fade show';
                            alert.innerHTML = `Error: ${error.message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
                            document.body.appendChild(alert);
                        });
                }
            });
        });
    });
</script>
</body>
</html>