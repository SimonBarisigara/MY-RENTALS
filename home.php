<?php include 'db_connect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <!-- Welcome Message -->
            <div class="text-2xl font-semibold text-gray-800">
                Welcome back, <span class="text-blue-600"><?php echo $_SESSION['login_name']; ?></span>!
            </div>
            <hr class="my-4 border-gray-200">
            <!-- Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Rooms Card -->
                <div class="card bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6 bg-blue-600 text-white relative">
                        <i class="fas fa-home text-4xl opacity-20 absolute top-4 right-4"></i>
                        <h4 class="text-3xl font-bold"><?php echo $conn->query("SELECT * FROM houses")->num_rows; ?></h4>
                        <p class="text-sm font-medium">Total Rooms</p>
                    </div>
                    <div class="p-4 bg-gray-50">
                        <a href="index.php?page=houses" class="text-blue-600 hover:text-blue-700 font-medium float-right">
                            View List <i class="fas fa-angle-right"></i>
                        </a>
                    </div>
                </div>
                <!-- Total Tenants Card -->
                <div class="card bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6 bg-yellow-500 text-white relative">
                        <i class="fas fa-user-friends text-4xl opacity-20 absolute top-4 right-4"></i>
                        <h4 class="text-3xl font-bold"><?php echo $conn->query("SELECT * FROM tenants WHERE status = 1")->num_rows; ?></h4>
                        <p class="text-sm font-medium">Total Tenants</p>
                    </div>
                    <div class="p-4 bg-gray-50">
                        <a href="index.php?page=tenants" class="text-blue-600 hover:text-blue-700 font-medium float-right">
                            View List <i class="fas fa-angle-right"></i>
                        </a>
                    </div>
                </div>
                <!-- Total Expenses Card -->
                <div class="card bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6 bg-green-600 text-white relative">
                        <i class="fas fa-credit-card text-4xl opacity-20 absolute top-4 right-4"></i>
                        <h4 class="text-3xl font-bold"><?php echo $conn->query("SELECT * FROM expenses")->num_rows; ?></h4>
                        <p class="text-sm font-medium">Total Expenses</p>
                    </div>
                    <div class="p-4 bg-gray-50">
                        <a href="index.php?page=expenses" class="text-blue-600 hover:text-blue-700 font-medium float-right">
                            View Details <i class="fas fa-angle-right"></i>
                        </a>
                    </div>
                </div>
                <!-- Payments Today Card -->
                <div class="card bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6 bg-purple-600 text-white relative">
                        <i class="fas fa-file-invoice text-4xl opacity-20 absolute top-4 right-4"></i>
                        <h4 class="text-3xl font-bold">
                            <?php
                            $payment = $conn->query("SELECT SUM(amount) AS paid FROM payments WHERE DATE(date_created) = '" . date('Y-m-d') . "'");
                            $payment_data = $payment->fetch_array();
                            echo $payment->num_rows > 0 && isset($payment_data['paid']) ? number_format($payment_data['paid'], 2) : '0.00';
                            ?>
                        </h4>
                        <p class="text-sm font-medium">Payments Today</p>
                    </div>
                    <div class="p-4 bg-gray-50">
                        <a href="index.php?page=invoices" class="text-blue-600 hover:text-blue-700 font-medium float-right">
                            View Payments <i class="fas fa-angle-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Font Awesome JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
</body>
</html>