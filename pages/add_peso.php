<?php
// add_peso.php - Adicionar registro de peso
require_once '../config/database.php';
require_once '../includes/auth.php';
requireFuncionario();

$conn = getConnection();

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pets.php');
    exit();
}

$pet_id = isset($_POST['pet_id']) ? (int)$_POST['pet_id'] : 0;
$data = isset($_POST['data']) ? $_POST['data'] : date('Y-m-d');
$peso = isset($_POST['peso']) ? (float)$_POST['peso'] : 0;

if ($pet_id <= 0 || $peso <= 0) {
    header('Location: pet_detalhe.php?id=' . $pet_id . '&error=' . urlencode('Dados inválidos'));
    exit();
}

// Verificar se a tabela existe
$conn->query("CREATE TABLE IF NOT EXISTS pet_historico_peso (
    idHistorico INT AUTO_INCREMENT PRIMARY KEY,
    idPet INT NOT NULL,
    data DATE NOT NULL,
    peso DECIMAL(5,3) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idPet) REFERENCES pet(idPet) ON DELETE CASCADE
)");

// Inserir registro
$stmt = $conn->prepare("INSERT INTO pet_historico_peso (idPet, data, peso) VALUES (?, ?, ?)");
$stmt->bind_param("isd", $pet_id, $data, $peso);

if ($stmt->execute()) {
    // Atualizar o peso atual do pet
    $conn->query("UPDATE pet SET peso = $peso WHERE idPet = $pet_id");
    header('Location: pet_detalhe.php?id=' . $pet_id . '&success=' . urlencode('Peso registrado com sucesso!'));
} else {
    header('Location: pet_detalhe.php?id=' . $pet_id . '&error=' . urlencode('Erro ao registrar peso'));
}

$stmt->close();
$conn->close();
?>