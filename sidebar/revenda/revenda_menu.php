<?php

function menu_categorias_shortcode() {
    ob_start();
    ?>
   <div class="slideshow-container">
    <div class="menu-categorias">
        <a href="https://inovetime.com.br/revenda-3/" id="todos-perfumes" class="menu-botao">Todos os Perfumes</a>
        <a href="https://inovetime.com.br/revenda-arabes/" id="perfumes-arabes" class="menu-botao">Perfumes Árabes</a>
        <a href="https://inovetime.com.br/revenda-importados/" id="seletivo-luxo" class="menu-botao">Seletivo de Luxo</a>
        <a href="https://inovetime.com.br/revenda-semi-seletivos/" id="brand-miniatura" class="menu-botao">Semi Seletivos</a>
        <a href="https://inovetime.com.br/revenda-brand/" id="brand-miniatura" class="menu-botao">Brand Miniatura</a>
        
        
        <a href="https://inovetime.com.br/revenda-pop/" id="pop" class="menu-botao">Pop</a>
        <a href="https://inovetime.com.br/revenda-hidratante/" id="brand-miniatura" class="menu-botao">hidratantes</a>
    </div></div>
    <?php
    return ob_get_clean();
}
add_shortcode('menu_categorias', 'menu_categorias_shortcode');
