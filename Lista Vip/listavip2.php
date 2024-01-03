<?php
/**
 * Plugin Name: Meu Plugin de Formulário Avançado
 * Description: Um plugin avançado de formulário para WordPress.
 * Version: 1.0
 * Author: Seu Nome
 */

// Criação da tabela no banco de dados ao ativar o plugin
function criar_tabela_formulario() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'meu_formulario';

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nome tinytext NOT NULL,
      telefone VARCHAR(20), 
        email VARCHAR(255) NOT NULL,
        user_id mediumint(9),
        page_url VARCHAR(255),
        UNIQUE KEY id (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

register_activation_hook( __FILE__, 'criar_tabela_formulario' );

// Adiciona o shortcode
add_shortcode('meu_formulario_avancado', 'exibir_formulario_avancado');

// Função para exibir o formulário
function exibir_formulario_avancado($atts) {
    $atts = shortcode_atts(
        array(
            'user_id' => '0',
        ),
        $atts
    );
    
    $user_id = $atts['user_id'];

    ob_start();
    ?>
    <form id="meu_formulario<?php echo $user_id; ?>" action="" method="post">
        <input type="hidden" name="user_id" value="<?php echo esc_attr($atts['user_id']); ?>">
        <input type="hidden" name="page_url" value="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
         <input type="hidden" name="form_id" value="<?php echo $user_id; ?>" />
         <div class="input-group">
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome">
        </div>
        <div class="input-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email">
         </div>
         <div  class="input-group" >
        <label for="telefone">Telefone:</label>
        <input type="text" id="telefone" name="telefone">
        </div>
        <input class="submit-button" type="submit" value="Enviar">
    </form>


    <style>
        /* Estilo base para o formulário */
.custom-form {
    width: 100%;
    max-width: 400px;
    background-color: #fff;
    padding: 25px 30px;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.custom-form:hover {
    transform: translateY(-5px);
    box-shadow: 0 7px 25px rgba(0, 0, 0, 0.15);
}

/* Estilo para os grupos de entrada */
.input-group {
    margin-bottom: 25px;
}

.input-group:last-child {
    margin-bottom: 0;
}

.input-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

/* Estilo para os campos de entrada */
.input-group input[type="text"],
.input-group input[type="email"],
.input-group input[type="tel"] {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 16px;
    transition: border-color 0.3s, box-shadow 0.3s;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.input-group input[type="text"]:focus,
.input-group input[type="email"]:focus,
.input-group input[type="tel"]:focus {
    border-color: #007BFF;
    outline: none;
    box-shadow: 0 3px 8px rgba(0, 123, 255, 0.25);
}

/* Estilo para o botão de envio */
.submit-button {
    cursor: pointer;
    background-color: #007BFF;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 12px 20px;
    font-size: 16px;
    transition: background-color 0.3s, transform 0.3s;
    display: block;
    width: 100%;
    text-align: center;
}

.submit-button:hover {
    background-color: #0056b3;
    transform: translateY(-2px);
}

.submit-button:active {
    transform: translateY(0);
}

    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('meu_formulario_avancado', 'exibir_formulario_avancado');



// Captura o envio do formulário
add_action('init', 'processar_formulario_avancado');



function processar_formulario_avancado() {
    global $wpdb;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome']) && isset($_POST['email']) && isset($_POST['telefone']) && isset($_POST['form_id'])) {
        $nome = sanitize_text_field($_POST['nome']);
        $email = sanitize_email($_POST['email']);
        $telefone = sanitize_text_field($_POST['telefone']);
        $user_id = intval($_POST['user_id']);
        $page_url = esc_url_raw($_POST['page_url']);
        $form_id = sanitize_text_field($_POST['form_id']);

        $table_name = $wpdb->prefix . 'meu_formulario';
        $wpdb->insert(
            $table_name,
            array(
                'nome' => $nome,
                'email' => $email,
                'telefone' => $telefone,
                'user_id' => $user_id,
                'page_url' => $page_url,
                'form_id' => $form_id
            )
        );


        // Verifique se a inserção foi bem-sucedida e retorne um sinalizador
        if ($wpdb->insert_id) {
            wp_send_json_success(['success' => true]);
        } else {
            wp_send_json_error(['success' => false]);
        }
    }
}





// Adiciona o shortcode para estatísticas do usuário
add_shortcode('meu_formulario_estatisticas', 'exibir_estatisticas_usuario');

function inserir_notificacao() {
    global $wpdb;

    error_log("Inserindo notificação..."); // Adicionar log

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

    if ($user_id > 0) {
        $tabela_notificacoes = $wpdb->prefix . 'meu_plugin_notificacoes';

        $result = $wpdb->insert(
            $tabela_notificacoes,
            array(
                'user_id' => $user_id,
                'mensagem' => 'Nova conversão em sua Lista Vip!',
                'imagem' => '',
                'url_redirecionamento' => 'https://inovetime.com.br/lista-vip/',
                'data_envio' => current_time('mysql'),
                'lida' => 0
            )
        );

        if ($result) {
            error_log("Notificação inserida com sucesso!"); // Adicionar log
            wp_send_json_success(array('message' => 'Notificação inserida com sucesso!'));
        } else {
            error_log("Erro ao inserir notificação: " . $wpdb->last_error); // Adicionar log
            wp_send_json_error(array('message' => 'Falha ao inserir notificação. Detalhes: ' . $wpdb->last_error));
        }
    } else {
        error_log("user_id não informado ou é zero."); // Adicionar log
        wp_send_json_error();
    }
}



add_action('wp_ajax_inserir_notificacao', 'inserir_notificacao');
add_action('wp_ajax_nopriv_inserir_notificacao', 'inserir_notificacao');



// dashboard do usuario da lista vip
 function exibir_estatisticas_usuario() {
    // Adiciona um log para verificar se a função foi chamada
    error_log("Função exibir_estatisticas_usuario foi chamada");

    if (is_user_logged_in()) {
        global $wpdb;
        $user_id = get_current_user_id();
        $table_name_form = $wpdb->prefix . 'meu_formulario';
        $table_name_view = $wpdb->prefix . 'visualizacoes';

        $total_inscricoes = $email_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT email) FROM $table_name_form WHERE user_id = %d", $user_id));

        
        $form_id = "form_id" . $user_id; // Corrigido para $user_id

        $total_visualizacoes = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM wp_visualizacoes WHERE form_id = %s", $user_id)); // Corrigido para $table_name_view

        // Evitar divisão por zero
        $taxa_conversao = $total_visualizacoes > 0 ? ($total_inscricoes / $total_visualizacoes) * 100 : 0;
        
        $estatisticas = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name_form WHERE user_id = %d", $user_id));
        
        // Depuração
        error_log("Usuário está logado, ID: " . $user_id);
        error_log("Total de inscrições: " . $total_inscricoes);
        error_log("Total de visualizações: " . $total_visualizacoes);
        error_log("Taxa de conversão: " . $taxa_conversao . "%");
    

        ob_start();
        echo '<h2 class="custom-dashboard-title">Resumo de sua campanha - LISTA VIP</h2>';



        echo '<div class="dashboard-cards">';
        echo '<div class="card"><h3>Total de Leads</h3><p class="big-number">' . $total_inscricoes . '</p></div>';
        // Adiciona a tarja preta com a seta aqui
echo '<div class="black-stripe"><span class="arrow">&#8594;</span></div>';

        echo '<div class="card"><h3>Total de visualizações</h3><p class="big-number">' . $total_visualizacoes . '</p></div>';
        echo '<div class="black-stripe"><span class="arrow">&#8594;</span></div>';
        echo '<div class="card"><h3>Taxa de conversão</h3><p class="big-number">' . number_format($taxa_conversao, 2) . '%</p></div>';
        echo '<div class="black-stripe"><span class="arrow">&#8594;</span></div>';
        echo '</div>';


      // detalhes das conversão
      


        echo '<center><button id="showDetails">Ver Lista Vip</button></center>';
        echo '<div id="details" style="display:none;">';
        if ($total_inscricoes > 0) {
            echo '<table border="1">';
            echo '<tr><th>Nome</th><th>Email</th><th>Página</th><th>Telefone</th></tr>';
            foreach ($estatisticas as $stat) {
                echo '<tr>';
                echo '<td>' . esc_html($stat->nome) . '</td>';
                echo '<td>' . esc_html($stat->email) . '</td>';
                echo '<td>' . esc_html($stat->page_url) . '</td>';
                echo '<td>' . esc_html($stat->telefone) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p>Nenhuma conversão ainda.</p>';
        }
        echo '</div>';

        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                var showBtn = document.getElementById('showDetails');
                var details = document.getElementById('details');
                showBtn.addEventListener('click', function() {
                    if (details.style.display === 'none') {
                        details.style.display = 'block';
                    } else {
                        details.style.display = 'none';
                    }
                });
            });



        </script>";


          echo "<style>




     /* Estilos Avançados */
body {
    font-family: 'Helvetica', sans-serif;
    background-color: #F0F1F6;
    color: #333;
}

.custom-dashboard-title {
    font-size: 32px;
    text-align: center;
    margin: 40px 0;
    color: #4A5568;
}

.dashboard-cards {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-bottom: 40px;
    margin-left: 150px;
    margin-right: 151px;

}

.cardAPP  {
    background: linear-gradient(to bottom, #ffffff 0%, #f3f4f6 100%);
    border-radius: 20px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    overflow: hidden;
    width: 200px;
    text-align: center;
    padding: 20px;
}
.card {
    background: linear-gradient(to bottom, #ffffff 0%, #f3f4f6 100%);
    border-radius: 20px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    overflow: hidden;
    width: 200px;
    text-align: center;
    padding: 20px;
}

.big-number {
    font-size: 40px;
    color: #2D3748;
    margin-bottom: 10px;
}

.black-stripe {
    width: 70px;
    height: 100%;
    background: #000;
    display: flex;
    align-items: center; /* Alinha verticalmente */
    justify-content: center; /* Alinha horizontalmente */
    border-radius: 12px;
    margin: auto; /* Adicionado para centralizar no layout flexível */
}

.arrow {
    color: white;
    font-size: 25px; /* Aumenta o tamanho da seta */
    text-align: center; /* Certifique-se de que o ícone da seta está centralizado */
}
.card, h3 {
    font-size: 20px;
}

#showDetails {
    background-color: #2C7A7B;
    color: white;
    padding: 14px 28px;
    border-radius: 20px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

#showDetails:hover {
    background-color: #285E61;
}

#details {
    max-width: 1200px;
    margin: auto;
    background: #ffffff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 16px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}
.swiper-container {
    width: 100% !important;
    padding-top: 50px !important;
    padding-bottom: 50px !important;
}

.swiper-slide {
    background-position: center !important;
    background-size: cover !important;
    width: 300px !important;
    height: 300px !important;
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    margin-left: -100px;
}




        </style>";




        return ob_get_clean();
    } else {
        return 'Você precisa estar logado para ver as estatísticas.';
    }
}

add_action('init', 'criar_tabela_visualizacoes'); // Ou 'plugins_loaded'

function criar_tabela_visualizacoes() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'visualizacoes';

    // Verifique se a tabela já existe; retorne se sim.
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
        return;
    }

    $sql = "CREATE TABLE wp_visualizacoes (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT,
    user_ip VARCHAR(45),
    timestamp DATETIME,
    PRIMARY KEY (id)
);";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}


