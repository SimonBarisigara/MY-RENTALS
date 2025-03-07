<?php
session_start();
ini_set('display_errors', 1);
Class Action {
	private $db;

	public function __construct() {
		ob_start();
   	include 'db_connect.php';
    
    $this->db = $conn;
	}
	function __destruct() {
	    $this->db->close();
	    ob_end_flush();
	}

	function login(){
		
			extract($_POST);		
			$qry = $this->db->query("SELECT * FROM users where username = '".$username."' and password = '".md5($password)."' ");
			if($qry->num_rows > 0){
				foreach ($qry->fetch_array() as $key => $value) {
					if($key != 'passwors' && !is_numeric($key))
						$_SESSION['login_'.$key] = $value;
				}
				if($_SESSION['login_type'] != 1){
					foreach ($_SESSION as $key => $value) {
						unset($_SESSION[$key]);
					}
					return 2 ;
					exit;
				}
					return 1;
			}else{
				return 3;
			}
	}
	function login2(){
		
			extract($_POST);
			if(isset($email))
				$username = $email;
		$qry = $this->db->query("SELECT * FROM users where username = '".$username."' and password = '".md5($password)."' ");
		if($qry->num_rows > 0){
			foreach ($qry->fetch_array() as $key => $value) {
				if($key != 'passwors' && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
			if($_SESSION['login_alumnus_id'] > 0){
				$bio = $this->db->query("SELECT * FROM alumnus_bio where id = ".$_SESSION['login_alumnus_id']);
				if($bio->num_rows > 0){
					foreach ($bio->fetch_array() as $key => $value) {
						if($key != 'passwors' && !is_numeric($key))
							$_SESSION['bio'][$key] = $value;
					}
				}
			}
			if($_SESSION['bio']['status'] != 1){
					foreach ($_SESSION as $key => $value) {
						unset($_SESSION[$key]);
					}
					return 2 ;
					exit;
				}
				return 1;
		}else{
			return 3;
		}
	}
	function logout(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:login.php");
	}
	function logout2(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:../index.php");
	}

	function save_user(){
		extract($_POST);
		$data = " name = '$name' ";
		$data .= ", username = '$username' ";
		if(!empty($password))
		$data .= ", password = '".md5($password)."' ";
		$data .= ", type = '$type' ";
		if($type == 1)
			$establishment_id = 0;
			$establishment_id = isset($_POST['establishment_id']) ? $_POST['establishment_id'] : null;
			$chk = $this->db->query("Select * from users where username = '$username' and id !='$id' ")->num_rows;
		if($chk > 0){
			return 2;
			exit;
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set ".$data);
		}else{
			$save = $this->db->query("UPDATE users set ".$data." where id = ".$id);
		}
		if($save){
			return 1;
		}
	}
	function delete_user(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM users where id = ".$id);
		if($delete)
			return 1;
	}
	function signup(){
		extract($_POST);
		$data = " name = '".$firstname.' '.$lastname."' ";
		$data .= ", username = '$email' ";
		$data .= ", password = '".md5($password)."' ";
		$chk = $this->db->query("SELECT * FROM users where username = '$email' ")->num_rows;
		if($chk > 0){
			return 2;
			exit;
		}
			$save = $this->db->query("INSERT INTO users set ".$data);
		if($save){
			$uid = $this->db->insert_id;
			$data = '';
			foreach($_POST as $k => $v){
				if($k =='password')
					continue;
				if(empty($data) && !is_numeric($k) )
					$data = " $k = '$v' ";
				else
					$data .= ", $k = '$v' ";
			}
			if($_FILES['img']['tmp_name'] != ''){
							$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
							$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
							$data .= ", avatar = '$fname' ";

			}
			$save_alumni = $this->db->query("INSERT INTO alumnus_bio set $data ");
			if($data){
				$aid = $this->db->insert_id;
				$this->db->query("UPDATE users set alumnus_id = $aid where id = $uid ");
				$login = $this->login2();
				if($login)
				return 1;
			}
		}
	}
	function update_account(){
		extract($_POST);
		$data = " name = '".$firstname.' '.$lastname."' ";
		$data .= ", username = '$email' ";
		if(!empty($password))
		$data .= ", password = '".md5($password)."' ";
		$chk = $this->db->query("SELECT * FROM users where username = '$email' and id != '{$_SESSION['login_id']}' ")->num_rows;
		if($chk > 0){
			return 2;
			exit;
		}
			$save = $this->db->query("UPDATE users set $data where id = '{$_SESSION['login_id']}' ");
		if($save){
			$data = '';
			foreach($_POST as $k => $v){
				if($k =='password')
					continue;
				if(empty($data) && !is_numeric($k) )
					$data = " $k = '$v' ";
				else
					$data .= ", $k = '$v' ";
			}
			if($_FILES['img']['tmp_name'] != ''){
							$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
							$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
							$data .= ", avatar = '$fname' ";

			}
			$save_alumni = $this->db->query("UPDATE alumnus_bio set $data where id = '{$_SESSION['bio']['id']}' ");
			if($data){
				foreach ($_SESSION as $key => $value) {
					unset($_SESSION[$key]);
				}
				$login = $this->login2();
				if($login)
				return 1;
			}
		}
	}

	function save_settings(){
		extract($_POST);
		$data = " name = '".str_replace("'","&#x2019;",$name)."' ";
		$data .= ", email = '$email' ";
		$data .= ", contact = '$contact' ";
		$data .= ", about_content = '".htmlentities(str_replace("'","&#x2019;",$about))."' ";
		if($_FILES['img']['tmp_name'] != ''){
						$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
						$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
					$data .= ", cover_img = '$fname' ";

		}
		
		// echo "INSERT INTO system_settings set ".$data;
		$chk = $this->db->query("SELECT * FROM system_settings");
		if($chk->num_rows > 0){
			$save = $this->db->query("UPDATE system_settings set ".$data);
		}else{
			$save = $this->db->query("INSERT INTO system_settings set ".$data);
		}
		if($save){
		$query = $this->db->query("SELECT * FROM system_settings limit 1")->fetch_array();
		foreach ($query as $key => $value) {
			if(!is_numeric($key))
				$_SESSION['system'][$key] = $value;
		}

			return 1;
				}
	}

	
	function save_category(){
		extract($_POST);
		$data = " name = '$name' ";
			if(empty($id)){
				$save = $this->db->query("INSERT INTO categories set $data");
			}else{
				$save = $this->db->query("UPDATE categories set $data where id = $id");
			}
		if($save)
			return 1;
	}
	function delete_category(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM categories where id = ".$id);
		if($delete){
			return 1;
		}
	}

	function save_tenant(){
		extract($_POST);
		$data = " firstname = '$firstname' ";
		$data .= ", lastname = '$lastname' ";
		$data .= ", middlename = '$middlename' ";
		$data .= ", email = '$email' ";
		$data .= ", contact = '$contact' ";
		$data .= ", house_id = '$house_id' ";
		$data .= ", date_in = '$date_in' ";
			if(empty($id)){
				
				$save = $this->db->query("INSERT INTO tenants set $data");
			}else{
				$save = $this->db->query("UPDATE tenants set $data where id = $id");
			}
		if($save)
			return 1;
	}
	function delete_tenant(){
		extract($_POST);
		$delete = $this->db->query("UPDATE tenants set status = 0 where id = ".$id);
		if($delete){
			return 1;
		}
	}
	public function get_tdetails() {
		include 'db_connect.php';
	
		if (!isset($_POST['id']) || empty($_POST['id'])) {
			echo json_encode(['error' => 'Invalid tenant ID']);
			exit;
		}
	
		$id = intval($_POST['id']);
	
		$qry = $this->db->query("SELECT t.*, CONCAT(t.lastname, ', ', t.firstname, ' ', t.middlename) AS name, h.house_no, h.price 
								 FROM tenants t 
								 INNER JOIN houses h ON h.id = t.house_id 
								 WHERE t.id = {$id}");
	
		if ($qry->num_rows == 0) {
			echo json_encode(['error' => 'Tenant not found']);
			exit;
		}
	
		$row = $qry->fetch_assoc();
		
		// Calculate months
		$months = abs(strtotime(date('Y-m-d') . " 23:59:59") - strtotime($row['date_in'] . " 23:59:59"));
		$months = floor($months / (30 * 60 * 60 * 24));
	
		// Calculate payable rent
		$payable = $row['price'] * $months;
	
		// Fetch payments
		$paid_qry = $this->db->query("SELECT SUM(amount) AS paid FROM payments WHERE tenant_id = {$id}");
		$paid = ($paid_qry->num_rows > 0) ? $paid_qry->fetch_assoc()['paid'] : 0;
	
		$last_payment_qry = $this->db->query("SELECT date_created FROM payments WHERE tenant_id = {$id} ORDER BY date_created DESC LIMIT 1");
		$last_payment = ($last_payment_qry->num_rows > 0) ? date("M d, Y", strtotime($last_payment_qry->fetch_assoc()['date_created'])) : 'N/A';
	
		// Handle NULL values
		$data = [
			'name' => ucwords($row['name']),
			'house_no' => $row['house_no'],
			'price' => number_format($row['price'] ?? 0, 2),
			'months' => $months,
			'payable' => number_format($payable ?? 0, 2),
			'paid' => number_format($paid ?? 0, 2),
			'outstanding' => number_format(($payable - $paid) ?? 0, 2),
			'last_payment' => $last_payment,
			'rent_started' => date('M d, Y', strtotime($row['date_in']))
		];
	
		echo json_encode($data);
		exit;
	}
	
	
	function save_payment(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','ref_code')) && !is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO payments set $data");
			$id=$this->db->insert_id;
		}else{
			$save = $this->db->query("UPDATE payments set $data where id = $id");
		}

		if($save){
			return 1;
		}
	}
	function delete_payment(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM payments where id = ".$id);
		if($delete){
			return 1;
		}
	}

}