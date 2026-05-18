<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdmin(); // only admin

$conn = getConnection();

// Update permissions
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_perms'])) {
    $uid = $_POST['usuario_id'];
    $pets = isset($_POST['pode_editar_pet']) ? 1 : 0;
    $atend = isset($_POST['pode_editar_atendimento']) ? 1 : 0;
    $agend = isset($_POST['pode_editar_agendamento']) ? 1 : 0;
    $func = isset($_POST['pode_editar_funcionario']) ? 1 : 0;
    $stmt = $conn->prepare("REPLACE INTO permissoes (usuario_id, pode_editar_pet, pode_editar_atendimento, pode_editar_agendamento, pode_editar_funcionario) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiii", $uid, $pets, $atend, $agend, $func);
    $stmt->execute();
    $success = "Permissões atualizadas!";
}

$funcionarios = $conn->query("SELECT u.id, u.email, u.nome, u.role, f.cargo, f.telefone FROM usuario u LEFT JOIN funcionario f ON u.email = f.email");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Funcionários - PetProject</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .content-wrapper { margin-left: 250px; padding: 30px; }
        .card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
        .badge-admin { background: #dc3545; color: white; padding: 2px 8px; border-radius: 20px; }
        .badge-func { background: #28a745; color: white; padding: 2px 8px; border-radius: 20px; }
        .perm-form { display: inline-block; }
        .btn-sm { padding: 4px 8px; font-size: 12px; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="top-bar" style="margin-left:250px;"><h2>Funcionários</h2><div><span><?php echo $_SESSION['user_email']; ?></span> <a href="logout.php" class="btn-danger btn">Sair</a></div></div>
    <div class="content-wrapper">
        <div class="card">
            <h3>Total de funcionários: <?php echo $funcionarios->num_rows; ?></h3>
            <table>
                <thead><tr><th>ID</th><th>Nome</th><th>Email</th><th>Cargo</th><th>Role</th><th>Permissões</th><th>Ações</th></tr></thead>
                <tbody>
                <?php 
                $funcionarios->data_seek(0);
                while($f = $funcionarios->fetch_assoc()): 
                    $perms = $conn->query("SELECT * FROM permissoes WHERE usuario_id = {$f['id']}")->fetch_assoc();
                    if(!$perms) $perms = ['pode_editar_pet'=>1,'pode_editar_atendimento'=>1,'pode_editar_agendamento'=>1,'pode_editar_funcionario'=>0];
                ?>
                <tr>
                    <td><?php echo $f['id']; ?></td>
                    <td><?php echo $f['nome']; ?></td>
                    <td><?php echo $f['email']; ?></td>
                    <td><?php echo $f['cargo']; ?></td>
                    <td><span class="badge-<?php echo $f['role']=='admin'?'admin':'func'; ?>"><?php echo $f['role']; ?></span></td>
                    <td>
                        <form method="POST" class="perm-form">
                            <input type="hidden" name="usuario_id" value="<?php echo $f['id']; ?>">
                            <label><input type="checkbox" name="pode_editar_pet" <?php echo $perms['pode_editar_pet']?'checked':''; ?>> Editar Pet</label>
                            <label><input type="checkbox" name="pode_editar_atendimento" <?php echo $perms['pode_editar_atendimento']?'checked':''; ?>> Editar Atendimento</label>
                            <label><input type="checkbox" name="pode_editar_agendamento" <?php echo $perms['pode_editar_agendamento']?'checked':''; ?>> Editar Agendamento</label>
                            <label><input type="checkbox" name="pode_editar_funcionario" <?php echo $perms['pode_editar_funcionario']?'checked':''; ?>> Editar Funcionário</label>
                            <button type="submit" name="update_perms" class="btn btn-primary btn-sm">Salvar Permissões</button>
                        </form>
                    </td>
                    <td><a href="edit_funcionario.php?id=<?php echo $f['id']; ?>" class="btn btn-warning btn-sm">Editar Dados</a></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>