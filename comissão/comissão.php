<?php
/**
 * Plugin Name: Sistema de comissão 
 * Description: um plugin onde gerencia comissão dos franqueados lady griffe
 * Version: 3.0
 * Author: IKARO CALIXTO- INOVETIME
 */

 //criar tabela no banco de dados

 function wp_scm_create_saque_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'solicitacoes_saque';

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nome_usuario varchar(60) NOT NULL,
        valor_solicitado float NOT NULL,
        data_solicitacao datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        data_pagamento datetime,
        status varchar(20) DEFAULT 'Pendente' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

register_activation_hook(__FILE__, 'wp_scm_create_saque_table');



function wp_sales_commission_manager_activate() {
    global $wpdb;
    $nome_da_tabela = $wpdb->prefix . 'comissao_venda'; // Prefixo wp_ + nome da tabela em português

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $nome_da_tabela (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nome_usuario varchar(50) NOT NULL,
        data_comissao datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        valor_venda float NOT NULL,
        status varchar(20) NOT NULL,
        numero_pedido varchar(255),
        numero_rastreio varchar(255),
        data_pagamento datetime DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY  (id)
    ) $charset_collate;";
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'wp_sales_commission_manager_activate' );

function wp_scm_add_admin_menu() {
    add_menu_page(
        'Gerenciar Comissões', // Título da página
        'Comissões', // Título do menu
        'manage_options', // Capacidade necessária para ver este menu
        'wp_scm_manage_commissions', // Slug do menu
        'wp_scm_manage_commissions_page', // Função que renderiza a página do menu
        'dashicons-money', // Ícone
        6 // Posição no menu
    );
}

add_action('admin_menu', 'wp_scm_add_admin_menu');


// infelirando o css eo js do codigo 
function wp_scm_enqueue_scripts() {
    // Ajuste o caminho se os arquivos estiverem em um local diferente
    wp_enqueue_style('comissao-style', plugin_dir_url(__FILE__) . 'comissao-style.css');
    wp_enqueue_script('comissao-script', plugin_dir_url(__FILE__) . 'comissao-script.js', array('jquery'), false, true);
    
}

add_action('wp_enqueue_scripts', 'wp_scm_enqueue_scripts');


function wp_scm_manage_commissions_page() {
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        return;
    }


// Processar a submissão do formulário
if (isset($_POST['submit_commission']) && check_admin_referer('wp_scm_add_commission_action', 'wp_scm_add_commission_nonce')) {
    wp_scm_insert_commission();
}
    ?>
    <script>
function updateUserId() {
    var select = document.getElementById('nome_usuario');
    var userId = select.options[select.selectedIndex].getAttribute('data-user-id');
    document.getElementById('user_id').value = userId;
}
</script>
 <div class="wrap">
    <h2>Adicionar Nova Comissão</h2>
    <form method="post" action="">
        <?php
        // Adiciona campos de nonce para verificação de segurança
        wp_nonce_field('wp_scm_add_commission_action', 'wp_scm_add_commission_nonce');
        // Chama isso no início da marcação HTML da sua página personalizada
settings_errors('wp_scm_commissions');
        ?>
        <table class="form-table">
            <tbody>
            <tr>
                    <th scope="row"><label for="nome_usuario">Usuário</label></th>
                    <td>
                    <select name="nome_usuario" id="nome_usuario" class="regular-text" required onchange="updateUserId()">
    <option value="">Selecione um Usuário</option>
    <?php
    $users = get_users();
    foreach ($users as $user) {
        // Importante: O valor do <option> é o nome do usuário, mas também incluímos o user_id como um atributo data
        echo sprintf('<option value="%s" data-user-id="%d">%s</option>', esc_attr($user->user_login), $user->ID, esc_html($user->display_name));
    }
    ?>
</select>
<!-- Campo oculto para armazenar o user_id -->
<input type="hidden" name="user_id" id="user_id" value="">


                      
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="valor_venda">Valor da Venda</label></th>
                    <td><input name="valor_venda" type="text" id="valor_venda" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="status">Status</label></th>
                    <td>
                        <select name="status" id="status" class="regular-text">
                            <option value="Pendente">Pendente</option>
                            <option value="Aprovada">Aprovada</option>
                            <option value="Paga">Paga</option>
                        </select>
                    </td>
                </tr>
                <!-- Removendo o campo Data de Pagamento conforme solicitado anteriormente -->
                <tr>
                    <th scope="row"><label for="numero_pedido">Número do Pedido</label></th>
                    <td><input name="numero_pedido" type="text" id="numero_pedido" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="numero_rastreio">Número de Rastreio</label></th>
                    <td><input name="numero_rastreio" type="text" id="numero_rastreio" class="regular-text"></td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="submit_commission" id="submit_commission" class="button button-primary" value="Adicionar Comissão">
        </p>
    </form>
