<?php
/*
Plugin Name: Meu Plugin de Ponto
Description: Plugin para rastrear horários de login e logout dos funcionários.
Version: 1.0
Author: Seu Nome
*/

// Função de ativação do plugin
function ativar_meu_plugin() {
    global $wpdb;

    $tabela = $wpdb->prefix . 'carga_horaria';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $tabela (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        nome_usuario varchar(100) NOT NULL,
        data date NOT NULL,
        primeiro_login time DEFAULT '00:00:00' NOT NULL,
        ultimo_logout time DEFAULT '00:00:00' NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";


    // Verifica se as colunas de pausa já existem
    $coluna_inicio_pausa = $wpdb->get_results("SHOW COLUMNS FROM `$tabela` LIKE 'inicio_pausa'");
    $coluna_fim_pausa = $wpdb->get_results("SHOW COLUMNS FROM `$tabela` LIKE 'fim_pausa'");

    // Adiciona as colunas se elas não existirem
    if (empty($coluna_inicio_pausa)) {
        $wpdb->query("ALTER TABLE `$tabela` ADD `inicio_pausa` TIME DEFAULT '00:00:00'");
    }
    if (empty($coluna_fim_pausa)) {
        $wpdb->query("ALTER TABLE `$tabela` ADD `fim_pausa` TIME DEFAULT '00:00:00'");
    }
}

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

register_activation_hook(__FILE__, 'ativar_meu_plugin');

// Função de desativação do plugin
function desativar_meu_plugin() {
    // Código opcional para limpar configurações
    // Ex: Remover tabelas criadas
}
register_deactivation_hook(__FILE__, 'desativar_meu_plugin');

// Função para rastrear o login e o logout do usuário
function rastrear_login_logout_usuario( $user_login, $user, $is_logout = false ) {
    global $wpdb;

    $usuarios_permitidos = get_option( 'usuarios_rastreados', array() );

    if ( !in_array( $user->ID, $usuarios_permitidos ) ) {
        return;
    }

    $tabela = $wpdb->prefix . 'carga_horaria';
    $user_id = $user->ID;
    $data_atual = current_time('Y-m-d');
    $hora_atual = current_time('H:i:s');

    // Verifica se já existe um registro para o usuário na data atual
    $registro_existente = $wpdb->get_row( $wpdb->prepare(
        "SELECT id FROM $tabela WHERE user_id = %d AND data = %s",
        $user_id,
        $data_atual
    ));

    if ( is_null( $registro_existente ) ) {
        if (!$is_logout) { // Se for um login
            $wpdb->insert(
                $tabela, 
                array(
                    'user_id' => $user_id,
                    'nome_usuario' => $user->display_name,
                    'data' => $data_atual,
                    'primeiro_login' => $hora_atual,
                    'ultimo_logout' => '00:00:00'
                ),
                array( '%d', '%s', '%s', '%s', '%s' )
            );
        }
    } else {
        if ($is_logout) { // Se for um logout
            $wpdb->update(
                $tabela,
                array( 'ultimo_logout' => $hora_atual ),
                array( 'id' => $registro_existente->id ),
                array( '%s' ),
                array( '%d' )
            );
        }
    }
}

// Hooks para login e logout
add_action('wp_login', function ($user_login, $user) {
    rastrear_login_logout_usuario($user_login, $user, false);
}, 10, 2);

add_action('wp_logout', function ($user_login, $user) {
    rastrear_login_logout_usuario($user_login, $user, true);
}, 10, 2);


// Função para adicionar a página de opções
function adicionar_pagina_opcoes() {
    add_menu_page( 'Configurações de Rastreamento de Login', 'Rastreamento de Login', 'manage_options', 'rastreamento-login', 'pagina_opcoes_callback' );
}
add_action( 'admin_menu', 'adicionar_pagina_opcoes' );

// Callback para renderizar a página de opções
function pagina_opcoes_callback() {
    $usuarios = get_users();
    $usuarios_selecionados = get_option( 'usuarios_rastreados', array() );

    if ( isset( $_POST['usuarios_rastreados'] ) ) {
        update_option( 'usuarios_rastreados', $_POST['usuarios_rastreados'] );
        echo '<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"><p><strong>Configurações salvas.</strong></p></div>';
    }

    echo '<form method="post" action="">';
    foreach ( $usuarios as $usuario ) {
        $checked = in_array( $usuario->ID, $usuarios_selecionados ) ? 'checked' : '';
        echo '<input type="checkbox" name="usuarios_rastreados[]" value="' . esc_attr( $usuario->ID ) . '" ' . $checked . '> ' . esc_html( $usuario->display_name ) . '<br>';
    }
    echo '<input type="submit" value="Salvar Configurações">';
    echo '</form>';
}






// Função para adicionar a subpágina de visualização de horários
function adicionar_subpagina_horarios() {
    add_submenu_page(
        'rastreamento-login',
        'Horários dos Usuários',
        'Horários',
        'manage_options',
        'horarios-usuarios',
        'pagina_horarios_callback'
    );
}
add_action('admin_menu', 'adicionar_subpagina_horarios');



// Callback para renderizar a subpágina de horários
function pagina_horarios_callback() {
    global $wpdb;
    $tabela = $wpdb->prefix . 'carga_horaria';
    $usuarios_permitidos = get_option('usuarios_rastreados', array());

    // Filtro de pesquisa
    $filtro_usuario = isset($_GET['filtro_usuario']) ? $_GET['filtro_usuario'] : '';

    // Renderizar o formulário de filtro
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="horarios-usuarios" />';
    echo '<select name="filtro_usuario">';
    echo '<option value="">Todos os Usuários</option>';

    foreach ($usuarios_permitidos as $id_usuario) {
        $usuario_info = get_userdata($id_usuario);
        $selected = ($id_usuario == $filtro_usuario) ? 'selected' : '';
        echo '<option value="' . esc_attr($id_usuario) . '" ' . $selected . '>' . esc_html($usuario_info->display_name) . '</option>';
    }
    echo '</select>';
    echo '<input type="submit" value="Filtrar" />';
    echo '</form>';

    // Consulta ao banco de dados com filtro
    $where_clause = $filtro_usuario ? $wpdb->prepare(" WHERE user_id = %d", $filtro_usuario) : '';
    $query = "SELECT * FROM $tabela" . $where_clause;
    $registros = $wpdb->get_results($query);




    
      $total_horas_trabalhadas = 0;

    // Início do HTML da tabela
    $html = '<table style="width: 100%; border-collapse: collapse;">';
    $html .= '<tr style="background-color: #f2f2f2;">';
    $html .= '<th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Dia</th>';
    $html .= '<th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Data</th>';
    $html .= '<th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Horário de Entrada</th>';
    $html .= '<th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Horário de Saída</th>';
    $html .= '<th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Horas Trabalhadas</th>';
    $html .= '<th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Horas de Pausa</th>';
    $html .= '</tr>';

 

         // Iterar sobre os registros
    foreach ($registros as $registro) {
      
      
        // Formatar a data e obter o dia da semana
        $data_formatada = date_i18n('d/m/Y', strtotime($registro->data));
        $dia_semana = date_i18n('l', strtotime($registro->data));

          // Calcular minutos trabalhados para cada registro
        $minutos_trabalhados = (strtotime($registro->ultimo_logout) - strtotime($registro->primeiro_login)) / 60;
        $total_minutos_trabalhados += $minutos_trabalhados;
        $horas_trabalhadas = floor($minutos_trabalhados / 60);
        $minutos_trabalhados_resto = $minutos_trabalhados % 60;

        // Calcular minutos de pausa para cada registro
        $minutos_pausa = (strtotime($registro->fim_pausa) - strtotime($registro->inicio_pausa)) / 60;
        $total_minutos_pausa += $minutos_pausa;
        $horas_pausa = floor($minutos_pausa / 60);
        $minutos_pausa_resto = $minutos_pausa % 60;


        // Adicionar linhas à tabela
        $html .= '<tr>';
        $html .= '<td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">' . esc_html($dia_semana) . '</td>';
        $html .= '<td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">' . esc_html($data_formatada) . '</td>';
        $html .= '<td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">' . esc_html($registro->primeiro_login) . '</td>';
        $html .= '<td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">' . esc_html($registro->ultimo_logout) . '</td>';
   $html .= '<td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">' . $horas_trabalhadas . 'h ' . $minutos_trabalhados_resto . 'min</td>';
    $html .= '<td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">' . $horas_pausa . 'h ' . $minutos_pausa_resto . 'min</td>';
        $html .= '</tr>';
    }

    // Fim do HTML da tabela 
    $html .= '</table>';

      // Converter total de minutos em horas e minutos para o total
    $horas_trabalhadas_total = floor($total_minutos_trabalhados / 60);
    $minutos_trabalhados_total = $total_minutos_trabalhados % 60;
    $horas_pausa_total = floor($total_minutos_pausa / 60);
    $minutos_pausa_total = $total_minutos_pausa % 60;

    // Calcular horas restantes ou excedentes em relação a 220 horas
    $horas_para_220 = 220 * 60; // 220 horas em minutos
    $horas_restantes = $horas_para_220 - $total_minutos_trabalhados;
    $status_horas = $horas_restantes >= 0 ? 'horas restantes' : 'horas excedentes';
    $horas_restantes_abs = abs($horas_restantes);
    $horas_restantes_formatadas = floor($horas_restantes_abs / 60) . 'h ' . ($horas_restantes_abs % 60) . 'min';

    // Adicionar totais ao HTML
    $html .= '<div>Total de horas trabalhadas: ' . $horas_trabalhadas_total . 'h ' . $minutos_trabalhados_total . 'min</div>';
    $html .= '<div>Total de horas de pausa: ' . $horas_pausa_total . 'h ' . $minutos_pausa_total . 'min</div>';
    $html .= '<div>Você tem ' . $horas_restantes_formatadas . ' ' . $status_horas . ' em relação às 220 horas mensais.</div>';

     
    echo $html;
    wp_die();
}

function shortcode_registrar_pausa() {
    $user = wp_get_current_user();

    // Verifica se o usuário está logado
    if ($user->ID == 0) {
        return 'Você precisa estar logado para usar esta função.';
    }

    // Gera os botões para pausar e voltar da pausa
    return '<button id="pausar-btn">Pausar</button>
            <button id="voltar-pausa-btn" style="display:none;">Voltar da Pausa</button>
            <div id="resposta-pausa"></div>';
}
add_shortcode('bater_ponto', 'shortcode_registrar_pausa');

function pausar_ajax_handler() {
    global $wpdb;
    $user = wp_get_current_user();
    $data_atual = current_time('Y-m-d');
    $hora_pausa = current_time('H:i:s');

    // Atualiza a tabela com o horário de início da pausa
    $wpdb->update(
        $wpdb->prefix . 'carga_horaria',
        array('inicio_pausa' => $hora_pausa),
        array('user_id' => $user->ID, 'data' => $data_atual),
        array('%s'),
        array('%d', '%s')
    );

    echo "Pausado às: $hora_pausa";
    wp_die();
}


function voltar_pausa_ajax_handler() {
    global $wpdb;
    $user = wp_get_current_user();
    $data_atual = current_time('Y-m-d');
    $hora_retorno = current_time('H:i:s');

    // Atualiza a tabela com o horário de fim da pausa
    $wpdb->update(
        $wpdb->prefix . 'carga_horaria',
        array('fim_pausa' => $hora_retorno),
        array('user_id' => $user->ID, 'data' => $data_atual),
        array('%s'),
        array('%d', '%s')
    );

    echo "Retorno da pausa às: $hora_retorno";
    wp_die();
}


add_action('wp_ajax_pausar', 'pausar_ajax_handler');
add_action('wp_ajax_voltar_pausa', 'voltar_pausa_ajax_handler');

function shortcode_encerrar_expediente() {
    // Verifica se o usuário está logado
    if (!is_user_logged_in()) {
        return 'Você precisa estar logado para encerrar o expediente.';
    }

    // Gera o botão para encerrar expediente
    return '<button id="encerrar-expediente-btn">Encerrar Expediente</button>';
}
add_shortcode('encerrar_expediente', 'shortcode_encerrar_expediente');

function encerrar_expediente_ajax_handler() {
    global $wpdb;
    $user = wp_get_current_user();
    $hora_atual = current_time('H:i:s');
    $data_atual = current_time('Y-m-d');
    $tabela = $wpdb->prefix . 'carga_horaria';

    // Atualiza a tabela com o horário do último logout
    $wpdb->update(
        $tabela,
        array('ultimo_logout' => $hora_atual),
        array('user_id' => $user->ID, 'data' => $data_atual),
        array('%s'),
        array('%d', '%s')
    );

    wp_die();
}
add_action('wp_ajax_encerrar_expediente', 'encerrar_expediente_ajax_handler');



function bater_ponto_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('pausa-script', plugins_url('pausa.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('pausa-script', 'ajaxurl', admin_url('admin-ajax.php'));
}
add_action('wp_enqueue_scripts', 'bater_ponto_scripts');


function shortcode_historico_usuario() {
    if (!is_user_logged_in()) {
        return 'Você precisa estar logado para ver o histórico.';
    }

 ob_start(); // Iniciar buffer de saída

    // Formulário de filtro
    ?>
    <form id="filtro-historico-form">
        <label for="data_inicio">Data Início:</label>
        <input type="date" id="data_inicio" name="data_inicio">

        <label for="data_fim">Data Fim:</label>
        <input type="date" id="data_fim" name="data_fim">

        <button type="button" id="filtro-historico-btn">Filtrar</button>
    </form>

    <div id="tabela-historico"></div>

    <script>
        jQuery(document).ready(function($) {
            $('#filtro-historico-btn').on('click', function() {
                var dataInicio = $('#data_inicio').val();
                var dataFim = $('#data_fim').val();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'buscar_historico_usuario',
                        data_inicio: dataInicio,
                        data_fim: dataFim
                    },
                    success: function(response) {
                        $('#tabela-historico').html(response);
                    }
                });
            });
        });
    </script>
    <?php

    return ob_get_clean(); // Retorna o conteúdo do buffer


    // Gera o botão para mostrar o histórico
    return '<button id="mostrar-historico-btn">Ver Histórico</button>
            <div id="popup-historico" style="display:none;">
                <div id="conteudo-historico"></div>
                <button id="fechar-historico-btn">Fechar</button>
            </div>';
}
add_shortcode('historico_usuario', 'shortcode_historico_usuario');

