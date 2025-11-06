<?php 
$host = "localhost";
$user = "root";
$password = "";
$db = "pms_db";

try {
  // Use utf8mb4 charset and explicit port
  $dsn = "mysql:host={$host};port=3306;dbname={$db};charset=utf8mb4";
  $con = new PDO($dsn, $user, $password, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
  ]);
  // connection successful
} catch (PDOException $e) {
  // Log the error and show a generic message to users
  error_log("Database connection error: " . $e->getMessage());
  echo "Connection failed. Please contact the administrator.";
  exit;
}

// Start session only if none exists (do not force-start here if you prefer a central init)
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

//24 minutes default idle time
// if(isset($_SESSION['ABC'])) {
// 	unset($_SESSION['ABC']);
// }

?>