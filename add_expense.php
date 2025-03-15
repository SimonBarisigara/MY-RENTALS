<?php
include 'db_connect.php';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch expense categories and items
$categories = $conn->query("SELECT * FROM expense_categories ORDER BY category_name ASC")->fetch_all(MYSQLI_ASSOC);
$items = $conn->query("SELECT * FROM expense_items ORDER BY item_name ASC")->fetch_all(MYSQLI_ASSOC);

// Handle editing cart items
$edit_item = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM expense_cart WHERE cart_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_item = $stmt->get_result()->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['finalize'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }

    // Sanitize and validate inputs
    $category_id = (int)$_POST['category_id'];
    $item_id = (int)$_POST['item_id'];
    $unit_cost = floatval($_POST['unit_cost']);
    $quantity = (int)$_POST['quantity'];
    $total_amount = floatval($_POST['total_amount']);
    $description = htmlspecialchars(trim($_POST['description'] ?? ''));
    $cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : null;

    // Handle file upload
    $receipt = '';
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $receipt = $upload_dir . basename($_FILES['receipt']['name']);
        move_uploaded_file($_FILES['receipt']['tmp_name'], $receipt);
    }

    if ($cart_id) {
        // Update existing cart item
        $sql = "UPDATE expense_cart SET category_id = ?, item_id = ?, quantity = ?, total_amount = ?, description = ?, receipt = ? WHERE cart_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiidssi", $category_id, $item_id, $quantity, $total_amount, $description, $receipt, $cart_id);
    } else {
        // Insert new cart item
        $sql = "INSERT INTO expense_cart (category_id, item_id, quantity, total_amount, description, receipt) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiidss", $category_id, $item_id, $quantity, $total_amount, $description, $receipt);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Item ' . ($cart_id ? 'updated' : 'added') . ' successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $stmt->error]);
    }
    exit;
}