register_activation_hook( __FILE__, 'criar_tabela_visualizacoes' );


// Registra visualizações
function registrar_visualizacao() {
    global $wpdb;

    // Logar o início da função.
    error_log("Função registrar_visualizacao iniciada.");

    // Verifica se form_id está definido.
    $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : null;

    if ($form_id === null) {
        // Logar a falta de form_id e terminar a execução.
        error_log("ID do formulário não fornecido.");
        die();
    }

    // Logar o valor de form_id.
    error_log("form_id: $form_id");

    $current_user_id = get_current_user_id();

    // Logar o valor de current_user_id.
    error_log("current_user_id: $current_user_id");

    $current_time = current_time('mysql');
    // Logar o valor de current_time.
    error_log("current_time: $current_time");

    // Tentativa de inserção no banco de dados.
    $result = $wpdb->insert(
        'wp_visualizacoes',
        array(
            'user_id' => $current_user_id,
            'form_id' => $form_id,
            'timestamp' => $current_time
        )
    );

    // Verifica se a inserção foi bem-sucedida.
    if ($result === false) {
        error_log("Falha ao inserir no banco de dados.");
    } else {
        error_log("Inserção bem-sucedida no banco de dados.");
    }

    die();
}





// Inclui JS e CSS para o dashboard
function meu_dashboard_scripts() {
    ?>
    <style>
    .meu-plugin-dashboard {
        border: 1px solid #ccc;
        padding: 20px;
    }
    </style>
   

    <script>

document.addEventListener('DOMContentLoaded', function() {
    console.log("Script iniciado");
    var forms = document.querySelectorAll('form[id^="meu_formulario"]');
    console.log("Formulários encontrados: ", forms.length); // Adicionado para debug
    
    forms.forEach(function(form) {
        var matches = form.id.match(/meu_formulario(\d+)/);

        if (!matches) return;
        
        var form_id = matches[1];

        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                console.log('Visualização registrada para o formulário com ID:', form_id);
            }
        };
        
        xhttp.open("POST", "<?php echo admin_url('admin-ajax.php'); ?>", true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send("action=registrar_visualizacao&form_id=" + form_id);
    });
});
  


    </script>
    
      <?php
}

