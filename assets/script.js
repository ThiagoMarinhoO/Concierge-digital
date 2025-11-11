jQuery(document).ready(function ($) {

    const chatContainer = document.querySelector('.chatContainer');
    const assistantId = chatContainer ? chatContainer.getAttribute('data-assistant-id') : null;

    if (!assistantId) {
        localStorage.removeItem('assistant');
        localStorage.removeItem('chatbot_script');
        localStorage.removeItem('sessionID');
    }

    async function getAssistant(assistantId) {
        $.ajax({
            url: conciergeAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'get_assistant_by_id',
                assistant_id: assistantId
            },
            success: function (response) {
                if (response.success && response.data.assistant) {
                    localStorage.setItem('assistant', JSON.stringify(response.data.assistant));

                    // getAnswers(response.data.assistant.name);

                } else {
                    console.error('Erro ao buscar assistente:', response.data.message);
                    localStorage.removeItem('assistant');
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição AJAX:', error);
            }
        });
    }

    if (assistantId) getAssistant(assistantId);

    function getAnswers() {
        // const assistant_name = $('.assistent-name').val();
        localStorage.setItem('chatbotRespostas', JSON.stringify([]));

        $.ajax({
            url: conciergeAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'get_questions_answers',
            },
            success: function (response) {
                if (response.success && response.data.answers) {
                    localStorage.setItem('chatbotRespostas', JSON.stringify(response.data.answers));

                    populateField(response.data.answers);

                    if (checkAllTabsUnlocked() && assistantId) {
                        $('[data-tab="Download"]').attr('data-locked', 'false').removeClass('opacity-50 cursor-not-allowed');
                    }

                    getDataCurrent();

                } else {
                    console.error('Erro ao buscar assistente:', response.data.message);
                    localStorage.removeItem('chatbotRespostas');
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição AJAX:', error);
            }
        });
    }

    if (chatContainer) {
        getAnswers();
    }

    // const storedData = JSON.parse(localStorage.getItem('chatbotRespostas'));
    // if (storedData) {
    //     populateField(storedData)
    // }


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
        // const response = await fetch(`https://api.openai.com/v1/assistants/${assistantId}`, {
        //     method: 'GET',
        //     headers: {
        //         'Content-Type': 'application/json',
        //         'Authorization': `Bearer ${envVars.OPENAI_API_KEY}`,
        //         'OpenAI-Beta': 'assistants=v2'
        //     },
        // });

        // const data = await response.json();

        // if (data.error) {
        //     console.error('Error fetching assistant:', response.statusText);
        //     return;
        // }

        // localStorage.setItem('assistant', JSON.stringify(data));

        $.ajax({
            url: conciergeAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'get_assistant_by_id',
                assistant_id: assistantId
            },
            success: function (response) {
                if (response.success && response.data.assistant) {
                    localStorage.setItem('assistant', JSON.stringify(response.data.assistant));
                } else {
                    console.error('Erro ao buscar assistente:', response.data.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição AJAX:', error);
            }
        });
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
        const sessionId = localStorage.getItem('sessionID') || "";
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

        // await createThreadIfNeeded();

        // console.log(sessionId);

        if (!assistantId) return;

        const response = await $.ajax({
            url: conciergeAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'handle_assistant_message',
                session_id: sessionId,
                assistant_id: assistantId,
                message: message
            }
        });

        const responseData = response.data;

        if (responseData.thread_id) {
            localStorage.setItem('sessionID', responseData.thread_id);
            chatBox.attr('data-session-id', responseData.thread_id);
        }

        const assistantObj = JSON.parse(localStorage.getItem('assistant')) || null;
        let assistantImage = '';

        if (assistantObj) {
            assistantImage = assistantObj.metadata.assistant_image;
        }

        const aiMsgTemplate = $(`
            <div class="flex w-full mt-2 space-x-3 max-w-xs messageInput">
                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300">
                    <img src="${assistantImage}" class="w-10 !h-10 object-cover !rounded-full" alt="">
                </div>
                <div>
                    <div class="bg-gray-300 p-3 rounded-r-lg rounded-bl-lg text-sm ai-message">
                        <span class="stream-text !whitespace-pre-wrap animate-ping wrap-anywhere">...</span>
                    </div>
                    <span class="text-xs text-gray-500 leading-none">${currHour.getHours()}:${minutes}</span>
                </div>
            </div>`);

        chatBox.append(aiMsgTemplate);
        chatBox.scrollTop(chatBox.prop("scrollHeight"));

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

            texto = texto.replace(/[\[【]\d+:\d+†[^\]】]+[\]】]/g, '');

            return texto;
        }


        // Quando o streaming termina:
        // aiMessage = messageParts.join(""); // Monta a mensagem final
        const formattedMessage = transformarLinks(responseData.ai_response); // Aplica a conversão de links

        // Substitui a mensagem pelo texto formatado com links clicáveis
        aiMsgTemplate.find(".stream-text").html(formattedMessage);
        aiMsgTemplate.find(".stream-text").removeClass('animate-ping');

        sendButton.removeClass('opacity-90').prop('disabled', false);
        $("#enviarMensagem svg").removeClass('animate-spin');

        if (responseData.usage) {
            let usageValue = response.data.usage.usage.total;

            $('.usage-percentage-number').text(Math.floor(usageValue) + '%');

            $('.usage-percentage-bar').css('width', Math.floor(usageValue) + '%');
        }
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
    // const chatContainer = $('.chatContainer');

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
                text: "Tem certeza de que quer resetar o assistente?",
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

        // console.log('atualizando chatbot');

        const assistant = JSON.parse(localStorage.getItem('assistant')) || null;
        let chatbotOptions = JSON.parse(localStorage.getItem('chatbotRespostas')) || {};
        // chatbotOptions = $.map(chatbotOptions, function (val) { return val; }).flat();
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
            text: 'Por favor, aguarde enquanto o assistente é atualizado.',
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
                    text: 'Assistente atualizado com sucesso!',
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: `Erro ao atualizar assistente: ${data.data.message}`,
                });
                console.error("Erro ao atualizar assistente:", data.data.message);
            }
        } catch (error) {
            
            if(error.status == 401) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: error.data.message,
                });
                console.log(error);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro na requisição do assistente.',
                });
                console.error("Erro na requisição do assistente:", error);
            }
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
        // console.log('chamei saveResponses')
        const activeContent = $(".tab-content:not(.hidden)");
        const chatbotOptions = [];
        const fileInputs = activeContent.find('input[type="file"]');

        // console.log(fileInputs);

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

            // Salvar no user meta
            handleQuestionsAnswers(savedData);

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

            if (!isBehaviorTab) {
                activeContent.find(".question-block").each((index, questionBlock) => {
                    const inputElement = $(questionBlock).find("input:not([type='checkbox']), select, textarea").get(0);

                    if (inputElement) {
                        const perguntaLabel = $(questionBlock).find("label").text().trim();
                        let resposta = $(inputElement).val().trim();

                        if ($(inputElement).attr("type") === "file") {

                            const matchingFiles = fileUrls.filter(file => file.id === inputElement.name);
                            const newFileUrls = matchingFiles.map(f => f.url);

                            // Buscar respostas anteriores salvas no localStorage
                            let savedData = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};
                            const baseDeConhecimento = savedData['base_de_conhecimento'] || [];

                            const respostaAnterior = baseDeConhecimento.find(item => item.field_name === inputElement.name)?.resposta;

                            // Normaliza anterior para array (pode ser undefined, string ou array)
                            const respostaAnteriorArray = respostaAnterior
                                ? Array.isArray(respostaAnterior) ? respostaAnterior : [respostaAnterior]
                                : [];

                            // Concatena arquivos novos aos antigos, evitando duplicatas
                            const todasRespostas = [...new Set([...respostaAnteriorArray, ...newFileUrls])];

                            resposta = todasRespostas;

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
                    const fileData = {
                        inputId: fileInput.name,
                        file: fileInput.files[0]
                    };
                    formData.append("files[]", fileData.file);
                    formData.append("questionIds[]", fileData.inputId);
                    hasFiles = true;
                }
            });

            if (hasFiles) {
                // console.log('Fazendo upload de arquivos...');

                if (!$('input.assistent-name').val()) {
                    swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Por favor, dê um nome ao assistente antes de fazer upload de arquivos.',
                    });

                    return;
                }

                formData.append("action", "upload_files_to_media_library");
                formData.append("assistant_name", $('input.assistent-name').val());
                formData.append("assistant_id", $('.chatContainer').data('assistant-id'));

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
                            console.log(data)
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

        // Salvar no user meta
        $.ajax({
            url: conciergeAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'handle_questions_answers',
                assistant_name: $('.assistent-name').val(),
                saved_data: JSON.stringify(savedData)
            },
            success: function (response) {
                if (response.success) {
                    // console.log('Dados enviados com sucesso:', response.data.message);
                } else {
                    console.error('Erro ao enviar dados:', response.data.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição AJAX:', error);
            }
        });

        if (hasChatbot === '1') {
            updateChatbot(chatbotOptions);
        } else {
            console.log("Usuário não tem assistente, não atualizar.");
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

            // location.reload();
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
                                // $field.siblings('.file-name').text(item.resposta);
                                if ($field.siblings('.attachment-instructions-container').length > 0) {
                                    $field.siblings('.attachment-instructions-container').find('textarea').val(item.training_phrase);
                                }

                                if ($field.siblings('textarea').length > 0) {
                                    // console.log('sdsfdsa');
                                    const $questionBlock = $field.closest('.question-block');
                                    const $label = $questionBlock.find('label[for^="question-"]');
                                    // const labelText = $label.text().trim();

                                    // if (labelText === '') {
                                    $label.attr('data-question-base', item.training_phrase);
                                    $field.siblings('textarea').val(item.training_phrase);
                                    // }
                                }

                                if (Array.isArray(item.resposta)) {
                                    const $container = $field.siblings('.file-name-container');
                                    $container.empty();

                                    item.resposta.forEach(file => {
                                        const $fileBlock = $(`
                                                <div class="flex items-center gap-2 group relative p-2" data-field="${item.field_name}" data-file="${file}">
                                                    <svg class="" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 40 40" fill="none">
                                                        <g id="File">
                                                            <path id="icon" d="M31.6497 10.6056L32.2476 10.0741L31.6497 10.6056ZM28.6559 7.23757L28.058 7.76907L28.058 7.76907L28.6559 7.23757ZM26.5356 5.29253L26.2079 6.02233L26.2079 6.02233L26.5356 5.29253ZM33.1161 12.5827L32.3683 12.867V12.867L33.1161 12.5827ZM31.8692 33.5355L32.4349 34.1012L31.8692 33.5355ZM24.231 11.4836L25.0157 11.3276L24.231 11.4836ZM26.85 14.1026L26.694 14.8872L26.85 14.1026ZM11.667 20.8667C11.2252 20.8667 10.867 21.2248 10.867 21.6667C10.867 22.1085 11.2252 22.4667 11.667 22.4667V20.8667ZM25.0003 22.4667C25.4422 22.4667 25.8003 22.1085 25.8003 21.6667C25.8003 21.2248 25.4422 20.8667 25.0003 20.8667V22.4667ZM11.667 25.8667C11.2252 25.8667 10.867 26.2248 10.867 26.6667C10.867 27.1085 11.2252 27.4667 11.667 27.4667V25.8667ZM20.0003 27.4667C20.4422 27.4667 20.8003 27.1085 20.8003 26.6667C20.8003 26.2248 20.4422 25.8667 20.0003 25.8667V27.4667ZM23.3337 34.2H16.667V35.8H23.3337V34.2ZM7.46699 25V15H5.86699V25H7.46699ZM32.5337 15.0347V25H34.1337V15.0347H32.5337ZM16.667 5.8H23.6732V4.2H16.667V5.8ZM23.6732 5.8C25.2185 5.8 25.7493 5.81639 26.2079 6.02233L26.8633 4.56274C26.0191 4.18361 25.0759 4.2 23.6732 4.2V5.8ZM29.2539 6.70608C28.322 5.65771 27.7076 4.94187 26.8633 4.56274L26.2079 6.02233C26.6665 6.22826 27.0314 6.6141 28.058 7.76907L29.2539 6.70608ZM34.1337 15.0347C34.1337 13.8411 34.1458 13.0399 33.8638 12.2984L32.3683 12.867C32.5216 13.2702 32.5337 13.7221 32.5337 15.0347H34.1337ZM31.0518 11.1371C31.9238 12.1181 32.215 12.4639 32.3683 12.867L33.8638 12.2984C33.5819 11.5569 33.0406 10.9662 32.2476 10.0741L31.0518 11.1371ZM16.667 34.2C14.2874 34.2 12.5831 34.1983 11.2872 34.0241C10.0144 33.8529 9.25596 33.5287 8.69714 32.9698L7.56577 34.1012C8.47142 35.0069 9.62375 35.4148 11.074 35.6098C12.5013 35.8017 14.3326 35.8 16.667 35.8V34.2ZM5.86699 25C5.86699 27.3344 5.86529 29.1657 6.05718 30.593C6.25217 32.0432 6.66012 33.1956 7.56577 34.1012L8.69714 32.9698C8.13833 32.411 7.81405 31.6526 7.64292 30.3798C7.46869 29.0839 7.46699 27.3796 7.46699 25H5.86699ZM23.3337 35.8C25.6681 35.8 27.4993 35.8017 28.9266 35.6098C30.3769 35.4148 31.5292 35.0069 32.4349 34.1012L31.3035 32.9698C30.7447 33.5287 29.9863 33.8529 28.7134 34.0241C27.4175 34.1983 25.7133 34.2 23.3337 34.2V35.8ZM32.5337 25C32.5337 27.3796 32.532 29.0839 32.3577 30.3798C32.1866 31.6526 31.8623 32.411 31.3035 32.9698L32.4349 34.1012C33.3405 33.1956 33.7485 32.0432 33.9435 30.593C34.1354 29.1657 34.1337 27.3344 34.1337 25H32.5337ZM7.46699 15C7.46699 12.6204 7.46869 10.9161 7.64292 9.62024C7.81405 8.34738 8.13833 7.58897 8.69714 7.03015L7.56577 5.89878C6.66012 6.80443 6.25217 7.95676 6.05718 9.40704C5.86529 10.8343 5.86699 12.6656 5.86699 15H7.46699ZM16.667 4.2C14.3326 4.2 12.5013 4.1983 11.074 4.39019C9.62375 4.58518 8.47142 4.99313 7.56577 5.89878L8.69714 7.03015C9.25596 6.47133 10.0144 6.14706 11.2872 5.97592C12.5831 5.8017 14.2874 5.8 16.667 5.8V4.2ZM23.367 5V10H24.967V5H23.367ZM28.3337 14.9667H33.3337V13.3667H28.3337V14.9667ZM23.367 10C23.367 10.7361 23.3631 11.221 23.4464 11.6397L25.0157 11.3276C24.9709 11.1023 24.967 10.8128 24.967 10H23.367ZM28.3337 13.3667C27.5209 13.3667 27.2313 13.3628 27.0061 13.318L26.694 14.8872C27.1127 14.9705 27.5976 14.9667 28.3337 14.9667V13.3667ZM23.4464 11.6397C23.7726 13.2794 25.0543 14.5611 26.694 14.8872L27.0061 13.318C26.0011 13.1181 25.2156 12.3325 25.0157 11.3276L23.4464 11.6397ZM11.667 22.4667H25.0003V20.8667H11.667V22.4667ZM11.667 27.4667H20.0003V25.8667H11.667V27.4667ZM32.2476 10.0741L29.2539 6.70608L28.058 7.76907L31.0518 11.1371L32.2476 10.0741Z" fill="#4F46E5" />
                                                        </g>
                                                    </svg>
                                                    <div class="grid gap-1">
                                                        <h4 class="text-gray-900 font-normal font-['Inter'] !mb-0 leading-snug file-name">${file}</h4>
                                                    </div>
                                                    <button type="button" class="remove-file hidden group-hover:flex group-hover:justify-center group-hover:items-center text-red-500 hover:text-red-700 text-xl absolute top-0 right-0 !w-6 !h-6 !p-0 transition-all duration-200 -translate-y-5 group-hover:translate-x-2" title="Remover arquivo">&times;</button>
                                                </div>
                                        `);
                                        $container.append($fileBlock);
                                    });
                                } else {
                                    $field.siblings('.file-name').text(item.resposta);
                                }

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
            // const chatbotOptions = $.map(localChatbotOptions, function (val) { return val; }).flat();
            const chatbotOptions = localChatbotOptions;
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
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Sim, gerar!',
                customClass: {
                    confirmButton: 'swal-confirm-blue',
                    cancelButton: 'swal-cancel-red'
                },
                buttonsStyling: false
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
                                // window.location.reload();
                                let url = new URL(window.location.href);
                                url.searchParams.set('success', '1');
                                window.location.href = url.toString();
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

    if (checkAllTabsUnlocked() && assistantId) {
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
        // console.log(lastUnlocked);

        // Remover data-current de todos os botões
        $('.tab-btn').attr('data-current', 'false');

        // Definir data-current="true" no último botão desbloqueado
        lastUnlocked.attr('data-current', 'true');
    }

    getDataCurrent();

    $(document).on('click', '.remove-file', function () {
        const $container = $(this).closest('[data-field]');
        const fieldName = $container.data('field');
        const fileUrl = $container.data('file');
        const tabId = $('.tab-content:not(.hidden)').attr('id')?.replace('-content', '');

        const formData = new FormData();
        formData.append('action', 'delete_vector_store_file');
        formData.append('file_url', fileUrl);

        const allowedTypes = ['pdf', 'doc', 'docx', 'txt'];

        const fileExtension = fileUrl.split('.').pop().toLowerCase();
        if (allowedTypes.includes(fileExtension)) {
            swal.fire({
                title: 'Remover arquivo',
                text: 'Tem certeza que deseja remover este arquivo? Esta ação não pode ser desfeita.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, remover',
                cancelButtonText: 'Cancelar',
            }).then((result) => {
                if (result.isConfirmed) {
                    swal.fire({
                        title: 'Removendo arquivo...',
                        text: 'Por favor, aguarde.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: conciergeAjax.ajax_url,
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (response) {
                            if (response.success) {
                                console.log('Arquivo removido do vector store com sucesso.');

                                swal.close();

                                // Remover visualmente
                                $container.remove();

                                // Remover do localStorage
                                const savedData = JSON.parse(localStorage.getItem('chatbotRespostas')) || {};
                                if (tabId && savedData[tabId]) {
                                    savedData[tabId] = savedData[tabId].map(entry => {
                                        if (entry.field_name === fieldName && Array.isArray(entry.resposta)) {
                                            entry.resposta = entry.resposta.filter(file => file !== fileUrl);
                                        }
                                        return entry;
                                    });

                                    localStorage.setItem('chatbotRespostas', JSON.stringify(savedData));

                                    handleQuestionsAnswers(savedData);
                                }
                            } else {
                                swal.close();
                                console.error('Erro ao remover arquivo do vector store:', response.data?.message || response);
                            }
                        },
                        error: function (xhr, status, error) {
                            swal.close();
                            console.error('Erro na requisição AJAX:', error);
                        }
                    });
                }
            });
        } else {
            // Remover visualmente
            $container.remove();

            // Remover do localStorage
            const savedData = JSON.parse(localStorage.getItem('chatbotRespostas')) || {};
            if (tabId && savedData[tabId]) {
                savedData[tabId] = savedData[tabId].map(entry => {
                    if (entry.field_name === fieldName && Array.isArray(entry.resposta)) {
                        entry.resposta = entry.resposta.filter(file => file !== fileUrl);
                    }
                    return entry;
                });

                localStorage.setItem('chatbotRespostas', JSON.stringify(savedData));

                handleQuestionsAnswers(savedData);
            }
        }

    });

    function handleQuestionsAnswers(savedData) {

        if (!savedData) {
            console.error('Nenhum dado encontrado para salvar.');

            const savedData = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};
        }

        $.ajax({
            url: conciergeAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'handle_questions_answers',
                assistant_name: $('.assistent-name').val(),
                saved_data: JSON.stringify(savedData)
            },
            success: function (response) {
                if (response.success) {
                    // console.log('Dados enviados com sucesso:', response.data.message);
                } else {
                    console.error('Erro ao enviar dados:', response.data.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição AJAX:', error);
            }
        });
    }


    $('#attachmentInstructions').on('input', function () {
        const instructions = $(this).val();
        $(this)
            .closest('.question-block')
            .find('label[for^="question-"]')
            .attr('data-question-base', instructions);
    });

    $('.add-more-files').on('click', function () {
        // Duplicar apenas o primeiro input file que é sibling
        const $inputFile = $(this).siblings('input[type="file"]').first();
        if ($inputFile.length) {
            const $clone = $inputFile.clone();
            $clone.val('');
            const $fileContainer = $inputFile.siblings('.file-name-container');
            if ($fileContainer.length) {
                // $fileContainer.empty(); // Limpa nomes de arquivos exibidos
                $fileContainer.before($clone);
            }
        }
    });

    // 
    // 
    //  WHATSAPP HANDLERS
    // 
    // 

    const connectWhatsapp = document.getElementById('conectar-whatsapp');
    if (connectWhatsapp) {
        connectWhatsapp.addEventListener('click', async function () {

            swal.fire({
                title: 'Criando instância do WhatsApp',
                text: 'Aguarde enquanto criamos uma nova instância do WhatsApp.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
                showConfirmButton: false,
                showCancelButton: false,
            });

            try {
                const assistantId = JSON.parse(localStorage.getItem('assistant'))?.id;

                const response = await fetch(conciergeAjax.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: new URLSearchParams({
                        action: 'create_whatsapp_instance',
                        // instanceName: newInstanceName,
                        assistant_id: assistantId
                    })
                });
                const result = await response.json();
                if (result.success) {

                    Swal.close();

                    const data = result.data;

                    Swal.fire({
                        title: 'Escaneie o QR Code para conectar o WhatsApp',
                        html: `<img src="${data.qrcode.base64}" alt="QR Code do WhatsApp" style="max-width: 100%; height: auto;" />`,
                        showConfirmButton: true,
                        confirmButtonText: 'Fechar'
                    }).then((result) => {
                        console.log('SweetAlert fechado', result);
                        updateChatbot(); // Certifique-se que essa função existe nesse escopo
                    });


                    // alert('Instância do WhatsApp criada com sucesso!');
                } else {

                    Swal.close();

                    swal.fire({
                        icon: 'error',
                        title: 'Erro ao criar instância do WhatsApp',
                        text: data.data?.message || 'Erro desconhecido',
                        confirmButtonText: 'OK'
                    });
                }
            } catch (error) {
                console.error('Erro na requisição:', error);
                alert('Erro na requisição para criar instância do WhatsApp.');
            }
        });
    }

    const deleteInstance = document.getElementById('delete_instance');
    if (deleteInstance) {
        deleteInstance.addEventListener('click', async function () {

            swal.fire({
                title: 'Deletando instância do WhatsApp',
                text: 'Aguarde enquanto deletamos a instância do WhatsApp.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
                showConfirmButton: false,
                showCancelButton: false,
            });

            try {

                const instanceName = document.getElementById('whatsapp-instance').dataset.instancename;

                console.log(instanceName);

                const response = await fetch(conciergeAjax.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: new URLSearchParams({
                        action: 'delete_whatsapp_instance',
                        instanceName: instanceName,
                    })
                });

                const { data } = await response.json();

                if (data.error === false) {

                    Swal.close();

                    Swal.fire({
                        icon: 'success',
                        title: 'Instância do WhatsApp deletada com sucesso!',
                        text: 'A instância foi removida.',
                        confirmButtonText: 'OK'
                    }).then(async () => {
                        await updateChatbot();
                        window.location.reload();
                    });

                } else {

                    Swal.close();

                    swal.fire({
                        icon: 'error',
                        title: 'Erro ao deletar instância do WhatsApp',
                        text: 'Erro desconhecido',
                        confirmButtonText: 'OK'
                    });
                }
            } catch (error) {
                swal.fire({
                    icon: 'error',
                    title: 'Erro ao deletar instância do WhatsApp',
                    // text: error,
                    confirmButtonText: 'OK'
                });
            }
        });
    }

    $('#gcalendar-connect').on('click', function () {
        console.log('adfads')
        // Chama seu backend para pegar a URL de login do Google
        $.ajax({
            url: conciergeAjax.ajax_url, // WordPress fornece isso automaticamente em admin, mas você pode definir no frontend também
            method: 'POST',
            data: {
                action: 'gcalendar_auth'
            },
            success: function (response) {
                if (response.url) {
                    window.location.href = response.url; // Redireciona para o Google
                    // window.open(response.url, '_blank');
                } else {
                    alert('Erro ao gerar URL de autenticação.');
                }
            }
        });
    });

    // $('#calendarsList').on('click', function () {
    //     // Chama seu backend para pegar a URL de login do Google
    //     $.ajax({
    //         url: conciergeAjax.ajax_url,
    //         method: 'POST',
    //         data: {
    //             action: 'create_calendar_event',
    //             calendar_id: 'primary',
    //             summary: 'Reunião de estratégia',
    //             all_day: 0,
    //             recurrence: 0,
    //             recurrence_end: '',
    //             event_date: '2025-07-14',
    //             start_time: '2025-07-14T17:30:00',
    //             end_time: '2025-07-14T18:00:00',
    //             event_timezone: 'America/Sao_Paulo',
    //             use_meet: 1,
    //             attendees: JSON.stringify(['thiagomarinho19t@gmail.com', 'marcosmacedo.fogao@gmail.com'])
    //         },
    //         success: function (res) {
    //             console.log(res);
    //             if (res.success) {
    //                 alert('Evento criado com sucesso: ' + res.data.event_id);
    //             } else {
    //                 alert('Erro: ' + res.data);
    //             }
    //         }
    //     });
    // });

    $('#calendarsList').on('click', function () {
        // Chama seu backend para pegar a URL de login do Google
        $.ajax({
            url: conciergeAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'handle_freebusy',
                calendar_id: 'primary',
                // event_id: 'mgo4jkg3db106a1ktta53bb3b0',
            },
            success: function (res) {
                console.log(res);
                if (res.success) {
                    alert('sucesso');
                } else {
                    alert('Erro: ' + res.data);
                }
            }
        });
    });

    $('#calendar-settings').on('submit', function (e) {
        e.preventDefault();

        // Captura os valores
        const workStart = $('#work_start').val();
        const workEnd = $('#work_end').val();
        const slotDuration = parseInt($('#slot_duration').val());

        // Dias disponíveis (checkboxes marcados)
        let availableDays = [];
        $('input[name="available_days[]"]:checked').each(function () {
            availableDays.push(parseInt($(this).val()));
        });

        const data = {
            work_start: workStart,
            work_end: workEnd,
            slot_duration: slotDuration,
            'available_days[]': availableDays,

            action: 'gcalendar_settings'
        };

        console.log('Dados enviados:', data);

        swal.fire({
            title: 'Salvando configurações...',
            text: 'Aguarde enquanto salvamos suas configurações.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        $.ajax({
            url: conciergeAjax.ajax_url,
            method: 'POST',
            data: data,
            success: function (response) {
                Swal.close(); // Fecha o loading
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: 'Configurações salvas com sucesso!',
                });
                console.log(response);
            },
            error: function (xhr) {
                Swal.close(); // Fecha o loading
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro ao salvar configurações.',
                });
                console.error(xhr.responseText);
            }
        });
    });

    $('#humanSession').on('click', function () {
        $.ajax({
            url: conciergeAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'end_human_session',
                thread_id: 'thread_rjeQsKPDQK8YeUA8nCi4sToB',
                instance_name: 'asst_lPNwzFeQhkNnExoeUqckb5ET_dev2_charlie'
            },
            success: function (response) {
                console.log('Sessão encerrada:', response);
            },
            error: function (xhr) {
                console.error('Erro:', xhr.responseText);
            }
        });
    });

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


    // // 
    // // 
    // //  WHATSAPP HANDLERS
    // // 
    // // 

    // const connectWhatsapp = document.getElementById('conectar-whatsapp');
    // if (connectWhatsapp) {
    //     connectWhatsapp.addEventListener('click', async function () {

    //         swal.fire({
    //             title: 'Criando instância do WhatsApp',
    //             text: 'Aguarde enquanto criamos uma nova instância do WhatsApp.',
    //             allowOutsideClick: false,
    //             didOpen: () => {
    //                 Swal.showLoading();
    //             },
    //             showConfirmButton: false,
    //             showCancelButton: false,
    //         });

    //         try {
    //             const assistantId = JSON.parse(localStorage.getItem('assistant'))?.id;

    //             const response = await fetch(conciergeAjax.ajax_url, {
    //                 method: 'POST',
    //                 headers: {
    //                     'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
    //                 },
    //                 body: new URLSearchParams({
    //                     action: 'create_whatsapp_instance',
    //                     // instanceName: newInstanceName,
    //                     assistant_id: assistantId
    //                 })
    //             });
    //             const result = await response.json();
    //             if (result.success) {

    //                 Swal.close();

    //                 const data = result.data;

    //                 Swal.fire({
    //                     title: 'Escaneie o QR Code para conectar o WhatsApp',
    //                     html: `<img src="${data.qrcode.base64}" alt="QR Code do WhatsApp" style="max-width: 100%; height: auto;" />`,
    //                     showConfirmButton: true,
    //                     confirmButtonText: 'Fechar'
    //                 }).then((result) => {
    //                     console.log('SweetAlert fechado', result);
    //                     updateChatbot(); // Certifique-se que essa função existe nesse escopo
    //                 });


    //                 // alert('Instância do WhatsApp criada com sucesso!');
    //             } else {

    //                 Swal.close();

    //                 swal.fire({
    //                     icon: 'error',
    //                     title: 'Erro ao criar instância do WhatsApp',
    //                     text: data.data?.message || 'Erro desconhecido',
    //                     confirmButtonText: 'OK'
    //                 });
    //             }
    //         } catch (error) {
    //             console.error('Erro na requisição:', error);
    //             alert('Erro na requisição para criar instância do WhatsApp.');
    //         }
    //     });
    // }

    // const deleteInstance = document.getElementById('delete_instance');
    // if (deleteInstance) {
    //     deleteInstance.addEventListener('click', async function () {

    //         swal.fire({
    //             title: 'Deletando instância do WhatsApp',
    //             text: 'Aguarde enquanto deletamos a instância do WhatsApp.',
    //             allowOutsideClick: false,
    //             didOpen: () => {
    //                 Swal.showLoading();
    //             },
    //             showConfirmButton: false,
    //             showCancelButton: false,
    //         });

    //         try {

    //             const instanceName = document.getElementById('whatsapp-instance').dataset.instancename;

    //             console.log(instanceName);

    //             const response = await fetch(conciergeAjax.ajax_url, {
    //                 method: 'POST',
    //                 headers: {
    //                     'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
    //                 },
    //                 body: new URLSearchParams({
    //                     action: 'delete_whatsapp_instance',
    //                     instanceName: instanceName,
    //                 })
    //             });

    //             const { data } = await response.json();

    //             if (data.error === false) {

    //                 Swal.close();

    //                 Swal.fire({
    //                     icon: 'success',
    //                     title: 'Instância do WhatsApp deletada com sucesso!',
    //                     text: 'A instância foi removida.',
    //                     confirmButtonText: 'OK'
    //                 }).then(() => {
    //                     window.location.reload();
    //                 });

    //             } else {

    //                 Swal.close();

    //                 swal.fire({
    //                     icon: 'error',
    //                     title: 'Erro ao deletar instância do WhatsApp',
    //                     text: 'Erro desconhecido',
    //                     confirmButtonText: 'OK'
    //                 });
    //             }
    //         } catch (error) {
    //             swal.fire({
    //                 icon: 'error',
    //                 title: 'Erro ao deletar instância do WhatsApp',
    //                 // text: error,
    //                 confirmButtonText: 'OK'
    //             });
    //         }
    //     });
    // }


    //
    //  Passar as instruções junto do attachment
    //

    // const attachmentInstructionsObserver = new MutationObserver(functino)
});

