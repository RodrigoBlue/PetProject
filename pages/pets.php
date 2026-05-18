<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireFuncionario();

$conn = getConnection();
$search = $_GET['search'] ?? '';
$raca = $_GET['raca'] ?? '';
$sexo = $_GET['sexo'] ?? '';
$especie = $_GET['especie'] ?? '';
$peso_min = $_GET['peso_min'] ?? '';
$peso_max = $_GET['peso_max'] ?? '';
$cor = $_GET['cor'] ?? '';

$sql = "SELECT p.*, t.nome as tutor_nome FROM pet p JOIN tutor t ON p.idTutor = t.idTutor WHERE 1=1";
$params = [];
$types = "";
if($search) { $sql .= " AND p.nome LIKE ?"; $params[] = "%$search%"; $types .= "s"; }
if($raca) { $sql .= " AND p.raca = ?"; $params[] = $raca; $types .= "s"; }
if($sexo) { $sql .= " AND p.sexo = ?"; $params[] = $sexo; $types .= "s"; }
if($especie) { $sql .= " AND p.especie = ?"; $params[] = $especie; $types .= "s"; }
if($cor) { $sql .= " AND p.cor = ?"; $params[] = $cor; $types .= "s"; }
if($peso_min) { $sql .= " AND p.peso >= ?"; $params[] = $peso_min; $types .= "d"; }
if($peso_max) { $sql .= " AND p.peso <= ?"; $params[] = $peso_max; $types .= "d"; }
$sql .= " ORDER BY p.nome";

$stmt = $conn->prepare($sql);
if(count($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$pets = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pets - PetProject</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .content-wrapper { margin-left: 250px; padding: 30px; }
        .filter-bar { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .filter-group { flex: 1; min-width: 150px; }
        .filter-group label { display: block; font-weight: 500; }
        .filter-group input, .filter-group select { width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc; }
        .pet-card { background: white; border-radius: 10px; padding: 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .pet-info h3 { margin: 0; }
        .btn { padding: 6px 12px; border-radius: 5px; text-decoration: none; margin: 0 3px; display: inline-block; }
        .btn-primary { background: var(--secondary); color: white; }
        .btn-danger { background: var(--accent); color: white; }
        .btn-print { background: #6c757d; color: white; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="top-bar" style="margin-left:250px; background:white; padding:15px;"><h2><i class="fas fa-paw"></i> Pets</h2><div><span><?php echo $_SESSION['user_email']; ?></span> <a href="logout.php" class="btn-danger btn">Sair</a></div></div>
    <div class="content-wrapper">
        <form method="GET" class="filter-bar">
            <div class="filter-group"><label>Nome do Pet</label><input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar..."></div>
            <div class="filter-group"><label>Raça</label><input type="text" name="raca" value="<?php echo htmlspecialchars($raca); ?>"></div>
            <div class="filter-group"><label>Sexo</label><select name="sexo"><option value="">Todos</option><option value="Macho" <?php echo $sexo=='Macho'?'selected':''; ?>>Macho</option><option value="Fêmea" <?php echo $sexo=='Fêmea'?'selected':''; ?>>Fêmea</option></select></div>
            <div class="filter-group"><label>Espécie</label><input type="text" name="especie" value="<?php echo htmlspecialchars($especie); ?>"></div>
            <div class="filter-group"><label>Cor</label><input type="text" name="cor" value="<?php echo htmlspecialchars($cor); ?>"></div>
            <div class="filter-group"><label>Peso (min)</label><input type="number" step="0.1" name="peso_min" value="<?php echo $peso_min; ?>"></div>
            <div class="filter-group"><label>Peso (max)</label><input type="number" step="0.1" name="peso_max" value="<?php echo $peso_max; ?>"></div>
            <div class="filter-group"><button type="submit" class="btn btn-primary">Filtrar</button> <a href="pets.php" class="btn">Limpar</a></div>
        </form>
        <div>
            <?php if(hasPermission('pode_editar_pet')): ?><a href="add_pet.php" class="btn btn-primary">+ Novo Pet</a><?php endif; ?>
        </div>
        <?php while($pet = $pets->fetch_assoc()): ?>
        <div class="pet-card">
            <div class="pet-info">
                <h3><?php echo htmlspecialchars($pet['nome']); ?></h3>
                <p><?php echo $pet['especie']; ?> | <?php echo $pet['raca']; ?> | <?php echo $pet['sexo']; ?> | Peso: <?php echo $pet['peso']; ?>kg | Cor: <?php echo $pet['cor']; ?><br>Tutor: <?php echo htmlspecialchars($pet['tutor_nome']); ?></p>
            </div>
            <div>
                <a href="orcamento_pet.php?id=<?php echo $pet['idPet']; ?>" target="_blank" class="btn btn-print"><i class="fas fa-print"></i> Orçamento</a>
                <?php if(hasPermission('pode_editar_pet')): ?>
                <a href="edit_pet.php?id=<?php echo $pet['idPet']; ?>" class="btn btn-primary">Editar</a>
                <a href="delete_pet.php?id=<?php echo $pet['idPet']; ?>" class="btn btn-danger" onclick="return confirm('Excluir pet?')">Excluir</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</body>
</html>