<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireFuncionario();

$conn = getConnection();

// Buscar atendimentos das duas fontes
$atendimentos = $conn->query("
    (SELECT 
        a.data,
        a.hora,
        p.nome as pet_nome,
        s.tipo as servico,
        f.nome as funcionario
    FROM atendimento a 
    JOIN pet p ON a.idPet = p.idPet 
    JOIN servico s ON a.idServico = s.idServico 
    JOIN funcionario f ON a.idFuncionario = f.idFuncionario)
    
    UNION ALL
    
    (SELECT 
        rc.data_registro as data,
        rc.hora_registro as hora,
        p.nome as pet_nome,
        rc.tipo_registro as servico,
        'Registro Clínico' as funcionario
    FROM pet_registros_clinicos rc
    JOIN pet p ON rc.idPet = p.idPet)
    
    ORDER BY data DESC, hora DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Atendimentos</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .content-wrapper { margin-left: 250px; margin-top: 70px; padding: 30px; }
        table { width: 100%; background: white; border-collapse: collapse; border-radius: 10px; overflow: hidden; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
        .badge-clinico { background: #4ECDC4; color: white; padding: 2px 8px; border-radius: 20px; font-size: 0.7rem; }
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
        .btn-danger { background: #fc0000; color: white; padding: 5px 10px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="top-bar"><h2><i class="fas fa-stethoscope"></i> Atendimentos</h2><div><span><?php echo $_SESSION['user_email']; ?></span> <a href="logout.php" class="btn-danger">Sair</a></div></div>
    <div class="content-wrapper">
        <div style="background:white; padding:20px; border-radius:10px;">
            <h3 style="margin-bottom: 15px;">Histórico de Atendimentos</h3>
            <table>
                <thead>
                    <tr><th>Data</th><th>Hora</th><th>Pet</th><th>Serviço</th><th>Funcionário</th></tr>
                </thead>
                <tbody>
                    <?php if($atendimentos && $atendimentos->num_rows > 0): ?>
                        <?php while($a = $atendimentos->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($a['data'])); ?></td>
                            <td><?php echo substr($a['hora'], 0, 5); ?></td>
                            <td><?php echo htmlspecialchars($a['pet_nome']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($a['servico']); ?>
                                <?php if($a['funcionario'] == 'Registro Clínico'): ?>
                                    <span class="badge-clinico"><i class="fas fa-notes-medical"></i> Clínico</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($a['funcionario']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center;">Nenhum atendimento encontrado</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>