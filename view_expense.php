<?php
include('../header.php');
include('../config.php'); // Database connection

if (!isset($_SESSION['objLogin'])) {
    header("Location: " . WEB_URL . "logout.php");
    die();
}

// Initialize date filter (optional)
$date_filter = isset($_POST['date_filter']) ? $_POST['date_filter'] : null;

// Fetch total expenses
$total_expenses = 0;
$sql_total = "SELECT SUM(c.total_amount) AS total FROM expenses c";
$result_total = mysqli_query($link, $sql_total);
$total_expenses_row = mysqli_fetch_assoc($result_total);
$total_expenses = $total_expenses_row['total'] ?? 0;

// Fetch expenses (with optional date filter)
$expenses = [];
$sql = "SELECT ec.category_name, ei.item_name, c.quantity, c.total_amount, c.description, c.created_at 
        FROM expenses c
        INNER JOIN expense_categories ec ON c.category_id = ec.category_id
        INNER JOIN expense_items ei ON c.item_id = ei.item_id";

if ($date_filter) {
    $sql .= " WHERE DATE(c.created_at) = '$date_filter'";
}

$sql .= " ORDER BY c.created_at DESC";

$result = mysqli_query($link, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $expenses[] = $row;
}

// Fetch expenses by category for the pie chart
$sql_categories = "SELECT ec.category_name, SUM(c.total_amount) AS total 
                   FROM expenses c
                   INNER JOIN expense_categories ec ON c.category_id = ec.category_id
                   GROUP BY ec.category_name";
$result_categories = mysqli_query($link, $sql_categories);

$categories = [];
$totals = [];
while ($row = mysqli_fetch_assoc($result_categories)) {
    $categories[] = $row['category_name'];
    $totals[] = $row['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Expenses</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- jQuery & DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <!-- Header Section -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold text-gray-800">Expense Records</h1>
                <a href="add_expense.php" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition duration-300 flex items-center">
                    <i class="fas fa-plus-circle mr-2"></i> Add Expense
                </a>
            </div>

            <!-- Total Expenses Box -->
            <div style="margin-bottom: 20px;">
                <div style="background-color: #16a34a; color: white; padding: 20px; border-radius: 10px;">
                    <p>Total Expenses</p>
                    <h3 style="font-size: 24px; font-weight: bold;"><?php echo number_format($total_expenses, 2); ?></h3>
                </div>
            </div>

            <!-- Date Filter Section -->
            <div style="margin-bottom: 20px;">
                <form method="POST" action="" style="display: flex; gap: 10px;">
                    <input type="date" name="date_filter" value="<?php echo $date_filter; ?>" style="border: 1px solid #ccc; padding: 10px; border-radius: 5px;">
                    <button type="submit" style="background-color: #2563eb; color: white; padding: 10px 20px; border-radius: 5px;">Filter</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" style="background-color: #4b5563; color: white; padding: 10px 20px; border-radius: 5px;">Clear</a>
                </form>
            </div>

            <!-- Expense Table -->
            <div style="overflow-x: auto;">
                <table id="expenseTable" style="width: 100%; border-collapse: collapse; background-color: white;">
                    <thead style="background-color: #f3f4f6;">
                        <tr>
                            <th style="padding: 10px; border: 1px solid #ddd;">Category</th>
                            <th style="padding: 10px; border: 1px solid #ddd;">Item</th>
                            <th style="padding: 10px; border: 1px solid #ddd;">Quantity</th>
                            <th style="padding: 10px; border: 1px solid #ddd;">Total Amount</th>
                            <th style="padding: 10px; border: 1px solid #ddd;">Description</th>
                            <th style="padding: 10px; border: 1px solid #ddd;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td style="padding: 10px; border: 1px solid #ddd;"> <?php echo htmlspecialchars($expense['category_name']); ?> </td>
                                <td style="padding: 10px; border: 1px solid #ddd;"> <?php echo htmlspecialchars($expense['item_name']); ?> </td>
                                <td style="padding: 10px; border: 1px solid #ddd; text-align: center;"> <?php echo $expense['quantity']; ?> </td>
                                <td style="padding: 10px; border: 1px solid #ddd;"> <?php echo number_format($expense['total_amount'], 2); ?> </td>
                                <td style="padding: 10px; border: 1px solid #ddd;"> <?php echo htmlspecialchars($expense['description']); ?> </td>
                                <td style="padding: 10px; border: 1px solid #ddd;"> <?php echo date('d M Y, H:i A', strtotime($expense['created_at'])); ?> </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pie Chart Section -->
            <div style="background-color: white; padding: 20px; border-radius: 10px; margin-top: 20px;">
                <h2 style="font-size: 20px; font-weight: bold; margin-bottom: 10px;">Expense Distribution by Category</h2>
                <canvas id="expensePieChart" style="max-width: 300px; height: 250px; margin: 0 auto;"></canvas>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('#expenseTable').DataTable({
                dom: 'Bfrtip',
                buttons: ['excel', 'pdf', 'print'],
                paging: true,
                searching: true,
                ordering: true,
                responsive: true
            });
        });

        const categories = <?php echo json_encode($categories); ?>;
        const totals = <?php echo json_encode($totals); ?>;

        const backgroundColors = categories.map(() => {
            return `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 0.6)`;
        });

        const ctx = document.getElementById('expensePieChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: categories,
                datasets: [{
                    data: totals,
                    backgroundColor: backgroundColors
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>