<?php
include('db_connect.php');
header('Content-Type: application/json');

$sql = "SELECT c.cart_id, i.item_name, c.quantity, c.total_amount 
        FROM expense_cart c
        INNER JOIN expense_items i ON c.item_id = i.item_id";
$result = mysqli_query($conn, $sql);

$items_html = '';
$count = mysqli_num_rows($result);

if ($count > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $items_html .= "
            <tr>
                <td><input type='checkbox' name='cart_ids[]' value='{$row['cart_id']}'></td>
                <td>" . htmlspecialchars($row['item_name']) . "</td>
                <td>{$row['quantity']}</td>
                <td>" . number_format($row['total_amount'], 2) . "</td>
            </tr>";
    }
}

echo json_encode(['items' => $items_html, 'count' => $count]);
?>