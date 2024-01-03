<?php
/*
Plugin Name: produtos para  reserva
Description: Tracks user expenses and provides a shortcode to display a user expense panel
Version: 1.0
Author: inovetime
*/

// Funções para Reservas de Estoque

/**
 * Adiciona produto à lista de reservas do usuário.
 */
function add_product_to_reservation($user_id, $product_id) {
    if (current_user_can('editor') || current_user_can('administrator') || current_user_can('contributor')) {
        $reserved_products = get_user_meta($user_id, 'reserved_products', true);

        if (!is_array($reserved_products)) {
            $reserved_products = [];
        }

        array_push($reserved_products, $product_id);
        update_user_meta($user_id, 'reserved_products', $reserved_products);
    }
}

// Arquivo PHP do seu plugin

function show_reserved_products() {
    echo '<button id="myplugin-toggle-reserved-products">Meus Produtos Reservados Em Estoque</button>';
    echo '<div id="myplugin-reserved-products-container" style="display:none;">';

    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $reserved_products = get_user_meta($user_id, 'reserved_products', true);
        $total_cost = 0.0;

        if (is_array($reserved_products) && !empty($reserved_products)) {
            echo '<table class="myplugin-reserved-table">';
            echo '<thead><tr><th>Nome do Produto</th><th>Quantidade</th><th>Preço</th></tr></thead>';
            echo '<tbody>';

            foreach ($reserved_products as $product) {
                $subtotal = $product['price'] * $product['quantity'];
                echo '<tr class="myplugin-reserved-product-item">';
                
                echo '<td class="myplugin-product-name">' . esc_html($product['name']) . '</td>';
                echo '<td class="myplugin-product-quantity">' . intval($product['quantity']) . '</td>'; 
                echo '<td class="myplugin-product-price">R$ ' . number_format($subtotal, 2, ',', '.') . '</td>';
                
                echo '</tr>';
                $total_cost += $subtotal;
            }

            echo '</tbody>';
            echo '</table>';
            
            echo '<div class="myplugin-total-cost">Valor Total: R$ ' . number_format($total_cost, 2, ',', '.') . '</div>';
        } else {
            echo "Nenhum produto em reserva.";
        }
    } else {
        echo "Você precisa estar logado para ver produtos em reserva.";
    }
    echo '</div>';
}


function reserved_products_shortcode() {
    ob_start();
    show_reserved_products();
    return ob_get_clean();
}

add_shortcode('show_reserved_products', 'reserved_products_shortcode');

function enqueue_myplugin_scripts() {
    wp_enqueue_style('produtos-styles', plugin_dir_url( __FILE__ ) . 'produtos.css');
    wp_enqueue_script('produtos.script', plugin_dir_url( __FILE__ ) . 'produtos.js', array('jquery'), '1.0', true);
}
add_action('wp_enqueue_scripts', 'enqueue_myplugin_scripts');




/**
 * Registra a página de gerenciamento de reservas no painel admin.
 */
function register_reserved_products_page() {
    add_menu_page('Gerenciar Reservas', 'Reservas', 'manage_options', 'manage_reservations', 'manage_reservations_page', 'dashicons-list-view', 6);
}

add_action('admin_menu', 'register_reserved_products_page');



function get_all_reserved_products() {
    $all_users = get_users();
    $all_products = array();

    foreach ($all_users as $user) {
        $user_id = $user->ID;
        $reserved_products = get_user_meta($user_id, 'reserved_products', true);

        if (is_array($reserved_products) && !empty($reserved_products)) {
            foreach ($reserved_products as $product) {
                $all_products[] = $product['name'];
            }
        }
    }

    // Remove duplicates
    $all_products = array_unique($all_products);

    return $all_products;
}

/**
 * Página de gerenciamento de reservas.
 */
function manage_reservations_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handle_post_request();
    }


  $all_products = get_all_reserved_products()
