<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireFuncionario();

$conn = getConnection();
$message = '';
$error = '';

// Verificar se é edição ou novo
$pet_id = $_GET['id'] ?? 0;
$tutor_id = $_GET['tutor_id'] ?? 0;
$is_new = ($_GET['action'] ?? '') === 'new' || $pet_id == 0;

// Buscar dados do pet se for edição
$pet = null;
if (!$is_new && $pet_id > 0) {
    $result = $conn->query("SELECT p.*, t.nome as tutor_nome, t.cpf, t.telefone, t.endereco 
                            FROM pet p 
                            JOIN tutor t ON p.idTutor = t.idTutor 
                            WHERE p.idPet = $pet_id");
    $pet = $result->fetch_assoc();
    if ($pet) {
        $tutor_id = $pet['idTutor'];
    }
}

// Buscar dados do tutor
$tutor = null;
if ($tutor_id > 0) {
    $result = $conn->query("SELECT * FROM tutor WHERE idTutor = $tutor_id");
    $tutor = $result->fetch_assoc();
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $especie = $_POST['especie'];
    $raca = trim($_POST['raca']);
    $peso = $_POST['peso'];
    $sexo = $_POST['sexo'];
    $cor = trim($_POST['cor']);
    $castrado = $_POST['castrado'] ?? 'Não';
    $microchip = trim($_POST['microchip']);
    $data_nascimento = $_POST['data_nascimento'] ?? null;
    $tutor_id = $_POST['tutor_id'];
    
    if ($is_new) {
        $stmt = $conn->prepare("INSERT INTO pet (nome, especie, raca, peso, sexo, cor, castrado, microchip, data_nascimento, idTutor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdsssssi", $nome, $especie, $raca, $peso, $sexo, $cor, $castrado, $microchip, $data_nascimento, $tutor_id);
        if ($stmt->execute()) {
            $pet_id = $conn->insert_id;
            $message = "Pet cadastrado com sucesso!";
            header("Location: pet_detalhe.php?id=$pet_id");
            exit();
        } else {
            $error = "Erro ao cadastrar: " . $conn->error;
        }
    } else {
        $stmt = $conn->prepare("UPDATE pet SET nome=?, especie=?, raca=?, peso=?, sexo=?, cor=?, castrado=?, microchip=?, data_nascimento=? WHERE idPet=?");
        $stmt->bind_param("sssdsssssi", $nome, $especie, $raca, $peso, $sexo, $cor, $castrado, $microchip, $data_nascimento, $pet_id);
        if ($stmt->execute()) {
            $message = "Pet atualizado com sucesso!";
            header("Location: pet_detalhe.php?id=$pet_id");
            exit();
        } else {
            $error = "Erro ao atualizar: " . $conn->error;
        }
    }
}

// Buscar registros clínicos (atendimentos)
$registros_clinicos = [];
if ($pet_id > 0) {
    $registros = $conn->query("SELECT a.*, s.tipo as servico, f.nome as funcionario 
                               FROM atendimento a 
                               JOIN servico s ON a.idServico = s.idServico 
                               JOIN funcionario f ON a.idFuncionario = f.idFuncionario 
                               WHERE a.idPet = $pet_id 
                               ORDER BY a.data DESC, a.hora DESC");
    $registros_clinicos = $registros->fetch_all(MYSQLI_ASSOC);
}

// Buscar agendamentos
$agendamentos = [];
if ($pet_id > 0) {
    $agend = $conn->query("SELECT * FROM agendamento WHERE idPet = $pet_id ORDER BY data DESC, hora DESC");
    $agendamentos = $agend->fetch_all(MYSQLI_ASSOC);
}

// Buscar vacinas (vamos criar uma tabela se não existir)
$vacinas = [];
if ($pet_id > 0) {
    // Verificar se tabela vacinas existe
    $check = $conn->query("SHOW TABLES LIKE 'vacina'");
    if ($check->num_rows > 0) {
        $vac = $conn->query("SELECT * FROM vacina WHERE idPet = $pet_id ORDER BY data_aplicacao DESC");
        $vacinas = $vac->fetch_all(MYSQLI_ASSOC);
    }
}

// Buscar exames
$exames = [];
if ($pet_id > 0) {
    $check = $conn->query("SHOW TABLES LIKE 'exame'");
    if ($check->num_rows > 0) {
        $ex = $conn->query("SELECT * FROM exame WHERE idPet = $pet_id ORDER BY data_solicitacao DESC");
        $exames = $ex->fetch_all(MYSQLI_ASSOC);
    }
}

// Buscar produtos/medicamentos indicados
$receitas = [];
if ($pet_id > 0) {
    $rec = $conn->query("SELECT pp.*, pr.nome as produto_nome, pr.valor 
                         FROM pet_produto pp 
                         JOIN produto pr ON pp.idProduto = pr.idProduto 
                         WHERE pp.idPet = $pet_id 
                         ORDER BY pp.data_indicacao DESC");
    $receitas = $rec->fetch_all(MYSQLI_ASSOC);
}

// Buscar histórico de peso
$historico_peso = [];
if ($pet_id > 0) {
    $hist = $conn->query("SELECT data, peso FROM pet_historico_peso WHERE idPet = $pet_id ORDER BY data DESC");
    if ($hist && $hist->num_rows > 0) {
        $historico_peso = $hist->fetch_all(MYSQLI_ASSOC);
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
        
        /* Sidebar */
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
        
        /* Top Bar */
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
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            margin-top: 70px;
            padding: 30px;
        }
        
        /* Header do Pet */
        .pet-header {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .pet-info h1 { font-size: 2rem; color: #292F36; }
        .pet-info .id { color: #666; font-size: 0.9rem; }
        .pet-actions .btn { padding: 10px 20px; border-radius: 5px; text-decoration: none; margin-left: 10px; }
        .btn-primary { background: #4ECDC4; color: white; border: none; cursor: pointer; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; color: white; }
        
        /* Cards de informações */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
        
        /* Tabs */
        .tabs {
            display: flex;
            background: white;
            border-radius: 10px 10px 0 0;
            overflow-x: auto;
            border-bottom: 2px solid #e0e0e0;
        }
        .tab-btn {
            padding: 15px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
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
            border-radius: 0 0 10px 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: none;
        }
        .tab-content.active { display: block; }
        
        /* Tabelas */
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
        
        .btn-sm { padding: 5px 10px; font-size: 0.8rem; margin: 0 2px; }
        .empty-message { text-align: center; padding: 40px; color: #666; }
        
        .alert { padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        
        /* Modal */
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
            border-radius: 10px;
            min-width: 500px;
            max-width: 600px;
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .top-bar { left: 0; position: relative; }
            .main-content { margin-left: 0; margin-top: 0; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
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
    
    <!-- Top Bar -->
    <div class="top-bar">
        <h2><i class="fas fa-paw"></i> <?php echo $pet ? $pet['nome'] : 'Novo Pet'; ?></h2>
        <div class="user-info">
            <span><i class="fas fa-user"></i> <?php echo $_SESSION['user_email']; ?></span>
            <a href="logout.php" class="btn-logout">Sair</a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <?php if($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        
        <?php if($is_new || !$pet): ?>
        <!-- Formulário de Cadastro/Edição -->
        <div class="info-card">
            <h3><?php echo $is_new ? 'Cadastrar Novo Pet' : 'Editar Pet'; ?></h3>
            <form method="POST">
                <input type="hidden" name="tutor_id" value="<?php echo $tutor_id; ?>">
                <div class="form-group">
                    <label>Nome do Pet *</label>
                    <input type="text" name="nome" required value="<?php echo $pet['nome'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Espécie</label>
                    <select name="especie">
                        <option value="Canina" <?php echo ($pet['especie'] ?? '') == 'Canina' ? 'selected' : ''; ?>>Canina (Cachorro)</option>
                        <option value="Felina" <?php echo ($pet['especie'] ?? '') == 'Felina' ? 'selected' : ''; ?>>Felina (Gato)</option>
                        <option value="Outro" <?php echo ($pet['especie'] ?? '') == 'Outro' ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Raça</label>
                    <input type="text" name="raca" value="<?php echo $pet['raca'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Peso (kg)</label>
                    <input type="number" step="0.001" name="peso" value="<?php echo $pet['peso'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Sexo</label>
                    <select name="sexo">
                        <option value="Macho" <?php echo ($pet['sexo'] ?? '') == 'Macho' ? 'selected' : ''; ?>>Macho</option>
                        <option value="Fêmea" <?php echo ($pet['sexo'] ?? '') == 'Fêmea' ? 'selected' : ''; ?>>Fêmea</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Cor</label>
                    <input type="text" name="cor" value="<?php echo $pet['cor'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Castrado</label>
                    <select name="castrado">
                        <option value="Sim" <?php echo ($pet['castrado'] ?? '') == 'Sim' ? 'selected' : ''; ?>>Sim</option>
                        <option value="Não" <?php echo ($pet['castrado'] ?? '') == 'Não' ? 'selected' : ''; ?>>Não</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Microchip</label>
                    <input type="text" name="microchip" value="<?php echo $pet['microchip'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Data de Nascimento</label>
                    <input type="date" name="data_nascimento" value="<?php echo $pet['data_nascimento'] ?? ''; ?>">
                </div>
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="tutores.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
        
        <?php else: ?>
        <!-- Visualização do Pet (estilo SimpleVet) -->
        
        <!-- Cabeçalho do Pet -->
        <div class="pet-header">
            <div class="pet-info">
                <h1><?php echo htmlspecialchars($pet['nome']); ?></h1>
                <span class="id">#<?php echo $pet['idPet']; ?></span>
            </div>
            <div class="pet-actions">
                <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Imprimir</button>
                <a href="pet_detalhe.php?id=<?php echo $pet['idPet']; ?>&action=edit" class="btn btn-warning"><i class="fas fa-edit"></i> Alterar</a>
            </div>
        </div>
        
        <!-- Grid de Informações -->
        <div class="info-grid">
            <div class="info-card">
                <h3><i class="fas fa-info-circle"></i> Informações do Pet</h3>
                <div class="info-row"><div class="info-label">Espécie:</div><div class="info-value"><?php echo $pet['especie']; ?></div></div>
                <div class="info-row"><div class="info-label">Raça:</div><div class="info-value"><?php echo $pet['raca'] ?: 'SRD'; ?></div></div>
                <div class="info-row"><div class="info-label">Porte:</div><div class="info-value"><?php echo $pet['peso'] < 10 ? 'Pequeno' : ($pet['peso'] < 25 ? 'Médio' : 'Grande'); ?></div></div>
                <div class="info-row"><div class="info-label">Sexo:</div><div class="info-value"><?php echo $pet['sexo']; ?></div></div>
                <div class="info-row"><div class="info-label">Microchip:</div><div class="info-value"><?php echo $pet['microchip'] ?: 'Não informado'; ?></div></div>
                <div class="info-row"><div class="info-label">Peso:</div><div class="info-value"><?php echo number_format($pet['peso'], 3, ',', '.'); ?> kg</div></div>
                <div class="info-row"><div class="info-label">Cor:</div><div class="info-value"><?php echo $pet['cor'] ?: 'Não informada'; ?></div></div>
                <div class="info-row"><div class="info-label">Castrado:</div><div class="info-value"><?php echo $pet['castrado'] ?? 'Não'; ?></div></div>
                <div class="info-row"><div class="info-label">Última visita:</div><div class="info-value"><?php echo isset($registros_clinicos[0]) ? date('d/m/Y', strtotime($registros_clinicos[0]['data'])) : 'Nenhuma visita'; ?></div></div>
            </div>
            
            <div class="info-card">
                <h3><i class="fas fa-user"></i> Informações do Tutor</h3>
                <div class="info-row"><div class="info-label">Nome:</div><div class="info-value"><?php echo htmlspecialchars($tutor['nome']); ?></div></div>
                <div class="info-row"><div class="info-label">CPF/CNPJ:</div><div class="info-value"><?php echo $tutor['cpf'] ?: 'Não informado'; ?></div></div>
                <div class="info-row"><div class="info-label">Telefone:</div><div class="info-value"><?php echo $tutor['telefone']; ?></div></div>
                <div class="info-row"><div class="info-label">Endereço:</div><div class="info-value"><?php echo $tutor['endereco']; ?></div></div>
            </div>
        </div>
        
        <!-- Abas -->
        <div class="tabs">
            <button class="tab-btn active" data-tab="visao-geral">Visão Geral</button>
            <button class="tab-btn" data-tab="comanda">Comanda</button>
            <button class="tab-btn" data-tab="agendamentos">Agendamentos</button>
            <button class="tab-btn" data-tab="registros">Registros Clínicos</button>
            <button class="tab-btn" data-tab="exames">Exames</button>
            <button class="tab-btn" data-tab="vacinas">Vacinas</button>
            <button class="tab-btn" data-tab="receitas">Receitas</button>
        </div>
        
        <!-- Tab: Visão Geral -->
        <div id="visao-geral" class="tab-content active">
            <h3>Histórico de Peso</h3>
            <button class="btn btn-primary btn-sm" onclick="openPesoModal()"><i class="fas fa-plus"></i> Adicionar</button>
            <table class="data-table">
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
        
        <!-- Tab: Comanda -->
        <div id="comanda" class="tab-content">
            <button class="btn btn-primary btn-sm" onclick="openComandaModal()"><i class="fas fa-plus"></i> Adicionar Produto/Serviço</button>
            <div class="empty-message">Nenhum item na comanda</div>
        </div>
        
        <!-- Tab: Agendamentos -->
        <div id="agendamentos" class="tab-content">
            <button class="btn btn-primary btn-sm" onclick="openAgendamentoModal()"><i class="fas fa-plus"></i> Novo Agendamento</button>
            <table class="data-table">
                <thead><tr><th>Data</th><th>Hora</th><th>Status</th><th>Ações</th></tr></thead>
                <tbody>
                    <?php if(empty($agendamentos)): ?>
                    <tr><td colspan="4" class="empty-message">Nenhum agendamento</td></tr>
                    <?php else: ?>
                        <?php foreach($agendamentos as $ag): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($ag['data'])); ?></td>
                            <td><?php echo substr($ag['hora'], 0, 5); ?></td>
                            <td>Agendado</td>
                            <td><button class="btn btn-danger btn-sm" onclick="deleteAgendamento(<?php echo $ag['idAgendamento']; ?>)">Cancelar</button></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Tab: Registros Clínicos -->
        <div id="registros" class="tab-content">
            <a href="novo_atendimento.php?pet_id=<?php echo $pet['idPet']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Adicionar Registro</a>
            <table class="data-table">
                <thead><tr><th>Data</th><th>Serviço</th><th>Funcionário</th></tr></thead>
                <tbody>
                    <?php if(empty($registros_clinicos)): ?>
                    <tr><td colspan="3" class="empty-message">Nenhum registro clínico</td></tr>
                    <?php else: ?>
                        <?php foreach($registros_clinicos as $rc): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($rc['data'])); ?></td>
                            <td><?php echo $rc['servico']; ?></td>
                            <td><?php echo $rc['funcionario']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Tab: Exames -->
        <div id="exames" class="tab-content">
            <button class="btn btn-primary btn-sm" onclick="openExameModal()"><i class="fas fa-plus"></i> Solicitar Exame</button>
            <div class="empty-message">Nenhum exame solicitado</div>
        </div>
        
        <!-- Tab: Vacinas -->
        <div id="vacinas" class="tab-content">
            <button class="btn btn-primary btn-sm" onclick="openVacinaModal()"><i class="fas fa-plus"></i> Registrar Vacina</button>
            <div class="empty-message">Nenhuma vacina registrada</div>
        </div>
        
        <!-- Tab: Receitas -->
        <div id="receitas" class="tab-content">
            <button class="btn btn-primary btn-sm" onclick="openReceitaModal()"><i class="fas fa-plus"></i> Adicionar Receita</button>
            <table class="data-table">
                <thead><tr><th>Produto/Medicamento</th><th>Quantidade</th><th>Instruções</th><th>Data</th></tr></thead>
                <tbody>
                    <?php if(empty($receitas)): ?>
                    <tr><td colspan="4" class="empty-message">Nenhuma receita registrada</td></tr>
                    <?php else: ?>
                        <?php foreach($receitas as $rec): ?>
                        <tr>
                            <td><?php echo $rec['produto_nome']; ?></td>
                            <td><?php echo $rec['quantidade']; ?></td>
                            <td><?php echo $rec['instrucoes']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($rec['data_indicacao'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php endif; ?>
    </div>
    
    <!-- Modal para Adicionar Peso -->
    <div id="pesoModal" class="modal">
        <div class="modal-content">
            <h3>Adicionar Registro de Peso</h3>
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
    
    <!-- Modal para Agendamento -->
    <div id="agendamentoModal" class="modal">
        <div class="modal-content">
            <h3>Novo Agendamento</h3>
            <form method="POST" action="add_agendamento.php">
                <input type="hidden" name="pet_id" value="<?php echo $pet_id; ?>">
                <div class="form-group">
                    <label>Data</label>
                    <input type="date" name="data" required>
                </div>
                <div class="form-group">
                    <label>Hora</label>
                    <input type="time" name="hora" required>
                </div>
                <button type="submit" class="btn btn-primary">Salvar</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('agendamentoModal')">Cancelar</button>
            </form>
        </div>
    </div>
    
    <!-- Modal para Receita -->
    <div id="receitaModal" class="modal">
        <div class="modal-content">
            <h3>Adicionar Receita/Indicação</h3>
            <form method="POST" action="add_receita.php">
                <input type="hidden" name="pet_id" value="<?php echo $pet_id; ?>">
                <div class="form-group">
                    <label>Produto/Medicamento</label>
                    <select name="produto_id" required>
                        <option value="">Selecione...</option>
                        <?php
                        $produtos = $conn->query("SELECT idProduto, nome FROM produto");
                        while($prod = $produtos->fetch_assoc()):
                        ?>
                        <option value="<?php echo $prod['idProduto']; ?>"><?php echo $prod['nome']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantidade/Dosagem</label>
                    <input type="text" name="quantidade" placeholder="Ex: 1 comprimido a cada 12h">
                </div>
                <div class="form-group">
                    <label>Instruções</label>
                    <textarea name="instrucoes" rows="3" placeholder="Instruções de uso..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Salvar</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('receitaModal')">Cancelar</button>
            </form>
        </div>
    </div>
    
    <script>
        // Tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(btn.dataset.tab).classList.add('active');
            });
        });
        
        function openPesoModal() { document.getElementById('pesoModal').style.display = 'flex'; }
        function openAgendamentoModal() { document.getElementById('agendamentoModal').style.display = 'flex'; }
        function openReceitaModal() { document.getElementById('receitaModal').style.display = 'flex'; }
        function openExameModal() { alert('Funcionalidade em desenvolvimento'); }
        function openVacinaModal() { alert('Funcionalidade em desenvolvimento'); }
        function openComandaModal