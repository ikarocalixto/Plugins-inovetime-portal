jQuery(document).ready(function($) {
      // Evento de clique para o botão 'Adicionar Tarefa'
      $('#mostrar-form-tarefa').click(function() {
        // Alterna a visibilidade do formulário
        $('#kanban-add-task').toggle();
    });

    // Código existente para enviar dados do formulário via AJAX
    $('#kanban-add-task').submit(function(e) {
        e.preventDefault();

        
        var formData = $(this).serialize(); // Isso irá capturar os dados do formulário

      
        var descricao = $(this).data('descricao');
        var prazo = $(this).data('prazo');
        var nomeTarefa = $(this).text().trim();
        var responsaveis = $(this).data('responsaveis');
       // Obtenha e processe as subtarefas


       var subtarefasBrutas = $(this).find('textarea[name="subtasks"]').val();
       var subtarefasArray = subtarefasBrutas.split('\n');
       var subtarefasFormatadas = subtarefasArray.map((subtarefa, index) => (index + 1) + subtarefa).join(', ');
      
   

        var taskData = {
            action: 'adicionar_tarefa_kanban',
            task_name: $(this).find('input[name="task_name"]').val(),
            description: $(this).find('textarea[name="description"]').val(),
            due_date: $(this).find('input[name="due_date"]').val(),
            subtasks: subtarefasFormatadas,
            responsibles: $(this).find('select[name="responsibles"]').val() 
        };

        


        

        $.ajax({
            type: "POST",
            url: kanban_ajax.ajax_url,
            data: taskData,
            success: function(response) {
                alert('Nova tarefa adicionada com sucesso!'); // Exibir mensagem de sucesso
                location.reload(); // Recarregar a página para exibir a nova tarefa
            },
            error: function() {
                console.error('Erro ao adicionar tarefa.');
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
    
   $(document).on('click', '#btn-add-subtask', function() {
    var entradaSubtarefas = $('input[name="new_subtask"]').val().trim();
    if (entradaSubtarefas) {
        var subtarefas = entradaSubtarefas.split(',');

        subtarefas.forEach(function(subtarefa) {
            var subtarefaTrimmed = subtarefa.trim();
            if (subtarefaTrimmed) {
                var novaSubtarefaHtml = '<div><input type="checkbox" name="subtasks[]">' +
                                        '<label>' + subtarefaTrimmed + '</label></div>';
                $('#form-editar-tarefa div').first().append(novaSubtarefaHtml);
            }
        });

        $('input[name="new_subtask"]').val('');
    }
});

    
    
    
$(document).on('click', '.task', function() {
    var taskId = $(this).attr('id').split('-')[1];
    var descricao = $(this).data('descricao');
    var prazo = $(this).data('prazo');
    var subtarefas = $(this).find('.subtarefas-data').val().split('; ');
    console.log('Texto da tarefa:', $(this).text());
    var nomeTarefa = $(this).text().split('-')[0].trim();
    
    var subtarefasHtml = '';
    
    // Iterar sobre as subtarefas para criar o HTML
subtarefas.forEach(function(subtarefa) {
    if (subtarefa && subtarefa.trim() !== '')  {
        subtarefasHtml += '<div class="subtarefa-item">' +
                          '<input type="checkbox" class="subtarefa-checkbox">' +
                          '<span class="subtarefa-nome">' + subtarefa.trim() + '</span>' +
                          '</div>';
    }
});

    

    var responsaveis = $(this).data('responsaveis');
    console.log('Subtarefas:', subtarefasHtml);
    console.log('Responsáveis:', responsaveis);

    var formHtml = '<form id="form-editar-tarefa">' +
                   '<input type="hidden" name="task_id" value="' + taskId + '">' +
                   '<label for="task_name">Nome da Tarefa</label>' +
                   '<input type="text" name="task_name" value="' + nomeTarefa + '">' +
                   '<label for="description">Descrição</label>' +
                   '<textarea name="description">' + descricao + '</textarea>' +
                   '<label for="due_date">Prazo</label>' +
                   '<input type="date" name="due_date" value="' + prazo + '">' +
                   '<label for="subtasks">Subtarefas</label>' +
                   '<div class="subtarefas-container">' + subtarefasHtml + '</div>' +
                   '<label for="responsibles">Responsáveis</label>' +
                   '<input type="text" name="responsibles" value="' + responsaveis + '">' +
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

         // Capturar os dados das subtarefas
    var subtarefasData = [];
    $('.subtarefa-item').each(function() {
        var nomeSubtarefa = $(this).find('.subtarefa-nome').text();
        var statusSubtarefa = $(this).find('.subtarefa-checkbox').is(':checked') ? 'concluída' : 'pendente';

        subtarefasData.push({ nome: nomeSubtarefa, status: statusSubtarefa });
    });

        var taskData = {
            action: 'editar_tarefa_kanban',
            task_id: $(this).find('input[name="task_id"]').val(),
            task_name: $(this).find('input[name="task_name"]').val(),
            description: $(this).find('textarea[name="description"]').val(),
            due_date: $(this).find('input[name="due_date"]').val(),
            subtasks: subtarefasData,
            responsibles: $(this).find('input[name="responsibles"]').val()
        };
        console.log('Dados do formulário:', taskData);

        
        
      
        $.ajax({
            type: "POST",
            url: kanban_ajax.ajax_url,
            data: taskData,
            success: function(response) {
                console.log('Resposta do servidor (sucesso):', response);
                var taskId = 'task-' + taskData.task_id;
                var updatedTask = $('#' + taskId);
                updatedTask.text(taskData.task_name);
                updatedTask.data('descricao', taskData.description);
                updatedTask.data('prazo', taskData.due_date);
                updatedTask.data('subtarefas', taskData.subtasks);
                updatedTask.data('responsaveis', taskData.responsibles);
                $('#popup-info').hide();
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição AJAX:', status, error);
            }
                
            }
            
        );
        
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



$(document).on('change', '.subtarefa-checkbox', function() {
    var isChecked = $(this).is(':checked');
    var descricaoSubtarefa = $(this).siblings('.subtarefa-nome').text();
    var idTarefa = $(this).closest('.task').data('task-id'); // Supondo que cada tarefa tenha um ID único

    // Alterar a visualização para riscado, se necessário
    $(this).siblings('.subtarefa-nome').css('text-decoration', isChecked ? 'line-through' : 'none');

    // Preparar dados para enviar
    var data = {
        action: 'atualizar_status_subtarefa',
        id_tarefa: idTarefa,
        descricao: descricaoSubtarefa, // Mudança aqui para 'descricao'
        status: isChecked ? 'concluído' : 'pendente'
    };

    // Enviar para o servidor via AJAX
    $.ajax({
        type: 'POST',
        url: kanban_ajax.ajax_url, // Substitua pela URL correta
        data: data,
        success: function(response) {
            console.log('Subtarefa atualizada com sucesso:', response);
        },
        error: function(error) {
            console.error('Erro ao atualizar a subtarefa:', error);
        }
    });
});

jQuery(document).ready(function($) {
    $('#mostrar-form-tarefa').click(function() {
        $('#kanban-add-task').toggle(); // Alterna a visibilidade do formulário
    });
});
