<?php
include 'db_connect.php';

if (isset($_GET['billing_cycle_id'])) {
    $billing_cycle_id = filter_var($_GET['billing_cycle_id'], FILTER_VALIDATE_INT);
    if (!$billing_cycle_id) {
        echo json_encode([]);
        exit;
    }

    $stmt = $conn->prepare("SELECT house_id, price FROM house_billing_cycles WHERE billing_cycle_id = ?");
    $stmt->bind_param("i", $billing_cycle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $prices = [];
    while ($row = $result->fetch_assoc()) {
        $prices[$row['house_id']] = $row['price'];
    }
    echo json_encode($prices);
    $stmt->close();
} else {
    echo json_encode([]);
}
?>