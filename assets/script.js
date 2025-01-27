window.addEventListener('DOMContentLoaded', function () {
    const inputField = document.querySelector('.mensagem');
    const sendButton = document.querySelector('#enviarMensagem');

    sendButton.addEventListener('click', function (event) {
        event.preventDefault();

        const assistantId = document.querySelector('.chatContainer').getAttribute('data-chatbot-id');

        let currHour = new Date();

        const userMsgTemplate = `
                            <div class="flex w-full mt-2 space-x-3 max-w-xs ml-auto justify-end messageInput">
                                <div>
                                    <div class="bg-blue-600 text-white p-3 rounded-l-lg rounded-br-lg">
                                        <p class="text-sm">${document.querySelector(".mensagem").value}</p>
                                    </div>
                                    <span class="text-xs text-gray-500 leading-none">${currHour.getHours() + ":" + currHour.getMinutes()}</span>
                                </div>
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300"></div>
                            </div>`

        let chatBox = document.querySelector(".chatContainer");

        chatBox.innerHTML += userMsgTemplate;
        chatBox.scrollTop = chatBox.scrollHeight;

        const formData = new FormData();
        formData.append('action', 'concierge_chat');
        formData.append('assistantId', assistantId);
        formData.append('mensagem', document.querySelector(".mensagem").value);

        document.querySelector(".mensagem").value = "";
        document.querySelector("#enviarMensagem").disabled = true;
        document.querySelector("#enviarMensagem").classList.add('opacity-90');
        document.querySelector("#enviarMensagem svg").classList.add('animate-spin');

        fetch(conciergeAjax.ajax_url, {
            method: 'POST',
            body: formData
        }).then(response => response.json())
            .then(data => {
                let currHour = new Date();

                data.responseMessage = data.responseMessage.replace(/\\u[\dA-F]{4}/gi,
                    function (match) {
                        return String.fromCharCode(parseInt(match.replace(/\\u/g, ''), 16));
                    });

                data.responseMessage = data.responseMessage.replace("\n", "<br>");

                let aiMsgTemplate = `
                                <div class="flex w-full mt-2 space-x-3 max-w-xs messageInput">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300"></div>
                                    <div>
                                        <div class="bg-gray-300 p-3 rounded-r-lg rounded-bl-lg">
                                            <p class="text-sm">${data.responseMessage}</p>
                                        </div>
                                        <span class="text-xs text-gray-500 leading-none">${currHour.getHours() + ":" + currHour.getMinutes()}</span>
                                    </div>
                                </div>
                                `

                chatBox.innerHTML += aiMsgTemplate;
                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch((error) => {
                console.error('Error:', error);
            }).finally(() => {
                // document.querySelector(".sendMessage").classList.remove('is-loading');
                document.querySelector("#enviarMensagem svg").classList.remove('animate-spin');
                document.querySelector("#enviarMensagem").classList.remove('opacity-90');
                document.querySelector("#enviarMensagem").disabled = false;
            });

    });

    inputField.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            sendButton.click();
        }
    });

    const chatbotSelector = document.getElementById('chatbot-selector');
    const chatContainer = document.querySelector('.chatContainer');

    chatbotSelector.addEventListener('change', function () {
        const selectedChatbotId = chatbotSelector.value;
        chatContainer.setAttribute('data-chatbot-id', selectedChatbotId);
    });

    document.querySelector('#deleteChatbotForm').addEventListener('submit', (event) => {
        event.preventDefault();

        const form = document.getElementById('deleteChatbotForm');
        const chatbotId = document.querySelector('.chatContainer').getAttribute('data-chatbot-id');

        const formData = new FormData(form);
        formData.append('action', 'delete_chatbot');
        formData.append('chatbot_id', chatbotId);

        if (confirm('Tem certeza que deseja resetar o chatbot?')) {
            fetch(conciergeAjax.ajax_url, {
                method: 'POST',
                body: formData
            }).then(response => response.json())
                .then(data => {
                    console.log(data);
                    document.querySelector('body').insertAdjacentHTML('beforeend', `
                                    <div class="fixed top-2 p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
                                        <span class="font-medium">Sucesso!</span> Chatbot Deletado com sucesso!
                                    </div>
                                `);
                })
                .finally(() => {
                    window.location.reload();
                })
                .catch((error) => {
                    console.error('Error:', error);
                });
        }
    })
});
jQuery(document).ready(function ($) {
        $('#gerar-link').on('click', function () {
            var chatbotID = $('#chatbot-selector').val()
            console.log(chatbotID)
            $.ajax({
                url: conciergeAjax.ajax_url,
                method: 'GET',
                data: {
                    action: 'gerar_script_chatbot',
                    chatbotID: chatbotID
                },
                success: function (response) {
                    if (response.success) {
                        const script = response.data.script;
                        $('#chatbot-link').html('<pre>' + $('<div>').text(script).html() + '</pre>').show();
                    } else {
                        alert('Erro ao gerar o script: ' + response.data);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Erro na requisição AJAX:', error);
                    alert('Erro ao gerar o script. Tente novamente.');
                }
            });
        });
    });