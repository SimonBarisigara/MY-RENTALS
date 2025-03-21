<?php
include 'db_connect.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fetch expense categories and items
$categories = $conn->query("SELECT * FROM expense_categories ORDER BY category_name ASC")->fetch_all(MYSQLI_ASSOC);
$items = $conn->query("SELECT * FROM expense_items ORDER BY item_name ASC")->fetch_all(MYSQLI_ASSOC);

// Handle AJAX form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean();

    // Add Item to Cart
    if (!isset($_POST['finalize']) && !isset($_POST['remove_items'])) {
        $category_id = (int)$_POST['category_id'];
        $item_id = (int)$_POST['item_id'];
        $unit_cost = floatval($_POST['unit_cost']);
        $quantity = (int)$_POST['quantity'];
        $total_amount = floatval($_POST['total_amount']);
        $description = htmlspecialchars(trim($_POST['description'] ?? ''));

        $sql = "INSERT INTO expense_cart (category_id, item_id, quantity, total_amount, description, receipt) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiidss", $category_id, $item_id, $quantity, $total_amount, $description, $receipt);

        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Item added to cart successfully!',
                'cart_html' => getCartHtml($conn) // Fetch updated cart HTML
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error: ' . $stmt->error]);
        }
        exit;
    }

    // Remove Items
    if (isset($_POST['remove_items'])) {
        if (!isset($_POST['cart_ids']) || empty($_POST['cart_ids'])) {
            echo json_encode(['status' => 'error', 'message' => 'No items selected for removal']);
            exit;
        }
        $cart_ids = array_map('intval', $_POST['cart_ids']);
        $cart_ids_str = implode(',', $cart_ids);

        // Use prepared statement for DELETE query
        $sql = "DELETE FROM expense_cart WHERE cart_id IN ($cart_ids_str)";
        if ($conn->query($sql)) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Selected items removed successfully!',
                'cart_html' => getCartHtml($conn) // Fetch updated cart HTML
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error: ' . $conn->error]);
        }
        exit;
    }

    // Finalize Expenses
    if (isset($_POST['finalize'])) {
        $sql = "INSERT INTO expenses (category_id, item_id, quantity, total_amount, description, receipt) 
                SELECT category_id, item_id, quantity, total_amount, description, receipt FROM expense_cart";
        if ($conn->query($sql)) {
            $conn->query("DELETE FROM expense_cart"); // Clear the cart
            echo json_encode([
                'status' => 'success',
                'message' => 'Expenses submitted successfully! Cart cleared.',
                'cart_html' => getCartHtml($conn) // Fetch updated cart HTML
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error: ' . $conn->error]);
        }
        exit;
    }
}

// Function to fetch and generate cart HTML
function getCartHtml($conn) {
    $cart_items = $conn->query("SELECT c.cart_id, i.item_name, c.quantity, c.total_amount 
                                FROM expense_cart c
                                INNER JOIN expense_items i ON c.item_id = i.item_id")->fetch_all(MYSQLI_ASSOC);
    $cart_html = '';
    $total_amount = 0;

    if (!empty($cart_items)) {
        foreach ($cart_items as $row) {
            $cart_html .= "<tr>
                <td><input type='checkbox' name='cart_ids[]' value='{$row['cart_id']}'></td>
                <td>" . htmlspecialchars($row['item_name']) . "</td>
                <td>{$row['quantity']}</td>
                <td>" . number_format($row['total_amount'], 2) . "</td>
            </tr>";
            $total_amount += $row['total_amount'];
        }
        $cart_html .= "<tr><td colspan='3' class='text-end'><strong>Total</strong></td><td>" . number_format($total_amount, 2) . "</td></tr>";
    } else {
        $cart_html = '<tr><td colspan="4" class="text-center">Your cart is empty.</td></tr>';
    }
    return $cart_html;
}

// Fetch cart items for initial display
$cart_items = $conn->query("SELECT c.cart_id, i.item_name, c.quantity, c.total_amount 
                            FROM expense_cart c
                            INNER JOIN expense_items i ON c.item_id = i.item_id")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense - Rental Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar { margin-bottom: 20px; }
        .card { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .alert-container { margin-bottom: 20px; }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Rental Management</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=home">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=payments">Payments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php?page=add_expense">Add Expense</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <!-- Alert Container -->
        <div class="alert-container" id="alertContainer"></div>

        <!-- Form and Cart Section -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Add Expense Item</h5>
                    </div>
                    <div class="card-body">
                        <form id="expenseForm" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select id="category_id" name="category_id" class="form-select" required>
                                    <option value="">-- Choose a Category --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="item_id" class="form-label">Expense Item</label>
                                <select id="item_id" name="item_id" class="form-select" required>
                                    <option value="">-- Select an Item --</option>
                                    <?php foreach ($items as $item): ?>
                                        <option value="<?php echo $item['item_id']; ?>">
                                            <?php echo htmlspecialchars($item['item_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="unit_cost" class="form-label">Unit Cost</label>
                                <input type="number" id="unit_cost" name="unit_cost" step="0.01" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" id="quantity" name="quantity" min="1" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="total_amount" class="form-label">Total Amount</label>
                                <input type="number" id="total_amount" name="total_amount" step="0.01" class="form-control" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea id="description" name="description" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="receipt" class="form-label">Upload Receipt</label>
                                <input type="file" id="receipt" name="receipt" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary">Add to Cart</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Cart Section -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Your Expense Cart</h5>
                    </div>
                    <div class="card-body">
                        <form id="cartForm" method="POST">
                            <table class="table" id="cartTable">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAll"></th>
                                        <th>Item</th>
                                        <th>Qty</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody id="cartBody">
                                    <?php if (!empty($cart_items)): ?>
                                        <?php foreach ($cart_items as $row): ?>
                                            <tr>
                                                <td><input type="checkbox" name="cart_ids[]" value="<?php echo $row['cart_id']; ?>"></td>
                                                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                                <td><?php echo $row['quantity']; ?></td>
                                                <td><?php echo number_format($row['total_amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Your cart is empty.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-between">
                                <button type="submit" name="remove_items" id="removeItems" class="btn btn-danger">Remove Selected</button>
                                <button type="submit" name="finalize" id="finalizeExpenses" class="btn btn-success">Submit Expenses</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    $(document).ready(function() {
    // Calculate total amount
    function calculateTotalAmount() {
        const unitCost = parseFloat($('#unit_cost').val()) || 0;
        const quantity = parseFloat($('#quantity').val()) || 0;
        $('#total_amount').val((unitCost * quantity).toFixed(2));
    }
    $('#unit_cost, #quantity').on('input', calculateTotalAmount);

    // Select all checkboxes
    $('#selectAll').on('change', function() {
        $('input[name="cart_ids[]"]').prop('checked', this.checked);
    });

    // Show Bootstrap alert
    function showAlert(message, type) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
        $('#alertContainer').html(alertHtml);
        setTimeout(() => $('.alert').alert('close'), 3000);
    }

    // AJAX for Add Item
    $('#expenseForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        $.ajax({
            url: 'index.php?page=add_expense',
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                showAlert(response.message, response.status === 'success' ? 'success' : 'danger');
                if (response.status === 'success') {
                    $('#cartBody').html(response.cart_html); // Update cart
                    $('#expenseForm')[0].reset(); // Reset form
                    $('#total_amount').val(''); // Clear readonly field
                }
            },
            error: function(xhr) {
                const errorMessage = xhr.responseJSON?.message || 'An error occurred';
                showAlert(errorMessage, 'danger');
            }
        });
    });

    // AJAX for Remove Items and Finalize Expenses
    $('#cartForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const action = e.originalEvent.submitter.name;

        $.ajax({
            url: 'index.php?page=add_expense',
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                showAlert(response.message, response.status === 'success' ? 'success' : 'danger');
                if (response.status === 'success') {
                    $('#cartBody').html(response.cart_html); // Update cart
                    if (action === 'finalize') {
                        $('#selectAll').prop('checked', false); // Reset select all
                    }
                }
            },
            error: function(xhr, status, error) {
    const errorMessage = xhr.responseText || 'An error occurred';
    showAlert(errorMessage, 'danger');
}
        });
    });
});