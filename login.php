<!DOCTYPE html>
<html lang="en">
<?php 
session_start();
include('./db_connect.php');
ob_start();

if(!isset($_SESSION['system'])) {
    $query = $conn->query("SELECT * FROM system_settings LIMIT 1");
    if ($query && $query->num_rows > 0) { 
        $system = $query->fetch_assoc();  
        foreach($system as $k => $v){
            $_SESSION['system'][$k] = $v;
        }
    } else {
        $_SESSION['system'] = ["name" => "Rental Management System"];
    }
}   
ob_end_flush();
?>
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?php echo $_SESSION['system']['name'] ?></title>
  
  <?php include('./header.php'); ?>

  <?php 
  if(isset($_SESSION['login_id']))
    header("location:index.php?page=home");
  ?>

  <!-- FontAwesome for Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <style>
    /* ---- General Styling ---- */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      height: 100vh;
      background-image: url('assets/uploads/background.jpg');
      background-size: cover;
      background-position: center;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* ---- Login Container ---- */
    .login-container {
      background: rgba(255, 255, 255, 0.1);
      padding: 40px;
      border-radius: 15px;
      backdrop-filter: blur(10px);
      box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
      width: 400px;
      text-align: center;
      animation: fadeIn 0.8s ease-in-out;
    }

    .login-container h2 {
      color: white;
      margin-bottom: 20px;
      font-size: 28px;
      font-weight: bold;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }

    .login-container .form-group {
      margin-bottom: 20px;
      text-align: left;
    }

    .login-container label {
      color: white;
      font-weight: bold;
      font-size: 14px;
      display: block;
      margin-bottom: 5px;
    }

    .login-container input {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 5px;
      outline: none;
      font-size: 16px;
      background: rgba(255, 255, 255, 0.2);
      color: white;
      transition: background 0.3s ease;
    }

    .login-container input:focus {
      background: rgba(255, 255, 255, 0.3);
    }

    .login-container input::placeholder {
      color: rgba(255, 255, 255, 0.7);
    }

    /* ---- Login Button ---- */
    .login-container button {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 5px;
      font-size: 18px;
      background: #007bff;
      color: white;
      font-weight: bold;
      cursor: pointer;
      transition: 0.3s ease-in-out;
    }

    .login-container button:hover {
      background: #0056b3;
    }

    .login-container .loading {
      display: none;
      font-size: 16px;
      margin-top: 10px;
      color: white;
    }

    /* ---- Error Alert ---- */
    .alert {
      padding: 10px;
      background: #ffebee;
      color: #c62828;
      border-radius: 5px;
      margin-bottom: 15px;
    }

    /* ---- Animations ---- */
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* ---- Rental System Theme ---- */
    .login-container::before {
      content: "🏠";
      font-size: 50px;
      display: block;
      margin-bottom: 15px;
    }

    /* ---- Mobile Responsive ---- */
    @media (max-width: 450px) {
      .login-container {
        width: 90%;
        padding: 30px;
      }
    }
  </style>
</head>

<body>

  <div class="login-container">
    <h2><?php echo $_SESSION['system']['name'] ?></h2>

    <form id="login-form">
      <div class="form-group">
        <label for="username"><i class="fas fa-user"></i> Username</label>
        <input type="text" id="username" name="username" placeholder="Enter Username" required>
      </div>

      <div class="form-group">
        <label for="password"><i class="fas fa-lock"></i> Password</label>
        <input type="password" id="password" name="password" placeholder="Enter Password" required>
      </div>

      <button type="submit">Login</button>
      <p class="loading"><i class="fas fa-spinner fa-spin"></i> Verifying...</p>
    </form>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $('#login-form').submit(function(e){
      e.preventDefault();
      $('button').attr('disabled', true);
      $('.loading').show();

      if ($(this).find('.alert').length > 0) {
        $(this).find('.alert').remove();
      }

      $.ajax({
        url: 'ajax.php?action=login',
        method: 'POST',
        data: $(this).serialize(),
        error: function(err) {
          console.log(err);
          $('button').attr('disabled', false);
          $('.loading').hide();
        },
        success: function(resp) {
          if (resp == 1) {
            location.href = 'index.php?page=home';
          } else {
            $('#login-form').prepend('<div class="alert">Username or password is incorrect.</div>');
            $('button').attr('disabled', false);
            $('.loading').hide();
          }
        }
      });
    });
  </script>

</body>
</html>