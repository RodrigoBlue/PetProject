<<<<<<< HEAD
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$error = '';
$success = '';

// Se já estiver logado, redirecionar para dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        if (login($email, $senha)) {
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Email ou senha inválidos.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PetProject</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../styles/login.css">
</head>
<body>

<header>
        <div class="container header-content    "> 
            <div class="container-fluid col-11 m-auto"> 
                <a href="../index.html"> <img src="../img/PetProject.png" style="height: 100px; width: 100px;"> </a>
            
            </div>
            <nav>
                <ul>
                    <li><a href="#home">Início</a></li>
                    <li><a href="#footer-content">Sobre</a></li>
                    <li><a href="#footer-content">Contato</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="registrar.php" class="btn-register-nav">Registrar</a></li>
                   
                </ul>
            </nav>
        </div>
    </header>


    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <img src="../img/PetProject.png" alt="PetProject">
                <h2>Login Funcionário</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Digite seu email">
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required 
                           placeholder="Digite sua senha">
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
            </form>
            
            <div class="login-footer">
                <a href="registrar.php">
                <i class="fas fa-user-plus"></i> Não tem conta? Cadastre-se
                </a>
                <br>
                    <a href="../index.html">← Voltar para página inicial</a>
                
            </div>
        </div>
    </div>
</body>
=======
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$error = '';
$success = '';

// Se já estiver logado, redirecionar para dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        if (login($email, $senha)) {
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Email ou senha inválidos.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PetProject</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../styles/login.css">
</head>
<body>

<header>
        <div class="container header-content    "> 
            <div class="container-fluid col-11 m-auto"> 
                <a href="../index.html"> <img src="../img/PetProject.png" style="height: 100px; width: 100px;"> </a>
            
            </div>
            <nav>
                <ul>
                    <li><a href="#home">Início</a></li>
                    <li><a href="#footer-content">Sobre</a></li>
                    <li><a href="#footer-content">Contato</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="registrar.php" class="btn-register-nav">Registrar</a></li>
                   
                </ul>
            </nav>
        </div>
    </header>


    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <img src="../img/PetProject.png" alt="PetProject">
                <h2>Login Funcionário</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Digite seu email">
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required 
                           placeholder="Digite sua senha">
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
            </form>
            
            <div class="login-footer">
                <a href="registrar.php">
                <i class="fas fa-user-plus"></i> Não tem conta? Cadastre-se
                </a>
                <br>
                    <a href="../index.html">← Voltar para página inicial</a>
                
            </div>
        </div>
    </div>
</body>
>>>>>>> 20432ba957486b544a6a1e16972cece231fa70cc
</html>