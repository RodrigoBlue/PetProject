<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireFuncionario();

$conn = getConnection();

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "error";
    exit();
}

if (!hasPermission('pode_editar_agendamento')) {
    echo "error";
    exit();
}

$id_agendamento = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if($id_agendamento <= 0) {
    echo "error";
    exit();
}

$stmt = $conn->prepare("DELETE FROM agendamento WHERE idAgendamento = ?");
$stmt->bind_param("i", $id_agendamento);

if($stmt->execute()) {
    echo "success";
} else {
    echo "error";
}

$stmt->close();
$conn->close();
?>