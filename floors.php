<?php
include 'db_connect.php';

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_floor'])) {
        $floor_number = mysqli_real_escape_string($conn, trim($_POST['floor_number']));

        if (empty($floor_number)) {
            $msg = '<div class="alert alert-danger">❌ Floor number cannot be empty.</div>';
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM floors WHERE floor_number = ?");
            $stmt->bind_param("s", $floor_number);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if ($count > 0) {
                $msg = '<div class="alert alert-danger">❌ Floor number already exists.</div>';
            } else {
                $stmt = $conn->prepare("INSERT INTO floors (floor_number) VALUES (?)");
                $stmt->bind_param("s", $floor_number);
                if ($stmt->execute()) {
                    $msg = '<div class="alert alert-success">✅ Floor added successfully.</div>';
                } else {
                    $msg = '<div class="alert alert-danger">❌ Failed to save the floor. Try again.</div>';
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
            $msg = '<div class="alert alert-success">✅ Floor deleted successfully.</div>';
        } else {
            $msg = '<div class="alert alert-danger">❌ Failed to delete the floor. Try again.</div>';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Floors Management - SB Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .table-modern th, .table-modern td {
            vertical-align: middle;
        }
        .table-modern thead {
            background-color: #343a40;
            color: white;
        }
        .table-modern tbody tr:hover {
            background-color: #f8f9fa;
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
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
                <?php if (!empty($msg)) echo $msg; ?>

                <div class="row">
                    <!-- Add Floors Form -->
                    <div class="col-md-4">
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-dark text-white">
                                <i class="fas fa-plus me-1"></i> Add Floor
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Floor Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="floor_number" required pattern="[A-Za-z0-9\s]+" title="Alphanumeric characters only">
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" name="save_floor" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Floors List Table -->
                    <div class="col-md-8">
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-dark text-white">
                                <i class="fas fa-table me-1"></i> Floors List
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-modern table-bordered table-striped" id="floorsTable">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
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