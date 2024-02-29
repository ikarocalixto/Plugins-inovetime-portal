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

function criar_tabela_categorias_loja() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $tabela_nome = $wpdb->prefix . 'categorias_loja'; // Nome da tabela ajustado

    $sql = "CREATE TABLE $tabela_nome (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nome_categoria text NOT NULL,
        categorias_selecionadas text NOT NULL, // Armazena como texto; considere serializar
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'criar_tabela_categorias_loja');



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

    // Verifica se o formulário foi enviado
    if (isset($_POST['meu_plugin_opcoes_salvas']) && check_admin_referer('meu_plugin_opcoes_verificar')) {
        // Recupera as configurações existentes ou inicializa um array vazio se não existir
        $configuracoes_existentes = get_option('meu_plugin_configuracoes_categorias', array());

        // Salva as categorias selecionadas e o nome da nova categoria
        $categorias_selecionadas = isset($_POST['categorias_selecionadas']) ? (array) $_POST['categorias_selecionadas'] : array();
        // Mapeia cada ID de categoria para seu respectivo slug
        $categorias_selecionadas_slugs = array_map(function($term_id) {
            $term = get_term($term_id, 'product_cat');
            return $term ? $term->slug : '';
        }, $categorias_selecionadas);

        $nome_nova_categoria = sanitize_text_field($_POST['nome_nova_categoria']);

        // Serializa os slugs das categorias para armazenamento
        $categorias_serializadas = maybe_serialize($categorias_selecionadas_slugs);

        // Adiciona a nova configuração ao array de configurações existentes
        $configuracoes_existentes[] = array(
            'nome' => $nome_nova_categoria,
            'categorias' => $categorias_serializadas, // Aqui salvamos a string serializada
            // Gera o shortcode baseado no nome da nova categoria e o adiciona ao array
            'shortcode' => 'produtos_por_categoria tipo="' . esc_attr($nome_nova_categoria) . '"'
        );

        // Atualiza a opção com o novo array de configurações
        update_option('meu_plugin_configuracoes_categorias', $configuracoes_existentes);

        echo '<div id="message" class="updated fade"><p><strong>Configurações salvas.</strong></p></div>';
    }

    // Recupera as configurações existentes para exibir na parte de shortcodes criados
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
        <?php
        if (class_exists('WooCommerce')) {
            $categorias = get_terms('product_cat', array('hide_empty' => false));
            if (!empty($categorias) && !is_wp_error($categorias)) {
                foreach ($categorias as $categoria) {
                    $checked = in_array($categoria->term_id, array_column($configuracoes_existentes, 'categorias'), true) ? 'checked' : '';
                    echo '<label>';
                    echo '<input type="checkbox" name="categorias_selecionadas[]" value="' . esc_attr($categoria->term_id) . '"' . $checked . '>';
                    echo esc_html($categoria->name);
                    echo '</label><br />';
                }
            } else {
                echo '<p>Nenhuma categoria de produto encontrada.</p>';
            }
        } else {
            echo '<p>O WooCommerce precisa estar ativo para configurar este plugin.</p>';
        }
        ?>
        <p>
            <input type="submit" value="Salvar configurações" class="button button-primary" />
            <input type="hidden" name="meu_plugin_opcoes_salvas" value="1" />
        </p>
    </form>

    <h3>Shortcodes Criados</h3>
    <?php foreach ($configuracoes_existentes as $configuracao) : ?>
        <p><?php echo esc_html($configuracao['nome']); ?>: <code><?php echo esc_html($configuracao['shortcode']); ?></code></p>
    <?php endforeach; ?>
</div>
<?php
}



function inserir_dados_categorias_loja($nome_nova_categoria, $categorias_selecionadas) {
    global $wpdb;
    $tabela_nome = $wpdb->prefix . 'categorias_loja';

    $categorias_serializadas = maybe_serialize($categorias_selecionadas);

    $wpdb->insert(
        $tabela_nome,
        array(
            'nome_categoria' => $nome_nova_categoria,
            'categorias_selecionadas' => $categorias_serializadas
        ),
        array(
            '%s',
            '%s'
        )
    );
}

function recuperar_dados_categorias_loja() {
    global $wpdb;
    $tabela_nome = $wpdb->prefix . 'categorias_loja';
    
    $resultado = $wpdb->get_results("SELECT * FROM $tabela_nome", ARRAY_A);
    
    return $resultado;
}


function listar_produtos_por_categoria_shortcode($atts) {
    global $wpdb;
    $atts = shortcode_atts(
        array(
            'id' => '', // ID da configuração na tabela personalizada
            'quantidade' => 10, // Valor padrão para quantidade de produtos
        ),
        $atts,
        'produtos_por_categoria'
    );

    $id_configuracao = $atts['id'];
    $quantidade = $atts['quantidade'];

    // Busca as configurações pela ID na tabela personalizada
    $tabela_nome = $wpdb->prefix . 'categorias_loja';
    $configuracao = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabela_nome WHERE id = %d", $id_configuracao), ARRAY_A);

    if (is_null($configuracao)) {
        return 'Configuração não encontrada.';
    }

    // Deserializa as categorias selecionadas
    $categorias_selecionadas = maybe_unserialize($configuracao['categorias_selecionadas']);

    if (!is_array($categorias_selecionadas)) {
        return 'Erro ao deserializar as categorias selecionadas.';
    }

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $quantidade,
        'orderby' => 'rand',
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $categorias_selecionadas,
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