// Handle removing cart items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_items'])) {
    $cart_ids = array_map('intval', $_POST['cart_ids']);
    $cart_ids_str = implode(',', $cart_ids);

    $sql = "DELETE FROM expense_cart WHERE cart_id IN ($cart_ids_str)";
    if ($conn->query($sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Selected items removed successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $conn->error]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #007bff;
            color: white;
            border-radius: 10px 10px 0 0;
            font-weight: 600;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .form-label {
            font-weight: 500;
        }
        .breadcrumb {
            background-color: #e9ecef;
            border-radius: 5px;
        }
        .table-hover tbody tr:hover {
            background-color: #f1f3f5;
        }
        .empty-cart {
            opacity: 0.8;
            padding: 20px;
        }
        .modal-content {
            border-radius: 10px;
        }
        .modal-header {
            background-color: #007bff;
            color: white;
            border-radius: 10px 10px 0 0;
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4 py-4">
                <ol class="breadcrumb mb-4">
                    <li class="breadcrumb-item"><a href="index.php?page=home" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php?page=expenses" class="text-decoration-none">Expenses</a></li>
                    <li class="breadcrumb-item active">Add Expense</li>
                </ol>

                <!-- Display Success/Error Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Form Section -->
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-plus me-2"></i> 
                                <?php echo isset($edit_item) ? 'Edit Expense Item' : 'Add Expense Item'; ?>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <select id="category_id" name="category_id" class="form-select" required>
                                                <option value="">-- Choose a Category --</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['category_id']; ?>" <?php echo isset($edit_item) && $edit_item['category_id'] == $category['category_id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="btn btn-outline-primary" onclick="openCategoryModal()">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="item_id" class="form-label">Expense Item <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <select id="item_id" name="item_id" class="form-select" required>
                                                <option value="">-- Select an Item --</option>
                                                <?php foreach ($items as $item): ?>
                                                    <option value="<?php echo $item['item_id']; ?>" <?php echo isset($edit_item) && $edit_item['item_id'] == $item['item_id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($item['item_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="btn btn-outline-primary" onclick="openItemModal()">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="unit_cost" class="form-label">Unit Cost <span class="text-danger">*</span></label>
                                        <input type="number" id="unit_cost" name="unit_cost" step="0.01" 
                                               value="<?php echo htmlspecialchars(isset($edit_item) && $edit_item['quantity'] > 0 ? $edit_item['total_amount'] / $edit_item['quantity'] : ''); ?>" 
                                               class="form-control" required placeholder="e.g., 5.99">
                                    </div>

                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                                        <input type="number" id="quantity" name="quantity" min="1" 
                                               value="<?php echo htmlspecialchars($edit_item['quantity'] ?? '1'); ?>" 
                                               class="form-control" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="total_amount" class="form-label">Total Amount <span class="text-danger">*</span></label>
                                        <input type="number" id="total_amount" name="total_amount" step="0.01" 
                                               value="<?php echo htmlspecialchars($edit_item['total_amount'] ?? ''); ?>" 
                                               class="form-control" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description (Optional)</label>
                                        <textarea id="description" name="description" class="form-control" rows="3" 
                                                  placeholder="Add any additional details here..."><?php echo htmlspecialchars($edit_item['description'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="receipt" class="form-label">Upload Receipt (Optional)</label>
                                        <div class="input-group">
                                            <input type="file" id="receipt" name="receipt" accept="image/*,application/pdf" 
                                                   class="form-control" onchange="updateFileName()">
                                            <span class="input-group-text" id="file-name">No file selected</span>
                                        </div>
                                        <small class="form-text text-muted">Supported formats: JPG, PNG, PDF</small>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save me-2"></i> 
                                        <?php echo isset($edit_item) ? 'Update Expense' : 'Add to Cart'; ?>
                                    </button>
                                    <?php if (isset($edit_item)): ?>
                                        <input type="hidden" name="cart_id" value="<?php echo $edit_item['cart_id']; ?>">
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Cart Section -->
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-shopping-cart me-2"></i> Your Expense Cart
                            </div>
                            <div class="card-body" id="cart-body">
                                <?php
                                $sql = "SELECT c.cart_id, i.item_name, c.quantity, c.total_amount 
                                        FROM expense_cart c
                                        INNER JOIN expense_items i ON c.item_id = i.item_id";
                                $result = mysqli_query($conn, $sql);
                                ?>
                                <form action="remove_expense.php" method="POST" id="removeForm">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th><input type="checkbox" id="selectAll"></th>
                                                <th>Item</th>
                                                <th>Qty</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cart-items">
                                            <?php if (mysqli_num_rows($result) > 0): ?>
                                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                                    <tr>
                                                        <td><input type="checkbox" name="cart_ids[]" value="<?php echo $row['cart_id']; ?>"></td>
                                                        <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                                        <td><?php echo $row['quantity']; ?></td>
                                                        <td><?php echo number_format($row['total_amount'], 2); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center empty-cart">
                                                        <i class="fas fa-shopping-cart fa-4x text-muted"></i>
                                                        <p class="text-muted mt-2">Your cart is currently empty.</p>
                                                        <small class="text-muted">Start by adding items using the form!</small>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                    <?php if (mysqli_num_rows($result) > 0): ?>
                                        <div class="d-flex justify-content-between">
                                            <button type="submit" class="btn btn-danger" 
                                                    onclick="return confirm('Are you sure you want to remove the selected items from your cart?');">
                                                <i class="fas fa-trash me-2"></i> Remove Selected
                                            </button>
                                            </form>
                                            <form action="finalize_expense.php" method="POST">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fas fa-check me-2"></i> Submit Expenses
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        </form>
                                    <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Modal -->
            <div id="categoryModal" class="modal fade" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add a New Category</h5>
                            <button type="button" class="btn-close" onclick="closeCategoryModal()"></button>
                        </div>
                        <div class="modal-body">
                            <form id="addCategoryForm" method="POST" action="add_category.php">
                                <div class="mb-3">
                                    <label for="new_category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                    <input type="text" id="new_category_name" name="new_category_name" class="form-control" placeholder="e.g., Office Supplies" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i> Add Category
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Item Modal -->
            <div id="itemModal" class="modal fade" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add a New Item</h5>
                            <button type="button" class="btn-close" onclick="closeItemModal()"></button>
                        </div>
                        <div class="modal-body">
                            <form id="addItemForm" method="POST" action="add_item.php">
                                <div class="mb-3">
                                    <label for="item_category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select id="item_category_id" name="item_category_id" class="form-select" required>
                                        <option value="">-- Select Category --</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['category_id']; ?>">
                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="new_item_name" class="form-label">Item Name <span class="text-danger">*</span></label>
                                    <input type="text" id="new_item_name" name="new_item_name" class="form-control" placeholder="e.g., Printer Paper" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i> Add Item
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function updateFileName() {
            const fileInput = document.getElementById('receipt');
            const fileNameDisplay = document.getElementById('file-name');
            fileNameDisplay.textContent = fileInput.files.length > 0 ? fileInput.files[0].name : "No file selected";
        }

        function openCategoryModal() {
            $('#categoryModal').modal('show');
        }

        function closeCategoryModal() {
            $('#categoryModal').modal('hide');
        }

        function openItemModal() {
            $('#itemModal').modal('show');
        }

        function closeItemModal() {
            $('#itemModal').modal('hide');
        }

        function calculateTotalAmount() {
            const unitCostInput = document.getElementById('unit_cost');
            const quantityInput = document.getElementById('quantity');
            const totalAmountInput = document.getElementById('total_amount');

            const unitCost = parseFloat(unitCostInput.value) || 0;
            const quantity = parseFloat(quantityInput.value) || 0;
            totalAmountInput.value = (unitCost * quantity).toFixed(2);
        }

        document.getElementById('unit_cost').addEventListener('input', calculateTotalAmount);
        document.getElementById('quantity').addEventListener('input', calculateTotalAmount);

        document.addEventListener('DOMContentLoaded', function() {
            calculateTotalAmount();
            document.getElementById('selectAll').addEventListener('change', function() {
                document.querySelectorAll('input[name="cart_ids[]"]').forEach(cb => cb.checked = this.checked);
            });
        });
    </script>
</body>
</html>