<?php
include('db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = trim($_POST['new_item_name']);
    $item_cost = $_POST['new_item_cost'];
    $category_id = $_POST['item_category_id'];

    // Use prepared statement to prevent SQL injection
    $check_sql = "SELECT 1 FROM expense_items WHERE item_name = ? AND category_id = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "si", $item_name, $category_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);
        header("Location: add_expense.php?error=Item '$item_name' already exists in this category!");
        exit();
    }
    mysqli_stmt_close($stmt);

    // Insert new item
    $insert_sql = "INSERT INTO expense_items (item_name, unit_cost, category_id) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($stmt, "sdi", $item_name, $item_cost, $category_id);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: add_expense.php?success=Item '$item_name' added successfully!");
        exit();
    } else {
        mysqli_stmt_close($stmt);
        header("Location: add_expense.php?error=Error adding item.");
        exit();
    }
}
?>
