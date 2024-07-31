<?php
/*
Plugin Name: Meu Sidebar Plugin
Plugin URI: http://seusite.com/
Description: Um simples plugin de sidebar para WordPress.
Version: 1.0
Author: Seu Nome
Author URI: http://seusite.com/
*/

function meu_sidebar_enqueue_scripts() {
    wp_enqueue_style('meu-sidebar-style', plugins_url('/style.css', __FILE__));
    wp_enqueue_script('meu-sidebar-script', plugins_url('/script.js', __FILE__), array(), false, true);
}
add_action('wp_enqueue_scripts', 'meu_sidebar_enqueue_scripts');

function meu_sidebar_shortcode() {
  ob_start(); // Inicia o buffer de saída
  ?>
  <div class="layout">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
  <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
  <img src="https://i0.wp.com/inovetime.com.br/wp-content/uploads/cropped-LOGO-FAIXA-FRANQUIA-inv-2.png?resize=1320%2C386&ssl=1" alt="Logo" class="sidebar-logo" id="sidebar-logo">
</div>

  <!-- começo do menu -->
<?php
  // incluindo o menu do franqueado
include( plugin_dir_path( __FILE__ ) . 'menu/menu.php' );

?>
    <!-- Fim do menu -->


<!-- botão para recolher -->

    <div class="sidebar-footer">
    <i class="fas fa-arrow-left menu-toggle" id="menu-toggle"></i>
    </div>
  </aside>
<!-- fim  botão para recolher -->

 <!-- informações do usuarios -->
 <?php  global $wpdb;
    $current_user = wp_get_current_user();
  
    $nome_usuario = $current_user->user_login; // Ou user_nicename, dependendo de como você está salvando

     $table_name = $wpdb->prefix . 'comissao_venda';
    $table_name_solicitacoes = $wpdb->prefix . 'solicitacoes_saque';
    $table_name_comissoes = 'wp_comissoes_especificas';

    $user_id = get_current_user_id();
    if (isset($_GET['selected_franqueado']) && !empty($_GET['selected_franqueado'])) {
        $user_id = intval($_GET['selected_franqueado']);
    }

    $user_info = get_userdata($user_id);
    $nome_usuario = $user_info->user_login;

    // Verificar se o usuário tem uma comissão específica
    $comissao_especifica = $wpdb->get_var($wpdb->prepare("SELECT comissao FROM $table_name_comissoes WHERE user_id = %d", $user_id));
    if ($comissao_especifica === null) {
        $comissao_especifica = 0.164; // Comissão padrão de 16,4%
    }

    $total_vendas = $wpdb->get_var($wpdb->prepare("SELECT SUM(valor_venda) FROM $table_name WHERE user_id = %d AND status NOT IN ('Cancelado', 'cancelado')", $user_id));
    $comissao_real = 0;

    $data_tres_meses_atras = date('Y-m-d', strtotime('-3 months'));
    $data_tres_meses_atras_sql = date('Y-m-d', strtotime($data_tres_meses_atras));

    $comissoes = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d AND data_comissao >= %s ORDER BY data_comissao DESC", $user_id, $data_tres_meses_atras_sql));
    $vendas = $wpdb->get_results($wpdb->prepare("SELECT valor_venda, promo FROM $table_name WHERE user_id = %d AND status NOT IN ('Cancelado', 'cancelado')", $user_id));

    foreach ($vendas as $venda) {
        if (!empty($venda->promo)) {
            $comissao_real += $venda->valor_venda * ($comissao_especifica - 0.0082 * $venda->promo);
        } else {
            $comissao_real += $venda->valor_venda * $comissao_especifica;
        }
    }

    $numero_pedidos = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND status NOT IN ('Cancelado', 'cancelado')", $user_id));
    $total_sacado = $wpdb->get_var($wpdb->prepare("SELECT SUM(valor_solicitado) FROM $table_name_solicitacoes WHERE user_id = %d AND status IN ('Pago', 'Pendente')", $user_id));
    $total_sacado = $total_sacado ? $total_sacado : 0;

    $saldo_disponivel = $comissao_real - $total_sacado;


    ?>
  <?php if (is_user_logged_in()): // Verifica se o usuário está logado ?>
  <?php

 
    $user_id = get_current_user_id(); // Obtém o ID do usuário logado
    $user_info = get_userdata($user_id); // Obtém informações do usuário
    $nome = $user_info->display_name; // Nome de exibição do usuário
    $bio = get_user_meta($user_id, 'description', true); // Bio do usuário
    $avatar_url = get_avatar_url($user_id); // URL do avatar do usuário
  ?>
 <!-- final das informações do usuarios  -->


  <!-- Conteúdo Principal -->
  <div class="content-area">
    <!-- Header -->
    <header class="site-header">
    <div class="main-content">

    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
        <div class="info-card-home1s">
            <div class="card-home1">
               
                <div class="card-home1-content">
                    <h3>Valor Total das Vendas</h3>
                    <p>R$ <?php echo number_format($total_vendas, 2, ',', '.'); ?></p>
                </div>
            </div>
            <div class="card-home1">
 
    <div class="card-home1-content">
        <h3>Saldo Disponível para Saque</h3>
        <p>R$ <?php echo number_format($saldo_disponivel, 2, ',', '.'); ?></p>
    </div>
</div>
      <div class="user-info">
        <img src="<?php echo esc_url($avatar_url); ?>" alt="Foto do Usuário" class="user-photo"/>
        <div class="user-text">
          <div class="user-name"><?php echo esc_html($nome); ?></div>
          <div class="user-bio"><?php echo esc_html($bio); ?></div>
        </div>
        <div class="notificar">
    <div class="notification-icon">[icone_sino]</div>
    </div>
      </div>

    

    </header>
 



    <!-- Banner -->
    <div class="slideshow-container">
  <div class="mySlides fade" id="meuslideunico">
    <img src="https://inovetime.com.br/wp-content/uploads/Banner-outubronov-5.png" style="width:100%">
  </div>

 


  
  <!-- Botões de Navegação -->
  <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
  <a class="next" onclick="plusSlides(1)">&#10095;</a>
</div>


    <!-- Seção de Cards -->
    <div class="card-section">
      <!-- Cards vão aqui -->
      <div class="cartao">[meus_cartoes]</div>
      
      <center>
<div>   [blog_card]</div></center>
      
    </div>
 
  </div>
</div>
<?php endif; ?>

  <?php
  return ob_get_clean(); // Retorna o conteúdo do buffer e finaliza o buffer
} 

