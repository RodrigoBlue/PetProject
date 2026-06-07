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

// Total de atendimentos hoje
$sql_atendimentos_hoje = "SELECT COUNT(*) as total FROM atendimento WHERE data = CURDATE()";
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

// Últimos atendimentos - CORRIGIDO (sem coluna observacoes)
$sql_atendimentos = "SELECT a.idAtendimento, a.data, a.hora, p.nome as pet_nome, s.tipo as servico, f.nome as funcionario
                     FROM atendimento a 
                     JOIN pet p ON a.idPet = p.idPet 
                     JOIN servico s ON a.idServico = s.idServico 
                     JOIN funcionario f ON a.idFuncionario = f.idFuncionario 
                     ORDER BY a.data DESC, a.hora DESC 
                     LIMIT 10";
$atendimentos = $conn->query($sql_atendimentos);

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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #292F36;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header img {
            height: 60px;
            width: 60px;
            margin-bottom: 10px;
        }
        
        .sidebar-header h3 {
            font-size: 1.2rem;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover {
            background-color: #4ECDC4;
            padding-left: 30px;
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
        }
        
        .sidebar-menu .active {
            background-color: #4ECDC4;
        }
        
        /* Top Bar */
        .top-bar {
            background-color: white;
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
        
        .top-bar h2 {
            font-size: 1.5rem;
            color: #292F36;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info span {
            color: #292F36;
            font-weight: 500;
        }
        
        .btn-logout {
            background-color: #fc0000;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background-color: #cc0000;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            margin-top: 70px;
            padding: 30px;
        }
        
        /* Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
            border: 1px solid rgba(78, 205, 196, 0.1);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .stat-info h3 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }
        
        .stat-info .number {
            font-size: 2rem;
            font-weight: 700;
            color: #292F36;
        }
        
        .stat-icon {
            font-size: 3rem;
            color: #4ECDC4;
        }
        
        /* Tables */
        .data-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .data-section:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .section-title {
            font-size: 1.3rem;
            color: #292F36;
            border-left: 4px solid #4ECDC4;
            padding-left: 15px;
        }
        
        .section-title i {
            color: #4ECDC4;
            margin-right: 8px;
        }
        
        .btn-link {
            background: none;
            border: none;
            color: #4ECDC4;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-link:hover {
            color: #3bb3aa;
            transform: translateX(3px);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #292F36;
        }
        
        .data-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .empty-message {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-consulta { background: #4ECDC4; color: white; }
        .badge-vacina { background: #95E77E; color: white; }
        .badge-cirurgia { background: #E74C3C; color: white; }
        .badge-banho { background: #3498DB; color: white; }
        .badge-tosa { background: #F39C12; color: white; }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .top-bar {
                left: 0;
                position: relative;
            }
            .main-content {
                margin-left: 0;
                margin-top: 0;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .main-content {
            animation: fadeIn 0.5s ease;
        }
    </style>
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
                            <td><?php echo htmlspecialchars($row['pet_nome']); ?></td>
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