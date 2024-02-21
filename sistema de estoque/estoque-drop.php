<?php
/*
Plugin Name: Estoque Drop
Description: Plugin para gerenciar o estoque de produtos.
Version: 1.0
Author: IKARO CALIXTO 
*/




defined('ABSPATH') or die('Acesso direto não permitido.');

global $wpdb;

$wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}estoque (
    id INT NOT NULL AUTO_INCREMENT,
    nome_produto VARCHAR(255) NOT NULL,
    variacao VARCHAR(50),
    quantidade INT NOT NULL,
    categoria VARCHAR(255) DEFAULT NULL,
     marca VARCHAR(255) DEFAULT NULL,
     preco DECIMAL(10,2) DEFAULT NULL;
    tag_parado BOOLEAN DEFAULT 0,
    tag_sem_estoque BOOLEAN DEFAULT 0,
    tag_ativo BOOLEAN DEFAULT 1,
    PRIMARY KEY (id)
);");


// Função para adicionar uma página ao menu do WordPress
function estoque_drop_menu() {
    add_menu_page('Estoque Drop', 'Estoque Drop', 'manage_options', 'estoque-drop', 'estoque_drop_admin_page');
}

add_action('admin_menu', 'estoque_drop_menu');

// Página de administração
function estoque_drop_admin_page() {
    global $wpdb;






echo '<h3>Importar Produtos</h3>';
echo '<form method="post" enctype="multipart/form-data">';
echo '<input type="file" name="file_upload" accept=".csv">';
echo '<input type="submit" name="importar_produtos" value="Importar">';
echo '</form>';
// logica para ler a planilha 
if (isset($_POST['importar_produtos']) && isset($_FILES['file_upload'])) {
    $file = $_FILES['file_upload'];
    $filename = $file['tmp_name'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $handle = fopen($filename, "r");

    // Loop para processar cada linha do arquivo CSV
while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
    // Supondo que o CSV esteja na ordem: Nome, Categoria, Variação, Tag, Marca, Preço, Quantidade
    $nome_produto = sanitize_text_field($data[0]);
    $categoria = sanitize_text_field($data[1]);
    $variacao = sanitize_text_field($data[2]);
    $tag = sanitize_text_field($data[3]);
    $marca = sanitize_text_field($data[4]); // Quinta coluna (Marca)
    $preco = floatval($data[5]); // Sexta coluna (Preço)
    $quantidade = intval($data[6]); // Sétima coluna (Quantidade)

    $tag_ativo = ($tag == "ativo");
    $tag_sem_estoque = ($tag == "SE");
    $tag_parado = ($tag == "parado");

    // Inserir no banco de dados
    $wpdb->insert(
        "{$wpdb->prefix}estoque",
        array(
            'nome_produto' => $nome_produto,
            'variacao' => $variacao,
            'quantidade' => $quantidade, // Incluímos a coluna 'quantidade'
            'categoria' => $categoria,
            'marca' => $marca, // Incluímos a coluna 'marca'
            'preco' => $preco, // Incluímos a coluna 'preço'
            'tag_parado' => $tag_parado,
            'tag_sem_estoque' => $tag_sem_estoque,
            'tag_ativo' => $tag_ativo
        ),
        array('%s', '%s', '%d', '%s', '%s', '%f', '%d', '%d', '%d')
    );
}

fclose($handle);


        // Notificação de sucesso
        echo '<div class="notice notice-success is-dismissible"><p>Importação de produtos concluída com sucesso.</p></div>';
    } else {
        // Notificação de erro
        echo '<div class="notice notice-error is-dismissible"><p>Erro ao importar arquivo. Por favor, tente novamente.</p></div>';
    }
}

