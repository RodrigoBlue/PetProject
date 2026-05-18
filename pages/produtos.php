<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireFuncionario();

$conn = getConnection();
$produtos = $conn->query("SELECT * FROM produto");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Produtos</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .content-wrapper { margin-left: 250px; margin-top: 70px; padding: 30px; }
        table { width: 100%; background: white; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="top-bar"><h2>Produtos</h2><div><span><?php echo $_SESSION['user_email']; ?></span> <a href="logout.php" style="background:#fc0000; color:white; padding:5px 10px; text-decoration:none;">Sair</a></div></div>
    <div class="content-wrapper">
        <div style="background:white; padding:20px; border-radius:10px;">
            <table>
                <thead><tr><th>ID</th><th>Nome</th><th>Tipo</th><th>Valor</th></tr></thead>
                <tbody>
                    <?php while($p = $produtos->fetch_assoc()): ?>
                    <tr><td><?php echo $p['idProduto']; ?></td><td><?php echo $p['nome']; ?></td><td><?php echo $p['tipo']; ?></td><td>R$ <?php echo number_format($p['valor'],2,',','.'); ?></td></tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>