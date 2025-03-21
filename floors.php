<?php
include 'db_connect.php';

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_floor'])) {
        $floor_number = mysqli_real_escape_string($conn, trim($_POST['floor_number']));

        if (empty($floor_number)) {
            $msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">❌ Floor number cannot be empty.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM floors WHERE floor_number = ?");
            $stmt->bind_param("s", $floor_number);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if ($count > 0) {
                $msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">❌ Floor number already exists.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            } else {
                $stmt = $conn->prepare("INSERT INTO floors (floor_number) VALUES (?)");
                $stmt->bind_param("s", $floor_number);
                if ($stmt->execute()) {
                    $msg = '<div class="alert alert-success alert-dismissible fade show" role="alert">✅ Floor added successfully.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                } else {
                    $msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">❌ Failed to save the floor. Try again.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                }
                $stmt->close();
            }
        }
    }

    if (isset($_POST['delete_floor'])) {
        $floor_id = $_POST['floor_id'];
        $stmt = $conn->prepare("DELETE FROM floors WHERE floor_id = ?");
        $stmt->bind_param("i", $floor_id);
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success alert-dismissible fade show" role="alert">✅ Floor deleted successfully.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        } else {
            $msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">❌ Failed to delete the floor. Try again.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Floors Management - Rental Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous">
    <link href="css/styles.css" rel="stylesheet"> <!-- Assuming SB Admin styles -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .card {
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border: none;
            border-radius: 8px;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem;
            font-size: 1.25rem;
            font-weight: 500;
        }
        .table {
            margin-bottom: 0;
            width: 100%;
        }
        .table th, .table td {
            vertical-align: middle;
            padding: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .table thead th {
            background-color: #f1f3f5;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }
        .table tbody tr:hover {
            background-color: #f5f5f5;
        }
        .btn-sm {
            padding: 5px 10px;
        }
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 1.5rem;
        }
        .alert-container {
            margin-bottom: 1.5rem;
        }
        @media (max-width: 768px) {
            .table th, .table td {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <div id="layoutSidenav">
        <main>
            <div class="container-fluid px-4">
                <h1 class="mt-4">Floors Management</h1>
                <ol class="breadcrumb mb-4">
                    <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                    <li class="breadcrumb-item active">Floors Management</li>
                </ol>

                <!-- Display Messages -->
                <div class="alert-container">
                    <?php if (!empty($msg)) echo $msg; ?>
                </div>

                <div class="row">
                    <!-- Add Floors Form -->
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-plus me-2"></i> Add Floor
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Floor Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="floor_number" required pattern="[A-Za-z0-9\s]+" title="Alphanumeric characters only">
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" name="save_floor" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Save
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Floors List Table -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-2"></i> Floors List
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="floorsTable">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Floor Number</th>
                                                <th scope="col">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $i = 1;
                                            $floors = $conn->query("SELECT * FROM floors ORDER BY floor_number ASC");
                                            while ($row = $floors->fetch_assoc()):
                                            ?>
                                                <tr>
                                                    <td><?php echo $i++; ?></td>
                                                    <td><?php echo htmlspecialchars($row['floor_number']); ?></td>
                                                    <td>
                                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this floor?');" style="display:inline;">
                                                            <input type="hidden" name="floor_id" value="<?php echo $row['floor_id']; ?>">
                                                            <button type="submit" name="delete_floor" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-trash me-1"></i> Delete
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dataTable = new simpleDatatables.DataTable('#floorsTable', {
                searchable: true,
                fixedHeight: true,
                perPage: 10,
                perPageSelect: [5, 10, 15, 20],
                labels: {
                    placeholder: "Search floors...",
                    perPage: "{select} floors per page",
                    noRows: "No floors found",
                    info: "Showing {start} to {end} of {rows} floors"
                }
            });
        });
    </script>
</body>
</html>