document.addEventListener('DOMContentLoaded', () => {
    // --- Seletores do DOM ---
    const conversationList = document.getElementById('conversation-list');
    const searchInput = document.getElementById('search-input');
    const chatHeader = document.getElementById('chat-header');
    const chatMessages = document.getElementById('chat-messages');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const messageSendButton = chatForm?.querySelector('button');
    const messageSendButtonIcon = messageSendButton?.querySelector('svg');

    if (conversationList) {
        let conversations = [];

        const fetchConversations = () => {
            console.log('Fetching conversations...');
            fetch(ajaxurl + '?action=list_conversations')
                .then(res => res.json())
                .then(data => {
                    console.log('Conversations fetched:', data);
                    if (data.success) {
                        conversations = data.data.conversations;
                        if (activeConversationId) {
                            renderMessages(activeConversationId);
                        }
                        renderConversations();
                        // console.log('Conversations:', conversations);
                    } else {
                        alert('Erro ao carregar conversas');
                    }
                });
        };

        let notifications = [];

        const fetchNotifications = () => {
            console.log('Fetching notifications...');
            fetch(ajaxurl + '?action=list_human_session_flag')
                .then(res => res.json())
                .then(data => {
                    // console.log('Conversations fetched:', data);
                    if (data.success) {
                        notifications = data.data.flags || [];
                        // if (activeConversationId) {
                        //     renderMessages(activeConversationId);
                        // }
                        renderConversations();
                        console.log('Flags: ', notifications);
                    } else {
                        alert('Erro ao carregar flags');
                    }
                });
        };

        let activeConversationId = null;

        // --- Funções de Renderização ---

        const renderConversations = async () => {
            const filter = searchInput.value.toLowerCase();
            conversationList.innerHTML = '';

            const filteredConversations = conversations.filter(c =>
                c.name?.toLowerCase().includes(filter) || c.id.toString().includes(filter)
            );

            if (filteredConversations.length === 0) {
                conversationList.innerHTML = '<p style="padding: 1rem; color: var(--color-secondary-text);">Nenhuma conversa encontrada.</p>';
                return;
            }

            filteredConversations.forEach(convo => {
                const item = document.createElement('div');
                item.className = 'conversation-item';
                item.dataset.id = convo.id;
                if (convo.id === activeConversationId) {
                    item.classList.add('active');
                }
                if (convo.paused) {
                    item.classList.add('paused');
                }

                const humanFlag = notifications.find(f => f.remote_jid === convo.id);
                let humanFlagHTML = '';

                if (humanFlag) {
                    item.classList.add('relative')
                    humanFlagHTML = `
                        <div class="absolute top-1 left-0">
                            <span class="relative flex size-3">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex size-3 rounded-full bg-red-500"></span>
                            </span>
                        </div>
                    `
                }

                item.innerHTML = `
                    ${humanFlagHTML}
                    <div class="conversation-details">
                        <h4>${convo.name}</h4>
                        <p>${convo.lastMessage}</p>
                    </div>
                    <button class="pause-btn ${convo.paused ? 'resume' : ''}">
                        ${convo.paused ? 'Retomar' : 'Pausar'}
                    </button>
                `;

                // Evento para selecionar a conversa
                item.addEventListener('click', (e) => {
                    // Impede que o clique no botão de pausa selecione a conversa
                    if (e.target.closest('.pause-btn')) return;
                    handleConversationClick(convo.id);
                });

                // Evento para o botão de pausar/retomar
                item.querySelector('.pause-btn').addEventListener('click', () => {
                    handlePauseToggle(convo.id);
                });

                conversationList.appendChild(item);
            });
        };

        const renderMessages = (convoId) => {
            const conversation = conversations.find(c => c.id === convoId);
            if (!conversation) return;

            chatHeader.innerHTML = `<h2>${conversation.name}</h2>`;
            chatMessages.innerHTML = '';

            conversation.messages.forEach(msg => {
                const messageDiv = document.createElement('div');
                const isFromMe = msg.from_me === true || msg.from_me === 1 || msg.from_me === '1';
                messageDiv.className = `message ${isFromMe ? 'sent' : 'received'}`;

                // Criar elemento de texto da mensagem
                const messageText = document.createElement('span');
                // messageText.classList.add('whitespace-pre-wrap')
                messageText.innerHTML = msg.message;

                // Criar elemento de horário
                const messageTime = document.createElement('span');
                messageTime.className = 'message-time';

                // Converter string para Date e formatar HH:mm
                const date = new Date(msg.date_time.replace(' ', 'T')); // precisa do 'T' para ser ISO
                messageTime.textContent = date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

                // Montar a mensagem
                messageDiv.appendChild(messageText);
                messageDiv.appendChild(messageTime);

                // Caso de erro
                if (msg.status === 'error') {
                    messageDiv.classList.add('message-error');
                    messageDiv.innerHTML += `<span class="message-error-icon" title="Erro ao enviar. Clique para tentar novamente.">❗</span>`;
                }

                chatMessages.appendChild(messageDiv);
            });
            // conversation.messages.forEach(msg => {



            //     // const messageDiv = document.createElement('div');
            //     // const isFromMe = msg.from_me === true || msg.from_me === 1 || msg.from_me === '1';
            //     // messageDiv.className = `message ${isFromMe ? 'sent' : 'received'}`;
            //     // // messageDiv.className = `message ${msg.from_me === true ? 'sent' : 'received'}`;
            //     // messageDiv.textContent = msg.message;

            //     // if (msg.status === 'error') {
            //     //     messageEl.classList.add('message-error');
            //     //     messageEl.innerHTML += `<span class="message-error-icon" title="Erro ao enviar. Clique para tentar novamente.">❗</span>`;
            //     // }

            //     // chatMessages.appendChild(messageDiv);
            // });


            chatMessages.scrollTop = chatMessages.scrollHeight;
            messageInput.disabled = false;
            messageSendButton.disabled = false;
            messageInput.focus();
        };

        // --- Funções de Manipulação de Eventos ---

        const handleConversationClick = (convoId) => {
            activeConversationId = convoId;
            renderConversations(); // Re-renderiza para mostrar o item ativo
            renderMessages(convoId);
        };

        const handlePauseToggle = async (convoId) => {
            const conversation = conversations.find(c => c.id === convoId);

            if (!conversation) {
                console.error('Conversa não encontrada:', convoId);
                return;
            }

            const formData = new FormData();
            formData.append('action', 'toggle_human_session');
            formData.append('remoteJid', conversation.id);
            formData.append('instanceName', document.querySelector('.chat-container')?.dataset?.instance);

            try {
                const res = await fetch(ajaxurl, {
                    method: 'POST',
                    body: formData,
                });

                const data = await res.json();

                if (data.success) {
                    // console.log('Sessão alternada com sucesso:', data.data);
                    conversation.paused = !conversation.paused;
                    renderConversations();
                } else {
                    alert('Erro ao alternar a sessão: ' + (data.data.mensagem || ''));
                }
            } catch (error) {
                console.error('Erro na requisição:', error);
                alert('Ocorreu um erro ao tentar se conectar com o servidor.');
            }
        };

        const sendMessage = async (e) => {
            e.preventDefault();
            const text = messageInput.value.trim();
            if (text === '' || !activeConversationId) return;

            const conversation = conversations.find(c => c.id === activeConversationId);

            const formData = new FormData();
            formData.append('action', 'send_whatsapp_message');
            formData.append('remoteJid', conversation.id);
            formData.append('instanceName', document.querySelector('.chat-container')?.dataset?.instance);
            formData.append('message', text);

            // const message = {
            //     sender: 'Atendente',
            //     text,
            //     status: 'sending',
            // };

            // if (conversation) {
            //     conversation.messages.push(message);
            //     conversation.lastMessage = text;
            //     messageInput.value = '';
            //     renderMessages(activeConversationId);
            //     renderConversations();
            // }

            messageInput.disabled = true;
            messageSendButton.disabled = true;
            messageSendButtonIcon.classList.add('animate-spin');

            try {
                const res = await fetch(ajaxurl, {
                    method: 'POST',
                    body: formData,
                });

                const data = await res.json();

                // message.status = data.success ? 'sent' : 'error';

                renderMessages(activeConversationId);
                renderConversations();

            } catch (error) {
                console.error('Erro na requisição:', error);
                message.status = 'error';
                renderMessages(activeConversationId);
                renderConversations();
            }

            messageInput.value = '';
            messageSendButtonIcon.classList.remove('animate-spin');
            messageInput.disabled = false;
        };

        // --- Adicionando Event Listeners ---
        searchInput.addEventListener('input', renderConversations);
        chatForm.addEventListener('submit', sendMessage);

        // --- Inicialização ---
        fetchConversations();
        fetchNotifications();
        setInterval(fetchConversations, 8000);
        setInterval(fetchNotifications, 10000);

    }

});

