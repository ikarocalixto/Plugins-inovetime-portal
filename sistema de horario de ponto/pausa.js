jQuery(document).ready(function($) {
    $('#pausar-btn').click(function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pausar'
            },
            success: function(response) {
                $('#resposta-pausa').html(response);
                $('#pausar-btn').hide();
                $('#voltar-pausa-btn').show();
            }
        });
    });

    $('#voltar-pausa-btn').click(function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'voltar_pausa'
            },
            success: function(response) {
                $('#resposta-pausa').html(response);
                $('#pausar-btn').show();
                $('#voltar-pausa-btn').hide();
            }
        });
    });
});

jQuery(document).ready(function($) {
    $('#encerrar-expediente-btn').on('click', function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'encerrar_expediente'
            },
            success: function(response) {
                window.location.href = 'https://inovetime.com.br/wp-login.php?action=logout&_wpnonce=0479d2597a';
            }
        });
    });
});

jQuery(document).ready(function($) {
    $('#mostrar-historico-btn').on('click', function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'buscar_historico_usuario'
            },
            success: function(response) {
                $('#conteudo-historico').html(response);
                $('#popup-historico').show();
            }
        });
    });

    $('#fechar-historico-btn').on('click', function() {
        $('#popup-historico').hide();
    });
});


