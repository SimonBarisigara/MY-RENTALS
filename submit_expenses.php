<?php
include('db_connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $house_no = $_POST['house_no'];
    $date_incurred = $_POST['date_incurred'];
    $total_amount = 0;

    // Calculate total amount
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += $item['amount'];
    }

    // Insert into expenses table
    $query = "INSERT INTO expenses (category_id, house_no, amount, description, date_incurred, status) 
              VALUES (1, '$house_no', '$total_amount', 'Multiple items from cart', '$date_incurred', 'Pending')";
    if ($conn->query($query) === TRUE) {
        $expense_id = $conn->insert_id;

        // Insert cart items into expense_items table
        foreach ($_SESSION['cart'] as $item) {
            $category_id = $item['category_id'];
            $name = $item['name'];
            $amount = $item['amount'];
            $description = $item['description'];

            $query = "INSERT INTO expense_items (category_id, name, created_at) 
                       VALUES ('$category_id', '$name', NOW())";
            $conn->query($query);
        }

        // Clear the cart
        $_SESSION['cart'] = [];

        echo "Expense submitted successfully!";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>