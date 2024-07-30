<?php 



function kanban_trainee() {

    global $kanban_trainee_loaded;
    $kanban_trainee_loaded = true; // Marca que o shortcode está sendo usado.


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
include( plugin_dir_path( __FILE__ ) . '../menu/menu.php' );

?>
    <!-- Fim do menu -->


    <div class="sidebar-footer">
    <i class="fas fa-arrow-left menu-toggle" id="menu-toggle"></i>
    </div>
  </aside>


 <!-- informações do usuarios -->
 <?php  global $wpdb;
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
 <!-- final das informações do usuarios  -->


  <!-- Conteúdo Principal -->
  <div class="content-area">
    <!-- Header -->
    <header class="site-header">
    <div class="main-content">

    
   
   
      <div class="user-info">
<div></div>


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
  

 


    <!-- quadro kanban -->
    <div class="botões">
    <div class="adicionar"> [adicionar_tarefa]</div>


    <div class="elementor-widget-container">
   <div><form class="buscar_franqueados2">
  
    </form></div> 
</div>


    <div class="concluir">[concluir_modulo]</div>

</div>


    <div class="slideshow-container">
  <div class="mySlides fade" id="meuslideunico">
    <img src="" style="width:100%">
  </div>

 
  <div class="kanban">
    
  [quadro_kanban]

  </div>
  <div>
   
  </div>
<?php endif; ?>

  <?php
  return ob_get_clean(); // Retorna o conteúdo do buffer e finaliza o buffer
}
// Adiciona o shortcode [kanban_trainee] que pode ser usado em posts e páginas
add_shortcode('kanban_trainee', 'kanban_trainee');



function kanban_trainee_enqueue_scripts() {
    global $kanban_trainee_loaded;
    if ( ! empty($kanban_trainee_loaded) ) {
        wp_enqueue_style('meu-sidebar-style', plugins_url('/style.css', __FILE__));
        wp_enqueue_script('meu-sidebar-script', plugins_url('/script.js', __FILE__), array(), false, true);
    }
}
add_action('wp_enqueue_scripts', 'kanban_trainee_enqueue_scripts');

function mostrar_formulario_franqueados() {
    $users = get_users();
    // Botão que, quando clicado, irá mostrar o formulário
    echo '<div style="text-align:center; margin-top:20px;">'; // Ajuste o estilo conforme necessário
    echo '<button id="mostrarForm" style="cursor:pointer;">Mostrar Formulário</button>';
    echo '</div>';

    // O formulário começa escondido
    echo '<div class="form-busca-franqueados2" style="display:none; text-align:center; margin-top:20px;">'; // Centraliza e ajusta o posicionamento
    echo '<form action="" method="get" style="display:inline-block; margin:auto;">'; // Ajuste para centralizar o formulário
    echo '<select name="selected_user" onchange="this.form.submit()">';
    echo '<option value="">Selecione um Usuário</option>';
    foreach ($users as $user) {
        $selected = isset($_GET['selected_user']) && $_GET['selected_user'] == $user->ID ? 'selected' : '';
        echo '<option value="' . esc_attr($user->ID) . '" ' . $selected . '>' . esc_html($user->display_name) . '</option>';
    }
    echo '</select>';
    echo '<input type="submit" value="Ver Tarefas">';
    echo '</form>';

    if (isset($_GET['selected_user']) && !empty($_GET['selected_user'])) {
        $selected_user_id = intval($_GET['selected_user']);
        $user_info = get_userdata($selected_user_id);
        echo '<div class="user-info">';
        echo get_avatar($selected_user_id);
        echo '<span>' . esc_html($user_info->display_name) . '</span>';
        echo '</div>';
    }

    echo '</div>';

    // Adiciona o JavaScript necessário para mostrar o formulário
    echo "<script>
            document.getElementById('mostrarForm').addEventListener('click', function() {
                document.querySelector('.form-busca-franqueados2').style.display = 'block';
                this.style.display = 'none'; // Opcional: Esconde o botão após o clique
            });
          </script>";
}
add_shortcode('mostrar_franqueados_form', 'mostrar_formulario_franqueados');

