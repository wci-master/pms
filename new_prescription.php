<?php 
include './config/connection.php';
include './common_service/common_functions.php';

$message = '';

if(isset($_POST['submit'])) {
    $patientId = (int)$_POST['patient'];
    $visitDate = $_POST['visit_date'];
    $diagnosis = $_POST['diagnosis'] ?? '';
    $bp = $_POST['bp'];
    $weight = $_POST['weight'];
    $disease = $_POST['disease'];
    $next_visit_date = $_POST['next_visit_date'];
    
    try {
        $con->beginTransaction();
        
        // Insert visit record
        $queryVisit = "INSERT INTO `patient_visits` 
            (`patient_id`, `visit_date`, `bp`, `weight`, `disease`, `next_visit_date`) 
            VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmtVisit = $con->prepare($queryVisit);
        $stmtVisit->execute([$patientId, $visitDate, $bp, $weight, $disease, $next_visit_date]);
        $lastInsertId = $con->lastInsertId();
        
        // Process medications
        if (isset($_POST['medicine_details']) && is_array($_POST['medicine_details'])) {
            // Insert medicines with the new fields: frequency, duration_days, instructions, additional_notes
            $qeuryMedicationHistory = "INSERT INTO `patient_medication_history` 
                (`patient_visit_id`, `medicine_details_id`, `prescription_date`, `diagnosis`, `frequency`, `duration_days`, `instructions`, `additional_notes`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtDetails = $con->prepare($qeuryMedicationHistory);

            foreach ($_POST['medicine_details'] as $index => $medicineId) {
                $medicineId = (int)$medicineId;
                $frequency = $_POST['frequencies'][$index] ?? '';
                $duration = (int)($_POST['durations'][$index] ?? 0);
                $instructions = $_POST['instructions'][$index] ?? '';
                $notes = $_POST['notes'][$index] ?? '';

                $stmtDetails->execute([
                    $lastInsertId,
                    $medicineId,
                    $visitDate,
                    $diagnosis,
                    $frequency,
                    $duration,
                    $instructions,
                    $notes
                ]);
            }
        }
        
        $con->commit();
        $message = 'Prescription saved successfully.';
    } catch(PDOException $ex) {
        $con->rollback();
        $error = $ex->getMessage();
    }
}
$patients = getPatients($con);
$medicines = getMedicines($con);

?>
<!DOCTYPE html>
<html lang="en">
<head>
 <?php include './config/site_css_links.php' ?>

 <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
 <title>New Prescription - Clinic's Patient Management System in PHP</title>

</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
  <!-- Site wrapper -->
  <div class="wrapper">
    <!-- Navbar -->

    <?php include './config/header.php';
include './config/sidebar.php';?>  
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <section class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1>New Prescription</h1>
            </div>
          </div>
        </div><!-- /.container-fluid -->
      </section>

      <!-- Main content -->
      <section class="content">

        <!-- Default box -->
        <div class="card card-outline card-primary rounded-0 shadow">
          <div class="card-header">
            <h3 class="card-title">New Prescription</h3>
            <a href="prescriptions.php" class="btn btn-info btn-sm float-right">View All Prescriptions</a>
          </div>
          <div class="card-body">
            <!-- best practices-->
            <form method="post">
              <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                  <label>Select Patient</label>
                  <select id="patient" name="patient" class="form-control form-control-sm rounded-0" 
                  required="required">
                  <?php echo $patients;?>
                </select>
              </div>


              <div class="col-lg-3 col-md-3 col-sm-4 col-xs-10">
                <div class="form-group">
                  <label>Visit Date</label>
                  <div class="input-group date" 
                  id="visit_date" 
                  data-target-input="nearest">
                  <input type="text" class="form-control form-control-sm rounded-0 datetimepicker-input" data-target="#visit_date" name="visit_date" required="required" data-toggle="datetimepicker" autocomplete="off"/>
                  <div class="input-group-append" 
                  data-target="#visit_date" 
                  data-toggle="datetimepicker">
                  <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                </div>
              </div>
            </div>
          </div>
          


          <div class="col-lg-3 col-md-3 col-sm-4 col-xs-10">
            <div class="form-group">
              <label>Next Visit Date</label>
              <div class="input-group date" 
              id="next_visit_date" 
              data-target-input="nearest">
              <input type="text" class="form-control form-control-sm rounded-0 datetimepicker-input" data-target="#next_visit_date" name="next_visit_date" data-toggle="datetimepicker" autocomplete="off"/>
              <div class="input-group-append" 
              data-target="#next_visit_date" 
              data-toggle="datetimepicker">
              <div class="input-group-text"><i class="fa fa-calendar"></i></div>
            </div>
          </div>
        </div>
      </div>

      <div class="clearfix">&nbsp;</div>

      <div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
        <label>BP</label>
        <input id="bp" class="form-control form-control-sm rounded-0" name="bp" required="required" />
      </div>
      
      <div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
        <label>Weight</label>
        <input id="weight" name="weight" class="form-control form-control-sm rounded-0" required="required" />
      </div>

      <div class="col-lg-8 col-md-8 col-sm-6 col-xs-12">
        <label>Disease</label>
        <input id="disease" required="required" name="disease" class="form-control form-control-sm rounded-0" />
      </div>

      <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <label>Diagnosis/Condition</label>
        <textarea id="diagnosis" name="diagnosis" class="form-control form-control-sm rounded-0" required="required" rows="2"></textarea>
      </div>


    </div>

    <div class="col-md-12"><hr /></div>
    <div class="clearfix">&nbsp;</div>

    <div class="row">
     <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
      <label>Select Medicine</label>
      <select id="medicine" class="form-control form-control-sm rounded-0">
        <?php echo $medicines;?>
      </select>
    </div>

    <div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
      <label>Frequency</label>
      <select id="frequency" class="form-control form-control-sm rounded-0">
        <option value="daily">Once Daily</option>
        <option value="twice_daily">Twice Daily</option>
        <option value="thrice_daily">Thrice Daily</option>
        <option value="four_times">Four Times Daily</option>
        <option value="when_needed">When Needed</option>
      </select>
    </div>

    <div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
      <label>Duration</label>
      <input id="duration" type="number" class="form-control form-control-sm rounded-0" placeholder="Days"/>
    </div>

    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
      <label>Instructions</label>
      <select id="instructions" class="form-control form-control-sm rounded-0">
        <option value="before_meal">Before Meal</option>
        <option value="after_meal">After Meal</option>
        <option value="with_meal">With Meal</option>
        <option value="empty_stomach">Empty Stomach</option>
        <option value="bedtime">At Bedtime</option>
      </select>
    </div>

    <div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
      <label>Notes</label>
      <input id="notes" class="form-control form-control-sm rounded-0" placeholder="Additional notes"/>
    </div>

    <div class="col-lg-1 col-md-1 col-sm-6 col-xs-12">
      <label>&nbsp;</label>
      <button id="add_to_list" type="button" class="btn btn-primary btn-sm btn-flat btn-block">
        <i class="fa fa-plus"></i>
      </button>
    </div>

  </div>

  <div class="clearfix">&nbsp;</div>
  <div class="row table-responsive">
    <table id="medication_list" class="table table-striped table-bordered">
      <colgroup>
        <col width="10%">
        <col width="50%">
        <col width="10%">
        <col width="10%">
        <col width="15%">
        <col width="5%">
      </colgroup>
      <thead class="bg-primary">
        <tr>
          <th>S.No</th>
          <th>Medicine</th>
          <th>Frequency</th>
          <th>Duration</th>
          <th>Instructions</th>
          <th>Notes</th>
          <th>Action</th>
        </tr>
      </thead>

      <tbody id="current_medicines_list">

      </tbody>
    </table>
  </div>

  <div class="clearfix">&nbsp;</div>
  <div class="row">
    <div class="col-md-10">&nbsp;</div>
    <div class="col-md-2">
      <button type="submit" id="submit" name="submit" 
      class="btn btn-primary btn-sm btn-flat btn-block">Save</button>
    </div>
  </div>
</form>

</div>

</div>
<!-- /.card -->

</section>
<!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php include './config/footer.php';
$message = '';
if(isset($_GET['message'])) {
  $message = $_GET['message'];
}
?>  
<!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<?php include './config/site_js_links.php';
?>

<script src="plugins/moment/moment.min.js"></script>
<script src="plugins/daterangepicker/daterangepicker.js"></script>
<script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>

<script>
  var serial = 1;
  showMenuSelected("#mnu_patients", "#mi_new_prescription");

  var message = '<?php echo $message;?>';

  if(message !== '') {
    showCustomMessage(message);
  }


  $(document).ready(function() {
    
    $('#medication_list').find('td').addClass("px-2 py-1 align-middle")
    $('#medication_list').find('th').addClass("p-1 align-middle")
    $('#visit_date, #next_visit_date').datetimepicker({
      format: 'L'
    });


    $("#medicine").change(function() {

      // var medicineId = $("#medicine").val();
      var medicineId = $(this).val();

      if(medicineId !== '') {
        $.ajax({
          url: "ajax/get_packings.php",
          type: 'GET', 
          data: {
            'medicine_id': medicineId
          },
          cache:false,
          async:false,
          success: function (data, status, xhr) {
            $("#packing").html(data);
          },
          error: function (jqXhr, textStatus, errorMessage) {
            showCustomMessage(errorMessage);
          }
        });
      }
    });


    $("#add_to_list").click(function() {
      var medicineId = $("#medicine").val();
      var medicineName = $("#medicine option:selected").text();
      var frequency = $("#frequency").val();
      var frequencyText = $("#frequency option:selected").text();
      var duration = $("#duration").val().trim();
      var instructions = $("#instructions").val();
      var instructionsText = $("#instructions option:selected").text();
      var notes = $("#notes").val().trim();

      if(medicineName !== '' && frequency !== '' && duration !== '') {
        var inputs = '';
        inputs += '<input type="hidden" name="medicine_details[]" value="'+medicineId+'" />';
        inputs += '<input type="hidden" name="frequencies[]" value="'+frequency+'" />';
        inputs += '<input type="hidden" name="durations[]" value="'+duration+'" />';
        inputs += '<input type="hidden" name="instructions[]" value="'+instructions+'" />';
        inputs += '<input type="hidden" name="notes[]" value="'+notes+'" />';

        var tr = '<tr>';
        tr += '<td class="px-2 py-1 align-middle">'+serial+'</td>';
        tr += '<td class="px-2 py-1 align-middle">'+medicineName+'</td>';
        tr += '<td class="px-2 py-1 align-middle">'+frequencyText+'</td>';
        tr += '<td class="px-2 py-1 align-middle">'+duration+' days</td>';
        tr += '<td class="px-2 py-1 align-middle">'+instructionsText+'</td>';
        tr += '<td class="px-2 py-1 align-middle">'+notes+'</td>';
        tr += '<td class="px-2 py-1 align-middle text-center">'+inputs+'<button type="button" class="btn btn-outline-danger btn-sm rounded-0" onclick="deleteCurrentRow(this);"><i class="fa fa-times"></i></button></td>';
        tr += '</tr>';

        $("#current_medicines_list").append(tr);
        serial++;

        // Clear inputs
        $("#medicine").val('');
        $("#frequency").val('daily');
        $("#duration").val('');
        $("#instructions").val('before_meal');
        $("#notes").val('');
      } else {
        showCustomMessage('Please fill all required fields.');
      }
    });

  });

  function deleteCurrentRow(obj) {

    var rowIndex = obj.parentNode.parentNode.rowIndex;
    
    document.getElementById("medication_list").deleteRow(rowIndex);
  }
</script>
</body>
</html>