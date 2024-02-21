<?php
/**
 * Plugin Name: meu plugin de financias 
 * Description:para organização financeira.
 * Version: 1.0
 * Author: IKARO CALIXTO- INOVETIME
 */

function financial_manager_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$wpdb->prefix}custos_fixos (
        id_custo INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        nome_fatura VARCHAR(255) NOT NULL,
        data_vencimento DATE NOT NULL,
        valor DECIMAL(10, 2) NOT NULL
        prioridade INT NOT NULL DEFAULT 1
    ) $charset_collate;

    CREATE TABLE {$wpdb->prefix}metas_financeiras (
        id_meta INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        renda_mensal DECIMAL(10, 2) NOT NULL,
        objetivo_poupanca DECIMAL(10, 2) NOT NULL
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'financial_manager_activate');




// Função para definir a meta financeira de um usuário
function definir_meta_financeira($id_usuario, $renda_mensal, $objetivo_poupanca) {
    global $wpdb;
    $tabela = $wpdb->prefix . 'metas_financeiras';
    
    $resultado = $wpdb->insert(
        $tabela,
        array(
            'id_usuario' => $id_usuario,
            'renda_mensal' => $renda_mensal,
            'objetivo_poupanca' => $objetivo_poupanca
        ),
        array('%d', '%f', '%f')
    );
    
    return $resultado;
}

function adicionar_custo_fixo($id_usuario, $nome_fatura, $data_vencimento, $valor, $prioridade) {
    global $wpdb;
    $tabela =  'wp_custos_fixos';
    
    $resultado = $wpdb->insert(
        $tabela,
        array(
            'id_usuario' => $id_usuario,
            'nome_fatura' => $nome_fatura,
            'data_vencimento' => $data_vencimento,
            'valor' => $valor,
            'prioridade' => $prioridade // Adicione a prioridade aqui
        ),
        array('%d', '%s', '%s', '%f', '%d') // Lembre-se de adicionar '%d' para a prioridade
    );
    
    return $resultado;
}


// Função para atualizar a meta financeira de um usuário
function atualizar_meta_financeira($goal_id, $id_usuario, $renda_mensal, $objetivo_poupanca) {
    global $wpdb;
    $tabela = $wpdb->prefix . 'metas_financeiras';
    
    $resultado = $wpdb->update(
        $tabela,
        array(
            'renda_mensal' => $renda_mensal,
            'objetivo_poupanca' => $objetivo_poupanca
        ),
        array('goal_id' => $goal_id, 'id_usuario' => $id_usuario),
        array('%f', '%f'),
        array('%d', '%d')
    );
    
    return $resultado;
}

// Função para deletar um custo fixo
function deletar_custo_fixo($cost_id, $id_usuario) {
    global $wpdb;
    $tabela =  'wp_custos_fixos';
    
    $resultado = $wpdb->delete(
        $tabela,
        array('cost_id' => $cost_id, 'id_usuario' => $id_usuario),
        array('%d', '%d')
    );
    
    return $resultado;
}

// Função para deletar uma meta financeira
function deletar_meta_financeira($goal_id, $id_usuario) {
    global $wpdb;
    $tabela = $wpdb->prefix . 'metas_financeiras';
    
    $resultado = $wpdb->delete(
        $tabela,
        array('goal_id' => $goal_id, 'id_usuario' => $id_usuario),
        array('%d', '%d')
    );
    
    return $resultado;
}

