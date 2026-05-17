<?php
session_start();

// Verificar se usuário está logado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

// Verificar se é funcionário
function isFuncionario() {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'funcionario';
}

// Redirecionar se não estiver logado
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Redirecionar se não for funcionário
function requireFuncionario() {
    requireLogin();
    if (!isFuncionario()) {
        header('Location: ../index.html');
        exit();
    }
}

// Função de login
function login($email, $senha) {
    $conn = getConnection();
    
    // Buscar usuário pelo email
    $sql = "SELECT id, email, senha FROM usuario WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verificar senha (assumindo que as senhas estão em texto puro no banco)
        // Em produção, use password_verify() com senhas hash
        if ($senha === $user['senha']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = 'funcionario';
            $_SESSION['login_time'] = time();
            
            $stmt->close();
            $conn->close();
            return true;
        }
    }
    
    $stmt->close();
    $conn->close();
    return false;
}

// Função de logout
function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>