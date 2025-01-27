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
<div id="tabs-container" class="grid grid-cols-1 md:grid-cols-3 gap-4 relative">
	<!-- Botões das abas -->
	<button data-tab="Configurações" data-locked="false" class="tab-btn rounded-md cursor-pointer p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent hover:border-gray-800 focus:outline-none">
		Configurações
	</button>
	<button data-tab="Comportamento" data-locked="true" class="tab-btn rounded-md cursor-not-allowed p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent opacity-50">
		Comportamento
	</button>
	<button data-tab="Basedeconhecimento" data-locked="true" class="tab-btn rounded-md cursor-not-allowed p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent opacity-50">
		Base de Conhecimento
	</button>
	<button data-tab="Perguntas" data-locked="true" class="tab-btn rounded-md cursor-not-allowed p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent opacity-50">
		Perguntas
	</button>
	<button data-tab="Integrações" data-locked="true" class="tab-btn rounded-md cursor-not-allowed p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent opacity-50">
		Integrações
	</button>
	<button data-tab="Aparência" data-locked="true" class="tab-btn rounded-md cursor-not-allowed p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent opacity-50">
		Aparência
	</button>
	<button data-tab="Teste" data-locked="true" class="tab-btn rounded-md cursor-not-allowed p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent opacity-50">
		Teste
	</button>
	<button data-tab="Download" data-locked="true" class="tab-btn rounded-md cursor-not-allowed p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent opacity-50">
		Download
	</button>
</div>

<div id="tabs-content-container" class="">
	<div id="Configurações-content" class="tab-content hidden absolute inset-0 bg-white p-4">
		<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
		<p>Conteúdo da aba Configurações</p>
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
		<!-- <button id="validate-configuracoes" class="mt-4 bg-blue-500 text-white py-2 px-4 rounded">Validar</button> -->
	</div>
	<div id="Comportamento-content" class="tab-content hidden absolute inset-0 bg-white p-4">
		<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
		<p>Conteúdo da aba Comportamento</p>
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
	<div id="Basedeconhecimento-content" class="tab-content hidden absolute inset-0 bg-white p-4">
		<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
		<p>Conteúdo da aba Base de conhecimento</p>
		<button class="saveknowledgeButton px-4 py-2.5 bg-green-400 rounded-full">Salvar</button>
	</div>
	<div id="Perguntas-content" class="tab-content hidden absolute inset-0 bg-white p-4">
		<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
		<p>Conteúdo da aba Perguntas</p>
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
			<button class="saveQuestionsButton px-4 py-2.5 bg-green-400 rounded-full">Salvar</button>
		</div>
	</div>
	<div id="Integrações-content" class="tab-content hidden absolute inset-0 bg-white p-4">
		<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
		<p>Conteúdo da aba Integrações</p>
		<button class="saveIntegracaoButton px-4 py-2.5 bg-green-400 rounded-full">Salvar</button>
	</div>
	<div id="Aparência-content" class="tab-content hidden absolute inset-0 bg-white p-4">
		<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
		<p>Conteúdo da aba Aparência</p>
		<button class="saveAparenciaButton px-4 py-2.5 bg-green-400 rounded-full">Salvar</button>
	</div>
	<div id="Teste-content" class="tab-content hidden absolute inset-0 bg-white p-4">
		<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
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
	<div id="Download-content" class="tab-content hidden absolute inset-0 bg-white p-4">
		<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
		<p>Conteúdo da aba Download</p>
	</div>
</div>

