(function () {
    var chatButton = document.createElement('div');
    chatButton.id = 'chatbot-toggle';
    chatButton.style.position = 'fixed';
    chatButton.style.bottom = '20px';
    chatButton.style.right = '20px';
    chatButton.style.width = '50px';
    chatButton.style.height = '50px';
    chatButton.style.backgroundColor = '#0073aa';
    chatButton.style.borderRadius = '50%';
    chatButton.style.display = 'flex';
    chatButton.style.alignItems = 'center';
    chatButton.style.justifyContent = 'center';
    chatButton.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
    chatButton.style.cursor = 'pointer';
    chatButton.style.zIndex = '1000';

    var chatIcon = document.createElement('div');
    chatIcon.textContent = '💬';
    chatIcon.style.fontSize = '20px';
    chatIcon.style.color = 'white';
    chatButton.appendChild(chatIcon);

    document.body.appendChild(chatButton);

    var chatBubble = document.createElement('div');
    chatBubble.id = 'chatbot-bubble';
    chatBubble.textContent = 'Olá! Posso ajudar?';
    chatBubble.style.position = 'fixed';
    chatBubble.style.bottom = '26px';
    chatBubble.style.right = '75px';
    chatBubble.style.backgroundColor = '#0073aa';
    chatBubble.style.color = 'white';
    chatBubble.style.padding = '8px 12px';
    chatBubble.style.fontSize = '12px';
    chatBubble.style.borderRadius = '20px';
    chatBubble.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
    chatBubble.style.opacity = '0';
    chatBubble.style.transition = 'opacity 1s ease';
    chatBubble.style.zIndex = '1000';
    document.body.appendChild(chatBubble);

    setTimeout(() => {
        chatBubble.style.opacity = '1';
    }, 1000);

    var chatContainer = document.createElement('div');
    chatContainer.id = 'chatbot-container';
    chatContainer.style.position = 'fixed';
    chatContainer.style.bottom = '20px';
    chatContainer.style.right = '20px';
    chatContainer.style.width = '300px';
    chatContainer.style.height = '400px';
    chatContainer.style.border = '1px solid #ccc';
    chatContainer.style.backgroundColor = '#fff';
    chatContainer.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
    chatContainer.style.borderRadius = '8px';
    chatContainer.style.display = 'none'; 
    chatContainer.style.flexDirection = 'column';
    chatContainer.style.zIndex = '1000';
    chatContainer.style.overflow = 'hidden';

    var chatMessages = document.createElement('div');
    chatMessages.id = 'chatbot-messages';
    chatMessages.style.flex = '1';
    chatMessages.style.padding = '10px';
    chatMessages.style.overflowY = 'auto';
    chatMessages.style.backgroundColor = '#f9f9f9';

    var welcomeMessage = document.createElement('div');
    welcomeMessage.style.display = 'flex';
    welcomeMessage.style.alignItems = 'center';
    welcomeMessage.style.marginBottom = '10px';

    var botAvatar = document.createElement('div');
    botAvatar.style.width = '30px';
    botAvatar.style.height = '30px';
    botAvatar.style.backgroundColor = '#0073aa';
    botAvatar.style.borderRadius = '50%';
    botAvatar.style.display = 'flex';
    botAvatar.style.alignItems = 'center';
    botAvatar.style.justifyContent = 'center';
    botAvatar.style.color = 'white';
    botAvatar.style.fontSize = '14px';
    botAvatar.textContent = '🤖';

    var botMessage = document.createElement('div');
    botMessage.textContent = 'Olá! Como posso te ajudar?';
    botMessage.style.marginLeft = '10px';
    botMessage.style.padding = '10px';
    botMessage.style.background = '#f0f0f0';
    botMessage.style.borderRadius = '5px';
    botMessage.style.maxWidth = '80%';
    botMessage.style.fontSize = '14px';

    welcomeMessage.appendChild(botAvatar);
    welcomeMessage.appendChild(botMessage);
    chatMessages.appendChild(welcomeMessage);

    chatContainer.appendChild(chatMessages);

    var closeButton = document.createElement('button');
    closeButton.textContent = '×';
    closeButton.style.position = 'absolute';
    closeButton.style.top = '10px';
    closeButton.style.right = '10px';
    closeButton.style.background = 'none';
    closeButton.style.border = 'none';
    closeButton.style.fontSize = '18px';
    closeButton.style.cursor = 'pointer';
    closeButton.style.color = '#aaa';

    closeButton.addEventListener('click', function () {
        chatContainer.style.display = 'none';
    });

    chatContainer.appendChild(closeButton);

    var inputContainer = document.createElement('div');
    inputContainer.style.display = 'flex';
    inputContainer.style.padding = '10px';
    inputContainer.style.backgroundColor = '#fff';
    inputContainer.style.borderTop = '1px solid #ccc';

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
    button.style.backgroundColor = '#0073aa';
    button.style.color = '#fff';
    button.style.borderRadius = '5px';
    inputContainer.appendChild(button);

    chatContainer.appendChild(inputContainer);
    document.body.appendChild(chatContainer);

    chatButton.addEventListener('click', function () {
        if (chatContainer.style.display === 'none') {
            chatContainer.style.display = 'flex';
            chatBubble.style.opacity = '0';
        } else {
            chatContainer.style.display = 'none';
        }
    });

    function sendMessage() {
        var message = input.value.trim();
        if (!message) return;

        var userMessage = document.createElement('div');
        userMessage.style.display = 'flex';
        userMessage.style.justifyContent = 'flex-end';
        userMessage.style.marginBottom = '10px';

        var userAvatar = document.createElement('div');
        userAvatar.style.width = '30px';
        userAvatar.style.height = '30px';
        userAvatar.style.backgroundColor = '#0073aa';
        userAvatar.style.borderRadius = '50%';
        userAvatar.style.display = 'flex';
        userAvatar.style.alignItems = 'center';
        userAvatar.style.justifyContent = 'center';
        userAvatar.style.color = 'white';
        userAvatar.style.fontSize = '14px';
        userAvatar.textContent = '👤';

        var userBubble = document.createElement('div');
        userBubble.textContent = message;
        userBubble.style.marginRight = '5px';
        userBubble.style.padding = '5px 10px';
        userBubble.style.background = '#0073aa';
        userBubble.style.color = '#fff';
        userBubble.style.borderRadius = '5px';
        userBubble.style.maxWidth = '80%';
        userBubble.style.fontSize = '14px';

        userMessage.appendChild(userBubble);
        userMessage.appendChild(userAvatar);
        chatMessages.appendChild(userMessage);

        input.value = '';

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
                if (data.status === 'success') {
                    var botResponse = document.createElement('div');
                    botResponse.style.display = 'flex';
                    botResponse.style.alignItems = 'center';
                    botResponse.style.marginBottom = '10px';

                    var botResponseAvatar = botAvatar.cloneNode(true);

                    var botBubble = document.createElement('div');
                    botBubble.textContent = data.response;
                    botBubble.style.marginLeft = '10px';
                    botBubble.style.padding = '5px 10px';
                    botBubble.style.background = '#f0f0f0';
                    botBubble.style.borderRadius = '5px';
                    botBubble.style.maxWidth = '80%';
                    botBubble.style.fontSize = '14px';

                    botResponse.appendChild(botResponseAvatar);
                    botResponse.appendChild(botBubble);
                    chatMessages.appendChild(botResponse);
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                } else {
                    console.error('Erro: ', data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao enviar mensagem via REST API: ', error);
            });
    }

    input.addEventListener('keypress', function (event) {
        if (event.key === 'Enter') {
            sendMessage();
        }
    });

    button.addEventListener('click', sendMessage);
})();