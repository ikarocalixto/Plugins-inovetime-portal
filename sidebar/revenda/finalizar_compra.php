<?php

function finalizar_compra() {
    global $finalizar_compra_loaded;
    $finalizar_compra_loaded = true; // Marca que o shortcode está sendo usado.

    ob_start(); // Inicia o buffer de saída
    ?>
    <div class="layout">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
        <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet">
        <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

        <style>
        
        
        
        
      
@media screen and (max-width: 768px) {
    .sidebar-nav {
        display: none;
    }
    .bottom-nav {
        display: flex;
        
    }
    aside#sidebar {
    display: none;
}


}
  

@media screen and (max-width:480px){
    svg {
        width: 25px;
        margin-top: -5px;
    }
 
}


.mwai-trigger.mwai-open-button {
    display: none;
}


        </style>

        <!-- Sidebar para Desktop -->
        <aside class="sidebar sidebar-nav" id="sidebar">
            <div class="sidebar-header">
                <img src="https://i0.wp.com/inovetime.com.br/wp-content/uploads/cropped-LOGO-FAIXA-FRANQUIA-inv-2.png?resize=1320%2C386&ssl=1" alt="Logo" class="sidebar-logo" id="sidebar-logo">
            </div>

         <!-- começo do menu -->
<?php
  // incluindo o menu do franqueado
include( plugin_dir_path( __FILE__ ) . '../menu/menu.php' );

?>
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
                <div class="slideshow-container">
  <div class="mySlides fade" id="meuslideunico">
    <img src="" style="width:100%">
  </div>


           
                    <div class="comissao">
                        <div >
                        [woocommerce_checkout]
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

// Adiciona o shortcode [finalizar_compra] que pode ser usado em posts e páginas
add_shortcode('finalizar_compra', 'finalizar_compra');



add_filter('woocommerce_add_to_cart_fragments', 'woocommerce_header_add_to_cart_fragment');



?>
