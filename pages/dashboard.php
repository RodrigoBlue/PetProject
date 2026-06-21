<?php
// dashboard.php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Verificar se é funcionário
requireFuncionario();

// Buscar dados para o dashboard
$conn = getConnection();

// Total de pets cadastrados
$sql_pets = "SELECT COUNT(*) as total FROM pet";
$result_pets = $conn->query($sql_pets);
$total_pets = $result_pets->fetch_assoc()['total'];

// Total de tutores cadastrados
$sql_tutores = "SELECT COUNT(*) as total FROM tutor";
$result_tutores = $conn->query($sql_tutores);
$total_tutores = $result_tutores->fetch_assoc()['total'];

// Total de atendimentos hoje (incluindo registros clínicos) - CORRIGIDO
$sql_atendimentos_hoje = "SELECT 
    (SELECT COUNT(*) FROM atendimento WHERE data = CURDATE()) + 
    (SELECT COUNT(*) FROM pet_registros_clinicos WHERE data_registro = CURDATE()) as total";
$result_atendimentos = $conn->query($sql_atendimentos_hoje);
$total_atendimentos_hoje = $result_atendimentos->fetch_assoc()['total'];

// Próximos agendamentos
$sql_agendamentos = "SELECT a.idAgendamento, a.data, a.hora, p.nome as pet_nome, t.nome as tutor_nome 
                     FROM agendamento a 
                     JOIN pet p ON a.idPet = p.idPet 
                     JOIN tutor t ON p.idTutor = t.idTutor 
                     WHERE a.data >= CURDATE() 
                     ORDER BY a.data, a.hora 
                     LIMIT 10";
$agendamentos = $conn->query($sql_agendamentos);

// Últimos atendimentos - Buscar de atendimento E pet_registros_clinicos (CORRIGIDO)
$sql_atendimentos = "
    (SELECT 
        a.idAtendimento as id,
        a.data,
        a.hora,
        p.nome as pet_nome,
        s.tipo as servico,
        f.nome as funcionario,
        'atendimento' as origem
    FROM atendimento a 
    JOIN pet p ON a.idPet = p.idPet 
    JOIN servico s ON a.idServico = s.idServico 
    JOIN funcionario f ON a.idFuncionario = f.idFuncionario)
    
    UNION ALL
    
    (SELECT 
        rc.idRegistro as id,
        rc.data_registro as data,
        COALESCE(rc.hora_registro, '12:00:00') as hora,
        p.nome as pet_nome,
        rc.tipo_registro as servico,
        'Registro Clínico' as funcionario,
        'registro_clinico' as origem
    FROM pet_registros_clinicos rc
    JOIN pet p ON rc.idPet = p.idPet)
    
    ORDER BY data DESC, hora DESC 
    LIMIT 10";
$atendimentos = $conn->query($sql_atendimentos);

// Verificar se a query de atendimentos funcionou
if (!$atendimentos) {
    // Se a tabela pet_registros_clinicos não existir, usar apenas atendimentos
    $sql_atendimentos_fallback = "SELECT 
        a.idAtendimento as id,
        a.data,
        a.hora,
        p.nome as pet_nome,
        s.tipo as servico,
        f.nome as funcionario,
        'atendimento' as origem
    FROM atendimento a 
    JOIN pet p ON a.idPet = p.idPet 
    JOIN servico s ON a.idServico = s.idServico 
    JOIN funcionario f ON a.idFuncionario = f.idFuncionario
    ORDER BY a.data DESC, a.hora DESC 
    LIMIT 10";
    $atendimentos = $conn->query($sql_atendimentos_fallback);
}

// Total de agendamentos para hoje
$sql_agendamentos_hoje = "SELECT COUNT(*) as total FROM agendamento WHERE data = CURDATE()";
$result_agendamentos_hoje = $conn->query($sql_agendamentos_hoje);
$total_agendamentos_hoje = $result_agendamentos_hoje->fetch_assoc()['total'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PetProject</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../img/PetProject.png" alt="PetProject">
            <h3>PetProject</h3>
            <p>Sistema Veterinário</p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="tutores.php">
                <i class="fas fa-users"></i> Tutores
            </a>
            <a href="pets.php">
                <i class="fas fa-paw"></i> Pets
            </a>
            <a href="agendamentos.php">
                <i class="fas fa-calendar-alt"></i> Agendamentos
            </a>
            <a href="atendimentos.php">
                <i class="fas fa-stethoscope"></i> Atendimentos
            </a>
            <a href="produtos.php">
                <i class="fas fa-box"></i> Produtos
            </a>
            <?php if(isAdmin()): ?>
            <a href="funcionarios.php">
                <i class="fas fa-user-md"></i> Funcionários
            </a>
            <?php endif; ?>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </div>
    
    <!-- Top Bar -->
    <div class="top-bar">
        <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
        <div class="user-info">
            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total de Pets</h3>
                    <div class="number"><?php echo $total_pets; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-paw"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total de Tutores</h3>
                    <div class="number"><?php echo $total_tutores; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Atendimentos Hoje</h3>
                    <div class="number"><?php echo $total_atendimentos_hoje; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-stethoscope"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Agendamentos Hoje</h3>
                    <div class="number"><?php echo $total_agendamentos_hoje; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>
        </div>
        
        <!-- Próximos Agendamentos -->
        <div class="data-section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-calendar-alt"></i> Próximos Agendamentos
                </h3>
                <a href="agendamentos.php" class="btn-link">
                    Ver todos <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <?php if ($agendamentos && $agendamentos->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Hora</th>
                            <th>Pet</th>
                            <th>Tutor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $agendamentos->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($row['data'])); ?></td>
                            <td><?php echo substr($row['hora'], 0, 5); ?></td>
                            <td><?php echo htmlspecialchars($row['pet_nome']); ?></td>
                            <td><?php echo htmlspecialchars($row['tutor_nome']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-message">
                    <i class="fas fa-info-circle"></i> Nenhum agendamento encontrado.
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Últimos Atendimentos -->
        <div class="data-section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-history"></i> Últimos Atendimentos
                </h3>
                <a href="atendimentos.php" class="btn-link">
                    Ver todos <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <?php if ($atendimentos && $atendimentos->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Hora</th>
                            <th>Pet</th>
                            <th>Serviço</th>
                            <th>Funcionário</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $atendimentos->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($row['data'])); ?></td>
                            <td><?php echo substr($row['hora'], 0, 5); ?></td>
                            <td><?php echo htmlspecialchars($row['pet_nome']); ?> 
                                <?php if(isset($row['origem']) && $row['origem'] == 'registro_clinico'): ?>
                                     
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['servico']); ?></td>
                            <td><?php echo htmlspecialchars($row['funcionario']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-message">
                    <i class="fas fa-info-circle"></i> Nenhum atendimento encontrado.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>