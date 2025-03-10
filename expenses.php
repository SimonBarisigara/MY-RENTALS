<?php
include('db_connect.php');

// Initialize cart if not set
if (!isset($_SESSION['expense_cart'])) {
    $_SESSION['expense_cart'] = [];
}

// Handle AJAX request for fetching items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetch_items'])) {
    $category_id = (int)$_POST['fetch_items'];
    $stmt = $mysqli->prepare("SELECT id, name FROM expense_items WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();

    echo '<option value="">Select Item</option>';
    while ($row = $result->fetch_assoc()) {
        echo "<option value='{$row['id']}'>{$row['name']}</option>";
    }
    exit;
}

// Add Category
if (isset($_POST['save_category'])) {
    $category_name = $_POST['category_name'];
    $stmt = $mysqli->prepare("INSERT INTO expense_categories (name) VALUES (?)");
    $stmt->bind_param("s", $category_name);
    $stmt->execute();
}

// Add Item
if (isset($_POST['save_item'])) {
    $item_name = $_POST['item_name'];
    $item_category = (int)$_POST['item_category'];
    $stmt = $mysqli->prepare("INSERT INTO expense_items (name, category_id) VALUES (?, ?)");
    $stmt->bind_param("si", $item_name, $item_category);
    $stmt->execute();
}

// Add Expense to Cart
if (isset($_POST['add_to_cart'])) {
    $item_id = $_POST['item_id'];
    $item_name = $_POST['item_name'];
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $total = $quantity * $price;

    $_SESSION['expense_cart'][] = [
        'item_id' => $item_id,
        'item_name' => $item_name,
        'quantity' => $quantity,
        'price' => $price,
        'total' => $total
    ];
}

// Remove Item from Cart
if (isset($_GET['remove'])) {
    $index = (int)$_GET['remove'];
    unset($_SESSION['expense_cart'][$index]);
    $_SESSION['expense_cart'] = array_values($_SESSION['expense_cart']);
}

// Calculate total
$grand_total = array_sum(array_column($_SESSION['expense_cart'], 'total'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Expenses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="container py-5">

    <h2 class="mb-4">Add Expenses</h2>

    <!-- Category Modal -->
    <button class="btn btn-secondary mb-3" data-bs-toggle="modal" data-bs-target="#categoryModal">+ Add Category</button>
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header"><h5>Add Category</h5></div>
                <div class="modal-body">
                    <input type="text" name="category_name" class="form-control" placeholder="Category Name" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="save_category" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Item Modal -->
    <button class="btn btn-secondary mb-3" data-bs-toggle="modal" data-bs-target="#itemModal">+ Add Item</button>
    <div class="modal fade" id="itemModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header"><h5>Add Item</h5></div>
                <div class="modal-body">
                    <input type="text" name="item_name" class="form-control mb-2" placeholder="Item Name" required>
                    <select name="item_category" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php
                        $result = $mysqli->query("SELECT * FROM expense_categories");
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="save_item" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Expense Form -->
    <form method="post" class="row g-3">

        <div class="col-md-4">
            <label for="category" class="form-label">Expense Category</label>
            <select id="category" class="form-select" required>
                <option value="">Select Category</option>
                <?php
                $result = $mysqli->query("SELECT * FROM expense_categories");
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                }
                ?>
            </select>
        </div>

        <div class="col-md-4">
            <label for="item" class="form-label">Expense Item</label>
            <select id="item" name="item_id" class="form-select" required>
                <option value="">Select Item</option>
            </select>
        </div>

        <div class="col-md-2">
            <label for="quantity" class="form-label">Quantity</label>
            <input type="number" name="quantity" class="form-control" value="1" min="1" required>
        </div>

        <div class="col-md-2">
            <label for="price" class="form-label">Price</label>
            <input type="number" name="price" step="0.01" class="form-control" required>
        </div>

        <input type="hidden" name="item_name" id="item_name">

        <div class="col-12">
            <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>
        </div>
    </form>

    <!-- Cart -->
    <h3 class="mt-5">Cart</h3>
    <table class="table table-bordered">
        <thead>
            <tr><th>Item</th><th>Quantity</th><th>Price</th><th>Total</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php foreach ($_SESSION['expense_cart'] as $index => $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= number_format($item['price'], 2) ?></td>
                    <td><?= number_format($item['total'], 2) ?></td>
                    <td><a href="?remove=<?= $index ?>" class="btn btn-danger btn-sm">Remove</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><th colspan="3">Grand Total</th><th colspan="2"><?= number_format($grand_total, 2) ?></th></tr>
        </tfoot>
    </table>

    <a href="submit_expenses.php" class="btn btn-success">Submit Expenses</a>

    <script>
        $('#category').change(function() {
            $.post('', { fetch_items: $(this).val() }, function(response) {
                $('#item').html(response);
            });
        });
        $('#item').change(function() {
            $('#item_name').val($("#item option:selected").text());
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>