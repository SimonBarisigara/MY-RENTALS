<?php
include 'db_connect.php';

// Handle actions (Create Expense, Change Status, Delete Expense)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Action for creating an expense
    if (isset($_POST['create_expense'])) {
        $category_id = $_POST['category_id'];
        $house_no = $_POST['house_no']; // Get selected house_no
        $amount = $_POST['amount'];
        $description = $_POST['description'];
        $date_incurred = $_POST['date_incurred'];

        // Insert the expense data into the database
        $query = "INSERT INTO expenses (category_id, house_no, amount, description, date_incurred, status) 
                  VALUES ('$category_id', '$house_no', '$amount', '$description', '$date_incurred', 'Pending')";

        if ($conn->query($query) === TRUE) {
            $response = ['status' => 'success', 'message' => 'Expense added successfully'];
        } else {
            $response = ['status' => 'error', 'message' => 'Error: ' . $conn->error];
        }
    }

    // Action to change the status of an expense
    if (isset($_POST['change_status'])) {
        $expense_id = $_POST['expense_id'];
        $new_status = $_POST['status'];

        $query = "UPDATE expenses SET status='$new_status' WHERE id='$expense_id'";

        if ($conn->query($query) === TRUE) {
            $response = ['status' => 'success', 'message' => 'Expense status updated successfully'];
        } else {
            $response = ['status' => 'error', 'message' => 'Error: ' . $conn->error];
        }
    }

    // Action to delete an expense
    if (isset($_POST['delete_expense'])) {
        $expense_id = $_POST['expense_id'];

        $query = "DELETE FROM expenses WHERE id='$expense_id'";

        if ($conn->query($query) === TRUE) {
            $response = ['status' => 'success', 'message' => 'Expense deleted successfully'];
        } else {
            $response = ['status' => 'error', 'message' => 'Error: ' . $conn->error];
        }
    }
}

// Fetch categories for the form
$categories_query = "SELECT * FROM expense_categories";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

// Fetch available house numbers for the form (from the houses table)
$houses_query = "SELECT house_no FROM houses";
$houses_result = $conn->query($houses_query);
$houses = [];
while ($row = $houses_result->fetch_assoc()) {
    $houses[] = $row;
}

// Fetch all expenses for display
$expenses_query = "SELECT expenses.*, expense_categories.name AS category_name 
                   FROM expenses 
                   LEFT JOIN expense_categories ON expenses.category_id = expense_categories.id";
$expenses_result = $conn->query($expenses_query);
$expenses = [];
while ($row = $expenses_result->fetch_assoc()) {
    $expenses[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Management</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
        }
        .card-body {
            padding: 2rem;
        }
        .modal-header {
            background-color: #007bff;
            color: white;
        }
        .modal-footer {
            background-color: #f1f1f1;
        }
        .alert {
            margin-bottom: 1rem;
        }
        .btn-status {
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <h4><b>Expense Management</b></h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#expenseModal">Create Expense</button>
                </div>

                <!-- Response message -->
                <?php if (isset($response)): ?>
                    <div class="alert alert-<?php echo $response['status']; ?>" role="alert">
                        <?php echo $response['message']; ?>
                    </div>
                <?php endif; ?>

                <!-- Search and Sort Section -->
                <div class="d-flex justify-content-between mt-4">
                    <input type="text" id="searchInput" class="form-control w-25" placeholder="Search expenses...">
                    <button class="btn btn-secondary" onclick="sortTable(0)">Sort by Category</button> <!-- Sort button -->
                </div>

                <!-- Expense List -->
                <h5 class="mt-4">Expense List</h5>
                <table class="table table-striped" id="expenseTable">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>House No</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td><?php echo $expense['category_name']; ?></td>
                                <td><?php echo $expense['house_no']; ?></td>
                                <td><?php echo number_format($expense['amount'], 2); ?></td>
                                <td><?php echo $expense['description']; ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="Pending" <?php echo ($expense['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Cleared" <?php echo ($expense['status'] == 'Cleared') ? 'selected' : ''; ?>>Cleared</option>
                                        </select>
                                        <input type="hidden" name="expense_id" value="<?php echo $expense['id']; ?>">
                                        <input type="hidden" name="change_status" value="1">
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="expense_id" value="<?php echo $expense['id']; ?>">
                                        <input type="hidden" name="delete_expense" value="1">
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Expense Modal -->
    <div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="expenseModalLabel">Create Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="expense-form">
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select id="category_id" name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="house_no" class="form-label">House No</label>
                            <select id="house_no" name="house_no" class="form-select" required>
                                <option value="">Select House No</option>
                                <?php foreach ($houses as $house): ?>
                                    <option value="<?php echo $house['house_no']; ?>"><?php echo $house['house_no']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <input type="number" id="amount" name="amount" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="date_incurred" class="form-label">Date Incurred</label>
                            <input type="date" id="date_incurred" name="date_incurred" class="form-control" required>
                        </div>
                        <button type="submit" name="create_expense" class="btn btn-primary">Create Expense</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS for Search and Sorting -->
    <script>
        // Function to filter expenses based on search input
        document.getElementById("searchInput").addEventListener("input", function() {
            let searchValue = this.value.toLowerCase();
            let rows = document.querySelectorAll("#expenseTable tbody tr");

            rows.forEach(row => {
                let category = row.cells[0].textContent.toLowerCase();
                let houseNo = row.cells[1].textContent.toLowerCase();
                let description = row.cells[3].textContent.toLowerCase();

                if (category.includes(searchValue) || houseNo.includes(searchValue) || description.includes(searchValue)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });

        // Function to sort the table by category (column index 0)
        function sortTable(colIndex) {
            let table = document.getElementById("expenseTable");
            let rows = Array.from(table.rows).slice(1); // Exclude header row
            let sortedRows = rows.sort((a, b) => {
                let textA = a.cells[colIndex].textContent.trim().toLowerCase();
                let textB = b.cells[colIndex].textContent.trim().toLowerCase();
                return textA.localeCompare(textB);
            });

            // Reorder rows in the table
            sortedRows.forEach(row => table.appendChild(row));
        }
    </script>
</body>
</html>
