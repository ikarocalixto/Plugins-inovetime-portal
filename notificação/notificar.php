<?php
/*
Plugin Name: Meu Plugin de Notificações
Description: Um simples plugin de notificação para WordPress
Version:     1.1
Author:      Seu Nome
Author URI:  https://seusite.com
Text Domain: meu-plugin-de-notificacoes
*/




function meu_plugin_mostrar_icone_sino() {
    global $wpdb;
    $tabela = $wpdb->prefix . 'meu_plugin_notificacoes';
    $user_id = get_current_user_id();

    $icone_sino_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="38" height="48" viewBox="0 0 24 24">
    <path class="evJ6XQ" fill="currentColor" d="M13.5 19H15a3 3 0 0 1-6 0h1.5a1.5 1.5 0 0 0 3 0z"></path>
    <path class="x9EzJQ" fill="currentColor" fill-rule="evenodd" d="M20.04 17.5h.21a.75.75 0 1 1 0 1.5H3.75a.75.75 0 1 1 0-1.5h.21c1-1.15 1.82-2.7 1.82-5.97v-1.3a6.22 6.22 0 0 1 4.87-6.08 1.5 1.5 0 1 1 2.7 0 6.22 6.22 0 0 1 4.87 6.07v1.3c0 3.27.83 4.82 1.82 5.98zm-1.87 0c-.99-1.52-1.45-3.3-1.45-5.97v-1.3a4.72 4.72 0 0 0-9.44 0v1.3c0 2.67-.46 4.45-1.45 5.97h12.34z"></path>
</svg>

';

    $icone_sino = '<div id="meu-plugin-icone-sino">' . $icone_sino_svg . '</div>';
    $popup_notificacoes = '<div id="meu-plugin-popup-notificacoes" style="display: none;">';

    // Listar notificações do usuário
$notificacoes = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tabela WHERE user_id = %d ORDER BY data_envio DESC", $user_id));


    $popup_notificacoes .= '<h2>Suas Notificações</h2>';
    $popup_notificacoes .= '<button id="btn-marcar-tudo-como-lido" style="margin-bottom: 10px;">Marcar tudo como lido</button>';

  // Formatando a data
        $data_formatada = date('d/m/Y H:i', strtotime($notificacao->data_envio));


     foreach($notificacoes as $notificacao) {
        $popup_notificacoes .= '<div>';
         $popup_notificacoes .= '<p>Data: ' . $data_formatada . '</p>'; // Adiciona a data formatada
        $popup_notificacoes .= '<p>Mensagem: <a href="' . $notificacao->url_redirecionamento . '">' . $notificacao->mensagem . '</a></p>';
        $popup_notificacoes .= '<p>Imagem: ' . $notificacao->imagem . '</p>';
       
        $popup_notificacoes .= '</div>';
    }

    $popup_notificacoes .= '</div>';

    return $icone_sino . $popup_notificacoes;
}
add_shortcode('icone_sino', 'meu_plugin_mostrar_icone_sino');



function meu_plugin_remover_notificacao() {
    // Verifique se a solicitação AJAX enviou um ID de notificação
    if (isset($_POST['id'])) {
        global $wpdb;
        $tabela = $wpdb->prefix . 'meu_plugin_notificacoes';

        // Remova a notificação do banco de dados
        $wpdb->delete($tabela, array('id' => $_POST['id']));

        // Envie uma resposta de sucesso
        wp_send_json_success();
    } else {
        // Se não houver ID de notificação, envie uma resposta de erro
        wp_send_json_error('ID da notificação não especificado');
    }
    wp_die(); // isso é necessário para terminar corretamente o ajax
}
add_action('wp_ajax_meu_plugin_remover_notificacao', 'meu_plugin_remover_notificacao');
add_action('wp_ajax_nopriv_meu_plugin_remover_notificacao', 'meu_plugin_remover_notificacao');


