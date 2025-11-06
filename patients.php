<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include './config/connection.php';
include_once './config/roles.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Only allow staff to manage patients
if (!userHasAnyRole([ROLE_ADMIN, ROLE_DOCTOR, ROLE_NURSE])) {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

// Fetch doctors for "assigned doctor" select
$doctors = $con->prepare("SELECT id, display_name FROM users WHERE role IN ('doctor','admin') ORDER BY display_name");
$doctors->execute();
$doctors = $doctors->fetchAll(PDO::FETCH_ASSOC);

// Handle create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_patient'])) {
    $patient_name = trim($_POST['patient_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $cnic = trim($_POST['cnic'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?: null;
    $phone_number = trim($_POST['phone_number'] ?? '');
    $gender = $_POST['gender'] ?? null;

    // new fields
    $registration_date = $_POST['registration_date'] ?: null;
    $assigned_doctor_id = $_POST['assigned_doctor_id'] ?: null;
    $admission_date = $_POST['admission_date'] ?: null;
    $discharge_date = $_POST['discharge_date'] ?: null;
    $allergies = trim($_POST['allergies'] ?? null);
    $existing_conditions = trim($_POST['existing_conditions'] ?? null);
    $medical_history = trim($_POST['medical_history'] ?? null);

    if ($patient_name === '') {
        $error = 'Patient name is required.';
    } else {
        try {
            $con->beginTransaction();

            // Insert patient record
            $stmt = $con->prepare("INSERT INTO patients
                (patient_name, address, cnic, date_of_birth, phone_number, gender,
                 registration_date, assigned_doctor_id, admission_date, discharge_date,
                 allergies, existing_conditions, medical_history)
                VALUES
                (:patient_name, :address, :cnic, :dob, :phone, :gender,
                 :registration_date, :assigned_doctor_id, :admission_date, :discharge_date,
                 :allergies, :existing_conditions, :medical_history)");
            
            $stmt->execute([
                ':patient_name' => $patient_name,
                ':address' => $address ?: null,
                ':cnic' => $cnic ?: null,
                ':dob' => $date_of_birth,
                ':phone' => $phone_number ?: null,
                ':gender' => $gender ?: null,
                ':registration_date' => $registration_date,
                ':assigned_doctor_id' => $assigned_doctor_id ?: null,
                ':admission_date' => $admission_date,
                ':discharge_date' => $discharge_date,
                ':allergies' => $allergies ?: null,
                ':existing_conditions' => $existing_conditions ?: null,
                ':medical_history' => $medical_history ?: null
            ]); // Added missing closing parenthesis
            
            // Get the new patient ID
            $patientId = $con->lastInsertId();
            
            // Create user account for patient
            $username = 'patient' . $patientId;
            
            // Generate a secure default password
            $defaultPass = '';
            if (!empty($cnic)) {
                $defaultPass = substr(preg_replace('/[^0-9]/', '', $cnic), 0, 6);
            } elseif (!empty($phone_number)) {
                $defaultPass = substr(preg_replace('/[^0-9]/', '', $phone_number), 0, 6);
            }
            if (strlen($defaultPass) < 6) {
                $defaultPass = 'Pass' . $patientId . '@' . substr(md5(time()), 0, 4);
            }
            
            // Check if username already exists (shouldn't, but better safe)
            $check = $con->prepare("SELECT COUNT(*) FROM users WHERE user_name = ?");
            $check->execute([$username]);
            if ($check->fetchColumn() > 0) {
                throw new Exception('Username already exists: ' . $username);
            }
            
            // Create user account
            $stmt = $con->prepare("INSERT INTO users 
                (user_name, display_name, password, role) 
                VALUES (:username, :display_name, :password, :role)");
            
            $stmt->execute([
                ':username' => $username,
                ':display_name' => $patient_name,
                ':password' => password_hash($defaultPass, PASSWORD_DEFAULT),
                ':role' => ROLE_PATIENT
            ]);

            $con->commit();

            // Store credentials in session to show in next page load
            $_SESSION['temp_credentials'] = [
                'username' => $username,
                'password' => $defaultPass
            ];
            
            $message = 'Patient created successfully.<br>'
                    . '<div class="alert alert-info">'
                    . '<strong>Login Credentials:</strong><br>'
                    . 'Username: ' . htmlspecialchars($username) . '<br>'
                    . 'Password: ' . htmlspecialchars($defaultPass) . '<br>'
                    . '<small>Please provide these credentials to the patient</small>'
                    . '</div>';

        } catch (Exception $e) {
            $con->rollBack();
            $error = 'Error creating patient: ' . $e->getMessage();
        }
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_patient'])) {
    $pid = (int)($_POST['patient_id'] ?? 0);
    if ($pid <= 0) $error = 'Invalid patient id.';
    else {
        $patient_name = trim($_POST['patient_name'] ?? '');
        if ($patient_name === '') $error = 'Patient name is required.';
        else {
            $stmt = $con->prepare("UPDATE patients SET
                patient_name = :patient_name,
                address = :address,
                cnic = :cnic,
                date_of_birth = :dob,
                phone_number = :phone,
                gender = :gender,
                registration_date = :registration_date,
                assigned_doctor_id = :assigned_doctor_id,
                admission_date = :admission_date,
                discharge_date = :discharge_date,
                allergies = :allergies,
                existing_conditions = :existing_conditions,
                medical_history = :medical_history
                WHERE id = :id");
            $stmt->execute([
                ':patient_name' => $patient_name,
                ':address' => trim($_POST['address'] ?? '') ?: null,
                ':cnic' => trim($_POST['cnic'] ?? '') ?: null,
                ':dob' => $_POST['date_of_birth'] ?: null,
                ':phone' => trim($_POST['phone_number'] ?? '') ?: null,
                ':gender' => $_POST['gender'] ?: null,
                ':registration_date' => $_POST['registration_date'] ?: null,
                ':assigned_doctor_id' => $_POST['assigned_doctor_id'] ?: null,
                ':admission_date' => $_POST['admission_date'] ?: null,
                ':discharge_date' => $_POST['discharge_date'] ?: null,
                ':allergies' => trim($_POST['allergies'] ?? '') ?: null,
                ':existing_conditions' => trim($_POST['existing_conditions'] ?? '') ?: null,
                ':medical_history' => trim($_POST['medical_history'] ?? '') ?: null,
                ':id' => $pid
            ]);
            $message = 'Patient updated successfully.';
        }
    }
}

// Fetch patients list
$patients = $con->query("SELECT p.*, u.display_name AS assigned_doctor_name
                        FROM patients p
                        LEFT JOIN users u ON u.id = p.assigned_doctor_id
                        ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include './config/site_css_links.php'; ?>
    <title>Patients - Hospital IMS</title>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
<?php include './config/header.php'; include './config/sidebar.php'; ?>

<div class="content-wrapper p-4">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title">Manage Patients</h3>
      <div>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['temp_credentials'])): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong>New Patient Credentials:</strong><br>
                Username: <?php echo htmlspecialchars($_SESSION['temp_credentials']['username']); ?><br>
                Password: <?php echo htmlspecialchars($_SESSION['temp_credentials']['password']); ?><br>
                <small>Please save these credentials now - they won't be shown again!</small>
            </div>
            <?php unset($_SESSION['temp_credentials']); ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="card-body">
      <div class="row">
        <div class="col-md-5">
          <form method="post">
            <input type="hidden" name="patient_id" id="patient_id" value="">
            <div class="form-group">
              <label>Patient Name</label>
              <input name="patient_name" id="patient_name" class="form-control" required>
            </div>

            <div class="form-group">
              <label>Address</label>
              <input name="address" id="address" class="form-control">
            </div>

            <div class="form-group">
              <label>CNIC</label>
              <input name="cnic" id="cnic" class="form-control">
            </div>

            <div class="form-group">
              <label>Date of Birth</label>
              <input type="date" name="date_of_birth" id="date_of_birth" class="form-control">
            </div>

            <div class="form-group">
              <label>Phone</label>
              <input name="phone_number" id="phone_number" class="form-control">
            </div>

            <div class="form-group">
              <label>Gender</label>
              <select name="gender" id="gender" class="form-control">
                <option value="">-- Select --</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
              </select>
            </div>

            <hr>
            <h5>Additional Info</h5>

            <div class="form-group">
              <label>Registration Date</label>
              <input type="date" name="registration_date" id="registration_date" class="form-control">
            </div>

            <div class="form-group">
              <label>Assigned Doctor</label>
              <select name="assigned_doctor_id" id="assigned_doctor_id" class="form-control">
                <option value="">-- Unassigned --</option>
                <?php foreach ($doctors as $d): ?>
                  <option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['display_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label>Admission Date (optional)</label>
              <input type="date" name="admission_date" id="admission_date" class="form-control">
            </div>

            <div class="form-group">
              <label>Discharge Date (optional)</label>
              <input type="date" name="discharge_date" id="discharge_date" class="form-control">
            </div>

            <div class="form-group">
              <label>Allergies</label>
              <textarea name="allergies" id="allergies" class="form-control" rows="2"></textarea>
            </div>

            <div class="form-group">
              <label>Existing Conditions</label>
              <textarea name="existing_conditions" id="existing_conditions" class="form-control" rows="2"></textarea>
            </div>

            <div class="form-group">
              <label>Medical History</label>
              <textarea name="medical_history" id="medical_history" class="form-control" rows="3"></textarea>
            </div>

            <div class="form-group">
              <button type="submit" name="create_patient" id="create_patient" class="btn btn-primary">Create Patient</button>
              <button type="submit" name="update_patient" id="update_patient" class="btn btn-success" style="display:none;">Update Patient</button>
              <button type="button" id="reset_form" class="btn btn-secondary">Reset</button>
              <a href="my_records.php" class="btn btn-info">View My Records (Patient)</a>
            </div>
          </form>
        </div>

        <div class="col-md-7">
          <h5>Patients List</h5>
          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Phone</th>
                  <th>Doctor</th>
                  <th>Registered</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($patients)): ?>
                  <tr><td colspan="6" class="text-center">No patients found</td></tr>
                <?php else: foreach ($patients as $p): ?>
                  <tr data-p='<?php echo json_encode($p, JSON_HEX_APOS|JSON_HEX_QUOT); ?>'>
                    <td><?php echo (int)$p['id']; ?></td>
                    <td><?php echo htmlspecialchars($p['patient_name']); ?></td>
                    <td><?php echo htmlspecialchars($p['phone_number'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($p['assigned_doctor_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($p['registration_date'] ?? ''); ?></td>
                    <td>
                      <button class="btn btn-sm btn-primary btn-edit">Edit</button>
                      <a href="my_records.php?patient_id=<?php echo (int)$p['id']; ?>" class="btn btn-sm btn-info">View Records</a>
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

<?php include './config/footer.php'; include './config/site_js_links.php'; ?>

<script>
document.getElementById('reset_form').addEventListener('click', function(){
    document.getElementById('patient_id').value = '';
    document.getElementById('patient_name').value = '';
    document.getElementById('address').value = '';
    document.getElementById('cnic').value = '';
    document.getElementById('date_of_birth').value = '';
    document.getElementById('phone_number').value = '';
    document.getElementById('gender').value = '';
    document.getElementById('registration_date').value = '';
    document.getElementById('assigned_doctor_id').value = '';
    document.getElementById('admission_date').value = '';
    document.getElementById('discharge_date').value = '';
    document.getElementById('allergies').value = '';
    document.getElementById('existing_conditions').value = '';
    document.getElementById('medical_history').value = '';
    document.getElementById('create_patient').style.display = '';
    document.getElementById('update_patient').style.display = 'none';
});

// Edit button: populate form
document.querySelectorAll('.btn-edit').forEach(function(btn){
    btn.addEventListener('click', function(){
        var tr = this.closest('tr');
        var data = JSON.parse(tr.getAttribute('data-p'));
        document.getElementById('patient_id').value = data.id;
        document.getElementById('patient_name').value = data.patient_name || '';
        document.getElementById('address').value = data.address || '';
        document.getElementById('cnic').value = data.cnic || '';
        document.getElementById('date_of_birth').value = data.date_of_birth || '';
        document.getElementById('phone_number').value = data.phone_number || '';
        document.getElementById('gender').value = data.gender || '';
        document.getElementById('registration_date').value = data.registration_date || '';
        document.getElementById('assigned_doctor_id').value = data.assigned_doctor_id || '';
        document.getElementById('admission_date').value = data.admission_date || '';
        document.getElementById('discharge_date').value = data.discharge_date || '';
        document.getElementById('allergies').value = data.allergies || '';
        document.getElementById('existing_conditions').value = data.existing_conditions || '';
        document.getElementById('medical_history').value = data.medical_history || '';
        document.getElementById('create_patient').style.display = 'none';
        document.getElementById('update_patient').style.display = '';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});
</script>
</body>
</html>