// Adicionar novo produto
if (isset($_POST['adicionar_produto'])) {
    check_admin_referer('adicionar_produto_nonce', 'adicionar_produto_nonce');

    $nome_produto = sanitize_text_field($_POST['nome_produto']);
    $variacao = sanitize_text_field($_POST['variacao']);
    $categoria = sanitize_text_field($_POST['categoria']);
    $marca = sanitize_text_field($_POST['marca']);
    $preco = floatval($_POST['preco']);
    $quantidade = intval($_POST['quantidade']);
    $tag_parado = isset($_POST['tag_parado']) ? 1 : 0;
    $tag_sem_estoque = isset($_POST['tag_sem_estoque']) ? 1 : 0;
    $tag_ativo = isset($_POST['tag_ativo']) ? 1 : 0;

    $sql = $wpdb->prepare(
        "INSERT INTO {$wpdb->prefix}estoque (nome_produto, variacao, quantidade, categoria, marca, preco, tag_parado, tag_sem_estoque, tag_ativo) 
        VALUES (%s, %s, %d, %s, %s, %f, %d, %d, %d)",
        $nome_produto, $variacao, $quantidade, $categoria, $marca, $preco, $tag_parado, $tag_sem_estoque, $tag_ativo
    );

    $result = $wpdb->query($sql);

    if ($result !== false) {
        // Notificação de sucesso
        echo '<div class="notice notice-success is-dismissible"><p>Produto adicionado com sucesso.</p></div>';
    } else {
        // Notificação de erro
        echo '<div class="notice notice-error is-dismissible"><p>Erro ao adicionar produto. Por favor, tente novamente.</p></div>';
    }
}


    
 function atualizar_produto($produto_id, $nome_produto, $variacao, $quantidade, $categoria, $preco, $marca, $tag_parado, $tag_sem_estoque, $tag_ativo) {
    global $wpdb;

 
    $wpdb->update(
        "{$wpdb->prefix}estoque",
        array('nome_produto' => $nome_produto, 'variacao' => $variacao, 'quantidade' => $quantidade, 'categoria' => $categoria, 'marca' => $marca,
        'preco' => $preco, 'tag_parado' => $tag_parado, 'tag_sem_estoque' => $tag_sem_estoque, 'tag_ativo' => $tag_ativo),
        array('id' => $produto_id),
        array('%s', '%s', '%d', '%s', '%d', '%d', '%d'),
        array('%d')
    );
}

// Início do script de edição de produto
// editar produto

