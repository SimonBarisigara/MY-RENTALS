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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Floors Management</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between mb-3">
        <h4>Floors Management</h4>
        <a href="index.php?page=houses" class="btn btn-primary"><i class="fas fa-home"></i> View Rooms</a>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Floors List</h5>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#floorModal">
                <i class="fas fa-plus"></i> Add Floor
            </button>
        </div>
        <div class="card-body">
            <?php if (!empty($msg)) echo $msg; ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">Floor Number</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        $floors = $conn->query("SELECT * FROM floors ORDER BY floor_number ASC");
                        while ($row = $floors->fetch_assoc()):
                        ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($row['floor_number']); ?></td>
                                <td class="text-center">
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this floor?');" style="display:inline;">
                                        <input type="hidden" name="floor_id" value="<?php echo $row['floor_id']; ?>">
                                        <button type="submit" name="delete_floor" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
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

<div class="modal fade" id="floorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Add Floor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Floor Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="floor_number" required pattern="[A-Za-z0-9\s]+" title="Alphanumeric characters only">
                    </div>
                    <div class="text-end">
                        <button type="submit" name="save_floor" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