?>
    
 <div class="wrap">
    <h1>Adicionar Produto Reservado</h1>
    <form method="post" class="product-form">
        <label for="product_name">Nome do Produto:</label>
        <select id="product_name" name="product_name">
            <option value="new">-- Criar novo --</option>
            <?php
            foreach ($all_products as $product_name) {
                echo '<option value="' . esc_attr($product_name) . '">' . esc_html($product_name) . '</option>';
            }
            ?>
        </select>
        <div id="new_product_name_container" style="display:none;">
            <label for="new_product_name">Novo Produto:</label>
            <input type="text" id="new_product_name" name="new_product_name">
        </div>
        <br>

        <label for="product_price">Preço de Custo:</label>
        <input type="number" id="product_price" name="product_price" step="0.01" required><br>

        <label for="product_quantity">Quantidade:</label>
        <input type="number" id="product_quantity" name="product_quantity" min="1" step="1" required><br>

        <label for="user_id">Usuário:</label>
        <select id="user_id" name="user_id">
            <?php
            $users = get_users();
            foreach ($users as $user) {
                echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
            }
            ?>
        </select><br>

        <input type="submit" name="add_product" value="Adicionar Reserva">
    </form>
</div>



   <div class="wrap">

        <h1>Cadastre um novo produto </h1>
        <form method="post"class="product-form">
            <label for="product_name">Nome do Produto:</label>
            <input type="text" id="product_name" name="product_name" required><br>

            <label for="product_price">Preço de Custo:</label>
            <input type="number" id="product_price" name="product_price" step="0.01" required><br>
              
              
     <label for="product_quantity">Quantidade:</label>
            <input type="number" id="product_quantity" name="product_quantity" min="1" step="1" required><br>


            <label for="user_id">Usuário:</label>
            <select id="user_id" name="user_id">
                <?php
                $users = get_users();
                foreach ($users as $user) {
                    echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
                }
                ?>
            </select><br>

            <input type="submit" name="add_product" value="Adicionar Reserva">
        </form>
    </div>




<script>
    // Show the text input if the "Create new" option is selected
    document.getElementById('product_name').addEventListener('change', function() {
        var container = document.getElementById('new_product_name_container');
        if (this.value === 'new') {
            container.style.display = 'block';
        } else {
            container.style.display = 'none';
        }
    });
</script>



<?php
// controle de estoque reservados
echo '<h2>Buscar Produtos Reservados</h2>';
echo '<form method="post" action="">
      <select name="search_term">';

// Puxe todos os produtos reservados distintos para criar opções no menu suspenso
$users = get_users(); // Obtém todos os usuários

$distinct_products = [];

foreach ($users as $user) {
    $user_id = $user->ID;
    $reserved_products = get_user_meta($user_id, 'reserved_products', true);

    if (is_array($reserved_products) && !empty($reserved_products)) {
        foreach ($reserved_products as $product) {
            $distinct_products[$product['name']] = true;
        }
    }
}

foreach ($distinct_products as $product_name => $value) {
    echo '<option value="' . esc_attr($product_name) . '">' . esc_html($product_name) . '</option>';
}

echo '  </select>
        <input type="submit" value="Buscar" />
      </form>';

if (isset($_POST['search_term'])) {
    $search_term = sanitize_text_field($_POST['search_term']);
    
    $found_products = [];
    $total_price = 0;
    $total_quantity = 0;

    foreach ($users as $user) {
        $user_id = $user->ID;
        $reserved_products = get_user_meta($user_id, 'reserved_products', true);

        if (is_array($reserved_products) && !empty($reserved_products)) {
            foreach ($reserved_products as $product) {
                if ($product['name'] === $search_term) {
                    $found_products[] = [
                        'user_name' => $user->display_name,
                        'price' => $product['price'],
                        'quantity' => $product['quantity']
                    ];

                    $total_price += $product['price'] * $product['quantity'];
                    $total_quantity += $product['quantity'];
                }
            }
        }
    }

    if (!empty($found_products)) {
        echo '<table>';
        echo '<tr><th>Nome do Produto</th><th>Nome do Usuário</th><th>Preço</th><th>Quantidade</th></tr>';
        
        foreach ($found_products as $product) {
            echo '<tr>';
            echo '<td>' . esc_html($search_term) . '</td>';
            echo '<td>' . esc_html($product['user_name']) . '</td>';
            echo '<td>R$ ' . number_format(esc_html($product['price']), 2, ',', '.') . '</td>';
            echo '<td>' . esc_html($product['quantity']) . '</td>';
            echo '</tr>';
        }

        // Linha mostrando o total
        echo '<tr><td>Total</td><td></td><td>R$ ' . number_format($total_price, 2, ',', '.') . '</td><td>' . $total_quantity . '</td></tr>';
        
        echo '</table>';
    } else {
        echo '<p>Nenhum produto reservado encontrado.</p>';
    }
}

