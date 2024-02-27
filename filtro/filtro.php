<?php
/**
 * Plugin Name: Filtro de Categorias - Perfumes
 * Description: Um plugin para criar shortcodes que listam produtos de categorias personalizadas, como perfumes.
 * Version: 1.0
 * Author: Seu Nome
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


function meu_plugin_adicionar_pagina_admin() {
    add_menu_page(
        'Configurações do Filtro de Produtos', // Título da página
        'Filtro de Produtos', // Título do menu
        'manage_options', // Capacidade necessária para ver esta página
        'filtro-de-produtos-config', // Slug do menu
        'meu_plugin_pagina_de_configuracao', // Função que renderiza a página de administração
        null, // Ícone
        56 // Posição no menu
    );
}
add_action('admin_menu', 'meu_plugin_adicionar_pagina_admin');

function meu_plugin_pagina_de_configuracao() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['meu_plugin_opcoes_salvas'])) {
        check_admin_referer('meu_plugin_opcoes_verificar');

        // Recupera as configurações existentes ou inicializa um array vazio se não existir
        $configuracoes_existentes = get_option('meu_plugin_configuracoes_categorias', array());

        // Salva as categorias selecionadas e o nome da nova categoria
        $categorias_selecionadas = isset($_POST['categorias_selecionadas']) ? (array) $_POST['categorias_selecionadas'] : array();
        $categorias_selecionadas = array_map('sanitize_text_field', $categorias_selecionadas);
        $nome_nova_categoria = sanitize_text_field($_POST['nome_nova_categoria']);

        // Adiciona a nova configuração ao array de configurações existentes
        $configuracoes_existentes[] = array(
            'nome' => $nome_nova_categoria,
            'categorias' => $categorias_selecionadas,
            // Gera o shortcode baseado no nome da nova categoria e o adiciona ao array
            'shortcode' => 'produtos_por_categoria tipo="' . esc_attr($nome_nova_categoria) . '"'
        );

        // Atualiza a opção com o novo array de configurações
        update_option('meu_plugin_configuracoes_categorias', $configuracoes_existentes);

        echo '<div id="message" class="updated fade"><p><strong>Configurações salvas.</strong></p></div>';
    }

    // Busca todas as categorias de produtos existentes
    $args = array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
    );
    $categorias = get_terms($args);

    // Busca as configurações existentes
    $configuracoes_existentes = get_option('meu_plugin_configuracoes_categorias', array());
?>
    <div class="wrap">
        <h2>Configurações do Filtro de Produtos</h2>
        <form method="POST" action="">
            <?php wp_nonce_field('meu_plugin_opcoes_verificar'); ?>

            <p>
                <label for="nome_nova_categoria">Nome da Nova Categoria:</label>
                <input type="text" id="nome_nova_categoria" name="nome_nova_categoria" value="" />
            </p>

            <p>Selecione as Categorias:</p>
            <?php foreach ($categorias as $categoria) : ?>
                <label>
                    <input type="checkbox" name="categorias_selecionadas[]" value="<?php echo esc_attr($categoria->slug); ?>" />
                    <?php echo esc_html($categoria->name); ?>
                </label><br />
            <?php endforeach; ?>

            <p>
                <input type="submit" value="Salvar configurações" class="button button-primary" />
                <input type="hidden" name="meu_plugin_opcoes_salvas" value="1" />
            </p>
        </form>

        <h3>Shortcodes Criados</h3>
        <?php foreach ($configuracoes_existentes as $configuracao) : ?>
            <p><?php echo esc_html($configuracao['nome']); ?>: <code>[<?php echo esc_html($configuracao['shortcode']); ?>]</code></p>
        <?php endforeach; ?>
    </div>
<?php
}

function listar_produtos_por_categoria_shortcode($atts) {
  // Atributos do shortcode, incluindo 'quantidade'
  $atts = shortcode_atts(
    array(
        'tipo' => '', // 'roupas', 'perfumes', 'bolsas'
        'quantidade' => 10, // Valor padrão para quantidade de produtos
    ),
    $atts,
    'produtos_por_categoria'
);

$tipo = $atts['tipo'];
$quantidade = $atts['quantidade']; // Acessando a quantidade especificada
    $categorias_selecionadas = get_option("meu_plugin_categorias_selecionadas");

    if (empty($categorias_selecionadas)) {
        return 'Por favor, configure as categorias para este tipo de filtro no admin.';
    }
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $quantidade, // Usa a quantidade especificada no shortcode
        'orderby' => 'rand', // Define a ordem como aleatória
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $categorias_selecionadas,
            ),
        ),
        
 
'meta_query' => array(
            array(
                'key'     => '_stock_status',
                'value'   => 'instock',
                'compare' => '=',
            ),
        ),
    );

    $query = new WP_Query($args);
    $output = '<div class="meu-plugin-produtos-grid">';
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            global $product;

          // Dentro do loop, onde você monta o HTML de cada produto
$output .= '<div class="meu-plugin-produto">';
$output .= '<a href="' . get_the_permalink() . '">';
$output .= get_the_post_thumbnail($product->get_id(), 'shop_catalog', array('class' => 'meu-plugin-produto-imagem'));
$output .= '<div class="meu-plugin-produto-info">';
$output .= '<h2 class="meu-plugin-produto-titulo">' . get_the_title() . '</h2>';

// Incluindo as estrelas de avaliação
if (function_exists('wc_get_rating_html')) { // Verifica se WooCommerce está ativo
    $rating_html = wc_get_rating_html($product->get_average_rating());
    $output .= '<div class="meu-plugin-produto-avaliacao">' . $rating_html . '</div>';
}

$output .= '<span class="meu-plugin-produto-preco">' . $product->get_price_html() . '</span>';
$output .= '</div>';
$output .= '</a>';
$output .= '<a href="?add-to-cart=' . get_the_ID() . '" class="button add_to_cart_button ajax_add_to_cart">Comprar</a>';
$output .= '</div>'; // Fim do produto

        }
    } else {
        $output .= '<div class="meu-plugin-nenhum-produto">Nenhum produto encontrado.</div>';
    }
    wp_reset_postdata();
    $output .= '</div>'; // Fim da grade de produtos

    return $output;
}


add_shortcode('produtos_por_categoria', 'listar_produtos_por_categoria_shortcode');

function meu_plugin_enqueue_assets() {
    // Enfileira o CSS com um nome único
    wp_enqueue_style('meu-plugin-estilo-unico', plugin_dir_url(__FILE__) . 'filtro-style.css');

    // Enfileira o JavaScript com um nome único
    wp_enqueue_script('meu-plugin-script-unico', plugin_dir_url(__FILE__) . 'filtro-script.js', array('jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'meu_plugin_enqueue_assets');
