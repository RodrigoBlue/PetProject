<?php
require_once '../config/database.php';
session_start();
if(!isset($_SESSION['user_id'])) die("Unauthorized");
$conn = getConnection();
$start = $_POST['start'];
$pet_id = $_POST['pet_id'];
$date = date('Y-m-d', strtotime($start));
$time = date('H:i:s', strtotime($start));
$stmt = $conn->prepare("INSERT INTO agendamento (data, hora, idPet) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $date, $time, $pet_id);
$stmt->execute();
echo "ok";
?>