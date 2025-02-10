jQuery(document).ready(function ($) {
    var sendButton = $('#enviarMensagem');
    if (sendButton.length) {
        sendButton.on('click', function (event) {
            event.preventDefault();

            const assistantId = $('.chatContainer').data('chatbot-id');
            let currHour = new Date();

            const userMsgTemplate = `
                <div class="flex w-full mt-2 space-x-3 max-w-xs ml-auto justify-end messageInput">
                    <div>
                        <div class="bg-blue-600 text-white p-3 rounded-l-lg rounded-br-lg text-sm text-black">
                            ${$(".mensagem").val()}
                        </div>
                        <span class="text-xs text-gray-500 leading-none">${currHour.getHours()}:${currHour.getMinutes()}</span>
                    </div>
                    <div class="flex-shrink-0 flex justify-center items-center h-10 w-10 rounded-full bg-gray-300">
                        <svg class="size-6 text-blue-600" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0z"/></svg>
                    </div>
                </div>`;

            let chatBox = $(".chatContainer");
            chatBox.append(userMsgTemplate);
            chatBox.scrollTop(chatBox.prop("scrollHeight"));

            const formData = new FormData();
            formData.append('action', 'concierge_chat');
            formData.append('assistantId', assistantId);
            formData.append('mensagem', $(".mensagem").val());

            $(".mensagem").val("");
            sendButton.prop('disabled', true).addClass('opacity-90');
            $("#enviarMensagem svg").addClass('animate-spin');

            $.ajax({
                url: conciergeAjax.ajax_url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (data) {
                    let currHour = new Date();
                    let responseData = JSON.parse(data.data);

                    function transformarLinks(texto) {
                        return texto.replace(/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g, '<a href="$2" target="_blank" class="text-blue-600 underline">$1</a>');
                    }

                    let mensagemFormatada = transformarLinks(responseData.message);

                    let aiMsgTemplate = `
                        <div class="flex w-full mt-2 space-x-3 max-w-xs messageInput">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300">
                                <img src="${responseData.image}" class="size-10 rounded-full" alt="">
                            </div>
                            <div>
                                <div class="bg-gray-300 p-3 rounded-r-lg rounded-bl-lg text-sm">
                                    ${mensagemFormatada}
                                </div>
                                <span class="text-xs text-gray-500 leading-none">${currHour.getHours()}:${currHour.getMinutes()}</span>
                            </div>
                        </div>`;

                    chatBox.append(aiMsgTemplate);
                    chatBox.scrollTop(chatBox.prop("scrollHeight"));
                },
                error: function (error) {
                    console.error('Error:', error);
                },
                complete: function () {
                    $("#enviarMensagem svg").removeClass('animate-spin');
                    sendButton.removeClass('opacity-90').prop('disabled', false);
                }
            });
        });
    }

    const messageField = $(".mensagem");
    if (messageField.length) {
        messageField.on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                sendButton.click();
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

    async function updateChatbot(chatbotOptions) {
        const chatbotId = $("#chatbotID").val();
        const chatbotName = $(".assistent-name").val();
        const welcomeMessage = $(".assistent-message").val();
        const fileInput = $("#appearance_image")[0];
        let file_url = "";

        if (fileInput && fileInput.files.length > 0) {
            const formData = new FormData();
            formData.append("files[]", fileInput.files[0]);
            formData.append("action", "upload_files_to_media_library");

            try {
                const response = await fetch(conciergeAjax.ajax_url, {
                    method: "POST",
                    body: formData,
                });

                const data = await response.json();
                if (data.success) {
                    file_url = data.data.urls;
                } else {
                    console.error("Falha ao enviar arquivos:", data.message);
                    return;
                }
            } catch (error) {
                console.error("Erro na requisição de upload:", error);
                return;
            }
        }

        chatbotOptions = chatbotOptions.map(option => {
            if (option.field_type === "file" && !option.value) {
                delete option.value;
            }
            return option;
        });

        const chatbotFormData = new FormData();
        chatbotFormData.append("action", "save_responses");
        chatbotFormData.append("chatbot_options", JSON.stringify(chatbotOptions));
        chatbotFormData.append("chatbot_id", chatbotId);
        chatbotFormData.append("chatbot_name", chatbotName);
        chatbotFormData.append("chatbot_image", file_url);
        chatbotFormData.append("chatbot_welcome_message", welcomeMessage);

        try {
            const chatbotResponse = await fetch(conciergeAjax.ajax_url, {
                method: "POST",
                body: chatbotFormData,
            });

            const chatbotData = await chatbotResponse.json();
            if (chatbotData.success) {
                console.log("Chatbot atualizado com sucesso!", chatbotData);
            } else {
                console.error("Erro ao atualizar chatbot:", chatbotData.message);
            }
        } catch (error) {
            console.error("Erro na requisição do chatbot:", error);
        }
    }

    function unlockNextTab() {
        if (currentTabIndex < buttons.length - 1) {
            currentTabIndex++;
            const nextTabName = $(buttons[currentTabIndex]).data("tab");
            $($(buttons[currentTabIndex])).data("locked", "false");
            $($(buttons[currentTabIndex])).removeClass("opacity-50 cursor-not-allowed");
            showTabContent(nextTabName);
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

            console.log(categoryNameElement)

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
            activeContent.find(".question-block").each((index, questionBlock) => {
                const inputElement = $(questionBlock).find("input:not([type='checkbox']), select").get(0);
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

            saveData(chatbotOptions);

            const hasChatbot = $("#hasChatbot").val();

            if (hasChatbot === '1') {
                updateChatbot(chatbotOptions);
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
            if ($(button).data("locked") === "true") {
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
            console.log(tabName)
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
        $saveBtn.on("click", function () {
            saveResponses();
        });
    }

    const $saveAparenciaButton = $("button.saveAparenciaButton");

    if ($saveAparenciaButton.length) {
        $saveAparenciaButton.on("click", function () {
            saveStyles();
        });
    }

    const storedData = JSON.parse(localStorage.getItem('chatbotRespostas'));
    if (storedData) {
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
                    $nextTabButton.data('locked', false);
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

            formData.append("action", "create_chatbot");
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
                    $.ajax({
                        url: conciergeAjax.ajax_url,
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false
                    })
                        .done(function (data) {
                            // unlockNextTab();
                            // window.location.reload();
                        })
                        // .complete(function() {
                        //     unlockNextTab();
                        //     window.location.reload();
                        // })
                        .fail(function (error) {
                            console.error("Erro:", error);
                        })
                }
            });
        });
    }

    const $downloadTabButton = $('button[data-tab="Download"]');

    function checkAllTabsUnlocked() {
        let allUnlocked = true;
        $.each(buttons, function (index, button) {
            if (index < buttons.length - 1 && $(button).data('locked') === "true") {
                allUnlocked = false;
            }
        });
        return allUnlocked;
    }

    if (checkAllTabsUnlocked()) {
        $('[data-tab="Download"]').data('locked', 'false').removeClass('opacity-50 cursor-not-allowed');
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