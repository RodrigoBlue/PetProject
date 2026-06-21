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
    <link rel="stylesheet" href="../styles/agendamentos.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="top-bar">
        <h2><i class="fas fa-calendar-alt"></i> Agendamentos</h2>
        <div>
            <span><i class="fas fa-user"></i> <?php echo $_SESSION['user_email']; ?></span> 
            <a href="logout.php" class="btn-danger"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
    </div>
    <div class="content-wrapper">
        <!-- Mensagens de feedback -->
        <div id="messageArea"></div>
        
        <div id="calendar"></div>
    </div>

    <!-- Modal Simples para Agendamento -->
    <div id="agendamentoModal" class="simple-modal">
        <div class="simple-modal-content">
            <h3><i class="fas fa-calendar-plus"></i> Novo Agendamento</h3>
            <form id="agendamentoForm">
                <div class="form-group">
                    <label><i class="fas fa-calendar-day"></i> Data Selecionada</label>
                    <input type="text" id="selectedDate" readonly style="background: #f5f5f5;">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Horário</label>
                    <input type="time" id="agendamentoHora" required placeholder="Ex: 14:30">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-stethoscope"></i> Tipo de Serviço</label>
                    <select id="agendamentoTipo" required>
                        <option value="Consulta">🐾 Consulta</option>
                        <option value="Vacina">💉 Vacina</option>
                        <option value="Cirurgia">🏥 Cirurgia</option>
                        <option value="Banho">🛁 Banho</option>
                        <option value="Tosa">✂️ Tosa</option>
                        <option value="Emergência">🚨 Emergência</option>
                        <option value="Retorno">📋 Retorno</option>
                        <option value="Exame">🔬 Exame</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-paw"></i> Nome do Pet</label>
                    <input type="text" id="agendamentoPet" required placeholder="Digite o nome do pet">
                </div>
                
                <div class="button-group">
                    <button type="button" class="btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let selectedDate = null;
        let calendar = null;
        
        // Mostrar mensagem
        function showMessage(message, type) {
            const messageArea = document.getElementById('messageArea');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            messageArea.innerHTML = `
                <div class="alert ${alertClass}" style="animation: slideIn 0.3s ease;">
                    <i class="fas ${icon}"></i> 
                    ${message}
                </div>
            `;
            
            setTimeout(() => {
                messageArea.innerHTML = '';
            }, 3000);
        }
        
        // Função para carregar eventos com retry
        function loadEventsWithRetry(retryCount = 0) {
            $.ajax({
                url: 'load_events.php',
                method: 'GET',
                dataType: 'json',
                timeout: 10000,
                success: function(events) {
                    if (calendar) {
                        calendar.removeAllEvents();
                        calendar.addEventSource(events);
                        console.log('Eventos carregados:', events.length);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro ao carregar eventos:', error);
                    if (retryCount < 3) {
                        setTimeout(() => loadEventsWithRetry(retryCount + 1), 2000);
                    } else {
                        showMessage('Erro ao carregar agendamentos. Recarregue a página.', 'error');
                    }
                }
            });
        }
        
        // Inicializar calendário
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt-br',
                height: 'auto',
                contentHeight: 600,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: 'Hoje',
                    month: 'Mês',
                    week: 'Semana',
                    day: 'Dia'
                },
                events: function(fetchInfo, successCallback, failureCallback) {
                    $.ajax({
                        url: 'load_events.php',
                        method: 'GET',
                        dataType: 'json',
                        success: function(events) {
                            successCallback(events);
                        },
                        error: function(xhr, status, error) {
                            console.error('Erro:', error);
                            failureCallback(error);
                            showMessage('Erro ao carregar eventos do calendário', 'error');
                        }
                    });
                },
                eventClick: function(info) {
                    let event = info.event;
                    let title = event.title;
                    let start = event.start;
                    let hora = start.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
                    let data = start.toLocaleDateString('pt-BR');
                    
                    let mensagem = `📅 Data: ${data}\n⏰ Horário: ${hora}\n📋 ${title}\n\nDeseja excluir este agendamento?`;
                    
                    if(confirm(mensagem)) {
                        if(confirm("⚠️ Tem certeza que deseja excluir este agendamento?")) {
                            $.post("delete_agendamento.php", { id: event.id }, function(response) {
                                if(response === "success") {
                                    showMessage("✅ Agendamento excluído com sucesso!", "success");
                                    calendar.refetchEvents();
                                } else {
                                    showMessage("❌ Erro ao excluir agendamento", "error");
                                }
                            }).fail(function() {
                                showMessage("❌ Erro ao excluir agendamento", "error");
                            });
                        }
                    }
                },
                dateClick: function(info) {
                    if(<?php echo hasPermission('pode_editar_agendamento') ? 'true' : 'false'; ?>) {
                        selectedDate = info.date;
                        const dataFormatada = info.date.toLocaleDateString('pt-BR', {
                            weekday: 'long', 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric'
                        });
                        document.getElementById('selectedDate').value = dataFormatada;
                        document.getElementById('agendamentoModal').style.display = 'flex';
                        document.getElementById('agendamentoHora').focus();
                    } else {
                        showMessage("Sem permissão para adicionar agendamentos", "error");
                    }
                },
                loading: function(isLoading) {
                    if (isLoading) {
                        // Mostrar loading
                        calendarEl.style.opacity = '0.5';
                    } else {
                        calendarEl.style.opacity = '1';
                    }
                }
            });
            
            calendar.render();
            
            // Carregar eventos após renderizar
            setTimeout(() => {
                calendar.refetchEvents();
            }, 500);
            
            // Submit do formulário de agendamento
            $('#agendamentoForm').on('submit', function(e) {
                e.preventDefault();
                
                let hora = $('#agendamentoHora').val();
                let tipo = $('#agendamentoTipo').val();
                let pet = $('#agendamentoPet').val().trim();
                
                if(!hora || !pet) {
                    showMessage("Por favor, preencha todos os campos", "error");
                    return;
                }
                
                // Validar formato da hora
                if(!/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/.test(hora)) {
                    showMessage("Horário inválido! Use o formato HH:MM (ex: 14:30)", "error");
                    return;
                }
                
                // Desabilitar botão para evitar duplo clique
                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Salvando...');
                
                $.post("save_agendamento.php", { 
                    start: selectedDate.toISOString(), 
                    hora: hora,
                    tipo_servico: tipo,
                    nome_pet: pet
                }, function(response) {
                    submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Salvar');
                    
                    if(response === "success") {
                        showMessage("✅ Agendamento salvo com sucesso!", "success");
                        closeModal();
                        calendar.refetchEvents();
                        $('#agendamentoForm')[0].reset();
                    } else {
                        showMessage("❌ Erro: " + response, "error");
                    }
                }).fail(function(xhr) {
                    submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Salvar');
                    showMessage("❌ Erro ao salvar agendamento", "error");
                });
            });
        });
        
        function closeModal() {
            $('#agendamentoModal').css('display', 'none');
            $('#agendamentoForm')[0].reset();
        }
        
        // Fechar modal clicando fora
        window.onclick = function(event) {
            let modal = document.getElementById('agendamentoModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>