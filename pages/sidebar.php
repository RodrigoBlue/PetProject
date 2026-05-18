<?php
// sidebar.php - Menu lateral para todas as páginas do admin
?>
<div class="sidebar">
    <div class="sidebar-header">
        <img src="../img/PetProject.png" alt="PetProject">
        <h3>PetProject</h3>
        <p>Sistema Veterinário</p>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="tutores.php">
            <i class="fas fa-users"></i> Tutores
        </a>
        <a href="pets.php">
            <i class="fas fa-paw"></i> Pets
        </a>
        <a href="agendamentos.php">
            <i class="fas fa-calendar-alt"></i> Agendamentos
        </a>
        <a href="atendimentos.php">
            <i class="fas fa-stethoscope"></i> Atendimentos
        </a>
        <a href="produtos.php">
            <i class="fas fa-box"></i> Produtos
        </a>
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="funcionarios.php">
            <i class="fas fa-user-md"></i> Funcionários
        </a>
        <?php endif; ?>
        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i> Sair
        </a>
    </div>
</div>

<style>
    .sidebar {
        width: 250px;
        background-color: #292F36;
        color: white;
        position: fixed;
        height: 100vh;
        overflow-y: auto;
        z-index: 100;
    }
    
    .sidebar-header {
        padding: 20px;
        text-align: center;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .sidebar-header img {
        height: 60px;
        width: 60px;
        margin-bottom: 10px;
    }
    
    .sidebar-header h3 {
        font-size: 1.2rem;
    }
    
    .sidebar-menu {
        padding: 20px 0;
    }
    
    .sidebar-menu a {
        display: block;
        padding: 12px 20px;
        color: white;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .sidebar-menu a:hover {
        background-color: #4ECDC4;
        padding-left: 30px;
    }
    
    .sidebar-menu a i {
        margin-right: 10px;
        width: 20px;
    }
    
    .top-bar {
        background-color: white;
        padding: 15px 30px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: fixed;
        top: 0;
        right: 0;
        left: 250px;
        z-index: 99;
    }
    
    .content-wrapper {
        margin-left: 250px;
        margin-top: 70px;
        padding: 30px;
    }
    
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
    }
</style>