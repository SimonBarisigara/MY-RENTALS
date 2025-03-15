<?php
include('db_connect.php');

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_house'])) {
        if (isset($_POST['id']) && is_numeric($_POST['id'])) {
            $id = intval($_POST['id']);
            $delete = $conn->query("DELETE FROM houses WHERE id = $id");
            if ($delete) {
                $msg = '<div class="alert alert-success">✅ Room deleted successfully.</div>';
            } else {
                $msg = '<div class="alert alert-danger">❌ Failed to delete the room.</div>';
            }
        } else {
            $msg = '<div class="alert alert-danger">❌ Invalid room ID.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Rooms Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/2.2.2/css/dataTables.bootstrap5.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="css/styles.css" rel="stylesheet" />
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
                            <h1 class="mt-4">Rooms Management</h1>
                            <ol class="breadcrumb mb-4">
                                <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                                <li class="breadcrumb-item active">Rooms Management</li>
                            </ol>
                            <?php if (!empty($msg)) echo $msg; ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <b>List of Rooms</b>
                                    <span class="float:right">
                                        <a class="btn btn-primary btn-block btn-sm col-sm-2 float-right" href="index.php?page=add_room" id="new_room">
                                            <i class="fa fa-plus"></i> New Room
                                        </a>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <table id="example" class="table table-condensed table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th class="text-center">#</th>
                                                <th class="">Room No</th>
                                                <th class="">Type</th>
                                                <th class="">Floor</th>
                                                <th class="">Description</th>
                                                <th class="">Price (UGX)</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $i = 1;
                                            $houses = $conn->query("SELECT h.*, c.name as cname, f.floor_number as fname FROM houses h INNER JOIN categories c ON c.id = h.category_id INNER JOIN floors f ON f.floor_id = h.floor_id ORDER BY h.id ASC");
                                            while ($row = $houses->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="text-center"><?php echo $i++ ?></td>
                                                    <td>
                                                        <p><?php echo htmlspecialchars($row['house_no']) ?></p>
                                                    </td>
                                                    <td>
                                                        <p><?php echo htmlspecialchars($row['cname']) ?></p>
                                                    </td>
                                                    <td>
                                                        <p><?php echo htmlspecialchars($row['fname']) ?></p>
                                                    </td>
                                                    <td>
                                                        <p><?php echo htmlspecialchars($row['description']) ?></p>
                                                    </td>
                                                    <td>
                                                        <p><?php echo number_format($row['price'], 2) ?></p>
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="index.php?page=add_room&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary edit_room">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this room?');" style="display:inline;">
                                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                                            <button type="submit" name="delete_house" class="btn btn-sm btn-danger delete_room">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
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
    <script>
        $(document).ready(function(){
            $('#example').dataTable();
        });
    </script>
</body>
</html>