<?php 
include 'db_connect.php';
// If filter form is submitted
$filterQuery = "";
if (isset($_POST['filter'])) {
    $filter = $_POST['filter'];

    if ($filter == 'month' && isset($_POST['month'])) {
        $month = $_POST['month'];
        $filterQuery = " WHERE MONTH(date_incurred) = '$month'";
    } elseif ($filter == 'week' && isset($_POST['week'])) {
        $week = $_POST['week'];
        $filterQuery = " WHERE WEEK(date_incurred) = '$week'";
    } elseif ($filter == 'room' && isset($_POST['room'])) {
        $room = $_POST['room'];
        $filterQuery = " WHERE house_no = '$room'";
    }
}

// Fetch the expenses based on the filter criteria
$query = "SELECT * FROM expenses";
$query .= $filterQuery; // Append filter condition
$expenses_result = $conn->query($query);
$expenses = $expenses_result->fetch_all(MYSQLI_ASSOC);

// Handle CSV download
if (isset($_POST['download_csv'])) {
    $filename = "expense_report_" . date('Y_m_d') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['category_id', 'Room No', 'Amount', 'Description', 'Date Incurred', 'Status']);

    foreach ($expenses as $expense) {
        fputcsv($output, [
            $expense['category_id'],
            $expense['house_no'],
            $expense['amount'],
            $expense['description'],
            $expense['date_incurred'],
            $expense['status']
        ]);
    }

    fclose($output);
    exit();
}
?>

<div class="container">
    <div class="row">
        <div class="col-sm-4">
            <!-- Filter Form -->
            <form method="POST">
                <label for="filter" class="form-label">Filter by:</label>
                <select name="filter" id="filter" class="form-select" onchange="this.form.submit()">
                    <option value="">All Expenses</option>
                    <option value="month" <?php echo (isset($_POST['filter']) && $_POST['filter'] == 'month') ? 'selected' : ''; ?>>By Month</option>
                    <option value="week" <?php echo (isset($_POST['filter']) && $_POST['filter'] == 'week') ? 'selected' : ''; ?>>By Week</option>
                    <option value="room" <?php echo (isset($_POST['filter']) && $_POST['filter'] == 'room') ? 'selected' : ''; ?>>By Room</option>
                </select>
            </form>
        </div>

        <!-- Month Filter -->
        <?php if (isset($_POST['filter']) && $_POST['filter'] == 'month'): ?>
            <div class="col-sm-4">
                <form method="POST">
                    <label for="month" class="form-label">Select Month:</label>
                    <select name="month" id="month" class="form-select" onchange="this.form.submit()">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo (isset($_POST['month']) && $_POST['month'] == $m) ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>
        <?php endif; ?>

        <!-- Week Filter -->
        <?php if (isset($_POST['filter']) && $_POST['filter'] == 'week'): ?>
            <div class="col-sm-4">
                <form method="POST">
                    <label for="week" class="form-label">Select Week:</label>
                    <select name="week" id="week" class="form-select" onchange="this.form.submit()">
                        <option value="1">Week 1</option>
                        <option value="2">Week 2</option>
                        <option value="3">Week 3</option>
                        <option value="4">Week 4</option>
                    </select>
                </form>
            </div>
        <?php endif; ?>

        <!-- Room Filter -->
        <?php if (isset($_POST['filter']) && $_POST['filter'] == 'room'): ?>
            <div class="col-sm-4">
                <form method="POST">
                    <label for="room" class="form-label">Select Room:</label>
                    <select name="room" id="room" class="form-select" onchange="this.form.submit()">
                        <!-- Populate with available room numbers -->
                        <?php
                        $rooms_query = "SELECT house_no FROM houses";
                        $rooms_result = $conn->query($rooms_query);
                        while ($room = $rooms_result->fetch_assoc()) {
                            echo '<option value="' . $room['house_no'] . '">' . $room['house_no'] . '</option>';
                        }
                        ?>
                    </select>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- Expense Table -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>category_id</th>
                <th>Room No</th>
                <th>Amount</th>
                <th>Description</th>
                <th>Date Incurred</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($expenses as $expense): ?>
                <tr>
                    <td><?php echo $expense['category_id']; ?></td>
                    <td><?php echo $expense['house_no']; ?></td>
                    <td><?php echo $expense['amount']; ?></td>
                    <td><?php echo $expense['description']; ?></td>
                    <td><?php echo $expense['date_incurred']; ?></td>
                    <td><?php echo $expense['status']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Download Button -->
    <form method="POST">
        <button type="submit" name="download_csv" class="btn btn-success">Download as CSV</button>
    </form>
</div>