// Adiciona o shortcode [meu_sidebar] que pode ser usado em posts e páginas
add_shortcode('meu_sidebar', 'meu_sidebar_shortcode');


function mostrar_informacoes_usuario() {
    $user_id = get_current_user_id(); // Obtém o ID do usuário logado
    if ($user_id == 0) return 'Usuário não está logado.'; // Verifica se o usuário está logado

    // Obtém informações do usuário
    $user_info = get_userdata($user_id);
    $nome = $user_info->display_name; // Nome de exibição do usuário
    $bio = get_user_meta($user_id, 'description', true); // Bio do usuário
    $avatar_url = get_avatar_url($user_id); // URL do avatar do usuário

    // Monta o HTML com as informações do usuário
    $html = '<div class="informacoes-usuario">';
    $html .= '<img src="' . esc_url($avatar_url) . '" alt="Foto do usuário">';
    $html .= '<div class="nome-usuario">' . esc_html($nome) . '</div>';
    $html .= '<div class="bio-usuario">' . esc_html($bio) . '</div>';
    $html .= '</div>';

    return $html;
}

function blog_card_shortcode() {
    ob_start();
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /*PEN STYLES*/
        @charset "UTF-8";
        * {
            box-sizing: border-box;
        }

.blog-card.alt {
    height: 200px;
}
i.fas.fa-phone-alt.icon {
    font-size: 15px;
}
        .blog-card {
            display: flex;
            flex-direction: column;
            margin: 1rem auto;
            box-shadow: 0 3px 7px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.6%;
            background: #fff;
            line-height: 1.4;
            font-family: sans-serif;
            border-radius: 5px;
            overflow: hidden;
            z-index: 0;
        }
        .blog-card a {
            color: inherit;
        }
        .blog-card a:hover {
            color: #5ad67d;
        }
        .blog-card:hover .photo {
            transform: scale(1.3) rotate(3deg);
        }
        .blog-card .meta {
            position: relative;
            z-index: 0;
            height: 300px;
        }
        .blog-card .photo {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background-size: cover;
            background-position: center;
            transition: transform 0.2s;
        }
        .blog-card .details,
        .blog-card .details ul {
            margin: auto;
            padding: 0;
            list-style: none;
        }
        .blog-card .details {
            position: absolute;
            top: 0;
            bottom: 0;
            left: -100%;
            margin: auto;
            transition: left 0.2s;
            background: rgba(0, 0, 0, 0.6);
            color: #fff;
            padding: 10px;
            width: 80%;
            font-size: 0.9rem;
        }
        .blog-card .details a {
            -webkit-text-decoration: dotted underline;
                    text-decoration: dotted underline;
        }
        .blog-card .details ul li {
            display: inline-block;
        }
        .blog-card .details .author:before {
            font-family: FontAwesome;
            margin-right: 10px;
            content: "";
        }
        .blog-card .details .date:before {
            font-family: FontAwesome;
            margin-right: 10px;
            content: "";
        }
        .blog-card .details .tags ul:before {
            font-family: FontAwesome;
            content: "";
            margin-right: 10px;
        }
        .blog-card .details .tags li {
            margin-right: 2px;
        }
        .blog-card .details .tags li:first-child {
            margin-left: -4px;
        }
        .blog-card .description {
            padding: 1rem;
            background: #fff;
            position: relative;
            z-index: 1;
        }
        .blog-card .description h1,
        .blog-card .description h2 {
            font-family: Poppins, sans-serif;
        }
        .blog-card .description h1 {
            line-height: 1;
            margin: 0;
            font-size: 20px;
        }
        .blog-card .description h2 {
            font-size: 1rem;
            font-weight: 300;
            text-transform: uppercase;
            color: #a2a2a2;
            margin-top: 5px;
        }
        .blog-card .description .read-more {
            text-align: right;
        }
        .blog-card .description .read-more a {
            color: #5ad67d;
            display: inline-block;
            position: relative;
        }
        .blog-card .description .read-more a:after {
            content: "";
            font-family: FontAwesome;
            margin-left: -10px;
            opacity: 0;
            vertical-align: middle;
            transition: margin 0.3s, opacity 0.3s;
        }
        .blog-card .description .read-more a:hover:after {
            margin-left: 5px;
            opacity: 1;
        }
        .blog-card p {
            position: relative;
            margin: 1rem 0 0;
        }
        .blog-card p:first-of-type {
            margin-top: 1.25rem;
        }
        .blog-card p:first-of-type:before {
            content: "";
            position: absolute;
            height: 5px;
            background: #5ad67d;
            width: 35px;
            top: -0.75rem;
            border-radius: 3px;
        }
        .blog-card:hover .details {
            left: 0%;
        }
        @media (min-width: 640px) {
            .blog-card {
                flex-direction: row;
                max-width: 900px;
            }
            .blog-card .meta {
                flex-basis: 40%;
                height: auto;
            }
            .blog-card .description {
                flex-basis: 60%;
            }
            .blog-card .description:before {
                transform: skewX(-3deg);
                content: "";
                background: #fff;
                width: 30px;
                position: absolute;
                left: -10px;
                top: 0;
                bottom: 0;
                z-index: -1;
            }
            .blog-card.alt {
                flex-direction: row-reverse;
            }
            .blog-card.alt .description:before {
                left: inherit;
                right: -10px;
                transform: skew(3deg);
            }
            .blog-card.alt .details {
                padding-left: 18px;
            }
        }
    </style>
    <div class="blog-card">
        <div class="meta">
            <div class="photo" style="background-image: url(https://d335luupugsy2.cloudfront.net/cms/files/62200/1645277925/$42lh962h2fl)"></div>
            <ul class="details">
                <li class="author"><a href="#">Lady Griffe</a></li>
                <li class="date">02.2022</li>
                <li class="tags">
                    <ul>
                        <li><a href="#"></a></li>
                        <li><a href="#"></a></li>
                        <li><a href="#"></a></li>
                        <li><a href="#"></a></li>
                    </ul>
                </li>
            </ul>
        </div>
        <div class="description">
            <h1 style="color: black;">Anunciar no Instagram vale a pena? Porque preciso trabalhar minhas redes sociais?</h1>
            <h2> Anunciar no Instagram vale a pena?</h2>
            <p> Saiba 4 razões para investir no processo de vendas pelo Instagram e aumentar a visibilidade de sua loja virtual! </p>
            <p class="read-more">
                <a href="https://franquia.ladygriffeoficial.com.br/anunciar-no-intagram-vale-a-pena">VER DICAS</a>
            </p>
        </div>
    </div>
    <div class="blog-card alt">
        <div class="meta">
            <div class="photo" style="background-image: url(https://d335luupugsy2.cloudfront.net/cms/files/62200/1623961701/$sdq2uxnt7a)"></div>
            <ul class="details">
                <li class="author"><a href="#">Lady Griffe</a></li>
                <li class="date"></li>
                <li class="tags">
                    <ul>
                        <li><a href=""></a></li>
                    </ul>
                </li>
            </ul>
        </div>
        <div class="description">
            <h1 style="color: black">*Está precisando de ajuda?</h1>
            <h2>Clique Aqui</h2>
            <p>O Suporte da Lady Griffe está disponível de segunda a sexta-feira das 9:00 às 17:00 hrs.</p>
            <p class="read-more">   <i class="fas fa-phone-alt icon"></i> (11) 2803-8217</p>
            <p class="read-more">
                <a href="https://inovetime.com.br/ticket-suporte/">Preciso de ajuda</a>
            </p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('blog_card', 'blog_card_shortcode');


// incluindo o painel do treinamento 
include( plugin_dir_path( __FILE__ ) . 'treinamento/kanban-shortcode.php' );


// incluindo area de comissão 
include( plugin_dir_path( __FILE__ ) . 'comissao/comissao.php' );


// incluindo o painel de chamados
include( plugin_dir_path( __FILE__ ) . 'chamados/chamados.php' );


// incluindo o painel de chamados
include( plugin_dir_path( __FILE__ ) . 'chamados/abrir_chamado.php' );



// incluindo o painel de Colaborador
include( plugin_dir_path( __FILE__ ) . 'colaborador/portal_interno_mkt.php' );

// incluindo o painel de Colaborador
include( plugin_dir_path( __FILE__ ) . 'colaborador/portal_interno_adm.php' );
// incluindo o painel de Colaborador
include( plugin_dir_path( __FILE__ ) . 'estoque/estoque.php' );

// incluindo o painel de Colaborador
include( plugin_dir_path( __FILE__ ) . 'Reserva_estoque/Reserva_estoque_page.php' );

// incluindo o painel do fornecedores
include( plugin_dir_path( __FILE__ ) . 'fornecedores/fornecedor.php' );


// incluindo o painel do fornecedores
include( plugin_dir_path( __FILE__ ) . 'fornecedores/meus-produtos-for.php.php' );

// incluindo o painel do fornecedores
include( plugin_dir_path( __FILE__ ) . 'fornecedores/info_loja.php' );

// incluindo o painel do revendedores
include( plugin_dir_path( __FILE__ ) . 'revenda/revenda.php' );

// incluindo o painel do revendedores
include( plugin_dir_path( __FILE__ ) . 'revenda/revenda_brand.php' );

// incluindo o painel do revendedores
include( plugin_dir_path( __FILE__ ) . 'revenda/revenda_importados.php' );


// incluindo o painel do revendedores
include( plugin_dir_path( __FILE__ ) . 'revenda/revenda_arabes.php' );

// incluindo o painel do revendedores
include( plugin_dir_path( __FILE__ ) . 'revenda/revenda_menu.php' );

// incluindo o painel do revendedores
include( plugin_dir_path( __FILE__ ) . 'revenda/revenda_semi_seletivo.php' );

// incluindo o painel do revendedores
include( plugin_dir_path( __FILE__ ) . 'revenda/revenda_hidratante.php' );


// incluindo o painel do revendedores
include( plugin_dir_path( __FILE__ ) . 'revenda/revenda_pop.php' );



// incluindo o painel do revendedores
include( plugin_dir_path( __FILE__ ) . 'revenda/meu_carrinho.php' );



// incluindo o painel do revendedores
include( plugin_dir_path( __FILE__ ) . 'revenda/finalizar_compra.php' );

// incluindo o painel do fornecedores
include( plugin_dir_path( __FILE__ ) . 'fornecedores/meus_pedidos.php' );

  

