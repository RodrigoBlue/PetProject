<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Garantir que não há saída antes do JSON
ob_clean();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

if(!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$conn = getConnection();

// Buscar agendamentos com informações do pet
$sql = "SELECT 
            a.idAgendamento, 
            a.data, 
            a.hora, 
            IFNULL(a.tipo_servico, 'Consulta') as tipo_servico,
            p.nome as pet_name
        FROM agendamento a 
        INNER JOIN pet p ON a.idPet = p.idPet 
        ORDER BY a.data, a.hora";

$result = $conn->query($sql);

if(!$result) {
    echo json_encode(['error' => $conn->error]);
    exit();
}

$events = [];

// Cores diferentes por tipo de serviço
$cores = [
    'Consulta' => '#4ECDC4',
    'Vacina' => '#95E77E',
    'Cirurgia' => '#E74C3C',
    'Banho' => '#3498DB',
    'Tosa' => '#F39C12',
    'Emergência' => '#E74C3C',
    'Retorno' => '#9B59B6',
    'Exame' => '#1ABC9C'
];

while($row = $result->fetch_assoc()) {
    $tipo = $row['tipo_servico'];
    $cor = $cores[$tipo] ?? '#4ECDC4';
    
    // Ícone para o tipo de serviço
    $icone = '🐾';
    switch($tipo) {
        case 'Consulta': $icone = '🏥'; break;
        case 'Vacina': $icone = '💉'; break;
        case 'Cirurgia': $icone = '🔪'; break;
        case 'Banho': $icone = '🛁'; break;
        case 'Tosa': $icone = '✂️'; break;
        case 'Emergência': $icone = '🚨'; break;
        case 'Retorno': $icone = '📋'; break;
        case 'Exame': $icone = '🔬'; break;
    }
    
    $events[] = [
        'id' => (int)$row['idAgendamento'],
        'title' => $icone . ' ' . $row['pet_name'] . ' - ' . $tipo,
        'start' => $row['data'] . 'T' . $row['hora'],
        'color' => $cor,
        'textColor' => '#ffffff',
        'display' => 'block'
    ];
}

echo json_encode($events);
?>