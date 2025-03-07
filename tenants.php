<?php include('db_connect.php'); ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold">Tenant Management</h4>
        <a href="index.php?page=add_tenant" class="btn btn-primary">
            <i class="fa fa-plus"></i> Add New Tenant
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">List of Tenants</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="tenantTable">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center">#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Room No</th>
                            <th>Date In</th>
                            <th>Rent Due</th>
                            <th>Payment Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        $tenant = $conn->query("SELECT 
                            id, 
                            CONCAT(firstname, ' ', COALESCE(middlename, ''), ' ', lastname) AS name, 
                            email, 
                            contact, 
                            COALESCE(house_no, 'N/A') AS room_no, 
                            date_in, 
                            rent_due, 
                            payment_status 
                        FROM tenants 
                        ORDER BY house_no DESC");

                        if ($tenant->num_rows > 0) {
                            while ($row = $tenant->fetch_assoc()) { 
                                $payment_status = ($row['payment_status'] == 'Paid') ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-warning">Unpaid</span>';
                        ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?></td>
                                <td><?php echo ucwords($row['name']); ?></td>
                                <td><?php echo $row['email']; ?></td>
                                <td><?php echo $row['contact']; ?></td>
                                <td class="text-center"><b><?php echo $row['room_no']; ?></b></td>
                                <td><?php echo date("M d, Y", strtotime($row['date_in'])); ?></td>
                                <td class="text-end"><b><?php echo number_format(floatval($row['rent_due'] ?? 0), 2); ?></b></td>
                                <td class="text-center"><?php echo $payment_status; ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary view_tenant" data-id="<?php echo $row['id']; ?>">View</button>
                                    <button class="btn btn-sm btn-outline-danger delete_tenant" data-id="<?php echo $row['id']; ?>">Delete</button>
                                </td>
                            </tr>
                        <?php 
                            } 
                        } else { 
                        ?>
                            <tr>
                                <td colspan="9" class="text-center text-danger">No tenants found.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Tenant Details Modal -->
<div class="modal fade" id="tenantModal" tabindex="-1" aria-labelledby="tenantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="tenantModalLabel">Tenant Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="tenantDetails">
                <p class="text-center">Loading...</p>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#tenantTable').DataTable();

        $('.view_tenant').click(function () {
            let tenantId = $(this).attr('data-id');
            $('#tenantDetails').html('<p class="text-center">Loading...</p>');

            $.ajax({
                url: 'ajax.php?action=view_tenant',
                method: 'POST',
                data: { id: tenantId },
                success: function (response) {
                    $('#tenantDetails').html(response);
                }
            });

            $('#tenantModal').modal('show');
        });

        $('.delete_tenant').click(function () {
            _conf("Are you sure you want to delete this tenant?", "delete_tenant", [$(this).attr('data-id')]);
        });
    });

    function delete_tenant(id) {
        start_load();
        $.ajax({
            url: 'ajax.php?action=delete_tenant',
            method: 'POST',
            data: { id: id },
            success: function (resp) {
                if (resp == 1) {
                    alert_toast("Tenant successfully deleted", 'success');
                    setTimeout(function () {
                        location.reload();
                    }, 1500);
                }
            }
        });
    }
</script>
