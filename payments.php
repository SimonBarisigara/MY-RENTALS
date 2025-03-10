<?php include('db_connect.php'); ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold">Rent Payments</h4>
        <a class="btn btn-primary" href="index.php?page=add_payment">+ Add Payment</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">List of Payments</h5>
        </div>
        <div class="card-body">
            <!-- Search Bar -->
            <div class="mb-3">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by Tenant, Invoice, or House #">
            </div>

            <!-- Pagination Dropdown -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <label>Show</label>
                    <select id="rowsPerPage" class="form-select d-inline-block w-auto">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <label>entries</label>
                </div>
            </div>

            <!-- Payments Table -->
            <table class="table table-bordered table-hover table-striped" id="paymentsTable">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Tenant</th>
                        <th>House #</th>
                        <th>Amount Paid</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $payments = $conn->query("
                        SELECT p.id, p.tenant_id, p.amount, p.invoice, p.payment_method, 
                               p.date_paid, p.payment_status, p.outstanding_balance, p.late_fee,
                               CONCAT(t.lastname, ', ', t.firstname, ' ', t.middlename) AS tenant_name,
                               h.house_no
                        FROM payments p
                        INNER JOIN tenants t ON p.tenant_id = t.id
                        LEFT JOIN houses h ON t.house_no = h.house_no
                        ORDER BY p.date_paid DESC");

                    while ($row = $payments->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $i++ ?></td>
                            <td><?php echo ucwords($row['tenant_name']) ?></td>
                            <td><?php echo ($row['house_no']) ? $row['house_no'] : 'N/A' ?></td>
                            <td class="text-end"><?php echo number_format($row['amount'], 2) ?></td>
                            <td class="text-center">
                                <span class="badge bg-<?php echo ($row['payment_status'] == 'paid') ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($row['payment_status']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="index.php?page=view_payment&id=<?php echo $row['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                <a href="generate_receipt.php?id=<?php echo $row['id'] ?>" class="btn btn-sm btn-outline-secondary">Receipt</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Pagination Info -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div id="paginationInfo"></div>
                <nav>
                    <ul class="pagination mb-0" id="paginationControls">
                        <!-- Pagination buttons will be dynamically added here -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Search, Pagination, and Table Filtering -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
    $(document).ready(function () {
        const table = $('#paymentsTable');
        const rowsPerPage = $('#rowsPerPage');
        const searchInput = $('#searchInput');
        const paginationInfo = $('#paginationInfo');
        const paginationControls = $('#paginationControls');

        let currentPage = 1;
        let rows = table.find('tbody tr');
        let totalRows = rows.length;
        let rowsShown = parseInt(rowsPerPage.val());

        // Function to update the table based on search and pagination
        function updateTable() {
            const searchText = searchInput.val().toLowerCase();
            let filteredRows = rows.filter(function () {
                return $(this).text().toLowerCase().includes(searchText);
            });

            totalRows = filteredRows.length;
            const totalPages = Math.ceil(totalRows / rowsShown);

            // Hide all rows
            rows.hide();

            // Show rows for the current page
            filteredRows.slice((currentPage - 1) * rowsShown, currentPage * rowsShown).show();

            // Update pagination info
            paginationInfo.text(`Showing ${(currentPage - 1) * rowsShown + 1} to ${Math.min(currentPage * rowsShown, totalRows)} of ${totalRows} entries`);

            // Update pagination controls
            paginationControls.empty();
            for (let i = 1; i <= tixiotalPages; i++) {
                paginationControls.append(`
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#">${i}</a>
                    </li>
                `);
            }
        }

        // Event listeners
        rowsPerPage.on('change', function () {
            rowsShown = parseInt($(this).val());
            currentPage = 1;
            updateTable();
        });

        searchInput.on('keyup', function () {
            currentPage = 1;
            updateTable();
        });

        paginationControls.on('click', '.page-link', function (e) {
            e.preventDefault();
            currentPage = parseInt($(this).text());
            updateTable();
        });

        // Initialize table
        updateTable();
    });
</script>