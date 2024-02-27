
<?php
/**
 * Plugin Name: envio de email 
 * Description: Um plugin para envio de email com modelo salvo 
 * Version: 3.0
 * Author: IKARO CALIXTO- INOVETIME
 */


function email_settings_page() {
    add_menu_page(
        'Configurações do Email',
        'Configurações do Email',
        'manage_options',
        'email-settings',
        'email_settings_page_content',
        'dashicons-email',
        20
    );
}
add_action('admin_menu', 'email_settings_page');

function email_settings_page_content() {
    // Verificar se o formulário foi submetido
    if (isset($_POST['email_subject']) && isset($_POST['email_content'])) {
        // Atualizar as opções no banco de dados
        update_option('email_subject', sanitize_text_field($_POST['email_subject']));
        update_option('email_content', wp_kses_post($_POST['email_content']));
        update_option('reminder_days', sanitize_text_field($_POST['reminder_days']));
    }

    // Verificar se o formulário foi submetido
    if (isset($_POST['email_subject']) && isset($_POST['email_content']) && isset($_POST['template_id'])) {
        // ...

        // Atualizar a opção 'template_id' no banco de dados
        update_option('template_id', sanitize_text_field($_POST['template_id']));
    }

    // Se o botão de envio de email de teste foi pressionado, enviar o email de teste
    if (isset($_POST['send_test']) && isset($_POST['test_user'])) {
        $test_user_id = sanitize_text_field($_POST['test_user']);
        $test_user = get_user_by('id', $test_user_id);

        if ($test_user) {
            $total_expense = get_user_meta($test_user->ID, 'total_expense', true);

            $to = sanitize_email($test_user->user_email);
            $subject = get_option('email_subject', '');

            $message_template = get_option('email_content', '');
            $message = str_replace(
                ['[nome_do_usuário]', '[valor_devido]', '[link_para_pagamento]'], 
                [$test_user->display_name, $total_expense, "http://example.com/payment-page"], 
                $message_template
            );

            wp_mail($to, $subject, $message);
        } else {
            echo 'Usuário não encontrado.';
        }
    }

    if (isset($_POST['send_all'])) {
        send_email_to_all_users();
    }

    echo '<div class="admin-email-editor">';
    // ... seu código de formulário aqui ...
    

    // Obter as opções do banco de dados
    $email_subject = get_option('email_subject', '');
    $email_content = get_option('email_content', '');
    $reminder_days = get_option('reminder_days', '2,3,5,15,17,20');


// Obter todos os modelos
    global $wpdb;
    $templates = $wpdb->get_results("SELECT * FROM wp_email_templates");

    // ...

    // Campo de seleção de modelo
    echo '<label for="template_id">Escolha o Modelo:</label><br>';
    echo '<select id="template_id" name="template_id">';
    foreach ($templates as $template) {
        echo '<option value="' . esc_attr($template->id) . '">' . esc_html($template->template_name) . '</option>';
    }
    echo '</select><br>';


    // Obter todos os usuários
    $users = get_users();

    // Exibir o formulário
    echo '<form method="post">';
    echo '<label for="email_subject">Assunto do Email:</label><br>';
    echo '<input type="text" id="email_subject" name="email_subject" value="' . esc_attr($email_subject) . '"><br>';
    echo '<label for="email_content">Conteúdo do Email:</label><br>';
    echo '<textarea id="email_content" name="email_content">' . esc_textarea($email_content) . '</textarea><br>';
    echo '<label for="reminder_days">Dias para enviar lembretes (separados por vírgulas):</label><br>';
    echo '<input type="text" id="reminder_days" name="reminder_days" value="' . esc_attr($reminder_days) . '"><br>';

    // Campo de seleção de usuário para teste
    echo '<label for="test_user">Usuário para teste:</label><br>';
    echo '<select id="test_user" name="test_user">';
    foreach ($users as $user) {
        echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
    }
    echo '</select><br>';

    echo '<input type="submit" name="send_test" value="Enviar Teste"><br>';
    echo '<input type="submit" name="send_all" value="Enviar para todos"><br>'; // Novo botão
    echo '<input type="submit" value="Salvar">';
    echo '</form>';
    echo '</div>';

}