function shortcode_gerenciador_financeiro() {
    // Verifica se o usuário está logado
    if (!is_user_logged_in()) {
        return 'Você precisa estar logado para acessar esta funcionalidade.';
    }

    $form_html = '
    <h2>Adicionar Custo Fixo</h2>
    <form action="' . esc_url($_SERVER['REQUEST_URI']) . '" method="post">
        <p>
            <label for="nome_fatura">Nome da Fatura:</label>
            <input type="text" name="nome_fatura" value="" required>
        </p>
        <p>
            <label for="data_vencimento">Data de Vencimento:</label>
            <input type="date" name="data_vencimento" value="" required>
        </p>
        <p>
            <label for="valor">Valor (R$):</label>
            <input type="number" step="0.01" name="valor" value="" required>
        </p>
        <p>
            <label for="prioridade">Prioridade:</label>
            <select name="prioridade" required>
                <option value="1">Alta</option>
                <option value="2">Média</option>
                <option value="3">Baixa</option>
            </select>
        </p>
        <p><input type="submit" name="gerenciador_financeiro_submit" value="Adicionar"></p>
    </form>
    ';

    // Processa o formulário, se necessário
    $mensagem = processa_formulario_custo_fixo();
    if ($mensagem) {
        $form_html .= '<p>' . $mensagem . '</p>';
    }

    return $form_html;
}


add_shortcode('gerenciador_financeiro', 'shortcode_gerenciador_financeiro');


function processa_formulario_custo_fixo() {
    if (isset($_POST['gerenciador_financeiro_submit'])) {
        $nome_fatura = sanitize_text_field($_POST['nome_fatura']);
        $data_vencimento = sanitize_text_field($_POST['data_vencimento']);
        $valor = sanitize_text_field($_POST['valor']);
        $prioridade = intval($_POST['prioridade']); // Certifique-se de capturar e sanitizar a prioridade

        $id_usuario = get_current_user_id();
        $resultado = adicionar_custo_fixo($id_usuario, $nome_fatura, $data_vencimento, $valor, $prioridade);
        
        if ($resultado) {
            return 'Custo fixo adicionado com sucesso.';
        } else {
            return 'Houve um erro ao adicionar o custo fixo.';
        }
    }

    return null; // Nenhuma ação de formulário detectada
}






function shortcode_informar_recebimento() {
    if (!is_user_logged_in()) {
        return 'Você precisa estar logado para acessar esta funcionalidade.';
    }

    $form_html = '
    <h2>Informar Recebimento</h2>
    <form id="formRecebimento">
        <p>
            <label for="valor_recebido">Valor Recebido:</label>
            <input type="number" id="valor_recebido" name="valor_recebido" step="0.01" required>
        </p>
        <p><button type="button" id="calcularPagamentos">Calcular Pagamentos</button></p>
    </form>
    <div id="sugestoesPagamento"></div>
    ';

    return $form_html;
}
add_shortcode('informar_recebimento', 'shortcode_informar_recebimento');


function listar_custos_fixos_usuario() {
    // Verifica se um usuário está logado
    if (!is_user_logged_in()) {
        return 'Você precisa estar logado para ver esta informação.';
    }

    global $wpdb;
    $id_usuario = get_current_user_id(); // Pega o ID do usuário logado
    $tabela = 'wp_custos_fixos'; // Substitua pelo nome correto da sua tabela se for diferente

    // Busca os custos fixos do usuário no banco de dados
    $custos = $wpdb->get_results($wpdb->prepare(
        "SELECT nome_fatura, data_vencimento, valor, prioridade FROM $tabela WHERE id_usuario = %d ORDER BY data_vencimento ASC",
        $id_usuario
    ));

    // Inicia a tabela HTML
    $html = '<table>';
    $html .= '<thead><tr><th>Nome da Fatura</th><th>Data de Vencimento</th><th>Valor</th><th>Prioridade</th></tr></thead>';
    $html .= '<tbody>';

    // Checa se há custos fixos e os exibe
    if ($custos) {
        foreach ($custos as $custo) {
            $html .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>R$ %s</td><td>%d</td></tr>',
                esc_html($custo->nome_fatura),
                esc_html($custo->data_vencimento),
                esc_html(number_format($custo->valor, 2, ',', '.')),
                esc_html($custo->prioridade)
            );
        }
    } else {
        $html .= '<tr><td colspan="4">Nenhum custo fixo encontrado.</td></tr>';
    }

    $html .= '</tbody></table>';

    return $html;
}

add_shortcode('listar_custos_usuario', 'listar_custos_fixos_usuario');

