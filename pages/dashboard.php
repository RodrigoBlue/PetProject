<?php
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
$sql_agendamentos = "SELECT a.data, a.hora, p.nome as pet_nome, t.nome as tutor_nome 
                     FROM agendamento a 
                     JOIN pet p ON a.idPet = p.idPet 
                     JOIN tutor t ON p.idTutor = t.idTutor 
                     WHERE a.data >= CURDATE() 
                     ORDER BY a.data, a.hora 
                     LIMIT 5";
$agendamentos = $conn->query($sql_agendamentos);

// Últimos atendimentos
$sql_atendimentos = "SELECT a.data, a.hora, p.nome as pet_nome, s.tipo as servico, f.nome as funcionario 
                     FROM atendimento a 
                     JOIN pet p ON a.idPet = p.idPet 
                     JOIN servico s ON a.idServico = s.idServico 
                     JOIN funcionario f ON a.idFuncionario = f.idFuncionario 
                     ORDER BY a.data DESC, a.hora DESC 
                     LIMIT 5";
$atendimentos = $conn->query($sql_atendimentos);

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
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: var(--dark);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
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
            background-color: var(--secondary);
            padding-left: 30px;
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
        }
        
        .sidebar-menu .active {
            background-color: var(--secondary);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            background-color: #f5f5f5;
        }
        
        /* Top Bar */
        .top-bar {
            background-color: white;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info span {
            color: var(--dark);
            font-weight: 500;
        }
        
        .btn-logout {
            background-color: var(--accent);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background-color: #cc0000;
        }
        
        /* Dashboard Content */
        .dashboard-content {
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
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .stat-info h3 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }
        
        .stat-info .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .stat-icon {
            font-size: 3rem;
            color: var(--secondary);
        }
        
        /* Tables */
        .data-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 1.3rem;
            margin-bottom: 20px;
            color: var(--dark);
            border-left: 4px solid var(--secondary);
            padding-left: 15px;
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
            color: var(--dark);
        }
        
        .data-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .empty-message {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
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
                <a href="#">
                    <i class="fas fa-paw"></i> Pets
                </a>
                <a href="#">
                    <i class="fas fa-users"></i> Tutores
                </a>
                <a href="#">
                    <i class="fas fa-calendar-alt"></i> Agendamentos
                </a>
                <a href="#">
                    <i class="fas fa-stethoscope"></i> Atendimentos
                </a>
                <a href="#">
                    <i class="fas fa-box"></i> Produtos
                </a>
                <a href="#">
                    <i class="fas fa-chart-line"></i> Vendas
                </a>
                <a href="#">
                    <i class="fas fa-user-md"></i> Funcionários
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
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
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
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
                </div>
                
                <!-- Próximos Agendamentos -->
                <div class="data-section">
                    <h3 class="section-title">
                        <i class="fas fa-calendar-alt"></i> Próximos Agendamentos
                    </h3>
                    <?php if ($agendamentos && $agendamentos->num_rows > 0): ?>
                        <table class="data-table">
                            <thead>
                                
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
                    <h3 class="section-title">
                        <i class="fas fa-history"></i> Últimos Atendimentos
                    </h3>
                    <?php if ($atendimentos && $atendimentos->num_rows > 0): ?>
                        <table class="data-table">
                            <thead>
                                
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
        </div>
    </div>
</body>
</html>