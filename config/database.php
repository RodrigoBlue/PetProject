<?php
// Configuração do banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '9090');
define('DB_NAME', 'clinica_petshop');

// Conexão com o banco de dados
function obterconexao() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Verificar conexão
        if ($conn->connect_error) {
            throw new Exception("Falha na conexão: " . $conn->connect_error);
        }
        
        // Definir charset para UTF-8
        $conn->set_charset("utf8");
        
        return $conn;
    } catch (Exception $e) {
        die("Erro de conexão: " . $e->getMessage());
    }
}

// Função para executar queries com segurança
function executeQuery($sql, $params = []) {
    $conn = obterconexao();
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Função para buscar dados
function fetchData($sql, $params = []) {
    $conn = obterconexao();
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    
    $stmt->close();
    $conn->close();
    
    return $data;
}
?>