<?php
// produtos.php - melhorado com ações
require_once '../config/database.php';
require_once '../includes/auth.php';
requireFuncionario();

$conn = getConnection();
$message = '';
$error = '';

// Adicionar produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add' && hasPermission('pode_editar_produto')) {
        $nome = trim($_POST['nome']);
        $tipo = trim($_POST['tipo']);
        $valor = (float)$_POST['valor'];
        $quantidade = (int)$_POST['quantidade'];
        
        $stmt = $conn->prepare("INSERT INTO produto (nome, tipo, valor, quantidade) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssdi", $nome, $tipo, $valor, $quantidade);
        
        if ($stmt->execute()) {
            $message = "Produto adicionado com sucesso!";
        } else {
            $error = "Erro: " . $conn->error;
        }
        $stmt->close();
    } elseif ($_POST['action'] === 'edit' && hasPermission('pode_editar_produto')) {
        $id = (int)$_POST['id'];
        $nome = trim($_POST['nome']);
        $tipo = trim($_POST['tipo']);
        $valor = (float)$_POST['valor'];
        $quantidade = (int)$_POST['quantidade'];
        
        $stmt = $conn->prepare("UPDATE produto SET nome=?, tipo=?, valor=?, quantidade=? WHERE idProduto=?");
        $stmt->bind_param("ssdii", $nome, $tipo, $valor, $quantidade, $id);
        
        if ($stmt->execute()) {
            $message = "Produto atualizado com sucesso!";
        } else {
            $error = "Erro: " . $conn->error;
        }
        $stmt->close();
    } elseif ($_POST['action'] === 'delete' && hasPermission('pode_editar_produto')) {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM produto WHERE idProduto=$id");
        $message = "Produto removido!";
    }
}

// Buscar produtos
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT * FROM produto";
if ($search) {
    $sql .= " WHERE nome LIKE '%$search%' OR tipo LIKE '%$search%'";
}
$sql .= " ORDER BY nome";
$produtos = $conn->query($sql);

// Verificar se a tabela tem coluna quantidade, se não adicionar
$check = $conn->query("SHOW COLUMNS FROM produto LIKE 'quantidade'");
if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE produto ADD COLUMN quantidade INT DEFAULT 0");
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Produtos - PetProject</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary: #ffffff;
            --secondary: #4ECDC4;
            --accent: #fc0000;
            --dark: #292F36;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 15px rgba(0,0,0,0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%); }

        .content-wrapper { margin-left: 250px; padding: 30px; animation: fadeIn 0.5s ease; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .top-bar {
            background: white;
            padding: 15px 30px;
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            z-index: 99;
        }

        .top-bar h2 { color: var(--dark); font-size: 1.5rem; }
        .top-bar h2 i { color: var(--secondary); margin-right: 10px; }

        .card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
        }

        .card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.15); }

        .btn {
            padding: 8px 18px;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-primary { background: linear-gradient(135deg, var(--secondary), #44b3aa); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(78, 205, 196, 0.3); }
        .btn-warning { background: linear-gradient(135deg, #ffc107, #e0a800); color: white; }
        .btn-danger { background: linear-gradient(135deg, #fc0000, #d00000); color: white; }
        .btn-danger:hover { transform: translateY(-2px); }

        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-bar input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .search-bar input:focus {
            outline: none;
            border-color: var(--secondary);
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
        .data-table th { background: #f8f9fa; font-weight: 600; color: var(--dark); }
        .data-table tr:hover { background: #f8f9fa; }

        .empty-message { text-align: center; padding: 40px; color: #666; }

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
            min-width: 400px;
            max-width: 500px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark); }
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
            border-color: var(--secondary);
        }

        .alert {
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }

        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .top-bar { left: 0; position: relative; }
            .content-wrapper { margin-left: 0; margin-top: 0; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="top-bar">
        <h2><i class="fas fa-box"></i> Produtos</h2>
        <div>
            <span><i class="fas fa-user"></i> <?php echo $_SESSION['user_email']; ?></span>
            <a href="logout.php" class="btn btn-danger" style="margin-left: 15px;">Sair</a>
        </div>
    </div>
    
    <div class="content-wrapper">
        <?php if($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Buscar produto por nome ou tipo..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-primary" onclick="searchProducts()"><i class="fas fa-search"></i> Buscar</button>
                <button class="btn btn-primary" onclick="openModal('add')"><i class="fas fa-plus-circle"></i> Novo Produto</button>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Valor</th>
                        <th>Estoque</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($produtos && $produtos->num_rows > 0): ?>
                        <?php while($p = $produtos->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $p['idProduto']; ?></td>
                            <td><strong><?php echo htmlspecialchars($p['nome']); ?></strong></td>
                            <td><?php echo htmlspecialchars($p['tipo']); ?></td>
                            <td>R$ <?php echo number_format($p['valor'], 2, ',', '.'); ?></td>
                            <td><?php echo isset($p['quantidade']) ? $p['quantidade'] : 0; ?> unidades</td>
                            <td>
                                <button class="btn btn-warning" style="padding: 5px 10px; font-size: 0.8rem;" onclick='editProduto(<?php echo json_encode($p); ?>)'>
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;" onclick="deleteProduto(<?php echo $p['idProduto']; ?>, '<?php echo addslashes($p['nome']); ?>')">
                                    <i class="fas fa-trash"></i> Excluir
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="empty-message">Nenhum produto encontrado</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle"><i class="fas fa-box"></i> Novo Produto</h3>
            <form method="POST" id="productForm">
                <input type="hidden" name="action" id="formAction">
                <input type="hidden" name="id" id="productId">
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Nome</label>
                    <input type="text" name="nome" id="nome" required placeholder="Nome do produto">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-category"></i> Tipo</label>
                    <select name="tipo" id="tipo">
                        <option value="Medicamento">💊 Medicamento</option>
                        <option value="Vacina">💉 Vacina</option>
                        <option value="Ração">🍖 Ração</option>
                        <option value="Acessório">🎾 Acessório</option>
                        <option value="Higiene">🧼 Higiene</option>
                        <option value="Outro">📦 Outro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-dollar-sign"></i> Valor (R$)</label>
                    <input type="number" step="0.01" name="valor" id="valor" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-boxes"></i> Quantidade em Estoque</label>
                    <input type="number" name="quantidade" id="quantidade" value="0" placeholder="Quantidade">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary">Salvar</button>
                    <button type="button" class="btn" style="background: #6c757d; color: white;" onclick="closeModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