function meu_plugin_incluir_scripts_estilos() {
    if (!is_user_logged_in()) { 
        return; 
    }
    
    wp_enqueue_script('jquery');
    wp_enqueue_script('meu-plugin-script', plugins_url('meu-plugin-script.js', __FILE__), array('jquery'), '1.0', true);
    wp_localize_script('meu-plugin-script', 'meu_plugin_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
     wp_enqueue_style('meu-plugin-style', plugins_url('meu-plugin-style.css', __FILE__), array(), '1.0', 'all'); // Adiciona o arquivo CSS
}
add_action('wp_enqueue_scripts', 'meu_plugin_incluir_scripts_estilos');

function meu_plugin_verificar_notificacoes() {
    if ( is_user_logged_in() ) {
        global $wpdb;
        $user_id = get_current_user_id();
        $tabela_notificacoes = $wpdb->prefix . 'meu_plugin_notificacoes';
    
        $notificacoes = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tabela_notificacoes WHERE user_id = %d AND lida = 0", $user_id));

        echo count($notificacoes);
    } else {
        echo '0';
    }
    wp_die();
}
add_action('wp_ajax_meu_plugin_verificar_notificacoes', 'meu_plugin_verificar_notificacoes');

function meu_plugin_criar_tabela_notificacoes() {
    global $wpdb;

    $tabela = $wpdb->prefix . 'meu_plugin_notificacoes';

    $charset_collate = $wpdb->get_charset_collate();

    if($wpdb->get_var("SHOW TABLES LIKE '$tabela'") != $tabela) {
        // Se a tabela não existir, criá-la
        $sql = "CREATE TABLE $tabela (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            mensagem text NOT NULL,
            imagem varchar(255),
            url varchar(255),
            data_envio DATETIME,
            lida tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    } else {
        // Se a tabela existir, verificar se a coluna 'data_envio' existe
        $coluna = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s 
            AND TABLE_NAME = %s 
            AND COLUMN_NAME = %s ",
            DB_NAME, $tabela, 'data_envio'
        ));

        // Se a coluna 'data_envio' não existir, adicioná-la
        if(empty($coluna)){
            $wpdb->query("ALTER TABLE $tabela ADD COLUMN data_envio DATETIME");
        }
    }
}

register_activation_hook(__FILE__, 'meu_plugin_criar_tabela_notificacoes');




register_activation_hook( __FILE__, 'meu_plugin_ativar');
function meu_plugin_ativar() {
    ob_start(); // Iniciar buffering de saída
    meu_plugin_criar_tabela_notificacoes(); // Seu método que pode estar gerando saída
    $saida = ob_get_clean(); // Limpar o buffer de saída e armazená-lo em uma variável

    // Agora você pode verificar se há alguma saída
    if (!empty($saida)) {
        // Escrever a saída em um arquivo de log para inspeção
        error_log("Saída durante a ativação do plugin: " . $saida);
    }
}
add_action( 'admin_init', 'meu_plugin_criar_tabela_notificacoes');


function meu_plugin_adicionar_menu_admin() {
    add_menu_page(
        'Notificações', 
        'Notificações', 
        'manage_options', 
        'gerenciar-notificacoes', 
        'meu_plugin_mostrar_pagina_admin', 
        'dashicons-bell'
    );
}
add_action('admin_menu', 'meu_plugin_adicionar_menu_admin');

