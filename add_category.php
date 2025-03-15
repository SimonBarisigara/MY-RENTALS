<?php
include('db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['new_category_name']);

    // Use prepared statement to prevent SQL injection
    $check_sql = "SELECT 1 FROM expense_categories WHERE category_name = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "s", $category_name);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);
        header("Location: add_expense.php?error=Category '$category_name' already exists!");
        exit();
    }
    mysqli_stmt_close($stmt);

    // Insert new category
    $insert_sql = "INSERT INTO expense_categories (category_name) VALUES (?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($stmt, "s", $category_name);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: add_expense.php?success=Category '$category_name' added successfully!");
        exit();
    } else {
        mysqli_stmt_close($stmt);
        header("Location: add_expense.php?error=Error adding category.");
        exit();
    }
}
?>