</div>

    <?php
}

function wp_scm_insert_commission() {

    
    global $wpdb;
    $table_name = $wpdb->prefix . 'comissao_venda';





    // Sanitização dos dados recebidos via POST
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
    $nome_usuario = isset($_POST['nome_usuario']) ? sanitize_text_field($_POST['nome_usuario']) : '';
    
    $valor_venda = isset($_POST['valor_venda']) ? floatval($_POST['valor_venda']) : 0.0;
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $numero_pedido = isset($_POST['numero_pedido']) ? sanitize_text_field($_POST['numero_pedido']) : ''; // Novo campo
    $numero_rastreio = isset($_POST['numero_rastreio']) ? sanitize_text_field($_POST['numero_rastreio']) : ''; // Novo campo
    $data_pagamento = isset($_POST['data_pagamento']) ? sanitize_text_field($_POST['data_pagamento']) : ''; // Campo opcional

    $data_comissao = current_time('mysql'); // Usa a data e hora atual do WordPress

    // Inserção dos dados no banco de dados
    $resultado = $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id, // Agora usando user_id ao invés de nome_usuario
            'nome_usuario' => $nome_usuario,
            'data_comissao' => $data_comissao,
            'valor_venda' => $valor_venda,
            'status' => $status,
            'numero_pedido' => $numero_pedido, // Incluindo o número do pedido
            'numero_rastreio' => $numero_rastreio, // Incluindo o número de rastreio
            'data_pagamento' => $data_pagamento
        ),
        array('%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s') // Corrigindo os formatos
    );
    

    if ($resultado) {
        // Se a inserção for bem-sucedida, exibe uma mensagem de sucesso
        add_settings_error(
            'wp_scm_commissions',
            'wp_scm_commission_success',
            'Comissão adicionada com sucesso!',
            'updated'
        );
    } else {
        // Se houver um erro na inserção, exibe uma mensagem de erro
        add_settings_error(
            'wp_scm_commissions',
            'wp_scm_commission_error',
            'Erro ao adicionar comissão. Por favor, tente novamente.',
            'error'
        );
    }
}


function shortcode_painel_financeiro() {
    if (!is_user_logged_in()) {
        return 'Você precisa estar logado para ver suas comissões.';
    }

    global $wpdb;
    $current_user = wp_get_current_user();
  
    $nome_usuario = $current_user->user_login; // Ou user_nicename, dependendo de como você está salvando

    $table_name = $wpdb->prefix . 'comissao_venda';
    $table_name_solicitacoes = $wpdb->prefix . 'solicitacoes_saque';

    // Obtem o ID do usuário atual ou o ID do usuário selecionado via GET
    $user_id = get_current_user_id(); // ID do usuário logado por padrão
    if (isset($_GET['selected_franqueado']) && !empty($_GET['selected_franqueado'])) {
        $user_id = intval($_GET['selected_franqueado']);
    }

    // Informações do usuário para exibição
    $user_info = get_userdata($user_id);
    $nome_usuario = $user_info->user_login; // Pode ser usado para exibição

    // Consultas usando user_id
    $total_vendas = $wpdb->get_var($wpdb->prepare("SELECT SUM(valor_venda) FROM $table_name WHERE user_id = %d", $user_id));
    $comissao_real = $total_vendas * 0.164; // 16,4% de comissão
    $numero_pedidos = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_id = %d", $user_id));

    $comissoes = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY data_comissao DESC", $user_id));

    // Consulta para o total sacado usando user_id
    $total_sacado = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(valor_solicitado) FROM $table_name_solicitacoes WHERE user_id = %d AND status IN ('Pago', 'Pendente')",
        $user_id
    ));
    $total_sacado = $total_sacado ? $total_sacado : 0;

    // Calcula o saldo disponível
    $saldo_disponivel = $comissao_real - $total_sacado;



    ob_start(); // Inicia a captura do output para retornar no final
    ?>
    <style>
  

    </style>

    <!-- Aqui começa o layout dos card1s conforme seu modelo -->
    <div class="main-content">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
        <div class="info-card1s">
            <div class="card1">
                <i class="fas fa-shopping-cart card1-icon"></i>
                <div class="card1-content">
                    <h3>Valor Total das Vendas</h3>
                    <p>R$ <?php echo number_format($total_vendas, 2, ',', '.'); ?></p>
                </div>
            </div>
            <div class="card1">
    <i class="fas fa-wallet card1-icon"></i>
    <div class="card1-content">
        <h3>Saldo Disponível para Saque</h3>
        <p>R$ <?php echo number_format($saldo_disponivel, 2, ',', '.'); ?></p>
    </div>
