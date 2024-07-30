<?php

function revenda() {
    global $revenda_loaded;
    $revenda_loaded = true; // Marca que o shortcode está sendo usado.

    ob_start(); // Inicia o buffer de saída
    ?>
    <div class="layout">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
        <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet">
        <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

        <style>
        
        
        
        
        #meu-plugin-icone-sino {
    position: relative;
    cursor: pointer;
    display: inline-block;
}

#meu-plugin-popup-notificacoes {
    display: none; /* Começa escondido */
    position: absolute;
    bottom: 50px; /* ajustado para abrir para cima */
    right: 0;
    width: 300px;
    background: white;
    border: 1px solid #ccc;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    z-index: 1000; /* Certifique-se de que o popup esteja acima de outros elementos */
}

#meu-plugin-popup-notificacoes.show {
    display: block; /* Mostra o popup quando necessário */
}


#meu-plugin-popup-notificacoes {
    position: absolute;
    top: 50px; /* ajuste conforme necessário */
    right: 0;
    width: 300px;
    background: white;
    border: 1px solid #ccc;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    z-index: 1000;
}

.nav-slot-bottom {
    flex-basis: 15%;
    text-align: center;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

          .main {
    margin: 0px;
    height: 300vh;
    background-image: linear-gradient(to top, #cfd9df 0%, #e2ebf0 100%);
}

.bg-white {
    background: white;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    width: 60px;
    height: 100vh;
    filter: drop-shadow(0 0 5px rgba(31, 31, 31, 0.1));
    position: fixed;
    left: 0;
    top: 0;
}

.nav-slot {
    text-align: center;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nav-link {
    width: 45px;
    height: 45px;
    line-height: 1.5;
    align-items: center;
    color: #aab2bd;
    border-radius: 50rem;
    padding: 0.5rem 0.5rem;
    transition: 0.3s all;
    text-decoration: none;
}

.nav-link:hover {
    transition: 0.3s all;
    background: rgba(31, 31, 31, 0.1);
}

.curve {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23fff'%3E%3Cpath d='M99,0A36.33,36.33,0,0,0,70,15,25,25,0,0,1,30,15,36.33,36.33,0,0,0,1,0H0V50H100V0Z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-size: cover;
    background-position-y: center;
    width: 60px;
}

svg {
    height: 100%;
}

i {
    font-size: 18px;
}

.bottom-nav {
    display: none;
    justify-content: center;
    flex-direction: row;
    width: 100%;
    height: 50px;
    filter: drop-shadow(0 0 5px rgba(31, 31, 31, 0.1));
    position: fixed;
    bottom: 0;
    background: white;
}



.floating-button {
    position: fixed;
    width: 50px;
    height: 50px;
    line-height: 3;
    text-align: center;
    color: red;
    border-radius: 50%;
    bottom: 35px;
    background: linear-gradient(90deg, rgba(255, 255, 255, 1) 0%, rgba(255, 219, 217, 1) 35%, rgb(158 21 21 / 43%) 100%);
    box-shadow: 0 10px 6px -6px #777;
    z-index: 1;
    transition: 0.3s all;
}

.floating-button:hover {
    bottom: 40px;
    transition: 0.3s all;
}

.round-top-left {
    border-top-left-radius: 15px;
}

.round-top-right {
    border-top-right-radius: 15px;
}

@media screen and (max-width: 768px) {
    .sidebar-nav {
        display: none;
    }
    .bottom-nav {
        display: flex;
    }
  

@media screen and (max-width:480px){
    svg {
        width: 25px;
        margin-top: -5px;
    }
 
}



.empresa-logo {
    width: 50px; /* Ajuste o tamanho conforme necessário */
    height: 50px; /* Ajuste o tamanho conforme necessário */
    border-radius: 20px; /* Borda arredondada */
    transition: all 0.3s ease;
}

.floating-button img.empresa-logo {
    width: 100%; /* O logo ocupa todo o botão flutuante */
    height: auto; /* Mantém a proporção da imagem */
}

.floating-button:hover .empresa-logo {
    transform: scale(1.1); /* Aumenta o logo ao passar o mouse */
    transition: all 0.3s ease;
}


/* Estilos para o popup de pesquisa */
.search-popup {
    display: none; /* Inicialmente escondido */
    position: fixed;
    z-index: 1001;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5); /* Fundo escuro com opacidade */
}

.search-popup-content {
    background-color: #fff;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 300px;
    border-radius: 10px;
}

.search-popup-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.search-popup-close:hover,
.search-popup-close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

.search-form input[type="text"] {
    width: 80%;
    padding: 10px;
    margin: 10px 0;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 5px;
}

.search-form button {
    padding: 10px 15px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.search-form button i {
    margin: 0;
}

@media screen and (max-width: 480px) {
    .search-popup-content {
        width: 90%;
        margin-top: 30%;
    }

    .search-form input[type="text"] {
        width: 40%;
        font-size:10px;
        margin-left:-25px;
    }
    
    a.next.page-numbers {
    margin-top: 3520px;
    color: black;
}
}


.cart-popup {
    display: none;
    position: absolute;
    top: 50px;
    right: 0;
    width: 300px;
    background: white;
    border: 1px solid #ccc;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    z-index: 1000;
}

.cart-popup.show {
    display: block;
}

.widget_shopping_cart .remove {
    background: none;
    border: none;
    color: #ff0000;
    cursor: pointer;
    font-size: 16px;
}

.widget_shopping_cart .remove:hover {
    color: #cc0000;
}



        </style>

        <!-- Sidebar para Desktop -->
        <aside class="sidebar sidebar-nav" id="sidebar">
            <div class="sidebar-header">
                <img src="https://i0.wp.com/inovetime.com.br/wp-content/uploads/cropped-LOGO-FAIXA-FRANQUIA-inv-2.png?resize=1320%2C386&ssl=1" alt="Logo" class="sidebar-logo" id="sidebar-logo">
            </div>

            <!-- Início do menu -->
            <?php include(plugin_dir_path(__FILE__) . '../menu/menu_fornecedor.php'); ?>
            <!-- Fim do menu -->

            <div class="sidebar-footer">
                <i class="fas fa-arrow-left menu-toggle" id="menu-toggle"></i>
            </div>
        </aside>

        <!-- Informações do usuário -->
        <?php 
        global $wpdb;
        $current_user = wp_get_current_user();
        ?>

        <?php if (is_user_logged_in()): // Verifica se o usuário está logado ?>
            <?php
            $user_id = get_current_user_id(); // Obtém o ID do usuário logado
            $user_info = get_userdata($user_id); // Obtém informações do usuário
            $nome = $user_info->display_name; // Nome de exibição do usuário
            $bio = get_user_meta($user_id, 'description', true); // Bio do usuário
            $avatar_url = get_avatar_url($user_id); // URL do avatar do usuário
            ?>
            <!-- Conteúdo Principal -->
            <div class="content-area">
                <!-- Header -->
                <header class="site-header">
                    <div class="main-content">
                        <div class="user-info">
                            <form method="GET" class="search-form">
                                <input type="text" name="pesquisa" placeholder="Digite o nome do produto" value="<?php echo esc_attr($pesquisa_filtro); ?>">
                                <button type="submit"><i class="fas fa-search"></i></button>
                            </form>
                            <div>[usuario_extrato_saque]</div>
                            <img src="<?php echo esc_url($avatar_url); ?>" alt="Foto do Usuário" class="user-photo"/>
                            <div class="user-text">
                                <div class="user-name"><?php echo esc_html($nome); ?></div>
                                <div class="user-bio"><?php echo esc_html($bio); ?></div>
                            </div>
                            <div class="notificar">
                               
                            </div>
                            <div class="carrinho-icon">
    <a class="cart-contents" href="#" title="<?php _e( 'Ver seu carrinho' ); ?>">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-contents-total"><?php echo WC()->cart->get_cart_total(); ?></span>
    </a>
    <div class="cart-popup">
        <div class="widget_shopping_cart_content">
            <?php woocommerce_mini_cart(); ?>
        </div>
    </div>
</div>

                            </div>
                        </div>
                    </div>
                </header>

                <!-- Quadro Kanban -->
                <div>
                    [menu_categorias]
                </div>

                <div class="body5">
                    <div class="banner-desktop">  
                    <div class="mySlides1 fade" id="meuslideunico1">
                        <img id="banner-img" src="https://inovetime.com.br/wp-content/uploads/Fashion-banner-loja-atacado-6.png" style="width:100%">
                    </div> </div>
                    <center>
                     <div class="banner-mobile">  
                    <div class="mySlides fade" id="meuslideunico">
                        <img id="banner-img" src="https://inovetime.com.br/wp-content/uploads/Fashion-banner-loja-atacado-420-x-400-px.png" style="width:100%">
                    </div>
                    
                    
                      <div class="mySlides fade" id="meuslideunico">
                        <img id="banner-img" src="https://inovetime.com.br/wp-content/uploads/Fashion-banner-loja-atacado-420-x-400-px-1.png" style="width:100%">
                    </div>
                    
                      <div class="mySlides fade" id="meuslideunico">
                        <img id="banner-img" src="https://inovetime.com.br/wp-content/uploads/Fashion-banner-loja-atacado-420-x-400-px-2.png" style="width:100%">
                    </div>

 
                      <div class="mySlides fade" id="meuslideunico">
                        <img id="banner-img" src="https://inovetime.com.br/wp-content/uploads/Fashion-banner-loja-atacado-420-x-400-px-3.png" style="width:100%">
                    </div>
                    
                     <div class="mySlides fade" id="meuslideunico">
                        <img id="banner-img" src="https://inovetime.com.br/wp-content/uploads/Fashion-banner-loja-atacado-420-x-400-px-4.png" style="width:100%">
                    </div>
                    
                    
                    
                    </div>
                    </center>
                    
                    
                    <div class="icon-container">
                        <div class="icon-text">
                            <img src="https://cdn.scentbird.com/frontbird2/images-gif/bottles_b62b4b.gif" alt="Perfume Icon" class="img-icon">ESCOLHA SEUS AROMAS
                        </div>
                        <div class="icon-text">
                            <img src="https://cdn.scentbird.com/frontbird2/images-gif/cart_fa7c93.gif" alt="Perfume Icon" class="img-icon">RECEBA SUA FRAGRÂNCIA
                        </div>
                        <div class="icon-text">
                            <img src="https://d335luupugsy2.cloudfront.net/cms/files/62200/1720444830/$uo870ns92a9" alt="Perfume Icon" class="img-icon">MARGEM DE 120% DE MARKUP
                        </div>
                        <div class="icon-text">
                            <img src="https://cdn.scentbird.com/frontbird2/images-gif/case_6b99fd.gif" alt="Perfume Icon" class="img-icon">PULVERIZE, TOQUE E REPITA
                        </div>
                        <div class="icon-text">
                            <img src="https://cdn.scentbird.com/frontbird2/images-gif/handshake_686bd1.gif" alt="Perfume Icon" class="img-icon">COMPRA GARANTIDA
                        </div>
                    </div>
                    <div class="comissao">
                        <div>
                            [todos_perfumes]
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

     <!-- Bottom Navigation para Mobile -->
<div class="bottom-nav">
    
    <div class="nav-slot-bottom bg-white round-top-right">
        <a href="#profile" class="nav-link" title="Profile" style="color: black;">
            <i class="fa-solid fa-user"></i>
        </a>
    </div>
    
  
    
    <div class="nav-slot-bottom bg-white">
        <a href="#home" class="nav-link" style="color: black;">
            <i class="fa-solid fa-house"></i>
        </a>
    </div>
    
    <div class="nav-slot-bottom bg-white">
        <a href="#search" class="nav-link" style="color: black;" id="search-icon">
            <i class="fa-solid fa-magnifying-glass"></i>
        </a>
    </div>

    <!-- Popup de Pesquisa -->
    <div id="search-popup" class="search-popup">
        <div class="search-popup-content">
            <span class="search-popup-close">&times;</span>
            <form method="GET" class="search-form">
                <input type="text" name="pesquisa" placeholder="Digite o nome do produto" value="<?php echo esc_attr($pesquisa_filtro); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <div class="nav-slot-bottom curve">
        <a href="#logo" role="button" class="floating-button">
            <img src="https://i0.wp.com/inovetime.com.br/wp-content/uploads/2022/08/cropped-logo-insta.png?w=1200&ssl=1" alt="Logo da Empresa" class="empresa-logo">
        </a>
    </div>

    <div class="nav-slot-bottom bg-white">
        [icone_sino]
    </div>

 
    
    
       <!-- Ícone de Calculadora -->
    <div class="nav-slot-bottom bg-white round-top-right">
        <a href="#calculator" class="nav-link" title="Calculator" style="color: black;">
            <i class="fa-solid fa-calculator"></i>
        </a>
    </div>
    
      
    <!-- Ícone de Carteira -->
    <div class="nav-slot-bottom bg-white round-top-left">
        <a href="https://inovetime.com.br/comissao/" class="nav-link" style="color: black;">
            <i class="fa-solid fa-wallet"></i>
        </a>
    </div>

</div>



    <style>
        .menu-botao.pressed {
            background-color: black; /* Cor de fundo quando pressionado */
            color: white; /* Cor do texto quando pressionado */
            /* Adicione outros estilos que desejar */
        }
    </style>
    <script>
        
        jQuery(document).ready(function($) {
    // Toggle cart popup
    $('.cart-contents').on('click', function(event) {
        event.preventDefault();
        $('.cart-popup').toggleClass('show');
    });

    // Hide cart popup when clicking outside
    $(document).on('click', function(event) {
        if (!$(event.target).closest('.carrinho-icon').length) {
            $('.cart-popup').removeClass('show');
        }
    });

    // Ensure mini cart updates with AJAX
    $(document.body).on('wc_fragments_refreshed', function() {
        $.get(wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'get_refreshed_fragments'), function(fragments) {
            $.each(fragments.fragments, function(key, value) {
                $(key).replaceWith(value);
            });
        });
    });
});

        
        jQuery.noConflict();
(function($) {
    $(document).ready(function() {
        var $searchPopup = $('#search-popup');
        var $searchIcon = $('#search-icon');
        var $searchClose = $('.search-popup-close');

        $searchIcon.on('click', function(event) {
            event.preventDefault();
            $searchPopup.show();
        });

        $searchClose.on('click', function() {
            $searchPopup.hide();
        });

        $(window).on('click', function(event) {
            if ($(event.target).is($searchPopup)) {
                $searchPopup.hide();
            }
        });
    });
})(jQuery);

    </script>
 
    
    <?php
    return ob_get_clean(); // Retorna o conteúdo do buffer e finaliza o buffer
}

// Adiciona o shortcode [revenda] que pode ser usado em posts e páginas
add_shortcode('revenda', 'revenda');



add_filter('woocommerce_add_to_cart_fragments', 'woocommerce_header_add_to_cart_fragment');


?>