if (isset($_POST['editar_produto'])) {
    check_admin_referer('editar_produto_nonce', 'editar_produto_nonce');

    $produto_id = intval($_POST['produto_id']);
    $nome_produto = sanitize_text_field($_POST["nome_produto_{$produto_id}"]);
    $variacao = sanitize_text_field($_POST["variacao_{$produto_id}"]);
    $categoria = sanitize_text_field($_POST["categoria_{$produto_id}"]);
    $marca = sanitize_text_field($_POST["marca_{$produto_id}"]);
    $preco = floatval($_POST["preco_{$produto_id}"]);
    $quantidade = intval($_POST["quantidade_{$produto_id}"]);
    $tag_parado = isset($_POST["tag_parado_{$produto_id}"]) ? 1 : 0;
    $tag_sem_estoque = isset($_POST["tag_sem_estoque_{$produto_id}"]) ? 1 : 0;
    $tag_ativo = isset($_POST["tag_ativo_{$produto_id}"]) ? 1 : 0;

    // Verifica o estado anterior da tag 'sem estoque' e o preço anterior
    $dados_anteriores = $wpdb->get_row($wpdb->prepare("SELECT tag_sem_estoque, preco FROM {$wpdb->prefix}estoque WHERE id = %d", $produto_id));
    $preco_anterior = $dados_anteriores->preco;
    $estado_anterior = $dados_anteriores->tag_sem_estoque;

    // Atualiza o produto
    $resultado = $wpdb->update(
        "{$wpdb->prefix}estoque",
        array(
            'nome_produto' => $nome_produto,
            'variacao' => $variacao,
            'quantidade' => $quantidade,
            'categoria' => $categoria,
            'marca' => $marca,
            'preco' => $preco,
            'tag_parado' => $tag_parado,
            'tag_sem_estoque' => $tag_sem_estoque,
            'tag_ativo' => $tag_ativo
        ),
        array('id' => $produto_id),
        array('%s', '%s', '%d', '%s', '%s', '%f', '%d', '%d', '%d'),
        array('%d')
    );

    if ($resultado !== false) {
        // Verifica se o estado de 'sem estoque' foi alterado
        if ($estado_anterior != $tag_sem_estoque) {
            $mensagem_estoque = $tag_sem_estoque ?
                "O produto '{$nome_produto}' está sem estoque. Atualize agora mesmo!" :
                "O produto '{$nome_produto}' voltou ao estoque. Atualize sua loja e comece a vender!";
            
            $usuarios = $wpdb->get_col("SELECT ID FROM {$wpdb->users}");
            foreach ($usuarios as $user_id) {
                $wpdb->insert(
                    "{$wpdb->prefix}meu_plugin_notificacoes",
                    array(
                        'user_id' => $user_id,
                        'mensagem' => $mensagem_estoque,
                        'imagem' => '',
                        'url_redirecionamento' => 'https://inovetime.com.br/estoque-sistema/',
                        'data_envio' => current_time('mysql'),
                        'lida' => 0
                    ),
                    array('%d', '%s', '%s', '%s', '%s', '%d')
                );
            }
            echo '<div class="notice notice-success is-dismissible"><p>Notificação de estoque enviada com sucesso.</p></div>';
        }

        // Verifica se o preço foi alterado
        if ($preco_anterior != $preco) {
            $mensagem_preco = "O produto '{$nome_produto}' sofreu uma alteração de preço para R$ {$preco}. Confira as novidades!";

            $usuarios = $wpdb->get_col("SELECT ID FROM {$wpdb->users}");
            foreach ($usuarios as $user_id) {
                $wpdb->insert(
                    "{$wpdb->prefix}meu_plugin_notificacoes",
                    array(
                        'user_id' => $user_id,
                        'mensagem' => $mensagem_preco,
                        'imagem' => '',
                        'url_redirecionamento' => 'https://inovetime.com.br/estoque-sistema/',
                        'data_envio' => current_time('mysql'),
                        'lida' => 0
                    ),
                    array('%d', '%s', '%s', '%s', '%s', '%d')
                );
            }
            echo '<div class="notice notice-success is-dismissible"><p>Notificação de alteração de preço enviada com sucesso.</p></div>';
        }

         // Se houve alterações, registre-as no banco de dados de histórico
    if (!empty($campos_alterados)) {
        foreach ($campos_alterados as $campo => $mensagem) {
            $wpdb->insert(
                "{$wpdb->prefix}historico_alteracoes",
                array(
                    'produto_id' => $produto_id,
                    'detalhes' => $mensagem,
                    'data_alteracao' => current_time('mysql')
                ),
                array('%d', '%s', '%s')
            );
        }

        // Exibe uma mensagem de sucesso para o registro no histórico
        echo '<div class="notice notice-success is-dismissible"><p>Alterações registradas com sucesso no histórico.</p></div>';
    }

        echo '<div class="notice notice-success is-dismissible"><p>Produto atualizado com sucesso.</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Erro ao atualizar produto.</p></div>';
    }
}







  // Função de atalho para remover produto
function remover_produto($produto_id) {




    global $wpdb;
    $wpdb->delete("{$wpdb->prefix}estoque", array('id' => $produto_id), array('%d'));
}

// Remover produto
if (isset($_POST['remover_produto'])) {
    check_admin_referer('editar_produto_nonce', 'editar_produto_nonce');

    $produto_id = intval($_POST['produto_id']);
    remover_produto($produto_id);

    // Notificação de sucesso
    echo '<div class="notice notice-success is-dismissible"><p>Produto removido com sucesso.</p></div>';
}


// Continuação da exibição da tabela de produtos...

