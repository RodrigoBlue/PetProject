<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireFuncionario();

// Verificar permissão
if (!hasPermission('pode_editar_pet')) {
    header('Location: pets.php?error=' . urlencode('Sem permissão para excluir pets'));
    exit();
}

$pet_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($pet_id <= 0) {
    header('Location: pets.php?error=' . urlencode('ID do pet inválido'));
    exit();
}

$conn = getConnection();

// Verificar se o pet existe
$check = $conn->query("SELECT nome FROM pet WHERE idPet = $pet_id");
if ($check->num_rows == 0) {
    header('Location: pets.php?error=' . urlencode('Pet não encontrado'));
    exit();
}

$pet_nome = $check->fetch_assoc()['nome'];

// Verificar se o pet tem agendamentos
$agendamentos = $conn->query("SELECT COUNT(*) as total FROM agendamento WHERE idPet = $pet_id");
$total_agendamentos = $agendamentos->fetch_assoc()['total'];

// Verificar se o pet tem atendimentos
$atendimentos = $conn->query("SELECT COUNT(*) as total FROM atendimento WHERE idPet = $pet_id");
$total_atendimentos = $atendimentos->fetch_assoc()['total'];

// Verificar se o pet tem histórico de peso
$historico = $conn->query("SELECT COUNT(*) as total FROM pet_historico_peso WHERE idPet = $pet_id");
$total_historico = $historico->fetch_assoc()['total'];

// Se tiver registros associados, perguntar antes de excluir
if ($total_agendamentos > 0 || $total_atendimentos > 0 || $total_historico > 0) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Confirmar Exclusão - PetProject</title>
        <link rel="stylesheet" href="../styles/style.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <style>
            body {
                font-family: 'Poppins', sans-serif;
                background: #f5f5f5;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
                padding: 20px;
            }
            .confirm-box {
                background: white;
                border-radius: 10px;
                padding: 30px;
                max-width: 500px;
                text-align: center;
                box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            }
            .warning-icon {
                font-size: 60px;
                color: #ffc107;
                margin-bottom: 20px;
            }
            .pet-name {
                color: #4ECDC4;
                font-weight: bold;
            }
            .btn {
                padding: 10px 20px;
                border-radius: 5px;
                text-decoration: none;
                margin: 10px;
                display: inline-block;
            }
            .btn-danger {
                background: #dc3545;
                color: white;
            }
            .btn-secondary {
                background: #6c757d;
                color: white;
            }
            .records-list {
                text-align: left;
                margin: 20px 0;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 5px;
            }
        </style>
    </head>
    <body>
        <div class="confirm-box">
            <div class="warning-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Confirmar Exclusão</h2>
            <p>O pet <strong class="pet-name"><?php echo htmlspecialchars($pet_nome); ?></strong> possui registros associados:</p>
            <div class="records-list">
                <ul>
                    <?php if($total_agendamentos > 0): ?>
                        <li><i class="fas fa-calendar-alt"></i> <?php echo $total_agendamentos; ?> agendamento(s)</li>
                    <?php endif; ?>
                    <?php if($total_atendimentos > 0): ?>
                        <li><i class="fas fa-stethoscope"></i> <?php echo $total_atendimentos; ?> atendimento(s)</li>
                    <?php endif; ?>
                    <?php if($total_historico > 0): ?>
                        <li><i class="fas fa-weight"></i> <?php echo $total_historico; ?> registro(s) de peso</li>
                    <?php endif; ?>
                </ul>
            </div>
            <p><strong>Atenção:</strong> Ao excluir este pet, todos os registros relacionados também serão excluídos!</p>
            <div>
                <a href="delete_pet.php?id=<?php echo $pet_id; ?>&confirm=1" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Sim, excluir tudo
                </a>
                <a href="pets.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Se não tem registros ou confirmou a exclusão
if (isset($_GET['confirm']) && $_GET['confirm'] == 1) {
    // Excluir registros relacionados manualmente
    $conn->query("DELETE FROM pet_historico_peso WHERE idPet = $pet_id");
    $conn->query("DELETE FROM agendamento WHERE idPet = $pet_id");
    $conn->query("DELETE FROM atendimento WHERE idPet = $pet_id");
    $conn->query("DELETE FROM pet WHERE idPet = $pet_id");
    
    header('Location: pets.php?success=' . urlencode('Pet excluído com sucesso!'));
    exit();
}

// Tentar excluir diretamente (se não tiver registros relacionados)
$result = $conn->query("DELETE FROM pet WHERE idPet = $pet_id");

if ($conn->affected_rows > 0) {
    header('Location: pets.php?success=' . urlencode('Pet excluído com sucesso!'));
} else {
    header('Location: pets.php?error=' . urlencode('Erro ao excluir pet.'));
}
exit();
?>