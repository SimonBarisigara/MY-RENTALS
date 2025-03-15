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

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_house'])) {
        // Save Room
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $house_no = mysqli_real_escape_string($conn, trim($_POST['house_no']));
        $category_id = intval($_POST['category_id']);
        $floor_id = intval($_POST['floor_id']);
        $description = mysqli_real_escape_string($conn, trim($_POST['description']));
        $price = doubleval($_POST['price']);

        if ($id == 0) {
            // Check for duplicate room on the same floor
            $chk = $conn->query("SELECT * FROM houses WHERE house_no = '$house_no' AND floor_id = $floor_id")->num_rows;
            if ($chk > 0) {
                $msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">❌ Room number already exists on this floor.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                $save = $conn->query("INSERT INTO houses (house_no, category_id, floor_id, description, price) VALUES ('$house_no', $category_id, $floor_id, '$description', $price)");
                $msg = $save ? '<div class="alert alert-success alert-dismissible fade show" role="alert">✅ Room added successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>' : '<div class="alert alert-danger alert-dismissible fade show" role="alert">❌ Failed to save the room.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        } else {
            $save = $conn->query("UPDATE houses SET house_no = '$house_no', category_id = $category_id, floor_id = $floor_id, description = '$description', price = $price WHERE id = $id");
            $msg = $save ? '<div class="alert alert-success alert-dismissible fade show" role="alert">✅ Room updated successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>' : '<div class="alert alert-danger alert-dismissible fade show" role="alert">❌ Failed to update the room.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    } elseif (isset($_POST['add_category'])) {
        // Add New Category
        $category_name = mysqli_real_escape_string($conn, trim($_POST['category_name']));
        if (!empty($category_name)) {
            $chk = $conn->query("SELECT * FROM categories WHERE name = '$category_name'")->num_rows;
            if ($chk > 0) {
                $msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">❌ Category already exists.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                $save = $conn->query("INSERT INTO categories (name) VALUES ('$category_name')");
                $msg = $save ? '<div class="alert alert-success alert-dismissible fade show" role="alert">✅ Category added successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>' : '<div class="alert alert-danger alert-dismissible fade show" role="alert">❌ Failed to add category.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        } else {
            $msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">❌ Category name cannot be empty.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    } elseif (isset($_POST['add_floor'])) {
        // Add New Floor
        $floor_number = mysqli_real_escape_string($conn, trim($_POST['floor_number']));
        if (!empty($floor_number)) {
            $chk = $conn->query("SELECT * FROM floors WHERE floor_number = '$floor_number'")->num_rows;
            if ($chk > 0) {
                $msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">❌ Floor already exists.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            } else {
                $save = $conn->query("INSERT INTO floors (floor_number) VALUES ('$floor_number')");
                $msg = $save ? '<div class="alert alert-success alert-dismissible fade show" role="alert">✅ Floor added successfully.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>' : '<div class="alert alert-danger alert-dismissible fade show" role="alert">❌ Failed to add floor.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        } else {
            $msg = '<div class="alert alert-danger alert-dismissible fade show" role="alert">❌ Floor number cannot be empty.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    }
}

// Fetch categories and floors
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$floors = $conn->query("SELECT * FROM floors ORDER BY floor_number ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Room Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .container-fluid { padding: 20px; }
        .card { box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .form-label { font-weight: 500; }
        .select2-container { width: 100% !important; }
        .btn-add { 
            padding: 0.25rem 0.5rem; 
            margin-right: 10px;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        .input-group {
            display: flex;
            align-items: center;
        }
        .input-group .form-select {
            flex: 1;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        .alert { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="col-lg-12">
            <div class="row mb-4 mt-4">
                <div class="col-md-12">
                    <h1 class="mt-4"><?php echo $house['id'] ? 'Edit Room' : 'Add New Room'; ?></h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php?page=home">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="index.php?page=houses">Rooms</a></li>
                        <li class="breadcrumb-item active"><?php echo $house['id'] ? 'Edit Room' : 'Add Room'; ?></li>
                    </ol>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <b><?php echo $house['id'] ? 'Edit Room Details' : 'New Room'; ?></b>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($msg)) echo $msg; ?>
                            <form method="POST" id="houseForm">
                                <input type="hidden" name="id" value="<?php echo $house['id']; ?>">
                                <input type="hidden" name="save_house" value="1">

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Floor <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <button type="button" class="btn btn-primary btn-add" data-bs-toggle="modal" data-bs-target="#addFloorModal">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            <select name="floor_id" id="floor_id" class="form-select searchable" required>
                                                <option value="" disabled selected>Select Floor</option>
                                                <?php while ($row = $floors->fetch_assoc()): ?>
                                                    <option value="<?php echo $row['floor_id'] ?>" <?php echo ($house['floor_id'] == $row['floor_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($row['floor_number']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Room No <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="house_no" value="<?php echo htmlspecialchars($house['house_no']); ?>" required placeholder="e.g., 101">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Category <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <button type="button" class="btn btn-primary btn-add" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            <select name="category_id" id="category_id" class="form-select searchable" required>
                                                <option value="" disabled selected>Select Category</option>
                                                <?php while ($row = $categories->fetch_assoc()): ?>
                                                    <option value="<?php echo $row['id'] ?>" <?php echo ($house['category_id'] == $row['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($row['name']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Price (UGX) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="price" value="<?php echo htmlspecialchars($house['price']); ?>" step="0.01" required min="1" placeholder="e.g., 500000">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3" placeholder="Enter room description (optional)"><?php echo htmlspecialchars($house['description']); ?></textarea>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <button type="submit" name="save_house" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
                                    <a href="index.php?page=houses" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="addCategoryForm">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="category_name" name="category_name" required placeholder="e.g., Single Room">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="add_category" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Floor Modal -->
    <div class="modal fade" id="addFloorModal" tabindex="-1" aria-labelledby="addFloorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addFloorModalLabel">Add New Floor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="addFloorForm">
                        <div class="mb-3">
                            <label for="floor_number" class="form-label">Floor Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="floor_number" name="floor_number" required placeholder="e.g., Ground Floor">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="add_floor" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for searchable dropdowns
            $('.searchable').select2({
                placeholder: function() {
                    return $(this).find('option:selected').text();
                },
                allowClear: false
            });
        });
    </script>
</body>
</html>