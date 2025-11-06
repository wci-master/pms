<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require './config/connection.php';
require_once './config/roles.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$visitId = isset($_GET['visit_id']) ? (int)$_GET['visit_id'] : 0;
if ($visitId <= 0) {
    die("Invalid visit id.");
}

// If patient, ensure they own this visit
if (isRole(ROLE_PATIENT)) {
    $pid = null;
    if (isset($_SESSION['user_name']) && strpos($_SESSION['user_name'], 'patient') === 0) {
        $pid = (int) substr($_SESSION['user_name'], 7);
    }
    $check = $con->prepare("SELECT COUNT(*) FROM patient_visits WHERE id = :vid AND patient_id = :pid");
    $check->execute([':vid' => $visitId, ':pid' => $pid]);
    if ($check->fetchColumn() == 0) {
        die("Not authorized to view this prescription.");
    }
}

// Fetch visit + patient info
$visitStmt = $con->prepare("
    SELECT pv.*, p.patient_name, p.address, p.phone_number
    FROM patient_visits pv
    LEFT JOIN patients p ON p.id = pv.patient_id
    WHERE pv.id = :vid
    LIMIT 1
");
$visitStmt->execute([':vid' => $visitId]);
$visit = $visitStmt->fetch(PDO::FETCH_ASSOC);
if (!$visit) die("Visit not found.");

// Fetch prescription rows for this visit
$medStmt = $con->prepare("
    SELECT pmh.*, m.medicine_name 
    FROM patient_medication_history pmh
    LEFT JOIN medicine_details md ON md.id = pmh.medicine_details_id
    LEFT JOIN medicines m ON m.id = md.medicine_id
    WHERE pmh.patient_visit_id = :vid
    ORDER BY pmh.id ASC
");
$medStmt->execute([':vid' => $visitId]);
$medications = $medStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php'; ?>
    <title>View Prescription</title>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
    <?php include './config/sidebar.php'; ?>
    <div class="content-wrapper p-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Prescription Details</h3>
                <a href="prescriptions.php" class="btn btn-info btn-sm float-right">Back to List</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Patient Information</h5>
                        <p>
                            <strong>Name:</strong> <?php echo htmlspecialchars($visit['patient_name']); ?><br>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($visit['phone_number']); ?><br>
                            <strong>Visit Date:</strong> <?php echo htmlspecialchars($visit['visit_date']); ?><br>
                            <strong>Next Visit:</strong> <?php echo htmlspecialchars($visit['next_visit_date'] ?? 'Not scheduled'); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h5>Vital Signs</h5>
                        <p>
                            <strong>BP:</strong> <?php echo htmlspecialchars($visit['bp']); ?><br>
                            <strong>Weight:</strong> <?php echo htmlspecialchars($visit['weight']); ?><br>
                            <strong>Disease:</strong> <?php echo htmlspecialchars($visit['disease']); ?>
                        </p>
                    </div>
                </div>

                <h5 class="mt-4">Prescribed Medications</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Medicine</th>
                                <th>Frequency</th>
                                <th>Duration</th>
                                <th>Instructions</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($medications)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No medications prescribed</td>
                                </tr>
                            <?php else: foreach($medications as $index => $med): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($med['medicine_name']); ?></td>
                                    <td><?php echo htmlspecialchars($med['frequency']); ?></td>
                                    <td><?php echo htmlspecialchars($med['duration_days'].' days'); ?></td>
                                    <td><?php echo htmlspecialchars($med['instructions']); ?></td>
                                    <td><?php echo htmlspecialchars($med['additional_notes'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php include './config/footer.php'; ?>
    <?php include './config/site_js_links.php'; ?>
</body>
</html>