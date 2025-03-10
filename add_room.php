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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_house'])) {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $house_no = mysqli_real_escape_string($conn, trim($_POST['house_no']));
        $category_id = intval($_POST['category_id']);
        $floor_id = intval($_POST['floor_id']);
        $description = mysqli_real_escape_string($conn, trim($_POST['description']));
        $price = doubleval($_POST['price']);

        if ($id == 0) {
            $chk = $conn->query("SELECT * FROM houses WHERE house_no = '$house_no' AND floor_id = $floor_id")->num_rows;
            if ($chk > 0) {
                $msg = '<div class="alert alert-danger">❌ Room number already exists on this floor.</div>';
            } else {
                $save = $conn->query("INSERT INTO houses (house_no, category_id, floor_id, description, price) VALUES ('$house_no', $category_id, $floor_id, '$description', $price)");
                $msg = $save ? '<div class="alert alert-success">✅ Room added successfully.</div>' : '<div class="alert alert-danger">❌ Failed to save the room.</div>';
            }
        } else {
            $save = $conn->query("UPDATE houses SET house_no = '$house_no', category_id = $category_id, floor_id = $floor_id, description = '$description', price = $price WHERE id = $id");
            $msg = $save ? '<div class="alert alert-success">✅ Room updated successfully.</div>' : '<div class="alert alert-danger">❌ Failed to update the room.</div>';
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
    <title>Room Management</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Room Management</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($msg)) echo $msg; ?>
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $house['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Floor <span class="text-danger">*</span></label>
                        <select name="floor_id" class="form-select" required>
                            <option value="" disabled selected>Select Floor</option>
                            <?php while ($row = $floors->fetch_assoc()): ?>
                                <option value="<?php echo $row['floor_id'] ?>" <?php echo ($house['floor_id'] == $row['floor_id']) ? 'selected' : ''; ?>><?php echo $row['floor_number'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Room No <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="house_no" value="<?php echo $house['house_no']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="" disabled selected>Select Category</option>
                            <?php while ($row = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $row['id'] ?>" <?php echo ($house['category_id'] == $row['id']) ? 'selected' : ''; ?>><?php echo $row['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Price (Ugshs) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control text-end" name="price" value="<?php echo $house['price']; ?>" step="any" required min="1">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo $house['description']; ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <button type="submit" name="save_house" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                        <a href="index.php?page=houses" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
