jQuery(document).ready(function ($) {
    const chatBox = $(".chatContainer");
    const sendButton = $('#enviarMensagem');
    const messageField = $(".mensagem");

    // XXXXXXXXXXXXXXXXXX ASSISTENTES XXXXXXXXXXXXX

    function prepareAssistantData() {
        const assistantName = $('.assistent-name').val();
        const assistantImage = $('#appearance_image')[0].files[0];
        const assistantInstructions = treatInstructions(JSON.parse(localStorage.getItem('chatbotRespostas')));

        // let assistantFile = null;

        const assistantFiles = $('input[type="file"]').map(function () {
            const file = this.files[0];
            if (file && ['application/pdf', 'application/msword', 'text/plain'].includes(file.type)) {
                return file;
            }
        }).get().filter(file => file !== undefined);

        return {
            assistantName,
            assistantImage,
            assistantInstructions,
            assistantFiles
        }
    }


    async function getAssistantById(assistantId) {
        const response = await fetch(`https://api.openai.com/v1/assistants/${assistantId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${envVars.OPENAI_API_KEY}`,
                'OpenAI-Beta': 'assistants=v2'
            },
        });

        const data = await response.json();

        localStorage.setItem('assistant', JSON.stringify(data));
    }

    async function createAssistant(assistantDTO) {
        const assistantDto = prepareAssistantData();

        // let imageURL = null;
        // let vectorStoreId = null;

        // if (assistantDto.assistantImage) {
        //     imageURL = await uploadImage(assistantDto.assistantImage);
        // }

        // if(assistantDto.assistantFiles) {
        //     const uploadedFileId = await uploadFiles(assistantDto.assistantFiles);
        //     console.log(`File id: ${uploadedFileId}`);
        //     vectorStoreId = await vectorStore(uploadedFileId);
        //     console.log(`Vector id: ${vectorStoreId}`);
        //     // const completed = await checkCompletedVectorStore(vectorStoreId.id);
        // }

        const response = await fetch(`https://api.openai.com/v1/assistants`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${envVars.OPENAI_API_KEY}`,
                'OpenAI-Beta': 'assistants=v2'
            },
            body: JSON.stringify({
                "instructions": assistantDTO.assistant_instructions,
                "name": assistantDTO.assistant_name,
                "tools": [
                    { "type": "code_interpreter" },
                    { "type": "file_search" }
                ],
                "tool_resources": {
                    "file_search": {
                        "vector_store_ids": []
                    }
                },
                "model": "gpt-3.5-turbo",
                "metadata": {
                    "assistant_image": assistantDTO.assistant_image,
                }
            })
        });

        const data = await response.json();

        localStorage.setItem('assistant', JSON.stringify(data));

        await $.ajax({
            url: conciergeAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'create_assistant',
                assistantId: data.id
            },
            success: function (response) {
                console.log('Assistant created successfully:', response);
                location.reload();
            },
            error: function (error) {
                console.error('Error creating assistant:', error);
            }
        });
    }

    async function updateAssistant(assistantId, data = {}) {

        // const currentAssistant = JSON.parse(localStorage.getItem('assistant')) || {};

        // Object.assign(currentAssistant, data);

        // const response = await fetch(`https://api.openai.com/v1/assistants/${assistant_id}`, {
        //     method: 'POST',
        //     headers: {
        //         'Content-Type': 'application/json',
        //         'Authorization': `Bearer ${envVars.OPENAI_API_KEY}`,
        //         'OpenAI-Beta': 'assistants=v2'
        //     },
        //     body: JSON.stringify(data)
        // });

        // const assistant = await response.json();

        // localStorage.setItem('assistant', JSON.stringify(data));
        // location.reload();
    }

    // XXXXXXXXXXXXXXXXXX UPLOAD DE FILES XXXXXXXXXXXXX
    async function uploadFiles(file) {

        const response = await fetch(`https://api.openai.com/v1/files`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${envVars.OPENAI_API_KEY}`,
                'OpenAI-Beta': 'assistants=v2'
            },
            body: JSON.stringify({
                "file": file,
                "purpose": "assistants",
            })
        });

        const data = await response.json();

        return data;
    }

    async function vectorStore(uploadedFile) {
        const response = await fetch(`https://api.openai.com/v1/vector_stores`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${envVars.OPENAI_API_KEY}`,
                'OpenAI-Beta': 'assistants=v2'
            },
            body: JSON.stringify({
                "file_ids": [
                    uploadedFile.id
                ],
                "name": uploadedFile.filename,
            })
        });

        const data = await response.json();

        return data;
    }

    async function checkCompletedVectorStore(vectorStoreId) {
        const response = await fetch(`https://api.openai.com/v1/vector_stores/${vectorStoreId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${envVars.OPENAI_API_KEY}`,
                'OpenAI-Beta': 'assistants=v2'
            },
        });

        const data = await response.json();

        if (data.status === 'completed') {
            return true;
        }

        return false;
    }

    async function uploadImage(image) {
        const formData = new FormData();
        formData.append('file', image);
        formData.append('action', 'upload_image');

        try {
            const response = await $.ajax({
                url: conciergeAjax.ajax_url,
                method: 'POST',
                data: formData,
                processData: false, // Evita que o jQuery transforme o FormData em string
                contentType: false, // Deixa o navegador definir automaticamente o Content-Type
            });

            if (response.success) {
                return response.data.url; // Retorna a URL da imagem
            } else {
                throw new Error('Erro no upload da imagem.');
            }
        } catch (error) {
            console.error('Erro no upload da imagem:', error);
            return null;
        }
    }

    // XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
    let sessionId = localStorage.getItem('sessionID') || "";

    if (sessionId) {
        chatBox.attr('data-session-id', sessionId);
    }

    const data = JSON.parse(localStorage.getItem('chatbotRespostas'));

    function treatInstructions(hardInstructions) {
        let instructions = "";

        Object.keys(hardInstructions).forEach((tab) => {
            const tabData = hardInstructions[tab];

            if (tabData.length) {
                tabData.forEach(item => {
                    instructions += `${item.training_phrase} ${item.resposta};\n`;
                });
            }
        });

        return instructions;
    }

    async function createThreadIfNeeded() {
        if (!sessionId) {
            const response = await fetch(`https://api.openai.com/v1/threads`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${envVars.OPENAI_API_KEY}`,
                    'OpenAI-Beta': 'assistants=v2'
                },
            });

            const responseData = await response.json(); // Converte para JSON
            console.log(responseData); // Debug para verificar a resposta

            if (responseData && responseData.id) {
                sessionId = responseData.id; // Corrigido para acessar a ID corretamente
                localStorage.setItem('sessionID', sessionId);
                chatBox.attr('data-session-id', sessionId);
            }
        }
    }

    async function sendMessage() {
        // const sessionId = localStorage.getItem('sessionID') || "";
        const assistantId = $('.chatContainer').attr('data-assistant-id');
        const message = messageField.val().trim();
        if (!message) return;

        const currHour = new Date();
        messageField.val("");

        const minutes = currHour.getMinutes().toString().padStart(2, '0');
        const userMsgTemplate = `
            <div class="flex w-full mt-2 space-x-3 max-w-xs ml-auto justify-end messageInput">
                <div>
                    <div class="bg-blue-600 text-white p-3 rounded-l-lg rounded-br-lg text-sm text-black">
                        ${message}
                    </div>
                    <span class="text-xs text-gray-500 leading-none">${currHour.getHours()}:${minutes}</span>
                </div>
                <div class="flex-shrink-0 flex justify-center items-center h-10 w-10 rounded-full bg-gray-300">
                    <svg class="size-6 text-blue-600" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0z"/></svg>
                </div>
            </div>`;

        chatBox.append(userMsgTemplate);
        chatBox.scrollTop(chatBox.prop("scrollHeight"));

        sendButton.prop('disabled', true).addClass('opacity-90');
        $("#enviarMensagem svg").addClass('animate-spin');

        await createThreadIfNeeded();

        console.log(sessionId);

        if (!assistantId) return;

        // Envia a mensagem para a API
        await fetch(`https://api.openai.com/v1/threads/${sessionId}/messages`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${envVars.OPENAI_API_KEY}`,
                'OpenAI-Beta': 'assistants=v2'
            },
            body: JSON.stringify({ "role": "user", "content": message })
        });

        // Inicia o streaming da resposta
        const runResponse = await fetch(`https://api.openai.com/v1/threads/${sessionId}/runs`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${envVars.OPENAI_API_KEY}`,
                'OpenAI-Beta': 'assistants=v2'
            },
            body: JSON.stringify({
                "assistant_id": assistantId,
                "stream": true
            })
        });

        const assistantImage = JSON.parse(localStorage.getItem('assistant')).metadata.assistant_image;

        const aiMsgTemplate = $(`
            <div class="flex w-full mt-2 space-x-3 max-w-xs messageInput">
                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300">
                    <img src="${assistantImage}" class="size-10 rounded-full" alt="">
                </div>
                <div>
                    <div class="bg-gray-300 p-3 rounded-r-lg rounded-bl-lg text-sm ai-message">
                        <span class="stream-text animate-ping">...</span>
                    </div>
                    <span class="text-xs text-gray-500 leading-none">${currHour.getHours()}:${minutes}</span>
                </div>
            </div>`);

        chatBox.append(aiMsgTemplate);
        chatBox.scrollTop(chatBox.prop("scrollHeight"));

        const reader = runResponse.body.getReader();
        const decoder = new TextDecoder('utf-8');
        let done = false;
        let aiMessage = "";

        function transformarLinks(texto) {
            return texto.replace(/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g, '<a href="$2" target="_blank" class="text-blue-600 underline">$1</a>');
        }

        let messageParts = [];
        let usage = null;

        while (!done) {
            const { value, done: readerDone } = await reader.read();
            done = readerDone;
            const chunk = decoder.decode(value, { stream: true });

            if (chunk) {
                try {
                    const lines = chunk.trim().split("\n");

                    console.log(lines);

                    lines.forEach((line) => {
                        if (line.startsWith("data: {")) {
                            const jsonData = line.replace("data: ", "");
                            if (jsonData !== "[DONE]") {
                                const parsed = JSON.parse(jsonData);

                                console.log(parsed);

                                if (parsed?.delta?.content) {
                                    parsed.delta.content.forEach((part) => {
                                        if (part.type === "text") {
                                            messageParts.push(part.text.value);
                                            aiMessage += part.text.value; // Atualiza ao vivo
                                        }
                                    });

                                    // Exibe o texto sem modificar os links ainda
                                    aiMsgTemplate.find(".stream-text").text(aiMessage);
                                    chatBox.scrollTop(chatBox.prop("scrollHeight"));
                                }

                                if (parsed?.usage) {
                                    console.log(parsed.usage)
                                    usage = parsed.usage;
                                }
                            }
                        }
                    });
                } catch (e) {
                    console.error("Erro ao processar chunk:", chunk, e);
                }
            }
        }

        $.ajax({
            url: conciergeAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'manage_usage',
                usage: usage
            },
            success: function (response) {

                let usageValue = response.data.usage.total;

                $('.usage-percentage-number').text(Math.floor(usageValue) + '%');

                $('.usage-percentage-bar').css('width', Math.floor(usageValue) + '%');
            },
            error: function (error) {
                console.error('Error managing usage:', error);
            }
        });

        // Quando o streaming termina:
        aiMessage = messageParts.join(""); // Monta a mensagem final
        const formattedMessage = transformarLinks(aiMessage); // Aplica a conversão de links

        // Substitui a mensagem pelo texto formatado com links clicáveis
        aiMsgTemplate.find(".stream-text").html(formattedMessage);
        aiMsgTemplate.find(".stream-text").removeClass('animate-ping');



        sendButton.removeClass('opacity-90').prop('disabled', false);
        $("#enviarMensagem svg").removeClass('animate-spin');
    }

    function addMessageToChat(image, message) {
        const currHour = new Date();

        const aiMsgTemplate = `
                <div class="flex w-full mt-2 space-x-3 max-w-xs messageInput">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300">
                        <img src="" class="size-10 rounded-full" alt="">
                    </div>
                    <div>
                        <div class="bg-gray-300 p-3 rounded-r-lg rounded-bl-lg text-sm">
                            ${message}
                        </div>
                        <span class="text-xs text-gray-500 leading-none">${currHour.getHours()}:${currHour.getMinutes()}</span>
                    </div>
                </div>`;

        chatBox.append(aiMsgTemplate);
        chatBox.scrollTop(chatBox.prop("scrollHeight"));
    }

    function checkCharacters(mensagem) {
        const maxCharacters = 1000;
        const remainingCharacters = maxCharacters - mensagem.length;

        if (remainingCharacters < 0) {
            addMessageToChat(null, `Desculpe ! Sua mensagem é muito grande. Você excedeu o limite de tokens.`);
            return false;
        }
        return true;
    }

    function handleSendMessage() {
        const mensagem = messageField.val().trim();
        if (checkCharacters(mensagem)) {
            sendMessage();
        }
        messageField.val("");
    }

    if (sendButton.length) {
        sendButton.on('click', function (event) {
            event.preventDefault();
            handleSendMessage();
        });
    }

    if (messageField.length) {
        messageField.on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                handleSendMessage();
            }
        });
    }

    const chatbotSelector = $('#chatbot-selector');
    const chatContainer = $('.chatContainer');

    if (chatbotSelector.length) {
        chatbotSelector.on('change', function () {
            const selectedChatbotId = chatbotSelector.val();
            chatContainer.attr('data-chatbot-id', selectedChatbotId);
        });
    }

    const deleteChatbotForm = $('#deleteChatbotForm');

    if (deleteChatbotForm.length) {
        deleteChatbotForm.on('submit', function (event) {
            event.preventDefault();

            const chatbotId = chatContainer.data('chatbot-id');
            const formData = new FormData(this);
            formData.append('action', 'delete_chatbot');
            formData.append('chatbot_id', chatbotId);

            Swal.fire({
                title: 'Tem certeza?',
                text: "Tem certeza de que quer resetar o chatbot?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, resetar!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: conciergeAjax.ajax_url,
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function () {
                            localStorage.removeItem('chatbotRespostas');
                            localStorage.removeItem('chatbot_script');
                            window.location.reload();
                        },
                        error: function (error) {
                            console.error('Error:', error);
                        }
                    });
                }
            });
        });
    }

    const container = $("#tabs-container");
    const buttons = container.find(".tab-btn");
    const contentContainer = $("#tabs-content-container");
    const contentDivs = contentContainer.find(".tab-content");
    let currentTabIndex = 0;

    function showTabContent(tabName) {
        contentDivs.addClass("hidden");
        const activeContentDiv = $(`#${tabName}-content`);
        if (activeContentDiv.length) {
            activeContentDiv.removeClass("hidden");
        }
    }

    function hideAllTabs() {
        contentDivs.addClass("hidden");
    }

    async function updateChatbot() {
        // const chatbotId = $("#chatbotID").val();
        // const chatbotName = $(".assistent-name").val();
        // const welcomeMessage = $(".assistent-message").val();
        // const fileInput = $("#appearance_image")[0];
        // let file_url = "";

        // if (fileInput && fileInput.files.length > 0) {
        //     const formData = new FormData();
        //     formData.append("files[]", fileInput.files[0]);
        //     formData.append("action", "upload_files_to_media_library");

        //     try {


        //         const response = await fetch(conciergeAjax.ajax_url, {
        //             method: "POST",
        //             body: formData,
        //         });

        //         const data = await response.json();
        //         if (data.success) {
        //             file_url = data.data.urls;
        //         } else {
        //             console.error("Falha ao enviar arquivos:", data.message);
        //             return;
        //         }
        //     } catch (error) {
        //         console.error("Erro na requisição de upload:", error);
        //         return;
        //     }
        // }

        // chatbotOptions = chatbotOptions.map(option => {
        //     if (option.field_type === "file" && !option.value) {
        //         delete option.value;
        //     }
        //     return option;
        // });

        console.log('atualizando chatbot');

        const assistant = JSON.parse(localStorage.getItem('assistant')) || null;
        let chatbotOptions = JSON.parse(localStorage.getItem('chatbotRespostas')) || {};
        chatbotOptions = $.map(chatbotOptions, function (val) { return val; }).flat();
        const image = $("#appearance_image")[0].files[0];
        const chatbotName = $(".assistent-name").val();
        const welcomeMessage = $(".assistent-message").val();

        const chatbotFormData = new FormData();
        chatbotFormData.append("action", "save_responses");
        chatbotFormData.append("chatbot_options", JSON.stringify(chatbotOptions));
        chatbotFormData.append("chatbot_id", assistant.id);
        chatbotFormData.append("chatbot_name", chatbotName);
        chatbotFormData.append("chatbot_image", image);
        chatbotFormData.append("chatbot_welcome_message", welcomeMessage);

        Swal.fire({
            title: 'Atualizando...',
            text: 'Por favor, aguarde enquanto o chatbot é atualizado.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const chatbotResponse = await fetch(conciergeAjax.ajax_url, {
                method: "POST",
                body: chatbotFormData,
            });

            const data = await chatbotResponse.json();

            if (data.data.assistant) {
                localStorage.setItem('assistant', JSON.stringify(data.data.assistant));
            }

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: 'Chatbot atualizado com sucesso!',
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: `Erro ao atualizar chatbot: ${data.data.message}`,
                });
                console.error("Erro ao atualizar chatbot:", data.data.message);
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: 'Erro na requisição do chatbot.',
            });
            console.error("Erro na requisição do chatbot:", error);
        }
    }

    function unlockNextTab() {
        if (currentTabIndex < buttons.length - 1) {
            currentTabIndex++;
            const nextTabName = $(buttons[currentTabIndex]).data("tab");
            $($(buttons[currentTabIndex])).attr("data-locked", "false");
            $($(buttons[currentTabIndex])).removeClass("opacity-50 cursor-not-allowed");
            // showTabContent(nextTabName);
        }
    }

    function stopAllVideos() {
        const videos = $("video");
        videos.each(function () {
            this.pause();
            this.currentTime = 0;
        });
    }

    function saveResponses() {
        console.log('chamei saveResponses')
        const activeContent = $(".tab-content:not(.hidden)");
        const chatbotOptions = [];
        const fileInputs = activeContent.find('input[type="file"]');

        if (!activeContent.length) {
            console.error("Aba ativa não encontrada");
            return;
        }

        // Função para salvar no localStorage
        const saveData = (chatbotOptions) => {
            const categoryNameElement = activeContent.find("h2").get(0) || {
                innerText: activeContent.attr("id").replace("-content", ""),
            };

            const categoryName = categoryNameElement instanceof HTMLElement
                ? categoryNameElement.innerText
                : categoryNameElement.innerText;

            const savedData = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};
            savedData[categoryName] = chatbotOptions;
            localStorage.setItem("chatbotRespostas", JSON.stringify(savedData));

            unlockNextTab();
            stopAllVideos();

            Swal.fire({
                title: `Respostas salvas`,
                text: `Respostas salvas para a categoria: ${categoryName}`,
                icon: "success",
            }).then((result) => {
                if (result.isConfirmed) {
                    hideAllTabs();
                    const nextTabName = $(buttons[currentTabIndex]).data("tab");
                    showTabContent(nextTabName);
                }
            });
        };

        // Função para processar blocos de perguntas
        const processQuestionBlocks = (fileUrls = []) => {

            const isBehaviorTab = activeContent.attr("id") === "comportamento-content";
            if (isBehaviorTab) {
                const activeTab = activeContent.find("[x-show]:not([style*='display: none'])");

                activeTab.find(".question-block").each((index, questionBlock) => {
                    const inputElement = $(questionBlock).find("input:not([type='checkbox']), select, textarea").get(0);

                    if (inputElement) {
                        const perguntaLabel = $(questionBlock).find("label").text().trim();
                        let resposta = $(inputElement).val().trim();

                        // Verifica se o input é de arquivo e atribui a URL correta
                        if ($(inputElement).attr("type") === "file" && fileUrls.length > 0) {
                            resposta = fileUrls.shift();
                        }

                        const trainingPhrase = $(questionBlock).find("label").data("questionBase");
                        const fieldType = $(inputElement).prop("tagName").toLowerCase() === "select" ? "select" : $(inputElement).attr("type");

                        chatbotOptions.push({
                            pergunta: perguntaLabel,
                            field_name: $(inputElement).attr("name"),
                            resposta: resposta,
                            training_phrase: trainingPhrase,
                            field_type: fieldType,
                        });
                    }
                });
            }

            if (!isBehaviorTab) {
                activeContent.find(".question-block").each((index, questionBlock) => {
                    const inputElement = $(questionBlock).find("input:not([type='checkbox']), select, textarea").get(0);

                    if (inputElement) {
                        const perguntaLabel = $(questionBlock).find("label").text().trim();
                        let resposta = $(inputElement).val().trim();

                        // Verifica se o input é de arquivo e atribui a URL correta
                        if ($(inputElement).attr("type") === "file" && fileUrls.length > 0) {
                            resposta = fileUrls.shift();
                        }

                        const trainingPhrase = $(questionBlock).find("label").data("questionBase");
                        const fieldType = $(inputElement).prop("tagName").toLowerCase() === "select" ? "select" : $(inputElement).attr("type");

                        chatbotOptions.push({
                            pergunta: perguntaLabel,
                            field_name: $(inputElement).attr("name"),
                            resposta: resposta,
                            training_phrase: trainingPhrase,
                            field_type: fieldType,
                        });
                    }
                });
            }

            saveData(chatbotOptions);

            const hasChatbot = $("#hasChatbot").val();

            if (hasChatbot === '1') {
                updateChatbot();
            }
        };

        if (fileInputs.length > 0) {
            const formData = new FormData();
            let hasFiles = false;

            fileInputs.each((index, fileInput) => {
                if (fileInput.files.length > 0) {
                    formData.append("files[]", fileInput.files[0]);
                    hasFiles = true;
                }
            });

            if (hasFiles) {
                formData.append("action", "upload_files_to_media_library");

                Swal.fire({
                    title: 'Uploading...',
                    text: 'Aguarde enquanto fazemos o upload dos documentos.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Fazer upload dos arquivos via AJAX
                $.ajax({
                    url: conciergeAjax.ajax_url,
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: (data) => {
                        if (data.success) {
                            processQuestionBlocks(data.data.urls);
                        } else {
                            console.error("Falha ao enviar arquivos:", data.message);
                        }
                    },
                    error: (error) => {
                        console.error("Erro na requisição de upload:", error);
                    }
                });
            } else {
                processQuestionBlocks();
            }
        } else {
            processQuestionBlocks();
        }
    }

    function saveStyles() {
        const activeContent = $(".tab-content:not(.hidden)");
        const chatbotOptions = [];

        if (!activeContent.length) {
            console.error("Aba ativa não encontrada");
            return;
        }

        const categoryNameElement = activeContent.find("h2").get(0) || {
            innerText: activeContent.attr("id").replace("-content", ""),
        };
        const categoryName = categoryNameElement instanceof HTMLElement
            ? categoryNameElement.innerText
            : categoryNameElement.innerText;

        const savedData = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};
        savedData[categoryName] = chatbotOptions;
        localStorage.setItem("chatbotRespostas", JSON.stringify(savedData));

        const hasChatbot = $("#hasChatbot").val();

        if (hasChatbot === '1') {
            updateChatbot(chatbotOptions);
        } else {
            console.log("Usuário não tem chatbot, não atualizar.");
        }

        unlockNextTab();

        stopAllVideos();

        Swal.fire({
            title: `Respostas salvas`,
            text: `Respostas salvas para a categoria: ${categoryName}`,
            icon: "success"
        }).then((result) => {
            if (result.isConfirmed) {
                hideAllTabs();
                showTabContent("Teste");
            }
        });
    }

    $.each(buttons, function (index, button) {
        if (index > 0) {
            $(button).data("locked", "true"); // Bloquear abas exceto a primeira
            $(button).addClass("opacity-50 cursor-not-allowed");
        }

        $(button).on("click", function () {
            if ($(button).attr("data-locked") === "true") {
                alert("Complete a aba atual antes de prosseguir.");
                return;
            }

            // Define o botão ativo
            $.each(buttons, function (i, btn) {
                $(btn).removeClass("border-gray-800");
            });
            $(button).addClass("border-gray-800");

            // Atualiza o índice da aba ativa
            currentTabIndex = index;

            // Exibe o conteúdo da aba ativa
            const tabName = $(button).data("tab");
            // console.log(tabName)
            showTabContent(tabName);
        });
    });

    $.each(contentDivs, function (index, contentDiv) {
        const $backButton = $(contentDiv).find(".back-btn");
        if ($backButton.length) {
            $backButton.on("click", function () {
                stopAllVideos();
                hideAllTabs();
            });
        }
    });

    const $saveBtn = $("button.saveButton");

    if ($saveBtn.length) {
        $saveBtn.on("click", function (e) {
            e.preventDefault();
            saveResponses();
            getDataCurrent();
        });
    }

    const $saveAparenciaButton = $("button.saveAparenciaButton");

    if ($saveAparenciaButton.length) {
        $saveAparenciaButton.on("click", function (e) {
            e.preventDefault();
            saveStyles();
            getDataCurrent();
        });
    }

    const storedData = JSON.parse(localStorage.getItem('chatbotRespostas'));
    if (storedData) {
        populateField(storedData)
    }


    function populateField(storedData) {
        Object.keys(storedData).forEach((tab, index) => {
            const tabData = storedData[tab];
            const $tabContent = $(`#${tab}-content`);
            if ($tabContent.length) {
                tabData.forEach(item => {
                    const $field = $tabContent.find(`[name="${item.field_name}"]`);
                    if ($field.length) {
                        if ($field.is('input, textarea')) {
                            if ($field.attr('type') !== 'file') {
                                $field.val(item.resposta);
                            } else {
                                $field.siblings('.file-name').text(item.resposta);
                            }
                        } else if ($field.is('select')) {
                            if ($field.find(`option[value="${item.resposta}"]`).length) {
                                $field.val(item.resposta);
                            } else {
                                console.warn(`Valor "${item.resposta}" não encontrado para o campo "${item.field_name}"`);
                            }
                        }
                    }
                });
                if (index < buttons.length - 1) {
                    const $nextTabButton = $(buttons[index + 1]);
                    $nextTabButton.attr("data-locked", "false");
                    $nextTabButton.removeClass("opacity-50 cursor-not-allowed");
                }
            }
        });
    }

    const $generateChatbotButton = $(".generateChatbot");
    if ($generateChatbotButton.length) {
        $generateChatbotButton.on("click", function (event) {
            event.preventDefault();

            const localChatbotOptions = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};
            const chatbotOptions = $.map(localChatbotOptions, function (val) { return val; }).flat();
            const chatbotName = $('.assistent-name').val();
            const chatbotWelcomeMessage = $('.assistent-message').val();

            const $appearanceImageInput = $("#appearance_image");
            const formData = new FormData();

            formData.append("action", "create_assistant");
            formData.append("chatbot_name", chatbotName);
            formData.append("chatbot_welcome_message", chatbotWelcomeMessage);
            formData.append("chatbot_options", JSON.stringify(chatbotOptions));

            if ($appearanceImageInput.length && $appearanceImageInput[0].files.length > 0) {
                formData.append("chatbot_image", $appearanceImageInput[0].files[0]);
            }

            Swal.fire({
                title: 'Tem certeza?',
                text: "Você deseja gerar o Assistente Virtual?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, gerar!'
            }).then(function (result) {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Criando Assistente...',
                        text: 'Por favor, aguarde enquanto o assistente é criado.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: conciergeAjax.ajax_url,
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false
                    })
                        .complete(function (data) {
                            Swal.close(); // Fecha o loading

                            const response = data.data;
                            console.log(response);

                            localStorage.setItem('assistant', JSON.stringify(response));

                            Swal.fire({
                                icon: 'success',
                                title: 'Assistente criado com sucesso!',
                                text: 'O assistente foi criado e está pronto para uso.',
                            }).then(() => {
                                window.location.reload();
                            });
                        })
                        .fail(function (error) {
                            Swal.close(); // Fecha o loading

                            Swal.fire({
                                icon: 'error',
                                title: 'Erro ao criar assistente',
                                text: 'Ocorreu um erro ao tentar criar o assistente. Por favor, tente novamente.',
                            });

                            console.error("Erro:", error);
                        });
                }
            });
        });
    }

    // const $generateChatbotButton = $(".generateChatbot");
    // if ($generateChatbotButton.length) {
    //     $generateChatbotButton.on("click", function (event) {

    //         const assistantName = $(".assistent-name").val();

    //         // const assistantImage = $("#appearance_image")[0].files[0];

    //         const assistantDto = {
    //             assistantName,
    //             // assistantImage
    //         }

    //         createAssistant(assistantDto);
    //     });
    // }

    const $downloadTabButton = $('button[data-tab="Download"]');

    function checkAllTabsUnlocked() {
        let allUnlocked = true;
        $.each(buttons, function (index, button) {
            if (index < buttons.length - 1 && $(button).attr('data-locked') === "true") {
                allUnlocked = false;
            }
        });
        return allUnlocked;
    }

    if (checkAllTabsUnlocked()) {
        $('[data-tab="Download"]').attr('data-locked', 'false').removeClass('opacity-50 cursor-not-allowed');
    }

    const $clipboardSection = $('#clipboardSection');
    const $targetText = $('#targetText');
    const $copyButton = $('#copyButton');
    const $copyStatus = $('#copyStatus');
    const $copyIcon = $('#copyIcon');
    const $successIcon = $('#successIcon');

    // Função para copiar o texto para o clipboard
    function copyToClipboard() {
        const textToCopy = $targetText.text();

        navigator.clipboard
            .writeText(textToCopy)
            .then(() => {
                // Atualiza o status visual para "copiado"
                $copyStatus.text('Copiado!');
                $copyIcon.addClass('hidden');
                $successIcon.removeClass('hidden');

                // Reseta o estado visual após 2 segundos
                setTimeout(() => {
                    $copyStatus.text('Copiar');
                    $copyIcon.removeClass('hidden');
                    $successIcon.addClass('hidden');
                }, 2000);
            })
            .catch((err) => {
                console.error("Erro ao copiar para o clipboard: ", err);
            });
    }

    // Adiciona o evento de clique no botão de copiar
    $copyButton.on('click', copyToClipboard);


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

    $("input[type='file']").on('change', function () {
        const file = this.files[0];
        const maxSize = 30 * 1024 * 1024;

        if (file && file.size > maxSize) {
            Swal.fire({
                icon: 'warning',
                title: 'Arquivo muito grande!',
                text: 'O arquivo excede o limite de 30MB.',
            });
            $(this).val('');
        }
    });

    function getDataCurrent() {
        let lastUnlocked = $('.tab-btn[data-locked="false"]').last();
        console.log(lastUnlocked);

        // Remover data-current de todos os botões
        $('.tab-btn').attr('data-current', 'false');

        // Definir data-current="true" no último botão desbloqueado
        lastUnlocked.attr('data-current', 'true');
    }

    getDataCurrent();
});

