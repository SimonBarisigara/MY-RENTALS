<?php
include('db_connect.php');
$msg = "";
$house = [
    'id' => '',
    'house_no' => '',
    'category_id' => '',
    'floor_id' => '',
    'description' => '',
    'price' => ''
];

// Handle Room Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_house'])) {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $house_no = mysqli_real_escape_string($conn, trim($_POST['house_no']));
        $category_id = intval($_POST['category_id']);
        $floor_id = intval($_POST['floor_id']);
        $description = mysqli_real_escape_string($conn, trim($_POST['description']));
        $price = doubleval($_POST['price']);

        if ($id == 0) {
            // Check if room number already exists on the same floor
            $chk = $conn->query("SELECT * FROM houses WHERE house_no = '$house_no' AND floor_id = $floor_id")->num_rows;
            if ($chk > 0) {
                $msg = '<div class="alert alert-danger">❌ Room number already exists on this floor.</div>';
            } else {
                // Insert new room
                $save = $conn->query("INSERT INTO houses (house_no, category_id, floor_id, description, price) VALUES ('$house_no', $category_id, $floor_id, '$description', $price)");
                if ($save) {
                    $msg = '<div class="alert alert-success">✅ Room added successfully.</div>';
                } else {
                    $msg = '<div class="alert alert-danger">❌ Failed to save the room. Try again.</div>';
                }
            }
        } else {
            // Update existing room
            $save = $conn->query("UPDATE houses SET house_no = '$house_no', category_id = $category_id, floor_id = $floor_id, description = '$description', price = $price WHERE id = $id");
            if ($save) {
                $msg = '<div class="alert alert-success">✅ Room updated successfully.</div>';
            } else {
                $msg = '<div class="alert alert-danger">❌ Failed to update the room. Try again.</div>';
            }
        }
    }

    // Handle Category Form Submission
    if (isset($_POST['save_category'])) {
        $category_name = mysqli_real_escape_string($conn, trim($_POST['category_name']));
        // Check for duplicate category
        $chk = $conn->query("SELECT * FROM categories WHERE name = '$category_name'")->num_rows;
        if ($chk > 0) {
            $msg = '<div class="alert alert-danger">❌ Category already exists.</div>';
        } else {
            $save = $conn->query("INSERT INTO categories (name) VALUES ('$category_name')");
            if ($save) {
                $msg = '<div class="alert alert-success">✅ Category added successfully.</div>';
            } else {
                $msg = '<div class="alert alert-danger">❌ Failed to add category. Try again.</div>';
            }
        }
    }

    // Handle Floor Form Submission
    if (isset($_POST['save_floor'])) {
        $floor_number = mysqli_real_escape_string($conn, trim($_POST['floor_number']));
        // Check for duplicate floor
        $chk = $conn->query("SELECT * FROM floors WHERE floor_number = '$floor_number'")->num_rows;
        if ($chk > 0) {
            $msg = '<div class="alert alert-danger">❌ Floor already exists.</div>';
        } else {
            $save = $conn->query("INSERT INTO floors (floor_number) VALUES ('$floor_number')");
            if ($save) {
                $msg = '<div class="alert alert-success">✅ Floor added successfully.</div>';
            } else {
                $msg = '<div class="alert alert-danger">❌ Failed to add floor. Try again.</div>';
            }
        }
    }
}

// Fetch Room Data for Editing
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM houses WHERE id = $id");
    if ($result->num_rows > 0) {
        $house = $result->fetch_assoc();
    }
}

// Fetch Categories and Floors
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$floors = $conn->query("SELECT * FROM floors ORDER BY floor_number ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add/Edit Room</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Room Management</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><?php echo ($house['id'] ? 'Edit' : 'Add'); ?> Room</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($msg)) echo $msg; ?>
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $house['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Room No <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="house_no" value="<?php echo $house['house_no']; ?>" required>
                    </div>
                    <div class="form-row mb-3">
                        <div class="form-group">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="category_id" class="form-select" required>
                                    <option value="" disabled>Select Category</option>
                                    <?php while ($row = $categories->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id'] ?>" <?php echo ($house['category_id'] == $row['id']) ? 'selected' : ''; ?>><?php echo $row['name'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#categoryModal"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Floor <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="floor_id" class="form-select" required>
                                    <option value="" disabled>Select Floor</option>
                                    <?php while ($row = $floors->fetch_assoc()): ?>
                                        <option value="<?php echo $row['floor_id'] ?>" <?php echo ($house['floor_id'] == $row['floor_id']) ? 'selected' : ''; ?>><?php echo $row['floor_number'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#floorModal"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?php echo $house['description']; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price (Ugshs) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control text-end" name="price" value="<?php echo $house['price']; ?>" step="any" required min="1">
                    </div>
                    <div class="text-end">
                        <button type="submit" name="save_house" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                        <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Category Form Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="category_name" required>
                        </div>
                        <div class="text-end">
                            <button type="submit" name="save_category" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                            <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Floor Form Modal -->
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
                            <input type="text" class="form-control" name="floor_number" required>
                        </div>
                        <div class="text-end">
                            <button type="submit" name="save_floor" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                            <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>