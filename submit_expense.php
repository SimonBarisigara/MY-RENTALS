<?php
include('db_connect.php');


// Validate input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    if ($category_id <= 0 || $item_id <= 0 || $quantity <= 0) {
        $_SESSION['error'] = "Invalid input. Please fill all fields correctly.";
        header("Location: add_expense.php");
        exit();
    }

    // Fetch item cost
    $query = "SELECT unit_cost FROM expense_items WHERE item_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $item_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $unit_cost);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if (!$unit_cost) {
        $_SESSION['error'] = "Item not found!";
        header("Location: add_expense.php");
        exit();
    }

    // Calculate total amount
    $total_amount = $unit_cost * $quantity;

    // Insert into expense_cart
    $query = "INSERT INTO expense_cart (category_id, item_id, quantity, total_amount, description) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iiids", $category_id, $item_id, $quantity, $total_amount, $description);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($success) {
        $_SESSION['success'] = "Expense added to cart successfully!";
    } else {
        $_SESSION['error'] = "Failed to add expense. Please try again.";
    }
    
    header("Location: add_expense.php");
    exit();
}
?>
