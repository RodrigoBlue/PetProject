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
    <link rel="stylesheet" href="../styles/pets.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="top-bar">
        <h2><i class="fas fa-paw"></i> Meus Pets</h2>
        <div>
            <span><i class="fas fa-user"></i> <?php echo $_SESSION['user_email']; ?></span>
            <a href="logout.php" class="btn btn-danger" style="margin-left: 15px;">Sair</a>
        </div>
    </div>
    
    <div class="content-wrapper">
        <!-- Mensagens de Feedback -->
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Filtros Modernos -->
        <form method="GET" class="filter-bar">
            <div class="filter-group">
                <label><i class="fas fa-search"></i> Nome do Pet</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar por nome...">
            </div>
            <div class="filter-group">
                <label><i class="fas fa-dog"></i> Raça</label>
                <input type="text" name="raca" value="<?php echo htmlspecialchars($raca); ?>" placeholder="Ex: Labrador">
            </div>
            <div class="filter-group">
                <label><i class="fas fa-venus-mars"></i> Sexo</label>
                <select name="sexo">
                    <option value="">Todos</option>
                    <option value="Macho" <?php echo $sexo=='Macho'?'selected':''; ?>>🐕 Macho</option>
                    <option value="Fêmea" <?php echo $sexo=='Fêmea'?'selected':''; ?>>🐕 Fêmea</option>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-cat"></i> Espécie</label>
                <input type="text" name="especie" value="<?php echo htmlspecialchars($especie); ?>" placeholder="Cachorro, Gato...">
            </div>
            <div class="filter-group">
                <label><i class="fas fa-palette"></i> Cor</label>
                <input type="text" name="cor" value="<?php echo htmlspecialchars($cor); ?>" placeholder="Cor do pet">
            </div>
            <div class="filter-group">
                <label><i class="fas fa-weight"></i> Peso (kg)</label>
                <div style="display: flex; gap: 5px;">
                    <input type="number" step="0.1" name="peso_min" value="<?php echo $peso_min; ?>" placeholder="Mín">
                    <span style="align-self: center;">-</span>
                    <input type="number" step="0.1" name="peso_max" value="<?php echo $peso_max; ?>" placeholder="Máx">
                </div>
            </div>
            <div class="filter-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="pets.php" class="btn" style="background: #6c757d; color: white;">
                    <i class="fas fa-sync-alt"></i> Limpar
                </a>
            </div>
        </form>

        <!-- Botão Novo Pet -->
        <div style="margin-bottom: 20px;">
            <?php if(hasPermission('pode_editar_pet')): ?>
                <a href="add_pet.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Novo Pet
                </a>
            <?php endif; ?>
        </div>

        <!-- Grid de Cards dos Pets -->
        <?php if($pets->num_rows > 0): ?>
            <div class="pets-grid">
                <?php while($pet = $pets->fetch_assoc()): ?>
                    <div class="pet-card" onclick="window.location.href='pet_detalhe.php?id=<?php echo $pet['idPet']; ?>'">
                        <div class="pet-avatar">
                            <i class=""></i>
                            <div class="pet-badge">
                                <i class="fas fa-tag"></i> #<?php echo $pet['idPet']; ?>
                            </div>
                        </div>
                        <div class="pet-info">
                            <h3>
                                <span class="pet-name">
                                    <?php echo htmlspecialchars($pet['nome']); ?>
                                </span>
                            </h3>
                            <div class="pet-details">
                                <div class="detail-item">
                                    <i class="fas fa-venus-mars"></i>
                                    <strong>Sexo:</strong>
                                    <span><?php echo $pet['sexo']; ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-paw"></i>
                                    <strong>Espécie:</strong>
                                    <span><?php echo $pet['especie']; ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fa thin fa-horse"></i>
                                    <strong>Raça:</strong>
                                    <span><?php echo $pet['raca'] ?: 'SRD'; ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-weight"></i>
                                    <strong>Peso:</strong>
                                    <span><?php echo number_format($pet['peso'], 2, ',', '.'); ?> kg</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-palette"></i>
                                    <strong>Cor:</strong>
                                    <span><?php echo $pet['cor'] ?: 'Não informada'; ?></span>
                                </div>
                            </div>
                            <div class="tutor-info">
                                <i class="fas fa-user"></i>
                                <span><strong>Tutor:</strong> <?php echo htmlspecialchars($pet['tutor_nome']); ?></span>
                            </div>
                        </div>
                        <div class="pet-actions">
                            <a href="orcamento_pet.php?id=<?php echo $pet['idPet']; ?>" target="_blank" class="btn btn-print" onclick="event.stopPropagation()">
                                <i class="fas fa-print"></i> Orçamento
                            </a>
                            <?php if(hasPermission('pode_editar_pet')): ?>
                                <a href="edit_pet.php?id=<?php echo $pet['idPet']; ?>" class="btn btn-warning" onclick="event.stopPropagation()">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <a href="delete_pet.php?id=<?php echo $pet['idPet']; ?>" class="btn btn-danger" onclick="event.stopPropagation(); return confirm('⚠️ Tem certeza que deseja excluir <?php echo addslashes($pet['nome']); ?>?')">
                                    <i class="fas fa-trash"></i> Excluir
                                </a>
                            <?php endif; ?>
                        </div>
                      
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-paw"></i>
                <h3>Nenhum pet encontrado</h3>
                <p>Não encontramos pets com os filtros selecionados.</p>
                <?php if(hasPermission('pode_editar_pet')): ?>
                    <a href="add_pet.php" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-plus-circle"></i> Cadastrar primeiro pet
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Previne que o clique nos botões dentro do card dispare o clique do card
        document.querySelectorAll('.pet-actions .btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
        
        // Adiciona animação suave aos cards
        document.querySelectorAll('.pet-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transition = 'all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            });
        });
    </script>
</body>
</html>