echo '<h3>Pesquisar Produtos</h3>';
echo '<div class="warp">';

echo '<form  method="post">';
wp_nonce_field('pesquisar_produto_nonce', 'pesquisar_produto_nonce');
echo 'Nome do Produto: <input type="text" name="termo_pesquisa" value="' . esc_attr($_POST['termo_pesquisa']) . '">';
echo '<input type="submit" name="pesquisar_produto" value="Pesquisar">';
echo '</form>';


     // Exibir a tabela de produtos
    $produtos = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}estoque");
 
      echo '<div class="warp"> <h2>Estoque Drop - Página de Administração</h2>';


    echo '<h3>Adicionar Produto</h3>';
    echo '<div class="warp">';

    echo '<form method="post">';
    wp_nonce_field('adicionar_produto_nonce', 'adicionar_produto_nonce');
    echo 'Nome do Produto: <input type="text" name="nome_produto" required>';
    echo 'Variação: <input type="text" name="variacao">';
    echo 'Categoria: <select name="categoria">';
    echo '<option value="Perfume">Perfume</option>';
    echo '<option value="Bolsa">Bolsa</option>';
    echo '<option value="Maquiagem">Maquiagem</option>';
    echo '<option value="Roupa">Roupa</option>';
    echo '</select>';
    echo 'Marca: <input type="text" name="marca" required>'; // Campo de marca
    echo 'Preço: <input type="number" step="0.01" name="preco" required>'; // Campo de preço
    echo 'Quantidade: <input type="number" name="quantidade" required>';
    echo 'Tag Parado: <input type="checkbox" name="tag_parado">';
    echo 'Tag Sem Estoque: <input type="checkbox" name="tag_sem_estoque">';
    echo 'Tag Ativo: <input type="checkbox" name="tag_ativo" checked>';
    echo '<input type="submit" name="adicionar_produto" value="Adicionar Produto">';
    echo '</form>';



// Tabela de Produtos
echo '<h3>Lista de Produtos</h3>';

echo '<form  class="warp" method="post">';
wp_nonce_field('editar_produto_nonce', 'editar_produto_nonce');
echo '<table class="warp">';
echo '<thead><tr><th>ID</th><th>Nome do Produto</th><th>Variação</th><th>Categoria</th><th>Marca</th><th>Preço</th><th>Quantidade</th><th>Tag Parado</th><th>Tag Sem Estoque</th><th>Tag Ativo</th><th>Ações</th></tr></thead><tbody>';

$termo_pesquisa = isset($_POST['termo_pesquisa']) ? sanitize_text_field($_POST['termo_pesquisa']) : '';

$sql = "SELECT * FROM {$wpdb->prefix}estoque";
if (!empty($termo_pesquisa)) {
    $sql .= $wpdb->prepare(" WHERE nome_produto LIKE '%%%s%%'", $termo_pesquisa);
}

$produtos = $wpdb->get_results($sql);

foreach ($produtos as $produto) {
        echo '<tr>';
        echo '<td>' . $produto->id . '</td>';
        echo '<td><input type="text" name="nome_produto_' . $produto->id . '" value="' . esc_attr($produto->nome_produto) . '"></td>';
        echo '<td><input type="text" name="variacao_' . $produto->id . '" value="' . esc_attr($produto->variacao) . '"></td>';
        echo '<td><input type="text" name="categoria_' . $produto->id . '" value="' . esc_attr($produto->categoria) . '"></td>';
        echo '<td><input type="text" name="marca_' . $produto->id . '" value="' . esc_attr($produto->marca) . '"></td>';
        echo '<td><input type="number" step="0.01" name="preco_' . $produto->id . '" value="' . esc_attr($produto->preco) . '"></td>';
        echo '<td><input type="number" name="quantidade_' . $produto->id . '" value="' . esc_attr($produto->quantidade) . '"></td>';
        echo '<td><input type="checkbox" name="tag_parado_' . $produto->id . '" ' . checked(1, $produto->tag_parado, false) . '></td>';
        echo '<td><input type="checkbox" name="tag_sem_estoque_' . $produto->id . '" ' . checked(1, $produto->tag_sem_estoque, false) . '></td>';
        echo '<td><input type="checkbox" name="tag_ativo_' . $produto->id . '" ' . checked(1, $produto->tag_ativo, false) . '></td>';
        echo '<td>';
        echo '<input type="hidden" name="produto_id" value="' . $produto->id . '">';
        echo '<input type="submit" name="editar_produto" value="Salvar">';
        echo '<input type="submit" name="remover_produto" value="Remover" onclick="return confirm(\'Tem certeza?\');">';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</form></div> </div>';
}

