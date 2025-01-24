(function() {
    var container = document.createElement('div');
    container.id = 'chatbot-container';
    container.style.position = 'fixed';
    container.style.bottom = '20px';
    container.style.right = '20px';
    container.style.width = '300px';
    container.style.height = '400px';
    container.style.border = '1px solid #ccc';
    container.style.background = '#fff';
    container.style.overflow = 'auto';
    container.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
    container.style.borderRadius = '8px';
    container.style.padding = '10px';

    document.body.appendChild(container);

    var welcomeMessage = document.createElement('div');
    welcomeMessage.textContent = 'Olá! Como posso te ajudar?';
    welcomeMessage.style.marginBottom = '10px';
    welcomeMessage.style.padding = '10px';
    welcomeMessage.style.background = '#f0f0f0';
    welcomeMessage.style.borderRadius = '5px';
    container.appendChild(welcomeMessage);

    var inputContainer = document.createElement('div');
    inputContainer.style.display = 'flex';
    inputContainer.style.marginTop = 'auto';

    var input = document.createElement('input');
    input.type = 'text';
    input.placeholder = 'Digite sua mensagem...';
    input.style.flex = '1';
    input.style.padding = '10px';
    input.style.border = '1px solid #ccc';
    input.style.borderRadius = '5px';
    input.style.marginRight = '5px';
    inputContainer.appendChild(input);

    var button = document.createElement('button');
    button.textContent = 'Enviar';
    button.style.padding = '10px';
    button.style.border = 'none';
    button.style.background = '#0073aa';
    button.style.color = '#fff';
    button.style.borderRadius = '5px';
    inputContainer.appendChild(button);

    container.appendChild(inputContainer);

    // Função para enviar a mensagem via REST API
    button.addEventListener('click', function() {
        var message = input.value.trim();
        if (!message) return;

        // Exibe a mensagem do usuário
        var userMessage = document.createElement('div');
        userMessage.textContent = message;
        userMessage.style.textAlign = 'right';
        userMessage.style.marginBottom = '10px';
        userMessage.style.padding = '10px';
        userMessage.style.background = '#0073aa';
        userMessage.style.color = '#fff';
        userMessage.style.borderRadius = '5px';
        container.appendChild(userMessage);

        // Limpa o input
        input.value = '';

        // Usando a REST API para enviar a mensagem
        fetch('https://heygen.devhouse.com.br/wp-json/chatbot/v1/send_message', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                message: message,
                user_id: localStorage.getItem('chatbot_user_id'),
                chatbot_id: localStorage.getItem('chatbot_id'),
            }),
        })
        .then(response => response.json())
        .then(data => {
            console.log(data);
            if (data.status === 'success') {
                var botResponse = document.createElement('div');
                botResponse.textContent = data.response;
                botResponse.style.textAlign = 'left';
                botResponse.style.marginBottom = '10px';
                botResponse.style.padding = '10px';
                botResponse.style.background = '#f0f0f0';
                botResponse.style.borderRadius = '5px';
                container.appendChild(botResponse);
            } else {
                console.error('Erro: ', data.message);
            }
        })
        .catch(error => {
            console.error('Erro ao enviar mensagem via REST API: ', error);
        });
    });
})();