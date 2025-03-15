<?php
ob_start();
header('Content-Type: application/json');

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'save_category') {
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $chk = $conn->query("SELECT * FROM expense_categories WHERE category_name = '$name'")->num_rows;
        if ($chk > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Category already exists']);
        } else {
            $save = $conn->query("INSERT INTO expense_categories (category_name) VALUES ('$name')");
            if ($save) {
                $id = $conn->insert_id;
                echo json_encode(['status' => 'success', 'id' => $id]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to save category']);
            }
        }
    } elseif ($_GET['action'] == 'save_item') {
        $category_id = intval($_POST['category_id']);
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $unit_cost = floatval($_POST['unit_cost']);
        $chk = $conn->query("SELECT * FROM expense_items WHERE item_name = '$name' AND category_id = $category_id")->num_rows;
        if ($chk > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Item already exists in this category']);
        } else {
            $save = $conn->query("INSERT INTO expense_items (category_id, item_name, unit_cost) VALUES ($category_id, '$name', $unit_cost)");
            if ($save) {
                echo json_encode(['status' => 'success', 'id' => $conn->insert_id]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to save item']);
            }
        }
    } elseif ($_GET['action'] == 'update_item_cost') {
        $item_id = intval($_POST['item_id']);
        $unit_cost = floatval($_POST['unit_cost']);
        $update = $conn->query("UPDATE expense_items SET unit_cost = $unit_cost WHERE item_id = $item_id");
        if ($update) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update unit cost']);
        }
    }
}
$action = $_GET['action'];
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'admin_class.php';
$crud = new Action();
if($action == 'login'){
	$login = $crud->login();
	if($login)
		echo $login;
}
if($action == 'login2'){
	$login = $crud->login2();
	if($login)
		echo $login;
}
if($action == 'logout'){
	$logout = $crud->logout();
	if($logout)
		echo $logout;
}
if($action == 'logout2'){
	$logout = $crud->logout2();
	if($logout)
		echo $logout;
}
if($action == 'save_user'){
	$save = $crud->save_user();
	if($save)
		echo $save;
}
if($action == 'delete_user'){
	$save = $crud->delete_user();
	if($save)
		echo $save;
}
if($action == 'signup'){
	$save = $crud->signup();
	if($save)
		echo $save;
}
if($action == 'update_account'){
	$save = $crud->update_account();
	if($save)
		echo $save;
}
if($action == "save_settings"){
	$save = $crud->save_settings();
	if($save)
		echo $save;
}
if($action == "save_category"){
	$save = $crud->save_category();
	if($save)
		echo $save;
}

if($action == "delete_category"){
	$delete = $crud->delete_category();
	if($delete)
		echo $delete;
}

if($action == "save_tenant"){
	$save = $crud->save_tenant();
	if($save)
		echo $save;
}
if($action == "delete_tenant"){
	$save = $crud->delete_tenant();
	if($save)
		echo $save;
}
if($action == "get_tdetails"){
	$get = $crud->get_tdetails();
	if($get)
		echo $get;
}

if($action == "save_payment"){
	$save = $crud->save_payment();
	if($save)
		echo $save;
}
if($action == "delete_payment"){
	$save = $crud->delete_payment();
	if($save)
		echo $save;
}

ob_end_flush();
?>
