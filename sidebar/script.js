
(function() {

document.addEventListener("DOMContentLoaded", function() {
  const menuToggle = document.getElementById('menu-toggle');
  const sidebar = document.getElementById('sidebar');
  const sidebarLogo = document.getElementById('sidebar-logo'); // Assegure-se de que este é o ID correto.
  const menuItems = document.querySelectorAll('.menu-item');
  const menuTexts = document.querySelectorAll('.menu-text');
  const slideshowContainer = document.querySelector('.slideshow-container'); // Seleciona o contêiner do slideshow
  const kanban = document.querySelector('.kanban'); // Seleciona o contêiner do slideshow
  const slideshow = {
    slideIndex: 1, // Estado inicial do índice do slide

    showSlides: function(n) {
        let slides = document.getElementsByClassName("mySlides");
        if (n > slides.length) { this.slideIndex = 1; }
        if (n < 1) { this.slideIndex = slides.length; }

        // Esconde todos os slides
        Array.from(slides).forEach(slide => {
            slide.style.display = "none";
        });

        // Mostra o slide atual
        slides[this.slideIndex - 1].style.display = "block";
    },

    plusSlides: function(n) {
        this.showSlides(this.slideIndex += n);
    },

    currentSlide: function(n) {
        this.showSlides(this.slideIndex = n);
    },

    startSlideShow: function() {
        this.plusSlides(1); // Incrementa o índice para mover ao próximo slide
        setTimeout(() => this.startSlideShow(), 9500); // Chama startSlideShow a cada 9.5 segundos
    },

    initialize: function() {
        this.showSlides(this.slideIndex); // Mostra o primeiro slide
        this.startSlideShow(); // Inicia o ciclo automático de slides
    }
};

slideshow.initialize();
 // Aqui, você pode configurar event listeners para botões de navegação, se necessário
        // Por exemplo:
        // document.getElementById('prevButton').addEventListener('click', () => slideshow.plusSlides(-1));
        // document.getElementById('nextButton').addEventListener('click', () => slideshow.plusSlides(1));

  
  const cardSection = document.querySelector('.card-section'); // Selecione a seção de cards
  var body = document.body;
 

    // Função para verificar a presença da classe e ajustar o estilo
    function adjustHeaderStyle() {
        var siteHeader = document.querySelector('.site-header');
        if (body.classList.contains('sidebar-collapse')) {
            siteHeader.style.justifyContent = 'flex-start'; // Ou qualquer valor desejado
        } else {
            siteHeader.style.justifyContent = 'flex-end'; // Valor original
        }
    }

    // Chame a função no carregamento da página
    adjustHeaderStyle();

  // Função para ajustar a margem do slideshow baseada na largura do sidebar
  function adjustSlideshowMargin() {
    const sidebarWidth = sidebar.offsetWidth; // Obtém a largura atual do sidebar
    kanban.style.marginLeft = `${sidebarWidth}px`; // Ajusta a margem esquerda do slideshow
  }
   

  // Função para ajustar a margem do slideshow baseada na largura do sidebar
  function adjustSlideshowMargin() {
    const sidebarWidth = sidebar.offsetWidth; // Obtém a largura atual do sidebar
    slideshowContainer.style.marginLeft = `${sidebarWidth}px`; // Ajusta a margem esquerda do slideshow
  }

  // Verifica e ajusta a margem do slideshow quando a página é carregada
  adjustSlideshowMargin();


  // Verifique os URLs para garantir que estão corretos.
  const logoExpandido = "https://i0.wp.com/inovetime.com.br/wp-content/uploads/cropped-LOGO-FAIXA-FRANQUIA-inv-2.png?resize=1320%2C386&ssl=1";
  const logoRecolhido = "https://i0.wp.com/inovetime.com.br/wp-content/uploads/2022/08/cropped-logo-insta.png?w=1200&ssl=1";

  menuToggle.addEventListener('click', function() {
    const isDifferentDomain = !(window.location.hostname.startsWith('https://inovetime.com.br/'));
    
    if (isDifferentDomain) {
        // Recolher o sidebar
        const isCollapsed = sidebar.classList.toggle('collapsed');

        if (isCollapsed) {
            sidebar.style.width = '80px';
            menuToggle.classList.replace('fa-arrow-left', 'fa-arrow-right');
            menuTexts.forEach(text => text.style.display = 'none');
            sidebarLogo.src = logoRecolhido;
            document.querySelector('.slideshow-container').style.marginLeft = '150px';
            document.querySelector('.kanban').style.marginLeft = '-150px';
            cardSection.classList.add('sidebar-collapsed');
            cardSection.classList.add('card-section');
            // Ajustar o margin-left do cards-section
            cardSection.style.marginLeft = '250px';
        } else {
            sidebar.style.width = '250px';
            menuToggle.classList.replace('fa-arrow-right', 'fa-arrow-left');
            menuTexts.forEach(text => text.style.display = 'inline');
            sidebarLogo.src = logoExpandido;
            document.querySelector('.slideshow-container').style.marginLeft = '250px';
            document.querySelector('.kanban').style.marginLeft = '0px';
            cardSection.classList.remove('sidebar-collapsed');
            cardSection.classList.remove('card-section');
            // Ajustar o margin-left do cards-section
            cardSection.style.marginLeft = '215px';
        }
    }
});



  
  menuItems.forEach(item => {
    item.addEventListener('click', function(event) {
      // Encontra o submenu relacionado ao item do menu clicado
      const submenu = item.nextElementSibling;

      if (submenu && submenu.classList.contains('submenu')) {
        event.stopPropagation(); // Impede que o evento se propague mais

        // Verifica se o sidebar está recolhido
        if (sidebar.classList.contains('collapsed')) {
          // Calcula a posição necessária para o submenu aparecer fora do sidebar
          const sidebarRect = sidebar.getBoundingClientRect();
          const menuItemRect = item.getBoundingClientRect();
          
          // Posiciona o submenu fora do sidebar recolhido
          submenu.style.position = 'fixed';
          submenu.style.left = `${sidebarRect.right}px`; // Posiciona à direita do sidebar
          submenu.style.top = `${menuItemRect.top}px`; // Alinha ao topo do item do menu

          // Alterna a visibilidade do submenu
          submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
        } else {
          // Caso o sidebar não esteja recolhido, o comportamento normal é seguido
          submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
        }
      }
    });
  });


  // Verificar se a URL é exatamente a página principal
if (window.location.href === 'https://inovetime.com.br/') {
  // Expandir o sidebar
  sidebar.style.width = '250px';
  menuToggle.classList.replace('fa-arrow-right', 'fa-arrow-left');
  menuTexts.forEach(text => text.style.display = 'inline');
  sidebarLogo.src = logoExpandido;
  document.querySelector('.slideshow-container').style.marginLeft = '250px';
  document.querySelector('.kanban').style.marginLeft = '0px';
  cardSection.classList.remove('sidebar-collapsed');
  cardSection.classList.remove('card-section');
  // Ajustar o margin-left do cards-section
  cardSection.style.marginLeft = '215px';
} else {
  // Recolher o sidebar
  sidebar.style.width = '80px';
  menuToggle.classList.replace('fa-arrow-left', 'fa-arrow-right');
  menuTexts.forEach(text => text.style.display = 'none');
  sidebarLogo.src = logoRecolhido;
  document.querySelector('.slideshow-container').style.marginLeft = '150px';
  document.querySelector('.kanban').style.marginLeft = '-150px';
  cardSection.classList.add('sidebar-collapsed');
  cardSection.classList.add('card-section');
  // Ajustar o margin-left do cards-section
  cardSection.style.marginLeft = '250px';
}
 



  // Clique fora para fechar os submenus
  document.addEventListener('click', function(e) {
    const isClickInsideMenuItem = e.target.closest('.menu-item');
    if (!isClickInsideMenuItem) {
      document.querySelectorAll('.submenu').forEach(function(submenu) {
        submenu.style.display = 'none'; // Esconde os submenus
      });
    }
  });
});









document.addEventListener("DOMContentLoaded", function() {
  const menuToggle = document.getElementById('menu-toggle');
  const sidebar = document.getElementById('sidebar');
  const textSidebar = document.getElementById('text-sidebar'); // Seleciona o elemento H1 pelo ID

  menuToggle.addEventListener('click', function() {
    // Verifica se o sidebar está recolhido
    if (sidebar.classList.contains('collapsed')) {
      textSidebar.classList.add('hidden'); // Oculta o H1
    } else {
      textSidebar.classList.remove('hidden'); // Mostra o H1
    }
  });
});

})();