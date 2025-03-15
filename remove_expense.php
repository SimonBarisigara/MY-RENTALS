<?php
include('db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_ids'])) {
    $cart_ids = $_POST['cart_ids']; // Get array of selected cart IDs

    if (!empty($cart_ids)) {
        // Convert array values to integers to prevent SQL injection
        $cart_ids = array_map('intval', $cart_ids);
        $cart_ids_str = implode(',', $cart_ids); // Create comma-separated string

        // Check if items exist before deleting
        $check_sql = "SELECT cart_id FROM expense_cart WHERE cart_id IN ($cart_ids_str)";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            // Delete the selected items
            $delete_sql = "DELETE FROM expense_cart WHERE cart_id IN ($cart_ids_str)";
            if (mysqli_query($conn, $delete_sql)) {
                header('Location: add_expense.php?message=Selected items removed successfully');
                exit();
            } else {
                echo "Error: " . mysqli_error($conn);
            }
        } else {
            echo "Error: Selected items do not exist!";
        }
    }
} else {
    echo "No items selected for removal!";
}
?>
