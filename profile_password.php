<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require './config/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['current_password'], $_POST['new_password'], $_POST['confirm_password'])) {
    $uid = (int)$_SESSION['user_id'];
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new === '' || $confirm === '' || $current === '') {
        $error = 'All fields are required.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        // fetch current password
        $stmt = $con->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $uid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $error = 'User not found.';
        } else {
            // compare using password_verify for new passwords and md5 for old ones
            if (password_verify($current, $row['password']) || $row['password'] === md5($current)) {
                $upd = $con->prepare("UPDATE users SET password = :password WHERE id = :id");
                $upd->execute([':password' => password_hash($new, PASSWORD_DEFAULT), ':id' => $uid]);
                $message = 'Password changed successfully.';
            } else {
                $error = 'Current password is incorrect.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <title>Change Password - Hospital IMS</title>
</head>
<body class="hold-transition sidebar-mini dark-mode layout-fixed layout-navbar-fixed">
<?php include './config/sidebar.php'; ?>

<div class="content-wrapper p-4">
  <div class="card">
    <div class="card-header"><h3 class="card-title">Change Password</h3></div>
    <div class="card-body">
      <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message);?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error);?></div><?php endif; ?>
      <form method="post">
        <div class="form-group">
          <label>Current password</label>
          <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="form-group">
          <label>New password</label>
          <input type="password" name="new_password" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Confirm new password</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <div class="form-group">
          <button class="btn btn-primary">Change Password</button>
          <a href="./" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include './config/footer.php'; ?>
<?php include './config/site_js_links.php'; ?>
</body>
</html>