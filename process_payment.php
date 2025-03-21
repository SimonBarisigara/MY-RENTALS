<?php
include 'db_connect.php'; // Ensure database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tenant_id = $_POST['tenant_id'];
    $room_number = $_POST['room_number'];
    $amount_paid = $_POST['amount_paid'];
    $payment_period = $_POST['payment_period'];
    $payment_date = date("Y-m-d");
    
    // Validate Inputs
    if (empty($tenant_id) || empty($room_number) || empty($amount_paid) || empty($payment_period)) {
        echo "All fields are required.";
        exit;
    }

    if ($amount_paid <= 0) {
        echo "Invalid payment amount.";
        exit;
    }

    // Get expected rent from database
    $query = "SELECT rent_amount, balance FROM tenants WHERE tenant_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tenant = $result->fetch_assoc();

    if (!$tenant) {
        echo "Tenant not found.";
        exit;
    }

    $expected_rent = $tenant['rent_amount'];
    $previous_balance = $tenant['balance'];

    // Calculate new balance
    $new_balance = ($previous_balance + $expected_rent) - $amount_paid;

    // Insert Payment Record
    $insert_payment = "INSERT INTO payments (tenant_id, room_number, amount_paid, payment_period, payment_date) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_payment);
    $stmt->bind_param("isiss", $tenant_id, $room_number, $amount_paid, $payment_period, $payment_date);
    $stmt->execute();

    // Update Balance
    $update_balance = "UPDATE tenants SET balance = ? WHERE tenant_id = ?";
    $stmt = $conn->prepare($update_balance);
    $stmt->bind_param("di", $new_balance, $tenant_id);
    $stmt->execute();

    echo "Payment recorded successfully.";
} else {
    echo "Invalid request.";
}
?>