</div>
            <div class="card1">
                <i class="fas fa-clipboard-list card1-icon"></i>
                <div class="card1-content">
                    <h3>Número Total de Pedidos</h3>
                    <p><?php echo $numero_pedidos; ?></p>
                </div>
            </div>
        </div>
        <!-- Aqui termina o layout dos card1s -->



        <div class="details">
    <h2 style="font-size: 14px;">Faça seu saque agora mesmo!</h2>
    <p>Seu saldo disponível é: <strong>R$<?php echo number_format($saldo_disponivel, 2, ',', '.'); ?></strong></p>
  
    <button style=" border-color:green; background-color:green;" id="btnSolicitarSaque" class="button button-primary">Solicitar Saque</button>
</div>

<!-- Popup de Solicitação de Saque -->
<div id="popupSaque" class="popup-container" style="display:none;">
    <div class="popup-content">
        <span class="close-btn">&times;</span>
        <h3>Solicitar Saque</h3>
        <p>Insira o valor que deseja sacar (mínimo R$ 200):</p>
        <input type="number" id="valorSaque" placeholder="Valor do Saque" min="200" max="<?php echo $saldo_disponivel; ?>" step="0.01">

        <button id="confirmarSaque" class="button button-secondary">Confirmar Saque</button>
        <!-- Indicador de carregamento -->
        <div id="loadingIndicator" style="display:none;">Processando...</div>
    </div>
</div>

<style>

/* Adicione mais estilos conforme necessário */
</style>

<script>

</script>
        <!-- Sua tabela de comissões aqui -->
        <?php if (!empty($comissoes)): ?>
            <table>
                <tr><th>Data</th><th>Valor da Venda</th><th>Status</th><th>Número do Pedido</th><th>Número de Rastreio</th><th>Data do Pagamento</th></tr>
                <?php foreach ($comissoes as $comissao): ?>
                    <tr>
                        <td><?php echo $comissao->data_comissao; ?></td>
                        <td>R$ <?php echo number_format($comissao->valor_venda, 2, ',', '.'); ?></td>
                        <td><?php echo $comissao->status; ?></td>
                        <td><?php echo $comissao->numero_pedido; ?></td>
                        <td><?php echo $comissao->numero_rastreio ? $comissao->numero_rastreio : 'N/A'; ?></td>
                        <td><?php echo $comissao->data_pagamento ? $comissao->data_pagamento : 'N/A'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>Não foram encontradas comissões para você.</p>
        <?php endif; ?>
    </div>
    

    <?php
    return ob_get_clean(); // Retorna o conteúdo capturado
}
add_shortcode('painel_financeiro', 'shortcode_painel_financeiro');

function verificar_comissoes() {
    if (!is_user_logged_in()) {
        return 'Você precisa estar logado para ver suas comissões.';
    }

    echo '<div class="form-verificar-comissoes">';
    echo '<form action="" method="get">';
    echo '<select name="selected_franqueado" onchange="this.form.submit()">';
    echo '<option value="">Selecione um Franqueado</option>';

    $users = get_users();
    foreach ($users as $user) {
        $selected = isset($_GET['selected_franqueado']) && $_GET['selected_franqueado'] == $user->ID ? 'selected' : '';
        echo '<option value="' . esc_attr($user->ID) . '" ' . $selected . '>' . esc_html($user->display_name) . '</option>';
    }

    echo '</select>';
    echo '<input type="submit" value="Ver Comissões">';
    echo '</form>';

    // Se um franqueado for selecionado, busca e exibe as informações relevantes
    if (isset($_GET['selected_franqueado']) && !empty($_GET['selected_franqueado'])) {
        global $wpdb;
        $selected_franqueado_id = intval($_GET['selected_franqueado']);
        $table_name = $wpdb->prefix . 'comissao_venda';
        $table_name_solicitacoes = $wpdb->prefix . 'solicitacoes_saque';

    }

       

    echo '</div>';
}
add_shortcode('verificar_comissoes', 'verificar_comissoes');