// Shortcode para mostrar produtos
function estoque_drop_shortcode($atts) {
    global $wpdb;

    $produtos_disponiveis = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}estoque WHERE tag_ativo = 1 AND tag_sem_estoque = 0");
    $produtos_sem_estoque = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}estoque WHERE tag_ativo = 1 AND tag_sem_estoque = 1");

    // Adicione aqui o código para exibir os produtos
    echo '<div class="warp">';
    echo '<h2>Estoque Drop - Produtos Disponíveis</h2>';

    // Formulário de pesquisa
    echo '<form method="post">';
    echo '<label for="search">Buscar Produto:</label>';
    echo '<input type="text" name="search" id="search">';
    echo '<input type="submit" value="Buscar">';
    echo '</form>';

    // Tabela de Produtos Disponíveis
    echo '<h3>Produtos Disponíveis</h3>';
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>ID</th><th>Nome do Produto</th><th>Variação</th><th>Quantidade</th><th>Ações</th></tr></thead><tbody>';

    foreach ($produtos_disponiveis as $produto) {
        echo '<tr>';
        echo '<td>' . $produto->id . '</td>';
        echo '<td>' . $produto->nome_produto . '</td>';
        echo '<td>' . $produto->variacao . '</td>';
        echo '<td>' . $produto->quantidade . '</td>';
        echo '<td><a href="#" class="visualizar-produto" data-id="' . $produto->id . '">Visualizar</a></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    // Tabela de Produtos Sem Estoque
    echo '<h3>Produtos Sem Estoque</h3>';
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>ID</th><th>Nome do Produto</th><th>Variação</th><th>Quantidade</th><th>Ações</th></tr></thead><tbody>';

    foreach ($produtos_sem_estoque as $produto) {
        echo '<tr>';
        echo '<td>' . $produto->id . '</td>';
        echo '<td>' . $produto->nome_produto . '</td>';
        echo '<td>' . $produto->variacao . '</td>';
        echo '<td>' . $produto->quantidade . '</td>';
        echo '<td><a href="#" class="visualizar-produto" data-id="' . $produto->id . '">Visualizar</a></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';


    echo '</div>';

    // JavaScript para visualizar detalhes do produto
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var links = document.querySelectorAll(".visualizar-produto");
            links.forEach(function(link) {
                link.addEventListener("click", function(e) {
                    e.preventDefault();
                    var produtoId = this.getAttribute("data-id");
                    // Adicione aqui a lógica para exibir os detalhes do produto com o ID produtoId
                    alert("Visualizar detalhes do produto com ID " + produtoId);
                });
            });
        });
    </script>';
}


add_shortcode('estoque_drop', 'estoque_drop_shortcode');


function estoque_drop_relatorio_menu() {
    add_submenu_page(
        'estoque-drop', // Slug da página pai
        'Relatório Estoque Drop', // Título da página
        'Relatório', // Título do menu
        'manage_options', // Capacidade necessária
        'estoque-drop-relatorio', // Slug da subpágina
        'estoque_drop_relatorio_page' // Função que renderiza a página
    );
}

add_action('admin_menu', 'estoque_drop_relatorio_menu');

