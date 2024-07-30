<?php

function revenda_pop() {
    global $revenda_pop_loaded;
    $revenda_pop_loaded = true; // Marca que o shortcode está sendo usado.

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
                                <div class="notification-icon">[icone_sino]</div>
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
                </header>

                <!-- Quadro Kanban -->
                <div>
                    [menu_categorias]
                </div>

                <div class="slideshow-container">
                    <div class="mySlides fade" id="meuslideunico">
                        <img id="banner-img" src="https://inovetime.com.br/wp-content/uploads/Fashion-banner-loja-atacado-8.png" style="width:100%">
                    </div>
                    <div class="icon-container">
                        <div class="icon-text">
                            <img src="https://cdn.scentbird.com/frontbird2/images-gif/bottles_b62b4b.gif" alt="Perfume Icon" class="img-icon">ESCOLHA SEUS
                            AROMAS
                        </div>
                        <div class="icon-text">
                            <img src="https://cdn.scentbird.com/frontbird2/images-gif/cart_fa7c93.gif" alt="Perfume Icon" class="img-icon">RECEBA SUA
                            FRAGRÂNCIA
                        </div>
                        <div class="icon-text">
                            <img src="https://d335luupugsy2.cloudfront.net/cms/files/62200/1720444830/$uo870ns92a9" alt="Perfume Icon" class="img-icon">MARGEM DE 120% DE MARKUP
                        </div>
                        <div class="icon-text">
                            <img src="https://cdn.scentbird.com/frontbird2/images-gif/case_6b99fd.gif" alt="Perfume Icon" class="img-icon">PULVERIZE, TOQUE
                            E REPITA
                        </div>
                        <div class="icon-text">
                            <img src="https://cdn.scentbird.com/frontbird2/images-gif/handshake_686bd1.gif" alt="Perfume Icon" class="img-icon">COMPRA GARANTIDA
                        </div>
                    </div>
                <div class="comissao">
   <div >
   [perfumes_populares]
</div>

                </div>
            </div>
        <?php endif; ?>
    </div>
    <style>
        .menu-botao.pressed {
    background-color: black; /* Cor de fundo quando pressionado */
    color: white; /* Cor do texto quando pressionado */
    /* Adicione outros estilos que desejar */
}

    </style>
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Obter a URL completa da janela atual
        const currentUrl = window.location.href;

        // Obter todos os links do menu
        const menuLinks = document.querySelectorAll('.menu-botao');

        // Percorrer os links e verificar se o href corresponde à URL atual
        menuLinks.forEach(link => {
            if (currentUrl.includes(link.getAttribute('href'))) {
                // Adicionar a classe "pressed" ao link correspondente
                link.classList.add('pressed');
            }
        });
    });
</script>


 
    <?php
    return ob_get_clean(); // Retorna o conteúdo do buffer e finaliza o buffer
}

// Adiciona o shortcode [revenda_pop] que pode ser usado em posts e páginas
add_shortcode('revenda_pop', 'revenda_pop');

?>
