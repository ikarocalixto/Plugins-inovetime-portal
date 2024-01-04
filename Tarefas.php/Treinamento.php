<?php
/**
 * Plugin Name: Meu Quadro Kanban
 * Description: Um plugin para criar um quadro Kanban interativo.
 * Version: 1.0
 * Author: Seu Nome
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
    $descricao = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';
    $prazo = isset($_POST['due_date']) ? sanitize_text_field($_POST['due_date']) : ''; // Certifique-se de que este seja um formato de data válido

    $table_name = $wpdb->prefix . 'kanban_tarefas';

    // Insira a nova tarefa com o ID do usuário
    $wpdb->insert($table_name, array(
        'nome_tarefa' => $nome_tarefa,
        'descricao' => $descricao,
        'prazo' => $prazo,
        'user_id' => $user_id // Adiciona o ID do usuário
    ));

    // Retorna o ID da nova tarefa para uso no front-end
    echo $wpdb->insert_id;
    wp_die();
}

    add_action('wp_ajax_adicionar_tarefa_kanban', 'adicionar_tarefa_kanban');
    add_action('wp_ajax_nopriv_adicionar_tarefa_kanban', 'adicionar_tarefa_kanban');
    
    function adicionar_tarefa_kanban_form() {
        $html = '<form id="kanban-add-task">
                    <input type="text" name="task_name" placeholder="Nome da Tarefa" required>
                    <textarea name="description" placeholder="Descrição da Tarefa"></textarea>
                    <input type="date" name="due_date" placeholder="Prazo">
                    <input type="submit" value="Adicionar Tarefa">
                 </form>';
        return $html;
    }
    add_shortcode('adicionar_tarefa', 'adicionar_tarefa_kanban_form');
    

    function mostrar_quadro_kanban() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kanban_tarefas';
        $tarefas = $wpdb->get_results("SELECT * FROM $table_name");
        $user_id = get_current_user_id(); // Pega o ID do usuário atual

        // Busca apenas as tarefas que pertencem ao usuário atual
        $tarefas = $wpdb->get_results("SELECT * FROM $table_name WHERE user_id = $user_id");
    
    
        $html = '<div id="kanban-board">';
    
        // Coluna 'Para Fazer'
        $html .= '<div class="kanban-column" ondrop="window.drop(event)" ondragover="window.allowDrop(event)" data-status="todo">
                    <h3>Para Fazer</h3>
                    <div class="kanban-tasks" data-status="todo">';
        foreach ($tarefas as $tarefa) {
            if ($tarefa->status == 'todo') {
                $html .= '<div id="task-' . $tarefa->id . '" class="task" draggable="true" ondragstart="window.drag(event)" data-descricao="' . esc_attr($tarefa->descricao) . '" data-prazo="' . esc_attr($tarefa->prazo) . '">' . esc_html($tarefa->nome_tarefa) . '</div>';
            }
        }
        $html .= '</div></div>';
    
        // Coluna 'Em Andamento'
        $html .= '<div class="kanban-column" ondrop="window.drop(event)" ondragover="window.allowDrop(event)" data-status="doing">
                    <h3>Em Andamento</h3>
                    <div class="kanban-tasks" data-status="doing">';
        foreach ($tarefas as $tarefa) {
            if ($tarefa->status == 'doing') {
                $html .= '<div id="task-' . $tarefa->id . '" class="task" draggable="true" ondragstart="window.drag(event)" data-descricao="' . esc_attr($tarefa->descricao) . '" data-prazo="' . esc_attr($tarefa->prazo) . '">' . esc_html($tarefa->nome_tarefa) . '</div>';
            }
        }
        $html .= '</div></div>';
    
        // Coluna 'Concluído'
        $html .= '<div class="kanban-column" ondrop="window.drop(event)" ondragover="window.allowDrop(event)" data-status="done">
                    <h3>Concluído</h3>
                    <div class="kanban-tasks" data-status="done">';
        foreach ($tarefas as $tarefa) {
            if ($tarefa->status == 'done') {
                $html .= '<div id="task-' . $tarefa->id . '" class="task" draggable="true" ondragstart="window.drag(event)" data-descricao="' . esc_attr($tarefa->descricao) . '" data-prazo="' . esc_attr($tarefa->prazo) . '">' . esc_html($tarefa->nome_tarefa) . '</div>';
            }
        }
        $html .= '</div></div>';
    
        $html .= '</div>'; // Fecha o #kanban-board
    
        // Popup para exibir informações da tarefa
        $html .= '<div id="popup-info" style="display:none;"></div>';
    
        return $html;
    }
    
    add_shortcode('quadro_kanban', 'mostrar_quadro_kanban');

    function editar_tarefa_kanban() {
        global $wpdb;
    
        // Verifique o nonce aqui (nonce deve ser enviado na solicitação AJAX)
    
        $table_name = $wpdb->prefix . 'kanban_tarefas';
    
        $id_tarefa = sanitize_text_field($_POST['task_id']);
        $nome_tarefa = sanitize_text_field($_POST['task_name']);
        $descricao = sanitize_text_field($_POST['description']);
        $prazo = sanitize_text_field($_POST['due_date']); // Valide o formato da data, se necessário
    
        // Atualizar a tarefa no banco de dados
        $resultado = $wpdb->update(
            $table_name, 
            array('nome_tarefa' => $nome_tarefa, 'descricao' => $descricao, 'prazo' => $prazo), 
            array('id' => $id_tarefa)
        );
    
        // Verifique se a atualização foi bem-sucedida
        if ($resultado !== false) {
            wp_send_json(array('message' => 'Sucesso'));
        } else {
            wp_send_json_error(array('message' => 'Erro ao atualizar a tarefa'));
        }
    
        wp_die();
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
    
        $wpdb->insert($table_name, array(
            'nome_tarefa' => $nome_tarefa,
            'descricao' => $descricao,
            'prazo' => $prazo,
            'user_id' => $user_id // Adicione o ID do usuário
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
    



// Função para adicionar tarefas automaticamente para um usuário específico
function iniciar_treinamento_trainee($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kanban_tarefas';

    // Defina as tarefas padrão para o treinamento
    $tarefas_treinamento = [
        ['nome_tarefa' => 'Tarefa teste', 'descricao' => 'Descrição da Tarefa 1', 'prazo' => '2024-02-01'],
        // Adicione mais tarefas conforme necessário
    ];

    error_log("Iniciando treinamento para o usuário ID: " . $user_id);

    // Após cada inserção de tarefa, adicione uma declaração de depuração
    $wpdb->insert($table_name, array(
        'nome_tarefa' => $tarefa['nome_tarefa'],
        'descricao' => $tarefa['descricao'],
        'prazo' => $tarefa['prazo'],
        'user_id' => $user_id
    ));
    error_log("Tarefa inserida com sucesso para o usuário ID: " . $user_id);
    
}

function ajax_iniciar_treinamento_trainee() {
    global $wpdb;

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $table_name = $wpdb->prefix . 'kanban_tarefas';

    // Define a tarefa de treinamento
    $tarefa_treinamento = array(
        'nome_tarefa' => 'Definição do Nome da Sua Loja Franqueada',
        'descricao' => 'Descreva o processo de escolha do nome da sua franquia.',
        'prazo' => date('Y-m-d', strtotime('+1 week')), // Define o prazo para uma semana a partir de hoje
        'user_id' => $user_id
    );

    // Insere a tarefa no banco de dados
    $wpdb->insert($table_name, $tarefa_treinamento);

    wp_send_json_success(['message' => 'Treinamento iniciado. Primeira tarefa adicionada.']);
}

add_action('wp_ajax_iniciar_treinamento_trainee', 'ajax_iniciar_treinamento_trainee');
add_action('wp_ajax_nopriv_iniciar_treinamento_trainee', 'ajax_iniciar_treinamento_trainee'); // se necessário


    
    