function estoque_drop_relatorio_shortcode($atts) {
    global $wpdb;
    ob_start();


    // Obter marcas e categorias únicas do banco de dados para os filtros
    $marcas = $wpdb->get_col("SELECT DISTINCT marca FROM {$wpdb->prefix}estoque");
    $categorias = $wpdb->get_col("SELECT DISTINCT categoria FROM {$wpdb->prefix}estoque");

  

    // Lógica para buscar produtos
    $where_clause = '1=1';
    if (!empty($_POST['marca_busca'])) {
        $marca_busca = sanitize_text_field($_POST['marca_busca']);
        $where_clause .= " AND marca = '{$marca_busca}'";
    }
    if (!empty($_POST['categoria_busca'])) {
        $categoria_busca = sanitize_text_field($_POST['categoria_busca']);
        $where_clause .= " AND categoria = '{$categoria_busca}'";
    }
    if (!empty($_POST['status_busca'])) {
        $status_busca = sanitize_text_field($_POST['status_busca']);
        switch ($status_busca) {
            case 'ativo':
                $where_clause .= " AND tag_ativo = 1";
                break;
            case 'sem_estoque':
                $where_clause .= " AND tag_sem_estoque = 1";
                break;
            case 'parado':
                $where_clause .= " AND tag_parado = 1";
                break;
        }
    }
    if (!empty($_POST['nome_produto_busca'])) {
        $nome_produto_busca = sanitize_text_field($_POST['nome_produto_busca']);
        $where_clause .= " AND nome_produto LIKE '%$nome_produto_busca%'";
    }

    $query = "SELECT * FROM {$wpdb->prefix}estoque WHERE {$where_clause}";
    $produtos = $wpdb->get_results($query);


// Realizar a consulta
$ativos = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}estoque WHERE tag_ativo = 1");

// Multiplicar o resultado por 10
$ativosVezesDez = $ativos * 10;


// Contagem de produtos sem estoque
$sem_estoque = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}estoque WHERE tag_sem_estoque = 1");

// Contagem de produtos parados
$parados = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}estoque WHERE tag_parado = 1");

// Multiplicar o resultado por 5
$paradosVezescinco = $parados * 5;
?>

<div class="wrap">
    <h2>Resumo do Estoque</h2>
    <div class="estoque-drop-dashboard">
        <div class="dashboard-card">
            <h3>Total de Produtos Ativos</h3>
            <p><?php echo $ativosVezesDez; ?></p>
        </div>
        <div class="dashboard-card">
            <h3>Produtos Sem Estoque</h3>
            <p><?php echo $sem_estoque; ?></p>
        </div>
        <div class="dashboard-card">
            <h3>Lançamentos</h3>
            <p><?php echo $paradosVezescinco ?></p>
        </div>
    </div>
</div>
   <style>
        
  /* Estoque Drop - estilos */

body {
    font-family: Arial, sans-serif;
    color: #333;
    background: #f4f4f4; /* Adiciona um fundo para a página */
}

.wrap {
    /* ... */
}

.wrap h2 {
    /* ... */
}

.produto-form {
    margin-top: 1rem;
}

.produtos-table {
    margin-top: 1rem;
    border-collapse: collapse;
    width: 100%;
}

.produtos-table thead th {
    background-color: #f9f9f9;
}

.produtos-table tbody td {
    padding: 0.5rem;
    border-bottom: 1px solid #eee;
}

.produtos-table tbody tr:hover {
    background-color: #f5f5f5;
}

/* Ícones de ação */
.edit-icon,
.remove-icon {
    cursor: pointer;
    margin-left: 0.5rem;
}

/* Tooltips */
.tooltip {
    position: relative;
    display: inline-block;
}

.tooltip .tooltiptext {
    visibility: hidden;
    width: 120px;
    background-color: #555;
    color: #fff;
    text-align: center;
    border-radius: 6px;
    padding: 5px;
    position: absolute;
    z-index: 1;
    bottom: 125%;
    left: 50%;
    margin-left: -60px;
    opacity: 0;
    transition: opacity 0.3s;
}