function meu_plugin_mostrar_pagina_admin() {
    global $wpdb;
    $tabela = $wpdb->prefix . 'meu_plugin_notificacoes';
    $usuarios = get_users();

    // Remover notificação
    if (isset($_POST['delete'])) {
        $delete_id = $_POST['delete_id'];

        $wpdb->delete(
            $tabela,
            array('id' => $delete_id)
        );

        echo '<p>Notificação removida com sucesso.</p>';
    }

    // Remover todas as notificações
    if (isset($_POST['delete_all'])) {
        $wpdb->query("DELETE FROM $tabela");

        echo '<p>Todas as notificações foram removidas com sucesso.</p>';
    }

     // Adicionar notificação
    if (isset($_POST['submit'])) {
        $user_id = $_POST['user_id'];
        $mensagem = $_POST['mensagem'];
        $imagem = $_POST['imagem'];
        $link = $_POST['url_redirecionamento'];
        $data_envio = $_POST['data_envio'];

        // Converter a data/hora do formato local para GMT
        $data_envio_gmt = get_gmt_from_date($data_envio);

        if ($user_id === 'all') {
            foreach($usuarios as $usuario) {
                $wpdb->insert(
                    $tabela,
                    array(
                        'user_id' => $usuario->ID,
                        'mensagem' => $mensagem,
                        'imagem' => $imagem,
                        'url_redirecionamento' => $link,
                        'data_envio' => $data_envio_gmt
                    )
                );
                if($wpdb->last_error !== '') {
                    echo '<p>Erro no banco de dados: ' . $wpdb->last_error . '</p>';
                    return;
                }

                // Agendar o envio da notificação
                wp_schedule_single_event(strtotime($data_envio_gmt), 'meu_plugin_enviar_notificacao', array($wpdb->insert_id));
            }
            echo '<p>Notificação adicionada para todos os usuários.</p>';
        } else {
            $wpdb->insert(
                $tabela,
                array(
                    'user_id' => $user_id,
                    'mensagem' => $mensagem,
                    'imagem' => $imagem,
                    'url_redirecionamento' => $link,
                    'data_envio' => $data_envio_gmt
                )
            );
            if($wpdb->last_error !== '') {
                echo '<p>Erro no banco de dados: ' . $wpdb->last_error . '</p>';
                return;
            }

            // Agendar o envio da notificação
            wp_schedule_single_event(strtotime($data_envio_gmt), 'meu_plugin_enviar_notificacao', array($wpdb->insert_id));

            echo '<p>Notificação adicionada para o usuário ' . $user_id . '.</p>';
        }
    }

    echo '<div id="meu-plugin-admin-page">';
    echo '<h1>Adicionar Notificação</h1>';
    echo '<form method="post">';
    echo '<label for="user_id">Usuário:</label>';
    echo '<select name="user_id" id="user_id" required>';
    echo '<option value="all">Todos os usuários</option>';

    foreach($usuarios as $usuario) {
        echo '<option value="' . $usuario->ID . '">' . $usuario->display_name . '</option>';
    }

    echo '</select>';
    echo '<label for="mensagem">Mensagem:</label>';
    echo '<textarea name="mensagem" id="mensagem" required></textarea>';
    echo '<label for="imagem">URL da Imagem:</label>';
    echo '<input type="url" name="imagem" id="imagem">';
    echo '<label for="url_redirecionamento">URL de Redirecionamento:</label>';
    echo '<input type="url" name="url_redirecionamento" id="url_redirecionamento">';
    echo '<label for="data_envio">Data/Hora de Envio:</label>';
    echo '<input type="datetime-local" name="data_envio" id="data_envio" required>';
    echo '<input type="submit" name="submit" value="Adicionar Notificação">';
    echo '</form>';

    echo '<form method="post">';
    echo '<input type="submit" name="delete_all" value="Remover Todas Notificações">';
    echo '</form>';

    $notificacoes = $wpdb->get_results("SELECT * FROM " . $tabela);

    $usuarios = [];

    foreach($notificacoes as $notificacao) {
        $user_info = get_userdata($notificacao->user_id);
        if (!array_key_exists($user_info->ID, $usuarios)) {
            $usuarios[$user_info->ID] = ['login' => $user_info->user_login, 'notificacoes' => []];
        }
        $usuarios[$user_info->ID]['notificacoes'][] = $notificacao;
    }

    echo '<h2>Notificações existentes</h2>';

    foreach($usuarios as $id => $info) {
        echo '<button class="usuario" data-id="' . $id . '">' . $info['login'] . '</button>';
        echo '<div class="notificacoes" data-id="' . $id . '" style="display: none;">';

        foreach($info['notificacoes'] as $notificacao) {
            echo '<p>Mensagem: ' . $notificacao->mensagem . '</p>';
            echo '<p>Imagem: ' . $notificacao->imagem . '</p>';
            echo '<p>URL de Redirecionamento: ' . $notificacao->url_redirecionamento . '</p>';
            echo '<form method="post">';
            echo '<input type="hidden" name="delete_id" value="' . $notificacao->id . '">';
            echo '<input type="submit" name="delete" value="Remover Notificação">';
            echo '</form>';
        }
        echo '</div>';
    }
    echo '</div>';
}


