<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireFuncionario();

$conn = getConnection();
$message = '';
$error = '';

// Handle add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add' && hasPermission('pode_editar_pet')) {
            $nome = trim($_POST['nome']);
            $cpf = trim($_POST['cpf']);
            $telefone = trim($_POST['telefone']);
            $endereco = trim($_POST['endereco']);
            $stmt = $conn->prepare("INSERT INTO tutor (nome, cpf, telefone, endereco) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nome, $cpf, $telefone, $endereco);
            if ($stmt->execute()) $message = "Tutor cadastrado com sucesso!";
            else $error = "Erro: " . $conn->error;
            $stmt->close();
        } elseif ($action === 'edit' && hasPermission('pode_editar_pet')) {
            $id = $_POST['id'];
            $nome = trim($_POST['nome']);
            $cpf = trim($_POST['cpf']);
            $telefone = trim($_POST['telefone']);
            $endereco = trim($_POST['endereco']);
            $stmt = $conn->prepare("UPDATE tutor SET nome=?, cpf=?, telefone=?, endereco=? WHERE idTutor=?");
            $stmt->bind_param("ssssi", $nome, $cpf, $telefone, $endereco, $id);
            $stmt->execute();
            $message = "Tutor atualizado!";
            $stmt->close();
        } elseif ($action === 'delete' && hasPermission('pode_editar_pet')) {
            $id = $_POST['id'];
            // Verificar se o tutor tem pets antes de excluir
            $check_pets = $conn->query("SELECT COUNT(*) as total FROM pet WHERE idTutor=$id");
            $total_pets = $check_pets->fetch_assoc()['total'];
            
            if($total_pets > 0) {
                $error = "Não é possível excluir este tutor pois ele possui $total_pets pet(s) associado(s).";
            } else {
                $conn->query("DELETE FROM tutor WHERE idTutor=$id");
                $message = "Tutor removido com sucesso!";
            }
        }
    }
}

