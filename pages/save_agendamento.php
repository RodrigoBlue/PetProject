<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Não precisa chamar session_start() novamente porque auth.php já inicia a sessão

// Verificar se está logado
if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit();
}

// Verificar permissão
if (!hasPermission('pode_editar_agendamento')) {
    http_response_code(403);
    echo "Sem permissão para adicionar agendamentos";
    exit();
}

$conn = getConnection();

// Verificar se é POST
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Método não permitido";
    exit();
}

// Pegar dados do POST
$start = $_POST['start'] ?? '';
$tipo_servico = $_POST['tipo_servico'] ?? '';
$hora = $_POST['hora'] ?? '';
$nome_pet = trim($_POST['nome_pet'] ?? '');

// Validar dados
if(empty($start) || empty($tipo_servico) || empty($hora) || empty($nome_pet)) {
    echo "Por favor, preencha todos os campos";
    exit();
}

// Validar formato da hora
if(!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $hora)) {
    echo "Horário inválido! Use o formato HH:MM";
    exit();
}

// Converter a data
$date = date('Y-m-d', strtotime($start));

// Verificar se o pet existe pelo nome
$sql = "SELECT idPet FROM pet WHERE nome LIKE ? LIMIT 1";
$stmt = $conn->prepare($sql);
$search_nome = "%$nome_pet%";
$stmt->bind_param("s", $search_nome);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    $pet = $result->fetch_assoc();
    $pet_id = $pet['idPet'];
} else {
    // Se não encontrar o pet, criar um registro básico
    // Primeiro, verificar se existe algum tutor
    $check_tutor = $conn->query("SELECT idTutor FROM tutor LIMIT 1");
    if($check_tutor && $check_tutor->num_rows > 0) {
        $tutor = $check_tutor->fetch_assoc();
        $tutor_id = $tutor['idTutor'];
    } else {
        // Criar tutor padrão
        $stmt_tutor = $conn->prepare("INSERT INTO tutor (nome, telefone, endereco) VALUES (?, ?, ?)");
        $tutor_nome = "Tutor Padrão";
        $tutor_telefone = "(00) 00000-0000";
        $tutor_endereco = "Endereço não informado";
        $stmt_tutor->bind_param("sss", $tutor_nome, $tutor_telefone, $tutor_endereco);
        $stmt_tutor->execute();
        $tutor_id = $conn->insert_id;
        $stmt_tutor->close();
    }
    
    // Criar o pet
    $stmt_pet = $conn->prepare("INSERT INTO pet (nome, especie, raca, idTutor) VALUES (?, ?, ?, ?)");
    $especie = "Não informado";
    $raca = "SRD";
    $stmt_pet->bind_param("sssi", $nome_pet, $especie, $raca, $tutor_id);
    
    if($stmt_pet->execute()) {
        $pet_id = $conn->insert_id;
    } else {
        echo "Erro ao criar pet: " . $conn->error;
        exit();
    }
    $stmt_pet->close();
}
$stmt->close();

// Verificar se já existe agendamento para este horário
$check = $conn->prepare("SELECT idAgendamento FROM agendamento WHERE data = ? AND hora = ?");
$check->bind_param("ss", $date, $hora);
$check->execute();
$check_result = $check->get_result();

if($check_result->num_rows > 0) {
    echo "Já existe um agendamento para este dia e horário!";
    $check->close();
    $conn->close();
    exit();
}
$check->close();

// Inserir o agendamento
$stmt = $conn->prepare("INSERT INTO agendamento (data, hora, tipo_servico, idPet) VALUES (?, ?, ?, ?)");
$stmt->bind_param("sssi", $date, $hora, $tipo_servico, $pet_id);

if($stmt->execute()) {
    echo "success";
} else {
    echo "Erro ao salvar: " . $conn->error;
}

$stmt->close();
$conn->close();
?>