?>
     

<?php

 

// Menu suspenso para listar todos os usuários
echo '<h2>Selecionar Usuário</h2>';
echo '<form method="post" action="">
      <select name="selected_user">
      <option value="">--Selecione--</option>';
$all_users = get_users();
foreach ($all_users as $user) {
    echo '<option value="' . $user->ID . '">' . $user->display_name . '</option>';
}
echo '</select>
      <input type="submit" value="Mostrar Reservas">
      </form>';

// Código para filtrar usuários por pesquisa
if (isset($_POST['search_username'])) {
    $search_username = sanitize_text_field($_POST['search_username']);
    $all_users = get_users(['search' => "*{$search_username}*"]);
}

// Código para filtrar usuários por seleção do menu suspenso
if (isset($_POST['selected_user']) && !empty($_POST['selected_user'])) {
    $selected_user_id = sanitize_text_field($_POST['selected_user']);
    $all_users = get_users(['include' => [$selected_user_id]]);
}

// Restante do código ...
echo ' <h2>Reservas existentes</h2>';

$all_users = get_users();

foreach ($all_users as $user) {
    $user_id = $user->ID;
    $reserved_products = get_user_meta($user_id, 'reserved_products', true);

    // Verifique se o usuário tem produtos reservados
    if (is_array($reserved_products) && !empty($reserved_products)) {
        ?>
        <div class="user-reservation">
            <button class="toggle-reservation" data-user-id="<?php echo $user_id; ?>"><?php echo esc_html($user->display_name); ?></button>

            <div class="reserved-products-list" id="user-<?php echo $user_id; ?>-reservations" style="display:none;">
                <ul>
                    <?php
                    foreach ($reserved_products as $index => $product) {
                        ?>
                        <li>
                            <?php echo intval($product['quantity']) . ' - ' . esc_html($product['name']) . ' - R$ ' . number_format($product['price'], 2, ',', '.'); ?>
                            <!-- Formulário para remover um produto específico -->
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="remove_product" value="true">
                                <input type="hidden" name="user_id" value="<?php echo esc_attr($user->ID); ?>">
                                <input type="hidden" name="product_index" value="<?php echo esc_attr($index); ?>">
                                <input type="submit" value="Remover">
                            </form>
                        </li>
                        <?php
                    }
                    ?>
                </ul>
                <!-- Formulário para remover todas as reservas de um usuário -->
                <form method="post">
                    <input type="hidden" name="remove_all_products" value="true">
                    <input type="hidden" name="user_id" value="<?php echo esc_attr($user->ID); ?>">
                    <input type="submit" value="Remover todas as reservas">
                </form>
            </div>
        </div>
        <script>



    
        </script>
        <?php
        
    }
}
?>
    </div>

    

    
    <?php

}
function enqueue_my_script() {
    ?>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            let buttons = document.querySelectorAll('.toggle-reservation');
        
            buttons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    let userId = button.getAttribute('data-user-id');
                    let reservationList = document.getElementById(`user-${userId}-reservations`);
                    
                    if (reservationList) {
                        reservationList.style.display = reservationList.style.display === 'block' ? 'none' : 'block';
                    } else {
                        console.error(`Element with id user-${userId}-reservations not found.`);
                    }
                });
            });
        });
    </script>
    <?php
}
add_action('admin_footer', 'enqueue_my_script');


