<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);

require_once '../config/database.php';
require_once '../includes/auth.php';
requireFuncionario();

$conn = getConnection();
$message = '';
$error = '';

// Verificar se é edição ou novo
$pet_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tutor_id = isset($_GET['tutor_id']) ? (int)$_GET['tutor_id'] : 0;
$is_new = (isset($_GET['action']) && $_GET['action'] === 'new') || $pet_id == 0;

// Mensagem de sucesso via GET (após redirecionamento)
if (isset($_GET['success'])) {
    $message = 'Pet cadastrado com sucesso!';
}

// Buscar dados do pet se for edição
$pet = null;
if (!$is_new && $pet_id > 0) {
    $result = $conn->query("SELECT p.*, t.nome as tutor_nome, t.cpf, t.telefone, t.endereco 
                            FROM pet p 
                            JOIN tutor t ON p.idTutor = t.idTutor 
                            WHERE p.idPet = $pet_id");
    $pet = $result->fetch_assoc();
    if ($pet) {
        // Garantir valores padrão para campos que podem não existir
        $pet['microchip'] = isset($pet['microchip']) ? $pet['microchip'] : '';
        $pet['observacoes'] = isset($pet['observacoes']) ? $pet['observacoes'] : '';
        $pet['diagnostico'] = isset($pet['diagnostico']) ? $pet['diagnostico'] : '';
        $pet['notas_gerais'] = isset($pet['notas_gerais']) ? $pet['notas_gerais'] : '';
        $pet['alertas'] = isset($pet['alertas']) ? $pet['alertas'] : '';
        $pet['castrado'] = isset($pet['castrado']) ? $pet['castrado'] : 0;
        $pet['data_nascimento'] = isset($pet['data_nascimento']) ? $pet['data_nascimento'] : '';
        $tutor_id = $pet['idTutor'];
    } else {
        header('Location: pets.php?error=' . urlencode('Pet não encontrado'));
        exit();
    }
}

// Buscar dados do tutor
$tutor = null;
if ($tutor_id > 0) {
    $result = $conn->query("SELECT * FROM tutor WHERE idTutor = $tutor_id");
    $tutor = $result->fetch_assoc();
}

// Processar formulário de edição/cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_pet') {
        $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
        $especie = isset($_POST['especie']) ? $_POST['especie'] : 'Canina';
        $raca = isset($_POST['raca']) ? trim($_POST['raca']) : '';
        $peso = isset($_POST['peso']) ? (float)$_POST['peso'] : 0;
        $sexo = isset($_POST['sexo']) ? $_POST['sexo'] : 'Macho';
        $cor = isset($_POST['cor']) ? trim($_POST['cor']) : '';
        $castrado = (isset($_POST['castrado']) && $_POST['castrado'] === 'Sim') ? 1 : 0;
        $microchip = isset($_POST['microchip']) ? trim($_POST['microchip']) : '';
        $data_nascimento = isset($_POST['data_nascimento']) && !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
        $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : '';
        $diagnostico = isset($_POST['diagnostico']) ? trim($_POST['diagnostico']) : '';
        $notas_gerais = isset($_POST['notas_gerais']) ? trim($_POST['notas_gerais']) : '';
        $alertas = isset($_POST['alertas']) ? trim($_POST['alertas']) : '';

        if ($pet_id > 0) {
            // ========== ATUALIZA PET EXISTENTE ==========
            $stmt = $conn->prepare("UPDATE pet SET nome=?, especie=?, raca=?, peso=?, sexo=?, cor=?, castrado=?, microchip=?, data_nascimento=?, observacoes=?, diagnostico=?, notas_gerais=?, alertas=? WHERE idPet=?");
            $stmt->bind_param("sssdsssssssssi", $nome, $especie, $raca, $peso, $sexo, $cor, $castrado, $microchip, $data_nascimento, $observacoes, $diagnostico, $notas_gerais, $alertas, $pet_id);
            
            if ($stmt->execute()) {
                $message = "Informações do pet atualizadas com sucesso!";
                // Recarregar dados do pet
                $result = $conn->query("SELECT p.*, t.nome as tutor_nome, t.cpf, t.telefone, t.endereco FROM pet p JOIN tutor t ON p.idTutor = t.idTutor WHERE p.idPet = $pet_id");
                $pet = $result->fetch_assoc();
                if ($pet) {
                    $pet['microchip'] = isset($pet['microchip']) ? $pet['microchip'] : '';
                    $pet['observacoes'] = isset($pet['observacoes']) ? $pet['observacoes'] : '';
                    $pet['diagnostico'] = isset($pet['diagnostico']) ? $pet['diagnostico'] : '';
                    $pet['notas_gerais'] = isset($pet['notas_gerais']) ? $pet['notas_gerais'] : '';
                    $pet['alertas'] = isset($pet['alertas']) ? $pet['alertas'] : '';
                }
            } else {
                $error = "Erro ao atualizar: " . $conn->error;
            }
            $stmt->close();
        } else {
            // ========== CADASTRA NOVO PET (INSERT) ==========
            $tutor_id_insert = isset($_POST['tutor_id']) ? (int)$_POST['tutor_id'] : 0;
            if ($tutor_id_insert <= 0) {
                $error = "ID do tutor inválido. Não foi possível cadastrar o pet.";
            } else {
                $stmt = $conn->prepare("INSERT INTO pet (idTutor, nome, especie, raca, peso, sexo, cor, castrado, microchip, data_nascimento, observacoes, diagnostico, notas_gerais, alertas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssdsssssssss", $tutor_id_insert, $nome, $especie, $raca, $peso, $sexo, $cor, $castrado, $microchip, $data_nascimento, $observacoes, $diagnostico, $notas_gerais, $alertas);
                
                if ($stmt->execute()) {
                    $new_id = $conn->insert_id;
                    // Redireciona para a página de detalhes do novo pet
                    header("Location: pet_detalhe.php?id=$new_id&success=1");
                    
                    exit();
                } else {
                    $error = "Erro ao cadastrar pet: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
    
    // Adicionar registro clínico
    if ($_POST['action'] === 'add_registro') {
        $data_registro = isset($_POST['data_registro']) ? $_POST['data_registro'] : date('Y-m-d');
        $tipo_registro = isset($_POST['tipo_registro']) ? $_POST['tipo_registro'] : 'Consulta';
        $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
        $medicamentos = isset($_POST['medicamentos']) ? trim($_POST['medicamentos']) : '';
        $procedimentos = isset($_POST['procedimentos']) ? trim($_POST['procedimentos']) : '';
        
        // Criar tabela se não existir
        $conn->query("CREATE TABLE IF NOT EXISTS pet_registros_clinicos (
            idRegistro INT AUTO_INCREMENT PRIMARY KEY,
            idPet INT NOT NULL,
            data_registro DATE NOT NULL,
            tipo_registro VARCHAR(50) NOT NULL,
            descricao TEXT,
            medicamentos TEXT,
            procedimentos TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (idPet) REFERENCES pet(idPet) ON DELETE CASCADE
        )");
        
        $stmt = $conn->prepare("INSERT INTO pet_registros_clinicos (idPet, data_registro, tipo_registro, descricao, medicamentos, procedimentos) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $pet_id, $data_registro, $tipo_registro, $descricao, $medicamentos, $procedimentos);
        
        if ($stmt->execute()) {
            $message = "Registro clínico adicionado com sucesso!";
        } else {
            $error = "Erro ao adicionar registro: " . $conn->error;
        }
        $stmt->close();
    }
    
    // Adicionar atividade agendada
    if ($_POST['action'] === 'add_atividade') {
        $data_atividade = isset($_POST['data_atividade']) ? $_POST['data_atividade'] : date('Y-m-d');
        $hora_atividade = isset($_POST['hora_atividade']) ? $_POST['hora_atividade'] : '09:00:00';
        $tipo_atividade = isset($_POST['tipo_atividade']) ? $_POST['tipo_atividade'] : 'Consulta';
        $descricao_atividade = isset($_POST['descricao_atividade']) ? trim($_POST['descricao_atividade']) : '';
        $status_atividade = isset($_POST['status_atividade']) ? $_POST['status_atividade'] : 'Pendente';
        
        // Criar tabela se não existir
        $conn->query("CREATE TABLE IF NOT EXISTS pet_atividades (
            idAtividade INT AUTO_INCREMENT PRIMARY KEY,
            idPet INT NOT NULL,
            data_atividade DATE NOT NULL,
            hora_atividade TIME NOT NULL,
            tipo_atividade VARCHAR(50) NOT NULL,
            descricao TEXT,
            status VARCHAR(20) DEFAULT 'Pendente',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (idPet) REFERENCES pet(idPet) ON DELETE CASCADE
        )");
        
        $stmt = $conn->prepare("INSERT INTO pet_atividades (idPet, data_atividade, hora_atividade, tipo_atividade, descricao, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $pet_id, $data_atividade, $hora_atividade, $tipo_atividade, $descricao_atividade, $status_atividade);
        
        if ($stmt->execute()) {
            $message = "Atividade agendada com sucesso!";
        } else {
            $error = "Erro ao agendar atividade: " . $conn->error;
        }
        $stmt->close();
    }
}

// Buscar registros clínicos
$registros_clinicos = [];
if ($pet_id > 0) {
    $check = $conn->query("SHOW TABLES LIKE 'pet_registros_clinicos'");
    if ($check->num_rows > 0) {
        $registros = $conn->query("SELECT * FROM pet_registros_clinicos WHERE idPet = $pet_id ORDER BY data_registro DESC");
        if ($registros) {
            $registros_clinicos = $registros->fetch_all(MYSQLI_ASSOC);
        }
    }
}

// Buscar atividades agendadas
$atividades = [];
if ($pet_id > 0) {
    $check = $conn->query("SHOW TABLES LIKE 'pet_atividades'");
    if ($check->num_rows > 0) {
        $ativ = $conn->query("SELECT * FROM pet_atividades WHERE idPet = $pet_id ORDER BY data_atividade DESC, hora_atividade DESC");
        if ($ativ) {
            $atividades = $ativ->fetch_all(MYSQLI_ASSOC);
        }
    }
}

// Buscar histórico de peso
$historico_peso = [];
if ($pet_id > 0) {
    $check = $conn->query("SHOW TABLES LIKE 'pet_historico_peso'");
    if ($check->num_rows > 0) {
        $hist = $conn->query("SELECT * FROM pet_historico_peso WHERE idPet = $pet_id ORDER BY data DESC");
        if ($hist) {
            $historico_peso = $hist->fetch_all(MYSQLI_ASSOC);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pet ? $pet['nome'] : 'Novo Pet'; ?> - PetProject</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f5f5; }
        
        .sidebar {
            width: 250px;
            background: #292F36;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header img { height: 60px; width: 60px; margin-bottom: 10px; }
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: 0.3s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #4ECDC4; padding-left: 30px; }
        .sidebar-menu i { margin-right: 10px; width: 20px; }
        
        .top-bar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            z-index: 99;
        }
        .btn-logout { background: #fc0000; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; }
        
        .main-content {
            margin-left: 250px;
            margin-top: 70px;
            padding: 30px;
        }
        
        .pet-header {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .pet-info h1 { font-size: 2rem; color: #292F36; }
        .pet-info .id { color: #666; font-size: 0.9rem; }
        .pet-actions .btn { padding: 10px 20px; border-radius: 8px; text-decoration: none; margin-left: 10px; }
        .btn-primary { background: #4ECDC4; color: white; border: none; cursor: pointer; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-sm { padding: 5px 10px; font-size: 0.8rem; margin: 0 2px; border-radius: 6px; }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .info-card h3 {
            color: #4ECDC4;
            margin-bottom: 15px;
            border-bottom: 2px solid #4ECDC4;
            padding-bottom: 5px;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        .info-label {
            width: 120px;
            font-weight: 600;
            color: #666;
        }
        .info-value { flex: 1; color: #292F36; }
        
        .tabs {
            display: flex;
            background: white;
            border-radius: 15px 15px 0 0;
            overflow-x: auto;
            border-bottom: 2px solid #e0e0e0;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 15px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            color: #666;
            transition: 0.3s;
        }
        .tab-btn:hover { background: #f5f5f5; }
        .tab-btn.active {
            color: #4ECDC4;
            border-bottom: 3px solid #4ECDC4;
        }
        .tab-content {
            background: white;
            border-radius: 0 0 15px 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: none;
        }
        .tab-content.active { display: block; }
        
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #292F36;
        }
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus {
            outline: none;
            border-color: #4ECDC4;
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .data-table th { background: #f8f9fa; font-weight: 600; }
        .data-table tr:hover { background: #f8f9fa; }
        
        .empty-message { text-align: center; padding: 40px; color: #666; }
        
        .alert { padding: 12px; border-radius: 10px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 20px;
            min-width: 500px;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-pendente { background: #ffc107; color: #000; }
        .badge-concluido { background: #28a745; color: #fff; }
        .badge-cancelado { background: #dc3545; color: #fff; }
        
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .top-bar { left: 0; position: relative; }
            .main-content { margin-left: 0; margin-top: 0; }
            .modal-content { min-width: 90%; margin: 20px; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../img/PetProject.png" alt="PetProject">
            <h3>PetProject</h3>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="tutores.php"><i class="fas fa-users"></i> Tutores</a>
            <a href="pets.php"><i class="fas fa-paw"></i> Pets</a>
            <a href="agendamentos.php"><i class="fas fa-calendar-alt"></i> Agendamentos</a>
            <a href="atendimentos.php"><i class="fas fa-stethoscope"></i> Atendimentos</a>
            <a href="produtos.php"><i class="fas fa-box"></i> Produtos</a>
            <?php if(isAdmin()): ?>
            <a href="funcionarios.php"><i class="fas fa-user-md"></i> Funcionários</a>
            <?php endif; ?>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
    </div>
    
    <div class="top-bar">
        <h2><i class="fas fa-paw"></i> <?php echo $pet ? $pet['nome'] : 'Novo Pet'; ?></h2>
        <div>
            <span><i class="fas fa-user"></i> <?php echo $_SESSION['user_email']; ?></span>
            <a href="logout.php" class="btn-logout">Sair</a>
        </div>
    </div>
    
    <div class="main-content">
        <?php if($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        
        <?php if($is_new || !$pet): ?>
        <div class="info-card">
            <h3>Cadastrar Novo Pet</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_pet">
                <input type="hidden" name="tutor_id" value="<?php echo $tutor_id; ?>">
                <div class="form-group">
                    <label>Nome do Pet *</label>
                    <input type="text" name="nome" required>
                </div>
                <div class="form-group">
                    <label>Espécie</label>
                    <select name="especie">
                        <option value="Canina">Canina (Cachorro)</option>
                        <option value="Felina">Felina (Gato)</option>
                        <option value="Outro">Outro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Raça</label>
                    <input type="text" name="raca">
                </div>
                <div class="form-group">
                    <label>Peso (kg)</label>
                    <input type="number" step="0.001" name="peso">
                </div>
                <div class="form-group">
                    <label>Sexo</label>
                    <select name="sexo">
                        <option value="Macho">Macho</option>
                        <option value="Fêmea">Fêmea</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Cor</label>
                    <input type="text" name="cor">
                </div>
                <div class="form-group">
                    <label>Data de Nascimento</label>
                    <input type="date" name="data_nascimento">
                </div>
                <!-- Campos adicionais para consistência com a edição -->
                <div class="form-group">
                    <label>Castrado</label>
                    <select name="castrado">
                        <option value="Não">Não</option>
                        <option value="Sim">Sim</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Microchip</label>
                    <input type="text" name="microchip" placeholder="Código do microchip (se houver)">
                </div>
                <div class="form-group">
                    <label>Observações</label>
                    <textarea name="observacoes" rows="2" placeholder="Observações iniciais..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="tutores.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
        
        <?php else: ?>
        
        <div class="pet-header">
            <div class="pet-info">
                <h1><?php echo htmlspecialchars($pet['nome']); ?></h1>
                <span class="id">#<?php echo $pet['idPet']; ?></span>
            </div>
            <div class="pet-actions">
                <button class="btn btn-primary" onclick="openEditModal()"><i class="fas fa-edit"></i> Editar Dados</button>
                <button class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> Imprimir</button>
            </div>
        </div>
        
        <div class="info-grid">
            <div class="info-card">
                <h3><i class="fas fa-info-circle"></i> Informações do Pet</h3>
                <div class="info-row"><div class="info-label">Espécie:</div><div class="info-value"><?php echo $pet['especie']; ?></div></div>
                <div class="info-row"><div class="info-label">Raça:</div><div class="info-value"><?php echo $pet['raca'] ?: 'SRD'; ?></div></div>
                <div class="info-row"><div class="info-label">Sexo:</div><div class="info-value"><?php echo $pet['sexo']; ?></div></div>
                <div class="info-row"><div class="info-label">Peso:</div><div class="info-value"><?php echo number_format($pet['peso'], 3, ',', '.'); ?> kg</div></div>
                <div class="info-row"><div class="info-label">Cor:</div><div class="info-value"><?php echo $pet['cor'] ?: 'Não informada'; ?></div></div>
                <div class="info-row"><div class="info-label">Castrado:</div><div class="info-value"><?php echo ($pet['castrado'] == 1) ? 'Sim' : 'Não'; ?></div></div>
                <div class="info-row"><div class="info-label">Microchip:</div><div class="info-value"><?php echo $pet['microchip'] ?: 'Não informado'; ?></div></div>
            </div>
            
            <div class="info-card">
                <h3><i class="fas fa-user"></i> Tutor</h3>
                <div class="info-row"><div class="info-label">Nome:</div><div class="info-value"><?php echo htmlspecialchars($tutor['nome']); ?></div></div>
                <div class="info-row"><div class="info-label">CPF:</div><div class="info-value"><?php echo $tutor['cpf'] ?: 'Não informado'; ?></div></div>
                <div class="info-row"><div class="info-label">Telefone:</div><div class="info-value"><?php echo $tutor['telefone']; ?></div></div>
                <div class="info-row"><div class="info-label">Endereço:</div><div class="info-value"><?php echo $tutor['endereco']; ?></div></div>
            </div>
        </div>
        
        <div class="tabs">
            <button class="tab-btn active" data-tab="registros">📋 Registros Clínicos</button>
            <button class="tab-btn" data-tab="observacoes">📝 Observações</button>
            <button class="tab-btn" data-tab="diagnostico">🏥 Diagnóstico</button>
            <button class="tab-btn" data-tab="notas">📌 Notas Gerais</button>
            <button class="tab-btn" data-tab="atividades">⏰ Atividades Agendadas</button>
            <button class="tab-btn" data-tab="peso">⚖️ Histórico de Peso</button>
        </div>
        
        <div id="registros" class="tab-content active">
            <button class="btn btn-primary btn-sm" onclick="openRegistroModal()">
                <i class="fas fa-plus"></i> Adicionar Registro Clínico
            </button>
            <table class="data-table" style="margin-top: 15px;">
                <thead>
                    <tr><th>Data</th><th>Tipo</th><th>Descrição</th><th>Medicamentos</th><th>Procedimentos</th></tr>
                </thead>
                <tbody>
                    <?php if(empty($registros_clinicos)): ?>
                        <tr><td colspan="5" class="empty-message">Nenhum registro clínico encontrado</td></tr>
                    <?php else: ?>
                        <?php foreach($registros_clinicos as $rc): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($rc['data_registro'])); ?></td>
                            <td><?php echo $rc['tipo_registro']; ?></td>
                            <td><?php echo nl2br(htmlspecialchars($rc['descricao'])); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($rc['medicamentos'])); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($rc['procedimentos'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div id="observacoes" class="tab-content">
            <div class="info-card">
                <h3><i class="fas fa-comment-dots"></i> Observações do Pet</h3>
                <div class="info-value" style="white-space: pre-wrap; line-height: 1.6;">
                    <?php echo !empty($pet['observacoes']) ? nl2br(htmlspecialchars($pet['observacoes'])) : '<em style="color: #999;">Nenhuma observação registrada</em>'; ?>
                </div>
            </div>
        </div>
        
        <div id="diagnostico" class="tab-content">
            <div class="info-card">
                <h3><i class="fas fa-stethoscope"></i> Diagnóstico</h3>
                <div class="info-value" style="white-space: pre-wrap; line-height: 1.6;">
                    <?php echo !empty($pet['diagnostico']) ? nl2br(htmlspecialchars($pet['diagnostico'])) : '<em style="color: #999;">Nenhum diagnóstico registrado</em>'; ?>
                </div>
            </div>
        </div>
        
        <div id="notas" class="tab-content">
            <div class="info-card">
                <h3><i class="fas fa-pen-alt"></i> Notas Gerais</h3>
                <div class="info-value" style="white-space: pre-wrap; line-height: 1.6;">
                    <?php echo !empty($pet['notas_gerais']) ? nl2br(htmlspecialchars($pet['notas_gerais'])) : '<em style="color: #999;">Nenhuma nota registrada</em>'; ?>
                </div>
            </div>
            <?php if(!empty($pet['alertas'])): ?>
                <div class="info-card" style="margin-top: 15px; border-left: 4px solid #ffc107;">
                    <h3><i class="fas fa-bell"></i> Alertas</h3>
                    <div class="info-value" style="white-space: pre-wrap; line-height: 1.6; color: #856404;">
                        <?php echo nl2br(htmlspecialchars($pet['alertas'])); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="atividades" class="tab-content">
            <button class="btn btn-primary btn-sm" onclick="openAtividadeModal()">
                <i class="fas fa-plus"></i> Nova Atividade
            </button>
            <table class="data-table" style="margin-top: 15px;">
                <thead>
                    <tr><th>Data</th><th>Hora</th><th>Tipo</th><th>Descrição</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php if(empty($atividades)): ?>
                        <tr><td colspan="5" class="empty-message">Nenhuma atividade agendada</td></tr>
                    <?php else: ?>
                        <?php foreach($atividades as $atv): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($atv['data_atividade'])); ?></td>
                            <td><?php echo substr($atv['hora_atividade'], 0, 5); ?></td>
                            <td><?php echo $atv['tipo_atividade']; ?></td>
                            <td><?php echo htmlspecialchars($atv['descricao']); ?></td>
                            <td><span class="badge badge-<?php echo $atv['status'] == 'Concluído' ? 'concluido' : ($atv['status'] == 'Cancelado' ? 'cancelado' : 'pendente'); ?>"><?php echo $atv['status']; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div id="peso" class="tab-content">
            <button class="btn btn-primary btn-sm" onclick="openPesoModal()">
                <i class="fas fa-plus"></i> Adicionar Peso
            </button>
            <table class="data-table" style="margin-top: 15px;">
                <thead><tr><th>Data</th><th>Peso (kg)</th></tr></thead>
                <tbody>
                    <?php if(empty($historico_peso)): ?>
                        <tr><td colspan="2" class="empty-message">Nenhum registro de peso</td></tr>
                    <?php else: ?>
                        <?php foreach($historico_peso as $hp): ?>
                        <tr><td><?php echo date('d/m/Y', strtotime($hp['data'])); ?></td><td><?php echo $hp['peso']; ?> kg</td></tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php endif; ?>
    </div>
    
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-edit"></i> Editar <?php echo htmlspecialchars($pet['nome']); ?></h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_pet">
                <div class="form-group">
                    <label>Nome do Pet</label>
                    <input type="text" name="nome" value="<?php echo htmlspecialchars($pet['nome']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Espécie</label>
                    <select name="especie">
                        <option value="Canina" <?php echo $pet['especie'] == 'Canina' ? 'selected' : ''; ?>>Canina</option>
                        <option value="Felina" <?php echo $pet['especie'] == 'Felina' ? 'selected' : ''; ?>>Felina</option>
                        <option value="Outro" <?php echo $pet['especie'] == 'Outro' ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Raça</label>
                    <input type="text" name="raca" value="<?php echo htmlspecialchars($pet['raca']); ?>">
                </div>
                <div class="form-group">
                    <label>Peso (kg)</label>
                    <input type="number" step="0.001" name="peso" value="<?php echo $pet['peso']; ?>">
                </div>
                <div class="form-group">
                    <label>Sexo</label>
                    <select name="sexo">
                        <option value="Macho" <?php echo $pet['sexo'] == 'Macho' ? 'selected' : ''; ?>>Macho</option>
                        <option value="Fêmea" <?php echo $pet['sexo'] == 'Fêmea' ? 'selected' : ''; ?>>Fêmea</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Cor</label>
                    <input type="text" name="cor" value="<?php echo htmlspecialchars($pet['cor']); ?>">
                </div>
                <div class="form-group">
                    <label>Castrado</label>
                    <select name="castrado">
                        <option value="Sim" <?php echo $pet['castrado'] == 1 ? 'selected' : ''; ?>>Sim</option>
                        <option value="Não" <?php echo $pet['castrado'] == 0 ? 'selected' : ''; ?>>Não</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Microchip</label>
                    <input type="text" name="microchip" value="<?php echo htmlspecialchars($pet['microchip']); ?>">
                </div>
                <div class="form-group">
                    <label>Data de Nascimento</label>
                    <input type="date" name="data_nascimento" value="<?php echo $pet['data_nascimento']; ?>">
                </div>
                <div class="form-group">
                    <label>Observações</label>
                    <textarea name="observacoes" rows="3"><?php echo htmlspecialchars($pet['observacoes']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Diagnóstico</label>
                    <textarea name="diagnostico" rows="3"><?php echo htmlspecialchars($pet['diagnostico']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Notas Gerais</label>
                    <textarea name="notas_gerais" rows="3"><?php echo htmlspecialchars($pet['notas_gerais']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Alertas</label>
                    <textarea name="alertas" rows="2" placeholder="Alertas importantes sobre o pet..."><?php echo htmlspecialchars($pet['alertas']); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancelar</button>
            </form>
        </div>
    </div>
    
    <div id="registroModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-notes-medical"></i> Adicionar Registro Clínico</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_registro">
                <div class="form-group">
                    <label>Data</label>
                    <input type="date" name="data_registro" required>
                </div>
                <div class="form-group">
                    <label>Tipo de Registro</label>
                    <select name="tipo_registro" required>
                        <option value="Consulta">🏥 Consulta</option>
                        <option value="Vacina">💉 Vacina</option>
                        <option value="Exame">🔬 Exame</option>
                        <option value="Cirurgia">🔪 Cirurgia</option>
                        <option value="Retorno">📋 Retorno</option>
                        <option value="Emergência">🚨 Emergência</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Descrição</label>
                    <textarea name="descricao" rows="3" required placeholder="Descreva o atendimento, sintomas, etc..."></textarea>
                </div>
                <div class="form-group">
                    <label>Medicamentos Prescritos</label>
                    <textarea name="medicamentos" rows="2" placeholder="Medicamentos, dosagens, período..."></textarea>
                </div>
                <div class="form-group">
                    <label>Procedimentos Realizados</label>
                    <textarea name="procedimentos" rows="2" placeholder="Procedimentos, exames, etc..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Salvar</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('registroModal')">Cancelar</button>
            </form>
        </div>
    </div>
    
    <div id="atividadeModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-calendar-plus"></i> Agendar Atividade</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_atividade">
                <div class="form-group">
                    <label>Data</label>
                    <input type="date" name="data_atividade" required>
                </div>
                <div class="form-group">
                    <label>Hora</label>
                    <input type="time" name="hora_atividade" required>
                </div>
                <div class="form-group">
                    <label>Tipo de Atividade</label>
                    <select name="tipo_atividade" required>
                        <option value="Consulta">🏥 Consulta</option>
                        <option value="Vacina">💉 Vacina</option>
                        <option value="Banho">🛁 Banho</option>
                        <option value="Tosa">✂️ Tosa</option>
                        <option value="Cirurgia">🔪 Cirurgia</option>
                        <option value="Exame">🔬 Exame</option>
                        <option value="Retorno">📋 Retorno</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Descrição</label>
                    <textarea name="descricao_atividade" rows="2" placeholder="Detalhes da atividade..."></textarea>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status_atividade">
                        <option value="Pendente">⏳ Pendente</option>
                        <option value="Concluído">✅ Concluído</option>
                        <option value="Cancelado">❌ Cancelado</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Agendar</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('atividadeModal')">Cancelar</button>
            </form>
        </div>
    </div>
    
    <div id="pesoModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-weight"></i> Registrar Peso</h3>
            <form method="POST" action="add_peso.php">
                <input type="hidden" name="pet_id" value="<?php echo $pet_id; ?>">
                <div class="form-group">
                    <label>Data</label>
                    <input type="date" name="data" required>
                </div>
                <div class="form-group">
                    <label>Peso (kg)</label>
                    <input type="number" step="0.001" name="peso" required>
                </div>
                <button type="submit" class="btn btn-primary">Salvar</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('pesoModal')">Cancelar</button>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(btn.dataset.tab).classList.add('active');
            });
        });
        
        function openEditModal() { document.getElementById('editModal').style.display = 'flex'; }
        function openRegistroModal() { document.getElementById('registroModal').style.display = 'flex'; }
        function openAtividadeModal() { document.getElementById('atividadeModal').style.display = 'flex'; }
        function openPesoModal() { document.getElementById('pesoModal').style.display = 'flex'; }
        
        function closeModal(modalId) { 
            document.getElementById(modalId).style.display = 'none'; 
        }
        
        window.onclick = function(event) {
            const modals = ['editModal', 'registroModal', 'atividadeModal', 'pesoModal'];
            modals.forEach(modalId => {
                let modal = document.getElementById(modalId);
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const dateInputs = document.querySelectorAll('input[type="date"]');
            dateInputs.forEach(input => {
                if (!input.value) input.value = today;
            });
        });
    </script>
</body>
</html>