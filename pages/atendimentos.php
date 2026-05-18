<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireFuncionario();

$conn = getConnection();
$atendimentos = $conn->query("SELECT a.*, p.nome as pet_nome, s.tipo as servico, f.nome as funcionario FROM atendimento a JOIN pet p ON a.idPet = p.idPet JOIN servico s ON a.idServico = s.idServico JOIN funcionario f ON a.idFuncionario = f.idFuncionario ORDER BY a.data DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Atendimentos</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .content-wrapper { margin-left: 250px; margin-top: 70px; padding: 30px; }
        table { width: 100%; background: white; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="top-bar"><h2>Atendimentos</h2><div><span><?php echo $_SESSION['user_email']; ?></span> <a href="logout.php" style="background:#fc0000; color:white; padding:5px 10px; text-decoration:none;">Sair</a></div></div>
    <div class="content-wrapper">
        <div style="background:white; padding:20px; border-radius:10px;">
            <table>
                <thead><tr><th>Data</th><th>Hora</th><th>Pet</th><th>Serviço</th><th>Funcionário</th></tr></thead>
                <tbody>
                    <?php while($a = $atendimentos->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($a['data'])); ?></td>
                        <td><?php echo $a['hora']; ?></td>
                        <td><?php echo $a['pet_nome']; ?></td>
                        <td><?php echo $a['servico']; ?></td>
                        <td><?php echo $a['funcionario']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>