function mostrar_ultimas_conversoes() {
    if (is_user_logged_in()) {
        global $wpdb;
        $user_id = get_current_user_id();
        $table_name = $wpdb->prefix . 'meu_formulario';
        
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d AND form_id = %d GROUP BY email ORDER BY timestamp DESC", $user_id, $user_id), ARRAY_A);
        
        if($results) {
            echo '<div class="swiper-container"><div class="swiper-wrapper">';
            foreach ($results as $row) {
                $dataFormatada = date('d/m/Y H:i:s', strtotime($row['timestamp']));
                echo '<div class="swiper-slide">';
                echo '<div class="cardAPP">';
                echo '<p><strong>Nome:</strong> ' . esc_html($row['nome']) . '</p>';
                echo '<p><strong>Telefone:</strong> ' . esc_html($row['telefone']) . '</p>';
                echo '<p><strong>Página:</strong> ' . esc_html($row['page_url']) . '</p>';
                echo '<p><strong>Horário:</strong> ' . $dataFormatada . '</p>';
                echo '</div>'; // Fim do card
                echo '</div>'; // Fim do slide
            }
            echo '</div><div class="swiper-pagination"></div><div class="swiper-button-next"></div><div class="swiper-button-prev"></div></div>'; // Fim do container e wrapper
        } else {
            echo '<p>Nenhuma conversão foi encontrada.</p>';
        }
    } else {
        echo '<p>Você precisa estar logado para visualizar as conversões.</p>';
    }
}
add_shortcode('ultimas_conversoes', 'mostrar_ultimas_conversoes');






