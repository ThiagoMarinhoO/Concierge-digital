<?php

$question = new Question();

$configQuestions = $question->getQuestionsByCategory('Configurações');
$comportamentoQuestions = $question->getQuestionsByCategory('Comportamento');
$perguntasQuestions = $question->getQuestionsByCategory('Perguntas');

$comportamentoPromptQuestions = [];
$comportamentoOtherQuestions = [];

foreach ($comportamentoQuestions as $question) {
	if ($question['title'] === 'Escreva aqui seu prompt:') {
		$comportamentoPromptQuestions[] = $question;
	} else {
		$comportamentoOtherQuestions[] = $question;
	}
}
?>

<div class="bg-gray-100 relative min-h-screen">
	<div class="relative container mx-auto p-4">
		<div x-data="{ activeTab: '' }" class="grid grid-cols-1 md:grid-cols-3 gap-4">
			<!-- Botões das abas -->
			<button @click="activeTab = 'Configurações'" :class="{ 'border-gray-800': activeTab === 'Configurações' }"
				class="tab-btn rounded-md cursor-pointer p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent hover:border-gray-800 focus:outline-none">
				Configurações
			</button>
			<button @click="activeTab = 'Comportamento'" :class="{ 'border-gray-800': activeTab === 'Comportamento' }"
				class="tab-btn rounded-md cursor-pointer p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent hover:border-gray-800 focus:outline-none">
				Comportamento
			</button>
			<button @click="activeTab = 'Base de Conhecimento'" :class="{ 'border-gray-800': activeTab === 'Base de Conhecimento' }"
				class="tab-btn rounded-md cursor-pointer p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent hover:border-gray-800 focus:outline-none">
				Base de Conhecimento
			</button>
			<button @click="activeTab = 'Perguntas'" :class="{ 'border-gray-800': activeTab === 'Perguntas' }"
				class="tab-btn rounded-md cursor-pointer p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent hover:border-gray-800 focus:outline-none">
				Perguntas
			</button>
			<button @click="activeTab = 'Integrações'" :class="{ 'border-gray-800': activeTab === 'Integrações' }"
				class="tab-btn rounded-md cursor-pointer p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent hover:border-gray-800 focus:outline-none">
				Integrações
			</button>
			<button @click="activeTab = 'Aparência'" :class="{ 'border-gray-800': activeTab === 'Aparência' }"
				class="tab-btn rounded-md cursor-pointer p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent hover:border-gray-800 focus:outline-none">
				Aparência
			</button>
			<button @click="activeTab = 'Teste'" :class="{ 'border-gray-800': activeTab === 'Teste' }"
				class="tab-btn rounded-md cursor-pointer p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent hover:border-gray-800 focus:outline-none">
				Teste
			</button>
			<button @click="activeTab = 'Download'" :class="{ 'border-gray-800': activeTab === 'Download' }"
				class="tab-btn rounded-md cursor-pointer p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent hover:border-gray-800 focus:outline-none">
				Download
			</button>

			<!-- Conteúdo das tabs -->
			<div class="tab-content mt-4">
				<div x-show="activeTab === 'Configurações'" class="tab-pane absolute inset-0 bg-gray-100 p-4">
					<div class="flex items-center gap-3">
						<button @click="activeTab = ''" class="px-4 py-2.5 bg-yellow-400 rounded-full">Voltar</button>
						<h2 class="text-xl font-bold mb-2">Configurações</h2>
					</div>
					<div class="input-container mb-4">
						<div class="question-block">
							<label for="chatbot_name" class="block font-medium text-gray-700 mb-2">
								Nome do Assistente
							</label>
							<input type="text" name="chatbot_name" placeholder="Qual o nome do chatbot ?"
								class="py-2 px-2.5 border border-gray-100 rounded-lg w-full" required>
						</div>
					</div>
					<?php if (!empty($configQuestions)): ?>
						<?php foreach ($configQuestions as $index => $question): ?>
							<div class="question-block">
								<label for="question-<?php echo esc_attr($index); ?>" data-question-base="<?php echo esc_attr($question['training_phrase']); ?>">
									<?php echo esc_html($question['title']); ?>
								</label>
								<?php
								$options = json_decode($question['options'], true);
								$field_type = $question['field_type']; // Verifica o tipo de campo
								?>
								<?php if ($field_type === 'selection' && !empty($options) && is_array($options)): ?>
									<!-- Campo do tipo seleção -->
									<select class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2" id="question-<?php echo esc_attr($index); ?>" name="question_<?php echo esc_attr($question['id']); ?>">
										<?php foreach ($options as $option): ?>
											<option value="<?php echo esc_attr($option); ?>">
												<?php echo esc_html($option); ?>
											</option>
										<?php endforeach; ?>
									</select>
								<?php elseif ($field_type === 'file'): ?>
									<!-- Campo do tipo arquivo -->
									<input
										type="file"
										id="question-<?php echo esc_attr($index); ?>"
										name="question_<?php echo esc_attr($question['id']); ?>"
										class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2">
								<?php else: ?>
									<!-- Campo do tipo texto (padrão) -->
									<input
										type="text"
										id="question-<?php echo esc_attr($index); ?>"
										name="question_<?php echo esc_attr($question['id']); ?>"
										class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2"
										placeholder="<?php echo esc_attr($question['training_phrase']); ?>">
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					<?php else: ?>
						<p>Nenhuma pergunta cadastrada no momento.</p>
					<?php endif; ?>
					<div class="flex justify-center mt-10">
						<button class="saveConfigButton px-4 py-2.5 bg-green-400 rounded-full">Salvar</button>
					</div>
				</div>

				<div x-show="activeTab === 'Comportamento'" class="tab-pane absolute inset-0 bg-gray-100 p-4">
					<div class="flex items-center gap-3">
						<button @click="activeTab = ''" class="px-4 py-2.5 bg-yellow-400 rounded-full">Voltar</button>
						<h2 class="text-xl font-bold mb-2">Comportamento</h2>
					</div>
					<div class="input-container mb-4">
						<div x-data="{ tab: 'rapida' }">
							<button :class="{ 'active': tab === 'rapida' }" @click="tab = 'rapida'" class="px-4 py-2 rounded-md">Rápida</button>
							<button :class="{ 'active': tab === 'personalizada' }" @click="tab = 'personalizada'" class="px-4 py-2 rounded-md">Personalizada</button>

							<div x-show="tab === 'rapida'" class="mt-4">
								<?php if (!empty($comportamentoOtherQuestions)): ?>
									<?php foreach ($comportamentoOtherQuestions as $index => $question): ?>
										<div class="question-block">
											<label for="question-<?php echo esc_attr($index); ?>" data-question-base="<?php echo esc_attr($question['training_phrase']); ?>">
												<?php echo esc_html($question['title']); ?>
											</label>
											<?php
											$options = json_decode($question['options'], true);
											$field_type = $question['field_type']; // Verifica o tipo de campo
											?>
											<?php if ($field_type === 'selection' && !empty($options) && is_array($options)): ?>
												<!-- Campo do tipo seleção -->
												<select class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2" id="question-<?php echo esc_attr($index); ?>" name="question_<?php echo esc_attr($question['id']); ?>">
													<?php foreach ($options as $option): ?>
														<option value="<?php echo esc_attr($option); ?>">
															<?php echo esc_html($option); ?>
														</option>
													<?php endforeach; ?>
												</select>
											<?php elseif ($field_type === 'file'): ?>
												<!-- Campo do tipo arquivo -->
												<input
													type="file"
													id="question-<?php echo esc_attr($index); ?>"
													name="question_<?php echo esc_attr($question['id']); ?>"
													class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2">
											<?php else: ?>
												<!-- Campo do tipo texto (padrão) -->
												<input
													type="text"
													id="question-<?php echo esc_attr($index); ?>"
													name="question_<?php echo esc_attr($question['id']); ?>"
													class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2"
													placeholder="<?php echo esc_attr($question['training_phrase']); ?>">
											<?php endif; ?>
										</div>
									<?php endforeach; ?>
								<?php else: ?>
									<p>Nenhuma pergunta cadastrada no momento.</p>
								<?php endif; ?>
							</div>

							<div x-show="tab === 'personalizada'" class="mt-4">
								<?php if (!empty($comportamentoPromptQuestions)): ?>
									<?php foreach ($comportamentoPromptQuestions as $index => $question): ?>
										<div class="question-block">
											<label for="question-<?php echo esc_attr($index); ?>" data-question-base="<?php echo esc_attr($question['training_phrase']); ?>">
												<?php echo esc_html($question['title']); ?>
											</label>
											<?php
											$options = json_decode($question['options'], true);
											$field_type = $question['field_type']; // Verifica o tipo de campo
											?>
											<?php if ($field_type === 'selection' && !empty($options) && is_array($options)): ?>
												<!-- Campo do tipo seleção -->
												<select class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2" id="question-<?php echo esc_attr($index); ?>" name="question_<?php echo esc_attr($question['id']); ?>">
													<?php foreach ($options as $option): ?>
														<option value="<?php echo esc_attr($option); ?>">
															<?php echo esc_html($option); ?>
														</option>
													<?php endforeach; ?>
												</select>
											<?php elseif ($field_type === 'file'): ?>
												<!-- Campo do tipo arquivo -->
												<input
													type="file"
													id="question-<?php echo esc_attr($index); ?>"
													name="question_<?php echo esc_attr($question['id']); ?>"
													class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2">
											<?php else: ?>
												<!-- Campo do tipo texto (padrão) -->
												<input
													type="text"
													id="question-<?php echo esc_attr($index); ?>"
													name="question_<?php echo esc_attr($question['id']); ?>"
													class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2"
													placeholder="<?php echo esc_attr($question['training_phrase']); ?>">
											<?php endif; ?>
										</div>
									<?php endforeach; ?>
								<?php else: ?>
									<p>Nenhuma pergunta cadastrada no momento.</p>
								<?php endif; ?>
							</div>
						</div>
						<div class="flex justify-center mt-10">
							<button class="saveComportamentoButton px-4 py-2.5 bg-green-400 rounded-full">Salvar</button>
						</div>
					</div>
				</div>

				<div x-show="activeTab === 'Base de Conhecimento'" class="tab-pane absolute inset-0 bg-gray-100 p-4">
					<div class="flex items-center gap-3">
						<button @click="activeTab = ''" class="px-4 py-2.5 bg-yellow-400 rounded-full">Voltar</button>
						<h2 class="text-xl font-bold mb-2">Base de Conhecimento</h2>
					</div>
				</div>

				<div x-show="activeTab === 'Perguntas'" class="tab-pane absolute inset-0 bg-gray-100 p-4">
					<div class="flex items-center gap-3">
						<button @click="activeTab = ''" class="px-4 py-2.5 bg-yellow-400 rounded-full">Voltar</button>
						<h2 class="text-xl font-bold mb-2">Perguntas</h2>
					</div>
					<?php if (!empty($perguntasQuestions)): ?>
						<?php foreach ($perguntasQuestions as $index => $question): ?>
							<div class="question-block">
								<label for="question-<?php echo esc_attr($index); ?>" data-question-base="<?php echo esc_attr($question['training_phrase']); ?>">
									<?php echo esc_html($question['title']); ?>
								</label>
								<?php
								$options = json_decode($question['options'], true);
								$field_type = $question['field_type']; // Verifica o tipo de campo
								?>
								<?php if ($field_type === 'selection' && !empty($options) && is_array($options)): ?>
									<!-- Campo do tipo seleção -->
									<select class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2" id="question-<?php echo esc_attr($index); ?>" name="question_<?php echo esc_attr($question['id']); ?>">
										<?php foreach ($options as $option): ?>
											<option value="<?php echo esc_attr($option); ?>">
												<?php echo esc_html($option); ?>
											</option>
										<?php endforeach; ?>
									</select>
								<?php elseif ($field_type === 'file'): ?>
									<!-- Campo do tipo arquivo -->
									<input
										type="file"
										id="question-<?php echo esc_attr($index); ?>"
										name="question_<?php echo esc_attr($question['id']); ?>"
										class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2">
								<?php else: ?>
									<!-- Campo do tipo texto (padrão) -->
									<input
										type="text"
										id="question-<?php echo esc_attr($index); ?>"
										name="question_<?php echo esc_attr($question['id']); ?>"
										class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2"
										placeholder="<?php echo esc_attr($question['training_phrase']); ?>">
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					<?php else: ?>
						<p>Nenhuma pergunta cadastrada no momento.</p>
					<?php endif; ?>
					<div class="flex justify-center mt-10">
						<button class="savePerguntasButton px-4 py-2.5 bg-green-400 rounded-full">Salvar</button>
					</div>
				</div>

				<div x-show="activeTab === 'Integrações'" class="tab-pane absolute inset-0 bg-gray-100 p-4">
					<div class="flex items-center gap-3">
						<button @click="activeTab = ''" class="px-4 py-2.5 bg-yellow-400 rounded-full">Voltar</button>
						<h2 class="text-xl font-bold mb-2">Integrações</h2>
					</div>
				</div>

				<div x-show="activeTab === 'Aparência'" class="tab-pane absolute inset-0 bg-gray-100 p-4">
					<div class="flex items-center gap-3">
						<button @click="activeTab = ''" class="px-4 py-2.5 bg-yellow-400 rounded-full">Voltar</button>
						<h2 class="text-xl font-bold mb-2">Aparência</h2>
					</div>
				</div>

				<div x-show="activeTab === 'Teste'" class="tab-pane absolute inset-0 bg-gray-100 p-4">
					<div class="flex items-center gap-3">
						<button @click="activeTab = ''" class="px-4 py-2.5 bg-yellow-400 rounded-full">Voltar</button>
						<h2 class="text-xl font-bold mb-2">Teste</h2>
					</div>
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
									<div class="flex w-full mt-2 space-x-3 max-w-xs">
										<div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300"></div>
										<div>
											<div class="bg-gray-300 p-3 rounded-r-lg rounded-bl-lg">
												<p class="text-sm">Olá! Como posso ajudar?</p>
											</div>
										</div>
									</div>
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

							<div class="flex justify-center gap-10">
								<form action="" method="POST" id="deleteChatbotForm">
									<button type="submit" name="delete_chatbot" class="bg-red-600 text-white p-2 mt-4 rounded">Resetar Chatbot</button>
								</form>
								<form action="" method="" id="">
									<button type="submit" name="" class="bg-green-600 text-white p-2 mt-4 rounded">Gerar link</button>
								</form>
							</div>
						</div>

						<script>
							window.addEventListener('DOMContentLoaded', function() {
								const inputField = document.querySelector('.mensagem');
								const sendButton = document.querySelector('#enviarMensagem');


								// Evento de clique no botão
								sendButton.addEventListener('click', function(event) {
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

								// Enviar mensagem ao pressionar Enter
								inputField.addEventListener('keydown', function(event) {
									if (event.key === 'Enter') {
										event.preventDefault();
										sendButton.click();
									}
								});

								// Seleciona o select e o container do chat
								const chatbotSelector = document.getElementById('chatbot-selector');
								const chatContainer = document.querySelector('.chatContainer');

								// Adiciona um evento de mudança ao select
								chatbotSelector.addEventListener('change', function() {
									const selectedChatbotId = chatbotSelector.value; // Obtém o ID selecionado
									chatContainer.setAttribute('data-chatbot-id', selectedChatbotId); // Atualiza o atributo data-chatbot-id
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
						</script>
					<?php else: ?>
						<button class="generateChatbot px-4 py-2.5 bg-green-400">Gerar chatbot</button>
					<?php endif; ?>
				</div>
				<div x-show="activeTab === 'Download'" class="tab-pane absolute inset-0 bg-gray-100 p-4">
					<div class="flex items-center gap-3">
						<button @click="activeTab = ''" class="px-4 py-2.5 bg-yellow-400 rounded-full">Voltar</button>
						<h2 class="text-xl font-bold mb-2">Download</h2>
					</div>
				</div>
			</div>
		</div>

	</div>
</div>

<script>
	document.addEventListener("DOMContentLoaded", () => {
		const saveConfigButton = document.querySelector("button.saveConfigButton");
		saveConfigButton.addEventListener("click", (event) => {
			const activeTab = document.querySelector("[x-show]"); // Aba visível no momento
			const chatbotOptions = []; // Array para armazenar as opções

			// Verifique se a aba ativa foi encontrada
			if (!activeTab) {
				console.error("Aba ativa não encontrada");
				return;
			}

			// Itera pelos blocos de perguntas dentro da aba ativa
			activeTab.querySelectorAll(".question-block").forEach((questionBlock) => {
				const inputElement = questionBlock.querySelector("input, select");
				if (inputElement) {
					const perguntaLabel = questionBlock.querySelector("label").innerText.trim();
					const resposta = inputElement.value.trim();
					const trainingPhrase = questionBlock.querySelector("label").dataset.questionBase;
					console.log(trainingPhrase);

					chatbotOptions.push({
						pergunta: perguntaLabel,
						field_name: inputElement.name,
						resposta: resposta,
						training_phrase: trainingPhrase
					});
				}
			});

			// Verifique se existe um h2 na aba ativa antes de tentar acessar o nome da categoria
			const categoryNameElement = activeTab.querySelector("h2");
			if (!categoryNameElement) {
				console.error("Não foi possível encontrar o título da categoria na aba ativa");
				return;
			}
			const categoryName = categoryNameElement.innerText; // Nome da categoria

			// Salva o chatbotOptions no localStorage
			const savedData = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};
			savedData[categoryName] = chatbotOptions;
			localStorage.setItem("chatbotRespostas", JSON.stringify(savedData));

			// Feedback ao usuário
			alert(`Respostas salvas para a categoria: ${categoryName}`);
			console.log(`Respostas salvas para ${categoryName}:`, chatbotOptions);
		});

		const saveComportamentoButton = document.querySelector("button.saveComportamentoButton");

		saveComportamentoButton.addEventListener("click", (event) => {
			// Seleciona a aba ativa com base no estado da variável "tab"
			const activeTab = saveComportamentoButton.closest(".tab-pane");

			const chatbotOptions = []; // Array para armazenar as opções

			// Verifique se a aba ativa foi encontrada
			if (!activeTab) {
				console.error("Aba ativa não encontrada");
				return;
			}

			// Encontre a variável 'tab' que determina qual aba está ativa ("rapida" ou "personalizada")
			const activeTabContent = activeTab.querySelector("button.active").innerHTML.trim();
			// console.log(activeTabContent);

			// Agora, selecione a aba ativa específica ("rapida" ou "personalizada")
			let tabToSearch;
			if (activeTabContent === 'Rápida') {
				tabToSearch = activeTab.querySelector('[x-show="tab === \'rapida\'"]');
			} else if (activeTabContent === 'Personalizada') {
				tabToSearch = activeTab.querySelector('[x-show="tab === \'personalizada\'"]');
			}

			// Verifique se a aba correta foi encontrada
			if (!tabToSearch) {
				console.error("Aba específica não encontrada");
				return;
			}

			// Itera pelos blocos de perguntas dentro da aba ativa
			tabToSearch.querySelectorAll(".question-block").forEach((questionBlock) => {
				const inputElement = questionBlock.querySelector("input, select");
				if (inputElement) {
					const perguntaLabel = questionBlock.querySelector("label").innerText.trim();
					const resposta = inputElement.value.trim();
					const trainingPhrase = questionBlock.querySelector("label").dataset.questionBase;

					chatbotOptions.push({
						pergunta: perguntaLabel,
						field_name: inputElement.name,
						resposta: resposta,
						training_phrase: trainingPhrase,
					});
				}
			});

			// Verifique se existe um h2 na aba ativa antes de tentar acessar o nome da categoria
			const categoryNameElement = activeTab.querySelector("h2");
			if (!categoryNameElement) {
				console.error("Não foi possível encontrar o título da categoria na aba ativa");
				return;
			}
			const categoryName = categoryNameElement.innerText; // Nome da categoria

			// Salva o chatbotOptions no localStorage
			const savedData = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};
			savedData[categoryName] = chatbotOptions;
			localStorage.setItem("chatbotRespostas", JSON.stringify(savedData));

			// Feedback ao usuário
			alert(`Respostas salvas para a categoria: ${categoryName}`);
			console.log(`Respostas salvas para ${categoryName}:`);

		});


		// const saveComportamentoButton = document.querySelector("button.saveComportamentoButton");
		// saveComportamentoButton.addEventListener("click", (event) => {
		// 	const activeTab = saveComportamentoButton.closest(".tab-pane"); // Aba visível no momento


		// 	const chatbotOptions = []; // Array para armazenar as opções

		// 	// Verifique se a aba ativa foi encontrada
		// 	if (!activeTab) {
		// 		console.error("Aba ativa não encontrada");
		// 		return;
		// 	}

		// 	const tabType = activeTab.querySelector("button.active");
		// 	console.log(tabType);

		// 	// Itera pelos blocos de perguntas dentro da aba ativa
		// 	activeTab.querySelectorAll(`.question-block`).forEach((questionBlock) => {
		// 		const inputElement = questionBlock.querySelector("input, select");
		// 		if (inputElement) {
		// 			const perguntaLabel = questionBlock.querySelector("label").innerText.trim();
		// 			const resposta = inputElement.value.trim();
		// 			const trainingPhrase = questionBlock.querySelector("label").dataset.questionBase;

		// 			chatbotOptions.push({
		// 				pergunta: perguntaLabel,
		// 				field_name: inputElement.name,
		// 				resposta: resposta,
		// 				training_phrase: trainingPhrase,
		// 			});
		// 		}
		// 	});

		// 	// Verifique se existe um h2 na aba ativa antes de tentar acessar o nome da categoria
		// 	const categoryNameElement = activeTab.querySelector("h2");
		// 	if (!categoryNameElement) {
		// 		console.error("Não foi possível encontrar o título da categoria na aba ativa");
		// 		return;
		// 	}
		// 	const categoryName = categoryNameElement.innerText; // Nome da categoria

		// 	// Salva o chatbotOptions no localStorage
		// 	const savedData = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};
		// 	savedData[categoryName] = chatbotOptions.filter(option => option.tab_type === tabType); // Filtra pelo tipo de tab
		// 	localStorage.setItem("chatbotRespostas", JSON.stringify(savedData));

		// 	// Feedback ao usuário
		// 	alert(`Respostas salvas para a categoria: ${categoryName}`);
		// 	console.log(`Respostas salvas para ${categoryName}:`, chatbotOptions.filter(option => option.tab_type === tabType));
		// });

		const savePerguntasButton = document.querySelector("button.savePerguntasButton");
		savePerguntasButton.addEventListener("click", (event) => {
			// const activeTab = document.querySelector("[x-show]"); // Aba visível no momento
			const activeTab = savePerguntasButton.closest(".tab-pane"); // Aba visível no momento

			const chatbotOptions = []; // Array para armazenar as opções

			// Verifique se a aba ativa foi encontrada
			if (!activeTab) {
				console.error("Aba ativa não encontrada");
				return;
			}

			// Itera pelos blocos de perguntas dentro da aba ativa
			activeTab.querySelectorAll(".question-block").forEach((questionBlock) => {
				const inputElement = questionBlock.querySelector("input, select");
				if (inputElement) {
					const perguntaLabel = questionBlock.querySelector("label").innerText.trim();
					const resposta = inputElement.value.trim();
					const trainingPhrase = questionBlock.querySelector("label").dataset.questionBase;
					console.log(trainingPhrase);

					chatbotOptions.push({
						pergunta: perguntaLabel,
						field_name: inputElement.name,
						resposta: resposta,
						training_phrase: trainingPhrase
					});
				}
			});

			// Verifique se existe um h2 na aba ativa antes de tentar acessar o nome da categoria
			const categoryNameElement = activeTab.querySelector("h2");
			if (!categoryNameElement) {
				console.error("Não foi possível encontrar o título da categoria na aba ativa");
				return;
			}
			const categoryName = categoryNameElement.innerText; // Nome da categoria

			// Salva o chatbotOptions no localStorage
			const savedData = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};
			savedData[categoryName] = chatbotOptions;
			localStorage.setItem("chatbotRespostas", JSON.stringify(savedData));

			// Feedback ao usuário
			alert(`Respostas salvas para a categoria: ${categoryName}`);
			console.log(`Respostas salvas para ${categoryName}:`, chatbotOptions);
		});
	});

	// document.addEventListener("DOMContentLoaded", () => {

	// 	const savedData = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};

	// 	Object.keys(savedData).forEach(category => {
	// 		const questions = savedData[category];
	// 		questions.forEach(question => {
	// 			const inputElement = document.querySelector(`[name="${question.field_name}"]`);
	// 			if (inputElement) {
	// 				const categoryElement = inputElement.closest(".tab-pane");
	// 				const categoryNameElement = categoryElement.querySelector("h2");
	// 				if (categoryNameElement && categoryNameElement.innerText === category) {
	// 					if (inputElement.tagName === 'select') {
	// 						inputElement.value = question.resposta;
	// 					} else {
	// 						inputElement.value = question.resposta;
	// 					}
	// 				}
	// 			}
	// 		});
	// 	});
	// });

	document.addEventListener("DOMContentLoaded", () => {
		const generateChatbotButton = document.querySelector(".generateChatbot");

		generateChatbotButton.addEventListener("click", (event) => {
			event.preventDefault();

			// const chatbotName = form.elements['chatbot_name'].value.trim();

			const localChatbotOptions = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};

			const chatbotOptions = Object.values(localChatbotOptions).reduce((acc, val) => acc.concat(val), []);			

			const chatbotName = localChatbotOptions["Configurações"][0].resposta;

			// Remove a pergunta que contém a resposta igual a variável chatbotName
			const filteredChatbotOptions = chatbotOptions.filter(option => option.resposta !== chatbotName);

			fetch(conciergeAjax.ajax_url, {
					method: "POST",
					body: new URLSearchParams({
						action: "create_chatbot",
						chatbot_name: chatbotName,
						chatbot_options: JSON.stringify(chatbotOptions),
					}),
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

	// document.addEventListener("DOMContentLoaded", () => {
	// 	const chatbotOptions = JSON.parse(localStorage.getItem("chatbotOptions")) || {};
	// 	const tabs = Array.from(document.querySelectorAll(".tab-pane"));
	// 	const buttons = Array.from(document.querySelectorAll(".tab-btn"));

	// 	function saveCurrentTab(tabName) {
	// 		const selectedTab = document.querySelector(`[data-tab-name="${tabName}"]`);
	// 		const inputs = selectedTab.querySelectorAll("input, select");
	// 		const tabData = {};

	// 		inputs.forEach((input) => {
	// 			const label = document.querySelector(`label[for="${input.id}"]`);
	// 			const pergunta = label ? label.innerText.trim() : `Pergunta ${input.id}`;
	// 			const resposta = input.value.trim();
	// 			tabData[pergunta] = resposta || "Não respondido";
	// 		});

	// 		chatbotOptions[tabName] = tabData;
	// 		localStorage.setItem("chatbotOptions", JSON.stringify(chatbotOptions));
	// 	}

	// 	function isTabComplete(tabName) {
	// 		const tabData = chatbotOptions[tabName];
	// 		return tabData && Object.values(tabData).every((resposta) => resposta !== "Não respondido" && resposta !== "");
	// 	}

	// 	function updateTabButtons() {
	// 		buttons.forEach((button, index) => {
	// 			const tabName = button.innerText.trim();

	// 			if (index === 0 || isTabComplete(buttons[index - 1].innerText.trim())) {
	// 				button.classList.remove("disabled");
	// 				button.disabled = false;
	// 			} else {
	// 				button.classList.add("disabled");
	// 				button.disabled = true;
	// 			}
	// 		});
	// 	}

	// 	function showTab(tabName) {
	// 		tabs.forEach((tab) => tab.classList.add("hidden"));
	// 		buttons.forEach((button) => button.classList.remove("active"));

	// 		const selectedTab = document.querySelector(`[data-tab-name="${tabName}"]`);
	// 		const selectedButton = buttons.find((button) => button.innerText.trim() === tabName);

	// 		if (selectedTab) selectedTab.classList.remove("hidden");
	// 		if (selectedButton) selectedButton.classList.add("active");
	// 	}

	// 	function handleTabClick(tabName) {
	// 		const currentTabIndex = buttons.findIndex((button) =>
	// 			button.classList.contains("active")
	// 		);

	// 		const clickedTabIndex = buttons.findIndex((button) => button.innerText.trim() === tabName);

	// 		if (clickedTabIndex > currentTabIndex) {
	// 			const currentTabName = buttons[currentTabIndex].innerText.trim();
	// 			saveCurrentTab(currentTabName);

	// 			if (!isTabComplete(currentTabName)) {
	// 				alert("Conclua todas as perguntas antes de avançar para a próxima aba.");
	// 				return;
	// 			}
	// 		}

	// 		showTab(tabName);
	// 		updateTabButtons(); // Atualiza as abas clicáveis
	// 	}

	// 	buttons.forEach((button) => {
	// 		button.addEventListener("click", () => {
	// 			const tabName = button.innerText.trim();
	// 			if (!button.classList.contains("disabled")) {
	// 				handleTabClick(tabName);
	// 			}
	// 		});
	// 	});

	// 	document.querySelectorAll(".tab-pane").forEach((tab) => {
	// 		const saveButton = tab.querySelector(".px-4.bg-green-400");
	// 		if (saveButton) {
	// 			saveButton.addEventListener("click", () => {
	// 				const tabName = tab.getAttribute("data-tab-name");
	// 				saveCurrentTab(tabName);
	// 				alert(`Dados da aba "${tabName}" salvos com sucesso!`);
	// 				updateTabButtons(); // Revalida as abas clicáveis
	// 			});
	// 		}
	// 	});

	// 	// Exibe a primeira aba ao carregar a página
	// 	// showTab(buttons[0].innerText.trim());
	// 	updateTabButtons(); // Atualiza os botões para inicializar a lógica
	// });
</script>