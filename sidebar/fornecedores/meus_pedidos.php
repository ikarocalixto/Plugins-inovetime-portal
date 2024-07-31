<?php

function meus_pedidos() {
    global $meus_pedidos_loaded;
    $meus_pedidos_loaded = true; // Marca que o shortcode está sendo usado.

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
                            <div>[usuario_extrato_saque]</div>
                            <img src="<?php echo esc_url($avatar_url); ?>" alt="Foto do Usuário" class="user-photo"/>
                            <div class="user-text">
                                <div class="user-name"><?php echo esc_html($nome); ?></div>
                                <div class="user-bio"><?php echo esc_html($bio); ?></div>
                            </div>
                            <div class="notificar">
                                <div class="notification-icon">[icone_sino]</div>
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
                        <div>[mp_marketplace_dashboard]</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean(); // Retorna o conteúdo do buffer e finaliza o buffer
}

// Adiciona o shortcode [meus_pedidos] que pode ser usado em posts e páginas
add_shortcode('meus_pedidos', 'meus_pedidos');

?>
