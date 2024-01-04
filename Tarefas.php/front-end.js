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

    // Permitir arrastar tarefas
    function allowDrop(event) {
        event.preventDefault();
    }

    function drag(event) {
        event.dataTransfer.setData("text", event.target.id);
    }

    function drop(event) {
        event.preventDefault();
        var data = event.dataTransfer.getData("text");
        var taskElement = document.getElementById(data);
        if (event.target.classList.contains('kanban-tasks')) {
            event.target.appendChild(taskElement);
        } else if (event.target.classList.contains('task')) {
            event.target.parentNode.appendChild(taskElement);
        }

        // Atualizar status da tarefa no banco de dados via AJAX
        var novoStatus = taskElement.parentNode.getAttribute('data-status');
        var taskId = taskElement.id.split('-')[1];

        $.ajax({
            type: "POST",
            url: kanban_ajax.ajax_url,
            data: {
                action: 'mover_tarefa_kanban',
                task_id: taskId,
                task_status: novoStatus
            }
        });
    }

    // Mostrar popup com detalhes da tarefa
    $(document).on('click', '.task', function() {
        var descricao = $(this).data('descricao');
        var prazo = $(this).data('prazo');
        var conteudoPopup = '<p>Descrição: ' + descricao + '</p><p>Prazo: ' + prazo + '</p>';

        $('#popup-info').html(conteudoPopup).show();
    });

    // Fechar o popup ao clicar fora
    $(document).on('click', '#popup-info', function() {
        $(this).hide();
    });
});

// Mostrar formulário de edição quando uma tarefa é clicada
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
                   '</form>';

    $('#popup-info').html(formHtml).show();
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
            // Atualizar as informações da tarefa no quadro
            var taskId = 'task-' + taskData.task_id;
            $('#' + taskId).text(taskData.task_name).data('descricao', taskData.description).data('prazo', taskData.due_date);
            $('#popup-info').hide();
        }
    });
});
