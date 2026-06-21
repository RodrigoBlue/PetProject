<?php

require_once '../config/database.php';
require_once '../includes/auth.php';
requireFuncionario();

$conn = getConnection();
$message = '';
$error = '';

// Verificar se a tabela tem coluna quantidade, se não adicionar
$check = $conn->query("SHOW COLUMNS FROM produto LIKE 'quantidade'");
if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE produto ADD COLUMN quantidade INT DEFAULT 0");
}

// Adicionar/Editar/Excluir produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ADICIONAR PRODUTO
    if ($_POST['action'] === 'add' && hasPermission('pode_editar_produto')) {
        $nome = trim($_POST['nome']);
        $tipo = trim($_POST['tipo']);
        $valor = str_replace(',', '.', $_POST['valor']); // Permite vírgula como separador decimal
        $valor = (float)$valor;
        $quantidade = (int)$_POST['quantidade'];
        
        // Verificar se campos estão preenchidos
        if (empty($nome) || empty($tipo) || $valor <= 0) {
            $error = "Preencha todos os campos corretamente!";
        } else {
            $stmt = $conn->prepare("INSERT INTO produto (nome, tipo, valor, quantidade) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssdi", $nome, $tipo, $valor, $quantidade);
            
            if ($stmt->execute()) {
                $message = "Produto adicionado com sucesso!";
                // Redirecionar para evitar reenvio do formulário
                header("Location: produtos.php?msg=" . urlencode($message));
                exit();
            } else {
                $error = "Erro ao inserir: " . $stmt->error;
            }
            $stmt->close();
        }
    } 
    // EDITAR PRODUTO
    elseif ($_POST['action'] === 'edit' && hasPermission('pode_editar_produto')) {
        $id = (int)$_POST['id'];
        $nome = trim($_POST['nome']);
        $tipo = trim($_POST['tipo']);
        $valor = str_replace(',', '.', $_POST['valor']);
        $valor = (float)$valor;
        $quantidade = (int)$_POST['quantidade'];
        
        $stmt = $conn->prepare("UPDATE produto SET nome=?, tipo=?, valor=?, quantidade=? WHERE idProduto=?");
        $stmt->bind_param("ssdii", $nome, $tipo, $valor, $quantidade, $id);
        
        if ($stmt->execute()) {
            $message = "Produto atualizado com sucesso!";
            header("Location: produtos.php?msg=" . urlencode($message));
            exit();
        } else {
            $error = "Erro ao atualizar: " . $stmt->error;
        }
        $stmt->close();
    } 
    // EXCLUIR PRODUTO
    elseif ($_POST['action'] === 'delete' && hasPermission('pode_editar_produto')) {
        $id = (int)$_POST['id'];
        
        // Verificar se produto existe antes de deletar
        $check = $conn->query("SELECT idProduto FROM produto WHERE idProduto = $id");
        if ($check->num_rows > 0) {
            $stmt = $conn->prepare("DELETE FROM produto WHERE idProduto = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Produto removido com sucesso!";
                header("Location: produtos.php?msg=" . urlencode($message));
                exit();
            } else {
                $error = "Erro ao excluir: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Produto não encontrado!";
        }
    }
}

// Capturar mensagem da URL (após redirecionamento)
if (isset($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
}

// Buscar produtos
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT * FROM produto";
if ($search) {
    // Usar prepared statement para evitar SQL injection
    $search = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM produto WHERE nome LIKE ? OR tipo LIKE ? ORDER BY nome");
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $produtos = $stmt->get_result();
} else {
    $produtos = $conn->query("SELECT * FROM produto ORDER BY nome");
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
    <link rel="stylesheet" href="../styles/produtos.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="top-bar">
        <h2><i class="fas fa-box"></i> Produtos</h2>
        <div>
            <span><i class="fas fa-user"></i> <?php echo $_SESSION['user_email'] ?? 'Usuário'; ?></span>
            <a href="logout.php" class="btn btn-danger" style="margin-left: 15px;">Sair</a>
        </div>
    </div>
    
    <div class="content-wrapper">
        <?php if($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> 
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Buscar produto por nome ou tipo..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
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
                                <button class="btn btn-warning" style="padding: 5px 10px; font-size: 0.8rem;" 
                                        onclick='editProduto(<?php echo json_encode($p); ?>)'>
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;" 
                                        onclick="deleteProduto(<?php echo $p['idProduto']; ?>, '<?php echo addslashes($p['nome']); ?>')">
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

    <script>
        function openModal(action, product = null) {
            const modal = document.getElementById('modal');
            const formAction = document.getElementById('formAction');
            const modalTitle = document.getElementById('modalTitle');
            
            if (action === 'add') {
                formAction.value = 'add';
                modalTitle.innerHTML = '<i class="fas fa-plus-circle"></i> Novo Produto';
                document.getElementById('productForm').reset();
                document.getElementById('productId').value = '';
                document.getElementById('quantidade').value = '0';
            } else if (action === 'edit' && product) {
                formAction.value = 'edit';
                modalTitle.innerHTML = '<i class="fas fa-edit"></i> Editar Produto';
                document.getElementById('productId').value = product.idProduto;
                document.getElementById('nome').value = product.nome;
                document.getElementById('tipo').value = product.tipo;
                document.getElementById('valor').value = product.valor;
                document.getElementById('quantidade').value = product.quantidade || 0;
            }
            modal.style.display = 'flex';
        }
        
        function editProduto(product) {
            openModal('edit', product);
        }
        
        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }
        
        function deleteProduto(id, nome) {
            if (confirm(`Tem certeza que deseja excluir o produto "${nome}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function searchProducts() {
            const search = document.getElementById('searchInput').value;
            window.location.href = `produtos.php?search=${encodeURIComponent(search)}`;
        }
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('modal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>