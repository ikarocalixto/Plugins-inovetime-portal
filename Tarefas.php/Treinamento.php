<?php
/**
 * Plugin Name: Meu Quadro Kanban
 * Description: Um plugin para criar um quadro Kanban interativo.
 * Version: 3.0
 * Author: IKARO CALIXTO- INOVETIME
 */

 function meu_kanban_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kanban_tarefas';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "ALTER TABLE $table_name ADD COLUMN user_id bigint(20);";


    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nome_tarefa text NOT NULL,
        descricao text,
        prazo date,
        status varchar(55) DEFAULT 'todo' NOT NULL,
        user_id bigint(20), // Adiciona a coluna user_id
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'meu_kanban_activate');




function kanban_enqueue_scripts() {
    wp_enqueue_style('kanban-css', plugins_url('front-end.css', __FILE__));
    wp_enqueue_script('kanban-js', plugins_url('front-end.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('kanban-js', 'kanban_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'kanban_enqueue_scripts');

   
    
function adicionar_tarefa_kanban() {
    global $wpdb;
 
    // Obtenha o ID do usuário atual
    $user_id = get_current_user_id();
    
    



    // Valide e limpe os dados de entrada
    $nome_tarefa = isset($_POST['task_name']) ? sanitize_text_field($_POST['task_name']) : '';
    $descricao = isset($_POST['description']) ? wp_kses_post($_POST['description']) : ''; // Alterado para permitir HTML seguro
    $prazo = isset($_POST['due_date']) ? sanitize_text_field($_POST['due_date']) : '';
    $subtarefas = isset($_POST['subtasks']) ? sanitize_text_field($_POST['subtasks']) : '';
    $responsaveis = isset($_POST['responsibles']) ? sanitize_text_field($_POST['responsibles']) : '';
    $table_name = $wpdb->prefix . 'kanban_tarefas';

    // Insira a nova tarefa com o ID do usuário
    $wpdb->insert($table_name, array(
        'nome_tarefa' => $nome_tarefa,
        'descricao' => $descricao,
        'prazo' => $prazo,
        'user_id' => $user_id,
        'responsaveis' => $responsaveis
    ));

    if ($wpdb->last_error) {
        error_log('Erro ao inserir tarefa principal: ' . $wpdb->last_error);
        return; // Encerra a função se houver um erro
    }

    $id_tarefa = $wpdb->insert_id;

    // Processar e salvar as subtarefas
    if (!empty($subtarefas)) {
        $lista_subtarefas = explode(',', $subtarefas);

        foreach ($lista_subtarefas as $descricao_subtarefa) {
            $descricao_subtarefa = trim($descricao_subtarefa);
            if (!empty($descricao_subtarefa)) {
                $wpdb->insert('kanban_subtarefas', array(
                    'id_tarefa' => $id_tarefa,
                    'descricao' => $descricao_subtarefa,
                    'status' => 'pendente'
                ));

                if ($wpdb->last_error) {
                    error_log('Erro ao inserir subtarefa: ' . $wpdb->last_error);
                }
            }
        }
    }

     // Enviar notificações aos responsáveis
     if (!empty($responsaveis)) {
        $ids_responsaveis = explode(',', $responsaveis);
        foreach ($ids_responsaveis as $id_responsavel) {
            $id_responsavel = trim($id_responsavel);
            if (is_numeric($id_responsavel)) {
                $wpdb->insert(
                    "{$wpdb->prefix}meu_plugin_notificacoes",
                    array(
                        'user_id' => $id_responsavel,
                        'mensagem' => 'Você tem uma nova tarefa atribuída.',
                        'imagem' => '',
                        'url_redirecionamento' => '#', // Link para a tarefa
                        'data_envio' => current_time('mysql'),
                        'lida' => 0
                    ),
                    array('%d', '%s', '%s', '%s', '%s', '%d')
                );
            }
        }
    }

    echo $id_tarefa;
    wp_die();
}


    add_action('wp_ajax_adicionar_tarefa_kanban', 'adicionar_tarefa_kanban');
    add_action('wp_ajax_nopriv_adicionar_tarefa_kanban', 'adicionar_tarefa_kanban');
    
    function adicionar_tarefa_kanban_form() {
        // Recuperar todos os usuários
        $users = get_users(array('fields' => array('ID', 'display_name')));
    
        // Botão para mostrar o formulário
        $html = '<button id="mostrar-form-tarefa" class="mostrar-form-button">Adicionar Tarefa</button>';
    
        // Início do formulário - note o uso de '.=' para adicionar ao conteúdo existente
        $html .= '<form id="kanban-add-task" class="kanban-form" style="display:none;">
                    <div class="form-group">
                        <label for="task_name">Nome da Tarefa:</label>
                        <input type="text" name="task_name" placeholder="Nome da Tarefa" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Descrição da Tarefa:</label>
                        <textarea name="description" placeholder="Descrição da Tarefa"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="due_date">Prazo:</label>
                        <input type="date" name="due_date" placeholder="Prazo">
                    </div>
                    <div class="form-group">
                        <label for="subtasks">Subtarefas:</label>
                        <textarea name="subtasks" placeholder="Adicione suas subtarefas aqui"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="responsibles">Responsáveis:</label>
                        <select name="responsibles">';
    
        // Adicionar cada usuário como uma opção no dropdown
        foreach ($users as $user) {
            $html .= '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
        }
    
        // Fechar select e continuar o formulário
        $html .= '</select>
                    </div>
                    <div class="form-group">
                        <input type="submit" value="Adicionar Tarefa" class="green-button">
                    </div>
                 </form>';
    
        return $html;
    }
    
    add_shortcode('adicionar_tarefa', 'adicionar_tarefa_kanban_form');
    
    
    function calcular_prazo($data_prazo) {
        global $wpdb;
        $data_atual = new DateTime();
        $prazo = new DateTime($data_prazo);
    
        $table_name = $wpdb->prefix . 'kanban_tarefas';
        $tarefas = $wpdb->get_results("SELECT * FROM $table_name WHERE status != 'done'");
    
        foreach ($tarefas as $tarefa) {
            $data_prazo_tarefa = new DateTime($tarefa->prazo);
            $intervalo_tarefa = $data_atual->diff($data_prazo_tarefa);
    
            // Verifica se a tarefa está prestes a vencer e a notificação ainda não foi enviada
            if ($intervalo_tarefa->days == 1 && $data_atual < $data_prazo_tarefa && !$tarefa->notificacao_prazo_proximo_enviada) {
                $mensagem = "A tarefa '{$tarefa->nome_tarefa}' está prestes a vencer.";
                enviar_notificacao($wpdb, $tarefa->user_id, $mensagem, $tarefa->id, 'prazo_proximo');
            }
    
            // Verifica se a tarefa está atrasada e a notificação ainda não foi enviada
            if ($intervalo_tarefa->days == 1 && $data_atual > $data_prazo_tarefa && !$tarefa->notificacao_prazo_ultrapassado_enviada) {
                $mensagem = "A tarefa '{$tarefa->nome_tarefa}' está atrasada.";
                enviar_notificacao($wpdb, $tarefa->user_id, $mensagem, $tarefa->id, 'prazo_ultrapassado');
            }
        }
    
        update_option('ultima_execucao_calcular_prazo', $data_atual->format('Y-m-d H:i:s'));
    
        // Retorna mensagem baseada no status do prazo
        return mensagem_status_prazo($data_atual->diff($prazo), $data_atual, $prazo);
    }
    
    
    function gerar_mensagem_prazo($intervalo, $nome_tarefa, $data_atual, $data_prazo) {
        if ($intervalo->days == 1) {
            if ($data_atual < $data_prazo) {
                return "A tarefa '{$nome_tarefa}' está prestes a vencer. Verifique o prazo!";
            } else {
                return "A tarefa '{$nome_tarefa}' está atrasada. Atualize o status!";
            }
        }
        return '';
    }


    function enviar_notificacao($wpdb, $user_id, $mensagem, $id_tarefa, $tipo_notificacao) {
        // Nome da tabela de tarefas
        $table_name_tarefas = $wpdb->prefix . 'kanban_tarefas';
    
        // Determinar o nome da coluna com base no tipo de notificação
        $coluna_notificacao = $tipo_notificacao == 'prazo_proximo' ? 'notificacao_prazo_proximo_enviada' : 'notificacao_prazo_ultrapassado_enviada';
    
        // Verificar se a notificação já foi enviada
        $notificacao_enviada = $wpdb->get_var($wpdb->prepare(
            "SELECT $coluna_notificacao FROM $table_name_tarefas WHERE id = %d",
            $id_tarefa
        ));
    
        // Se a notificação ainda não foi enviada, enviar e atualizar o banco de dados
        if ($notificacao_enviada == 0) {
            $wpdb->insert(
                "{$wpdb->prefix}meu_plugin_notificacoes",
                array(
                    'user_id' => $user_id,
                    'mensagem' => $mensagem,
                    'imagem' => '',
                    'url_redirecionamento' => '#',
                    'data_envio' => current_time('mysql'),
                    'lida' => 0
                ),
                array('%d', '%s', '%s', '%s', '%s', '%d')
            );
    
            // Atualizar a flag na tabela de tarefas
            $wpdb->
    update(
    $table_name_tarefas,
    array($coluna_notificacao => 1),
    array('id' => $id_tarefa),
    array('%d'),
    array('%d')
    );
    }
    }
 
    function mensagem_status_prazo($intervalo, $data_atual, $prazo) {
        if ($data_atual > $prazo) {
            if ($intervalo
    ->m >= 1) {
    return 'Atraso de ' . $intervalo->m . ' mês(es)';
    } elseif ($intervalo->d >= 7) {
    return 'Atraso de ' . floor($intervalo->d / 7) . ' semana(s)';
    } elseif ($intervalo->d > 0) {
    return 'Atraso de ' . $intervalo->d . ' dia(s)';
    } else {
    return 'hoje';
    }
    } else {
    if ($intervalo->m >= 1) {
    return $intervalo->m . ' mês(es) restantes';
    } elseif ($intervalo->d >= 7) {
    return floor($intervalo->d / 7) . ' semana(s) restantes';
    } elseif ($intervalo->d > 0) {
    return $intervalo->d . ' dia(s) restantes';
    } else {
    return '1 dia restante';
    }
    }
    }
   
    
    function concluir_modulo_shortcode() {
        $user_id = get_current_user_id();
        return '<button id="concluir-modulo" data-user-id="' . esc_attr($user_id) . '">Próximas Tarefas</button>';
    }
    
    add_shortcode('concluir_modulo', 'concluir_modulo_shortcode');

    function buscar_franqueados() {
        $users = get_users();
        echo '<div class="form-busca-franqueados">';
        echo '<form action="" method="get">';
        echo '<select name="selected_user" onchange="this.form.submit()">';
        echo '<option value="">Selecione um Usuário</option>';
        foreach ($users as $user) {
            $selected = isset($_GET['selected_user']) && $_GET['selected_user'] == $user->ID ? 'selected' : '';
            echo '<option value="' . esc_attr($user->ID) . '" ' . $selected . '>' . esc_html($user->display_name) . '</option>';
        }
        echo '</select>';
        echo '<input type="submit" value="Ver Tarefas">';
        echo '</form>';
    
        if (isset($_GET['selected_user']) && !empty($_GET['selected_user'])) {
            $selected_user_id = intval($_GET['selected_user']);
            $user_info = get_userdata($selected_user_id);
            echo '<div class="user-info">';
            echo get_avatar($selected_user_id);
            echo '<span>' . esc_html($user_info->display_name) . '</span>';
            echo '</div>';
        }
    
        echo '</div>';
    }
    add_shortcode('buscar_franqueados', 'buscar_franqueados');
    
    


    function mostrar_quadro_kanban() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kanban_tarefas';
        
     // Nome da tabela sem o prefixo padrão do WordPress
        $table_name_subtarefas = 'kanban_subtarefas'; 
        $tarefas = $wpdb->get_results("SELECT * FROM $table_name");
        $user_id = get_current_user_id(); // Pega o ID do usuário atual


 // Data de 30 dias atrás como padrão para a coluna 'Concluído'
 $data_limite = date('Y-m-d', strtotime('-7 days'));

 // Verifica se um usuário foi selecionado no formulário
if (isset($_GET['selected_user']) && !empty($_GET['selected_user'])) {
    $user_id = intval($_GET['selected_user']);
}

 // Pega as datas do formulário, se fornecidas
 $data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : $data_limite;
 $data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

// Consulta SQL
$tarefas = $wpdb->get_results(
    "SELECT * FROM $table_name 
    WHERE (user_id = $user_id OR FIND_IN_SET('$user_id', responsaveis))
    AND (status != 'done' OR (status = 'done' AND data_criacao BETWEEN '$data_inicio' AND '$data_fim'))"
);


      
    
        $html = '<div id="kanban-board">';
    
   // Coluna 'Para Fazer'
$html .= '<div class="kanban-column kanban-todo" ondrop="window.drop(event)" ondragover="window.allowDrop(event)" data-status="todo">
<h3>Para Fazer</h3>
<div class="kanban-tasks" data-status="todo">';



foreach ($tarefas as $tarefa) {
       
    if ($tarefa->status == 'todo') {
        
      

         // Obter a URL do avatar do responsável e do dono
         $avatar_responsavel_url = get_avatar_url($tarefa->responsaveis);
         $avatar_dono_url = get_avatar_url($tarefa->user_id);
        // Buscar subtarefas relacionadas à tarefa atual
        $subtarefas = $wpdb->get_results("SELECT * FROM $table_name_subtarefas WHERE id_tarefa = " . intval($tarefa->id));
        $prazo_formatado = calcular_prazo($tarefa->prazo);

        // Iniciar a div da tarefa
        $html .= '<div id="task-' . $tarefa->id . '" class="task" draggable="true" ondragstart="window.drag(event)" 
        data-descricao="' . esc_attr($tarefa->descricao) . '" 
        data-prazo="' . esc_attr($tarefa->prazo) . '"
        subtarefa="'. esc_html($subtarefa->descricao) .'"
        data-responsaveis="' . esc_attr($tarefa->responsaveis) . '">

        <strong>' . esc_html($tarefa->nome_tarefa) . '</strong> - Prazo: ' . $prazo_formatado;
         // Adicionar as imagens dos avatares
         $html .= '<img src="' . esc_url($avatar_responsavel_url) . '" alt="Avatar do Responsável" class="avatar-responsavel">';
         $html .= '<img src="' . esc_url($avatar_dono_url) . '" alt="Avatar do Dono" class="avatar-dono">';

         $subtarefas = $wpdb->get_results("SELECT id_subtarefa, descricao, status FROM kanban_subtarefas WHERE id_tarefa = " . intval($tarefa->id));

         $subtarefasHtml = '';
         foreach ($subtarefas as $subtarefa) {
             // Verifica se a subtarefa está concluída
             $styleConcluido = $subtarefa->status === 'concluído' ? ' style="text-decoration: line-through;"' : '';
             $checked = $subtarefa->status === 'concluído' ? ' checked' : '';
         
             $subtarefasHtml .= '<div class="subtarefa-item" data-id="' . esc_attr($subtarefa->id_subtarefa) . '">' . // Aqui foi ajustado para usar o valor do ID
                                '<input type="checkbox" class="subtarefa-checkbox"' . $checked . '>' .
                                '<span class="subtarefa-nome"' . $styleConcluido . '>' . esc_html($subtarefa->descricao) . '</span>' .
                                '</div>';
         }

     

         // Campo oculto para armazenar as subtarefas
         $html .= '<input type="hidden" class="subtarefas-data" value="' . esc_attr($subtarefasHtml) . '">';
         
        

        // Fechar a div da tarefa
        $html .= '</div>';
    }
}



$html .= '</div></div>'; // Fecha a coluna 'Para Fazer'


    
        // Coluna 'Em Andamento'
        $html .= '<div class="kanban-column kanban-doing" ondrop="window.drop(event)" ondragover="window.allowDrop(event)" data-status="doing">
                    <h3>Em Andamento</h3>
                    <div class="kanban-tasks" data-status="doing">';
                    foreach ($tarefas as $tarefa) {
                        if ($tarefa->status == 'doing') {
                             // Obter a URL do avatar do responsável e do dono
         $avatar_responsavel_url = get_avatar_url($tarefa->responsaveis);
         $avatar_dono_url = get_avatar_url($tarefa->user_id);
                            // Buscar subtarefas relacionadas à tarefa atual
                            $subtarefas = $wpdb->get_results("SELECT * FROM $table_name_subtarefas WHERE id_tarefa = " . intval($tarefa->id));
                            $prazo_formatado = calcular_prazo($tarefa->prazo);
                    
                            // Iniciar a div da tarefa
                            $html .= '<div id="task-' . $tarefa->id . '" class="task" draggable="true" ondragstart="window.drag(event)" 
                         data-descricao="' . esc_attr($tarefa->descricao) . '" 
 
                            data-prazo="' . esc_attr($tarefa->prazo) . '"
                            subtarefa="'. esc_html($subtarefa->descricao) .'"
                            data-responsaveis="' . esc_attr($tarefa->responsaveis) . '">
                    
                            <strong>' . esc_html($tarefa->nome_tarefa) . '</strong> - Prazo: ' . $prazo_formatado;
                              // Adicionar as imagens dos avatares
         $html .= '<img src="' . esc_url($avatar_responsavel_url) . '" alt="Avatar do Responsável" class="avatar-responsavel">';
         $html .= '<img src="' . esc_url($avatar_dono_url) . '" alt="Avatar do Dono" class="avatar-dono">';
         $subtarefas = $wpdb->get_results("SELECT id_subtarefa, descricao, status FROM kanban_subtarefas WHERE id_tarefa = " . intval($tarefa->id));

$subtarefasHtml = '';
foreach ($subtarefas as $subtarefa) {
    // Verifica se a subtarefa está concluída
    $styleConcluido = $subtarefa->status === 'concluído' ? ' style="text-decoration: line-through;"' : '';
    $checked = $subtarefa->status === 'concluído' ? ' checked' : '';

    $subtarefasHtml .= '<div class="subtarefa-item" data-id="' . esc_attr($subtarefa->id_subtarefa) . '">' . // Aqui foi ajustado para usar o valor do ID
                       '<input type="checkbox" class="subtarefa-checkbox"' . $checked . '>' .
                       '<span class="subtarefa-nome"' . $styleConcluido . '>' . esc_html($subtarefa->descricao) . '</span>' .
                       '</div>';
}

// Campo oculto para armazenar as subtarefas
$html .= '<input type="hidden" class="subtarefas-data" value="' . esc_attr($subtarefasHtml) . '">';

     
                            // Fechar a div da tarefa
                            $html .= '</div>';
                        }
                    }
        $html .= '</div></div>';
    
        
        // Coluna 'Concluído'
        $html .= '<div class="kanban-column kanban-done" ondrop="window.drop(event)" ondragover="window.allowDrop(event)" data-status="done">
        <center>
       
        
        <!-- Botão para mostrar/ocultar o filtro -->
        <button id="toggle-filtro" onclick="toggleFiltro()">Mostrar Filtro</button>
        
        <!-- Seu formulário de filtro -->
        <form id="formulario-filtro" action="" method="get" style="display: none;">
            <label for="data_inicio">Data de Início:</label>
            <input type="date" id="data_inicio" name="data_inicio">
            
            <label for="data_fim">Data de Fim:</label>
            <input type="date" id="data_fim" name="data_fim">
            
            <input type="submit" value="Filtrar">
        </form>
        </center>


                    <h3>Concluído</h3>
                    <div class="kanban-tasks" data-status="done">';

                

                    foreach ($tarefas as $tarefa) {
                        if ($tarefa->status == 'done') {
// Pega as datas do formulário e converte para o formato do banco de dados
$data_inicio = isset($_GET['data_inicio']) && $_GET['data_inicio'] ? converterFormatoData($_GET['data_inicio']) : date('Y-m-d', strtotime('-30 days'));
$data_fim = isset($_GET['data_fim']) && $_GET['data_fim'] ? converterFormatoData($_GET['data_fim']) : date('Y-m-d');

// Se as datas são nulas (ou seja, a conversão falhou), usa um valor padrão
$data_inicio = $data_inicio ?: date('Y-m-d', strtotime('-30 days'));
$data_fim = $data_fim ?: date('Y-m-d');

// Ajusta a data final para incluir o final do dia
$data_fim = $data_fim . ' 23:59:59';

// Consulta para obter as tarefas concluídas no intervalo especificado
$tarefas = $wpdb->get_results(
    "SELECT * FROM $table_name 
    WHERE user_id = $user_id 
    AND status = 'done' 
    AND data_criacao BETWEEN '$data_inicio' AND '$data_fim'"
);



                             // Obter a URL do avatar do responsável e do dono
         $avatar_responsavel_url = get_avatar_url($tarefa->responsaveis);
         $avatar_dono_url = get_avatar_url($tarefa->user_id);
         $subtarefas = $wpdb->get_results("SELECT id_subtarefa, descricao, status FROM kanban_subtarefas WHERE id_tarefa = " . intval($tarefa->id));

                        
                    
                            // Iniciar a div da tarefa
                            $html .= '<div id="task-' . $tarefa->id . '" class="task" draggable="true" ondragstart="window.drag(event)" 
                            data-descricao="' . esc_attr($tarefa->descricao) . '" 
                            data-prazo="' . esc_attr($tarefa->prazo) . '"
                            subtarefa="'. esc_html($subtarefa->descricao) .'"
                            data-responsaveis="' . esc_attr($tarefa->responsaveis) . '">
                    
                            <strong>' . esc_html($tarefa->nome_tarefa) . '</strong> ' ;
                              // Adicionar as imagens dos avatares
         $html .= '<img src="' . esc_url($avatar_responsavel_url) . '" alt="Avatar do Responsável" class="avatar-responsavel">';
         $html .= '<img src="' . esc_url($avatar_dono_url) . '" alt="Avatar do Dono" class="avatar-dono">';
                    
         $subtarefas = $wpdb->get_results("SELECT id_subtarefa, descricao, status FROM kanban_subtarefas WHERE id_tarefa = " . intval($tarefa->id));

         $subtarefasHtml = '';
         foreach ($subtarefas as $subtarefa) {
             // Verifica se a subtarefa está concluída
             $styleConcluido = $subtarefa->status === 'concluído' ? ' style="text-decoration: line-through;"' : '';
             $checked = $subtarefa->status === 'concluído' ? ' checked' : '';
         
             $subtarefasHtml .= '<div class="subtarefa-item" data-id="' . esc_attr($subtarefa->id_subtarefa) . '">' . // Aqui foi ajustado para usar o valor do ID
                                '<input type="checkbox" class="subtarefa-checkbox"' . $checked . '>' .
                                '<span class="subtarefa-nome"' . $styleConcluido . '>' . esc_html($subtarefa->descricao) . '</span>' .
                                '</div>';
         }
         
         // Campo oculto para armazenar as subtarefas
         $html .= '<input type="hidden" class="subtarefas-data" value="' . esc_attr($subtarefasHtml) . '">';
         
     
                            // Fechar a div da tarefa
                            $html .= '</div>';
                        }
                    }
        $html .= '</div></div>';
    
    
        $html .= '</div>'; // Fecha o #kanban-board
    
        $html .= '
        <!-- Fundo escurecido para o popup -->
        <div id="popup-background" style="display:none;"></div>
        
    <div  id="popup-info" style="display:none;">
   
    <!-- Restante do conteúdo do popup -->
</div>

        <!-- Popup para exibir informações da tarefa -->
       
   
        
        
   
          
        </div>
           
        </div>
    ';
    
    
        return $html;
    }
    
    add_shortcode('quadro_kanban', 'mostrar_quadro_kanban');


// Função para converter a data do formato DD-MM-YYYY para YYYY-MM-DD
function converterFormatoData($data) {
    $dataFormatada = DateTime::createFromFormat('d-m-Y', $data);
    return $dataFormatada ? $dataFormatada->format('Y-m-d') : null;
}


    function editar_tarefa_kanban() {
        global $wpdb;
        

        
        // Registrar os dados recebidos
        error_log('Recebendo dados de edição da tarefa: ' . print_r($_POST, true));
        
        // Verifique o nonce aqui
        $table_name_subtarefas = 'kanban_subtarefas';

        $table_name = $wpdb->prefix . 'kanban_tarefas';
        
        // Aqui você captura os dados do POST
        $id_tarefa = sanitize_text_field($_POST['task_id']);
        $nome_tarefa = sanitize_text_field($_POST['task_name']);
        $descricao = isset($_POST['description']) ? wp_kses_post($_POST['description']) : ''; // Alterado para permitir HTML seguro
        $prazo = sanitize_text_field($_POST['due_date']);
        $descricao_subtarefas = sanitize_text_field($_POST['subtasks']);
        $responsaveis = sanitize_text_field($_POST['responsibles']);
        
        
        // Registrar os dados após a sanitização
        error_log('Dados da tarefa após a sanitização: ID: ' . $id_tarefa . ', Nome: ' . $nome_tarefa . ', Descrição: ' . $descricao . ', Prazo: ' . $prazo);
    
    
        
    
        
        
    
        // Atualizar a tarefa no banco de dados
        $resultado = $wpdb->update(
            $table_name, 
            array(
                'nome_tarefa' => $nome_tarefa, 
                'descricao' => $descricao, 
                'prazo' => $prazo, 
                           
                'responsaveis' => $responsaveis
            ), 
            array('id' => $id_tarefa)
        );
    
        // Verificar o resultado da atualização
        if ($resultado !== false) {
            error_log('Tarefa atualizada com sucesso. ID: ' . $id_tarefa);
    


            if ($resultado !== false) {
                wp_send_json(array('message' => 'Sucesso'));
            } else {
                wp_send_json_error(array('message' => 'Erro ao atualizar a tarefa'));
            }
        
            wp_die();
        }
    }
    
    
    
    add_action('wp_ajax_editar_tarefa_kanban', 'editar_tarefa_kanban');
    add_action('wp_ajax_nopriv_editar_tarefa_kanban', 'editar_tarefa_kanban');
    
    function mover_tarefa_kanban() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kanban_tarefas';
    
        $id_tarefa = sanitize_text_field($_POST['task_id']); // Sanitize para evitar SQL Injection
        $novo_status = sanitize_text_field($_POST['task_status']); // Sanitize para evitar SQL Injection
    
        $wpdb->update(
            $table_name,
            array('status' => $novo_status),
            array('id' => $id_tarefa),
            array('%s'),
            array('%d')
        );
    
        echo 'Sucesso';
        wp_die();
    }
    
    add_action('wp_ajax_mover_tarefa_kanban', 'mover_tarefa_kanban');
    add_action('wp_ajax_nopriv_mover_tarefa_kanban', 'mover_tarefa_kanban'); // Se desejar permitir acesso não autenticado
    
    function excluir_subtarefa_kanban() {
        global $wpdb;
    
        // Validação e sanitização
        $subtarefa_id = intval($_POST['subtarefa_id']);
    
        // Verifique se a subtarefa pertence ao usuário autenticado ou a outra lógica de verificação necessária
    
        // Aqui você deve adicionar a lógica real para excluir a subtarefa do banco de dados
        $table_name = $wpdb->prefix . 'kanban_tarefas';
        
        $wpdb->delete($table_name, array('id' => $subtarefa_id), array('%d'));
    
        // Verifique se a exclusão foi bem-sucedida
        if ($wpdb->last_error) {
            wp_send_json_error(array('message' => 'Erro ao excluir a subtarefa'));
        } else {
            wp_send_json_success(array('message' => 'Subtarefa excluída com sucesso'));
        }
        wp_die();
    }
    
    add_action('wp_ajax_excluir_subtarefa_kanban', 'excluir_subtarefa_kanban');
    add_action('wp_ajax_nopriv_excluir_subtarefa_kanban', 'excluir_subtarefa_kanban');
    


    function excluir_tarefa_kanban() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kanban_tarefas';
    
        // Verificações de segurança, como nonce, devem ser feitas aqui
    
        $id_tarefa = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
    
        if ($id_tarefa > 0) {
            $wpdb->delete($table_name, array('id' => $id_tarefa));
            wp_send_json_success(array('message' => 'Tarefa excluída com sucesso'));
        } else {
            wp_send_json_error(array('message' => 'ID de tarefa inválido'));
        }
    
        wp_die();
    }
    add_action('wp_ajax_excluir_tarefa_kanban', 'excluir_tarefa_kanban');
    add_action('wp_ajax_nopriv_excluir_tarefa_kanban', 'excluir_tarefa_kanban');
        

    function adicionar_tarefa_kanban_ajax() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kanban_tarefas';
    
        $nome_tarefa = sanitize_text_field($_POST['task_name']);
        $descricao = sanitize_text_field($_POST['description']);
        $prazo = sanitize_text_field($_POST['due_date']);
        $user_id = get_current_user_id(); // Obtenha o ID do usuário atual
        $subtarefas = sanitize_text_field($_POST['subtasks']);
        $responsaveis = sanitize_text_field($_POST['responsibles']);
    
    
        $wpdb->insert($table_name, array(
            'nome_tarefa' => $nome_tarefa,
            'descricao' => $descricao,
            'prazo' => $prazo,
            'user_id' => $user_id, // Adicione o ID do usuário
            'subtarefas' => $subtarefas,
                'responsaveis' => $responsaveis
        ));
    
        // Outros códigos, como enviar uma resposta AJAX
    }
    add_action('wp_ajax_adicionar_tarefa_kanban', 'adicionar_tarefa_kanban_ajax');
    add_action('wp_ajax_nopriv_adicionar_tarefa_kanban', 'adicionar_tarefa_kanban_ajax');
    



    function treinamento_trainee_shortcode() {
        global $wpdb;
        $mensagem = '';
        if (isset($_POST['iniciar_treinamento']) && !empty($_POST['user_id'])) {
            $user_id = intval($_POST['user_id']);
            $table_name = $wpdb->prefix . 'kanban_tarefas';
    
           
    
            // Mensagem de confirmação
            $mensagem = 'Treinamento iniciado para o usuário com ID ' . $user_id . '. Primeira tarefa adicionada.';
        }
    
        ob_start();
    
        // Formulário para escolher um usuário para treinamento
        $html = '<form id="treinamento-trainee-form" action="" method="post">
                    <select name="user_id" required>';
        
        $users = get_users();
        foreach ($users as $user) {
            $html .= '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
        }
    
        $html .= '</select>
                  <input type="submit" name="iniciar_treinamento" value="Iniciar Treinamento">
                 </form>';
    
        if (!empty($mensagem)) {
            $html .= '<div class="mensagem">' . esc_html($mensagem) . '</div>';
        }
    
        echo $html;
        return ob_get_clean();
    }
    add_shortcode('treinamento_trainee', 'treinamento_trainee_shortcode');


    function marketplace_shortcode() {
        global $wpdb;
        $mensagem = '';
        if (isset($_POST['marketplace']) && !empty($_POST['user_id'])) {
            $user_id = intval($_POST['user_id']);
            $table_name = $wpdb->prefix . 'kanban_tarefas';
    
           
    
            // Mensagem de confirmação
            $mensagem = 'Treinamento iniciado para o usuário com ID ' . $user_id . '. Primeira tarefa adicionada.';
        }
    
        ob_start();
    
        // Formulário para escolher um usuário para treinamento
        $html = '<form id="marketplace-form" action="" method="post">
                    <select name="user_id" required>';
        
        $users = get_users();
        foreach ($users as $user) {
            $html .= '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
        }
    
        $html .= '</select>
                  <input type="submit" name="marketplace" value="Começar no marketplace">
                 </form>';
    
        if (!empty($mensagem)) {
            $html .= '<div class="mensagem">' . esc_html($mensagem) . '</div>';
        }
    
        echo $html;
        return ob_get_clean();
    }
    add_shortcode('marketplace', 'marketplace_shortcode');
    


    function ajax_marketplace() {
        global $wpdb;
    
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $table_name = $wpdb->prefix . 'kanban_tarefas';
    
        $tarefas_treinamento = [
            // Módulo 1
            ['nome' => 'Introdução Marketplace', 'descricao' => '  <button class="link-button" data-href="https://inovetime.com.br/wp-content/uploads/Guia-do-marketplace.pdf">Guia do Marketplace</button>  Olá, franqueados! Hoje, vamos mergulhar no mundo dos marketplaces e explorar como iniciar seu negócio nesse ambiente. A primeira pergunta que surge é: "Por onde começar e qual marketplace escolher?". É crucial compreender que cada marketplace tem seu público específico. Por exemplo, a Shopee tende a atrair vendas de produtos com ticket médio mais baixo, então produtos mais caros podem não ser a melhor estratégia aqui. Por outro lado, Americanas, Mercado Livre e Amazon são plataformas onde produtos de ticket médio mais alto são vendidos em grande escala. O segredo é estudar bem cada marketplace antes de se aventurar nele. Aconselhamos sempre começar focando em um marketplace, e após consolidar sua presença nele, expandir para outros.

            ', 'modulo' => 55],
    
                ['nome' => 'Criar CNPJ ', 'descricao' => ' ... "', 'modulo' => 55],

                ['nome' => 'Certificado A1', 'descricao' => ' ... "', 'modulo' => 55],
                
            ['nome' => 'participar da promoção', 'descricao' => ' Aprenda como participar das promoções do marketplace <iframe width="853" height="480" src="https://www.youtube.com/embed/WnykTgirCzA" title="Participar da Promocoes Marketplace" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>..', 'modulo' => 55],
    
            ['nome' => 'Fazer avaliação do produto ', 'descricao' => '<iframe width="853" height="480" src="https://www.youtube.com/embed/SBz_Xq4F-I0" title="Avaliando o Produto Apresentacao com video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>', 'modulo' => 55],

            ['nome' => 'Fiz minha primeira venda, e agora? ', 'descricao' => ' <iframe width="853" height="480" src="https://www.youtube.com/embed/YwAXwxP9HhI" title="fiz minha primeira venda e agora" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>', 'modulo' => 55],
    
        ];

        // Insere apenas as 4 primeiras tarefas do Módulo 1 no banco de dados
    $contador = 0;
    foreach ($tarefas_treinamento as $tarefa) {
        if ($contador < 6 && $tarefa['modulo'] ==55) { // Garantindo que apenas as tarefas do Módulo 1 sejam adicionadas
            $wpdb->insert($table_name, array(
                'nome_tarefa' => $tarefa['nome'],
                'descricao' => $tarefa['descricao'],
                'prazo' => date('Y-m-d', strtotime('+1 week')),
                'responsaveis' => $responsaveis['responsaveis'],
                'user_id' => $user_id,
                'status' => 'todo',
                'modulo' => $tarefa['modulo'] // Adicionando a informação do módulo
            ));
            $contador++;
        } else {
            break;
        }
    }
    
    
        wp_send_json_success(['message' => 'Treinamento iniciado. Primeira tarefa adicionada.']);
    }
    
    add_action('wp_ajax_marketplace', 'ajax_marketplace');
    add_action('wp_ajax_nopriv_marketplace', 'ajax_marketplace');

    

function ajax_iniciar_treinamento_trainee() {
    global $wpdb;

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $table_name = $wpdb->prefix . 'kanban_tarefas';

    $tarefas_treinamento = [
        // Módulo 1
        ['nome' => 'Introdução - Modulo 1', 'descricao' => ' Bem-vindo ao início da sua jornada emocionante! Este vídeo de introdução fornece uma visão geral do que você pode esperar ao longo deste módulo. Ele aborda os objetivos principais, a estrutura do curso e como aproveitar ao máximo a experiência de
        aprendizado. Prepare-se para mergulhar no mundo fascinante do empreendedorismo e da gestão de lojas franqueadas.<iframe width="793" height="447" src="https://www.youtube.com/embed/_PCQD5mvexc" title="Introdução Treinamento trainee" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>', 'modulo' => 1],

            ['nome' => 'Definição do Nome da Sua Loja ', 'descricao' => ' <button class="link-button" data-href="https://franquia.ladygriffeoficial.com.br/linkdaloja">Defina o nome da sua Loja</button> O nome da sua loja é uma das coisas mais importantes que você pode fazer para o seu negócio. Ao escolher um nome para sua loja franqueada Lady Griffe, lembre-se dos seguintes pontos:
                Reflita sua marca: O nome da sua loja deve refletir os valores e a personalidade da sua marca. Deve ser algo que os clientes lembrem e que represente o que sua loja oferece.
                Atraia seus clientes-alvo: Pense no tipo de cliente que você deseja atrair. 
                Seja fácil de lembrar: O nome da sua loja deve ser fácil de lembrar e pronunciar. Isso facilitará para os clientes encontrarem sua loja e lembrarem dela. 
                clique no botão acima e nomeie sua loja com personalidade e criatividade!
                Nos envie suas ideias e receba um feedback de aprovação ou sugestões em até 72 horas. "', 'modulo' => 1],
            
        ['nome' => 'Registro do Domínio .br para a Sua Loja', 'descricao' => '  <button class="link-button" data-href="https://registro.br/">Registre seu domínio</button> Agora é hora de estabelecer sua presença online com um domínio .br. Escolha um domínio que corresponda ao nome da sua loja e compre ele. Siga as instruções para registrar seu domínio e dar o próximo passo importante em direção à construção da sua loja online.', 'modulo' => 1],

        ['nome' => 'Briefing: Arte e Estratégia - Compondo o Palco do Seu Negócio.', 'descricao' => '  <button class="link-button" data-href="https://inovetime.com.br/qsm_quiz/briefing-para-design-de-marca-branding/">Preencha o Briefing</button>  O briefing é um documento essencial para o sucesso de qualquer projeto. Ele fornece informações sobre o contexto, os objetivos e os requisitos do projeto, o que permite aos profissionais envolvidos terem uma visão clara do que deve ser feito.

        Por favor, preencha todas as questões do briefing com o máximo de detalhes possível. Se houver informações que você considere importantes, mas que não estejam listadas, adicione-as no final do documento.', 'modulo' => 1],
//
      
    
    ];

    // Insere apenas as 4 primeiras tarefas do Módulo 1 no banco de dados
$contador = 0;
foreach ($tarefas_treinamento as $tarefa) {
    if ($contador < 4 && $tarefa['modulo'] == 1) { // Garantindo que apenas as tarefas do Módulo 1 sejam adicionadas
        $wpdb->insert($table_name, array(
            'nome_tarefa' => $tarefa['nome'],
            'descricao' => $tarefa['descricao'],
            'prazo' => date('Y-m-d', strtotime('+1 week')),
            'responsaveis' => $responsaveis['responsaveis'],
            'user_id' => $user_id,
            'status' => 'todo',
            'modulo' => $tarefa['modulo'] // Adicionando a informação do módulo
        ));
        $contador++;
    } else {
        break;
    }
}


    wp_send_json_success(['message' => 'Treinamento iniciado. Primeira tarefa adicionada.']);
}

add_action('wp_ajax_iniciar_treinamento_trainee', 'ajax_iniciar_treinamento_trainee');
add_action('wp_ajax_nopriv_iniciar_treinamento_trainee', 'ajax_iniciar_treinamento_trainee'); // se necessário





function ajax_carregar_mais_tarefas() {
    global $wpdb;
    $user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'kanban_tarefas';

    $tarefas_treinamento = [
           // Módulo 1
           ['nome' => 'Introdução - Modulo 1', 'descricao' => ' Bem-vindo ao início da sua jornada emocionante! Este vídeo de introdução fornece uma visão geral do que você pode esperar ao longo deste módulo. Ele aborda os objetivos principais, a estrutura do curso e como aproveitar ao máximo a experiência de
           aprendizado. Prepare-se para mergulhar no mundo fascinante do empreendedorismo e da gestão de lojas franqueadas.<iframe width="793" height="447" src="https://www.youtube.com/embed/_PCQD5mvexc" title="Introdução Treinamento trainee" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>', 'modulo' => 1],
   
               ['nome' => 'Definição do Nome da Sua Loja Franqueada', 'descricao' => 'Uma parte crucial do seu sucesso começa aqui: escolher um nome cativante e memorável para a sua loja franqueada. Pense em um nome que reflita sua marca e atraia seus clientes-alvo. clique nesse link:<a href="https://franquia.ladygriffeoficial.com.br/linkdaloja" target="_blank">Escolha o Nome de sua Loja!</a> e nos envie suas ideias e prepare-se para trazer sua visão à vida! * Assim que enviar os nomes, no prazo maximo de até 72hrs enviamos um email confirmando os nome de sua loja. "', 'modulo' => 1],
               
           ['nome' => 'Registro do Domínio .br para a Sua Loja', 'descricao' => 'Agora é hora de estabelecer sua presença online com um domínio .br. Escolha um domínio que corresponda ao nome da sua loja e compre ele. Siga as instruções para registrar seu domínio e dar o próximo passo importante em direção à construção da sua loja online.', 'modulo' => 1],
   
           ['nome' => 'Briefing: Arte e Estratégia - Compondo o Palco do Seu Negócio.', 'descricao' => '<button class="link-button" data-href="https://inovetime.com.br/qsm/briefing-para-design-de-marca-branding/">Preencha o Briefing </button>  O briefing é um documento essencial para o sucesso de qualquer projeto. Ele fornece informações sobre o contexto, os objetivos e os requisitos do projeto, o que permite aos profissionais envolvidos terem uma visão clara do que deve ser feito.
   
           Por favor, preencha todas as questões do briefing com o máximo de detalhes possível. Se houver informações que você considere importantes, mas que não estejam listadas, adicione-as no final do documento.', 'modulo' => 1],
   
         
       
           // Módulo 2
        ['nome' => 'Introdução - Modulo 1.1 - Conhecendo seu Portal', 'descricao' => ' Explore o incrível Portal de Franqueados Lady Griffe! Neste vídeo, oferecemos um tour completo pelas funcionalidades que tornarão sua experiência como franqueado ainda mais bem-sucedida. Descubra como acessar suporte, gerenciar sua loja, aproveitar promoções exclusivas e muito mais. Este é o seu guia essencial para alcançar o sucesso na franquia Lady Griffe.  <iframe width="853" height="480" src="https://www.youtube.com/embed/htR6acwLbjQ" title="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>', 'modulo' => 2],
            ['nome' => 'Contrato: Entendendo a Parceria com a Lady Griffe','responsaveis'=>'27', 'descricao' => '  <button class="link-button" data-href="https://inovetime.com.br/wp-content/uploads/Contrato-de-Franquia-Empresarial-Lady-Griffe-2024.pdf">Ver o Contrato </button>     Em breve, iniciaremos o processo de formalização da nossa parceria através da assinatura do contrato. Para facilitar esse procedimento, adotamos um sistema totalmente digital. Você receberá um link por meio do qual poderá acessar o contrato. Pedimos que o revise cuidadosamente para se familiarizar com todos os termos e condições acordados.
    
            Após a revisão, é essencial que você prossiga com a assinatura do documento para concretizar a parceria. Para isso, solicitamos que abra um chamado conosco, por meio do qual enviaremos o contrato diretamente para o seu e-mail. Este e-mail incluirá instruções detalhadas sobre como proceder com a assinatura eletrônica, um processo rápido e seguro.
            
            Ressaltamos a importância desta etapa, pois a assinatura do contrato é um passo obrigatório e fundamental para a efetivação da nossa colaboração. Estamos à disposição para esclarecer quaisquer dúvidas ou oferecer assistência durante este processo, garantindo que ele ocorra de forma clara e eficiente.', 'modulo' => 2],
           
            ['nome' => 'Criação e Aprovação do Logo da Sua Marca', 'descricao' => ' Para adquirir um logotipo, acesse o link acima e efetue o pagamento. Se sua marca já possui um logotipo, abra um chamado e anexe o arquivo. A equipe analisará se a imagem atende aos requisitos mínimos para ser inserida no site e fornecerá um feedback. <button class="link-button" data-href="https://inovetime.com.br/produto/logotipo/">Adquira agora seu Logo </button> 
            <iframe width="848" height="480" src="https://www.youtube.com/embed/2vlHrsUHBlI" title="Criação do Logo" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>', 'modulo' => 2],
            ['nome' => 'Criar as redes sociais para Estabelecer uma Presença online', 'descricao' => '  <button class="link-button" data-href="https://inovetime.com.br/produto/criacao-das-redes-sociais/">Veja Como podemos te Ajudar!</button>   <iframe width="848" height="480" src="https://www.youtube.com/embed/34IC_WaghBo" title="Como Criar Conta no Instagram e Facebook" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe> Neste vídeo, vamos guiá-lo através do processo de criação de perfis profissionais no Instagram e no Facebook para a sua empresa. Ter presença nessas plataformas é essencial para expandir a sua marca, alcançar novos clientes e aumentar a visibilidade do seu negócio.', 'modulo' => 2],
            ['nome' => 'Configuração do WhatsApp Business para uma Comunicação Eficiente', 'descricao' => 'Você pode complementar sua presença online com o WhatsApp Business. Para isso, basta adquirir um novo chip de celular e não é necessário comprar outro celular. Após adquirir o chip, envie o nome do seu Instagram  e o seu novo numero, através de um chamado para nossa equipe. Iremos ajudá-lo a incorporar essas informações ao seu site para uma presença online completa', 'modulo' => 2],
            ['nome' => 'Configuração do Apontamento do Domínio para a Sua Loja Online', 'descricao' => '<button class="link-button" data-href="https://inovetime.com.br/wp-content/uploads/Cadastro-de-DNS-no-registro.br_.pdf.pdf">Saiba como Fazer o apontamento DNS </button>  Para começar, abra um chamado de suporte técnico solicitando a adição do seu domínio recém-adquirido ao sistema. Por favor, informe o nome do seu domínio. Após a confirmação da nossa equipe de suporte, você poderá prosseguir com o passo a passo indicado no botão acima. Estamos aqui para ajudá-lo a configurar seu domínio com facilidade e eficiência!', 'modulo' => 2],
            ['nome' => 'Solicitação de E-mail Corporativo ', 'descricao' => '  <button class="link-button" data-href="https://inovetime.com.br/suporte/">Abra Um Chamado </button>   Objetivo: Obter seu e-mail corporativo oficial, que será essencial para realizar as próximas tarefas e comunicações profissionais relacionadas à sua franquia.

            Descrição da Tarefa:
            Para iniciar formalmente suas atividades como franqueado e manter a comunicação profissional com clientes e a equipe de suporte, é essencial que você tenha um e-mail corporativo. Este e-mail não só fortalece sua identidade profissional, mas também é necessário para as próximas etapas do desenvolvimento da sua franquia.
            
            Abrir um Chamado: A primeira ação é abrir um chamado em nosso sistema. Isso pode ser feito acessando nossa plataforma de suporte e selecionando a opção correspondente para solicitar um e-mail corporativo.
            
            Fornecer Informações Necessárias: No chamado, você deverá fornecer informações básicas como seu nome completo, o nome da sua franquia e qualquer outra informação relevante solicitada pela equipe de suporte.
            
            Aguardar a Criação do E-mail: Após a abertura do chamado, nossa equipe técnica processará sua solicitação e criará seu e-mail corporativo. Este processo pode levar alguns dias, então pedimos paciência.
            
            Recebimento e Configuração: Uma vez que seu e-mail corporativo estiver pronto, você receberá as credenciais e instruções para configurá-lo em seu dispositivo.', 'modulo' => 2],
       
           // Módulo 3
           ['nome' => 'Introdução - Metodologia 2', 'descricao' => '  Parabéns, Franqueado, por ter alcançado esta etapa crucial! Neste módulo, vamos explorar aspectos fundamentais sobre a operação de sua loja e o dinamismo das vendas online. Estes conteúdos são essenciais para aprofundar seu entendimento sobre o marketing digital. Durante esta fase de desenvolvimento da sua loja, nosso objetivo é capacitá-lo com conhecimentos abrangentes, para que, quando sua loja estiver pronta, você esteja equipado para iniciar suas atividades com eficácia.
           <iframe width="848" height="480" src="https://www.youtube.com/embed/NjKTQ2SjpKA" title="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>', 'modulo' => 3],
           ['nome' => 'Conhecendo o painel de controle ', 'descricao' => '  
                 
           
           
          
           Este tour pelo painel de controle da sua loja é uma etapa crucial para que você se familiarize com todas as ferramentas e funcionalidades à sua disposição. Nosso objetivo é proporcionar uma visão clara e resumida do que você pode realizar através deste painel, assegurando que você tenha o conhecimento necessário para gerenciar sua loja com eficiência.
           
           O que você vai aprender:
           
           Visão Geral das Funcionalidades: Descubra as várias funcionalidades que o seu painel de controle oferece, desde a gestão de inventário até análises de vendas.
           
           Gerenciamento de Produtos: Entenda como adicionar, editar e organizar produtos, além de gerenciar o estoque com facilidade.
           
           Análises e Relatórios: Aprenda a acessar relatórios detalhados que podem ajudar na tomada de decisões estratégicas para o crescimento da sua loja.
           
           Marketing e Promoções: Explore ferramentas que lhe permitirão criar e gerenciar campanhas promocionais, cupons de desconto e estratégias de marketing por e-mail.
           
           Configurações de Loja: Familiarize-se com as configurações gerais da sua loja, incluindo personalizações, configurações de pagamento e opções de envio.
           
           Suporte ao Cliente: Saiba como gerenciar consultas de clientes, resolver problemas e manter um alto nível de satisfação do cliente.   <iframe width="853" height="480" src="https://www.youtube.com/embed/KAr6h3pQabs" title="Painel de Controle" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>', 'modulo' => 3],
           ['nome' => 'Faça as primeiras postagens ', 'descricao' => '  <button class="link-button" data-href="https://inovetime.com.br/postagens/">Fazer O Download De Posts </button>  Estamos nos aproximando de um momento empolgante – a inauguração de suas lojas! Para garantir um lançamento bem-sucedido, é crucial começar a criar expectativa e interesse em sua rede de contatos. Aqui está o que você pode fazer:

           Engajamento nas Redes Sociais: Comece a se comunicar com seu público nas redes sociais. Utilize as contas que você criou e comece a compartilhar conteúdo que ressoa com seu círculo de relacionamentos. Isso pode incluir amigos, familiares e conhecidos profissionais.
           
           Conteúdo Estratégico: Acesse o nosso portal para encontrar postagens prontas que você pode usar. Estes posts são projetados para gerar curiosidade e entusiasmo sobre o que está por vir.
           
           Personalize com a Nossa IA: Para tornar suas postagens ainda mais atraentes, utilize nossa ferramenta de inteligência artificial para ajudar na criação de descrições cativantes. Esta tecnologia pode ajudá-lo a criar conteúdo que se conecta de forma mais eficaz com o seu público.
           
           Foco na Construção de Expectativa: O objetivo dessas postagens é informar as pessoas sobre a iminente abertura da sua loja. Crie um senso de antecipação e deixe-os ansiosos para ver o que você tem a oferecer.
           
           Interaja com seu Público: Não se limite apenas a postar; interaja com quem comentar ou mostrar interesse. Construa relacionamentos e prepare o terreno para uma comunidade leal de clientes.
           
           Essa estratégia inicial é fundamental para estabelecer uma base sólida para o seu negócio. Vamos começar a criar uma onda de entusiasmo que culminará na grande inauguração da sua loja!', 'modulo' => 3],
           ['nome' => 'Alcançar os Primeiros 50 Seguidores nas Redes Sociais', 'descricao' => 'Objetivo: Este é o seu primeiro marco significativo no mundo digital – conseguir os primeiros 50 seguidores em sua conta de rede social. Este objetivo inicial é crucial para estabelecer a presença online da sua franquia e começar a construir uma comunidade engajada.

           Descrição da Tarefa:
           
           Crie Conteúdo Atraente: Compartilhe posts que reflitam a identidade da sua marca e que sejam relevantes para o seu público-alvo. Isso pode incluir informações sobre produtos, dicas úteis, histórias por trás da marca ou até mesmo teasers sobre a inauguração da loja.
           
           Engajamento Ativo: Siga contas relevantes, comente em posts de outros usuários e responda a todos os comentários em suas postagens. O engajamento é uma via de mão dupla – quanto mais você interage, mais visibilidade ganha.
           
           Use Hashtags Estrategicamente: Inclua hashtags relevantes para aumentar o alcance das suas publicações e atrair seguidores interessados no seu nicho de mercado.
           
           Promova sua Conta: Compartilhe o link da sua conta de rede social com amigos, familiares e contatos profissionais. Peça-lhes para seguir sua página e compartilhar com suas redes.
           
           Monitore Seu Progresso: Fique atento ao número de seguidores e ao engajamento das suas postagens. Use esses insights para ajustar sua estratégia conforme necessário.', 'modulo' => 3],
          


        // Módulo 4
        ['nome' => 'Conhecendo os Produtos', 'descricao' => ' <button class="link-button" data-href="https://inovetime.com.br/wp-content/uploads/New-Collection-Lady-2.pdf">Veja nossa Revista!</button>  Embarque em uma viagem de estilo e sofisticação com a Lady Griffe, onde a beleza e a elegância se encontram em cada produto. <iframe width="848" height="480" src="https://www.youtube.com/embed/G7IYiKl44JQ" title="Apresentação de Produtos" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>', 'modulo' => 4],
        ['nome' => 'Como funciona o dropshipping?', 'descricao' => ' <iframe width="848" height="480" src="https://www.youtube.com/embed/1WWhl2mpX3c" title="Como funciona o Drop lady Griffe" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe> <button class="link-button" data-href="https://inovetime.com.br/wp-content/uploads/2022/07/Manual-Dropshipping.pdf">Saiba mais</button> Dropshipping é um modelo de negócios de varejo onde a loja não mantém os produtos que vende em estoque. Em vez disso, quando uma loja vende um produto usando o modelo de dropshipping, ela compra o item de um terceiro e o envia diretamente ao cliente. Assim, o vendedor não precisa lidar diretamente com o produto.

        A principal vantagem do dropshipping é a redução dos custos operacionais, já que não é necessário investir em grandes estoques ou em um espaço de armazenamento. Isso torna o dropshipping uma opção atraente para empreendedores iniciantes ou para empresas que desejam expandir suas ofertas de produtos sem aumentar significativamente os custos.', 'modulo' => 4],
        ['nome' => 'Como funciona reserva de estoque?', 'descricao' => '  O vídeo apresenta o sistema de reserva de estoque da Lady Griffe, enfatizando como ele transforma as vendas. Inicia-se com uma introdução que destaca a inovação e parceria da marca. Em seguida, é explicado o conceito da reserva de estoque, que não é uma compra, mas uma garantia de disponibilidade dos produtos sem comprometer o capital de giro.

        A flexibilidade do sistema é ressaltada, mostrando a facilidade de troca de produtos que não atendem às expectativas de venda. O processo de reposição e manutenção do estoque é detalhado, explicando como as vendas geram lucro e a necessidade de gerir financeiramente a reposição do estoque.
        
        Além disso, o vídeo aborda como manter as reposições em dia melhora o score de crédito e pode aumentar o limite de crédito do franqueado. A integração do modelo de reserva de estoque com operações de marketplace e loja virtual também é discutida, destacando os benefícios de uma gestão operacional mais eficiente e lucratividade maximizada.  <iframe width="848" height="480" src="https://www.youtube.com/embed/KZm3BhWGKlY" title="Reserva de estoque oficial lady" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>', 'modulo' => 4],
        ['nome' => 'Como funciona a compra no atacado?', 'descricao' => ' Introdução: O vídeo começa apresentando a estratégia de compra no atacado da Lady Griffe, destacando como ela beneficia os franqueados.

        Compra no Atacado: Explica que comprar no atacado significa adquirir produtos a preços reduzidos diretamente dos fornecedores, com o processo facilitado pela equipe da Lady Griffe.
        
        Vantagens: As vantagens incluem entrega direta ao franqueado, permitindo estratégias de venda variadas como delivery ou venda de produtos a pronta entrega. Outro ponto positivo é a maior margem de lucro obtida com a compra no atacado.
        
        Critérios de Compra: Esclarece que existem critérios específicos para a compra no atacado, como a aquisição de uma quantidade mínima de um mesmo produto, variando de acordo com cada fornecedor.
        
        Reserva de Estoque: Aborda a reserva de estoque, onde o franqueado investe um valor sem se preocupar com logística, validade ou danos. Oferece a possibilidade de trocar produtos que não atendam às expectativas de vendas.
        
        Diferenças Chave: Destaca as diferenças entre comprar no atacado e reservar estoque. Na compra no atacado, o franqueado lida com a logística e não pode trocar produtos. Na reserva de estoque, a logística é gerida pela Lady Griffe, e há flexibilidade para trocar itens pouco vendidos. <iframe width="848" height="480" src="https://www.youtube.com/embed/D8_3IEBAaWw" title="Compras no Atacado" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>', 'modulo' => 4],
        // ... Adicione outras tarefas, se houver, seguindo o mesmo formato

  // Módulo 5
  ['nome' => 'Aprovação do Layout da Sua Loja: Garantindo uma Estética Atraente', 'descricao' => '...', 'modulo' => 5],
  ['nome' => 'Testando funcionalidade da loja: Pedido Teste', 'descricao' => '...', 'modulo' => 5],
  ['nome' => 'Saiba como cadastrar um produto', 'descricao' => ' Cadastre 15 Produtos para entender a dinâmica', 'modulo' => 5],
  ['nome' => 'Primeira Venda: Avaliando a Logística da Sua Loja com uma Venda Piloto', 'descricao' => '...', 'modulo' => 5],
  ['nome' => 'Melhor Envio', 'descricao' => '...', 'modulo' => 5],
  ['nome' => 'Termo de Aprovação da Entrega da Loja: Oficializando a Inauguração"', 'descricao' => '...', 'modulo' => 5],
];

  // Verifique se todas as tarefas do módulo atual estão concluídas
  $tarefas_nao_concluidas = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND status != 'done'",
    $user_id
));

if ($tarefas_nao_concluidas > 0) {
    wp_send_json_error(['message' => 'Não conseguimos prosseguir para o próximo módulo neste momento. Verificamos que ainda há tarefas pendentes no módulo atual que precisam ser concluídas. Por favor, complete todas as tarefas pendentes para continuar avançando no treinamento.']);
    return;
}

    // Descobrir qual módulo carregar
    $modulo_atual = $wpdb->get_var($wpdb->prepare(
        "SELECT modulo FROM $table_name WHERE user_id = %d AND status = 'todo' ORDER BY modulo ASC LIMIT 1",
        $user_id
    ));

    // Se não houver módulo em aberto, buscar o próximo módulo
    if ($modulo_atual === null) {
        $modulo_atual = $wpdb->get_var($wpdb->prepare(
            "SELECT modulo FROM $table_name WHERE user_id = %d ORDER BY modulo DESC LIMIT 1",
            $user_id
        )) + 1;
    }

    // Carregar tarefas do módulo atual
    $proximas_tarefas = array_filter($tarefas_treinamento, function($tarefa) use ($modulo_atual) {
        return $tarefa['modulo'] == $modulo_atual;
    });

    // Inserir tarefas do módulo especificado para o usuário
    foreach ($proximas_tarefas as $tarefa) {
        $wpdb->insert($table_name, array(
            'nome_tarefa' => $tarefa['nome'],
            'descricao' => $tarefa['descricao'],
            'responsaveis' => $tarefa['responsaveis'],
            'prazo' => date('Y-m-d', strtotime('+1 week')),
            'user_id' => $user_id,
            'status' => 'todo',
            'modulo' => $modulo_atual // Use $modulo_atual em vez de $modulo
        ));
    }

    // Enviar notificações aos responsáveis
    if (!empty($tarefa['responsaveis'])) {
        $ids_responsaveis = explode(',', $tarefa['responsaveis']);
        foreach ($ids_responsaveis as $id_responsavel) {
            $id_responsavel = trim($id_responsavel);
            if (is_numeric($id_responsavel)) {
                $wpdb->insert(
                    "{$wpdb->prefix}meu_plugin_notificacoes",
                    array(
                        'user_id' => $id_responsavel,
                        'mensagem' => 'Você tem uma nova tarefa atribuída.',
                        'imagem' => '',
                        'url_redirecionamento' => '#', // Link para a tarefa
                        'data_envio' => current_time('mysql'),
                        'lida' => 0
                    ),
                    array('%d', '%s', '%s', '%s', '%s', '%d')
                );
            }
        }
    }


    // Resposta AJAX
    wp_send_json_success(['message' => "Tarefas do Módulo $modulo_atual adicionadas com sucesso."]);
}

add_action('wp_ajax_carregar_mais_tarefas', 'ajax_carregar_mais_tarefas');
add_action('wp_ajax_nopriv_carregar_mais_tarefas', 'ajax_carregar_mais_tarefas');


function ajax_marcar_tarefa_concluida() {
    global $wpdb;
    $tarefa_id = isset($_POST['tarefa_id']) ? intval($_POST['tarefa_id']) : 0;
    $user_id = get_current_user_id(); // Certifique-se de obter o ID do usuário atual corretamente
    $table_name = $wpdb->prefix . 'kanban_tarefas';

    // Atualizar a tarefa para 'done'
    $wpdb->update(
        $table_name,
        array('status' => 'done'),
        array('id' => $tarefa_id)
    );

    // Verifique a conclusão do Módulo 1 e carregue o Módulo 2, se necessário
    verificar_conclusao_modulo_1($user_id, $table_name);

    wp_send_json_success();
}




function ajax_verificar_proximo_modulo($user_id, $table_name) {
    global $wpdb;

    // Verifique se todas as tarefas do módulo atual estão concluídas
    $modulo_atual = $wpdb->get_var($wpdb->prepare(
        "SELECT modulo FROM $table_name WHERE user_id = %d AND status = 'todo' ORDER BY modulo ASC LIMIT 1",
        $user_id
    ));

    if ($modulo_atual === null) {
        // Todas as tarefas do módulo atual foram concluídas, carregar o próximo módulo
        $proximo_modulo = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(modulo) FROM $table_name WHERE user_id = %d",
            $user_id
        )) + 1;

        ajax_carregar_mais_tarefas($user_id, $proximo_modulo, $table_name);
    }
}


add_action('wp_ajax_verificar_conclusao_modulo', 'verificar_conclusao_modulo_callback');
add_action('wp_ajax_nopriv_verificar_conclusao_modulo', 'verificar_conclusao_modulo_callback');




// Função para verificar se todas as tarefas do módulo estão concluídas
function verificar_conclusao_modulo($user_id, $table_name) {
    global $wpdb;

    // Obtenha o módulo atual do usuário
    $modulo_atual = $wpdb->get_var($wpdb->prepare(
        "SELECT MAX(modulo) FROM $table_name WHERE user_id = %d",
        $user_id
    ));

    if ($modulo_atual !== null) {
        // Verifique se todas as tarefas do módulo atual estão concluídas
        $tarefas_concluidas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND modulo = %d AND status = 'done'",
            $user_id,
            $modulo_atual
        ));

        $total_tarefas_modulo = count(obter_tarefas_modulo($modulo_atual, $table_name)); // Função para obter todas as tarefas do módulo

        if ($tarefas_concluidas == $total_tarefas_modulo) {
            // Todas as tarefas do módulo estão concluídas
            $proximo_modulo = $modulo_atual + 1;
            $mensagem = "Parabéns! Você concluiu o Módulo $modulo_atual. Vamos para o Módulo $proximo_modulo.";
            wp_send_json_success(['message' => $mensagem]);
        }
    }
}

function verificar_conclusao_modulo_1($user_id, $table_name) {
    global $wpdb;

    // Verifique se todas as tarefas do Módulo 1 estão concluídas
    $tarefas_concluidas_modulo_1 = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND modulo = 1 AND status = 'done'",
        $user_id
    ));

    $total_tarefas_modulo_1 = count(obter_tarefas_modulo(1, $table_name)); // Substitua obter_tarefas_modulo com a função correta

    if ($tarefas_concluidas_modulo_1 == $total_tarefas_modulo_1) {
        // Todas as tarefas do Módulo 1 estão concluídas, então carregue as tarefas do Módulo 2
        ajax_carregar_tarefas_modulo_2($user_id, $table_name);
    }
}

add_action('wp_ajax_verificar_conclusao_modulo_1', 'verificar_conclusao_modulo_1');
add_action('wp_ajax_nopriv_verificar_conclusao_modulo_1', 'verificar_conclusao_modulo_1');


function ajax_carregar_tarefas_modulo_2($user_id, $table_name) {
    global $wpdb;

    $tarefas_treinamento = [
       
        // Módulo 2
        ['nome' => 'Criação e Aprovação do Logo da Sua Marca', 'descricao' => '...', 'modulo' => 2],
        ['nome' => 'Criar as redes sociais para Estabelecer uma Presença online', 'descricao' => '...', 'modulo' => 2],
        ['nome' => 'Configuração do WhatsApp Business para uma Comunicação Eficiente', 'descricao' => '...', 'modulo' => 2],
        ['nome' => 'Definindo Sua Visão, Missão e Valores: A Base do Seu Negócio', 'descricao' => '...', 'modulo' => 2],
    
        
    ];

    // Insira as tarefas do Módulo 2 para o usuário
    foreach ($tarefas_modulo_2 as $tarefa) {
        $wpdb->insert($table_name, array(
            'nome_tarefa' => $tarefa['nome'],
            'descricao' => $tarefa['descricao'],
            'prazo' => date('Y-m-d', strtotime('+1 week')),
            'user_id' => $user_id,
            'status' => 'todo',
            'modulo' => 2 // Defina o número do módulo
        ));
    }

    wp_send_json_success(['message' => 'Tarefas do Módulo 2 adicionadas com sucesso.']);
}

function ajax_concluir_modulo_atual() {
    global $wpdb;
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $moduloAtual = isset($_POST['modulo']) ? intval($_POST['modulo']) : 1;

    $table_name = $wpdb->prefix . 'kanban_tarefas';

    // Contar o total de tarefas no módulo atual
    $total_tarefas = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND modulo = %d",
        $user_id, $moduloAtual
    ));

    // Contar as tarefas concluídas (status 'done') no módulo atual
    $tarefas_concluidas = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND modulo = %d AND status = 'done'",
        $user_id, $moduloAtual
    ));

    if ($total_tarefas == $tarefas_concluidas) {
        // Se todas as tarefas do módulo foram concluídas
        wp_send_json_success(['message' => 'Módulo ' . $moduloAtual . ' concluído. Carregando próximo módulo...']);
    } else {
        // Se ainda há tarefas pendentes
        wp_send_json_error(['message' => 'Algumas tarefas do Módulo ' . $moduloAtual . ' ainda não foram concluídas.']);
    }

    wp_die();
}

add_action('wp_ajax_concluir_modulo_atual', 'ajax_concluir_modulo_atual');
add_action('wp_ajax_nopriv_concluir_modulo_atual', 'ajax_concluir_modulo_atual'); // se necessário


// Adicione isso ao arquivo functions.php do seu tema ou a um plugin personalizado

function atualizar_status_subtarefa() {
    global $wpdb;

    $id_tarefa = isset($_POST['id_tarefa']) ? intval($_POST['id_tarefa']) : 0;
    $descricao = isset($_POST['descricao']) ? sanitize_text_field($_POST['descricao']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

    if (!$id_tarefa || !$descricao) {
        echo 'Dados insuficientes.';
        wp_die();
    }

    $table_name = 'kanban_subtarefas'; // Substitua pelo nome da sua tabela

    $result = $wpdb->update(
        $table_name,
        array('status' => $status),
        array(
            'id_tarefa' => $id_tarefa,
            'descricao' => $descricao
        ),
        array('%s'),
        array('%d', '%s')
    );

    if ($result !== false) {
        echo 'Subtarefa atualizada com sucesso.';
    } else {
        echo 'Erro ao atualizar a subtarefa.';
    }

    wp_die(); // Encerrar adequadamente a execução do AJAX
}

add_action('wp_ajax_atualizar_status_subtarefa', 'atualizar_status_subtarefa');
add_action('wp_ajax_nopriv_atualizar_status_subtarefa', 'atualizar_status_subtarefa');
// Adicione a ação 'wp_ajax_nopriv_' se necessário

function excluir_subtarefa() {
    global $wpdb;

    $id_subtarefa = isset($_POST['id_subtarefa']) ? intval($_POST['id_subtarefa']) : 0;

    if ($id_subtarefa) {
        $resultado = $wpdb->delete('kanban_subtarefas', array('id' => $id_subtarefa));

        if ($resultado !== false) {
            echo 'Subtarefa excluída com sucesso.';
        } else {
            echo 'Erro ao excluir a subtarefa.';
        }
    } else {
        echo 'ID da subtarefa inválido.';
    }

    wp_die();
}

add_action('wp_ajax_excluir_subtarefa', 'excluir_subtarefa');
add_action('wp_ajax_nopriv_excluir_subtarefa', 'excluir_subtarefa');
// Se necessário, adicione também a ação 'wp_ajax_nopriv_excluir_subtarefa'
