<?php
include('db_connect.php');

$date_filter = isset($_POST['date_filter']) ? $_POST['date_filter'] : null;

$total_expenses = 0;
$sql_total = "SELECT SUM(c.total_amount) AS total FROM expenses c";
$result_total = mysqli_query($conn, $sql_total);
$total_expenses_row = mysqli_fetch_assoc($result_total);
$total_expenses = $total_expenses_row['total'] ?? 0;

$expenses = [];
$sql = "SELECT ec.category_name AS category_name, ei.item_name AS item_name, c.quantity, c.total_amount, c.description, c.created_at 
        FROM expenses c
        INNER JOIN expense_categories ec ON c.category_id = ec.category_id
        INNER JOIN expense_items ei ON c.item_id = ei.item_id";

if ($date_filter) {
    $sql .= " WHERE DATE(c.created_at) = ?";
}

$sql .= " ORDER BY c.created_at DESC";

if ($date_filter) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date_filter);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

while ($row = $result->fetch_assoc()) {
    $expenses[] = $row;
}

$sql_categories = "SELECT ec.category_name AS category_name, SUM(c.total_amount) AS total 
                   FROM expenses c
                   INNER JOIN expense_categories ec ON c.category_id = ec.category_id
                   GROUP BY ec.category_name";
$result_categories = $conn->query($sql_categories);

$categories = [];
$totals = [];
while ($row = $result_categories->fetch_assoc()) {
    $categories[] = $row['category_name'];
    $totals[] = $row['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Records</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        td {
            vertical-align: middle !important;
        }
        td p {
            margin: unset;
        }
        img {
            max-width: 100px;
            max-height: 150px;
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid">
                <div class="col-lg-12">
                    <div class="row mb-4 mt-4">
                        <div class="col-md-12">
                            <h1 class="mt-4">Expense Records</h1>
                            <ol class="breadcrumb mb-4">
                                <li class="breadcrumb-item"><a href="index.php?page=home">Dashboard</a></li>
                                <li class="breadcrumb-item active">Expenses</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Filter Card -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <b>Filter Expenses</b>
                                    <span class="float:right">
                                        <a class="btn btn-primary btn-block btn-sm col-sm-2 float-right" href="index.php?page=add_expense" id="new_expense">
                                            <i class="fa fa-plus"></i> New Expense
                                        </a>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" class="d-flex gap-2 align-items-center">
                                        <input type="date" name="date_filter" value="<?php echo htmlspecialchars($date_filter ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control w-auto">
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">Clear</a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expenses Table -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <b>List of Expenses</b>
                                </div>
                                <div class="card-body">
                                    <table id="example" class="table table-condensed table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th class="">Category</th>
                                                <th class="">Item</th>
                                                <th class="text-center">Quantity</th>
                                                <th class="">Total Amount</th>
                                                <th class="">Description</th>
                                                <th class="">Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($expenses as $expense): ?>
                                                <tr>
                                                    <td>
                                                        <p><?php echo htmlspecialchars($expense['category_name']); ?></p>
                                                    </td>
                                                    <td>
                                                        <p><?php echo htmlspecialchars($expense['item_name']); ?></p>
                                                    </td>
                                                    <td class="text-center">
                                                        <p><?php echo $expense['quantity']; ?></p>
                                                    </td>
                                                    <td>
                                                        <p><?php echo number_format($expense['total_amount'], 2); ?></p>
                                                    </td>
                                                    <td>
                                                        <p><?php echo htmlspecialchars($expense['description'] ?? ''); ?></p>
                                                    </td>
                                                    <td>
                                                        <p><?php echo date('d M Y, H:i A', strtotime($expense['created_at'])); ?></p>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pie Chart -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <b>Expense Distribution by Category</b>
                                </div>
                                <div class="card-body">
                                    <canvas id="expensePieChart" style="max-width: 400px; height: 300px; margin: 0 auto;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.bootstrap5.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js" integrity="sha384-6gSOF0iY9XJOWQPkW9PMPKX7W1fXzX4mNgmD91X/2bM0lL1bkb6K8TJxLERd96nD" crossorigin="anonymous"></script>
    <script>
        $(document).ready(function(){
            $('#example').dataTable();
        });

        document.addEventListener('DOMContentLoaded', () => {
            const sidebarToggle = document.getElementById('sidebarToggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', event => {
                    event.preventDefault();
                    document.body.classList.toggle('sb-sidenav-toggled');
                    localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
                });
            }

            const categories = <?php echo json_encode($categories); ?>;
            const totals = <?php echo json_encode($totals); ?>;
            const backgroundColors = categories.map(() => `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 0.6)`);

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
                        legend: { position: 'bottom' }
                    }
                }
            });
        });
    </script>
</body>
</html>