<script>
	document.addEventListener("DOMContentLoaded", () => {

		const container = document.getElementById("tabs-container");
		const buttons = container.querySelectorAll(".tab-btn");
		const contentContainer = document.getElementById("tabs-content-container");
		const contentDivs = contentContainer.querySelectorAll(".tab-content");
		const currentTab = document.querySelector(".tab-content:not(.hidden)");

		let currentTabIndex = 0;

		function showTabContent(tabName) {
			contentDivs.forEach(contentDiv => contentDiv.classList.add("hidden"));
			const activeContentDiv = document.getElementById(`${tabName}-content`);
			if (activeContentDiv) {
				activeContentDiv.classList.remove("hidden");
			}
		}

		function hideAllTabs() {
			contentDivs.forEach(contentDiv => contentDiv.classList.add("hidden"));
		}

		function validateCurrentTab(isComplex) {
			const activeContent = document.querySelector(".tab-content:not(.hidden)");
			if (!activeContent) return false;

			if (isComplex) {
				const activeTabContent = activeContent.querySelector("button.active").innerHTML.trim();

				let tabToSearch;
				if (activeTabContent === 'Rápida') {
					tabToSearch = activeContent.querySelector('[x-show="tab === \'rapida\'"]');
				} else if (activeTabContent === 'Personalizada') {
					tabToSearch = activeContent.querySelector('[x-show="tab === \'personalizada\'"]');
				}

				const inputs = tabToSearch.querySelectorAll("input, select");

				let isValid = true;

				inputs.forEach(input => {
					if (!input.value.trim()) {
						isValid = false;
					}
				});

				return isValid;
			}

			const inputs = activeContent.querySelectorAll("input, select");
			let isValid = true;

			// Verificar se todos os campos estão preenchidos
			inputs.forEach(input => {
				if (!input.value.trim()) {
					isValid = false;
				}
			});

			return isValid;
		}

		function unlockNextTab() {
			if (currentTabIndex < buttons.length - 1) {
				const nextTabButton = buttons[currentTabIndex + 1];
				nextTabButton.dataset.locked = "false"; // Desbloquear
				nextTabButton.classList.remove("opacity-50", "cursor-not-allowed");
			}
		}

		function saveConfigurations() {
			const activeContent = document.querySelector(".tab-content:not(.hidden)"); // Aba visível
			const chatbotOptions = [];

			// Verificar se a aba visível foi encontrada
			if (!activeContent) {
				console.error("Aba ativa não encontrada");
				return;
			}

			// Iterar pelos blocos de perguntas
			activeContent.querySelectorAll(".question-block").forEach((questionBlock) => {
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

			// Obter o nome da categoria
			const categoryNameElement = activeContent.querySelector("h2") || {
				innerText: activeContent.id.replace("-content", "")
			};
			const categoryName = categoryNameElement.innerText.trim();

			// Salvar no localStorage
			const savedData = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};
			savedData[categoryName] = chatbotOptions;
			localStorage.setItem("chatbotRespostas", JSON.stringify(savedData));

			// Feedback ao usuário
			alert(`Respostas salvas para a categoria: ${categoryName}`);
			console.log(`Respostas salvas para ${categoryName}:`, chatbotOptions);

			// Desbloquear a próxima aba
			unlockNextTab();
		}

		function saveBehavior() {
			const activeContent = document.querySelector(".tab-content:not(.hidden)"); // Aba visível
			const chatbotOptions = [];

			// Verificar se a aba visível foi encontrada
			if (!activeContent) {
				console.error("Aba ativa não encontrada");
				return;
			}

			const activeTabContent = activeContent.querySelector("button.active").innerHTML.trim();

			let tabToSearch;
			if (activeTabContent === 'Rápida') {
				tabToSearch = activeContent.querySelector('[x-show="tab === \'rapida\'"]');
			} else if (activeTabContent === 'Personalizada') {
				tabToSearch = activeContent.querySelector('[x-show="tab === \'personalizada\'"]');
			}

			// Iterar pelos blocos de perguntas
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

			// // Obter o nome da categoria
			const categoryNameElement = activeContent.querySelector("h2") || {
				innerText: activeContent.id.replace("-content", "")
			};
			const categoryName = categoryNameElement.innerText.trim();

			// // Salvar no localStorage
			const savedData = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};
			savedData[categoryName] = chatbotOptions;
			localStorage.setItem("chatbotRespostas", JSON.stringify(savedData));

			// // Feedback ao usuário
			alert(`Respostas salvas para a categoria: ${categoryName}`);
			console.log(`Respostas salvas para ${categoryName}:`, chatbotOptions);

			// // Desbloquear a próxima aba
			unlockNextTab();
		}

		function saveKnowledge() {
			const activeContent = document.querySelector(".tab-content:not(.hidden)"); // Aba visível
			const chatbotOptions = [];

			// // Verificar se a aba visível foi encontrada
			if (!activeContent) {
				console.error("Aba ativa não encontrada");
				return;
			}

			// // Iterar pelos blocos de perguntas
			// activeContent.querySelectorAll(".question-block").forEach((questionBlock) => {
			// 	const inputElement = questionBlock.querySelector("input, select");
			// 	if (inputElement) {
			// 		const perguntaLabel = questionBlock.querySelector("label").innerText.trim();
			// 		const resposta = inputElement.value.trim();
			// 		const trainingPhrase = questionBlock.querySelector("label").dataset.questionBase;

			// 		chatbotOptions.push({
			// 			pergunta: perguntaLabel,
			// 			field_name: inputElement.name,
			// 			resposta: resposta,
			// 			training_phrase: trainingPhrase,
			// 		});
			// 	}
			// });

			// Obter o nome da categoria
			const categoryNameElement = activeContent.querySelector("h2") || {
				innerText: activeContent.id.replace("-content", "")
			};
			const categoryName = categoryNameElement.innerText.trim();

			// Salvar no localStorage
			const savedData = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};
			savedData[categoryName] = chatbotOptions;
			localStorage.setItem("chatbotRespostas", JSON.stringify(savedData));

			// // Feedback ao usuário
			alert(`Respostas salvas para a categoria: ${categoryName}`);
			console.log(`Respostas salvas para ${categoryName}:`, chatbotOptions);

			// Desbloquear a próxima aba
			unlockNextTab();
		}

		function saveQuestions() {
			const activeContent = document.querySelector(".tab-content:not(.hidden)"); // Aba visível
			const chatbotOptions = [];

			// Verificar se a aba visível foi encontrada
			if (!activeContent) {
				console.error("Aba ativa não encontrada");
				return;
			}

			// Iterar pelos blocos de perguntas
			activeContent.querySelectorAll(".question-block").forEach((questionBlock) => {
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

			// Obter o nome da categoria
			const categoryNameElement = activeContent.querySelector("h2") || {
				innerText: activeContent.id.replace("-content", "")
			};
			const categoryName = categoryNameElement.innerText.trim();

			// Salvar no localStorage
			const savedData = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};
			savedData[categoryName] = chatbotOptions;
			localStorage.setItem("chatbotRespostas", JSON.stringify(savedData));

			// Feedback ao usuário
			alert(`Respostas salvas para a categoria: ${categoryName}`);
			console.log(`Respostas salvas para ${categoryName}:`, chatbotOptions);

			// Desbloquear a próxima aba
			unlockNextTab();
		}

		function saveIntegrations() {
			const activeContent = document.querySelector(".tab-content:not(.hidden)"); // Aba visível
			const chatbotOptions = [];

			// // Verificar se a aba visível foi encontrada
			if (!activeContent) {
				console.error("Aba ativa não encontrada");
				return;
			}

			// // Iterar pelos blocos de perguntas
			// activeContent.querySelectorAll(".question-block").forEach((questionBlock) => {
			// 	const inputElement = questionBlock.querySelector("input, select");
			// 	if (inputElement) {
			// 		const perguntaLabel = questionBlock.querySelector("label").innerText.trim();
			// 		const resposta = inputElement.value.trim();
			// 		const trainingPhrase = questionBlock.querySelector("label").dataset.questionBase;

			// 		chatbotOptions.push({
			// 			pergunta: perguntaLabel,
			// 			field_name: inputElement.name,
			// 			resposta: resposta,
			// 			training_phrase: trainingPhrase,
			// 		});
			// 	}
			// });

			// Obter o nome da categoria
			const categoryNameElement = activeContent.querySelector("h2") || {
				innerText: activeContent.id.replace("-content", "")
			};
			const categoryName = categoryNameElement.innerText.trim();

			// Salvar no localStorage
			const savedData = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};
			savedData[categoryName] = chatbotOptions;
			localStorage.setItem("chatbotRespostas", JSON.stringify(savedData));

			// // Feedback ao usuário
			alert(`Respostas salvas para a categoria: ${categoryName}`);
			console.log(`Respostas salvas para ${categoryName}:`, chatbotOptions);

			// Desbloquear a próxima aba
			unlockNextTab();
		}

		function saveStyles() {
			const activeContent = document.querySelector(".tab-content:not(.hidden)"); // Aba visível
			const chatbotOptions = [];

			// // Verificar se a aba visível foi encontrada
			if (!activeContent) {
				console.error("Aba ativa não encontrada");
				return;
			}

			// // Iterar pelos blocos de perguntas
			// activeContent.querySelectorAll(".question-block").forEach((questionBlock) => {
			// 	const inputElement = questionBlock.querySelector("input, select");
			// 	if (inputElement) {
			// 		const perguntaLabel = questionBlock.querySelector("label").innerText.trim();
			// 		const resposta = inputElement.value.trim();
			// 		const trainingPhrase = questionBlock.querySelector("label").dataset.questionBase;

			// 		chatbotOptions.push({
			// 			pergunta: perguntaLabel,
			// 			field_name: inputElement.name,
			// 			resposta: resposta,
			// 			training_phrase: trainingPhrase,
			// 		});
			// 	}
			// });

			// Obter o nome da categoria
			const categoryNameElement = activeContent.querySelector("h2") || {
				innerText: activeContent.id.replace("-content", "")
			};
			const categoryName = categoryNameElement.innerText.trim();

			// Salvar no localStorage
			const savedData = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};
			savedData[categoryName] = chatbotOptions;
			localStorage.setItem("chatbotRespostas", JSON.stringify(savedData));

			// // Feedback ao usuário
			alert(`Respostas salvas para a categoria: ${categoryName}`);
			console.log(`Respostas salvas para ${categoryName}:`, chatbotOptions);

			// Desbloquear a próxima aba
			unlockNextTab();
		}

		buttons.forEach((button, index) => {
			if (index > 0) {
				button.dataset.locked = "true"; // Bloquear abas exceto a primeira
				button.classList.add("opacity-50", "cursor-not-allowed");
			}

			button.addEventListener("click", () => {
				if (button.dataset.locked === "true") {
					alert("Complete a aba atual antes de prosseguir.");
					return;
				}

				// Define o botão ativo
				buttons.forEach(btn => btn.classList.remove("border-gray-800"));
				button.classList.add("border-gray-800");

				// Atualiza o índice da aba ativa
				currentTabIndex = index;

				// Exibe o conteúdo da aba ativa
				const tabName = button.getAttribute("data-tab");
				showTabContent(tabName);
			});
		});

		contentDivs.forEach(contentDiv => {
			const backButton = contentDiv.querySelector(".back-btn");
			if (backButton) {
				backButton.addEventListener("click", () => {
					hideAllTabs();
				});
			}
		});

		const saveConfigButton = document.querySelector("button.saveConfigButton");
		if (saveConfigButton) {
			saveConfigButton.addEventListener("click", () => {
				if (validateCurrentTab(false)) {
					saveConfigurations(); // Salvar configurações e desbloquear próxima aba
				} else {
					alert("Preencha todos os campos antes de salvar.");
				}
			});
		}

		const saveComportamentoButton = document.querySelector("button.saveComportamentoButton");
		if (saveComportamentoButton) {
			saveComportamentoButton.addEventListener("click", () => {
				if (validateCurrentTab(true)) {
					saveBehavior();
				} else {
					alert("Preencha todos os campos antes de salvar.");
				}
			});
		}

		const saveknowledgeButton = document.querySelector("button.saveknowledgeButton");
		if (saveknowledgeButton) {
			saveknowledgeButton.addEventListener("click", () => {
				// if (validateCurrentTab(true)) {
				saveKnowledge();
				// } else {
				// 	alert("Preencha todos os campos antes de salvar.");
				// }
			});
		}

		const saveQuestionsButton = document.querySelector("button.saveQuestionsButton");
		if (saveQuestionsButton) {
			saveQuestionsButton.addEventListener("click", () => {
				if (validateCurrentTab(false)) {
					saveQuestions();
				} else {
					alert("Preencha todos os campos antes de salvar.");
				};
			});
		}

		const saveIntegracaoButton = document.querySelector("button.saveIntegracaoButton");
		if (saveIntegracaoButton) {
			saveIntegracaoButton.addEventListener("click", () => {
				// if (validateCurrentTab(false)) {
				saveIntegrations();
				// } else {
				// 	alert("Preencha todos os campos antes de salvar.");
				// };
			});
		}

		const saveAparenciaButton = document.querySelector("button.saveAparenciaButton");
		if (saveAparenciaButton) {
			saveAparenciaButton.addEventListener("click", () => {
				// if (validateCurrentTab(false)) {
				saveStyles();
				// } else {
				// 	alert("Preencha todos os campos antes de salvar.");
				// };
			});
		}

		const storedData = JSON.parse(localStorage.getItem('chatbotRespostas'));
		if (storedData) {
			Object.keys(storedData).forEach((tab, index) => {
				const tabData = storedData[tab];
				const tabContent = document.getElementById(`${tab}-content`);
				if (tabContent) {
					tabData.forEach(item => {
						const field = tabContent.querySelector(`[name="${item.field_name}"]`);
						if (field) {
							if (field.tagName === 'INPUT' || field.tagName === 'TEXTAREA') {
								field.value = item.resposta;
							} else if (field.tagName === 'SELECT') {
								const optionExists = Array.from(field.options).some(option => option.value === item.resposta);
								if (optionExists) {
									field.value = item.resposta;
								} else {
									console.warn(`Valor "${item.resposta}" não encontrado para o campo "${item.field_name}"`);
								}
							}
						}
					});
					if (index < buttons.length - 1) {
						const nextTabButton = buttons[index + 1];
						nextTabButton.dataset.locked = "false";
						nextTabButton.classList.remove("opacity-50", "cursor-not-allowed");
					}
				}
			});
		}

		const generateChatbotButton = document.querySelector(".generateChatbot");
		if (generateChatbotButton) {

			generateChatbotButton.addEventListener("click", (event) => {
				event.preventDefault();

				// const chatbotName = form.elements['chatbot_name'].value.trim();

				const localChatbotOptions = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};

				const chatbotOptions = Object.values(localChatbotOptions).reduce((acc, val) => acc.concat(val), []);

				const chatbotName = localChatbotOptions["Configurações"][0].resposta;

				// Remove a pergunta que contém a resposta igual a variável chatbotName
				const filteredChatbotOptions = chatbotOptions.filter(option => option.resposta !== chatbotName);


				if (confirm('Tem certeza que deseja gerar o chatbot?')) {
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
				}
			});
		}
	});
</script>