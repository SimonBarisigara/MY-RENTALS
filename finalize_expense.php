<?php
include('db_connect.php');

// Start output buffering to prevent headers issues with JSON
ob_start();

// Ensure JSON response
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if cart has items
$sql = "SELECT * FROM expense_cart";
$result = mysqli_query($conn, $sql);

if (!$result) {
    $response['message'] = "Error querying cart: " . mysqli_error($conn);
    echo json_encode($response);
    exit();
}

if (mysqli_num_rows($result) > 0) {
    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        while ($row = mysqli_fetch_assoc($result)) {
            // Prepare and execute insert into expenses table
            $sql_insert = "INSERT INTO expenses (category_id, item_id, quantity, total_amount, description, created_at) 
                           VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql_insert);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param(
                "iiids", 
                $row['category_id'], 
                $row['item_id'], 
                $row['quantity'], 
                $row['total_amount'], 
                $row['description']
            );

            if (!$stmt->execute()) {
                throw new Exception("Insert failed: " . $stmt->error);
            }
            $stmt->close();
        }

        // Clear the cart
        $sql_delete = "DELETE FROM expense_cart";
        if (!mysqli_query($conn, $sql_delete)) {
            throw new Exception("Delete failed: " . $conn->error);
        }

        // Commit transaction
        mysqli_commit($conn);

        $response['success'] = true;
        $response['message'] = "Expenses finalized successfully!";
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        $response['message'] = "Error finalizing expenses: " . $e->getMessage();
    }
} else {
    $response['message'] = "No items in the cart!";
}

// Clean buffer and output JSON
ob_end_clean();
echo json_encode($response);

$conn->close();
?>