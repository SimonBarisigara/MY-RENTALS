<?php
include 'db_connect.php'; // Include your database connection file

// Fetch all houses
$houses_query = $conn->query("SELECT id, house_no, price FROM houses");
$houses = $houses_query->fetch_all(MYSQLI_ASSOC);

// Fetch all billing cycles
$billing_cycles_query = $conn->query("SELECT * FROM billing_cycles");
$billing_cycles = $billing_cycles_query->fetch_all(MYSQLI_ASSOC);

// Handle form submission for creating a new billing cycle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_cycle'])) {
    $cycle_name = $_POST['cycle_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $stmt = $conn->prepare("INSERT INTO billing_cycles (cycle_name, start_date, end_date) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $cycle_name, $start_date, $end_date);
    $stmt->execute();
    $stmt->close();

    exit();
}

// Handle form submission for updating house prices in a billing cycle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_prices'])) {
    $billing_cycle_id = $_POST['billing_cycle_id'];
    foreach ($_POST['houses'] as $house_id => $price) {
        // Update the base price in the `houses` table
        $update_house_stmt = $conn->prepare("UPDATE houses SET price = ? WHERE id = ?");
        $update_house_stmt->bind_param("di", $price, $house_id);
        $update_house_stmt->execute();
        $update_house_stmt->close();

        // Check if the house is already associated with this billing cycle
        $check_query = $conn->prepare("SELECT id FROM house_billing_cycles WHERE house_id = ? AND billing_cycle_id = ?");
        $check_query->bind_param("ii", $house_id, $billing_cycle_id);
        $check_query->execute();
        $check_query->store_result();

        if ($check_query->num_rows > 0) {
            // Update existing record in `house_billing_cycles`
            $update_stmt = $conn->prepare("UPDATE house_billing_cycles SET price = ? WHERE house_id = ? AND billing_cycle_id = ?");
            $update_stmt->bind_param("dii", $price, $house_id, $billing_cycle_id);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            // Insert new record in `house_billing_cycles`
            $insert_stmt = $conn->prepare("INSERT INTO house_billing_cycles (house_id, billing_cycle_id, price) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("iid", $house_id, $billing_cycle_id, $price);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
        $check_query->close();
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Cycles</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Billing Cycles</h1>

        <!-- Form to create a new billing cycle -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Create New Billing Cycle</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="cycle_name">Cycle Name</label>
                        <input type="text" class="form-control" id="cycle_name" name="cycle_name" required>
                    </div>
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                    <button type="submit" name="create_cycle" class="btn btn-primary">Create Cycle</button>
                </form>
            </div>
        </div>

        <!-- List of billing cycles -->
        <div class="card">
            <div class="card-header">
                <h5>Manage House Prices for Billing Cycles</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="billing_cycle">Select Billing Cycle</label>
                        <select class="form-control" id="billing_cycle" name="billing_cycle_id" required>
                            <?php foreach ($billing_cycles as $cycle): ?>
                                <option value="<?= $cycle['id'] ?>"><?= $cycle['cycle_name'] ?> (<?= $cycle['start_date'] ?> to <?= $cycle['end_date'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>House No</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($houses as $house): ?>
                                <tr>
                                    <td><?= $house['house_no'] ?></td>
                                    <td>
                                        <input type="number" class="form-control" name="houses[<?= $house['id'] ?>]" value="<?= $house['price'] ?>" step="0.01" required>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <button type="submit" name="update_prices" class="btn btn-success">Update Prices</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>