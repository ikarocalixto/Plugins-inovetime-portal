jQuery(document).ready(function($) {
    // Enviar dados do formulário de nova tarefa via AJAX
    $('#kanban-add-task').submit(function(e) {
        e.preventDefault();

        var taskData = {
            action: 'adicionar_tarefa_kanban',
            task_name: $(this).find('input[name="task_name"]').val(),
            description: $(this).find('textarea[name="description"]').val(),
            due_date: $(this).find('input[name="due_date"]').val()
        };

        $.ajax({
            type: "POST",
            url: kanban_ajax.ajax_url,
            data: taskData,
            success: function(response) {
                var uniqueId = 'task-' + response;
                var taskHtml = '<div id="' + uniqueId + '" class="task" draggable="true" ondragstart="drag(event)" data-descricao="' + taskData.description + '" data-prazo="' + taskData.due_date + '">' + taskData.task_name + '</div>';
                $('.kanban-tasks[data-status="todo"]').append(taskHtml);
            }
        });
    });

    
    window.allowDrop = function(event) {
        event.preventDefault();
    };
    
    window.drag = function(event) {
        event.dataTransfer.setData("text", event.target.id);
    };
    
    window.drop = function(event) {
        event.preventDefault();
    
        var data = event.dataTransfer.getData("text");
        var taskElement = document.getElementById(data);
        var targetColumn = event.target.closest('.kanban-column');
    
        if (!targetColumn) {
            console.error("Não foi possível encontrar a coluna de destino.");
            return;
        }
    
        targetColumn.appendChild(taskElement);
        var novoStatus = targetColumn.getAttribute('data-status');
        var taskId = taskElement.id.split('-')[1];
    
        // Atualizar o status da tarefa no banco de dados via AJAX
        atualizarStatusTarefa(taskId, novoStatus);
    };
    
    function atualizarStatusTarefa(taskId, novoStatus) {
        jQuery.ajax({
            type: "POST",
            url: kanban_ajax.ajax_url,
            data: {
                action: 'mover_tarefa_kanban',
                task_id: taskId,
                task_status: novoStatus
            },
            success: function(response) {
                console.log("Tarefa movida com sucesso: ", response);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Erro ao mover tarefa: ", textStatus, errorThrown);
            }
        });
    }
    
    
    
    $(document).on('click', '.task', function() {
        var taskId = $(this).attr('id').split('-')[1];
        var descricao = $(this).data('descricao');
        var prazo = $(this).data('prazo');
        var nomeTarefa = $(this).text().trim();
    
        var formHtml = '<form id="form-editar-tarefa">' +
                       '<input type="hidden" name="task_id" value="' + taskId + '">' +
                       '<label for="task_name">Nome da Tarefa</label>' +
                       '<input type="text" name="task_name" value="' + nomeTarefa + '">' +
                       '<label for="description">Descrição</label>' +
                       '<textarea name="description">' + descricao + '</textarea>' +
                       '<label for="due_date">Prazo</label>' +
                       '<input type="date" name="due_date" value="' + prazo + '">' +
                       '<button type="submit">Salvar</button>' +
                       '<button type="button" id="btn-excluir-tarefa" data-task-id="' + taskId + '">Excluir</button>' +
                       '</form>';
    
        $('#popup-info').html(formHtml).show();
    });
    

    $(document).on('click', '#btn-excluir-tarefa', function() {
        var taskId = $(this).data('task-id');
    
        if(confirm("Tem certeza que deseja excluir esta tarefa?")) {
            $.ajax({
                type: "POST",
                url: kanban_ajax.ajax_url,
                data: {
                    action: 'excluir_tarefa_kanban',
                    task_id: taskId
                },
                success: function(response) {
                    console.log("Tarefa excluída: ", response);
                    $('#popup-info').hide();
                    $('#task-' + taskId).remove(); // Remove a tarefa do quadro
                },
                error: function(error) {
                    console.error("Erro ao excluir tarefa: ", error);
                }
            });
        }
    });
    

    // Enviar dados do formulário de edição via AJAX
    $(document).on('submit', '#form-editar-tarefa', function(e) {
        e.preventDefault();

        var taskData = {
            action: 'editar_tarefa_kanban',
            task_id: $(this).find('input[name="task_id"]').val(),
            task_name: $(this).find('input[name="task_name"]').val(),
            description: $(this).find('textarea[name="description"]').val(),
            due_date: $(this).find('input[name="due_date"]').val()
        };

        $.ajax({
            type: "POST",
            url: kanban_ajax.ajax_url,
            data: taskData,
            success: function(response) {
                var taskId = 'task-' + taskData.task_id;
                var updatedTask = $('#' + taskId);
                updatedTask.text(taskData.task_name);
                updatedTask.data('descricao', taskData.description);
                updatedTask.data('prazo', taskData.due_date);
                $('#popup-info').hide();
            }
        });
    });

    $(document).ready(function() {
        // Fechar o popup ao clicar no botão 'X'
        $('#popup-close-pp').click(function() {
            $('#popup-info').hide();
            $('#popup-background').hide(); // Não esqueça de esconder o fundo escurecido
        });
    
        // Outros eventos do popup
        // ...
    });
    

    
});


