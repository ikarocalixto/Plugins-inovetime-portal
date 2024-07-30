
<?php
// Aqui você pode inserir qualquer código PHP necessário, 
// como verificações de condição, loops, obtenção de dados do banco de dados, etc.

// Exemplo de código PHP para verificar se um usuário está logado
if (is_user_logged_in()) {
  // Você pode buscar informações específicas do usuário aqui
  $current_user = wp_get_current_user();
  $username = $current_user->user_login; // Apenas um exemplo
  // Obtendo as funções do usuário atual
  $user_roles = $current_user->roles;

  // Verificando se o usuário tem a função de "contributor"
  if (in_array('contributor', $user_roles) || in_array('administrator', $user_roles)) {
      // Se o usuário for um "contributor", adicionamos o novo menu
      echo '  <a href="#" class="menu-item"><i class="fa fa-angle-double-left" aria-hidden="true"></i> <span class="menu-text">Portal Interno</span></a>';
      echo '<div class="submenu">';
      echo '<a href="https://inovetime.com.br/portal-mkt/" class="submenu-item">Portal Mkt</a>';
      echo '<a href="https://inovetime.com.br/portal-adm/" class="submenu-item">Portal Adm</a>';
      echo '</div>';
  }
}
?>

<div class="menu-items">
      <a href="#" class="menu-item"><i class="fas fa-user"></i> <span class="menu-text">Meus Dados</span></a>
      <div class="submenu">
    <a href="https://inovetime.com.br/minha-conta/orders/" class="submenu-item">Meus Pedidos</a>
    <a href="https://inovetime.com.br/conta-da-assinatura/tipos-de-assinatura/" class="submenu-item">Plano Mensal</a>
   
  </div>
      <a href="#" class="menu-item"><i class="fas fa-headset"></i> <span class="menu-text">Abrir Chamado</span></a>
      <div class="submenu">
    <a href="https://inovetime.com.br/suporte/" class="submenu-item">Abrir novo Chamado</a>
    <a href="https://inovetime.com.br/meus-chamados/" class="submenu-item">Ver meus Chamados</a>
    
  </div>
      <a href="#" class="menu-item"><i class="fas fa-store"></i> <span class="menu-text">Loja Atacado</span></a>
      <div class="submenu">
      <a href="https://inovetime.com.br/loja/" class="submenu-item">Ver loja</a>
      <a href="https://inovetime.com.br/loja-all-products/" class="submenu-item">Ver Todos os Produtos</a>
      
  
    
  </div>
      <a href="#" class="menu-item"><i class="fas fa-tasks"></i> <span class="menu-text">Marketplace</span></a>
      <div class="submenu">
    <a href="https://inovetime.com.br/fornecedor-adicionar-produto/" class="submenu-item">Adicionar Novo Produto</a>
    <a href="https://inovetime.com.br/fornecedor-meus-produtos/" class="submenu-item">Meus Produtos</a>
    <a href="https://inovetime.com.br/fornecedor-meus-pedidos/" class="submenu-item">Meus Pedidos</a>
    <a href="https://inovetime.com.br/fornecedor-minha-loja/" class="submenu-item">Minha Loja</a>
    
   
  </div>
      <a href="#" class="menu-item"><i class="fas fa-wallet"></i> <span class="menu-text">Área Financeira</span></a>
      <div class="submenu">
    <a href="https://inovetime.com.br/comissao/" class="submenu-item">Sacar Minhas Comissões</a>
    <a href="https://inovetime.com.br/teste-3/" class="submenu-item">Reserva de Estoque</a>
   
  </div>
      <a href="#" class="menu-item"><i class="fas fa-calculator"></i> <span class="menu-text">Simulador</span></a>
      <div class="submenu">
      <a href="https://inovetime.com.br/simulador-de-metricas-ads/" class="submenu-item">Simular Metricas de ADS </a>
    <a href="https://inovetime.com.br/teste/" class="submenu-item">Simulador Marketplace</a>
    <a href="https://inovetime.com.br/ponto-de-equilibrio/" class="submenu-item">Ponto de Equilibrio</a>
    <a href="https://inovetime.com.br/teste-2" class="submenu-item">Simular markup </a>
  </div>

  <div>

  <center><h1 style="font-size: 13px; " id="text-sidebar"></h1></center>
    <a href="#" class="menu-item"><i class="fa-solid fa-file-contract"></i> <span class="menu-text">Contratos</span></a>
      <div class="submenu">
    <a href="https://inovetime.com.br/wp-content/uploads/CIRCULAR-DE-OFERTA-ATUALIZADA-DEZ-23.pdf" target="_blank" class="submenu-item">Circular de oferta - COF</a>
    <a  href="https://inovetime.com.br/wp-content/uploads/Contrato-de-Franquia-Empresarial-Lady-Griffe-PDF.pdf" target="_blank" class="submenu-item">Contrato de Franquia</a>
 
  </div>

  
  <div class="sair">
  <a href="https://inovetime.com.br/wp-login.php?action=logout&_wpnonce=0479d2597a" class="menu-item"><i class="fa fa-sign-out" aria-hidden="true"></i> <span class="menu-text">Sair</span></a>
     
  </div>
    </div>

    <script>
