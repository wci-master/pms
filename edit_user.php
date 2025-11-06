<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include './config/connection.php';
include_once './config/roles.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
if (!isRole(ROLE_ADMIN)) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$message = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: admin_users.php");
    exit;
}

// fetch user
$stmt = $con->prepare("SELECT id, display_name, user_name, email, profile_picture, role FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    header("Location: admin_users.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $display_name = trim($_POST['display_name'] ?? '');
    $user_name = trim($_POST['user_name'] ?? '');
    $role = $_POST['role'] ?? ROLE_PATIENT;
    $newPassword = $_POST['password'] ?? '';

    if ($display_name === '' || $user_name === '') {
        $error = 'Please fill in required fields.';
    } else {
        try {
            // ensure username uniqueness (excluding current user)
            $chk = $con->prepare("SELECT COUNT(*) FROM users WHERE user_name = :user_name AND id != :id");
            $chk->execute([':user_name' => $user_name, ':id' => $id]);
            if ($chk->fetchColumn() > 0) {
                $error = 'Username already in use by another user.';
            } else {
                // handle profile upload
                $profile_picture = $user['profile_picture'] ?? 'default.png';
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $tmp = $_FILES['profile_picture']['tmp_name'];
                    $orig = $_FILES['profile_picture']['name'];
                    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                    $allowed = ['jpg','jpeg','png','gif'];
                    if (in_array($ext, $allowed, true)) {
                        $newName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        $destDir = __DIR__ . DIRECTORY_SEPARATOR . 'user_images' . DIRECTORY_SEPARATOR;
                        if (!is_dir($destDir)) @mkdir($destDir, 0755, true);
                        if (move_uploaded_file($tmp, $destDir . $newName)) {
                            $profile_picture = $newName;
                        }
                    }
                }

                // build update query
                $params = [
                    ':display_name' => $display_name,
                    ':user_name' => $user_name,
                    ':profile_picture' => $profile_picture,
                    ':role' => $role,
                    ':id' => $id
                ];
                if ($newPassword !== '') {
                    $params[':password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET display_name = :display_name, user_name = :user_name, password = :password, profile_picture = :profile_picture, role = :role WHERE id = :id";
                } else {
                    $sql = "UPDATE users SET display_name = :display_name, user_name = :user_name, profile_picture = :profile_picture, role = :role WHERE id = :id";
                }

                $upd = $con->prepare($sql);
                $upd->execute($params);

                $message = 'User updated successfully.';
                // refresh data
                $stmt->execute([':id' => $id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (Exception $ex) {
            $error = 'Error: ' . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <title>Edit User - Hospital IMS</title>
</head>
<body class="hold-transition sidebar-mini dark-mode layout-fixed layout-navbar-fixed">
<?php include './config/sidebar.php'; ?>

<div class="content-wrapper p-4">
  <div class="card">
    <div class="card-header"><h3 class="card-title">Edit User</h3></div>
    <div class="card-body">
      <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message);?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error);?></div><?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label>Full name</label>
              <input type="text" name="display_name" class="form-control" required value="<?php echo htmlspecialchars($user['display_name']);?>">
            </div>
            <div class="form-group"><label>Username</label>
              <input type="text" name="user_name" class="form-control" required value="<?php echo htmlspecialchars($user['user_name']);?>">
            </div>
            <div class="form-group"><label>Role</label>
              <select name="role" class="form-control" required>
                <option value="<?php echo ROLE_ADMIN; ?>" <?php echo $user['role']===ROLE_ADMIN?'selected':''; ?>>Admin</option>
                <option value="<?php echo ROLE_DOCTOR; ?>" <?php echo $user['role']===ROLE_DOCTOR?'selected':''; ?>>Doctor</option>
                <option value="<?php echo ROLE_NURSE; ?>" <?php echo $user['role']===ROLE_NURSE?'selected':''; ?>>Nurse</option>
                <option value="<?php echo ROLE_PHARMACY; ?>" <?php echo $user['role']===ROLE_PHARMACY?'selected':''; ?>>Pharmacy</option>
                <option value="<?php echo ROLE_PATIENT; ?>" <?php echo $user['role']===ROLE_PATIENT?'selected':''; ?>>Patient</option>
              </select>
            </div>
            <div class="form-group"><label>New Password (leave blank to keep current)</label>
              <input type="password" name="password" class="form-control" autocomplete="new-password">
            </div>
            <div class="form-group"><label>Profile picture</label>
              <input type="file" name="profile_picture" class="form-control-file">
              <small>Current: <img src="user_images/<?php echo htmlspecialchars($user['profile_picture']?:'default.png');?>" style="width:40px;height:40px;border-radius:50%"></small>
            </div>
            <div class="form-group">
              <button name="update_user" class="btn btn-primary">Update User</button>
              <a href="admin_users.php" class="btn btn-secondary">Back</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include './config/footer.php'; ?>
<?php include './config/site_js_links.php'; ?>
</body>
</html>