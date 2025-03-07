<?php include 'db_connect.php'; ?>

<?php 
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM payments WHERE id=" . $_GET['id']);
    foreach($qry->fetch_array() as $k => $val){
        $$k = $val;
    }
}
?>

<div class="container-fluid">
    <form action="" id="manage-payment">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        
        <div id="msg"></div>

        <div class="form-group">
            <label for="" class="control-label">Tenant <span class="text-danger">*</span></label>
            <select name="tenant_id" id="tenant_id" class="custom-select select2" required>
                <option value=""></option>
                <?php 
                $tenant = $conn->query("SELECT *, CONCAT(lastname, ', ', firstname, ' ', middlename) AS name FROM tenants WHERE status = 1 ORDER BY name ASC");
                while($row = $tenant->fetch_assoc()):
                ?>
                <option value="<?php echo $row['id'] ?>" <?php echo isset($tenant_id) && $tenant_id == $row['id'] ? 'selected' : '' ?>>
                    <?php echo ucwords($row['name']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group" id="details"></div>

        <div class="form-group">
            <label for="" class="control-label">Invoice #<span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="invoice" value="<?php echo isset($invoice) ? $invoice :'' ?>" required>
        </div>

        <div class="form-group">
            <label for="" class="control-label">Amount Paid <span class="text-danger">*</span></label>
            <input type="number" class="form-control text-right" step="any" name="amount" value="<?php echo isset($amount) ? $amount :'' ?>" required>
        </div>

    </form>
</div>

<script>
    $(document).ready(function(){
        $('.select2').select2({ placeholder: "Please Select Here", width: "100%" });

        $('#tenant_id').change(function(){
    if ($(this).val() <= 0) return false;

    start_load();
    $.ajax({
        url: 'ajax.php?action=get_tdetails',
        method: 'POST',
        data: {id: $(this).val()},
        success: function(resp){
            try {
                resp = JSON.parse(resp);
                
                if (resp.error) {
                    $('#details').html('<p class="text-danger"><b>' + resp.error + '</b></p>');
                } else {
                    var details = $('#details_clone .d').clone();
                    details.find('.tname').text(resp.name);
                    details.find('.price').text(resp.price);
                    details.find('.outstanding').text(resp.outstanding);
                    details.find('.total_paid').text(resp.paid);
                    details.find('.rent_started').text(resp.rent_started);
                    details.find('.payable_months').text(resp.months);
                    $('#details').html(details);
                }
            } catch (e) {
                console.error("JSON Parsing Error: ", e);
                console.log("Response: ", resp);
                $('#details').html('<p class="text-danger"><b>Error processing request.</b></p>');
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error: ", status, error);
            $('#details').html('<p class="text-danger"><b>Failed to fetch tenant details.</b></p>');
        },
        complete: function(){
            end_load();
        }
    });
});

        $('#manage-payment').submit(function(e){
            e.preventDefault();
            start_load();
            $.ajax({
                url: 'ajax.php?action=save_payment',
                data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                success: function(resp){
                    if(resp == 1){
                        alert_toast("Data successfully saved.", 'success');
                        setTimeout(function(){ location.reload(); }, 1000);
                    }
                }
            });
        });
    });
</script>
