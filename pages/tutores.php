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
            $conn->query("DELETE FROM tutor WHERE idTutor=$id");
            $message = "Tutor removido (pets associados também serão removidos devido a ON DELETE CASCADE? Certifique-se).";
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
        .content-wrapper { margin-left: 250px; padding: 30px; }
        .card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .btn { padding: 8px 16px; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px; }
        .btn-primary { background: var(--secondary); color: white; }
        .btn-danger { background: var(--accent); color: white; }
        .btn-warning { background: #ffc107; color: black; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
        .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 30px; border-radius: 10px; min-width: 400px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px; }
        .sidebar { width: 250px; background: var(--dark); color: white; position: fixed; height: 100%; }
        .sidebar a { display: block; padding: 15px; color: white; text-decoration: none; }
        .sidebar a:hover { background: var(--secondary); }
        .top-bar { background: white; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex; justify-content: space-between; margin-left: 250px; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="top-bar"><h2><i class="fas fa-users"></i> Tutores</h2><div><span><?php echo $_SESSION['user_email']; ?></span> <a href="logout.php" class="btn-danger btn">Sair</a></div></div>
    <div class="content-wrapper">
        <div class="card">
            <button class="btn btn-primary" onclick="openModal('add')"><i class="fas fa-plus"></i> Novo Tutor</button>
        </div>
        <?php if($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        <div class="card">
            <table>
                <thead><tr><th>ID</th><th>Nome</th><th>CPF</th><th>Telefone</th><th>Endereço</th><th>Pets</th><th>Ações</th></tr></thead>
                <tbody>
                    <?php while($t = $tutores->fetch_assoc()): 
                        $petCount = $conn->query("SELECT COUNT(*) as c FROM pet WHERE idTutor={$t['idTutor']}")->fetch_assoc()['c'];
                    ?>
                    <tr>
                        <td><?php echo $t['idTutor']; ?></td>
                        <td><?php echo htmlspecialchars($t['nome']); ?></td>
                        <td><?php echo $t['cpf']; ?></td>
                        <td><?php echo $t['telefone']; ?></td>
                        <td><?php echo $t['endereco']; ?></td>
                        <td><a href="pets.php?tutor_id=<?php echo $t['idTutor']; ?>"><?php echo $petCount; ?> pets</a></td>
                        
                        <td>
                            <button class="btn btn-warning" onclick="editTutor(<?php echo htmlspecialchars(json_encode($t)); ?>)">Editar</button>
                            <button class="btn btn-danger" onclick="deleteTutor(<?php echo $t['idTutor']; ?>)">Excluir</button>
                            <a href="pet_detalhe.php?tutor_id=<?php echo $t['idTutor']; ?>&action=new" class="btn btn-primary">Adicionar Pet</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Novo Tutor</h3>
            <form method="POST" id="tutorForm">
                <input type="hidden" name="action" id="formAction">
                <input type="hidden" name="id" id="tutorId">
                <div class="form-group"><label>Nome</label><input type="text" name="nome" id="nome" required></div>
                <div class="form-group"><label>CPF</label><input type="text" name="cpf" id="cpf"></div>
                <div class="form-group"><label>Telefone</label><input type="text" name="telefone" id="telefone"></div>
                <div class="form-group"><label>Endereço</label><input type="text" name="endereco" id="endereco"></div>
                <button type="submit" class="btn btn-primary">Salvar</button>
                <button type="button" class="btn" onclick="closeModal()">Cancelar</button>
            </form>
        </div>
    </div>
    <script>
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
            if(confirm('Excluir tutor e todos os pets?')) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input name="action" value="delete"><input name="id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
        function closeModal() { document.getElementById('modal').style.display = 'none'; }
    </script>
</body>
</html>