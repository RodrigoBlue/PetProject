<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireFuncionario();

$idPet = $_GET['id'];
$conn = getConnection();
$pet = $conn->query("SELECT p.*, t.nome as tutor_nome FROM pet p JOIN tutor t ON p.idTutor = t.idTutor WHERE p.idPet = $idPet")->fetch_assoc();
if(!$pet) die("Pet não encontrado");

// Procedures (atendimentos)
$atendimentos = $conn->query("SELECT a.data, s.tipo as servico, s.valor, f.nome as funcionario FROM atendimento a JOIN servico s ON a.idServico = s.idServico JOIN funcionario f ON a.idFuncionario = f.idFuncionario WHERE a.idPet = $idPet ORDER BY a.data DESC");

// Medications / Products indicated
$produtos = $conn->query("SELECT pr.nome, pp.quantidade, pp.instrucoes FROM pet_produto pp JOIN produto pr ON pp.idProduto = pr.idProduto WHERE pp.idPet = $idPet");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Orçamento - <?php echo $pet['nome']; ?></title>
    <style>
        body { font-family: 'Poppins', sans-serif; padding: 30px; }
        .header { text-align: center; margin-bottom: 30px; }
        .section { margin-bottom: 30px; border: 1px solid #ccc; padding: 20px; border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #ddd; padding: 10px; text-align: left; }
        .total { font-weight: bold; margin-top: 15px; }
        .btn-print { background: #4ECDC4; padding: 10px 20px; border: none; cursor: pointer; margin-bottom: 20px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print"><button class="btn-print" onclick="window.print()">Imprimir Orçamento</button> <a href="pets.php">Voltar</a></div>
    <div class="header">
        <img src="../img/PetProject.png" width="80"><h2>Orçamento Veterinário</h2>
        <p><strong>Pet:</strong> <?php echo $pet['nome']; ?> | <strong>Tutor:</strong> <?php echo $pet['tutor_nome']; ?> | <strong>Espécie:</strong> <?php echo $pet['especie']; ?> | <strong>Raça:</strong> <?php echo $pet['raca']; ?></p>
    </div>
    <div class="section">
        <h3>Procedimentos Realizados</h3>
        <table>
            <thead><tr><th>Data</th><th>Serviço</th><th>Valor</th><th>Funcionário</th></tr></thead>
            <tbody>
            <?php $total = 0; while($a = $atendimentos->fetch_assoc()): ?>
                <tr><td><?php echo date('d/m/Y', strtotime($a['data'])); ?></td><td><?php echo $a['servico']; ?></td><td>R$ <?php echo number_format($a['valor'],2,',','.'); ?></td><td><?php echo $a['funcionario']; ?></td></tr>
                <?php $total += $a['valor']; endwhile; ?>
            </tbody>
        </table>
        <div class="total">Total Procedimentos: R$ <?php echo number_format($total,2,',','.'); ?></div>
    </div>
    <div class="section">
        <h3>Produtos / Medicamentos Indicados</h3>
        <table>
            <thead><tr><th>Produto</th><th>Quantidade</th><th>Instruções</th></tr></thead>
            <tbody>
            <?php while($p = $produtos->fetch_assoc()): ?>
                <tr><td><?php echo $p['nome']; ?></td><td><?php echo $p['quantidade']; ?></td><td><?php echo $p['instrucoes']; ?></td></tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <div class="footer">Documento gerado em <?php echo date('d/m/Y H:i'); ?></div>
</body>
</html>