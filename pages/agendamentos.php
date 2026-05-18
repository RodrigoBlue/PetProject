<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireFuncionario();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Agendamentos - PetProject</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .content-wrapper { margin-left: 250px; padding: 30px; }
        #calendar { max-width: 1100px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .fc-event { cursor: pointer; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="top-bar" style="margin-left:250px;"><h2><i class="fas fa-calendar-alt"></i> Agendamentos</h2><div><span><?php echo $_SESSION['user_email']; ?></span> <a href="logout.php" class="btn btn-danger">Sair</a></div></div>
    <div class="content-wrapper">
        <div id="calendar"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt-br',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: 'load_events.php',
                eventClick: function(info) {
                    alert("Agendamento: " + info.event.title + "\nData/Hora: " + info.event.start);
                    // Opcional: abrir modal para editar
                },
                editable: <?php echo hasPermission('pode_editar_agendamento') ? 'true' : 'false'; ?>,
                selectable: true,
                select: function(info) {
                    if(<?php echo hasPermission('pode_editar_agendamento') ? 'true' : 'false'; ?>) {
                        let start = info.startStr;
                        let end = info.endStr;
                        let petId = prompt("ID do Pet para agendamento:");
                        if(petId) {
                            $.post("save_agendamento.php", { start: start, pet_id: petId }, function() {
                                calendar.refetchEvents();
                            });
                        }
                    } else alert("Sem permissão para adicionar");
                }
            });
            calendar.render();
        });
    </script>
</body>
</html>