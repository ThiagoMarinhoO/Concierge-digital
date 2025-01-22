<?php
$chatbot = new Chatbot();
$user_id = get_current_user_id();

$user_has_chatbot = $chatbot->userHasChatbot($user_id);
$chatbots = $chatbot->getAllChatbots();

if ($user_has_chatbot) : ?>
	<div class="flex flex-col items-center justify-center w-screen min-h-screen bg-gray-100 text-gray-800 p-10">
		<div class="flex flex-col flex-grow w-full max-w-xl bg-white shadow-xl rounded-lg overflow-hidden">

			<!-- Select para selecionar o chatbot -->
			<div class="p-4 bg-gray-200">
				<label for="chatbot-selector" class="block text-sm font-medium text-gray-700">Selecione o Chatbot:</label>
				<select id="chatbot-selector" class="block w-full py-2 mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
					<?php foreach ($chatbots as $bot): ?>
						<option value="<?php echo esc_attr($bot->id); ?>">
							<?php echo esc_html($bot->chatbot_name); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<!-- Container do chat -->
			<div class="flex flex-col flex-grow h-0 p-4 overflow-auto chatContainer" data-chatbot-id="<?php echo esc_attr($chatbots[0]->id); ?>">
			</div>

			<!-- Input para mensagem -->
			<div class="bg-gray-300 p-4 relative">
				<input class="flex items-center h-10 w-full rounded px-3 text-sm mensagem" type="text" placeholder="Escreva sua mensagem">
				<button class="bg-blue-600 text-white flex items-center justify-center p-2 rounded absolute top-4 right-4" id="enviarMensagem">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
					</svg>
				</button>
			</div>
		</div>
	</div>

	<script>

window.addEventListener('DOMContentLoaded', function() {
    const inputField = document.querySelector('.mensagem');
    const sendButton = document.querySelector('#enviarMensagem');
	const assistantId = document.querySelector('.chatContainer').getAttribute('data-chatbot-id');

    // Evento de clique no botão
    sendButton.addEventListener('click', function(event) {
        event.preventDefault();
		
		let currHour = new Date();
    
                const userMsgTemplate = `
                        <div class="flex w-full mt-2 space-x-3 max-w-xs ml-auto justify-end message">
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
                formData.append('assistantId', assistantId );
                formData.append('mensagem', document.querySelector(".mensagem").value);
    
                document.querySelector(".mensagem").value = "";
                document.querySelector("#enviarMensagem").disabled = true;
                document.querySelector("#enviarMensagem").classList.add('opacity-90');
    
                fetch(conciergeAjax.ajax_url, {
                        method: 'POST',
                        body: formData
                    }).then(response => response.json())
                    .then(data => {
    
                        let currHour = new Date();
    
                        data.responseMessage = data.responseMessage.replace("\n", "<br>");
    
                        let aiMsgTemplate = `
                            <div class="flex w-full mt-2 space-x-3 max-w-xs">
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
                        document.querySelector("#enviarMensagem").classList.remove('opacity-90');
                        document.querySelector("#enviarMensagem").disabled = false;
                    });

    });

    // Enviar mensagem ao pressionar Enter
    inputField.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            sendButton.click();
        }
    });
});

	</script>
<?php else: ?>


	<?php
	$questionsManager = new Question();
	$questions = $questionsManager->getAllQuestions();
	?>

	<div id="concierge-container">
		<form id="concierge-form" method="POST">
			<h2>Responda às Perguntas</h2>

			<div class="question-block">
				<label for="chatbot_name">
					Qual o nome do Chatbot
				</label>
				<input
					type="text"
					id=""
					name="chatbot_name"
					placeholder="Qual o nome do chatbot ?"
					required>
			</div>

			<?php if (!empty($questions)): ?>
				<?php foreach ($questions as $index => $question): ?>
					<div class="question-block">
						<label for="question-<?php echo esc_attr($index); ?>" data-question-base="<?php echo $question['training_phrase']; ?>">
							<?php echo esc_html($question['title']); ?>
						</label>
						<?php
						$options = json_decode($question['options'], true);
						?>
						<?php if (!empty($options) && is_array($options)): ?>
							<select class="py-2 px=2.5 border border-gray-100 rounded-lg w-full my-2" id="question-<?php echo esc_attr($index); ?>" name="question_<?php echo esc_attr($question['id']); ?>">
								<?php foreach ($options as $option): ?>
									<option value="<?php echo esc_attr($option); ?>">
										<?php echo esc_html($option); ?>
									</option>
								<?php endforeach; ?>
							</select>
						<?php else: ?>
							<input
								type="text"
								id="question-<?php echo esc_attr($index); ?>"
								name="question_<?php echo esc_attr($question['id']); ?>"
								placeholder="<?php echo esc_attr($question['training_phrase']); ?>">
						<?php endif; ?>
					</div>
				<?php endforeach; ?>

				<button type="submit">Enviar Respostas</button>
			<?php else: ?>
				<p>Nenhuma pergunta cadastrada no momento.</p>
			<?php endif; ?>

			<div id="concierge-test-result"></div>
		</form>
	</div>


	<!-- Scripts inline para controle dinâmico -->
	<script>
		function toggleAdditionalField(selectId, containerId) {
			const select = document.getElementById(selectId);
			const container = document.getElementById(containerId);
			container.style.display = select.value === 'Outros' ? 'block' : 'none';
		}

		document.addEventListener("DOMContentLoaded", () => {
			const form = document.getElementById("concierge-form");
			const resultDiv = document.getElementById("concierge-test-result");

			form.addEventListener("submit", (event) => {
				event.preventDefault();

				const chatbotOptions = [];

				// Itera pelos campos do formulário para construir o chatbotOptions
				const formElements = form.elements;
				for (let element of formElements) {
					if (element.name.startsWith('question_')) {
						// Captura a pergunta (label), o nome do campo, a resposta fornecida e o training_phrase
						const perguntaLabel = document.querySelector(`label[for="${element.id}"]`).innerText.trim();
						const resposta = element.value.trim();
						const trainingPhrase = document.querySelector(`label[for="${element.id}"]`).dataset.questionBase;

						chatbotOptions.push({
							pergunta: perguntaLabel,
							field_name: element.name,
							resposta: resposta,
							training_phrase: trainingPhrase
						});
					}
				}

				// Configura o FormData para envio
				const formData = new FormData(form);
				formData.append('action', 'create_chatbot');
				formData.append('chatbot_options', JSON.stringify(chatbotOptions));

				resultDiv.innerHTML = "Enviando...";

				// Envia os dados usando fetch
				fetch(conciergeAjax.ajax_url, {
						method: "POST",
						body: formData,
					})
					.then((response) => response.json())
					.then((data) => {
						resultDiv.innerHTML = `
                    <strong>Resposta do Servidor:</strong> ${JSON.stringify(data.data)}
                `;
					}).finally(() => {
						window.location.reload();
					})
					.catch((error) => {
						console.error("Erro:", error);
						resultDiv.innerHTML = `
                    <strong>Erro:</strong> Não foi possível processar a solicitação.
                `;
					});
			});
		});
	</script>

<?php endif; ?>


<!-- USUÁRIO PRECISA DE UM PAINEL PARA CADASTRAR CHATBOTS OU APRESENTAR OS EXISTENTES -->