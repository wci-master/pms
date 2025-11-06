<?php 
	include '../config/connection.php';

  	$userName = $_GET['user_name'];

  $query = "select count(*) as `count` 
from `users` 
where `user_name` = :user_name;";
  $stmt = $con->prepare($query);
  $stmt->bindParam(':user_name', $userName);
  $stmt->execute();

	$r = $stmt->fetch(PDO::FETCH_ASSOC);
  $count = $r['count'];

  echo $count;

?>