<?php 
include('db_connect.php'); 

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_house'])) {
        if (isset($_POST['id']) && is_numeric($_POST['id'])) {
            $id = intval($_POST['id']);
            $delete = $conn->query("DELETE FROM houses WHERE id = $id");
            if ($delete) {
                $msg = '<div class="alert alert-success">✅ Room deleted successfully.</div>';
                header("Refresh: 2; url=" . $_SERVER['PHP_SELF']);
            } else {
                $msg = '<div class="alert alert-danger">❌ Failed to delete the room.</div>';
            }
        } else {
            $msg = '<div class="alert alert-danger">❌ Invalid room ID.</div>';
        }
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$floors = $conn->query("SELECT * FROM floors ORDER BY floor_number ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms Management</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <!-- Room List Panel -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between">
            <h5 class="mb-0">Room List</h5>
            <a href="index.php?page=add_room" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Add Room</a>
        </div>
        <div class="card-body">
            <?php if (!empty($msg)) echo $msg; ?>
            <div class="table-responsive">
                <table id="houseTable" class="table table-hover table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">Room No</th>
                            <th class="text-center">Type</th>
                            <th class="text-center">Floor</th>
                            <th class="text-center">Description</th>
                            <th class="text-center">Price (Ugshs)</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        $houses = $conn->query("SELECT h.*, c.name as cname, f.floor_number as fname FROM houses h INNER JOIN categories c ON c.id = h.category_id INNER JOIN floors f ON f.floor_id = h.floor_id ORDER BY h.id ASC");
                        while ($row = $houses->fetch_assoc()): ?>
                            <tr>
                                <td class="text-center"><?php echo $i++ ?></td>
                                <td class="text-center"><?php echo $row['house_no'] ?></td>
                                <td class="text-center"><?php echo $row['cname'] ?></td>
                                <td class="text-center"><?php echo $row['fname'] ?></td>
                                <td class="text-center"><?php echo $row['description'] ?></td>
                                <td class="text-center"><?php echo number_format($row['price'], 2) ?></td>
                                <td class="text-center">
                                    <a href="index.php?page=add_room&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>

                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this room?');" style="display:inline;">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                        <button type="submit" name="delete_house" class="btn btn-sm btn-danger">
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

<!-- DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#houseTable').DataTable({
            "paging": true, // Enable pagination
            "searching": true, // Enable search
            "ordering": true, // Enable sorting
            "info": true, // Show table information
            "responsive": true // Make table responsive
        });
    });
</script>
</body>
</html>