//script das ultimas conversoes card
function ultimas_conversoes_scripts() {
    wp_enqueue_style('swiper-style', 'https://unpkg.com/swiper/swiper-bundle.min.css');
    wp_enqueue_script('swiper-script', 'https://unpkg.com/swiper/swiper-bundle.min.js', array(), null, true);
    
    wp_add_inline_script('swiper-script', 'document.addEventListener("DOMContentLoaded", function() {
        (function() {
            var container = document.querySelector(".swiper-container");
            if(container) {
                var swiper = new Swiper(container, {
                    slidesPerView: 4,
                    spaceBetween: 30,
                    pagination: {
                        el: ".swiper-pagination",
                        clickable: true,
                    },
                    navigation: {
                        nextEl: ".swiper-button-next",
                        prevEl: ".swiper-button-prev",
                    },
                });
            }
        })();
    });


    ');
}


add_action('wp_enqueue_scripts', 'ultimas_conversoes_scripts');

//daqui pra baixo esta o problema


function meu_plugin_activation() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'redirecionar_form';

    $sql = "CREATE TABLE $table_name (
        form_id mediumint(9) NOT NULL AUTO_INCREMENT,
        nome_formulario varchar(255) NOT NULL,
        redirect_url varchar(255) NOT NULL,
        PRIMARY KEY (form_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'meu_plugin_activation');


function meu_plugin_menu() {
    add_menu_page('Configuração de Formulários', 'Configuração de Formulários', 'manage_options', 'meu_plugin', 'meu_plugin_options');
}
add_action('admin_menu', 'meu_plugin_menu');

function meu_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('form.js', plugin_dir_url(__FILE__) . 'form.js', array('jquery'), null, true); // Certifique-se de que este caminho está correto.
    
    wp_localize_script('form.js', 'meu_script_vars', array(
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'meu_scripts');

function meu_scripts2() {
    wp_enqueue_script('form.js', plugin_dir_url(__FILE__) . 'form.js', array('jquery'), null, true);
    wp_localize_script('form.js', 'my_data', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('my_nonce_string')
    ));
}
add_action('admin_enqueue_scripts', 'meu_scripts'); // Use admin_enqueue_scripts para enfileirar scripts na administração

