<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include './config/connection.php';
include_once './config/roles.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$message = '';
$error = '';

// Helper: get patient id for current logged-in patient (username format: patient{ID})
function currentPatientId() {
    if (!isset($_SESSION['user_name'])) return null;
    $u = $_SESSION['user_name'];
    if (strpos($u, 'patient') === 0) {
        return (int)substr($u, 7);
    }
    return null;
}

// Remove all the complex name detection logic and simply use patient_name
// Fetch patients list for staff (to create appointment for any patient)
$patients = [];
if (userHasAnyRole([ROLE_ADMIN, ROLE_DOCTOR, ROLE_NURSE])) {
    $sqlPatients = "SELECT `id`, `patient_name` FROM `patients` ORDER BY `id` DESC";
    $patients = $con->query($sqlPatients)->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch visits: staff see all, patients see their own
if (userHasAnyRole([ROLE_ADMIN, ROLE_DOCTOR, ROLE_NURSE])) {
    $sqlVisits = "SELECT pv.*, p.patient_name 
                  FROM `patient_visits` pv
                  LEFT JOIN `patients` p ON p.id = pv.patient_id
                  ORDER BY pv.appointment_date DESC, pv.booking_date DESC";
    $visits = $con->query($sqlVisits)->fetchAll(PDO::FETCH_ASSOC);
} else {
    $pid = currentPatientId();
    $stmt = $con->prepare("SELECT pv.*, p.patient_name
                          FROM `patient_visits` pv
                          LEFT JOIN `patients` p ON p.id = pv.patient_id
                          WHERE pv.patient_id = :pid
                          ORDER BY pv.appointment_date DESC");
    $stmt->execute([':pid' => $pid]);
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle booking (patients) or creating (staff)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['create_appointment'])) {
            // determine patient id
            if (isRole(ROLE_PATIENT)) {
                $patient_id = currentPatientId();
            } else {
                $patient_id = (int)($_POST['patient_id'] ?? 0);
            }
            $appointment_date = $_POST['appointment_date'] ?? '';
            $appointment_type = $_POST['appointment_type'] ?? 'check-up';
            $reason = $_POST['reason'] ?? '';

            if (!$patient_id || !$appointment_date) {
                $error = 'Please select a valid patient and appointment date.';
            } else {
                $ins = $con->prepare("INSERT INTO patient_visits (patient_id, visit_date, appointment_date, appointment_type, reason, status, booking_date) VALUES (:patient_id, :visit_date, :appointment_date, :appointment_type, :reason, :status, NOW())");
                $ins->execute([
                    ':patient_id' => $patient_id,
                    ':visit_date' => $appointment_date,
                    ':appointment_date' => $appointment_date,
                    ':appointment_type' => $appointment_type,
                    ':reason' => $reason,
                    ':status' => 'pending'
                ]);
                $message = 'Appointment created successfully.';
            }
        }

        // Update status (staff)
        if (isset($_POST['update_status']) && userHasAnyRole([ROLE_ADMIN, ROLE_DOCTOR, ROLE_NURSE])) {
            $vid = (int)($_POST['visit_id'] ?? 0);
            $newStatus = $_POST['status'] ?? 'pending';
            if ($vid > 0) {
                $upd = $con->prepare("UPDATE patient_visits SET status = :status WHERE id = :id");
                $upd->execute([':status' => $newStatus, ':id' => $vid]);
                $message = 'Appointment status updated.';
            } else {
                $error = 'Invalid visit id.';
            }
        }

        // Cancel appointment (patient or staff)
        if (isset($_POST['cancel_appointment'])) {
            $vid = (int)($_POST['visit_id'] ?? 0);
            if ($vid > 0) {
                if (isRole(ROLE_PATIENT)) {
                    $patient_id = currentPatientId();
                    $chk = $con->prepare("SELECT COUNT(*) FROM patient_visits WHERE id = :id AND patient_id = :pid");
                    $chk->execute([':id' => $vid, ':pid' => $patient_id]);
                    if ($chk->fetchColumn() == 0) {
                        throw new Exception('Not authorized to cancel this appointment.');
                    }
                }
                $upd = $con->prepare("UPDATE patient_visits SET status = 'cancelled' WHERE id = :id");
                $upd->execute([':id' => $vid]);
                $message = 'Appointment cancelled.';
            }
        }

        // Delete appointment (admin/staff only)
        if (isset($_POST['delete_appointment']) && userHasAnyRole([ROLE_ADMIN, ROLE_DOCTOR, ROLE_NURSE])) {
            $vid = (int)($_POST['visit_id'] ?? 0);
            if ($vid > 0) {
                // First delete related records in patient_medication_history if any
                $del1 = $con->prepare("DELETE FROM patient_medication_history WHERE patient_visit_id = :id");
                $del1->execute([':id' => $vid]);
                
                // Then delete the visit record
                $del2 = $con->prepare("DELETE FROM patient_visits WHERE id = :id");
                $del2->execute([':id' => $vid]);
                
                $message = 'Appointment deleted successfully.';
            }
        }

    } catch (Exception $ex) {
        $error = $ex->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include './config/site_css_links.php'; ?>
  <title>Appointments / Visits - Hospital IMS</title>
</head>
<body class="hold-transition sidebar-mini dark-mode layout-fixed layout-navbar-fixed">
<?php include './config/sidebar.php'; ?>

<div class="content-wrapper p-4">
  <div class="card">
    <div class="card-header"><h3 class="card-title">Appointments / Visits</h3></div>
    <div class="card-body">
      <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

      <div class="row">
        <div class="col-md-5">
          <form method="post">
            <div class="form-group">
              <label>Patient</label>
              <?php if (userHasAnyRole([ROLE_ADMIN, ROLE_DOCTOR, ROLE_NURSE])): ?>
                <select name="patient_id" class="form-control" required>
                  <option value="">-- Select patient --</option>
                  <?php foreach ($patients as $p): ?>
                    <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['patient_name'].' ('.$p['id'].')'); ?></option>
                  <?php endforeach; ?>
                </select>
              <?php else:
                $pid = currentPatientId();
                $pinfoStmt = $con->prepare("SELECT id, `patient_name` FROM patients WHERE id = :id LIMIT 1");
                $pinfoStmt->execute([':id' => $pid]);
                $pi = $pinfoStmt->fetch(PDO::FETCH_ASSOC);
              ?>
                <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars(($pi['patient_name'] ?? 'Patient '.$pid).' ('.$pid.')'); ?>">
              <?php endif; ?>
            </div>

            <div class="form-group">
              <label>Appointment Date</label>
              <input type="date" name="appointment_date" class="form-control" required>
            </div>

            <div class="form-group">
              <label>Appointment Type</label>
              <select name="appointment_type" class="form-control" required>
                <option value="check-up">Check-up</option>
                <option value="follow-up">Follow-up</option>
                <option value="emergency">Emergency</option>
                <option value="consultation">Consultation</option>
              </select>
            </div>

            <div class="form-group">
              <label>Reason / Notes</label>
              <textarea name="reason" class="form-control" rows="3"></textarea>
            </div>

            <div class="form-group">
              <button name="create_appointment" class="btn btn-primary">Book Appointment</button>
            </div>
          </form>
        </div>

        <div class="col-md-7">
          <h5>Appointments</h5>
          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Patient</th>
                  <th>Appt Date</th>
                  <th>Type</th>
                  <th>Reason</th>
                  <th>Status</th>
                  <th>Booked On</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($visits) === 0): ?>
                  <tr><td colspan="8" class="text-center">No appointments found</td></tr>
                <?php else: foreach ($visits as $v): ?>
                  <tr>
                    <td><?php echo (int)$v['id']; ?></td>
                    <td><?php echo htmlspecialchars(($v['patient_name'] ?? 'Patient '.$v['patient_id']).' ('.$v['patient_id'].')'); ?></td>
                    <td><?php echo htmlspecialchars($v['appointment_date'] ?? $v['visit_date']); ?></td>
                    <td><?php echo htmlspecialchars($v['appointment_type'] ?? 'N/A'); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($v['reason'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($v['status'] ?? 'pending')); ?></td>
                    <td><?php echo htmlspecialchars($v['booking_date'] ?? ''); ?></td>
                    <td>
                      <?php if (userHasAnyRole([ROLE_ADMIN, ROLE_DOCTOR, ROLE_NURSE])): ?>
                        <form method="post" style="display:inline">
                          <input type="hidden" name="visit_id" value="<?php echo (int)$v['id']; ?>">
                          <select name="status" class="form-control form-control-sm" style="display:inline-block;width:auto">
                            <option value="pending" <?php echo ($v['status']=='pending')?'selected':'';?>>Pending</option>
                            <option value="confirmed" <?php echo ($v['status']=='confirmed')?'selected':'';?>>Confirmed</option>
                            <option value="complete" <?php echo ($v['status']=='complete')?'selected':'';?>>Complete</option>
                            <option value="cancelled" <?php echo ($v['status']=='cancelled')?'selected':'';?>>Cancelled</option>
                          </select>
                          <button name="update_status" class="btn btn-sm btn-primary">Update</button>
                        </form>
                      <?php endif; ?>

                      <?php if (isRole(ROLE_PATIENT)): ?>
                        <form method="post" style="display:inline">
                          <input type="hidden" name="visit_id" value="<?php echo (int)$v['id']; ?>">
                          <button name="cancel_appointment" class="btn btn-sm btn-danger" onclick="return confirm('Cancel appointment?')">Cancel</button>
                        </form>
                      <?php endif; ?>

                      <?php if (userHasAnyRole([ROLE_ADMIN, ROLE_DOCTOR, ROLE_NURSE])): ?>
                        <form method="post" style="display:inline">
                          <input type="hidden" name="visit_id" value="<?php echo (int)$v['id']; ?>">
                          <button name="delete_appointment" class="btn btn-sm btn-danger" onclick="return confirm('Delete this appointment?')">Delete</button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>

    </div>
  </div>
</div>

<?php include './config/footer.php'; ?>
<?php include './config/site_js_links.php'; ?>
</body>
</html>