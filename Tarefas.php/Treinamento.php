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
    
