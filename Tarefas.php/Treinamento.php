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

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nome_tarefa text NOT NULL,
        descricao text,
        prazo date,
        status varchar(55) DEFAULT 'todo' NOT NULL,
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
    
        $nome_tarefa = $_POST['task_name'];
        $descricao = $_POST['description'];
        $prazo = $_POST['due_date']; // Supondo que seja fornecido em formato apropriado
        $table_name = $wpdb->prefix . 'kanban_tarefas';
    
        $wpdb->insert($table_name, array('nome_tarefa' => $nome_tarefa, 'descricao' => $descricao, 'prazo' => $prazo));
    
        echo $wpdb->insert_id; // Retorna o ID da nova tarefa para uso no front-end
        wp_die();
    }
    add_action('wp_ajax_adicionar_tarefa_kanban', 'adicionar_tarefa_kanban');
    
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
    
        $html = '<div id="kanban-board">
                    <div class="kanban-column" ondrop="drop(event)" ondragover="allowDrop(event)" data-status="todo">
                        <h3>Para Fazer</h3>
                        <div class="kanban-tasks" data-status="todo"></div>
                    </div>
                    <div class="kanban-column" ondrop="drop(event)" ondragover="allowDrop(event)" data-status="doing">
                        <h3>Em Andamento</h3>
                        <div class="kanban-tasks" data-status="doing"></div>
                    </div>
                    <div class="kanban-column" ondrop="drop(event)" ondragover="allowDrop(event)" data-status="done">
                        <h3>Concluído</h3>
                        <div class="kanban-tasks" data-status="done"></div>
                    </div>
                 </div>';
    
        foreach ($tarefas as $tarefa) {
            // Inclua a descrição e o prazo na tarefa
            $html .= '<div id="task-' . $tarefa->id . '" class="task" draggable="true" ondragstart="drag(event)" data-descricao="' . esc_attr($tarefa->descricao) . '" data-prazo="' . esc_attr($tarefa->prazo) . '" data-status="' . esc_attr($tarefa->status) . '">' . esc_html($tarefa->nome_tarefa) . '</div>';
        }
    
        // Popup para exibir informações da tarefa
        $html .= '<div id="popup-info" style="display:none;"></div>';
    
        return $html;
    }
    add_shortcode('quadro_kanban', 'mostrar_quadro_kanban');

    function editar_tarefa_kanban() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kanban_tarefas';
    
        $id_tarefa = $_POST['task_id'];
        $nome_tarefa = $_POST['task_name'];
        $descricao = $_POST['description'];
        $prazo = $_POST['due_date'];
    
        $wpdb->update($table_name, array('nome_tarefa' => $nome_tarefa, 'descricao' => $descricao, 'prazo' => $prazo), array('id' => $id_tarefa));
    
        echo 'Sucesso';
        wp_die();
    }
    add_action('wp_ajax_editar_tarefa_kanban', 'editar_tarefa_kanban');
    
    