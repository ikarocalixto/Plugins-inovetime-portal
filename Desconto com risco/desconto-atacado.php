<?php
/**
 * Plugin Name: Meu Plugin de Desconto WooCommerce
 * Description: Aplica um desconto a todos os produtos e mostra o preço original riscado.
 * Version: 1.0
 * Author: Seu Nome
 */
add_filter('woocommerce_get_price_html', 'custom_price_html', 100, 2);
add_filter('woocommerce_cart_item_price', 'custom_cart_item_price', 100, 3);

add_filter('woocommerce_get_price_html', 'custom_price_html', 100, 2);
add_filter('woocommerce_cart_item_price', 'custom_cart_item_price', 100, 3);

add_filter('woocommerce_get_price_html', 'custom_price_html_with_labels', 100, 2);

function custom_price_html_with_labels($price, $product) {
    // Defina o desconto aqui. Exemplo: 10% de desconto
    $desconto = 0.30;
    // Obtenha o preço regular e o preço de venda, se houver
    $preco_regular = $product->get_regular_price();
    $preco_venda = $product->get_sale_price() ? $product->get_sale_price() : $preco_regular;
    // Calcule o preço com desconto
    $preco_com_desconto = $preco_venda - ($preco_venda * $desconto);

    // Construa o HTML para os preços com os rótulos "Atacado" e "Varejo"
    $preco_html = '<div class="preco-varejo" style="font-size: 10px; color: grey;">Venda no Varejo Por:</div>';
    $preco_html .= '<del>' . wc_price($preco_regular) . '</del><br>';
    $preco_html .= '<div class="preco-atacado" style="font-size: 10px; color: grey;">Compre no Atacado Por:</div>';
    $preco_html .= '<ins>' . wc_price($preco_com_desconto) . '</ins>';

    return $preco_html;
}

function custom_cart_item_price($price, $cart_item, $cart_item_key) {
    $desconto = 0.30; // 10% de desconto
    $produto = $cart_item['data'];
    $preco_regular = $produto->get_regular_price();
    $preco_venda = $produto->get_sale_price() ? $produto->get_sale_price() : $preco_regular;
    $preco_com_desconto = $preco_venda - ($preco_venda * $desconto);
    return wc_price($preco_com_desconto);
}

add_action('woocommerce_before_calculate_totals', 'custom_apply_discount_before_calculate_totals', 10, 1);

function custom_apply_discount_before_calculate_totals($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (did_action('woocommerce_before_calculate_totals') >= 2) return;

    $desconto = 0.30; // Define o desconto aqui

    foreach ($cart->get_cart() as $cart_item) {
        $preco_original = $cart_item['data']->get_regular_price();
        $preco_venda = $cart_item['data']->get_sale_price() ? $cart_item['data']->get_sale_price() : $preco_original;
        $preco_com_desconto = $preco_venda - ($preco_venda * $desconto);
        $cart_item['data']->set_price($preco_com_desconto);
    }
}

add_action('admin_menu', 'add_my_custom_menu');

function add_my_custom_menu() {
    // Adiciona uma nova página de submenu em "Produtos"
    add_submenu_page(
        'edit.php?post_type=product',
        'Configurações de Desconto por Categoria',
        'Descontos por Categoria',
        'manage_options',
        'descontos-por-categoria',
        'my_custom_menu_page'
    );
}

function my_custom_menu_page(){
    ?>
    <div class="wrap">
        <h2>Configurações de Desconto por Categoria</h2>
        <form method="post" action="options.php">
            <?php
                settings_fields('my-custom-settings-group');
                do_settings_sections('my-custom-settings-group');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}
add_action('admin_init', 'my_custom_settings');

function my_custom_settings() {
    register_setting('my-custom-settings-group', 'categoria_descontos');
    // Aqui você pode adicionar seções e campos de configuração
}
