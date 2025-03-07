<?php include('db_connect.php'); ?>

<div class="container-fluid">
    <div class="col-lg-12">
        <div class="row mb-4 mt-4">
            <div class="col-md-12"></div>
        </div>
        <div class="row">
            <!-- Table Panel -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <b>List of Payments</b>
                        <button class="btn btn-primary btn-sm" id="new_invoice">
                            <i class="fa fa-plus"></i> New Entry
                        </button>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Date</th>
                                    <th>Tenant</th>
                                    <th>Invoice</th>
                                    <th>Amount</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $i = 1;
                                $invoices = $conn->query("SELECT p.*, CONCAT(t.lastname, ', ', t.firstname, ' ', t.middlename) AS name 
                                                          FROM payments p 
                                                          INNER JOIN tenants t ON t.id = p.tenant_id 
                                                          WHERE t.status = 1 
                                                          ORDER BY DATE(p.date_created) DESC");
                                while ($row = $invoices->fetch_assoc()): 
                                    $amount = floatval($row['amount'] ?? 0); // Ensure amount is always a valid number
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $i++; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['date_created'])); ?></td>
                                    <td><b><?php echo ucwords($row['name']); ?></b></td>
                                    <td><b><?php echo htmlspecialchars($row['invoice']); ?></b></td>
                                    <td class="text-right"><b><?php echo number_format($amount, 2); ?></b></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary edit_invoice" data-id="<?php echo $row['id']; ?>">Edit</button>
                                        <button class="btn btn-sm btn-outline-danger delete_invoice" data-id="<?php echo $row['id']; ?>">Delete</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Table Panel -->
        </div>
    </div>
</div>

<style>
    td {
        vertical-align: middle !important;
    }
    td p {
        margin: 0;
    }
    img {
        max-width: 100px;
        max-height: 150px;
    }
</style>

<script>
    $(document).ready(function () {
        $('table').dataTable();

        // Open the modal for new invoice
        $('#new_invoice').click(function () {
            uni_modal("New Invoice", "manage_payment.php", "mid-large");
        });

        // Open the modal for editing invoice
        $('.edit_invoice').click(function () {
            let id = $(this).attr('data-id');
            uni_modal("Manage Invoice Details", "manage_payment.php?id=" + id, "mid-large");
        });

        // Delete invoice confirmation
        $('.delete_invoice').click(function () {
            let id = $(this).attr('data-id');
            _conf("Are you sure you want to delete this invoice?", "delete_invoice", [id]);
        });
    });

    function delete_invoice(id) {
        start_load();
        $.ajax({
            url: 'ajax.php?action=delete_payment',
            method: 'POST',
            data: { id: id },
            success: function (resp) {
                if (resp == 1) {
                    alert_toast("Invoice successfully deleted", 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
            }
        });
    }
</script>
