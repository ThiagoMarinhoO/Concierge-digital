document.addEventListener('DOMContentLoaded', function () {
    const formElement = document.getElementById('concierge-form');
    const chatbotButton = document.getElementById('concierge-test-chatbot');
    const resultContainer = document.getElementById('concierge-test-result');

    chatbotButton.addEventListener('click', async function (e) {
        e.preventDefault();

        // Gerar JSONL a partir dos campos do formulário
        const jsonlData = [];
        formElement.querySelectorAll('input, select, textarea').forEach((field) => {
            if (field.name && field.value.trim()) {
                jsonlData.push({
                    field: field.name,
                    content: field.value.trim(),
                });
            }
        });

        if (jsonlData.length === 0) {
            resultContainer.innerHTML = '<p style="color: red;">Por favor, preencha os campos do formulário.</p>';
            return;
        }

        console.log('JSONL gerado no frontend:', jsonlData);

        // Exibir mensagem de carregamento
        resultContainer.innerHTML = '<p>Processando... Por favor, aguarde.</p>';

        try {
            // Enviar JSONL para o backend
            const response = await fetch(conciergeAjax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'concierge_process_form', // Nome da ação configurada no backend
                    jsonl_data: jsonlData.map(item => JSON.stringify(item)).join('\n'), // Concatenar JSONL
                    nonce: conciergeAjax.nonce, // Segurança do WordPress
                }),
            });

            if (!response.ok) {
                throw new Error(`Erro na requisição: ${response.statusText}`);
            }

            const responseData = await response.json();
            console.log('Resposta do Backend:', responseData);

            if (responseData.success) {
                // Exibir resultado do chatbot
                const chatbotWindow = window.open('', '_blank');
                chatbotWindow.document.write(
                    `<iframe src="${responseData.data.chatbot_url}" style="width:100%; height:500px;"></iframe>`
                );
                resultContainer.innerHTML = '<p style="color: green;">Chatbot gerado com sucesso!</p>';
            } else {
                const errorMessage = responseData.data?.message || 'Erro desconhecido.';
                resultContainer.innerHTML = `<p style="color: red;">Erro: ${errorMessage}</p>`;
                console.error('Erro do Backend:', errorMessage);
            }
        } catch (error) {
            resultContainer.innerHTML = '<p style="color: red;">Erro ao se conectar ao servidor. Por favor, tente novamente.</p>';
            console.error('Erro na Requisição:', error);
        }
    });
});
