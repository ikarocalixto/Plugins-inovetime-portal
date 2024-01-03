jQuery.noConflict();
(function($) {
    $(document).ready(function() {

$(document).on('click', '.usuario', function() {
    var userID = $(this).data('id');
    $('.notificacoes[data-id="' + userID + '"]').toggle();
});



        if ($('#meu-plugin-icone-sino').length && $('#meu-plugin-popup-notificacoes').length) {
            var ATUALIZAR_INTERVALO = 5000;
            var $numeroNotificacoes = $('<span>').css({
                'background-color': 'red',
                'color': 'white',
                'border-radius': '50%',
                'padding': '0 5px',
                'position': 'absolute',
                'top': '0',
                'right': '0'
            }).hide().appendTo('#meu-plugin-icone-sino');

            var $popupNotificacoes = $('#meu-plugin-popup-notificacoes');
            var $marcarTudoComoLidoBtn = $('<button>').text('Marcar tudo como lido').css({
    'margin-top': '10px',
    'padding': '5px 10px',
    'cursor': 'pointer'
}).appendTo($popupNotificacoes);
            
$('<button>').text('Teste').appendTo($popupNotificacoes);

$marcarTudoComoLidoBtn.on('click', function() {
    $.ajax({
        url: meu_plugin_ajax.ajax_url,
        type: 'POST',
        data: { 
            action: 'meu_plugin_marcar_todas_notificacoes_como_lidas'
        },
        success: function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                $popupNotificacoes.empty().append('<p>Nenhuma notificação nova.</p>');
                $numeroNotificacoes.hide();
            } else {
                console.error(res.message);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Erro na requisição AJAX:", textStatus, errorThrown);
        }
    });
});

            var $popupCloseBtn = $('<button>').html('&times;').css({
                'position': 'absolute',
                'right': '10px',
                'top': '10px',
                'background': 'none',
                'border': 'none',
                'font-size': '20px',
                'cursor': 'pointer'
            }).appendTo($popupNotificacoes);

            $popupCloseBtn.on('click', function() {
                $popupNotificacoes.hide();
            });


            var atualizarNumeroNotificacoes = function() {
                $.ajax({
                    url: meu_plugin_ajax.ajax_url,
                    data: { action: 'meu_plugin_verificar_notificacoes' },
                    success: function(data) {
                        var numeroNotificacoes = parseInt(data);
                        $numeroNotificacoes.text(numeroNotificacoes);
                        if (numeroNotificacoes > 0) {
                            $numeroNotificacoes.show();
                        } else {
                            $numeroNotificacoes.hide();
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Erro na requisição AJAX:", textStatus, errorThrown);
                    }
                });
            };

           $('#meu-plugin-icone-sino').click(function() {
    $.ajax({
        url: meu_plugin_ajax.ajax_url,
        data: { action: 'meu_plugin_pegar_notificacoes' },
        success: function(data) {
            var notificacoes = JSON.parse(data);
            $popupNotificacoes.empty();
            if (notificacoes.length === 0) {
                $popupNotificacoes.append('<p>Nenhuma notificação nova.</p>');
            } else {
                notificacoes.forEach(function(notificacao) {
                    // Formata a data aqui
                    var dataFormatada = new Date(notificacao.data_envio).toLocaleDateString("pt-BR", {
                        year: 'numeric', month: 'long', day: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    });

                    var notificacaoHTML = '<div class="notificacao">';
                    notificacaoHTML += '<p class="notificacao-data">' + dataFormatada + '</p>'; // Inclui a data
                    if (notificacao.url_redirecionamento) {
                        notificacaoHTML += '<a href="' + notificacao.url_redirecionamento + '" class="notificacao-link" data-id="' + notificacao.id + '">';
                    }
                    notificacaoHTML += '<h3>' + notificacao.mensagem + '</h3>';
                    if (notificacao.url_redirecionamento) {
                        notificacaoHTML += '</a>';
                    }
                    if (notificacao.imagem) {
                        notificacaoHTML += '<img src="' + notificacao.imagem + '" alt="Notificação" />';
                    }
                    notificacaoHTML += '</div>';
                    $popupNotificacoes.append(notificacaoHTML);
                });
            }
            $popupNotificacoes.show();
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Erro na requisição AJAX:", textStatus, errorThrown);
        }
    });
});

// Resto do seu código...

            atualizarNumeroNotificacoes();
            setInterval(atualizarNumeroNotificacoes, ATUALIZAR_INTERVALO);

            $(document).on('click', function(event) {
                if (!$(event.target).closest('#meu-plugin-popup-notificacoes').length &&
                    !$(event.target).closest('#meu-plugin-icone-sino').length) {
                    $('#meu-plugin-popup-notificacoes').hide();
                }
            });

            $popupNotificacoes.on('click', '.notificacao-link', function(e) {
                e.preventDefault();
                var notificacaoId = $(this).data('id');
                $.ajax({
                    url: meu_plugin_ajax.ajax_url,
                    type: 'POST',
                    data: { 
                        action: 'meu_plugin_marcar_notificacao_como_lida',
                        id: notificacaoId
                    },
                    success: function(response) {
                        var res = JSON.parse(response);
                        if (res.status === 'success') {
                            window.location.href = e.currentTarget.href;
                        } else {
                            console.error(res.message);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Erro na requisição AJAX:", textStatus, errorThrown);
                    }
                });
            });
        }



    });
})(jQuery);