function buscar_historico_usuario_ajax_handler() {
    global $wpdb;
    $user = wp_get_current_user();
    $tabela = $wpdb->prefix . 'carga_horaria';

   
    // Receber as datas do filtro
    $data_inicio = isset($_POST['data_inicio']) ? $_POST['data_inicio'] : '';
    $data_fim = isset($_POST['data_fim']) ? $_POST['data_fim'] : '';

    // Se as datas do filtro não forem definidas, use os últimos 7 dias como padrão
    if (empty($data_inicio) || empty($data_fim)) {
        $data_atual = current_time('Y-m-d');
        $data_inicio = date('Y-m-d', strtotime('-7 days', strtotime($data_atual)));
        $data_fim = $data_atual;
    }

    $query = $wpdb->prepare(
        "SELECT * FROM $tabela WHERE user_id = %d AND data BETWEEN %s AND %s ORDER BY data DESC",
        $user->ID, $data_inicio, $data_fim
    );
    $registros = $wpdb->get_results($query);



    
      $total_horas_trabalhadas = 0;

    // Início do HTML da tabela
    $html = '<table style="width: 100%; border-collapse: collapse;">';
    $html .= '<tr style="background-color: #f2f2f2;">';
    $html .= '<th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Dia</th>';
    $html .= '<th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Data</th>';
    $html .= '<th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Horário de Entrada</th>';
    $html .= '<th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Horário de Saída</th>';
    $html .= '<th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Horas Trabalhadas</th>';
    $html .= '<th style="border: 1px solid #dddddd; text-align: left; padding: 8px;">Horas de Pausa</th>';
    $html .= '</tr>';

 

         // Iterar sobre os registros
    foreach ($registros as $registro) {
      
      
        // Formatar a data e obter o dia da semana
        $data_formatada = date_i18n('d/m/Y', strtotime($registro->data));
        $dia_semana = date_i18n('l', strtotime($registro->data));

          // Calcular minutos trabalhados para cada registro
        $minutos_trabalhados = (strtotime($registro->ultimo_logout) - strtotime($registro->primeiro_login)) / 60;
        $total_minutos_trabalhados += $minutos_trabalhados;
        $horas_trabalhadas = floor($minutos_trabalhados / 60);
        $minutos_trabalhados_resto = $minutos_trabalhados % 60;

        // Calcular minutos de pausa para cada registro
        $minutos_pausa = (strtotime($registro->fim_pausa) - strtotime($registro->inicio_pausa)) / 60;
        $total_minutos_pausa += $minutos_pausa;
        $horas_pausa = floor($minutos_pausa / 60);
        $minutos_pausa_resto = $minutos_pausa % 60;


        // Adicionar linhas à tabela
        $html .= '<tr>';
        $html .= '<td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">' . esc_html($dia_semana) . '</td>';
        $html .= '<td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">' . esc_html($data_formatada) . '</td>';
        $html .= '<td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">' . esc_html($registro->primeiro_login) . '</td>';
        $html .= '<td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">' . esc_html($registro->ultimo_logout) . '</td>';
   $html .= '<td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">' . $horas_trabalhadas . 'h ' . $minutos_trabalhados_resto . 'min</td>';
    $html .= '<td style="border: 1px solid #dddddd; text-align: left; padding: 8px;">' . $horas_pausa . 'h ' . $minutos_pausa_resto . 'min</td>';
        $html .= '</tr>';
    }

    // Fim do HTML da tabela 
    $html .= '</table>';

      // Converter total de minutos em horas e minutos para o total
    $horas_trabalhadas_total = floor($total_minutos_trabalhados / 60);
    $minutos_trabalhados_total = $total_minutos_trabalhados % 60;
    $horas_pausa_total = floor($total_minutos_pausa / 60);
    $minutos_pausa_total = $total_minutos_pausa % 60;

    // Calcular horas restantes ou excedentes em relação a 220 horas
    $horas_para_220 = 220 * 60; // 220 horas em minutos
    $horas_restantes = $horas_para_220 - $total_minutos_trabalhados;
    $status_horas = $horas_restantes >= 0 ? 'horas restantes' : 'horas excedentes';
    $horas_restantes_abs = abs($horas_restantes);
    $horas_restantes_formatadas = floor($horas_restantes_abs / 60) . 'h ' . ($horas_restantes_abs % 60) . 'min';

    // Adicionar totais ao HTML
    $html .= '<div>Total de horas trabalhadas: ' . $horas_trabalhadas_total . 'h ' . $minutos_trabalhados_total . 'min</div>';
    $html .= '<div>Total de horas de pausa: ' . $horas_pausa_total . 'h ' . $minutos_pausa_total . 'min</div>';
    $html .= '<div>Você tem ' . $horas_restantes_formatadas . ' ' . $status_horas . ' em relação às 220 horas mensais.</div>';

     
    echo $html;
    wp_die();
}
add_action('wp_ajax_buscar_historico_usuario', 'buscar_historico_usuario_ajax_handler');