document.addEventListener("DOMContentLoaded", () => {
    const chatContainer = document.querySelector('.chatContainer');
    const assistantId = chatContainer ? chatContainer.getAttribute('data-assistant-id') : null;

    async function getAssistant(assistantId) {
        const response = await fetch(`https://api.openai.com/v1/assistants/${assistantId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${envVars.OPENAI_API_KEY}`,
                'OpenAI-Beta': 'assistants=v2'
            },
        });

        const data = await response.json();

        localStorage.setItem('assistant', JSON.stringify(data));
    }

    if (assistantId) getAssistant(assistantId);

});


window.addEventListener("load", function () {
    const buttons = document.querySelectorAll("button[data-current]");
    if (buttons.length === 0) return;

    function updateSpans() {
        // Remove spans existentes
        buttons.forEach(button => {
            const existingSpan = button.querySelector("span");
            if (existingSpan) {
                existingSpan.remove();
            }
        });

        let firstButton = buttons[0]; 
        let otherButtonsHaveCurrent = Array.from(buttons).some((btn, index) =>
            index > 0 && btn.getAttribute("data-current") === "true"
        );

        buttons.forEach(function (button, index) {
            if (button.getAttribute("data-current") === "true" && !(index === 0 && !otherButtonsHaveCurrent)) {
                let span = document.createElement("span");
                span.textContent = "Você está nessa etapa";
                span.style.display = "block";
                span.style.fontSize = "0.9rem";
                span.style.fontWeight = "400";
                span.style.marginTop = "4px";
                button.appendChild(span);
            }
        });

        if (!otherButtonsHaveCurrent) {
            let span = document.createElement("span");
            span.textContent = "Clique aqui para começar";
            span.style.display = "block";
            span.style.fontSize = "0.9rem";
            span.style.fontWeight = "400";
            span.style.marginTop = "4px";
            firstButton.appendChild(span);
        }
    }

    // Observador para monitorar alterações no atributo data-current
    const observer = new MutationObserver(function (mutationsList) {
        for (let mutation of mutationsList) {
            if (mutation.type === "attributes" && mutation.attributeName === "data-current") {
                updateSpans(); // Atualiza os spans ao detectar mudanças
            }
        }
    });

    // Observa cada botão com data-current
    buttons.forEach(button => {
        observer.observe(button, { attributes: true });
    });

    updateSpans(); // Executa a atualização inicial
});
