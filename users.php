<?php 
include 'db_connect.php';
?>

<div class="container-fluid">
	<div class="row">
		<div class="col-lg-12">
			<button class="btn btn-primary float-right btn-sm" id="new_user">
				<i class="fa fa-plus"></i> New User
			</button>
		</div>
	</div>
	<br>
	<div class="row">
		<div class="card col-lg-12">
			<div class="card-body">
				<table class="table table-striped table-bordered">
					<thead>
						<tr>
							<th class="text-center">#</th>
							<th class="text-center">Name</th>
							<th class="text-center">Username</th>
							<th class="text-center">Type</th>
							<th class="text-center">Action</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$type = array("", "Admin", "Staff", "Alumnus/Alumna");
						$users = $conn->query("SELECT * FROM users ORDER BY name ASC");
						$i = 1;
						while ($row = $users->fetch_assoc()):
						?>
							<tr>
								<td class="text-center"><?php echo $i++; ?></td>
								<td><?php echo ucwords($row['name']); ?></td>
								<td><?php echo $row['username']; ?></td>
								<td><?php echo $type[$row['type']]; ?></td>
								<td class="text-center">
									<div class="btn-group">
										<button type="button" class="btn btn-primary btn-sm edit_user" data-id="<?php echo $row['id']; ?>">
											<i class="fa fa-edit"></i> Edit
										</button>
										<button type="button" class="btn btn-danger btn-sm delete_user" data-id="<?php echo $row['id']; ?>">
											<i class="fa fa-trash"></i> Delete
										</button>
									</div>
								</td>
							</tr>
						<?php endwhile; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<script>
	$('table').DataTable(); // Ensure table has proper pagination & search

	// Open "New User" Modal
	$('#new_user').click(function(){
		uni_modal('New User', 'manage_user.php');
	});

	// Open "Edit User" Modal
	$('.edit_user').click(function(){
		uni_modal('Edit User', 'manage_user.php?id=' + $(this).attr('data-id'));
	});

	// Delete User Confirmation
	$('.delete_user').click(function(){
		_conf("Are you sure to delete this user?", "delete_user", [$(this).attr('data-id')]);
	});

	// Delete User Function
	function delete_user(id){
		start_load();
		$.ajax({
			url: 'ajax.php?action=delete_user',
			method: 'POST',
			data: { id: id },
			success: function(resp){
				if (resp == 1){
					alert_toast("User successfully deleted", 'success');
					setTimeout(function(){
						location.reload();
					}, 1500);
				}
			}
		});
	}
</script>
