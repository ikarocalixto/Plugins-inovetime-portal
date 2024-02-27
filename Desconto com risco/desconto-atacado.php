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

add_action('admin_menu', 'add_my_custom_menu');
add_action('admin_init', 'my_custom_settings');

function custom_price_html_with_labels($price, $product) {
    $categories = wp_get_post_terms($product->get_id(), 'product_cat', array("fields" => "ids"));
    $desconto_aplicado = 30; // Desconto a ser aplicado

    foreach ($categories as $category_id) {
        $desconto_categoria = floatval(get_option('desconto_categoria_' . $category_id, 0));
        if ($desconto_categoria > $desconto_aplicado) {
            $desconto_aplicado = $desconto_categoria;
        }
    }

    if ($desconto_aplicado > 0) {
        $desconto = $desconto_aplicado / 100; // Converte porcentagem para decimal
        $preco_regular = $product->get_regular_price();
        $preco_com_desconto = $preco_regular - ($preco_regular * $desconto);

        // Construa o HTML para os preços com os rótulos "Atacado" e "Varejo"
        $preco_html = '<div class="preco-varejo" style="font-size: 10px; color: grey;">Venda no Varejo Por:</div>';
        $preco_html .= '<del>' . wc_price($preco_regular) . '</del><br>';
        $preco_html .= '<div class="preco-atacado" style="font-size: 10px; color: grey;">Compre no Atacado Por:</div>';
        $preco_html .= '<ins>' . wc_price($preco_com_desconto) . '</ins>';

        return $preco_html;
    }

    // Retorna o preço original se nenhum desconto for aplicável
    return $price;
}


// Ajuste as outras funções de forma similar para usar o valor de desconto das configurações


function custom_cart_item_price($price, $cart_item, $cart_item_key) {
    $produto = $cart_item['data'];
    $categories = wp_get_post_terms($produto->get_id(), 'product_cat', array("fields" => "ids"));
    $desconto_aplicado = 0; // Desconto padrão de 30% como decimal

    // Verifica o desconto para cada categoria e aplica o maior
    foreach ($categories as $category_id) {
        $desconto_categoria = floatval(get_option('desconto_categoria_' . $category_id)) / 100; // Converte para decimal
        if ($desconto_categoria > $desconto_aplicado) {
            $desconto_aplicado = $desconto_categoria;
        }
    }

    // Calcula o preço com desconto apenas se houver um desconto definido e maior que 0
    if ($desconto_aplicado > 0) {
        $preco_regular = $produto->get_regular_price();
        // Verifica se há um preço de venda e usa o menor entre o regular e o de venda para aplicar o desconto
        $preco_base = $produto->get_sale_price() && $produto->get_sale_price() < $preco_regular ? $produto->get_sale_price() : $preco_regular;
        $preco_com_desconto = $preco_base * (1 - $desconto_aplicado); // Aplica o desconto

        // Altera o preço do item no carrinho
        $cart_item['data']->set_price($preco_com_desconto);
    }

    // Não precisa alterar o retorno aqui, pois a modificação do preço é feita através do set_price()
}


add_action('woocommerce_before_calculate_totals', 'custom_apply_discount_before_calculate_totals', 10, 1);

function custom_apply_discount_before_calculate_totals($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (did_action('woocommerce_before_calculate_totals') >= 2) return;

    foreach ($cart->get_cart() as $cart_item) {
        $produto = $cart_item['data'];
        $categories = wp_get_post_terms($produto->get_id(), 'product_cat', array("fields" => "ids"));
        $desconto_aplicado = 0.30; // Desconto padrão de 30% como decimal

        // Inicializa a variável para verificar se um desconto específico de categoria foi encontrado
        $desconto_especifico_encontrado = false;

        foreach ($categories as $category_id) {
            $desconto_categoria = floatval(get_option('desconto_categoria_' . $category_id, 0)) / 100; // Converte para decimal

            // Verifica se o desconto da categoria é maior que o desconto aplicado
            if ($desconto_categoria > $desconto_aplicado) {
                $desconto_aplicado = $desconto_categoria;
                $desconto_especifico_encontrado = true;
            }
        }

        // Se nenhum desconto específico de categoria for encontrado, aplica o desconto padrão
        if (!$desconto_especifico_encontrado) {
            $desconto_aplicado = 0.30; // Mantém o desconto padrão
        }

        $preco_regular = $produto->get_regular_price();
        $preco_base = $produto->get_sale_price() && $produto->get_sale_price() < $preco_regular ? $produto->get_sale_price() : $preco_regular;
        $preco_com_desconto = $preco_base * (1 - $desconto_aplicado);

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

function my_custom_menu_page() {
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

function my_custom_settings() {
    $categorias = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
    foreach ($categorias as $categoria) {
        $option_name = 'desconto_categoria_' . $categoria->term_id;
        register_setting('my-custom-settings-group', $option_name, 'sanitize_desconto');
        add_settings_section('default', 'Descontos', null, 'my-custom-settings-group');
        add_settings_field($option_name, $categoria->name, 'my_custom_desconto_field_callback', 'my-custom-settings-group', 'default', ['label_for' => $option_name, 'class' => 'my_custom_class', 'categoria_id' => $categoria->term_id]);
    }
}

function my_custom_desconto_field_callback($args) {
    $option = get_option($args['label_for']);
    echo "<input type='text' id='" . esc_attr($args['label_for']) . "' name='" . esc_attr($args['label_for']) . "' value='" . esc_attr($option) . "' /> %";
}

function sanitize_desconto($input) {
    return is_numeric($input) ? $input : '';
}
