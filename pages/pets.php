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
        :root {
            --primary: #ffffff;
            --secondary: #4ECDC4;
            --accent: #fc0000;
            --dark: #292F36;
            --light: #F7FFF7;
            --gradient-start: #4ECDC4;
            --gradient-end: #44b3aa;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 15px rgba(0,0,0,0.1);
            --shadow-lg: 0 8px 25px rgba(0,0,0,0.15);
            --shadow-hover: 0 15px 35px rgba(78, 205, 196, 0.2);
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
            backdrop-filter: blur(10px);
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

        /* Filter Bar - Modernizado */
        .filter-bar {
            background: white;
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            border: 1px solid rgba(78, 205, 196, 0.1);
        }

        .filter-bar:hover {
            box-shadow: var(--shadow-lg);
        }

        .filter-group {
            flex: 1;
            min-width: 150px;
        }

        .filter-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-group label i {
            color: var(--secondary);
            margin-right: 5px;
        }

        .filter-group input, 
        .filter-group select {
            width: 100%;
            padding: 10px 12px;
            border-radius: 12px;
            border: 2px solid #e0e0e0;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: white;
        }

        .filter-group input:focus, 
        .filter-group select:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.1);
        }

        /* Botões */
        .btn {
            padding: 8px 16px;
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
            background: linear-gradient(135deg, var(--secondary), var(--gradient-end));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .btn-danger {
            background: linear-gradient(135deg, #fc0000, #d00000);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(252, 0, 0, 0.3);
        }

        .btn-print {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }

        .btn-print:hover {
            transform: translateY(-2px);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: white;
        }

        /* Grid de Pets - Cards Modernos */
        .pets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .pet-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            cursor: pointer;
            border: 1px solid rgba(78, 205, 196, 0.1);
        }

        /* Efeito Hover no Card */
        .pet-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--shadow-hover);
        }

        .pet-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--secondary), var(--gradient-end), var(--secondary));
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .pet-card:hover::before {
            transform: scaleX(1);
        }

        /* Avatar/Imagem do Pet */
        .pet-avatar {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            text-align: center;
            position: relative;
            transition: all 0.4s ease;
        }

        .pet-avatar i {
            font-size: 70px;
            color: var(--secondary);
            transition: all 0.4s ease;
        }

        .pet-card:hover .pet-avatar {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        }

        .pet-card:hover .pet-avatar i {
            transform: scale(1.1) rotate(5deg);
            color: var(--gradient-end);
        }

        .pet-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--secondary);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: var(--shadow-sm);
        }

        .pet-badge i {
            font-size: 0.7rem;
            margin-right: 4px;
        }

        /* Informações do Pet */
        .pet-info {
            padding: 20px;
        }

        .pet-info h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .pet-info h3 .pet-name {
            color: var(--dark);
            transition: color 0.3s ease;
        }

        .pet-card:hover .pet-info h3 .pet-name {
            color: var(--secondary);
        }

        .pet-details {
            margin-bottom: 15px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.85rem;
        }

        .detail-item i {
            width: 30px;
            color: var(--secondary);
            font-size: 1rem;
        }

        .detail-item strong {
            width: 70px;
            color: var(--dark);
        }

        .detail-item span {
            color: #666;
            flex: 1;
        }

        .tutor-info {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 12px;
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tutor-info i {
            color: var(--secondary);
            font-size: 1.1rem;
        }

        .tutor-info span {
            font-size: 0.85rem;
            color: #666;
        }

        .tutor-info strong {
            color: var(--dark);
            font-weight: 600;
        }

        /* Ações do Card - Botões menores e sem o botão Detalhes */
        .pet-actions {
            padding: 15px 20px;
            background: #f8f9fa;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            border-top: 1px solid #e0e0e0;
        }

        .pet-actions .btn {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
        }

        .empty-state i {
            font-size: 80px;
            color: var(--secondary);
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            color: var(--dark);
            margin-bottom: 10px;
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

        @keyframes slideIn {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
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

        /* Indicador de clique */
        .click-hint {
            position: absolute;
            bottom: 10px;
            right: 15px;
            font-size: 0.7rem;
            color: #999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .pet-card:hover .click-hint {
            opacity: 1;
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
            .pets-grid {
                grid-template-columns: 1fr;
            }
            .filter-bar {
                flex-direction: column;
            }
        }

        /* Scrollbar personalizada */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--secondary);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--gradient-end);
        }
    </style>
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
                            <i class="fas fa-dog"></i>
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
                                    <i class="fas fa-dog"></i>
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