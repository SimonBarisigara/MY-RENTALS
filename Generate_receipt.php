<?php
// Fetch payment details based on the ID from the URL
if (isset($_GET['id'])) {
    include('db_connect.php'); // Include the database connection
    $payment_id = $_GET['id'];
    $payment = $conn->query("
        SELECT p.*, 
               CONCAT(t.lastname, ', ', t.firstname, ' ', t.middlename) AS tenant_name,
               h.house_no
        FROM payments p
        INNER JOIN tenants t ON p.tenant_id = t.id
        LEFT JOIN houses h ON t.house_id = h.id
        WHERE p.id = $payment_id
    ")->fetch_assoc();

    if (!$payment) {
        die("Payment not found!");
    }
} else {
    die("Invalid request!");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .receipt {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        .receipt h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        .receipt p {
            margin: 10px 0;
        }
        .receipt .label {
            font-weight: bold;
        }
        .print-button {
            text-align: center;
            margin-top: 20px;
        }
        .print-button button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .print-button button:hover {
            background-color: #0056b3;
        }
        @media print {
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <h1>Payment Receipt</h1>
        <p><span class="label">Tenant Name:</span> <?php echo ucwords($payment['tenant_name']) ?></p>
        <p><span class="label">House Number:</span> <?php echo $payment['house_no'] ? $payment['house_no'] : 'N/A'; ?></p>
        <p><span class="label">Amount Paid:</span> <?php echo number_format($payment['amount'], 2); ?></p>
        <p><span class="label">Invoice Number:</span> <?php echo $payment['invoice']; ?></p>
        <p><span class="label">Payment Method:</span> <?php echo ucwords($payment['payment_method']); ?></p>
        <p><span class="label">Date Paid:</span> <?php echo date("M d, Y H:i:s", strtotime($payment['date_paid'])); ?></p>
        <p><span class="label">Outstanding Balance:</span> <?php echo number_format($payment['outstanding_balance'], 2); ?></p>
        <p><span class="label">Late Fee:</span> <?php echo number_format($payment['late_fee'], 2); ?></p>
        <p><span class="label">Payment Status:</span> <?php echo ucfirst($payment['payment_status']); ?></p>
        <p><span class="label">Payment Period:</span></p>
        <p><span class="label">Start Date:</span> <?php echo date("M d, Y", strtotime($payment['period_start'])); ?></p>
        <p><span class="label">End Date:</span> <?php echo date("M d, Y", strtotime($payment['period_end'])); ?></p>
        <div class="print-button">
            <button onclick="window.print()">Print Receipt</button>
        </div>
    </div>
</body>
</html>