 jQuery(document).ready(function($) {
    // Seleciona todos os formulários cujo ID começa com "meu_formulario"
    var forms = $('form[id^="meu_formulario"]');
    
    console.log('Número de formulários encontrados:', forms.length); // Log para mostrar quantos formulários foram encontrados

    forms.on('submit', function(e) {
        e.preventDefault();

        // Extrai o número do ID do formulário usando regex
        var formId = $(this).attr('id').match(/\d+/);

        if (formId) {
            formId = formId[0]; // Obtém o primeiro número encontrado
            console.log('Form ID:', formId); // Adiciona um log para o ID do formulário
            console.log('O código jQuery está sendo executado.');

            console.log(meu_script_vars.ajaxurl);

            var form = $(this); // Referência ao formulário atual

            // Certifique-se de que você está acessando ajaxurl corretamente.
            console.log('ajaxurl:', meu_script_vars.ajaxurl); // Adiciona um log para o ajaxurl


// Adicionar notificação para o user_id após o processamento bem-sucedido do formulário
$.ajax({
    type: "POST",
    url: meu_script_vars.ajaxurl,
    data: {
        action: 'inserir_notificacao', // O nome da ação definido no add_action do WordPress
        user_id: form.find('[name="user_id"]').val() // Pega o user_id do formulário
    },
    success: function(response) {
        if (response.success) {
            console.log('Notificação inserida com sucesso para o user_id:', form.find('[name="user_id"]').val());
        } else {
            console.error('Erro ao inserir notificação para o user_id:', form.find('[name="user_id"]').val());
        }
    }
});


            $.ajax({
                type: "POST",
                url: meu_script_vars.ajaxurl,
                data: {
                    action: 'processar_formulario_avancado',
                    nome: form.find('[name="nome"]').val(),
                    email: form.find('[name="email"]').val(),
                    telefone: form.find('[name="telefone"]').val(),
                    user_id: form.find('[name="user_id"]').val(),
                    page_url: form.find('[name="page_url"]').val(),
                    form_id: formId
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Formulário processado com sucesso!');
                        
                        // Após o processamento bem-sucedido, buscar a URL de redirecionamento e redirecionar
                        $.ajax({
                            type: "POST",
                            url: meu_script_vars.ajaxurl,
                            data: {
                                action: 'buscar_url_redirecionamento',
                                form_id: formId
                            },
                            success: function(response) {
                                if (response.success) {
                                    console.log('Redirecionando para:', response.data.url);
                                    window.location.href = response.data.url;
                                } else {
                                    console.error('Erro ao buscar URL de redirecionamento!');
                                    alert('Erro ao buscar URL de redirecionamento!');
                                }
                            }
                        });
                    } else {
                        console.error('Erro ao processar o formulário!');
                        alert('Erro ao processar o formulário!');
                    }
                }
            });
        }
    });
});