// Fetch all tutors
$tutores = $conn->query("SELECT * FROM tutor ORDER BY nome");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Tutores - PetProject</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary: #ffffff;
            --secondary: #4ECDC4;
            --accent: #fc0000;
            --orange: #ff9800;
            --orange-dark: #f57c00;
            --dark: #292F36;
            --light: #F7FFF7;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 15px rgba(0,0,0,0.1);
            --shadow-lg: 0 8px 25px rgba(0,0,0,0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
        }

        .content-wrapper {
            margin-left: 250px;
            padding: 30px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Top Bar */
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

        .top-bar h2 {
            color: var(--dark);
            font-size: 1.5rem;
            font-weight: 600;
        }

        .top-bar h2 i {
            color: var(--secondary);
            margin-right: 10px;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            border: 1px solid rgba(78, 205, 196, 0.1);
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        /* Botões */
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

        .btn-primary {
            background: linear-gradient(135deg, var(--secondary), #44b3aa);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 205, 196, 0.3);
        }

        /* Botão Laranja para Pets */
        .btn-orange {
            background: linear-gradient(135deg, var(--orange), var(--orange-dark));
            color: white;
        }

        .btn-orange:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 152, 0, 0.3);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: white;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #fc0000, #d00000);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(252, 0, 0, 0.3);
        }

        /* Tabela Moderna */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead tr {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
        }

        .data-table th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            color: #555;
        }

        .data-table tbody tr {
            transition: all 0.3s ease;
        }

        .data-table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
        }

        /* Link de Pets na tabela */
        .pets-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--orange), var(--orange-dark));
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .pets-link i {
            font-size: 0.9rem;
        }

        .pets-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 152, 0, 0.3);
        }

        .pets-count {
            background: var(--orange);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        /* Botões de ação na tabela */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-buttons .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

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
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 20px;
            min-width: 450px;
            max-width: 500px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-content h3 {
            color: var(--dark);
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.1);
        }

        /* Alertas */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Responsividade */
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
            .content-wrapper {
                margin-left: 0;
                margin-top: 0;
            }
            .data-table {
                display: block;
                overflow-x: auto;
            }
            .modal-content {
                min-width: 90%;
                margin: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="top-bar">
        <h2><i class="fas fa-users"></i> Tutores</h2>
        <div>
            <span><i class="fas fa-user"></i> <?php echo $_SESSION['user_email']; ?></span>
            <a href="logout.php" class="btn btn-danger" style="margin-left: 15px;">Sair</a>
        </div>
    </div>
    
    <div class="content-wrapper">
        <!-- Mensagens de Feedback -->
        <?php if($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Botão Novo Tutor -->
        <div class="card">
            <button class="btn btn-primary" onclick="openModal('add')">
                <i class="fas fa-plus-circle"></i> Novo Tutor
            </button>
        </div>

        <!-- Tabela de Tutores -->
        <div class="card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Telefone</th>
                        <th>Endereço</th>
                        <th>Pets</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($t = $tutores->fetch_assoc()): 
                        $petCount = $conn->query("SELECT COUNT(*) as c, GROUP_CONCAT(idPet) as ids FROM pet WHERE idTutor={$t['idTutor']}")->fetch_assoc();
                        $total_pets = $petCount['c'];
                        $pets_ids = $petCount['ids'];
                    ?>
                    <tr>
                        <td><?php echo $t['idTutor']; ?></td>
                        <td><strong><?php echo htmlspecialchars($t['nome']); ?></strong></td>
                        <td><?php echo $t['cpf']; ?></td>
                        <td><?php echo $t['telefone']; ?></td>
                        <td><?php echo $t['endereco']; ?></td>
                        <td>
                            <?php if($total_pets > 0): ?>
                                <?php if($total_pets == 1): ?>
                                    <!-- Se tiver apenas 1 pet, redireciona direto para pet_detalhe.php -->
                                    <a href="pet_detalhe.php?id=<?php echo $pets_ids; ?>" class="pets-link">
                                        <i class="fas fa-paw"></i> 
                                        <?php echo $total_pets; ?> pet(s)
                                    </a>
                                <?php else: ?>
                                    <!-- Se tiver vários pets, mostra um dropdown com todos os pets -->
                                    <div style="position: relative; display: inline-block;">
                                        <div class="pets-link" onclick="togglePetList(this)" style="cursor: pointer;">
                                            <i class="fas fa-paw"></i> 
                                            <?php echo $total_pets; ?> pet(s)
                                            <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i>
                                        </div>
                                        <div class="pet-dropdown" style="display: none; position: absolute; top: 100%; left: 0; background: white; border-radius: 12px; box-shadow: var(--shadow-lg); min-width: 200px; z-index: 100; margin-top: 5px;">
                                            <?php 
                                            $pets_list = $conn->query("SELECT idPet, nome FROM pet WHERE idTutor={$t['idTutor']} ORDER BY nome");
                                            while($pet_item = $pets_list->fetch_assoc()): 
                                            ?>
                                                <a href="pet_detalhe.php?id=<?php echo $pet_item['idPet']; ?>" style="display: block; padding: 10px 15px; text-decoration: none; color: var(--dark); transition: all 0.3s ease;">
                                                    <i class="fas fa-dog"></i> <?php echo htmlspecialchars($pet_item['nome']); ?>
                                                </a>
                                            <?php endwhile; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #999;">Nenhum pet</span>
                            <?php endif; ?>
                        </td>
                        <td class="action-buttons">
                            <button class="btn btn-warning btn-sm" onclick="editTutor(<?php echo htmlspecialchars(json_encode($t)); ?>)">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteTutor(<?php echo $t['idTutor']; ?>)">
                                <i class="fas fa-trash"></i> Excluir
                            </button>
                            <a href="pet_detalhe.php?tutor_id=<?php echo $t['idTutor']; ?>&action=new" class="btn btn-orange btn-sm">
                                <i class="fas fa-plus-circle"></i> Adicionar Pet
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para Add/Edit Tutor -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Novo Tutor</h3>
            <form method="POST" id="tutorForm">
                <input type="hidden" name="action" id="formAction">
                <input type="hidden" name="id" id="tutorId">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Nome</label>
                    <input type="text" name="nome" id="nome" required placeholder="Digite o nome completo">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-id-card"></i> CPF</label>
                    <input type="text" name="cpf" id="cpf" placeholder="000.000.000-00">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Telefone</label>
                    <input type="text" name="telefone" id="telefone" placeholder="(00) 00000-0000">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Endereço</label>
                    <input type="text" name="endereco" id="endereco" placeholder="Rua, número, bairro, cidade">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary">Salvar</button>
                    <button type="button" class="btn" style="background: #6c757d; color: white;" onclick="closeModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Funções do Modal
        function openModal(action, data=null) {
            document.getElementById('modal').style.display = 'flex';
            if(action === 'add') {
                document.getElementById('modalTitle').innerText = 'Novo Tutor';
                document.getElementById('formAction').value = 'add';
                document.getElementById('tutorId').value = '';
                document.getElementById('nome').value = '';
                document.getElementById('cpf').value = '';
                document.getElementById('telefone').value = '';
                document.getElementById('endereco').value = '';
            }
        }
        
        function editTutor(tutor) {
            document.getElementById('modalTitle').innerText = 'Editar Tutor';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('tutorId').value = tutor.idTutor;
            document.getElementById('nome').value = tutor.nome;
            document.getElementById('cpf').value = tutor.cpf;
            document.getElementById('telefone').value = tutor.telefone;
            document.getElementById('endereco').value = tutor.endereco;
            document.getElementById('modal').style.display = 'flex';
        }
        
        function deleteTutor(id) {
            if(confirm('⚠️ Tem certeza que deseja excluir este tutor?\n\nATENÇÃO: Se o tutor tiver pets, não será possível excluí-lo.')) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input name="action" value="delete"><input name="id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function closeModal() { 
            document.getElementById('modal').style.display = 'none'; 
        }
        
        // Fechar modal clicando fora
        window.onclick = function(event) {
            let modal = document.getElementById('modal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Função para mostrar/esconder lista de pets
        function togglePetList(element) {
            let dropdown = element.nextElementSibling;
            let isVisible = dropdown.style.display === 'block';
            
            // Fechar todos os outros dropdowns
            document.querySelectorAll('.pet-dropdown').forEach(drop => {
                drop.style.display = 'none';
            });
            
            // Abrir/fechar o atual
            dropdown.style.display = isVisible ? 'none' : 'block';
        }
        
        // Fechar dropdowns ao clicar fora
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.pets-link')) {
                document.querySelectorAll('.pet-dropdown').forEach(drop => {
                    drop.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>