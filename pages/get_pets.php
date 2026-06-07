<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

session_start();

if(!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

$conn = getConnection();

$query = "SELECT p.idPet as id, p.nome, t.nome as tutor 
          FROM pet p 
          JOIN tutor t ON p.idTutor = t.idTutor 
          ORDER BY p.nome";

$result = $conn->query($query);
$pets = [];

while($row = $result->fetch_assoc()) {
    $pets[] = [
        'id' => $row['id'],
        'nome' => $row['nome'],
        'tutor' => $row['tutor']
    ];
}

header('Content-Type: application/json');
echo json_encode($pets);
?>