<?php
session_start();

require_once __DIR__ . '/../config/database.php';

function getConnection() {
    $host = 'localhost';
    $user = 'root';
    $password = '9090';
    $database = 'clinica_petshop1';

    $conn = new mysqli($host, $user, $password, $database);
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }
    return $conn;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

function isFuncionario() {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'funcionario';
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireFuncionario() {
    requireLogin();
    if (!isFuncionario()) {
        header('Location: ../index.html');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

function login($email, $senha) {
    $conn = getConnection();
    $sql = "SELECT id, email, senha, role FROM usuario WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if ($senha === $user['senha']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = 'funcionario';
            $_SESSION['role'] = $user['role'];
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

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Helper: check permission
function hasPermission($permission) {
    if (!isLoggedIn()) return false;
    if (isAdmin()) return true;
    
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT $permission FROM permissoes WHERE usuario_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $allowed = $row[$permission] == 1;
        $stmt->close();
        $conn->close();
        return $allowed;
    }
    $stmt->close();
    $conn->close();
    return false;
}
?>