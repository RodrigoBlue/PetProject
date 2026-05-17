<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Se já estiver logado, redirecionar para dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';
$nome = '';
$email = '';

// Processar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    // Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif (strlen($nome) < 3) {
        $error = 'O nome deve ter pelo menos 3 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um email válido.';
    } elseif (strlen($senha) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $confirmar_senha) {
        $error = 'As senhas não conferem.';
    } else {
        // Verificar se email já existe
        $conn = getConnection();
        $sql_check = "SELECT id FROM usuario WHERE email = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $error = 'Este email já está cadastrado.';
        } else {
            // Inserir novo usuário
            // NOTA: Em produção, use password_hash($senha, PASSWORD_DEFAULT)
            $sql_insert = "INSERT INTO usuario (nome, email, senha, status) VALUES (?, ?, ?, 1)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("sss", $nome, $email, $senha);
            
            if ($stmt_insert->execute()) {
                $success = 'Cadastro realizado com sucesso! Faça login para continuar.';
                // Limpar campos
                $nome = '';
                $email = '';
                // Redirecionar após 2 segundos
                header("refresh:2;url=login.php");
            } else {
                $error = 'Erro ao cadastrar. Tente novamente.';
            }
            $stmt_insert->close();
        }
        
        $stmt_check->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar - PetProject</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../styles/registrar.css">
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
    

<div class="register-container">
        <div class="register-box">
            <div class="register-header">
                <img src="../img/PetProject.png" alt="PetProject">
                <h2>Criar Conta</h2>
                <p>Cadastre-se para acessar o sistema</p>
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
            
            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label for="nome">
                        <i class="fas fa-user"></i> Nome Completo
                    </label>
                    <input type="text" id="nome" name="nome" required 
                           placeholder="Digite seu nome completo"
                           value="<?php echo htmlspecialchars($nome); ?>">
                    <div class="error-message" id="nomeError">
                        Nome deve ter pelo menos 3 caracteres
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Digite seu email"
                           value="<?php echo htmlspecialchars($email); ?>">
                    <div class="error-message" id="emailError">
                        Digite um email válido
                    </div>
                </div>
                
                <div class="form-group show-password">
                    <label for="senha">
                        <i class="fas fa-lock"></i> Senha
                    </label>
                    <input type="password" id="senha" name="senha" required 
                           placeholder="Digite sua senha (mínimo 6 caracteres)">
                    <i class="fas fa-eye toggle-password" data-target="senha"></i>
                    <div class="password-strength">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="strength-text" id="strengthText"></div>
                    <div class="error-message" id="senhaError">
                        Senha deve ter pelo menos 6 caracteres
                    </div>
                </div>
                
                <div class="form-group show-password">
                    <label for="confirmar_senha">
                        <i class="fas fa-check-circle"></i> Confirmar Senha
                    </label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required 
                           placeholder="Confirme sua senha">
                    <i class="fas fa-eye toggle-password" data-target="confirmar_senha"></i>
                    <div class="error-message" id="confirmarSenhaError">
                        As senhas não conferem
                    </div>
                </div>
                
                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus"></i> Cadastrar
                </button>
                
                <div class="terms">
                    Ao cadastrar, você concorda com nossos 
                    <a href="#">Termos de Uso</a> e 
                    <a href="#">Política de Privacidade</a>
                </div>
            </form>
            
            <div class="register-footer">
                <a href="login.php">
                    <i class="fas fa-sign-in-alt"></i> Já tem uma conta? Faça login
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Validação em tempo real
        const form = document.getElementById('registerForm');
        const nomeInput = document.getElementById('nome');
        const emailInput = document.getElementById('email');
        const senhaInput = document.getElementById('senha');
        const confirmarSenhaInput = document.getElementById('confirmar_senha');
        
        // Força da senha
        function checkPasswordStrength(password) {
            let strength = 0;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[$@#&!]+/)) strength++;
            
            const width = (strength / 5) * 100;
            strengthBar.style.width = width + '%';
            
            if (strength <= 1) {
                strengthBar.style.backgroundColor = '#dc3545';
                strengthText.textContent = 'Senha fraca';
                strengthText.style.color = '#dc3545';
            } else if (strength <= 3) {
                strengthBar.style.backgroundColor = '#ffc107';
                strengthText.textContent = 'Senha média';
                strengthText.style.color = '#ffc107';
            } else {
                strengthBar.style.backgroundColor = '#28a745';
                strengthText.textContent = 'Senha forte';
                strengthText.style.color = '#28a745';
            }
            
            return strength;
        }
        
        // Validar nome
        function validateNome() {
            const nome = nomeInput.value.trim();
            const nomeError = document.getElementById('nomeError');
            
            if (nome.length < 3) {
                nomeInput.classList.add('error');
                nomeError.style.display = 'block';
                return false;
            } else {
                nomeInput.classList.remove('error');
                nomeError.style.display = 'none';
                return true;
            }
        }
        
        // Validar email
        function validateEmail() {
            const email = emailInput.value.trim();
            const emailError = document.getElementById('emailError');
            const emailRegex = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
            
            if (!emailRegex.test(email)) {
                emailInput.classList.add('error');
                emailError.style.display = 'block';
                return false;
            } else {
                emailInput.classList.remove('error');
                emailError.style.display = 'none';
                return true;
            }
        }
        
        // Validar senha
        function validateSenha() {
            const senha = senhaInput.value;
            const senhaError = document.getElementById('senhaError');
            
            if (senha.length < 6) {
                senhaInput.classList.add('error');
                senhaError.style.display = 'block';
                return false;
            } else {
                senhaInput.classList.remove('error');
                senhaError.style.display = 'none';
                return true;
            }
        }
        
        // Validar confirmação de senha
        function validateConfirmarSenha() {
            const senha = senhaInput.value;
            const confirmarSenha = confirmarSenhaInput.value;
            const confirmarSenhaError = document.getElementById('confirmarSenhaError');
            
            if (senha !== confirmarSenha) {
                confirmarSenhaInput.classList.add('error');
                confirmarSenhaError.style.display = 'block';
                return false;
            } else {
                confirmarSenhaInput.classList.remove('error');
                confirmarSenhaError.style.display = 'none';
                return true;
            }
        }
        
        // Event listeners para validação em tempo real
        nomeInput.addEventListener('input', validateNome);
        emailInput.addEventListener('input', validateEmail);
        senhaInput.addEventListener('input', function() {
            validateSenha();
            checkPasswordStrength(this.value);
            if (confirmarSenhaInput.value) {
                validateConfirmarSenha();
            }
        });
        confirmarSenhaInput.addEventListener('input', validateConfirmarSenha);
        
        // Validação no submit
        form.addEventListener('submit', function(e) {
            const isNomeValid = validateNome();
            const isEmailValid = validateEmail();
            const isSenhaValid = validateSenha();
            const isConfirmarSenhaValid = validateConfirmarSenha();
            
            if (!isNomeValid || !isEmailValid || !isSenhaValid || !isConfirmarSenhaValid) {
                e.preventDefault();
                alert('Por favor, corrija os erros no formulário.');
            }
        });
        
        // Mostrar/Esconder senha
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });
        
        // Inicializar força da senha
        checkPasswordStrength('');
    </script>
</body>
</html>