document.addEventListener("DOMContentLoaded", function () {

    if (!document.getElementById("conversations-body")) return;

    function loadConversations(page = 1, startDate = null, endDate = null) {
        const body = document.getElementById("conversations-body");
        const pagination = document.getElementById("pagination");

        body.innerHTML = `<tr><td colspan="7" class="text-center py-4">Carregando...</td></tr>`;
        pagination.innerHTML = "";

        const params = new URLSearchParams({
            action: "get_conversations",
            page: page,
        });

        if (startDate && endDate) {
            params.append("startDate", startDate);
            params.append("endDate", endDate);
        }

        fetch(ajaxurl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: params
        })
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    body.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-red-500">${res.data.message}</td></tr>`;
                    return;
                }

                const { data, page: currentPage, pages } = res.data;

                if (data.length === 0) {
                    body.innerHTML = `<tr><td colspan="7" class="text-center py-4">Nenhuma conversa encontrada.</td></tr>`;
                    return;
                }

                body.innerHTML = data.map(conv => `
            <tr class="odd:bg-white even:bg-gray-50 border-b border-gray-200">
                <td class="px-6 py-4">${conv.canal}</td>
                <td class="px-6 py-4">${conv.id}</td>
                <td class="px-6 py-4">${conv.status}</td>
                <td class="px-6 py-4">
                    ${conv.lead && (conv.lead.nome || conv.lead.telefone)
                        ? [conv.lead.nome ? "nome" : "", conv.lead.telefone ? "telefone" : ""].filter(Boolean).join(", ")
                        : "Não informado"}
                </td>
                <td class="px-6 py-4">${conv.titulo}</td>
                <td class="px-6 py-4">${conv.dataHora}</td>
                <td class="px-6 py-4">
                    <div class="flex flex-1 h-full space-x-2 items-center">
                        <a href="#" class="ver-conversa text-blue-600 hover:underline" 
                        data-id="${conv.id}" 
                        data-conversa='${JSON.stringify(conv.messages)}'>
                        Ver
                        </a>
                        <a href="#" class="download-conversa text-blue-600 hover:underline ml-2" 
                        data-id="${conv.id}" 
                        data-conversa='${JSON.stringify(conv.messages)}'>
                        Download
                        </a>
                    </div>
                </td>
            </tr>
        `).join("");

                // Paginação
                let pagBtns = "";
                if (currentPage > 1) {
                    pagBtns += `<button class="px-3 py-1 bg-gray-100 rounded" data-page="${currentPage - 1}">Anterior</button>`;
                }
                for (let i = 1; i <= pages; i++) {
                    pagBtns += `<button class="px-3 py-1 ${i === currentPage ? 'bg-blue-500 text-white' : 'bg-gray-100'} rounded" data-page="${i}">${i}</button>`;
                }
                if (currentPage < pages) {
                    pagBtns += `<button class="px-3 py-1 bg-gray-100 rounded" data-page="${currentPage + 1}">Próxima</button>`;
                }
                pagination.innerHTML = pagBtns;

                pagination.querySelectorAll("button").forEach(btn => {
                    btn.addEventListener("click", () => loadConversations(btn.dataset.page, startDate, endDate));
                });

                document.querySelectorAll(".ver-conversa").forEach(link => {
                    link.addEventListener("click", function (e) {
                        e.preventDefault();

                        // console.log(this)
                        const mensagens = JSON.parse(this.dataset.conversa);
                        const conversationId = this.dataset.id;

                        let dataPrimeiraMensagem = '';
                        if (mensagens.length > 0 && mensagens[0].date) {
                            // Pega só a parte da data (antes do espaço)
                            const partes = mensagens[0].date.split(' ')[0].split('/');
                            if (partes.length === 3) {
                                // Garante que está no formato dd/mm/yyyy
                                dataPrimeiraMensagem = `${partes[0]}/${partes[1]}/${partes[2]}`;
                            } else {
                                dataPrimeiraMensagem = mensagens[0].date;
                            }
                        }
                        let html = `
                            <div class="text-left max-h-96 overflow-y-auto flex flex-col space-y-4 p-4">
                                <div class="flex flex-col space-y-1 mb-5">
                                    <h1 class="font-bold text-2xl text-black">Conversa do dia ${dataPrimeiraMensagem}</h1>
                                    <span class="text-gray-500 text-sm">${conversationId}</span>
                                </div>
                        `;
                        mensagens.forEach(msg => {
                            const fromMe = msg.from_me == 1 || msg.from_me === true;
                            html += `
                                    <div class="flex items-start gap-2.5 ${fromMe ? "self-end" : "self-start"}">
                                    <div class="flex flex-col w-full max-w-[320px] leading-1.5 p-4 border-gray-200 rounded-xl ${fromMe ? "bg-blue-100" : "bg-gray-100"}">
                                        <p class="text-sm font-normal py-2.5 text-gray-900 whitespace-pre-wrap">${msg.text}</p>
                                        <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                            <span class="text-sm font-normal text-gray-500">${msg.date}</span>
                                        </div>
                                    </div>
                                    </div>

                                `;
                        });
                        html += '</div>';

                        Swal.fire({
                            // title: `Conversa ${conversationId}`,
                            html: html,
                            width: "800px",
                            showCloseButton: true,
                            showConfirmButton: false,
                            scrollbarPadding: false
                        });
                    });
                });

                document.querySelectorAll(".download-conversa").forEach(link => {
                    link.addEventListener("click", function (e) {
                        e.preventDefault();

                        const mensagens = JSON.parse(this.dataset.conversa);
                        const conversationId = this.dataset.id;

                        const formData = new FormData();
                        formData.append("action", "conversations_download");
                        formData.append("conversationId", conversationId);
                        formData.append("messages", JSON.stringify(mensagens));

                        swal.fire({
                            title: 'Confirmar download',
                            text: 'Deseja gerar e baixar o PDF desta conversa?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Sim, baixar',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                swal.fire({
                                    title: 'Gerando PDF...',
                                    text: 'Aguarde enquanto o PDF é gerado.',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    },
                                    showConfirmButton: false,
                                    showCancelButton: false,
                                });

                                fetch(ajaxurl, {
                                    method: "POST",
                                    body: formData
                                })
                                    .then(res => res.blob())
                                    .then(blob => {
                                        const url = window.URL.createObjectURL(blob);
                                        const a = document.createElement("a");
                                        a.href = url;
                                        a.download = `conversation-${conversationId}.pdf`;
                                        document.body.appendChild(a);
                                        a.click();
                                        a.remove();
                                        window.URL.revokeObjectURL(url);

                                        swal.close();
                                    })
                                    .catch(err => {
                                        console.error("Erro ao baixar:", err);
                                        alert("Erro ao gerar PDF");
                                    });
                            }
                        })
                    });
                });

            });
    }

    // Carregar inicial
    loadConversations(1);

    const calendar = document.querySelector("#messages-range");
    const fp = flatpickr(calendar,
        {
            mode: 'range',
            dateFormat: "Y-m-d",
            locale: 'pt'
        }
    );  // flatpickr

    document.querySelector("#apply-filter").addEventListener("click", () => {
        const dates = fp.selectedDates; // array de objetos Date
        if (dates.length === 2) {
            const startDate = fp.formatDate(dates[0], "Y-m-d");
            const endDate = fp.formatDate(dates[1], "Y-m-d");

            loadConversations(1, startDate, endDate);
        } else {
            alert("Selecione um intervalo de datas completo.");
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    const created = urlParams.get('success') === '1';
    if (created) {
        const testeTabButton = document.querySelector('.tab-btn[data-tab="Teste"]');
        if (testeTabButton) {
            console.log('Clicking on Teste tab button');
            console.log(testeTabButton);
            setTimeout(() => {
                testeTabButton.click();
            }, 800);
        }
    }
});

// Controle arquivos
document.addEventListener('DOMContentLoaded', function () {
    const fileInputs = document.querySelectorAll('#base_de_conhecimento-content input[type="file"]');

    fileInputs.forEach(input => {
        const acceptedTypes = input.getAttribute('accept');

        // Seleção manual
        input.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;

            if (!isValidFileType(file, acceptedTypes)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tipo de arquivo inválido!',
                    text: 'Por favor, envie um arquivo do tipo permitido.',
                });
                input.value = '';
            }
        });
    });

    function isValidFileType(file, acceptString) {
        const accepted = acceptString.split(',').map(type => type.trim());

        for (const type of accepted) {
            if (type === 'audio/*' && file.type.startsWith('audio/')) return true;
            if (type.startsWith('.') && file.name.toLowerCase().endsWith(type)) return true;
            if (type === file.type) return true;
        }

        return false;
    }
});

//Gcalendar Js
document.addEventListener('DOMContentLoaded', function () {
    const connectButton = document.querySelector('#gcalendar-connect');
    if (!connectButton) return;

    connectButton.addEventListener('click', function (e) {
        if (!connectButton.classList.contains('gcalendar-tooltip')) return;

        e.preventDefault();

        Swal.fire({
            icon: 'warning',
            title: 'Atenção',
            html: `Certifique-se de que você está usando um e-mail do Gmail para conectar ao Google Calendar.<br>Se não estiver, por favor, <a href="/minha-conta/edit-account/" target="_blank" class="underline text-blue-950">clique aqui</a> para alterar seu e-mail na página de edição de conta antes de prosseguir.`,

        });

        connectButton.classList.remove('gcalendar-tooltip');
    });

    // Lidando com o erro
    const urlParams = new URLSearchParams(window.location.search);
    const notCreated = urlParams.get('gcalendar-error') === 'user-not-found';
    if (notCreated) {
        swal.fire({
            icon: 'error',
            title: 'Erro',
            text: 'Não foi possivel conectar. Usuário não encontrado no sistema. Por favor, tente novamente.',
        })
    }
});