<?php
//session_start();
include('db_connect.php');



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }

    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action == 'delete_tenant') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM tenants WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Tenant deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete tenant']);
            }
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.2.2/css/dataTables.bootstrap5.css" rel="stylesheet">
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
<body>

<div class="container-fluid">
    <div class="col-lg-12">
        <div class="row mb-4 mt-4">
            <div class="col-md-12">
                <!-- Breadcrumb could go here if needed -->
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <b>List of Tenants</b>
                        <span class="float:right">
                            <a class="btn btn-primary btn-block btn-sm col-sm-2 float-right" href="index.php?page=add_tenant" id="new_tenant">
                                <i class="fa fa-plus"></i> New Tenant
                            </a>
                        </span>
                    </div>
                    <div class="card-body">
                        <table class="table table-condensed table-bordered table-hover" id="example">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>e
                                    <th class="">Name</th>
                                    <th class="">Email</th>
                                    <th class="">Contact</th>
                                    <th class="">Room No</th>
                                    <th class="">Payment Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                $tenant = $conn->query("SELECT id, CONCAT(firstname, ' ', COALESCE(middlename, ''), ' ', lastname) AS name, email, contact, COALESCE(house_no, 'N/A') AS room_no, payment_status FROM tenants ORDER BY house_no DESC");
                                if ($tenant->num_rows > 0) {
                                    while ($row = $tenant->fetch_assoc()) {
                                        $payment_status = ($row['payment_status'] == 'Paid') ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-warning">Unpaid</span>';
                                ?>
                                    <tr>
                                        <td class="text-center"><?php echo $i++; ?></td>
                                        <td>
                                            <p><?php echo htmlspecialchars($row['name']); ?></p>
                                        </td>
                                        <td>
                                        <p><?php echo htmlspecialchars($row['email'] ?? ''); ?></p>
                                        </td>
                                        <td>
                                            <p><?php echo htmlspecialchars($row['contact']); ?></p>
                                        </td>
                                        <td>
                                            <p><b><?php echo htmlspecialchars($row['room_no']); ?></b></p>
                                        </td>
                                        <td>
                                            <p><?php echo $payment_status; ?></p>
                                        </td>
                                        <td class="text-center">
                                            <a href="index.php?page=add_tenant&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary edit_tenant" title="Edit"><i class="fas fa-edit"></i></a>
                                            <button class="btn btn-sm btn-danger delete_tenant" data-id="<?php echo $row['id']; ?>" title="Delete"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php
                                    }
                                } else {
                                ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No tenants found.</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.2.2/js/dataTables.bootstrap5.js"></script>
<script>
    $(document).ready(function(){
        $('#example').dataTable()
    })

    $('.delete_tenant').click(function(){
        if(confirm("Are you sure you want to delete this tenant?")){
            const tenantId = $(this).attr('data-id');
            $.ajax({
                url: '',
                method: 'POST',
                data: {
                    action: 'delete_tenant',
                    id: tenantId,
                    csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                },
                dataType: 'json',
                success: function(resp){
                    if(resp.status === 'success'){
                        alert(resp.message)
                        setTimeout(function(){
                            location.reload()
                        },1500)
                    } else {
                        alert(resp.message)
                    }
                },
                error: function(){
                    alert("Error deleting tenant.")
                }
            })
        }
    })
</script>
</body>
</html>