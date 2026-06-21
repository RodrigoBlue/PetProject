<?php
// fix_horas_registros.php - Corrigir horas dos registros clínicos antigos
require_once '../config/database.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    die("Você precisa estar logado para executar este script.");
}

$conn = getConnection();

echo "<h1>Corrigindo Horas dos Registros Clínicos</h1>";

// Primeiro, verificar se a coluna hora_registro existe
$check_column = $conn->query("SHOW COLUMNS FROM pet_registros_clinicos LIKE 'hora_registro'");
if ($check_column->num_rows == 0) {
    echo "<p>Adicionando coluna hora_registro...</p>";
    $conn->query("ALTER TABLE pet_registros_clinicos ADD COLUMN hora_registro TIME DEFAULT '12:00:00'");
    echo "<p style='color: green;'>✓ Coluna hora_registro adicionada com sucesso!</p>";
}

// Buscar registros que têm hora_registro = '12:00:00' (padrão) ou NULL
$sql = "SELECT rc.idRegistro, rc.data_registro, a.hora as hora_atendimento
        FROM pet_registros_clinicos rc
        LEFT JOIN atendimento a ON a.data = rc.data_registro AND a.idPet = rc.idPet
        WHERE rc.hora_registro = '12:00:00' OR rc.hora_registro IS NULL";
$result = $conn->query($sql);

$count = 0;
echo "<h2>Registros encontrados para corrigir: " . $result->num_rows . "</h2>";

while ($rc = $result->fetch_assoc()) {
    $nova_hora = '12:00:00';
    
    // Tentar buscar a hora do atendimento correspondente
    if ($rc['hora_atendimento']) {
        $nova_hora = $rc['hora_atendimento'];
        echo "<p>Registro ID {$rc['idRegistro']} - Data {$rc['data_registro']} - Hora encontrada no atendimento: $nova_hora</p>";
    } else {
        // Se não encontrar, perguntar ao usuário ou usar 14:00 como padrão
        $nova_hora = '14:00:00';
        echo "<p style='color: orange;'>Registro ID {$rc['idRegistro']} - Data {$rc['data_registro']} - Usando hora padrão: $nova_hora</p>";
    }
    
    // Atualizar a hora
    $stmt = $conn->prepare("UPDATE pet_registros_clinicos SET hora_registro = ? WHERE idRegistro = ?");
    $stmt->bind_param("si", $nova_hora, $rc['idRegistro']);
    if ($stmt->execute()) {
        $count++;
    }
    $stmt->close();
}

// Atualizar também os atendimentos que possam estar com hora errada
echo "<h2>Sincronizando atendimentos...</h2>";
$sql_sync = "UPDATE atendimento a
             JOIN pet_registros_clinicos rc ON rc.idPet = a.idPet AND rc.data_registro = a.data
             SET a.hora = rc.hora_registro
             WHERE a.hora != rc.hora_registro";
$conn->query($sql_sync);
echo "<p>✓ Atendimentos sincronizados com as horas dos registros clínicos</p>";

echo "<hr>";
echo "<h2>Resumo:</h2>";
echo "<ul>";
echo "<li>Registros corrigidos: <strong>$count</strong></li>";
echo "</ul>";

echo "<p style='color: green; font-size: 16px;'>✅ Correção concluída! Agora as horas devem aparecer corretamente.</p>";

echo "<br><a href='dashboard.php' style='display: inline-block; padding: 10px 20px; background: #4ECDC4; color: white; text-decoration: none; border-radius: 5px;'>Ir para o Dashboard</a>";
echo "&nbsp;&nbsp;";
echo "<a href='atendimentos.php' style='display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px;'>Ir para Atendimentos</a>";

$conn->close();
?>