window.addEventListener('DOMContentLoaded', function () {
    const inputField = document.querySelector('.mensagem');
    const sendButton = document.querySelector('#enviarMensagem');

    if (sendButton) {
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
                                <div class="flex-shrink-0 flex justify-center items-center h-10 w-10 rounded-full bg-gray-300">
                                    <svg class="size-6 text-blue-600" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0z"/></svg>
                                </div>
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

                    let responseData = JSON.parse(data.data);
                    console.log(responseData);

                    let aiMsgTemplate = `
                                <div class="flex w-full mt-2 space-x-3 max-w-xs messageInput">
            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300">
                <img src="${responseData.image}" class="size-10 rounded-full" alt="">
            </div>
            <div>
                <div class="bg-gray-300 p-3 rounded-r-lg rounded-bl-lg">
                    <p class="text-sm">${responseData.message}</p>
                </div>
                <span class="text-xs text-gray-500 leading-none">${currHour.getHours()}:${currHour.getMinutes()}</span>
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
    }
    if (inputField) {
        inputField.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                sendButton.click();
            }
        });
    }

    const chatbotSelector = document.getElementById('chatbot-selector');
    const chatContainer = document.querySelector('.chatContainer');

    if (chatbotSelector) {
        chatbotSelector.addEventListener('change', function () {
            const selectedChatbotId = chatbotSelector.value;
            chatContainer.setAttribute('data-chatbot-id', selectedChatbotId);
        });
    }

    // document.querySelector('#deleteChatbotForm').addEventListener('submit', (event) => {
    //     event.preventDefault();

    //     const form = document.getElementById('deleteChatbotForm');
    //     const chatbotId = document.querySelector('.chatContainer').getAttribute('data-chatbot-id');

    //     const formData = new FormData(form);
    //     formData.append('action', 'delete_chatbot');
    //     formData.append('chatbot_id', chatbotId);

    //     Swal.fire({
    //         title: 'Tem certeza?',
    //         text: "Tem certeza de que quer resetar o chatbot?",
    //         icon: 'warning',
    //         showCancelButton: true,
    //         confirmButtonColor: '#3085d6',
    //         cancelButtonColor: '#d33',
    //         confirmButtonText: 'Sim, resetar!'
    //     }).then((result) => {
    //         if (result.isConfirmed) {
    //             fetch(conciergeAjax.ajax_url, {
    //                 method: 'POST',
    //                 body: formData
    //             }).then(response => response.json())
    //                 .then(data => {
    //                 })
    //                 .finally(() => {
    //                     localStorage.removeItem('chatbotRespostas');
    //                     window.location.reload();
    //                 })
    //                 .catch((error) => {
    //                     console.error('Error:', error);
    //                 });
    //         }
    //     });
    // })
});
jQuery(document).ready(function ($) {
    // Verifica se o script já está no localStorage
    const storedScript = localStorage.getItem('chatbot_script');

    if (storedScript) {
        // Exibe o script armazenado e esconde o botão
        $('#targetText').text(storedScript);
        $('#clipboardSection').removeClass('hidden').show();
        $('#gerar-link').hide();
    } else {
        // Adiciona evento ao botão para gerar script
        $('#gerar-link').on('click', function () {
            var chatbotID = $('#chatbot-selector').val();
            console.log(chatbotID);

            $.ajax({
                url: conciergeAjax.ajax_url,
                method: 'GET',
                data: {
                    action: 'gerar_script_chatbot',
                    chatbotID: chatbotID,
                },
                success: function (response) {
                    if (response.success) {
                        const script = response.data.script;
                        // console.log(script);

                        // Salva o script no localStorage
                        localStorage.setItem('chatbot_script', script);

                        // Exibe o script no <pre>
                        $('.clipboardScript pre').text(script);
                        $('.clipboardScript').removeClass('hidden').show();

                        // Esconde o botão de gerar script
                        $('#gerar-link').hide();
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
    }
});