if (!wp_next_scheduled('check_date_and_send_email')) {
    wp_schedule_event(time(), 'daily', 'check_date_and_send_email');
}
add_action('check_date_and_send_email', 'check_date_and_send_email');


// Adicione isso ao seu functions.php ou a um plugin personalizado

// 2. Funções para inserir e recuperar modelos

function insert_email_template($template_name, $email_subject, $email_content) {
    global $wpdb;

    $wpdb->insert('wp_email_templates', array(
      'template_name' => $template_name,
      'email_subject' => $email_subject,
      'email_content' => $email_content,
    ));
}

function get_email_template($template_id) {
    global $wpdb;

    $template = $wpdb->get_row("SELECT * FROM wp_email_templates WHERE id = $template_id");
    return $template;
}

// 3. Página de administração




function email_templates_page_content() {
    // Inserir novo modelo
    if (isset($_POST['new_template_name']) && isset($_POST['new_email_subject']) && isset($_POST['new_email_content'])) {
        insert_email_template($_POST['new_template_name'], $_POST['new_email_subject'], $_POST['new_email_content']);

        // Adicionar uma mensagem de sucesso
        add_settings_error(
            'emailTemplates',
            'emailTemplateAdded',
            'Novo modelo de email adicionado com sucesso.',
            'updated'
        );
    }

    // Mostrar todos os modelos
    global $wpdb;
    $templates = $wpdb->get_results("SELECT * FROM wp_email_templates");

    echo '<div class="wrap">';
    echo '<h1>Modelos de Email</h1>';

    // Mostrar as mensagens
    settings_errors('emailTemplates');

    foreach ($templates as $template) {
        echo '<h2>' . esc_html($template->template_name) . '</h2>';
        echo '<p>Assunto: ' . esc_html($template->email_subject) . '</p>';
        echo '<p>Conteúdo: ' . esc_html($template->email_content) . '</p>';
    }

    // Formulário para adicionar um novo modelo
    echo '<h2>Adicionar novo modelo</h2>';
    echo '<form method="post">';
    echo '<label for="new_template_name">Nome do Modelo:</label><br>';
    echo '<input type="text" id="new_template_name" name="new_template_name"><br>';
    echo '<label for="new_email_subject">Assunto do Email:</label><br>';
    echo '<input type="text" id="new_email_subject" name="new_email_subject"><br>';
    echo '<label for="new_email_content">Conteúdo do Email:</label><br>';
    echo '<textarea id="new_email_content" name="new_email_content"></textarea><br>';
    echo '<input type="submit" value="Adicionar Modelo">';
    echo '</form>';
    echo '</div>';
}

function add_email_templates_page() {
    add_menu_page('Modelos de Email', 'Modelos de Email', 'manage_options', 'email-templates', 'email_templates_page_content');
}
add_action('admin_menu', 'add_email_templates_page');


// final de email teste





function custom_admin_css() {
    echo '
    
       <style>
    .admin-email-editor {
        max-width: 600px;
        margin: auto;
        background: #f1f1f1;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
    }
    .admin-email-editor form {
        display: flex;
        flex-direction: column;
    }
    .admin-email-editor label {
        font-weight: 600;
        margin-top: 10px;
    }
    .admin-email-editor input[type=text], 
    .admin-email-editor select, 
    .admin-email-editor textarea {
        width: 100%;
        padding: 10px;
        margin-top: 5px;
        border-radius: 5px;
        border: 1px solid #ddd;
    }
    .admin-email-editor input[type=submit] {
        margin-top: 20px;
        padding: 10px;
        border: none;
        color: #fff;
        background: #333;
        cursor: pointer;
        border-radius: 5px;
    }
    .admin-email-editor input[type=submit]:hover {
        background: #555;
    }
    .paghiper-notice.success.updated {
    display: none;
}

.updraftmessage.error.updraftupdatesnotice.updraftupdatesnotice-updatesexpiringsoon {
    display: none;
}
</style>

    

    ';
}
add_action('admin_head', 'custom_admin_css');