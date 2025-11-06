<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require './config/connection.php';
require_once './config/roles.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// staff see all prescriptions, patients see only their own
if (userHasAnyRole([ROLE_ADMIN, ROLE_DOCTOR, ROLE_NURSE, ROLE_PHARMACY])) {
    $sql = "SELECT DISTINCT pv.id AS visit_id, pv.visit_date, pv.patient_id,
                   p.patient_name,
                   pmh.diagnosis
            FROM patient_visits pv
            LEFT JOIN patients p ON p.id = pv.patient_id
            LEFT JOIN patient_medication_history pmh ON pmh.patient_visit_id = pv.id
            ORDER BY pv.visit_date DESC";
    $stmt = $con->query($sql);
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $pid = substr($_SESSION['user_name'], 7); // extract ID from 'patient{ID}'
    $sql = "SELECT DISTINCT pv.id AS visit_id, pv.visit_date, pv.patient_id,
                   p.patient_name,
                   pmh.diagnosis
            FROM patient_visits pv
            LEFT JOIN patients p ON p.id = pv.patient_id
            LEFT JOIN patient_medication_history pmh ON pmh.patient_visit_id = pv.id
            WHERE pv.patient_id = :pid
            ORDER BY pv.visit_date DESC";
    $stmt = $con->prepare($sql);
    $stmt->execute([':pid' => $pid]);
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php'; ?>
    <title>Prescriptions List</title>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
    <?php include './config/sidebar.php'; ?>
    <div class="content-wrapper p-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Prescriptions</h3>
                <?php if (userHasAnyRole([ROLE_ADMIN, ROLE_DOCTOR, ROLE_NURSE])): ?>
                    <a href="new_prescription.php" class="btn btn-primary btn-sm float-right">New Prescription</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Visit ID</th>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Diagnosis</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($prescriptions)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No prescriptions found</td>
                            </tr>
                        <?php else: foreach($prescriptions as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['visit_id']); ?></td>
                                <td><?php echo htmlspecialchars($p['visit_date']); ?></td>
                                <td><?php echo htmlspecialchars($p['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($p['diagnosis'] ?? '', 0, 100)); ?></td>
                                <td>
                                    <a href="view_prescription.php?visit_id=<?php echo $p['visit_id']; ?>" 
                                       class="btn btn-info btn-sm">View</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php include './config/footer.php'; ?>
    <?php include './config/site_js_links.php'; ?>
</body>
</html>