.tooltip:hover .tooltiptext {
    visibility: visible;
    opacity: 1;
}

@media screen and (max-width: 768px) {
    .produtos-table {
        display: block;
        overflow-x: auto;
    }
}


.wrap {
    max-width: 960px;
    margin: 35px auto;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.wrap h2 {
    font-size: 24px;
    color: #21759b;
    margin-bottom: 20px;
    text-align: center;
    padding: 10px;
}

.wrap h3 {
    font-size: 18px;
    color: #21759b;
    margin-top: 30px;
    margin-bottom: 10px;
}

.wrap form {
    margin-bottom: 20px;
}

.wrap input[type="text"],
.wrap input[type="number"] {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
    margin-right: 10px;
}

.wrap input[type="submit"] {
    background-color: #21759b;
    color: #fff;
    border: none;
    padding: 10px 15px;
    border-radius: 4px;
    cursor: pointer;
}

.wrap input[type="submit"]:hover {
    background-color: #1e6a8d;
}

.wrap .notice {
    padding: 10px;
    border-left: 4px solid #ffba00;
    background-color: #fffbea;
    margin-bottom: 20px;
}

.estoque-drop-dashboard {
    display: flex;
    justify-content: space-evenly; /* Distribui o espaço igualmente */
    flex-wrap: wrap; /* Quebra a linha em telas menores */
    gap: 20px; /* Espaço entre os cards */
      
}

.dashboard-card {
    flex: 1;
    min-width: 250px; /* Largura mínima para responsividade */
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    text-align: center;
    transition: transform 0.3s ease;
    position: relative; /* Para posicionamento absoluto de elementos internos */
}

.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.dashboard-card h3 {
    font-size: 1.2em;
    color: #333;
    font-weight: 600;
}

.dashboard-card p {
    font-size: 2em;
    color: #21759b;
    margin: 0;
    font-weight: 700; /* Torna o texto mais grosso */
}

.dashboard-card:before {
    content: '';
    position: absolute;
    top: -1px;
    right: -1px;
    bottom: -1px;
    left: -1px;
    border-radius: 10px;
    background: linear-gradient(45deg, #6EC1E4, #6495ED); /* Gradiente de cores para um visual moderno */
    z-index: -1; /* Coloca o gradiente atrás do conteúdo */
    opacity: 0.7;
}

@media (max-width: 768px) {
    .estoque-drop-dashboard {
        justify-content: center; /* Centraliza os cards em telas menores */
    }
}

div#members_review_notice {
    display: none;
}


:root {
    --primary-color: #21759b;
    --hover-color: #1e6a8d;
    --background-color: #fff;
    --text-color: #333;
    --secondary-text-color: #666;
    --border-color: #ddd;
    --shadow-color: rgba(0, 0, 0, 0.1);
    --padding: 10px;
    --border-radius: 4px;
}

.warp {
    font-family: 'Arial', sans-serif;
    color: var(--text-color);
    max-width: 960px;
    margin: 0 auto;
    padding: var(--padding);
    background-color: var(--background-color);
    box-shadow: 0 4px 8px var(--shadow-color);
    border-radius: var(--border-radius);
}

.warp h2,
.warp h3 {
    color: var(--primary-color);
}

.warp input[type="text"],
.warp input[type="number"],
.warp select {
    padding: var(--padding);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    margin: var(--padding) 0;
    width: 100%;
}

.warp input[type="submit"] {
    background-color: var(--primary-color);
    color: var(--background-color);
    border: none;
    padding: var(--padding);
    border-radius: var(--border-radius);
    cursor: pointer;
    margin-top: var(--padding);
    width: 100%;
}

.warp input[type="submit"]:hover {
    background-color: var(--hover-color);
}

.warp table {
    width: 100%;
    border-collapse: collapse;
    margin-top: var(--padding);
}

.warp table th,
.warp table td {
    padding: var(--padding);
    border-bottom: 1px solid var(--border-color);
}

.warp table th {
    background-color: #f1f1f1;
}
    </style>
 
 
    <?php


    echo '<div class="warp"><h2>Relatório do Estoque Drop</h2>';

    // Formulário de filtro
    echo '<form method="post">';
     // Formulário de filtro
    echo '<form method="post">';

    // Filtro por marca
    echo 'Marca: <select name="marca_busca"><option value="">Todas</option>';
    foreach ($marcas as $marca) {
        echo "<option value='{$marca}'>{$marca}</option>";
    }
    echo '</select>';

    // Filtro por categoria
    echo ' Categoria: <select name="categoria_busca"><option value="">Todas</option>';
    foreach ($categorias as $categoria) {
        echo "<option value='{$categoria}'>{$categoria}</option>";
    }
    echo '</select>';

    // Filtro por status
    echo ' Status: <select name="status_busca">';
    echo '<option value="">Todos</option><option value="ativo">Ativo</option>';
    echo '<option value="sem_estoque">Sem Estoque</option><option value="parado">Parado</option></select>';

    // Campo de busca pelo nome do produto
    echo ' ou Nome do Produto: <input type="text" name="nome_produto_busca">';

    // Botão de busca
    echo '<input type="submit" value="Buscar">';
    echo '</form>';



    // Exibição dos produtos
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Nome</th><th>Variação</th><th>Categoria</th><th>Marca</th><th>Preço</th><th>Quantidade</th><th>Status</th></tr></thead>';
    echo '<tbody>';

    foreach ($produtos as $produto) {
        $status = ($produto->tag_ativo) ? 'Ativo' : 'Inativo';
        $status .= ($produto->tag_sem_estoque) ? ', Sem Estoque' : '';
        $status .= ($produto->tag_parado) ? ', Parado' : '';

        echo "<tr>
                <td>{$produto->id}</td>
                <td>{$produto->nome_produto}</td>
                <td>{$produto->variacao}</td>
                <td>{$produto->categoria}</td>
                <td>{$produto->marca}</td>
                <td>R$ {$produto->preco}</td>
                <td>{$produto->quantidade}</td>
                <td>{$status}</td>
              </tr>";
    }

    echo '</tbody></table>';
    echo '</div>';

 // Retorna o conteúdo capturado
    return ob_get_clean();
}

