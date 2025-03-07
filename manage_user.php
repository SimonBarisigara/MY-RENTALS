<?php 
include 'db_connect.php';

$user_data = [];
if (isset($_GET['id'])) {
    $user = $conn->query("SELECT * FROM users WHERE id = " . $_GET['id']);
    if ($user->num_rows > 0) {
        $user_data = $user->fetch_assoc();
    }
}
?>

<div class="container-fluid">
    <form id="manage-user">
        <input type="hidden" name="id" value="<?php echo isset($user_data['id']) ? $user_data['id'] : ''; ?>">

        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" class="form-control" name="name" required value="<?php echo isset($user_data['name']) ? $user_data['name'] : ''; ?>">
        </div>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" class="form-control" name="username" required value="<?php echo isset($user_data['username']) ? $user_data['username'] : ''; ?>">
        </div>

        <div class="form-group">
            <label for="password">Password <small>(Leave blank to keep current password)</small></label>
            <input type="password" class="form-control" name="password">
        </div>

        <div class="form-group">
            <label for="type">User Type</label>
            <select class="form-control" name="type" required>
                <option value="1" <?php echo (isset($user_data['type']) && $user_data['type'] == 1) ? 'selected' : ''; ?>>Admin</option>
                <option value="2" <?php echo (isset($user_data['type']) && $user_data['type'] == 2) ? 'selected' : ''; ?>>Staff</option>
                <option value="3" <?php echo (isset($user_data['type']) && $user_data['type'] == 3) ? 'selected' : ''; ?>>Alumnus/Alumna</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Save</button>
    </form>
</div>

<script>
    $(document).ready(function(){
        $('#manage-user').submit(function(e){
            e.preventDefault();
            let form = $(this);
            let submitBtn = form.find('button[type="submit"]');

            // Disable button while submitting
            submitBtn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: 'ajax.php?action=save_user',
                method: 'POST',
                data: form.serialize(),
                success: function(resp){
                    console.log("Response: ", resp); // Debugging

                    if (resp.trim() == "1") {
                        alert_toast("User successfully saved", 'success');
                        setTimeout(function(){
                            location.reload();
                        }, 1500);
                    } else {
                        alert_toast("Error saving user! Check console.", 'danger');
                        console.error("Server Response: ", resp);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    alert_toast("Something went wrong. Try again.", 'danger');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).text('Save'); // Enable button again
                }
            });
        });
    });
</script>
