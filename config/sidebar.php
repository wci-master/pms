<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/roles.php';

if(!(isset($_SESSION['user_id']))) {
  header("location:index.php");
  exit;
}
?>
<aside class="main-sidebar sidebar-dark-primary bg-black elevation-4">
    <a href="./" class="brand-link logo-switch bg-black">
      <h4 class="brand-image-xl logo-xs mb-0 text-center"><b>IMS</b></h4>
      <h4 class="brand-image-xl logo-xl mb-0 text-center">Hospital <b>IMS</b></h4>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img 
          src="user_images/<?php echo $_SESSION['profile_picture'];?>" class="img-circle elevation-2" alt="User Image" />
        </div>
        <div class="info">
          <a href="#" class="d-block"><?php echo $_SESSION['display_name'];?></a>
        </div>
      </div>

      
      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

          <?php if (isRole(ROLE_ADMIN)): ?>
            <li class="nav-item"><a href="admin_users.php" class="nav-link"><i class="nav-icon fas fa-users-cog"></i><p>Manage Users</p></a></li>
          <?php endif; ?>

          <?php if (userHasAnyRole([ROLE_ADMIN, ROLE_DOCTOR, ROLE_NURSE])): ?>
            <li class="nav-item"><a href="patients.php" class="nav-link"><i class="nav-icon fas fa-user-injured"></i><p>Patients</p></a></li>
            <li class="nav-item"><a href="visits.php" class="nav-link"><i class="nav-icon fas fa-notes-medical"></i><p>Visits</p></a></li>
            <li class="nav-item"><a href="new_prescription.php" class="nav-link"><i class="nav-icon fas fa-prescription"></i><p>Prescriptions</p></a></li>
          <?php endif; ?>

          <?php if (userHasAnyRole([ROLE_ADMIN, ROLE_PHARMACY])): ?>
            <li class="nav-item"><a href="medicines.php" class="nav-link"><i class="nav-icon fas fa-pills"></i><p>Pharmacy</p></a></li>
          <?php endif; ?>

          <?php if (isRole(ROLE_PATIENT)): ?>
            <li class="nav-item"><a href="my_records.php" class="nav-link"><i class="nav-icon fas fa-file-medical"></i><p>My Records</p></a></li>
          <?php endif; ?>

          <!-- Change password available to all logged-in users -->
          <li class="nav-item">
            <a href="profile_password.php" class="nav-link">
              <i class="nav-icon fas fa-key"></i>
              <p>Change Password</p>
            </a>
          </li>

          <!-- Logout -->
          <li class="nav-item mt-3">
            <a href="logout.php" class="nav-link">
              <i class="nav-icon fas fa-sign-out-alt"></i>
              <p>Logout</p>
            </a>
          </li>

        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>