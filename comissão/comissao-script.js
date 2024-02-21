document.getElementById('btnSolicitarSaque').onclick = function() {
    document.getElementById('popupSaque').style.display = 'block';
    // Aqui você insere o saldo disponível do usuário, pode ser um valor dinâmico
    document.getElementById('saldoDisponivel').textContent = '500.00'; // Exemplo
};

document.querySelector('.close-btn').onclick = function() {
    document.getElementById('popupSaque').style.display = 'none';
};

document.getElementById('confirmarSaque').addEventListener('click', function() {
    var valorSaque = parseFloat(document.getElementById('valorSaque').value); // Converte o valor do saque para float
    var saldoDisponivel = parseFloat(document.getElementById('valorSaque').max); // Garante que o saldo disponível também é tratado como float

    if (valorSaque < 200) {
        alert('O valor mínimo para saque é de R$ 200.');
        return;
    }

    if (valorSaque > saldoDisponivel) {
        alert('O valor solicitado não pode exceder seu saldo disponível.');
        return;
    }


    // Desabilita o botão e mostra o indicador de carregamento
    document.getElementById('confirmarSaque').disabled = true;
    document.getElementById('loadingIndicator').style.display = 'block';

    // Envio da solicitação via AJAX
    jQuery.post(
        ajaxurl, 
        {
            action: 'solicitar_saque',
            valor_saque: valorSaque
        }, 
        function(response) {
            alert(response.data);
            // Recarrega a página após o alerta
            location.reload();c
            document.getElementById('popupSaque').style.display = 'none';

            // Reabilita o botão e esconde o indicador de carregamento
            document.getElementById('confirmarSaque').disabled = false;
            document.getElementById('loadingIndicator').style.display = 'none';
        }
    ).fail(function() {
        // Caso a solicitação AJAX falhe, também reabilita o botão e esconde o indicador
        alert('Falha ao processar a solicitação.');
        document.getElementById('confirmarSaque').disabled = false;
        document.getElementById('loadingIndicator').style.display = 'none';
    });
});


document.getElementById('openExtratoModal').onclick = function() {
    document.getElementById('extratoModal').style.display = "block";
}

document.getElementsByClassName('close')[0].onclick = function() {
    document.getElementById('extratoModal').style.display = "none";
}

window.onclick = function(event) {
    if (event.target == document.getElementById('extratoModal')) {
        document.getElementById('extratoModal').style.display = "none";
    }
}

function updateUserId() {
    var select = document.getElementById('nome_usuario');
    var selectedOption = select.options[select.selectedIndex];
    var userId = select.options[select.selectedIndex].getAttribute('data-user-id');
    document.getElementById('user_id').value = userId;
}