(function () {
    let assistant = {};

    const assistantId = (localStorage.getItem('asst_id')) || null;
    const loadingElement = 'https://i.gifer.com/ZKZx.gif';
    const loadingText = 'carregando...';

    const botAvatarImgBubble = document.createElement('img');
    botAvatarImgBubble.src = loadingElement;
    botAvatarImgBubble.alt = 'Bot Avatar';
    botAvatarImgBubble.style.width = '80px';
    botAvatarImgBubble.style.height = '80px';
    botAvatarImgBubble.style.borderRadius = '999px';

    const botAvatarImg = document.createElement('img');
    botAvatarImg.src = loadingElement;
    botAvatarImg.alt = '';
    botAvatarImg.style.width = '80px';
    botAvatarImg.style.height = '80px';
    botAvatarImg.style.borderRadius = '999px';

    const chatBubble = document.createElement('div');
    chatBubble.id = 'chatbot-bubble';
    chatBubble.textContent = loadingText;
    chatBubble.style.position = 'fixed';
    chatBubble.style.bottom = '26px';
    chatBubble.style.right = '120px';
    chatBubble.style.backgroundColor = '#ffffff';
    chatBubble.style.color = '#000000';
    chatBubble.style.padding = '16px';
    chatBubble.style.fontFamily = '"Nunito Sans", "Open Sans", Arial, sans-serif';
    chatBubble.style.fontSize = '16px';
    chatBubble.style.lineHeight = '1.5';
    chatBubble.style.borderRadius = '10px';
    chatBubble.style.boxShadow = '5px 0px 21px 0px rgba(0, 0, 0, 0.15)';
    chatBubble.style.opacity = '0';
    chatBubble.style.transition = 'opacity 1s ease';
    chatBubble.style.zIndex = '1000';


    document.body.appendChild(chatBubble);
    setTimeout(() => chatBubble.style.opacity = '1', 1000);

    const botMessage = document.createElement('div');
    botMessage.textContent = loadingText;
    botMessage.style.marginLeft = '10px';
    botMessage.style.padding = '10px';
    botMessage.style.background = '#f0f0f0';
    botMessage.style.borderRadius = '5px';
    botMessage.style.maxWidth = '80%';
    botMessage.style.fontSize = '14px';

    // LOTTIE: Adiciona script do player Lottie no head
    const lottieScript = document.createElement('script');
    lottieScript.src = 'https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs';
    lottieScript.type = 'module';
    document.head.appendChild(lottieScript);

    // LOTTIE: Exibe animação no lugar da imagem temporariamente
    const lottie = document.createElement('dotlottie-player');
    lottie.setAttribute('src', 'https://lottie.host/f36c548e-1a03-4d30-91e5-943075333b57/Uzz6ezZRV2.lottie');
    lottie.setAttribute('background', 'transparent');
    lottie.setAttribute('speed', '1');
    lottie.setAttribute('loop', '');
    lottie.setAttribute('autoplay', '');
    lottie.style.width = '50px';
    lottie.style.height = '50px';

    const chatIcon = document.createElement('div');
    chatIcon.style.width = '80px';
    chatIcon.style.height = '80px';
    chatIcon.appendChild(lottie); // mostra Lottie enquanto carrega

    const chatButton = document.createElement('div');
    chatButton.id = 'chatbot-toggle';
    chatButton.style.position = 'fixed';
    chatButton.style.bottom = '20px';
    chatButton.style.right = '20px';
    chatButton.style.width = '80px';
    chatButton.style.height = '80px';
    chatButton.style.backgroundColor = '#FFF';
    chatButton.style.borderRadius = '50%';
    chatButton.style.display = 'flex';
    chatButton.style.alignItems = 'center';
    chatButton.style.justifyContent = 'center';
    chatButton.style.boxShadow = '5px 0px 21px 0px rgba(0, 0, 0, 0.15)';
    chatButton.style.cursor = 'pointer';
    chatButton.style.zIndex = '1000';
    chatButton.appendChild(chatIcon);
    document.body.appendChild(chatButton);

    // Mensagem de boas-vindas
    const welcomeMessage = document.createElement('div');
    welcomeMessage.style.display = 'flex';
    welcomeMessage.style.alignItems = 'center';
    welcomeMessage.style.marginBottom = '10px';

    const botAvatar = document.createElement('div');
    botAvatar.style.width = '50px';
    botAvatar.style.height = '50px';
    botAvatar.style.backgroundColor = '#FFF';
    botAvatar.style.borderRadius = '50%';
    botAvatar.style.display = 'flex';
    botAvatar.style.alignItems = 'center';
    botAvatar.style.justifyContent = 'center';
    botAvatar.style.color = 'white';
    botAvatar.style.fontSize = '14px';

    // Inside chat
    botAvatarImg.style.width = '50px';
    botAvatarImg.style.height = '50px';
    botAvatarImg.style.borderRadius = '50%';

    botAvatar.appendChild(botAvatarImg);

    welcomeMessage.appendChild(botAvatar);
    welcomeMessage.appendChild(botMessage);

    const chatMessages = document.createElement('div');
    chatMessages.id = 'chatbot-messages';
    chatMessages.style.flex = '1';
    chatMessages.style.padding = '20px';
    chatMessages.style.overflowY = 'auto';
    chatMessages.style.backgroundColor = '#FFF';
    // chatMessages.appendChild(welcomeMessage);

    const chatContainer = document.createElement('div');
    chatContainer.id = 'chatbot-container';
    chatContainer.style.position = 'fixed';
    chatContainer.style.bottom = '20px';
    chatContainer.style.right = '20px';
    chatContainer.style.width = '450px';
    chatContainer.style.height = '90vh';
    chatContainer.style.border = 'none';
    chatContainer.style.backgroundColor = '#fff';
    // chatContainer.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
    chatContainer.style.borderRadius = '0';
    chatContainer.style.display = 'none';
    chatContainer.style.flexDirection = 'column';
    chatContainer.style.zIndex = '1000';
    chatContainer.style.overflow = 'hidden';
    chatContainer.appendChild(chatMessages);

    const closeButton = document.createElement('button');
    closeButton.textContent = '×';
    closeButton.style.position = 'absolute';
    closeButton.style.top = '10px';
    closeButton.style.right = '10px';
    closeButton.style.background = '#212429';
    closeButton.style.border = 'none';
    closeButton.style.fontSize = '18px';
    closeButton.style.cursor = 'pointer';
    closeButton.style.color = '#aaa';
    closeButton.style.padding = '8px';
    closeButton.addEventListener('click', () => chatContainer.style.display = 'none');
    chatContainer.appendChild(closeButton);

    const inputContainer = document.createElement('div');
    inputContainer.style.display = 'flex';
    inputContainer.style.padding = '10px';
    inputContainer.style.backgroundColor = '#fff';
    inputContainer.style.borderTop = '1px solid #ccc';

    const input = document.createElement('input');
    input.type = 'text';
    input.placeholder = 'Digite sua mensagem...';
    input.style.flex = '1';
    input.style.padding = '10px';
    input.style.border = 'none';
    input.style.borderRadius = '5px';
    input.style.marginRight = '5px';

    const button = document.createElement('button');
    button.textContent = 'Enviar';
    button.style.padding = '10px';
    button.style.border = 'none';
    button.style.backgroundColor = '#212429';
    button.style.color = '#fff';
    button.style.borderRadius = '5px';

    inputContainer.appendChild(input);
    inputContainer.appendChild(button);
    chatContainer.appendChild(inputContainer);
    document.body.appendChild(chatContainer);

    // Carregar dados do assistente
    async function getAssistant(assistantId) {
        try {
            const response = await fetch('https://charlieapp.io/wp-json/chatbot/v1/get_assistant', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ assistant_id: assistantId }),
            });

            const data = await response.json();

            if (data.status) {
                localStorage.setItem('asst', JSON.stringify(data.assistant));
                assistant = data.assistant;

                // Atualiza conteúdo
                const msg = assistant.metadata?.welcome_message || 'Olá! Como posso ajudar?';
                const img = assistant.metadata?.assistant_image || '';

                chatBubble.textContent = msg;
                botMessage.textContent = msg;
                botAvatarImgBubble.src = img;
                botAvatarImg.src = img;

                // Substitui animação Lottie pelo avatar
                chatIcon.innerHTML = '';
                chatIcon.appendChild(botAvatarImgBubble);

                const chatMessagesHistory = loadChat();
                chatMessagesHistory.forEach(message => {
                    displayMessage(message);
                })
            } else {
                console.error('Erro: ', data.message);
            }
        } catch (err) {
            console.error('Erro na requisição:', err);
        }
    }

    if (assistantId) getAssistant(assistantId);

    chatButton.addEventListener('click', function () {
        if (chatContainer.style.display === 'none') {
            chatContainer.style.display = 'flex';
            chatBubble.style.opacity = '0';
        } else {
            chatContainer.style.display = 'none';
        }
    });

    function sendMessage() {

        const thread_id = localStorage.getItem('td_id') || null;

        var message = input.value.trim();
        if (!message) return;

        var userMessage = document.createElement('div');
        userMessage.style.display = 'flex';
        userMessage.style.justifyContent = 'flex-end';
        userMessage.style.marginBottom = '10px';

        var userAvatar = document.createElement('div');
        userAvatar.style.width = '50px';
        userAvatar.style.height = '50px';
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
        userBubble.style.padding = '16px';
        userBubble.style.background = '#212429';
        userBubble.style.color = '#fff';
        userBubble.style.borderRadius = '5px';
        userBubble.style.maxWidth = '80%';
        userBubble.style.fontSize = '14px';


        userMessage.appendChild(userBubble);
        userMessage.appendChild(userAvatar);
        chatMessages.appendChild(userMessage);

        input.value = '';

        button.disabled = true;
        button.innerHTML = '...';
        input.disabled = true;

        fetch('https://charlieapp.io/wp-json/chatbot/v1/send_message', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                message: message,
                user_id: localStorage.getItem('asst_user_id'),
                chatbot_id: localStorage.getItem('asst_id'),
                thread_id
            }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.thread_id) {
                    localStorage.setItem('td_id', data.thread_id);
                }

                if (data.status === 'success') {

                    var botResponse = document.createElement('div');
                    botResponse.style.display = 'flex';
                    botResponse.style.alignItems = 'center';
                    botResponse.style.marginBottom = '10px';

                    var botResponseAvatar = botAvatar.cloneNode(true);

                    var botBubble = document.createElement('div');

                    // console.log(data.response);

                    function transformarLinks(texto) {
                        // Converte links no formato Markdown [texto](url)
                        texto = texto.replace(
                            /\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g,
                            '<a href="$2" target="_blank" class="text-blue-600 underline">$1</a>'
                        );

                        // Converte URLs em texto puro em links clicáveis
                        texto = texto.replace(
                            /(^|\s)(https?:\/\/[^\s]+)/g,
                            '$1<a href="$2" target="_blank" class="text-blue-600 underline">Clique aqui</a>'
                        );

                        return texto;
                    }

                    botBubble.innerHTML = transformarLinks(data.response);

                    botBubble.style.marginLeft = '10px';
                    botBubble.style.padding = '16px';
                    botBubble.style.background = 'rgb(240, 240, 240)';
                    botBubble.style.borderRadius = '5px';
                    botBubble.style.maxWidth = '80%';
                    botBubble.style.fontSize = '14px';
                    botBubble.style.whiteSpace = 'pre-wrap';
                    botBubble.style.wordBreak = 'break-word';
                    botBubble.style.overflowWrap = 'anywhere';


                    botResponse.appendChild(botResponseAvatar);
                    botResponse.appendChild(botBubble);
                    chatMessages.appendChild(botResponse);

                    // Adicionar a mensagem do usuário e do bot ao armazenamento
                    saveChat({ role: 'user', content: message });
                    saveChat({ role: 'assistant', content: transformarLinks(data.response) });

                    button.disabled = false;
                    button.innerHTML = '';
                    button.innerHTML = 'Enviar';
                    input.disabled = false;

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

    async function saveChat(message) {
        let chat = JSON.parse(localStorage.getItem("chat")) || [];
        chat.push(message);
        localStorage.setItem("chat", JSON.stringify(chat));
    }

    function loadChat() {
        return JSON.parse(localStorage.getItem("chat")) || [];
    }

    function displayMessage(message) {
        const { role, content } = message;

        // function transformarLinks(texto) {
        //     texto = texto.replace(/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g, '<a href="$2" target="_blank" style="color: #2563eb; text-decoration: underline;">Clique aqui</a>');
        //     texto = texto.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" style="color: #2563eb; text-decoration: underline;">Clique aqui</a>');
        //     return texto;
        // }

        if (role === 'user') {
            // Lógica de mensagem do Usuário
            var userMessage = document.createElement('div');
            userMessage.style.display = 'flex';
            userMessage.style.justifyContent = 'flex-end';
            userMessage.style.marginBottom = '10px';

            var userAvatar = document.createElement('div');
            userAvatar.style.width = '50px';
            userAvatar.style.height = '50px';
            userAvatar.style.backgroundColor = '#0073aa';
            userAvatar.style.borderRadius = '50%';
            userAvatar.style.display = 'flex';
            userAvatar.style.alignItems = 'center';
            userAvatar.style.justifyContent = 'center';
            userAvatar.style.color = 'white';
            userAvatar.style.fontSize = '14px';
            userAvatar.textContent = '👤';

            var userBubble = document.createElement('div');
            userBubble.innerHTML = content; // Usamos innerHTML para garantir a renderização de links caso o texto já tenha sido salvo com <a>
            userBubble.style.marginRight = '5px';
            userBubble.style.padding = '16px';
            userBubble.style.background = '#212429';
            userBubble.style.color = '#fff';
            userBubble.style.borderRadius = '5px';
            userBubble.style.maxWidth = '80%';
            userBubble.style.fontSize = '14px';

            userMessage.appendChild(userBubble);
            userMessage.appendChild(userAvatar);
            chatMessages.appendChild(userMessage);

        } else if (role === 'assistant') {
            // Lógica de mensagem do Assistente
            var botResponse = document.createElement('div');
            botResponse.style.display = 'flex';
            botResponse.style.alignItems = 'center';
            botResponse.style.marginBottom = '10px';

            // Clonamos o avatar do bot (ou criamos um novo se preferir)
            var botResponseAvatar = botAvatar.cloneNode(true);
            // Certificamos que o clone do avatar tem a imagem correta (pode ser necessário atualizar a imagem após o clone)
            if (botResponseAvatar.querySelector('img')) {
                botResponseAvatar.querySelector('img').src = assistant.metadata?.assistant_image || '';
            } else {
                botResponseAvatar.textContent = '🤖'; // Fallback
            }

            var botBubble = document.createElement('div');
            botBubble.innerHTML = content;
            botBubble.style.marginLeft = '10px';
            botBubble.style.padding = '16px';
            botBubble.style.background = 'rgb(240, 240, 240)';
            botBubble.style.borderRadius = '5px';
            botBubble.style.maxWidth = '80%';
            botBubble.style.fontSize = '14px';
            botBubble.style.whiteSpace = 'pre-wrap';
            botBubble.style.wordBreak = 'break-word';
            botBubble.style.overflowWrap = 'anywhere';

            botResponse.appendChild(botResponseAvatar);
            botResponse.appendChild(botBubble);
            chatMessages.appendChild(botResponse);
        }

        chatMessages.scrollTop = chatMessages.scrollHeight;
    }


})();