jQuery(document).ready(function($) {
    $('#kanban-add-task').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize(); // Isso irá capturar os dados do formulário

        $.ajax({
            type: "POST",
            url: ajaxurl, // URL para o manipulador AJAX do WordPress
            data: formData + '&action=adicionar_tarefa_kanban',
            success: function(response) {
                // Tratar a resposta do sucesso
                console.log(response);
            },
            error: function() {
                // Tratar erro
                console.error('Erro ao adicionar tarefa.');
            }
        });
    });
});
jQuery(document).ready(function($) {    
    // Iniciar o treinamento
    $('#treinamento-trainee-form').on('submit', function(e) {
        e.preventDefault();
        var userId = $(this).find('select[name="user_id"]').val();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                'action': 'iniciar_treinamento_trainee',
                'user_id': userId
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message); // Ou atualize a interface do usuário conforme necessário
            }
        }
        });
    });
});
jQuery(document).ready(function($) {
    $('#concluir-modulo').click(function() {
        var userId = $(this).data('user-id');
        var moduloAtual = 1; // Obtenha o número do módulo atual. Isso pode ser obtido do DOM ou de uma variável global.

        $.ajax({
            url: kanban_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'concluir_modulo_atual',
                user_id: userId,
                modulo: moduloAtual
            },
            success: function(response) {
                if(response.success) {
                    alert(response.data.message);
                    carregarTarefasProximoModulo(moduloAtual, userId);
                } else {
                    alert('Algumas tarefas ainda não foram concluídas.');
                }
            },
            error: function() {
                alert('Ocorreu um erro ao tentar concluir o módulo.');
            }
        });
    });
});

function carregarTarefasProximoModulo(moduloAtual, userId) {
    // Lógica para carregar tarefas do próximo módulo
    // ...
}



function verificarConclusaoModuloAtual(userId, callback) {
    // Substitua 'modulo_atual' pelo código que obtém o módulo atual do usuário
    var moduloAtual = 1 

    jQuery.post(ajaxurl, {
        action: "verificar_conclusao_modulo",
        user_id: userId,
        modulo: moduloAtual
    }, function (response) {
        if (response.success) {
            callback(response.data.moduloConcluido);
        } else {
            console.error('Erro ao verificar conclusão do módulo.');
        }
    });
}

function carregarTarefasProximoModulo(moduloAtual, userId) {
    jQuery.post(ajaxurl, {
        action: 'carregar_mais_tarefas',
        modulo: moduloAtual + 1,
        user_id: userId
    }, function(response) {
        if (response.success) {
            alert(response.data.message);
        } else {
            console.error('Erro ao carregar tarefas do próximo módulo.');
        }
    });
}

// Restante do código...