// Enviar a notificação
function meu_plugin_enviar_notificacao($notificacao_id) {
    global $wpdb;
    $tabela = $wpdb->prefix . 'meu_plugin_notificacoes';

  // Pegar a notificação do banco de dados ordenadas por data_envio em ordem decrescente
$notificacoes = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tabela WHERE user_id = %d ORDER BY data_envio DESC", $user_id));


    // Configurar Firebase
    $factory = (new \Kreait\Firebase\Factory())->withServiceAccount('/path/to/your/firebase_credentials.json');
    $messaging = $factory->createMessaging();

    // Configurar notificação
    $notification = Notification::fromArray([
        'title' => 'Nova notificação',
        'body' => $notificacao->mensagem,
        'image' => $notificacao->imagem,
    ]);
    
    // Recuperar o token do usuário para enviar a notificação
    // Esta parte depende de como você armazena os tokens dos usuários
    $user_token = getUserToken($notificacao->user_id);

    // Criar a mensagem
    $message = CloudMessage::withTarget('token', $user_token)
        ->withNotification($notification) 
        ->withData(['url' => $notificacao->url_redirecionamento]);

    // Enviar a notificação
    try {
        $messaging->send($message);
    } catch (\Kreait\Firebase\Exception\MessagingException $e) {
        echo 'Erro ao enviar notificação: ' . $e->getMessage();
        return;
    }

    // Remover a notificação do banco de dados
    $wpdb->delete($tabela, array('id' => $notificacao_id));
}
add_action('meu_plugin_enviar_notificacao', 'meu_plugin_enviar_notificacao');




function meu_plugin_marcar_notificacao_como_lida() {
    if ( is_user_logged_in() && isset($_POST['id']) ) {
        global $wpdb;
        $user_id = get_current_user_id();
        $id_notificacao = $_POST['id'];
        $tabela = $wpdb->prefix . 'meu_plugin_notificacoes';

        // Garanta que o usuário atual é o destinatário da notificação antes de marcá-la como lida
        $notificacao = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $tabela WHERE id = %d AND user_id = %d",
                $id_notificacao,
                $user_id
            )
        );

        if ( $notificacao ) {
            $wpdb->update(
                $tabela,
                array(
                    'lida' => 1
                ),
                array(
                    'id' => $id_notificacao,
                    'user_id' => $user_id
                ),
                array('%d'), 
                array('%d', '%d') 
            );

            echo json_encode(array('status' => 'success', 'message' => 'Notificação marcada como lida.'));
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'Notificação não encontrada ou não pertence ao usuário atual.'));
        }
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Você deve estar logado para marcar notificações como lidas.'));
    }

    wp_die();
}
add_action('wp_ajax_meu_plugin_marcar_notificacao_como_lida', 'meu_plugin_marcar_notificacao_como_lida');
add_action('wp_ajax_nopriv_meu_plugin_marcar_notificacao_como_lida', 'meu_plugin_marcar_notificacao_como_lida');




function meu_plugin_pegar_notificacoes() {
    if ( is_user_logged_in() ) {
        global $wpdb;
        $user_id = get_current_user_id();
        $tabela_notificacoes = $wpdb->prefix . 'meu_plugin_notificacoes';
        
    
       // Pegar as notificações não lidas do banco de dados ordenadas por data_envio em ordem decrescente
$notificacoes = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tabela_notificacoes WHERE user_id = %d AND lida = 0 ORDER BY data_envio DESC", $user_id));


        // Se houver notificações, retorne os resultados
        if (count($notificacoes) > 0) {
            echo json_encode($notificacoes);
        } else {
            echo 'Não há novas notificações';
        }
    } else {
        echo 'Erro: usuário não está logado';
    }

    wp_die();
}
add_action('wp_ajax_meu_plugin_pegar_notificacoes', 'meu_plugin_pegar_notificacoes');

function meu_plugin_admin_style() {
    wp_register_style('meu_plugin_admin_style', plugins_url('meu-plugin-style.css', __FILE__));
    wp_enqueue_style('meu_plugin_admin_style');
}
add_action('admin_enqueue_scripts', 'meu_plugin_admin_style');


function meu_plugin_admin_scripts($hook) {
    // Verificar se estamos na página correta, se necessário
    // if ('your-admin-page-slug.php' != $hook) return;

    // Enfileira o script
    wp_enqueue_script(
        'meu-plugin-script', // Identificador único para o script
        plugins_url('meu-plugin-script.js', __FILE__), // Caminho para o arquivo JS
        array('jquery'), // Dependências (ex.: jQuery)
        '1.0.0', // Versão
        true  // Incluir no footer
    );
}
add_action('admin_enqueue_scripts', 'meu_plugin_admin_scripts');