add_action('admin_menu', 'register_reserved_products_page');
/**
 * Lida com requisições POST para adicionar ou remover produtos.
 */
function handle_post_request() {
    // Adicionar produto
    if (isset($_POST['add_product'])) {
        $user_id = intval($_POST['user_id']);
        $product_name = sanitize_text_field($_POST['product_name']);
        $product_price = floatval($_POST['product_price']);
        
        // Buscar produtos reservados existentes
        $reserved_products = get_user_meta($user_id, 'reserved_products', true);
        
        // Upload da imagem
        if (isset($_FILES['product_image'])) {
            $uploadedfile = $_FILES['product_image'];
            $upload_overrides = array('test_form' => false);
            $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
            if ($movefile && !isset($movefile['error'])) {
                $new_product['image_url'] = $movefile['url'];
            } else {
                // Tratar erro de upload
            }
        }
        
        if (!is_array($reserved_products)) {
            $reserved_products = [];
        }
        
        $product_quantity = intval($_POST['product_quantity']);
        $new_product = [
            'name' => $product_name,
            'price' => $product_price,
            'quantity' => $product_quantity
        ];

        // Adicionar novo produto à lista de produtos reservados
        array_push($reserved_products, $new_product);
        
        // Atualizar metadados do usuário
        update_user_meta($user_id, 'reserved_products', $reserved_products);
    } 
    // Remover produto
    elseif (isset($_POST['remove_product'])) {
        $user_id = intval($_POST['user_id']);
        $product_index = intval($_POST['product_index']);

        $reserved_products = get_user_meta($user_id, 'reserved_products', true);

        if (is_array($reserved_products) && isset($reserved_products[$product_index])) {
            // Remover produto
            unset($reserved_products[$product_index]);
            
            // Reindexar array
            $reserved_products = array_values($reserved_products);
            
            // Atualizar metadados do usuário
            update_user_meta($user_id, 'reserved_products', $reserved_products);
        }
    }
}


/**
 * Renderiza o formulário e lista de produtos reservados.
 */
function render_reservation_form() {
    // Formulário e lista aqui.
}
function add_custom_admin_styles() {
    echo '<style>
    <head>
  <link rel="stylesheet" type="text/css" href="estilos.css">
</head>
    /* Estilizando o formulário de busca */
form {
  margin: 20px 0;
  font-family: Arial, sans-serif;
}

select, input[type="submit"] {
  padding: 10px;
  margin: 5px;
  font-size: 16px;
}

/* Estilizando a tabela de produtos */
table {
  width: 100%;
  border-collapse: collapse;
  margin: 20px 0;
  font-family: Arial, sans-serif;
}

th, td {
  border: 1px solid #ccc;
  text-align: left;
  padding: 8px;
}

th {
  background-color: #f2f2f2;
}

tr:nth-child(even) {
  background-color: #f2f2f2;
}

h2 {
  font-family: Arial, sans-serif;
  font-size: 24px;
  margin-bottom: 10px;
}

        /* Estilos customizados aqui */
       
        .user-reservation {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .toggle-reservation {
            background-color: #0073aa;
            color: #fff;
            padding: 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .toggle-reservation:focus {
            outline: none;
        }
        .reserved-products-list {
            padding-left: 30px;
        }
        /* admin-style.css */


.product-form label,
.product-form input {
    display: block
    ;
    margin-bottom: 10px;
}

.user-reservation {
    margin-top: 20px;
}

.reserved-products-list {
    background-color: #f9f9f9;
    padding: 10px;
    border: 1px solid #ccc;
}
   .wrap {
            
            
            margin: 20px;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .product-form {
            display:flex;
            flex-wrap: wrap;
            
            grid-template-columns: auto auto;
            gap: 10px;
        }
        label, input, select {
            padding: 8px;
            font-size: 14px;
        }
        input, select {
            
        }
        input[type="submit"] {
            grid-column: span 2;
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        
    </style>';
}
add_action('admin_head', 'add_custom_admin_styles');









?>
