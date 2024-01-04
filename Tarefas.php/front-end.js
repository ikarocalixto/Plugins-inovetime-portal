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
                       '<input type="text" name="task_name" value="' + nomeTarefa + '">' +
                       '<textarea name="description">' + descricao + '</textarea>' +
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

    // Fechar o popup ao clicar fora
    $(document).on('click', '#popup-info', function() {
        $(this).hide();
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
