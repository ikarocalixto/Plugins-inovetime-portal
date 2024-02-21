<?php
/**
 * Plugin Name: Importador XML de Produtos para WooCommerce
 * Plugin URI: http://seusite.com/
 * Description: Importa produtos de um arquivo XML para WooCommerce.
 * Version: 1.0
 * Author: Íkaro calixto 
 * Author URI: http://seusite.com/
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Adicionar menu de administração
add_action('admin_menu', 'adicionar_menu_importacao');

function adicionar_menu_importacao() {
    add_menu_page('Importar Produtos XML', 'Importar XML', 'manage_options', 'importar-xml-produtos', 'pagina_importacao_xml');

  
}

function pagina_importacao_xml() {
    // Verifica se o usuário selecionou um produto específico para importar
    if (isset($_POST['importar_produto_especifico'])) {
        $produto_id = $_POST['produto_id'];
        processar_importacao_produto_especifico($produto_id);
        echo '<div class="notice notice-success"><p>Produto específico importado com sucesso!</p></div>';
    }

    

    // Exibe o formulário para inserir a URL do XML
    ?>
    <div class="wrap">
        <h1>Importar Produtos XML</h1>
        <form method="post" action="">
            <?php
                settings_fields('importar-xml-produtos-options');
                do_settings_sections('importar-xml-produtos');
                submit_button('Salvar URL do XML');
            ?>
        </form>
   

<script>
jQuery(document).ready(function($) {
    $('#importar-produto-especifico').click(function(e) {
        e.preventDefault();
        
        var produtoId = $(this).prev('input[name="produto_id"]').val();
        
        // Iniciar a barra de progresso
        var progresso = 0;
        $('#progress-bar').val(progresso);
        $('#progress-bar-container').show();
        
        $.ajax({
            url: ajaxurl, // ajaxurl é definido pelo WordPress
            type: 'POST',
            data: {
                action: 'importar_produto_especifico_ajax',
                produto_id: produtoId
            },
            success: function(response) {
                // Aqui você atualiza a barra de progresso ou exibe uma mensagem de conclusão
                $('#progress-bar').val(100); // Completa a barra
                alert('Produto importado com sucesso!');
            },
            error: function() {
                alert('Erro ao importar produto.');
            }
        });
    });
});
</script>


        <?php
            // Tentar carregar e exibir os dados do XML
            $url_xml = get_option('url_xml_produto');
            if (!empty($url_xml)) {
                try {
                    $xml = simplexml_load_file($url_xml);
                    if ($xml === false) {
                        throw new Exception('Falha ao carregar XML');
                    }

                    $xml->registerXPathNamespace('g', 'http://base.google.com/ns/1.0');
                    $produtos = $xml->xpath('//channel/item');
                    error_log('Total de produtos encontrados: ' . count($produtos));
                    $contador = 0;
                    foreach ($produtos as $item) { // Limita a visualização a 25 produtos
                        
                        $imagem = (string)$item->xpath('g:image_link')[0];
                        $descricao = (string)$item->description;
                        $preco = (string)$item->xpath('g:price')[0];
                        $produto_id = (string)$item->id; // Supondo que exista um elemento de ID
                        
                        echo "<div class='produto'>";
                        echo "<img src='{$imagem}' alt='Imagem do Produto' style='width:100px;height:auto;'>";
                        echo "<p>Descrição: {$descricao}</p>";
                        echo "<p>Preço: {$preco}</p>";
                        
                       // Dentro da sua função `pagina_importacao_xml`
echo "<form method='post' action='' id='form-importar-produto'>";
echo "<input type='hidden' name='produto_id' value='{$produto_id}'>";
echo "<input type='submit' name='importar_produto_especifico' value='Importar Este Produto!' id='importar-produto-especifico'>";
echo "</form>";
 

                        echo "</div>";
                    }
                } catch (Exception $e) {
                    echo '<div class="notice notice-error"><p>Erro ao carregar o XML. Verifique a URL e tente novamente.</p></div>';
                }
            }
        ?>
    </div>
    <?php
}



add_action('wp_ajax_importar_produto_especifico_ajax', 'processar_importacao_produto_especifico_ajax');

function processar_importacao_produto_especifico($produto_id) {
    // Verifica se o ID do produto é fornecido e é válido
    if (!$produto_id) {
        echo '<div class="notice notice-error"><p>ID do produto inválido ou não fornecido.</p></div>';
        return;
    }

    // Obtém a URL do arquivo XML e verifica se está configurada
    $url_xml = get_option('url_xml_produto');
    if (empty($url_xml)) {
        echo '<div class="notice notice-error"><p>URL do XML não configurada.</p></div>';
        return;
    }

    // Tenta carregar o arquivo XML
    $xml = simplexml_load_file($url_xml);
    if ($xml === false) {
        echo '<div class="notice notice-error"><p>Falha ao carregar XML.</p></div>';
        return;
    }

    // Prepara a expressão XPath com a ID do produto sanitizada
    $expressao = sprintf("//channel/item[id='%s']", esc_sql($produto_id));
    $xml->registerXPathNamespace('g', 'http://base.google.com/ns/1.0');
    $produtos = $xml->xpath($expressao);

    if (empty($produtos)) {
        echo "<div><p>Produto com ID $produto_id não encontrado no XML.</p></div>";
        return;
    }

    // Loop pelos produtos encontrados no XML
    foreach ($produtos as $item) {
        // Assumindo que você já tenha uma função para verificar se o produto existe
        if (produto_existe($produto_id)) {
            echo "<div><p>Produto com ID $produto_id já existe.</p></div>";
            continue;
        }

        // Criação de um novo produto no WooCommerce
        $produto = new WC_Product_Simple();
        $produto->set_name(sanitize_text_field((string)$item->title));
        $produto->set_description(sanitize_textarea_field((string)$item->description));
        $produto->set_regular_price(sanitize_text_field((string)$item->xpath('g:price')[0]));
        $produto->set_sku($produto_id);
        
        // Sanitização dos campos adicionais aqui

                // Atualiza o estoque e o status do produto com base na disponibilidade
if ($disponibilidade == 'in stock') {
    $produto->set_manage_stock(true); // Habilita gerenciamento de estoque
    $produto->set_stock_quantity(15);  // Define a quantidade, ajuste conforme necessário
    $produto->set_stock_status('instock'); // Define como disponível
} else if ($disponibilidade == 'out of stock') {
    $produto->set_manage_stock(true); // Considera que o gerenciamento de estoque deve ser habilitado
    $produto->set_stock_quantity(0);  // Define a quantidade como 0
    $produto->set_stock_status('outofstock'); // Define como indisponível
}
  
          // Define a categoria do produto
          $categoria_nome = sanitize_text_field((string)$item->xpath('g:product_type')[0]);
          if (!empty($categoria_nome)) {
              $categoria_id = term_exists($categoria_nome, 'product_cat');
              if ($categoria_id) {
                  $produto->set_category_ids([$categoria_id['term_id']]);
              } else {
                  // Cria a categoria se ela não existir
                  $nova_categoria = wp_insert_term($categoria_nome, 'product_cat');
                  if (!is_wp_error($nova_categoria)) {
                      $produto->set_category_ids([$nova_categoria['term_id']]);
                  }
              }
          }
  
          // Importa e define a imagem do produto
          $imagem_url = esc_url((string)$item->xpath('g:image_link')[0]);
          $imagem_id = importar_imagem_para_biblioteca_media($imagem_url, $produto_id_wc);
          if (!is_wp_error($imagem_id)) {
              $produto->set_image_id($imagem_id);
          }
        
        // Salvar produto
        $produto_id_wc = $produto->save();
        
        // Atualizar metadados do produto, como ID externo, se necessário
        update_post_meta($produto_id_wc, 'id_externo', $produto_id);
        
        echo "<div><p>Produto '{$produto->get_name()}' importado com sucesso.</p></div>";
    }
}


// Implementação da função 'produto_existe'
function produto_existe($produto_id) {
    $args = [
        'post_type' => 'product',
        'meta_query' => [
            [
                'key' => 'id_externo',
                'value' => $produto_id,
                'compare' => '='
            ]
        ]
    ];
    $query = new WP_Query($args);
    return $query->have_posts();
}


function importar_imagem_para_biblioteca_media($imagem_url, $produto_id) {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    // Define o contexto para evitar que a imagem seja exibida diretamente na tela após o upload
    $temp = download_url($imagem_url);

    if (is_wp_error($temp)) {
        error_log('Erro ao baixar imagem: ' . $temp->get_error_message());
        return null;
    }

    $file_array = array(
        'name' => basename($imagem_url),
        'tmp_name' => $temp
    );

    // Verifica o tipo do arquivo
    $filetype = wp_check_filetype(basename($imagem_url), null);
    $file_array['type'] = $filetype['type'];

    // Faz o upload do arquivo para a biblioteca de mídia
    $id_anexo = media_handle_sideload($file_array, 0);

    // Verifica se houve erro no upload
    if (is_wp_error($id_anexo)) {
        @unlink($file_array['tmp_name']); // Limpa qualquer arquivo temporário
        error_log('Erro ao importar imagem para a biblioteca de mídia: ' . $id_anexo->get_error_message());
        return null;
    }

    return $id_anexo;
}






// Registrar e definir configurações
add_action('admin_init', 'registrar_configuracoes_xml');

function registrar_configuracoes_xml() {
    register_setting('importar-xml-produtos-options', 'url_xml_produto');
    add_settings_section('importar-xml-produtos-main', 'Configurações Principais', 'descricao_secao_xml', 'importar-xml-produtos');
    add_settings_field('url_xml_produto', 'URL do XML', 'url_xml_produto_callback', 'importar-xml-produtos', 'importar-xml-produtos-main');
    add_settings_field('url_xml_produto', 'URL do XML', 'url_xml_produto_callback', 'configuracoes-importacao-xml', 'importar-xml-produtos-main');
    add_settings_field('horario_atualizacao_produto', 'Horário de Atualização', 'horario_atualizacao_callback', 'configuracoes-importacao-xml', 'importar-xml-produtos-main');
}

function descricao_secao_xml() {
    echo '<p>Insira a URL do arquivo XML dos produtos.</p>';
}

function url_xml_produto_callback() {
    $url = get_option('url_xml_produto');
    echo '<input type="text" id="url_xml_produto" name="url_xml_produto" value="' . esc_attr($url) . '" size="50" />';
}






function adicionar_menu_atualizacao() {
    add_menu_page(
        'Atualizar Produtos XML', // Título da página
        'Atualizar XML', // Título do menu
        'manage_options', // Capacidade necessária para acessar essa página
        'atualizar-produtos-xml', // Slug do menu
        'pagina_atualizacao_xml' // Nome da função que renderiza a página
    );
}
add_action('admin_menu', 'adicionar_menu_atualizacao');


function pagina_atualizacao_xml() {
    ?>
    <div class="wrap">
        <h1>Atualizar Produtos XML</h1>
        <form method="post">
            <?php submit_button('Iniciar Atualização', 'primary', 'atualizar_xml'); ?>
        </form>
    </div>
    <?php

    // Verifica se o botão foi clicado
    if (isset($_POST['atualizar_xml'])) {
        iniciar_atualizacao_produtos();
    }
}



function iniciar_atualizacao_produtos() {
    $url_xml = get_option('url_xml_produto');
    if (empty($url_xml)) {
        echo '<div class="notice notice-error"><p>URL do XML não configurada.</p></div>';
        return;
    }

    $xml = simplexml_load_file($url_xml);
    if ($xml === false) {
        echo '<div class="notice notice-error"><p>Falha ao carregar XML.</p></div>';
        return;
    }

    $xml->registerXPathNamespace('g', 'http://base.google.com/ns/1.0');
    $produtos = $xml->xpath('//channel/item');
    
    foreach ($produtos as $item) {
        $produto_id = (string)$item->id;
        $args = [
            'post_type' => 'product',
            'meta_query' => [
                [
                    'key' => 'id_externo',
                    'value' => $produto_id
                ]
            ]
        ];
        $posts = get_posts($args);

        if (!empty($posts)) {
            // Produto existe, atualize-o
            atualizar_produto_com_base_no_xml($posts[0]->ID, $item);
        } else {
            // Produto não existe, ignore
            continue;
        }
    }

    echo '<div class="notice notice-success"><p>Atualização concluída.</p></div>';
}

function atualizar_produto_com_base_no_xml($produto_id_wc, $item_xml) {
    $produto = wc_get_product($produto_id_wc);

    // Supondo que a estrutura do seu XML seja semelhante a isto:
    // <item>
    //   <title>Nome do Produto</title>
    //   <description>Descrição do Produto</description>
    //   <g:price>29.99</g:price>
    //   <g:availability>in stock</g:availability>
    //   <g:image_link>url_da_imagem</g:image_link>
    // </item>
    
    // Registra o namespace 'g' se necessário
    $item_xml->registerXPathNamespace('g', 'http://base.google.com/ns/1.0');

    // Atualiza o nome/título do produto
    $produto->set_name((string)$item_xml->title);

    // Atualiza a descrição do produto
    $produto->set_description((string)$item_xml->description);

    // Atualiza o preço regular do produto
    $preco = (string)$item_xml->xpath('g:price')[0];
    $preco = preg_replace('/[^0-9.]/', '', $preco); // Remove qualquer caractere que não seja número ou ponto
    $produto->set_regular_price($preco);

    // Atualiza a disponibilidade do estoque
    $disponibilidade = (string)$item_xml->xpath('g:availability')[0];
    if ($disponibilidade == 'in stock') {
        $produto->set_stock_status('instock');
    } else {
        $produto->set_stock_status('outofstock');
    }

    // Atualiza a imagem do produto (exemplo simplificado, veja a nota abaixo)
    $imagem_url = (string)$item_xml->xpath('g:image_link')[0];
    $imagem_id = importar_imagem_para_biblioteca_media($imagem_url, $produto_id_wc);
    if ($imagem_id) {
        $produto->set_image_id($imagem_id);
    }

    // Salva as alterações no produto
    $produto->save();
}

ini_set('memory_limit', '1024M');
set_time_limit(300); // Define o tempo máximo de execução para 5 minutos
