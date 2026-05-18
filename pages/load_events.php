<?php
require_once '../config/database.php';
header('Content-Type: application/json');
$conn = getConnection();
$result = $conn->query("SELECT a.idAgendamento, a.data, a.hora, p.nome as pet_name, t.nome as tutor FROM agendamento a JOIN pet p ON a.idPet = p.idPet JOIN tutor t ON p.idTutor = t.idTutor");
$events = [];
while($row = $result->fetch_assoc()) {
    $events[] = [
        'id' => $row['idAgendamento'],
        'title' => $row['pet_name'] . " - " . $row['tutor'],
        'start' => $row['data'] . 'T' . $row['hora'],
        'url' => '#'
    ];
}
echo json_encode($events);
?>