add_shortcode('estoque_drop_relatorio', 'estoque_drop_relatorio_shortcode');



function meu_plugin_exibir_alteracoes_shortcode($atts) {
    ob_start();

    // Obter as alterações armazenadas no transiente
    $alteracoes = get_transient('meu_plugin_alteracoes_recentes') ?: array();

    // Exibir as alterações
    echo "<div class='meu-plugin-alteracoes'><h3>Últimas Alterações nos Produtos</h3>";
    if (!empty($alteracoes)) {
        echo "<table><thead><tr><th>ID do Produto</th><th>Detalhes da Alteração</th><th>Data da Alteração</th></tr></thead><tbody>";
        foreach ($alteracoes as $alteracao) {
            echo "<tr>
                    <td>{$alteracao['produto_id']}</td>
                    <td>".implode(", ", $alteracao['detalhes'])."</td>
                    <td>{$alteracao['data']}</td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>Nenhuma alteração recente registrada.</p>";
    }
    echo "</div>";

    return ob_get_clean();
}
add_shortcode('exibir_alteracoes_produto', 'meu_plugin_exibir_alteracoes_shortcode');




function estoque_drop_enqueue_styles() {
    // Enfileira o estilo CSS
    wp_enqueue_style('estoque-drop-styles', plugin_dir_url(__FILE__) . 'estoque-drop-styles.css');
}

// Enfileira o estilo apenas na página de administração do plugin
add_action('admin_enqueue_scripts', 'estoque_drop_enqueue_styles');




function meu_custom_admin_style() {
    echo '<style type="text/css">
        /* Seu CSS aqui */
    </style>';
}
add_action('admin_head', 'meu_custom_admin_style');
