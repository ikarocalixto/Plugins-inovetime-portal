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

    echo $wpdb->insert_id;
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
        $data_atual = new DateTime();
        $prazo = new DateTime($data_prazo);
        $intervalo = $data_atual->diff($prazo);
    
        if ($data_atual > $prazo) {
            // Se a data atual for maior que a data do prazo, então há um atraso
            if ($intervalo->m >= 1) {
                return 'Atraso de ' . $intervalo->m . ' mês(es)';
            } elseif ($intervalo->d >= 7) {
                return 'Atraso de ' . floor($intervalo->d / 7) . ' semana(s)';
            } elseif ($intervalo->d > 0) {
                return 'Atraso de ' . $intervalo->d . ' dia(s)';
            } else {
                return 'Atraso de hoje';
            }
        } else {
            // Se a data do prazo ainda não passou
            if ($intervalo->m >= 1) {
                return $intervalo->m . ' mês(es)';
            } elseif ($intervalo->d >= 7) {
                return floor($intervalo->d / 7) . ' semana(s)';
            } elseif ($intervalo->d > 0) {
                return $intervalo->d . ' dia(s)';
            } else {
                return 'Hoje';
            }
        }
    }
    


    function mostrar_quadro_kanban() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kanban_tarefas';
        
     // Nome da tabela sem o prefixo padrão do WordPress
        $table_name_subtarefas = 'kanban_subtarefas'; 
        $tarefas = $wpdb->get_results("SELECT * FROM $table_name");
        $user_id = get_current_user_id(); // Pega o ID do usuário atual
          // Data de 30 dias atrás
    $data_limite = date('Y-m-d', strtotime('-30 days'));

        // Busca apenas as tarefas que pertencem ao usuário atual
        $tarefas = $wpdb->get_results("SELECT * FROM $table_name WHERE user_id = $user_id");

      
    
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

       // Concatenar as subtarefas em uma string e armazenar de forma oculta
       $subtarefasString = "";
       foreach ($subtarefas as $subtarefa) {
           $subtarefasString .= esc_html($subtarefa->descricao) . '; ';
       }

       // Campo oculto para armazenar as subtarefas
       $html .= '<input type="hidden" class="subtarefas-data" value="' . esc_attr($subtarefasString) . '">';
        

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
                    
                          // Concatenar as subtarefas em uma string e armazenar de forma oculta
       $subtarefasString = "";
       foreach ($subtarefas as $subtarefa) {
           $subtarefasString .= esc_html($subtarefa->descricao) . '; ';
       }

       // Campo oculto para armazenar as subtarefas
       $html .= '<input type="hidden" class="subtarefas-data" value="' . esc_attr($subtarefasString) . '">';
                            // Fechar a div da tarefa
                            $html .= '</div>';
                        }
                    }
        $html .= '</div></div>';
    
        
        // Coluna 'Concluído'
        $html .= '<div class="kanban-column kanban-done" ondrop="window.drop(event)" ondragover="window.allowDrop(event)" data-status="done">
        <center>
        <button id="concluir-modulo" data-user-id="<?php echo get_current_user_id(); ?>">Concluir Módulo</button></center>

                    <h3>Concluído</h3>
                    <div class="kanban-tasks" data-status="done">';

                

                    foreach ($tarefas as $tarefa) {
                        if ($tarefa->status == 'done') {
                            // Pega as datas do formulário
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

// Consulta para filtrar tarefas com base nas datas
$tarefas = $wpdb->get_results("SELECT * FROM $table_name WHERE user_id = $user_id AND data_criacao BETWEEN '$data_inicio' AND '$data_fim'");


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
                    
                          // Concatenar as subtarefas em uma string e armazenar de forma oculta
       $subtarefasString = "";
       foreach ($subtarefas as $subtarefa) {
           $subtarefasString .= esc_html($subtarefa->descricao) . '; ';
       }

       // Campo oculto para armazenar as subtarefas
       $html .= '<input type="hidden" class="subtarefas-data" value="' . esc_attr($subtarefasString) . '">';
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
        <span id="popup-close-pp">&times;</span>
        <div id="popup-info" style="display:none;">
        
        
        <span id="popup-close-pp" style="cursor: pointer; position: absolute; top: 10px; right: 15px; font-size: 20px;">&times;</span>
            <h2>Detalhes da Tarefa</h2>
            <div class="popup-section">
                <div class="popup-section-title">Título</div>
                <p id="popup-titulo">Nome da Tarefa</p>
            </div>
            <div class="popup-section">
                <div class="popup-section-title">Descrição</div>
                <p id="popup-descricao">Descrição da Tarefa</p>
            </div>
            <div class="popup-section">
                <div class="popup-section-title">Prazo</div>
                <p id="popup-prazo">Data do Prazo</p>
            </div>
            <!-- Botão para fechar o popup -->
            <button id="popup-close-pp">Fechar</button>
        </div>
    ';
    
    
        return $html;
    }
    
    add_shortcode('quadro_kanban', 'mostrar_quadro_kanban');

    function editar_tarefa_kanban() {
        global $wpdb;
        

        
        // Registrar os dados recebidos
        error_log('Recebendo dados de edição da tarefa: ' . print_r($_POST, true));
        
        // Verifique o nonce aqui
     $table_name_subtarefas = $wpdb->prefix . 'kanban_subtarefas';
        $table_name = $wpdb->prefix . 'kanban_tarefas';
        
        // Aqui você captura os dados do POST
        $id_tarefa = sanitize_text_field($_POST['task_id']);
        $nome_tarefa = sanitize_text_field($_POST['task_name']);
        $descricao = sanitize_text_field($_POST['description']);
        $prazo = sanitize_text_field($_POST['due_date']);
        $subtarefas = isset($_POST['subtasks']) ? $_POST['subtasks'] : ''; // Aqui as subtarefas devem ser um array ou string JSON
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
    
            $subtarefas = isset($_POST['subtasks']) ? explode(',', $_POST['subtasks']) : array();
    
            foreach ($subtarefas as $descricao_subtarefa) {
                $descricao_subtarefa = trim($descricao_subtarefa);
                if (!empty($descricao_subtarefa)) {
                    // Verifica se a subtarefa contém um ID
                    if (strpos($descricao_subtarefa, ':') !== false) {
                        // Subtarefa existente
                        list($id_subtarefa, $descricao) = explode(':', $descricao_subtarefa, 2);
        
                        if (is_numeric($id_subtarefa)) {
                            // Atualizar subtarefa existente
                            $wpdb->update(
                                $table_name_subtarefas,
                                array('descricao' => trim($descricao)),
                                array('id' => intval($id_subtarefa))
                            );
                        }
                    } else {
                        // Nova subtarefa
                        $wpdb->insert(
                            $table_name_subtarefas,
                            array(
                                'id_tarefa' => $id_tarefa,
                                'descricao' => $descricao_subtarefa,
                                'status' => 'pendente'
                            )
                        );
                    }
                }
            }
        
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
    





function ajax_iniciar_treinamento_trainee() {
    global $wpdb;

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $table_name = $wpdb->prefix . 'kanban_tarefas';

    $tarefas_treinamento = [
        // Módulo 1
        ['nome' => 'Definição do Nome da Sua Loja Franqueada', 'descricao' => '...', 'modulo' => 1],
        ['nome' => 'Registro do Domínio .br para a Sua Loja', 'descricao' => '...', 'modulo' => 1],
        ['nome' => 'Briefing e Contrato: Entendendo a Parceria com a Lady Griffe', 'descricao' => '...', 'modulo' => 1],
        ['nome' => 'Configuração do Apontamento do Domínio para a Sua Loja Online', 'descricao' => '...', 'modulo' => 1],
    
        // Módulo 2
        ['nome' => 'Criação e Aprovação do Logo da Sua Marca', 'descricao' => '...', 'modulo' => 2],
        ['nome' => 'Criar as redes sociais para Estabelecer uma Presença online', 'descricao' => '...', 'modulo' => 2],
        ['nome' => 'Configuração do WhatsApp Business para uma Comunicação Eficiente', 'descricao' => '...', 'modulo' => 2],
        ['nome' => 'Definindo Sua Visão, Missão e Valores: A Base do Seu Negócio', 'descricao' => '...', 'modulo' => 2],
    
        // Módulo 3
        ['nome' => 'Lista VIP: Criando um Grupo no WhatsApp para Clientes Exclusivos', 'descricao' => '...', 'modulo' => 3],
        ['nome' => 'Meta Inicial: Construindo Sua Primeira Audiência com 30 Pessoas do Seu Círculo', 'descricao' => '...', 'modulo' => 3],
        ['nome' => 'Elaboração do Plano de Ação para o Sucesso da Sua Franquia', 'descricao' => '...', 'modulo' => 3],
        ['nome' => 'Aprovação do Layout da Sua Loja: Garantindo uma Estética Atraente', 'descricao' => '...', 'modulo' => 3],
    
        // Módulo 4
        ['nome' => 'Testando as Funcionalidades da Loja: Pedido de Teste', 'descricao' => '...', 'modulo' => 4],
        ['nome' => 'Primeira Venda: Avaliando a Logística da Sua Loja com uma Venda Piloto', 'descricao' => '...', 'modulo' => 4],
        ['nome' => 'Termo de Aprovação da Entrega da Loja: Oficializando a Inauguração', 'descricao' => '...', 'modulo' => 4],
        ['nome' => 'Alinhamento Estratégico: Preparando-se para a Inauguração e o Plano de Ação', 'descricao' => '...', 'modulo' => 4],
        // ... Adicione outras tarefas, se houver, seguindo o mesmo formato
    ];

    // Insere apenas as 4 primeiras tarefas do Módulo 1 no banco de dados
$contador = 0;
foreach ($tarefas_treinamento as $tarefa) {
    if ($contador < 4 && $tarefa['modulo'] == 1) { // Garantindo que apenas as tarefas do Módulo 1 sejam adicionadas
        $wpdb->insert($table_name, array(
            'nome_tarefa' => $tarefa['nome'],
            'descricao' => $tarefa['descricao'],
            'prazo' => date('Y-m-d', strtotime('+1 week')),
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
        ['nome' => 'Definição do Nome da Sua Loja Franqueada', 'descricao' => '...', 'modulo' => 1],
        ['nome' => 'Registro do Domínio .br para a Sua Loja', 'descricao' => '...', 'modulo' => 1],
        ['nome' => 'Briefing e Contrato: Entendendo a Parceria com a Lady Griffe', 'descricao' => '...', 'modulo' => 1],
        ['nome' => 'Configuração do Apontamento do Domínio para a Sua Loja Online', 'descricao' => '...', 'modulo' => 1],
    
        // Módulo 2
        ['nome' => 'Criação e Aprovação do Logo da Sua Marca', 'descricao' => '...', 'modulo' => 2],
        ['nome' => 'Criar as redes sociais para Estabelecer uma Presença online', 'descricao' => '...', 'modulo' => 2],
        ['nome' => 'Configuração do WhatsApp Business para uma Comunicação Eficiente', 'descricao' => '...', 'modulo' => 2],
        ['nome' => 'Definindo Sua Visão, Missão e Valores: A Base do Seu Negócio', 'descricao' => '...', 'modulo' => 2],
    
        // Módulo 3
        ['nome' => 'Lista VIP: Criando um Grupo no WhatsApp para Clientes Exclusivos', 'descricao' => '...', 'modulo' => 3],
        ['nome' => 'Meta Inicial: Construindo Sua Primeira Audiência com 30 Pessoas do Seu Círculo', 'descricao' => '...', 'modulo' => 3],
        ['nome' => 'Elaboração do Plano de Ação para o Sucesso da Sua Franquia', 'descricao' => '...', 'modulo' => 3],
        ['nome' => 'Aprovação do Layout da Sua Loja: Garantindo uma Estética Atraente', 'descricao' => '...', 'modulo' => 3],
    
        // Módulo 4
        ['nome' => 'Testando as Funcionalidades da Loja: Pedido de Teste', 'descricao' => '...', 'modulo' => 4],
        ['nome' => 'Primeira Venda: Avaliando a Logística da Sua Loja com uma Venda Piloto', 'descricao' => '...', 'modulo' => 4],
        ['nome' => 'Termo de Aprovação da Entrega da Loja: Oficializando a Inauguração', 'descricao' => '...', 'modulo' => 4],
        ['nome' => 'Alinhamento Estratégico: Preparando-se para a Inauguração e o Plano de Ação', 'descricao' => '...', 'modulo' => 4],
        // ... Adicione outras tarefas, se houver, seguindo o mesmo formato
    ];

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
            'prazo' => date('Y-m-d', strtotime('+1 week')),
            'user_id' => $user_id,
            'status' => 'todo',
            'modulo' => $modulo_atual // Use $modulo_atual em vez de $modulo
        ));
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


