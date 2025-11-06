<?php 
	include './config/connection.php';
	if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_name'], $_POST['password'])) {
    $userName = trim($_POST['user_name']);
    $password = $_POST['password'];

    // Use prepared statement and fetch role
    $sql = "SELECT id, display_name, user_name, password, profile_picture, role FROM users WHERE user_name = :user_name LIMIT 1";
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':user_name', $userName);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // password_verify is for new passwords, md5 is for old ones
        if (password_verify($password, $user['password']) || $user['password'] === md5($password)) {
            // If it was an md5 password, rehash it and update the database
            if ($user['password'] === md5($password)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $con->prepare("UPDATE users SET password = :password WHERE id = :id");
                $updateStmt->execute([':password' => $newHash, ':id' => $user['id']]);
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['display_name'] = $user['display_name'];
            $_SESSION['user_name'] = $user['user_name'];
            $_SESSION['profile_picture'] = $user['profile_picture'];
            $_SESSION['role'] = $user['role']; // <-- store role
            header('Location: dashboard.php');
            exit;
        }
    }
    $login_error = 'Incorrect username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Hospital Information Management System</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">

  <style>
  	
  	.login-box {
    	width: 430px;
	}
  
#system-logo {
    width: 5em !important;
    height: 5em !important;
    object-fit: cover;
    object-position: center center;
}
  </style>
</head>
<body class="hold-transition login-page dark-mode">
<div class="login-box">
  <div class="login-logo mb-4">
    <img src="dist/img/logo.jpg" class="img-thumbnail p-0 border rounded-circle" id="system-logo">
    <div class="text-center h3 mb-0">Application of Mobile Cloud Computing in Healthcare Pathways</div>
  </div>
  <!-- /.login-logo -->
  <div class="card card-outline card-primary rounded-0 shadow">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Please enter your login credentials</p>
      <form method="post">
        <div class="input-group mb-3">
          <input type="text" class="form-control form-control-lg rounded-0 autofocus" 
          placeholder="Username" id="user_name" name="user_name">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" class="form-control form-control-lg rounded-0" 
          placeholder="Password" id="password" name="password">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <button name="login" type="submit" class="btn btn-primary rounded-0 btn-block">Sign In</button>
          </div>
          <!-- /.col -->
        </div>

        <div class="row">
          <div class="col-md-12">
            <p class="text-danger">
              <?php 
              if($message != '') {
                echo $message;
              }
              ?>
            </p>
          </div>
        </div>
      </form>

      
    </div>
    <!-- /.login-card-body -->
  </div>
</div>
<!-- /.login-box -->

<!-- jQuery -->

</body>
</html>