function wp_scm_admin_saque_shortcode() {
    if (!current_user_can('manage_options')) {
        return 'Você não tem permissão para acessar esta página.';
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'solicitacoes_saque'; // Atualizado para nova tabela
    $solicitacoes = $wpdb->get_results("SELECT * FROM $table_name WHERE status = 'Pendente'");

    ob_start();
    ?>
    <div class="wrap">
        <h2>Solicitações de Saque</h2>
        <table>
            <thead>
                <tr>
                    <th>Nome do Usuário</th>
                    <th>Valor Solicitado</th>
                    <th>Data da Solicitação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($solicitacoes as $solicitacao): ?>
                <tr>
                    <td><?php echo esc_html($solicitacao->nome_usuario); ?></td>
                    <td>R$ <?php echo number_format($solicitacao->valor_solicitado, 2, ',', '.'); ?></td>
                    <td><?php echo esc_html($solicitacao->data_solicitacao); ?></td>
                    <td>
                        <button class="button button-primary wp-scm-aprovar-saque" data-id="<?php echo esc_attr($solicitacao->id); ?>">Aprovar</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
   document.querySelectorAll('.wp-scm-aprovar-saque').forEach(button => {
    button.addEventListener('click', function() {
        var id = this.getAttribute('data-id');
        if (!confirm('Tem certeza que deseja aprovar este saque?')) return;

        jQuery.post(ajaxurl, {
            action: 'aprovar_saque',
            id_saque: id
        }, function(response) {
            if(response.success) {
                alert('Saque aprovado com sucesso.');
                location.reload(); // Recarrega a página para atualizar a lista
            } else {
                alert('Erro ao aprovar saque: ' + response.data);
            }
        });
    });
});
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('admin_saque', 'wp_scm_admin_saque_shortcode');




function wp_scm_usuario_extrato_saque_shortcode() {
    // Verifica se o usuário está logado, substitua 'subscriber' pelo papel adequado se necessário
    if (!is_user_logged_in()) {
        return 'Você precisa estar logado para acessar esta página.';
    }

    global $wpdb;
    $user_id = get_current_user_id(); // Assegure-se de que esta chamada está correta e retorna o ID do usuário atual
    $table_name = $wpdb->prefix . 'solicitacoes_saque'; // Confirme se o prefixo da tabela e o nome estão corretos

    // Prepara e executa a query
    $solicitacoes = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM `$table_name` WHERE `user_id` = %d AND (`status` = 'Pendente' OR `status` = 'Pago')",
        $user_id
    ));

 
  
    ob_start();
    ?>

    <!-- Ícone de Extrato -->
    <div class="extrato-icon-wrapper">
        <a href="#!" id="openExtratoModal"><i class="fas fa-receipt"></i> </a>
    </div>

    <!-- Modal de Extrato -->
    <div id="extratoModal" class="extrato-modal">
        <div class="extrato-modal-content">
            <span class="close">&times;</span>
            <h2>Extrato de Saques</h2>
            <div class="wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Valor Solicitado</th>
                            <th>Data da Solicitação</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitacoes as $solicitacao): ?>
                        <tr>
                            <td>R$ <?php echo number_format($solicitacao->valor_solicitado, 2, ',', '.'); ?></td>
                            <td><?php echo esc_html($solicitacao->data_solicitacao); ?></td>
                            <td><?php echo esc_html($solicitacao->status); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('usuario_extrato_saque', 'wp_scm_usuario_extrato_saque_shortcode');




add_action('wp_ajax_solicitar_saque', 'wp_scm_solicitar_saque_handler');