// esta aqui em cima 

function buscar_url_redirecionamento() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'redirecionar_form';

    // Verificar se form_id está definido
    if (!isset($_POST['form_id'])) {
        wp_send_json_error();
        return;
    }

    // Sanitiza o form_id
    $form_id = sanitize_text_field($_POST['form_id']);
    
    // Busca a URL de redirecionamento no banco de dados
    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE form_id = %s", $form_id));
    
    if ($result) {
        wp_send_json_success(['url' => $result->redirect_url]);
    } else {
        wp_send_json_error();
    }
}

add_action('wp_ajax_buscar_url_redirecionamento', 'buscar_url_redirecionamento');
add_action('wp_ajax_nopriv_buscar_url_redirecionamento', 'buscar_url_redirecionamento');



function meu_plugin_options() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'redirecionar_form';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nome_formulario = sanitize_text_field($_POST['nome_formulario']);
        $redirect_url = esc_url_raw($_POST['redirect_url']);
        $form_id = intval($_POST['form_id']); // Recupera e sanitiza o form_id

        $wpdb->insert($table_name, [
            'nome_formulario' => $nome_formulario,
            'redirect_url' => $redirect_url,
            'form_id' => $form_id, // Adiciona o form_id ao banco de dados
        ]);
    }

    $formularios = $wpdb->get_results("SELECT * FROM $table_name");

    ?>
    <div class="wrap">
        <h2>Gerenciar Formulários</h2>

        <form method="post" action="">
            <h3>Adicionar Novo Formulário</h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="form_id">ID do Formulário</label></th>
                    <td><input name="form_id" type="number" id="form_id" class="small-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="nome_formulario">Nome do Formulário</label></th>
                    <td><input name="nome_formulario" type="text" id="nome_formulario" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="redirect_url">URL de Redirecionamento</label></th>
                    <td><input name="redirect_url" type="url" id="redirect_url" class="regular-text" required></td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Adicionar Formulário">
            </p>
        </form>
<?php if($formularios): ?>
    <h3>Formulários Existentes</h3>
    <style>
        .editable input {
    width: 100%;
    box-sizing: border-box;
}

    </style>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" id="form_id" class="manage-column">ID do Formulário</th>
                <th scope="col" id="name" class="manage-column">Nome do Formulário</th>
                <th scope="col" id="url" class="manage-column">URL de Redirecionamento</th>
                <th scope="col" id="actions" class="manage-column">Ações</th> <!-- Adicionado coluna para ações -->
            </tr>
        </thead>
        <tbody>
            <?php foreach($formularios as $formulario): ?>
                <tr>
                    <td><?= esc_html($formulario->form_id) ?></td>
                    <td id="nome_formulario_<?= $formulario->form_id ?>" class="editable" data-type="nome_formulario" data-id="<?= $formulario->form_id ?>">
                        <?= esc_html($formulario->nome_formulario) ?>
                    </td>
                    <td id="redirect_url_<?= $formulario->form_id ?>" class="editable" data-type="redirect_url" data-id="<?= $formulario->form_id ?>">
                        <?= esc_url($formulario->redirect_url) ?>
                    </td>
                    <td>
                        <button class="button edit-button" data-id="<?= $formulario->form_id ?>">Editar</button> <!-- Adicionado botão de edição -->
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>

    <?php
}

//salvar o formulario 
function salvar_formulario() {
    check_ajax_referer('meu-plugin-nonce', '_ajax_nonce'); // Verificar o nonce para segurança

    global $wpdb;
    $table_name = $wpdb->prefix . 'redirecionar_form';
    
    // Sanitizar e salvar os dados do formulário
    $id = intval($_POST['id']);
    $nome_formulario = sanitize_text_field($_POST['nome_formulario']);
    $redirect_url = esc_url_raw($_POST['redirect_url']);

    $updated = $wpdb->update($table_name, ['nome_formulario' => $nome_formulario, 'redirect_url' => $redirect_url], ['form_id' => $id]);

    if ($updated) {
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
}
add_action('wp_ajax_salvar_formulario', 'salvar_formulario');