document.addEventListener("DOMContentLoaded", function() {
    // Verifica se a URL é exatamente "https://inovetime.com.br"
    var urlAtual = window.location.href;
    var pathName = window.location.pathname;
    
    // Elemento do menu "Início" (precisa ajustar o seletor conforme sua estrutura HTML)
    var menuInicio = document.createElement("div");
    menuInicio.innerHTML = `
        <a href="https://inovetime.com.br/" class="menu-item"><i class="fas fa-home"></i> <span class="menu-text">Início</span></a>
    `;

    // Verifica se não está na página inicial
    if(pathName !== "/" && urlAtual.includes("inovetime.com.br")) {
        // Se não estiver na página inicial, insira o menu "Início"
        // Ajuste o seletor abaixo conforme a estrutura do seu menu
        document.querySelector(".menu-items").prepend(menuInicio);
    }



});


document.addEventListener("DOMContentLoaded", function() {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menu-toggle');
    const menuTexts = document.querySelectorAll('.sidebar-menu-text');
    const sidebarLogo = document.getElementById('sidebar-logo');
    const cardSection = document.getElementById('card-section');
    const slideshowContainer = document.querySelector('.slideshow-container');
    const kanban = document.querySelector('.kanban');
    const logoExpandido = "https://i0.wp.com/inovetime.com.br/wp-content/uploads/cropped-LOGO-FAIXA-FRANQUIA-inv-2.png?resize=1320%2C386&ssl=1";
  const logoRecolhido = "https://i0.wp.com/inovetime.com.br/wp-content/uploads/2022/08/cropped-logo-insta.png?w=1200&ssl=1";

    // Verificar se a URL não corresponde exatamente a 'https://inovetime.com.br/'
    const urlAtual = window.location.href;

    if (urlAtual !== 'https://inovetime.com.br/') {
        // Recolher o sidebar
        sidebar.classList.add('collapsed');
        sidebar.style.width = '80px';
        menuToggle.classList.replace('fa-arrow-left', 'fa-arrow-right');
        menuTexts.forEach(text => text.style.display = 'none');
        sidebarLogo.src = logoRecolhido;
        slideshowContainer.style.marginLeft = '150px';
        kanban.style.marginLeft = '-150px';
        cardSection.classList.add('sidebar-collapsed');
    }

    // Adicionar evento de clique para recolher/expandir o sidebar
    menuToggle.addEventListener('click', function() {
        const isCollapsed = sidebar.classList.toggle('collapsed');

        if (isCollapsed) {
            sidebar.style.width = '80px';
            menuToggle.classList.replace('fa-arrow-left', 'fa-arrow-right');
            menuTexts.forEach(text => text.style.display = 'none');
            sidebarLogo.src = logoRecolhido;
            slideshowContainer.style.marginLeft = '150px';
            kanban.style.marginLeft = '-150px';
            cardSection.classList.add('sidebar-collapsed');
        } else {
            sidebar.style.width = '250px';
            menuToggle.classList.replace('fa-arrow-right', 'fa-arrow-left');
            menuTexts.forEach(text => text.style.display = 'inline');
            sidebarLogo.src = logoExpandido;
            slideshowContainer.style.marginLeft = '250px';
            kanban.style.marginLeft = '0px';
            cardSection.classList.remove('sidebar-collapsed');
        }
    });
});

</script>