function wp_scm_solicitar_saque_handler() {
    // Verifica se o usuário está logado
    if (!is_user_logged_in()) {
        wp_send_json_error('Você precisa estar logado para realizar esta ação.');
        return;
    }
    global $wpdb;
    $current_user = wp_get_current_user();
    $valor_saque = isset($_POST['valor_saque']) ? floatval($_POST['valor_saque']) : 0;
    $nome_usuario = $current_user->user_login;
    $user_id = get_current_user_id();

    

    // Calcula o saldo disponível
    $table_name_comissao = $wpdb->prefix . 'comissao_venda';
    $total_comissao = $wpdb->get_var($wpdb->prepare("SELECT SUM(valor_venda) FROM $table_name_comissao WHERE nome_usuario = %s", $nome_usuario));
    
    $table_name_solicitacoes = $wpdb->prefix . 'solicitacoes_saque';
    $total_sacado = $wpdb->get_var($wpdb->prepare("SELECT SUM(valor_solicitado) FROM $table_name_solicitacoes WHERE nome_usuario = %s AND status IN ('Pendente', 'Pago')", $nome_usuario));
    
    $saldo_disponivel = $total_comissao - $total_sacado;

    // Verifica se o valor solicitado excede o saldo disponível
    if ($valor_saque > $saldo_disponivel) {
        wp_send_json_error('O valor solicitado excede seu saldo disponível.');
        return;
    }


    // Certifique-se de usar a nova tabela de solicitações de saque
    $table_name = $wpdb->prefix . 'solicitacoes_saque';

    // Verifica se o valor solicitado é maior que zero
    if ($valor_saque <= 0) {
        wp_send_json_error('O valor solicitado deve ser maior que zero.');
        return;
    }

    // Insere a solicitação de saque na tabela
    $resultado = $wpdb->insert(
        $table_name,
        [
            'user_id' => $user_id, // Adiciona o user_id na inserção
            'nome_usuario' => $nome_usuario,
            'valor_solicitado' => $valor_saque,
            'status' => 'Pendente', // Status inicial
        ]
    );

     // Inserção da solicitação de saque...
     if ($resultado) {
        // Definir os IDs dos usuários responsáveis
        $responsaveis = [11,27, 2]; // Substitua 1 e 2 pelos IDs reais dos usuários responsáveis

        // Tabela de notificações
        $table_name_notificacoes = $wpdb->prefix . 'meu_plugin_notificacoes';

        // Mensagem de notificação
        $mensagem_notificacao = 'Uma nova solicitação de saque foi feita por ' . $nome_usuario;

        // Enviar notificações aos responsáveis
        foreach ($responsaveis as $id_responsavel) {
            $wpdb->insert(
                $table_name_notificacoes,
                array(
                    'user_id' => $id_responsavel,
                    'mensagem' => $mensagem_notificacao,
                    'imagem' => '', // Opcional: URL da imagem se aplicável
                    'url_redirecionamento' => admin_url('admin.php?page=solicitacoes-saque'), // Link para a página de administração das solicitações de saque
                    'data_envio' => current_time('mysql'),
                    'lida' => 0
                ),
                array('%d', '%s', '%s', '%s', '%s', '%d')
            );
        }

    if ($resultado) {
        wp_send_json_success('Confirmamos o recebimento de sua solicitação de saque. Ela está sob análise e, se aprovada, o montante será depositado em sua conta no dia 20 subsequente.');
    } else {
        wp_send_json_error('Não foi possível processar sua solicitação de saque.');
    }
} 
}


add_action('wp_ajax_aprovar_saque', 'wp_scm_aprovar_saque');

function wp_scm_aprovar_saque() {
    global $wpdb;
    $user_id = get_current_user_id();

    // Verifica se o usuário tem permissões
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sem permissões suficientes');
        return;
    }

    $id_saque = isset($_POST['id_saque']) ? intval($_POST['id_saque']) : 0;

    if ($id_saque <= 0) {
        wp_send_json_error('ID de saque inválido');
        return;
    }

    // Primeiro, atualize o status na tabela de solicitações de saque
    $tabela_solicitacoes = $wpdb->prefix . 'solicitacoes_saque';
    $wpdb->update($tabela_solicitacoes, ['status' => 'Pago', 'data_pagamento' => current_time('mysql')], ['id' => $id_saque]);

    // Depois, atualize todos os pedidos relacionados ao usuário para "Pago" na tabela comissao_venda
    $solicitacao = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabela_solicitacoes WHERE id = %d", $id_saque));
    if ($solicitacao) {
        $tabela_comissao = $wpdb->prefix . 'comissao_venda';
        $wpdb->update($tabela_comissao, ['status' => 'Pago', 'data_pagamento' => current_time('mysql')], ['nome_usuario' => $solicitacao->nome_usuario, 'status' => 'Pendente']);
    }
  // Obtenha informações da solicitação de saque para encontrar o user_id do solicitante
  $solicitacao = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabela_solicitacoes WHERE id = %d", $id_saque));

  if (!$solicitacao) {
      wp_send_json_error('Solicitação de saque não encontrada.');
      return;
  }

  // Tabela de notificações
  $table_name_notificacoes = $wpdb->prefix . 'meu_plugin_notificacoes';

  // Mensagem de notificação
  $mensagem_notificacao = 'Sua comissão foi paga com sucesso.';

  // Enviar notificação ao usuário que solicitou o saque
  $wpdb->insert(
      $table_name_notificacoes,
      array(
          'user_id' => $solicitacao->user_id, // Usa o user_id do solicitante do saque
          'mensagem' => $mensagem_notificacao,
          'imagem' => '', // Opcional: URL da imagem se aplicável
          'url_redirecionamento' => '', // Link para onde você quer que o usuário vá ao clicar na notificação, se aplicável
          'data_envio' => current_time('mysql'),
          'lida' => 0
      ),
      array('%d', '%s', '%s', '%s', '%s', '%d')
  );

  wp_send_json_success('Saque aprovado e status atualizado com sucesso');
}