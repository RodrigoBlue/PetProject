<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireFuncionario();

$conn = getConnection();
$message = '';
$error = '';

// Buscar agendamento para editar
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id > 0) {
    $query = $conn->prepare("SELECT a.*, p.nome as pet_name, p.idPet FROM agendamento a JOIN pet p ON a.idPet = p.idPet WHERE a.idAgendamento = ?");
    $query->bind_param("i", $id);
    $query->execute();
    $result = $query->get_result();
    $agendamento = $result->fetch_assoc();
    
    if(!$agendamento) {
        header('Location: agendamentos.php?error=' . urlencode('Agendamento não encontrado'));
        exit();
    }
}

// Processar atualização
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_agendamento = (int)$_POST['id'];
    $data = $_POST['data'];
    $hora = $_POST['hora'];
    $pet_id = (int)$_POST['pet_id'];
    
    $stmt = $conn->prepare("UPDATE agendamento SET data = ?, hora = ?, idPet = ? WHERE idAgendamento = ?");
    $stmt->bind_param("ssii", $data, $hora, $pet_id, $id_agendamento);
    
    if($stmt->execute()) {
        header('Location: agendamentos.php?success=' . urlencode('Agendamento atualizado com sucesso!'));
        exit();
    } else {
        $error = "Erro ao atualizar: " . $conn->error;
    }
}

// Buscar pets para o select
$pets = $conn->query("SELECT p.idPet, p.nome, t.nome as tutor FROM pet p JOIN tutor t ON p.idTutor = t.idTutor ORDER BY p.nome");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Editar Agendamento - PetProject</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .edit-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .edit-container h2 {
            color: #292F36;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #292F36;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #4ECDC4;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            margin-right: 10px;
        }
        .btn-primary {
            background: #4ECDC4;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .alert {
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <h2><i class="fas fa-edit"></i> Editar Agendamento</h2>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $agendamento['idAgendamento']; ?>">
            
            <div class="form-group">
                <label><i class="fas fa-calendar"></i> Data</label>
                <input type="date" name="data" value="<?php echo $agendamento['data']; ?>" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-clock"></i> Hora</label>
                <input type="time" name="hora" value="<?php echo substr($agendamento['hora'], 0, 5); ?>" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-paw"></i> Pet</label>
                <select name="pet_id" required>
                    <option value="">Selecione um pet</option>
                    <?php while($pet = $pets->fetch_assoc()): ?>
                        <option value="<?php echo $pet['idPet']; ?>" <?php echo ($pet['idPet'] == $agendamento['idPet']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($pet['nome']); ?> - Tutor: <?php echo htmlspecialchars($pet['tutor']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
                <a href="agendamentos.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</body>
</html>