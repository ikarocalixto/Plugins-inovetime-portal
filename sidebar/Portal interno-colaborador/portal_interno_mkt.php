<?php
/*
Plugin Name: Meu Sidebar Plugin
Plugin URI: http://seusite.com/
Description: Um simples plugin de sidebar para WordPress.
Version: 1.0
Author: Seu Nome
Author URI: http://seusite.com/
*/


function portal_interno_mkt_shortcode() {
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



<?php
  // incluindo o menu do colaborador
include( plugin_dir_path( __FILE__ ) . '../menu/menu_colaborador.php' );

?>



    <div class="sidebar-footer">
    <i class="fas fa-arrow-left menu-toggle" id="menu-toggle"></i>
    </div>
  </aside>


 <!-- informações do usuarios -->
 <?php  global $wpdb;
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

    <div class="botões">
    <div class="adicionar"> [bater_ponto] </div>

    <div>
   [historico_usuario] 
   </div>

    <div class="concluir">[encerrar_expediente]</div>

</div>

  <div class="mySlides fade" id="meuslideunico">
    <img src="https://inovetime.com.br/wp-content/uploads/Banner-outubronov-7.png" style="width:100%">
  </div>

 

  <div class="mySlides fade">
    <img src="https://inovetime.com.br/wp-content/uploads/Banner-outubronov-8.png" style="width:100%">
  </div>


  <div class="mySlides fade">
    <img src="https://inovetime.com.br/wp-content/uploads/Banner-outubronov-9.png" style="width:100%">
  </div>
  
  <!-- Botões de Navegação -->
  <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
  <a class="next" onclick="plusSlides(1)">&#10095;</a>
</div>


    <!-- Seção de Cards -->
    <div class="card-section">
      <!-- Cards vão aqui -->
      
      <div class="cartao">[Colaborador_mkt]</div>
      
      

      
    </div>
  </div>
</div>
<?php endif; ?>

  <?php
  return ob_get_clean(); // Retorna o conteúdo do buffer e finaliza o buffer
}
// Adiciona o shortcode [portal_interno_mkt] que pode ser usado em posts e páginas
add_shortcode('portal_interno_mkt', 'portal_interno_mkt_shortcode');



