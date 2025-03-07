<?php include('db_connect.php'); ?>

<div class="container-fluid">
    <div class="col-lg-12">
        <div class="row">
            <!-- Table Panel -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <b>Category List</b>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th class="text-center">Category</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $i = 1;
                                $categories = $conn->query("SELECT * FROM categories ORDER BY id ASC");
                                while ($row = $categories->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $i++; ?></td>
                                    <td><b><?php echo htmlspecialchars($row['name']); ?></b></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary edit_category" data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['name']); ?>">Edit</button>
                                        <button class="btn btn-sm btn-danger delete_category" data-id="<?php echo $row['id']; ?>">Delete</button>
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

        <div class="row mt-4">
            <!-- Form Panel -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <b>Category Form</b>
                    </div>
                    <div class="card-body">
                        <form action="" id="manage-category">
                            <input type="hidden" name="id">
                            <div class="form-group">
                                <label class="control-label">Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-center">
                            <button class="btn btn-primary btn-sm mx-2" type="submit" form="manage-category">Save</button>
                            <button class="btn btn-secondary btn-sm mx-2" type="button" onclick="$('#manage-category').get(0).reset()">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Form Panel -->
        </div>
    </div>
</div>

<style>
    td {
        vertical-align: middle !important;
    }
</style>

<script>
    $(document).ready(function () {
        $('table').dataTable();

        $('#manage-category').submit(function (e) {
            e.preventDefault();
            start_load();
            $.ajax({
                url: 'ajax.php?action=save_category',
                data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                success: function (resp) {
                    if (resp == 1) {
                        alert_toast("Category successfully added", 'success');
                    } else if (resp == 2) {
                        alert_toast("Category successfully updated", 'success');
                    }
                    setTimeout(() => { location.reload(); }, 1500);
                }
            });
        });

        $('.edit_category').click(function () {
            let cat = $('#manage-category');
            cat.get(0).reset();
            cat.find("[name='id']").val($(this).attr('data-id'));
            cat.find("[name='name']").val($(this).attr('data-name'));
        });

        $('.delete_category').click(function () {
            let id = $(this).attr('data-id');
            _conf("Are you sure you want to delete this category?", "delete_category", [id]);
        });
    });

    function delete_category(id) {
        start_load();
        $.ajax({
            url: 'ajax.php?action=delete_category',
            method: 'POST',
            data: { id: id },
            success: function (resp) {
                if (resp == 1) {
                    alert_toast("Category successfully deleted", 'success');
                    setTimeout(() => { location.reload(); }, 1500);
                }
            }
        });
    }
</script>
