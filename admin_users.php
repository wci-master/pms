<?php
if (session_status() === PHP_SESSION_NONE) session_start();

include './config/connection.php';
include_once './config/roles.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// only admin allowed
if (!isRole(ROLE_ADMIN)) {
    header("Location: dashboard.php");
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $display_name = trim($_POST['display_name'] ?? '');
    $user_name = trim($_POST['user_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? ROLE_PATIENT;

    if ($display_name === '' || $user_name === '' || $password === '' || $email === '') {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            // check username uniqueness
            $stmt = $con->prepare("SELECT COUNT(*) FROM `users` WHERE `user_name` = :user_name OR `email` = :email");
            $stmt->bindParam(':user_name', $user_name);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $error = 'Username or email already exists. Choose another username or email.';
            } else {
                // handle profile picture upload
                $profile_picture = 'default.png';
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

                $hashed = password_hash($password, PASSWORD_DEFAULT);

                $ins = $con->prepare("INSERT INTO `users` (`display_name`,`user_name`,`email`,`password`,`profile_picture`,`role`) VALUES (:display_name,:user_name,:email,:password,:profile_picture,:role)");
                $ins->bindParam(':display_name', $display_name);
                $ins->bindParam(':user_name', $user_name);
                $ins->bindParam(':email', $email);
                $ins->bindParam(':password', $hashed);
                $ins->bindParam(':profile_picture', $profile_picture);
                $ins->bindParam(':role', $role);
                $ins->execute();

                $message = 'User created successfully.';
                // redirect to avoid resubmit
                header("Location: admin_users.php?message=" . urlencode($message));
                exit;
            }
        } catch (PDOException $ex) {
            $error = 'Database error: ' . $ex->getMessage();
        }
    }
}

// fetch message from redirect
if (isset($_GET['message'])) $message = htmlspecialchars($_GET['message']);

$users = [];
try {
    $stmt = $con->query("SELECT `id`,`display_name`,`user_name`,`profile_picture`,`role` FROM `users` ORDER BY `id` DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ignore, $users remains empty
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php' ?>
  <title>Manage Users - Hospital IMS</title>
</head>
<body class="hold-transition sidebar-mini dark-mode layout-fixed layout-navbar-fixed">
<?php include './config/sidebar.php'; ?>

<div class="content-wrapper">
  <section class="content p-4">
    <div class="container-fluid">
      <div class="card card-primary card-outline">
        <div class="card-header">
          <h3 class="card-title">Manage Users</h3>
        </div>
        <div class="card-body">
          <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
          <?php endif; ?>
          <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
          <?php endif; ?>

          <form method="post" enctype="multipart/form-data" class="mb-4">
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Full name</label>
                  <input type="text" name="display_name" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Username</label>
                  <input type="text" name="user_name" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Email</label>
                  <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Password</label>
                  <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Role</label>
                  <select name="role" class="form-control" required>
                    <option value="<?php echo ROLE_ADMIN; ?>">Admin</option>
                    <option value="<?php echo ROLE_DOCTOR; ?>">Doctor</option>
                    <option value="<?php echo ROLE_NURSE; ?>">Nurse</option>
                    <option value="<?php echo ROLE_PHARMACY; ?>">Pharmacy</option>
                    <option value="<?php echo ROLE_PATIENT; ?>">Patient</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Profile picture (optional)</label>
                  <input type="file" name="profile_picture" class="form-control-file">
                </div>
                <div class="form-group">
                  <button name="create_user" class="btn btn-primary">Create User</button>
                </div>
              </div>

              <div class="col-md-8">
                <h5>Existing users</h5>
                <table class="table table-sm table-striped table-bordered">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Avatar</th>
                      <th>Name</th>
                      <th>Username</th>
                      <th>Role</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (count($users) === 0): ?>
                      <tr><td colspan="6" class="text-center">No users found</td></tr>
                    <?php else: ?>
                      <?php foreach ($users as $u): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($u['id']); ?></td>
                          <td><img src="user_images/<?php echo htmlspecialchars($u['profile_picture'] ?: 'default.png'); ?>" alt="avatar" style="width:40px;height:40px;object-fit:cover;border-radius:50%"></td>
                          <td><?php echo htmlspecialchars($u['display_name']); ?></td>
                          <td><?php echo htmlspecialchars($u['user_name']); ?></td>
                          <td><?php echo htmlspecialchars(ucfirst($u['role'])); ?></td>
                          <td>
                            <a href="edit_user.php?id=<?php echo (int)$u['id'];?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-user" data-id="<?php echo (int)$u['id']; ?>" data-name="<?php echo htmlspecialchars($u['display_name']); ?>">Delete</button>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </form>

        </div>
      </div>
    </div>
  </section>
</div>

<?php include './config/footer.php'; ?>
<?php include './config/site_js_links.php'; ?>
<script>
$(document).ready(function() {
    $('.delete-user').click(function() {
        const userId = $(this).data('id');
        const userName = $(this).data('name');
        const $row = $(this).closest('tr');
        
        if (confirm(`Are you sure you want to delete user "${userName}"? This cannot be undone.`)) {
            $.ajax({
                url: 'delete_user.php',
                type: 'POST',
                data: {user_id: userId},
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(400, function() {
                            $(this).remove();
                        });
                        alert(response.message || 'User deleted successfully');
                    } else {
                        alert(response.message || 'Error deleting user');
                    }
                },
                error: function() {
                    alert('Error occurred while deleting user');
                }
            });
        }
    });
});
</script>
</body>
</html>