<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include './config/connection.php';
include_once './config/roles.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Allow admin and doctors to view any patient (via ?patient_id=ID).
// Patients can view only their own records.
$canViewAny = userHasAnyRole([ROLE_ADMIN, ROLE_DOCTOR]);

$patientId = 0;
if ($canViewAny && isset($_GET['patient_id'])) {
    $patientId = (int) $_GET['patient_id'];
} elseif (isRole(ROLE_PATIENT)) {
    if (!isset($_SESSION['user_name']) || strpos($_SESSION['user_name'], 'patient') !== 0) {
        die('Invalid patient account.');
    }
    $patientId = (int) substr($_SESSION['user_name'], 7);
} else {
    // Not allowed: redirect to patients list (staff without permission) or home
    header('Location: patients.php');
    exit;
}

if ($patientId <= 0) {
    die('Invalid patient id.');
}

// Fetch patient record including assigned doctor's name
$stmt = $con->prepare("
    SELECT p.*, u.display_name AS assigned_doctor_name
    FROM patients p
    LEFT JOIN users u ON u.id = p.assigned_doctor_id
    WHERE p.id = :pid
    LIMIT 1
");
$stmt->execute([':pid' => $patientId]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$patient) {
    die('Patient not found.');
}

// Fetch appointments/visits for this patient
$visitStmt = $con->prepare("SELECT pv.* FROM patient_visits pv WHERE pv.patient_id = :pid ORDER BY pv.appointment_date DESC, pv.visit_date DESC");
$visitStmt->execute([':pid' => $patientId]);
$visits = $visitStmt->fetchAll(PDO::FETCH_ASSOC);

// Optionally fetch prescriptions count or latest prescription
$presStmt = $con->prepare("SELECT COUNT(*) FROM patient_medication_history pmh WHERE pmh.patient_visit_id IN (SELECT id FROM patient_visits WHERE patient_id = :pid)");
$presStmt->execute([':pid' => $patientId]);
$prescriptionsCount = (int)$presStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <title>Patient Record - <?php echo htmlspecialchars($patient['patient_name']); ?></title>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
<?php include './config/header.php'; include './config/sidebar.php'; ?>

<div class="content-wrapper p-4">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title">Patient Record — <?php echo htmlspecialchars($patient['patient_name']); ?></h3>
      <?php if ($canViewAny): ?>
        <a href="patients.php" class="btn btn-sm btn-secondary">Back to Patients</a>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <h5>Patient Information</h5>
          <p>
            <strong>Name:</strong> <?php echo htmlspecialchars($patient['patient_name']); ?><br>
            <strong>ID:</strong> <?php echo (int)$patient['id']; ?><br>
            <strong>Registration Date:</strong> <?php echo htmlspecialchars($patient['registration_date'] ?? ''); ?><br>
            <strong>Assigned Doctor:</strong> <?php echo htmlspecialchars($patient['assigned_doctor_name'] ?? 'Unassigned'); ?><br>
            <strong>Admission:</strong> <?php echo htmlspecialchars($patient['admission_date'] ?? ''); ?><br>
            <strong>Discharge:</strong> <?php echo htmlspecialchars($patient['discharge_date'] ?? ''); ?><br>
          </p>
        </div>
        <div class="col-md-6">
          <h5>Medical Details</h5>
          <p>
            <strong>Allergies:</strong> <?php echo nl2br(htmlspecialchars($patient['allergies'] ?? 'None')); ?><br>
            <strong>Existing Conditions:</strong> <?php echo nl2br(htmlspecialchars($patient['existing_conditions'] ?? 'None')); ?><br>
            <strong>Medical History:</strong> <?php echo nl2br(htmlspecialchars($patient['medical_history'] ?? '')); ?><br>
            <strong>Prescriptions Count:</strong> <?php echo $prescriptionsCount; ?><br>
          </p>
        </div>
      </div>

      <hr>
      <h5>Appointments / Visits</h5>
      <div class="table-responsive">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>#</th>
              <th>Appt Date</th>
              <th>Type</th>
              <th>Reason</th>
              <th>Status</th>
              <th>Booked On</th>
              <?php if ($canViewAny): ?><th>Action</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($visits)): ?>
              <tr><td colspan="<?php echo $canViewAny ? 7 : 6; ?>" class="text-center">No appointments found</td></tr>
            <?php else: foreach ($visits as $v): ?>
              <tr>
                <td><?php echo (int)$v['id']; ?></td>
                <td><?php echo htmlspecialchars($v['appointment_date'] ?? $v['visit_date']); ?></td>
                <td><?php echo htmlspecialchars($v['appointment_type'] ?? ''); ?></td>
                <td><?php echo nl2br(htmlspecialchars($v['reason'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($v['status'] ?? 'pending')); ?></td>
                <td><?php echo htmlspecialchars($v['booking_date'] ?? ''); ?></td>
                <?php if ($canViewAny): ?>
                  <td>
                    <a href="view_prescription.php?visit_id=<?php echo (int)$v['id']; ?>" class="btn btn-sm btn-primary">View Prescription</a>
                    <a href="visits.php?visit_id=<?php echo (int)$v['id']; ?>" class="btn btn-sm btn-secondary">View Visit</a>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<?php include './config/footer.php'; include './config/site_js_links.php'; ?>
</body>
</html>