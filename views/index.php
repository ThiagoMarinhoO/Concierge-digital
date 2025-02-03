<?php

$question = new Question();

$configQuestions = $question->getQuestionsByCategory('Configuração');
$comportamentoQuestions = $question->getQuestionsByCategory('Comportamento');
$perguntasQuestions = $question->getQuestionsByCategory('Perguntas');
$baseDeConhecimentoQuestions = $question->getQuestionsByCategory('Base de Conhecimento');
$aparenciaQuestions = $question->getQuestionsByCategory('Aparência');
$integracoesQuestions = $question->getQuestionsByCategory('Integrações');

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
	<button data-tab="Configurações" data-locked="false"
		class="tab-btn rounded-md cursor-pointer p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent hover:border-gray-800 focus:outline-none">
		Configurações
	</button>
	<button data-tab="Comportamento" data-locked="true"
		class="tab-btn rounded-md cursor-not-allowed p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent opacity-50">
		Comportamento
	</button>
	<button data-tab="Basedeconhecimento" data-locked="true"
		class="tab-btn rounded-md cursor-not-allowed p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent opacity-50">
		Base de Conhecimento
	</button>
	<button data-tab="Perguntas" data-locked="true"
		class="tab-btn rounded-md cursor-not-allowed p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent opacity-50">
		Perguntas
	</button>
	<button data-tab="Integrações" data-locked="true"
		class="tab-btn rounded-md cursor-not-allowed p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent opacity-50">
		Integrações
	</button>
	<button data-tab="Aparência" data-locked="true"
		class="tab-btn rounded-md cursor-not-allowed p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent opacity-50">
		Aparência
	</button>
	<button data-tab="Teste" data-locked="true"
		class="tab-btn rounded-md cursor-not-allowed p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent opacity-50">
		Teste
	</button>
	<button data-tab="Download" data-locked="true"
		class="tab-btn rounded-md cursor-not-allowed p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent opacity-50">
		Download
	</button>
</div>

<div id="tabs-content-container" class="min-h-[560px]">
	<div id="Configurações-content" class="tab-content hidden absolute inset-0 bg-white p-4">
		<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
		<p>Conteúdo da aba Configurações</p>
		<div class="flex items-center justify-center gap-12">
			<div class="">
				<div class="input-container mb-4">
					<div class="question-block">
						<label for="chatbot_name" class="block font-medium text-gray-700 mb-2">
							Nome do Assistente
						</label>
						<input type="text" name="chatbot_name" placeholde="Qual o nome do chatbot ?"
							class="py-2 px-2.5 border border-gray-100 rounded-lg w-full" required>
					</div>
				</div>
				<div class="question-block">
					<label for="toggle_welcome_message" class="block font-medium text-gray-700 mb-2">
						Mostrar mensagem de boas vindas?
					</label>
					<input type="checkbox" id="toggle_welcome_message" class="toggle-checkbox">
					<div id="welcome_message_container" class="hidden">
						<label for="chatbot_welcome_message" class="block font-medium text-gray-700 mb-2">
							Qual será a mensagem de boas vindas?
						</label>
						<input type="text" name="chatbot_welcome_message"
							placeholder="Qual será a mensagem de boas vindas?"
							class="py-2 px-2.5 border border-gray-100 rounded-lg w-full">
					</div>
				</div>
				<?php if (!empty($configQuestions)): ?>
					<?php foreach ($configQuestions as $index => $question): ?>
						<div class="question-block">
							<label for="question-<?php echo esc_attr($index); ?>"
								data-question-base="<?php echo esc_attr($question['training_phrase']); ?>">
								<?php echo esc_html($question['title']); ?>
							</label>
							<?php
							$options = json_decode($question['options'], true);
							$field_type = $question['field_type']; // Verifica o tipo de campo
							$required = $question['required_field'] == 'Sim' ? 'required' : '';
							?>
							<?php if ($field_type === 'selection' && !empty($options) && is_array($options)): ?>
								<!-- Campo do tipo seleção -->
								<select class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2"
									id="question-<?php echo esc_attr($index); ?>"
									name="question_<?php echo esc_attr($question['id']); ?>" <?php echo $required ?>>
									<?php foreach ($options as $option): ?>
										<option value="<?php echo esc_attr($option); ?>">
											<?php echo esc_html($option); ?>
										</option>
									<?php endforeach; ?>
								</select>
							<?php elseif ($field_type === 'file'): ?>
								<!-- Campo do tipo arquivo -->
								<input type="file" id="question-<?php echo esc_attr($index); ?>"
									name="question_<?php echo esc_attr($question['id']); ?>"
									class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2" <?php echo $required ?>>
							<?php else: ?>
								<!-- Campo do tipo texto (padrão) -->
								<input type="text" id="question-<?php echo esc_attr($index); ?>"
									name="question_<?php echo esc_attr($question['id']); ?>"
									class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2"
									placeholde="<?php echo esc_attr($question['training_phrase']); ?>" <?php echo $required ?>>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php else: ?>
					<p>Nenhuma pergunta cadastrada no momento.</p>
				<?php endif; ?>
			</div>
			<div class="">
				<div class="video-container mb-4">
					<video controls class="w-full rounded-lg size-64">
						<source
							src="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/videos/configuracoes.mp4'); ?>"
							type="video/mp4">
					</video>
				</div>
			</div>
		</div>
		<div class="flex justify-center mt-10">
			<button class="saveConfigButton px-4 py-2.5 bg-green-400 rounded-full">Salvar</button>
		</div>
	</div>
	<div id="Comportamento-content" class="tab-content hidden absolute inset-0 bg-white p-4">
		<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
		<p>Conteúdo da aba Comportamento</p>
		<div class="flex items-center justify-center gap-12">
			<div class="">
				<div x-data="{ tab: 'rapida' }">
					<button :class="{ 'active': tab === 'rapida' }" @click="tab = 'rapida'"
						class="px-4 py-2 rounded-md">Rápida</button>
					<button :class="{ 'active': tab === 'personalizada' }" @click="tab = 'personalizada'"
						class="px-4 py-2 rounded-md">Personalizada</button>

					<div x-show="tab === 'rapida'" class="mt-4">
						<?php if (!empty($comportamentoOtherQuestions)): ?>
							<?php foreach ($comportamentoOtherQuestions as $index => $question): ?>
								<div class="question-block">
									<label for="question-<?php echo esc_attr($index); ?>"
										data-question-base="<?php echo esc_attr($question['training_phrase']); ?>">
										<?php echo esc_html($question['title']); ?>
									</label>
									<?php
									$options = json_decode($question['options'], true);
									$field_type = $question['field_type']; // Verifica o tipo de campo
									$required = $question['required_field'] == 'Sim' ? 'required' : '';
									?>
									<?php if ($field_type === 'selection' && !empty($options) && is_array($options)): ?>
										<!-- Campo do tipo seleção -->
										<select class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2"
											id="question-<?php echo esc_attr($index); ?>"
											name="question_<?php echo esc_attr($question['id']); ?>" <?php echo $required ?>>
											<?php foreach ($options as $option): ?>
												<option value="<?php echo esc_attr($option); ?>">
													<?php echo esc_html($option); ?>
												</option>
											<?php endforeach; ?>
										</select>
									<?php elseif ($field_type === 'file'): ?>
										<!-- Campo do tipo arquivo -->
										<input type="file" id="question-<?php echo esc_attr($index); ?>"
											name="question_<?php echo esc_attr($question['id']); ?>"
											class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2" <?php echo $required ?>>
									<?php else: ?>
										<!-- Campo do tipo texto (padrão) -->
										<input type="text" id="question-<?php echo esc_attr($index); ?>"
											name="question_<?php echo esc_attr($question['id']); ?>"
											class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2"
											placeholde="<?php echo esc_attr($question['training_phrase']); ?>" <?php echo $required ?>>
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
									<label for="question-<?php echo esc_attr($index); ?>"
										data-question-base="<?php echo esc_attr($question['training_phrase']); ?>">
										<?php echo esc_html($question['title']); ?>
									</label>
									<?php
									$options = json_decode($question['options'], true);
									$field_type = $question['field_type']; // Verifica o tipo de campo
									$required = $question['required_field'] == 'Sim' ? 'required' : '';
									?>
									<?php if ($field_type === 'selection' && !empty($options) && is_array($options)): ?>
										<!-- Campo do tipo seleção -->
										<select class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2"
											id="question-<?php echo esc_attr($index); ?>"
											name="question_<?php echo esc_attr($question['id']); ?>" <?php echo $required ?>>
											<?php foreach ($options as $option): ?>
												<option value="<?php echo esc_attr($option); ?>">
													<?php echo esc_html($option); ?>
												</option>
											<?php endforeach; ?>
										</select>
									<?php elseif ($field_type === 'file'): ?>
										<!-- Campo do tipo arquivo -->
										<input type="file" id="question-<?php echo esc_attr($index); ?>"
											name="question_<?php echo esc_attr($question['id']); ?>"
											class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2" <?php echo $required ?>>
									<?php else: ?>
										<!-- Campo do tipo texto (padrão) -->
										<input type="text" id="question-<?php echo esc_attr($index); ?>"
											name="question_<?php echo esc_attr($question['id']); ?>"
											class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2"
											placeholde="<?php echo esc_attr($question['training_phrase']); ?>" <?php echo $required ?>>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						<?php else: ?>
							<p>Nenhuma pergunta cadastrada no momento.</p>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<div class="">
				<div class="video-container mb-4">
					<video controls class="w-full rounded-lg size-64">
						<source
							src="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/videos/comportamento.mp4'); ?>"
							type="video/mp4">
					</video>
				</div>
			</div>
		</div>
		<div class="flex justify-center mt-10">
			<button class="saveComportamentoButton px-4 py-2.5 bg-green-400 rounded-full">Salvar</button>
		</div>
	</div>
	<div id="Basedeconhecimento-content" class="tab-content hidden absolute inset-0 bg-white p-4">
		<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
		<p>Conteúdo da aba Base de conhecimento</p>
		<div class="flex items-center justify-center gap-12">
			<div class="">
				<?php if (!empty($baseDeConhecimentoQuestions)): ?>
					<?php foreach ($baseDeConhecimentoQuestions as $index => $question): ?>
						<div class="question-block">
							<label for="question-<?php echo esc_attr($index); ?>"
								data-question-base="<?php echo esc_attr($question['training_phrase']); ?>">
								<?php echo esc_html($question['title']); ?>
							</label>
							<?php
							$options = json_decode($question['options'], true);
							$field_type = $question['field_type']; // Verifica o tipo de campo
							$required = $question['required_field'] == 'Sim' ? 'required' : '';
							?>
							<?php if ($field_type === 'selection' && !empty($options) && is_array($options)): ?>
								<!-- Campo do tipo seleção -->
								<select class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2"
									id="question-<?php echo esc_attr($index); ?>"
									name="question_<?php echo esc_attr($question['id']); ?>" <?php echo $required ?>>
									<?php foreach ($options as $option): ?>
										<option value="<?php echo esc_attr($option); ?>">
											<?php echo esc_html($option); ?>
										</option>
									<?php endforeach; ?>
								</select>
							<?php elseif ($field_type === 'file'): ?>
								<!-- Campo do tipo arquivo -->
								<input type="file" id="question-<?php echo esc_attr($index); ?>"
									name="question_<?php echo esc_attr($question['id']); ?>"
									class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2" <?php echo $required ?>>
							<?php else: ?>
								<!-- Campo do tipo texto (padrão) -->
								<input type="text" id="question-<?php echo esc_attr($index); ?>"
									name="question_<?php echo esc_attr($question['id']); ?>"
									class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2"
									placeholde="<?php echo esc_attr($question['training_phrase']); ?>" <?php echo $required ?>>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php else: ?>
					<p>Nenhuma pergunta cadastrada no momento.</p>
				<?php endif; ?>
			</div>
			<div class="">
				<div class="video-container mb-4">
					<video controls class="w-full rounded-lg size-64">
						<source
							src="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/videos/base-de-conhecimento.mp4'); ?>"
							type="video/mp4">
					</video>
				</div>
			</div>
		</div>
		<button class="saveknowledgeButton px-4 py-2.5 bg-green-400 rounded-full">Salvar</button>
	</div>
	<div id="Perguntas-content" class="tab-content hidden absolute inset-0 bg-white p-4">
		<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
		<p>Conteúdo da aba Perguntas</p>
		<?php if (!empty($perguntasQuestions)): ?>
			<?php foreach ($perguntasQuestions as $index => $question): ?>
				<div class="question-block">
					<label for="question-<?php echo esc_attr($index); ?>"
						data-question-base="<?php echo esc_attr($question['training_phrase']); ?>">
						<?php echo esc_html($question['title']); ?>
					</label>
					<?php
					$options = json_decode($question['options'], true);
					$field_type = $question['field_type']; // Verifica o tipo de campo
					$required = $question['required_field'] == 'Sim' ? 'required' : '';
					?>
					<?php if ($field_type === 'selection' && !empty($options) && is_array($options)): ?>
						<!-- Campo do tipo seleção -->
						<select class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2"
							id="question-<?php echo esc_attr($index); ?>" name="question_<?php echo esc_attr($question['id']); ?>"
							<?php echo $required ?>>
							<?php foreach ($options as $option): ?>
								<option value="<?php echo esc_attr($option); ?>">
									<?php echo esc_html($option); ?>
								</option>
							<?php endforeach; ?>
						</select>
					<?php elseif ($field_type === 'file'): ?>
						<!-- Campo do tipo arquivo -->
						<input type="file" id="question-<?php echo esc_attr($index); ?>"
							name="question_<?php echo esc_attr($question['id']); ?>"
							class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2" <?php echo $required ?>>
					<?php else: ?>
						<!-- Campo do tipo texto (padrão) -->
						<input type="text" id="question-<?php echo esc_attr($index); ?>"
							name="question_<?php echo esc_attr($question['id']); ?>"
							class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2"
							placeholde="<?php echo esc_attr($question['training_phrase']); ?>" <?php echo $required ?>>
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
		<div class="flex items-center justify-center gap-12">
			<div class="">
				<?php if (!empty($integracoesQuestions)): ?>
					<?php foreach ($integracoesQuestions as $index => $question): ?>
						<div class="question-block">
							<label for="question-<?php echo esc_attr($index); ?>"
								data-question-base="<?php echo esc_attr($question['training_phrase']); ?>">
								<?php echo esc_html($question['title']); ?>
							</label>
							<?php
							$options = json_decode($question['options'], true);
							$field_type = $question['field_type']; // Verifica o tipo de campo
							$required = $question['required_field'] == 'Sim' ? 'required' : '';
							?>
							<?php if ($field_type === 'selection' && !empty($options) && is_array($options)): ?>
								<!-- Campo do tipo seleção -->
								<select class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2"
									id="question-<?php echo esc_attr($index); ?>"
									name="question_<?php echo esc_attr($question['id']); ?>" <?php echo $required ?>>
									<?php foreach ($options as $option): ?>
										<option value="<?php echo esc_attr($option); ?>">
											<?php echo esc_html($option); ?>
										</option>
									<?php endforeach; ?>
								</select>
							<?php elseif ($field_type === 'file'): ?>
								<!-- Campo do tipo arquivo -->
								<input type="file" id="question-<?php echo esc_attr($index); ?>"
									name="question_<?php echo esc_attr($question['id']); ?>"
									class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2" <?php echo $required ?>>
							<?php else: ?>
								<!-- Campo do tipo texto (padrão) -->
								<input type="text" id="question-<?php echo esc_attr($index); ?>"
									name="question_<?php echo esc_attr($question['id']); ?>"
									class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2"
									placeholde="<?php echo esc_attr($question['training_phrase']); ?>" <?php echo $required ?>>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php else: ?>
					<p>Nenhuma pergunta cadastrada no momento.</p>
				<?php endif; ?>
			</div>
			<div class="">
				<div class="video-container mb-4">
					<video controls class="w-full rounded-lg size-64">
						<source
							src="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/videos/integracao.mp4'); ?>"
							type="video/mp4">
					</video>
				</div>
			</div>
		</div>
		<button class="saveIntegracaoButton px-4 py-2.5 bg-green-400 rounded-full">Salvar</button>
	</div>
	<div id="Aparência-content" class="tab-content hidden absolute inset-0 bg-white p-4">
		<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
		<p>Conteúdo da aba Aparência</p>
		<div class="input-container mb-4">
			<div class="question-block">
				<label for="appearance_image" class="block font-medium text-gray-700 mb-2">
					Adicione a foto do seu assistente virtual:
				</label>
				<input type="file" name="appearance_image" id="appearance_image"
					class="py-2 px-2.5 border border-gray-100 rounded-lg w-full" accept="image/*">
			</div>
		</div>
		<button class="saveAparenciaButton px-4 py-2.5 bg-green-400 rounded-full">Salvar</button>
	</div>
	<div id="Teste-content" class="tab-content hidden absolute inset-0 bg-white p-4">
		<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
		<?php
		$chatbot = new Chatbot();
		$user_id = get_current_user_id();

		$user_has_chatbot = $chatbot->userHasChatbot($user_id);
		$chatbots = $chatbot->getAllChatbots();

		if ($user_has_chatbot): ?>
			<div class="flex flex-col items-center justify-center min-h-screen text-gray-800 p-10">
				<div class="flex flex-col flex-grow w-full max-w-xl bg-white shadow-xl rounded-lg overflow-hidden">

					<!-- Select para selecionar o chatbot -->
					<div class="p-4 bg-gray-200">
						<label for="chatbot-selector" class="block text-sm font-medium text-gray-700">Selecione o
							Chatbot:</label>
						<select id="chatbot-selector"
							class="block w-full py-2 mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
							<?php foreach ($chatbots as $bot): ?>
								<option value="<?php echo esc_attr($bot->id); ?>">
									<?php echo esc_html($bot->chatbot_name); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<!-- Container do chat -->
					<div class="flex flex-col flex-grow h-0 p-4 overflow-auto chatContainer"
						data-chatbot-id="<?php echo esc_attr($chatbots[0]->id); ?>">
						<?php if ($chatbots[0]->chatbot_welcome_message): ?>
							<div class="flex w-full mt-2 space-x-3 max-w-xs">
								<div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300">
									<img src="<?php echo $chatbots[0]->chatbot_image; ?>" alt="">
								</div>
								<div>
									<div class="bg-gray-300 p-3 rounded-r-lg rounded-bl-lg text-sm">
										<?php echo $chatbots[0]->chatbot_welcome_message; ?>
									</div>
								</div>
							</div>
						<?php endif; ?>
					</div>

					<!-- Input para mensagem -->
					<div class="bg-gray-300 p-4 relative">
						<input class="flex items-center h-10 w-full rounded px-3 text-sm mensagem" type="text"
							placeholde="Escreva sua mensagem">
						<button
							class="bg-blue-600 text-white flex items-center justify-center p-2 rounded absolute top-4 right-4"
							id="enviarMensagem">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
								stroke="currentColor" class="size-6">
								<path stroke-linecap="round" stroke-linejoin="round"
									d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
							</svg>
						</button>
					</div>
				</div>

				<div class="flex justify-center gap-10">
					<form action="" method="POST" id="deleteChatbotForm">
						<button type="submit" name="delete_chatbot" class="bg-red-600 text-white p-2 mt-4 rounded">Resetar
							Chatbot</button>
					</form>
					<!-- <form action="" method="" id="">
						<button type="submit" name="" class="bg-green-600 text-white p-2 mt-4 rounded">Gerar link</button>
					</form> -->
				</div>
			</div>
		<?php else: ?>
			<button class="generateChatbot px-4 py-2.5 bg-green-400">Gerar chatbot</button>
		<?php endif; ?>
	</div>
	<div id="Download-content" class="tab-content hidden absolute inset-0 bg-white p-4">
		<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
		<p>Conteúdo da aba Download</p>
		<div class="flex items-center justify-center gap-12">
			<div class="w-1/2">
				<button type="button" name="" id="gerar-link" class="bg-green-600 text-white p-2 mt-4 rounded">Gerar
					link</button>
				<div id="clipboardSection"
					class="clipboardScript hidden mt-10 flex flex-col gap-4 border border-neutral-300 rounded-md bg-neutral-50 p-6 text-neutral-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
					<span class="font-bold text-sm text-green-600">Adicione este código ao head do seu site</span>
					<pre id="targetText" class="w-full whitespace-normal"></pre>
					<button id="copyButton"
						class="rounded-full w-fit p-1 flex items-center gap-4 text-neutral-600/75 hover:bg-neutral-950/10 hover:text-neutral-600 focus:outline-hidden focus-visible:text-neutral-600 focus-visible:outline focus-visible:outline-offset-0 focus-visible:outline-black active:bg-neutral-950/5 active:-outline-offset-2 dark:text-neutral-300/75 dark:hover:bg-white/10 dark:hover:text-neutral-300 dark:focus-visible:text-neutral-300 dark:focus-visible:outline-white dark:active:bg-white/5"
						title="Copiar" aria-label="Copy">
						<span id="copyStatus" class="">Copiar</span>
						<svg id="copyIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
							class="size-4" aria-hidden="true">
							<path fill-rule="evenodd"
								d="M13.887 3.182c.396.037.79.08 1.183.128C16.194 3.45 17 4.414 17 5.517V16.75A2.25 2.25 0 0 1 14.75 19h-9.5A2.25 2.25 0 0 1 3 16.75V5.517c0-1.103.806-2.068 1.93-2.207.393-.048.787-.09 1.183-.128A3.001 3.001 0 0 1 9 1h2c1.373 0 2.531.923 2.887 2.182ZM7.5 4A1.5 1.5 0 0 1 9 2.5h2A1.5 1.5 0 0 1 12.5 4v.5h-5V4Z"
								clip-rule="evenodd" />
						</svg>
						<svg id="successIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
							class="size-4 fill-green-500 hidden">
							<path fill-rule="evenodd"
								d="M11.986 3H12a2 2 0 0 1 2 2v6a2 2 0 0 1-1.5 1.937V7A2.5 2.5 0 0 0 10 4.5H4.063A2 2 0 0 1 6 3h.014A2.25 2.25 0 0 1 8.25 1h1.5a2.25 2.25 0 0 1 2.236 2ZM10.5 4v-.75a.75.75 0 0 0-.75-.75h-1.5a.75.75 0 0 0-.75.75V4h3Z"
								clip-rule="evenodd" />
							<path fill-rule="evenodd"
								d="M2 7a1 1 0 0 1 1-1h7a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7Zm6.585 1.08a.75.75 0 0 1 .336 1.005l-1.75 3.5a.75.75 0 0 1-1.16.234l-1.75-1.5a.75.75 0 0 1 .977-1.139l1.02.875 1.321-2.64a.75.75 0 0 1 1.006-.336Z"
								clip-rule="evenodd" />
						</svg>
					</button>
				</div>
			</div>
			<div class="w-1/2">
				<div class="video-container mb-4">
					<video controls class="w-full rounded-lg size-64">
						<source
							src="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/videos/download.mp4'); ?>"
							type="video/mp4">
					</video>
				</div>
			</div>
		</div>


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

			// Função auxiliar para validar apenas os campos obrigatórios
			function validateInputs(container) {
				const inputs = container.querySelectorAll("input[required], select[required]");
				let isValid = true;

				inputs.forEach(input => {
					if (!input.value.trim()) {
						isValid = false;
						// Adiciona classe de erro se quiser
						input.classList.add("error");
					} else {
						input.classList.remove("error");
					}
				});

				return isValid;
			}

			if (isComplex) {
				const activeTabContent = activeContent.querySelector("button.active")?.innerHTML.trim();

				let tabToSearch;
				if (activeTabContent === 'Rápida') {
					tabToSearch = activeContent.querySelector('[x-show="tab === \'rapida\'"]');
				} else if (activeTabContent === 'Personalizada') {
					tabToSearch = activeContent.querySelector('[x-show="tab === \'personalizada\'"]');
				}

				return tabToSearch ? validateInputs(tabToSearch) : false;
			}

			return validateInputs(activeContent);
		}

		function unlockNextTab() {
			if (currentTabIndex < buttons.length - 1) {
				const nextTabButton = buttons[currentTabIndex + 1];
				nextTabButton.dataset.locked = "false"; // Desbloquear
				nextTabButton.classList.remove("opacity-50", "cursor-not-allowed");
			}
		}

		function stopAllVideos() {
			const videos = document.querySelectorAll("video");
			videos.forEach(video => {
				video.pause();
				video.currentTime = 0;
			});
		}

		function saveConfigurations() {
			const activeContent = document.querySelector(".tab-content:not(.hidden)");
			const chatbotOptions = [];
			const fileInputs = activeContent.querySelectorAll('input[type="file"]');

			if (!activeContent) {
				console.error("Aba ativa não encontrada");
				return;
			}

			// Função para salvar no localStorage
			const saveData = (chatbotOptions) => {
				const categoryNameElement = activeContent.querySelector("h2") || {
					innerText: activeContent.id.replace("-content", ""),
				};
				const categoryName = categoryNameElement.innerText.trim();

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
						showTabContent("Comportamento");
					}
				});
			};

			// Função para processar blocos de perguntas
			const processQuestionBlocks = (fileUrls = []) => {
				activeContent.querySelectorAll(".question-block").forEach((questionBlock, index) => {
					const inputElement = questionBlock.querySelector("input:not([type='checkbox']), select");
					if (inputElement) {
						const perguntaLabel = questionBlock.querySelector("label").innerText.trim();
						let resposta = inputElement.value.trim();

						// Verifica se o input é de arquivo e atribui a URL correta
						if (inputElement.type === "file" && fileUrls.length > 0) {
							resposta = fileUrls.shift();
						}

						const trainingPhrase = questionBlock.querySelector("label").dataset.questionBase;
						const fieldType = inputElement.tagName.toLowerCase() === "select" ? "select" : inputElement.type;

						chatbotOptions.push({
							pergunta: perguntaLabel,
							field_name: inputElement.name,
							resposta: resposta,
							training_phrase: trainingPhrase,
							field_type: fieldType,
						});
					}
				});
				saveData(chatbotOptions);
			};

			if (fileInputs.length > 0) {
				const formData = new FormData();
				let hasFiles = false;

				fileInputs.forEach((fileInput) => {
					console.log(fileInput)
					if (fileInput.files.length > 0) {
						formData.append("files[]", fileInput.files[0]);
						hasFiles = true;
					}
				});

				if (hasFiles) {
					formData.append("action", "upload_files_to_media_library");

					// Fazer upload dos arquivos via AJAX
					fetch(conciergeAjax.ajax_url, {
						method: "POST",
						body: formData,
					})
						.then((response) => response.json())
						.then((data) => {
							if (data.success) {
								// Processa blocos com URLs dos arquivos
								processQuestionBlocks(data.data.urls);
							} else {
								console.error("Falha ao enviar arquivos:", data.message);
							}
						})
						.catch((error) => {
							console.error("Erro na requisição de upload:", error);
						});
				} else {
					// Sem arquivos, apenas processa os blocos
					processQuestionBlocks();
				}
			} else {
				// Sem arquivos, apenas processa os blocos
				processQuestionBlocks();
			}


		}

		function saveBehavior() {
			const activeContent = document.querySelector(".tab-content:not(.hidden)"); // Aba visível
			const chatbotOptions = [];
			const fileInputs = activeContent.querySelectorAll('input[type="file"]');

			if (!activeContent) {
				console.error("Aba ativa não encontrada");
				return;
			}

			const activeTabContent = activeContent.querySelector("button.active").innerHTML.trim();

			let tabToSearch;
			if (activeTabContent === "Rápida") {
				tabToSearch = activeContent.querySelector('[x-show="tab === \'rapida\'"]');
			} else if (activeTabContent === "Personalizada") {
				tabToSearch = activeContent.querySelector('[x-show="tab === \'personalizada\'"]');
			}

			// Função para salvar no localStorage
			const saveData = (chatbotOptions) => {
				const categoryNameElement = activeContent.querySelector("h2") || {
					innerText: activeContent.id.replace("-content", ""),
				};
				const categoryName = categoryNameElement.innerText.trim();

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
						showTabContent("Basedeconhecimento");
					}
				});
			};

			// Função para processar blocos de perguntas
			const processQuestionBlocks = (fileUrls = []) => {
				tabToSearch.querySelectorAll(".question-block").forEach((questionBlock, index) => {
					const inputElement = questionBlock.querySelector("input, select");
					if (inputElement) {
						const perguntaLabel = questionBlock.querySelector("label").innerText.trim();
						let resposta = inputElement.value.trim();

						// Verifica se o input é de arquivo e atribui a URL correta
						if (inputElement.type === "file" && fileUrls.length > 0) {
							resposta = fileUrls.shift();
						}

						const trainingPhrase = questionBlock.querySelector("label").dataset.questionBase;
						const fieldType = inputElement.tagName.toLowerCase() === "select" ? "select" : inputElement.type;

						chatbotOptions.push({
							pergunta: perguntaLabel,
							field_name: inputElement.name,
							resposta: resposta,
							training_phrase: trainingPhrase,
							field_type: fieldType,
						});
					}
				});
				saveData(chatbotOptions);
			};

			// Processar upload de arquivos se houver
			if (fileInputs.length > 0) {
				const formData = new FormData();

				fileInputs.forEach((fileInput) => {
					if (fileInput.files.length > 0) {
						formData.append("files[]", fileInput.files[0]);
					}
				});

				formData.append("action", "upload_files_to_media_library");

				// Fazer upload dos arquivos via AJAX
				fetch(conciergeAjax.ajax_url, {
					method: "POST",
					body: formData,
				})
					.then((response) => response.json())
					.then((data) => {
						if (data.success) {
							// Processa blocos com URLs dos arquivos
							processQuestionBlocks(data.data.urls);
						} else {
							processQuestionBlocks();
							console.error("Falha ao enviar arquivos:", data.message);
						}
					})
					.catch((error) => {
						console.error("Erro na requisição de upload:", error);
					});
			} else {
				// Sem arquivos, apenas processa os blocos
				processQuestionBlocks();
			}
		}

		function saveKnowledge() {
			const activeContent = document.querySelector(".tab-content:not(.hidden)"); // Aba visível
			const chatbotOptions = [];
			const fileInputs = activeContent.querySelectorAll('input[type="file"]');

			// Verificar se a aba visível foi encontrada
			if (!activeContent) {
				console.error("Aba ativa não encontrada");
				return;
			}

			// Função para salvar no localStorage
			const saveData = (chatbotOptions) => {
				const categoryNameElement = activeContent.querySelector("h2") || {
					innerText: activeContent.id.replace("-content", ""),
				};
				const categoryName = categoryNameElement.innerText.trim();

				const savedData = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};
				savedData[categoryName] = chatbotOptions;
				localStorage.setItem("chatbotRespostas", JSON.stringify(savedData));

				unlockNextTab();
				stopAllVideos();

				Swal.close()
				Swal.fire({
					title: `Respostas salvas`,
					text: `Respostas salvas para a categoria: ${categoryName}`,
					icon: "success",
				}).then((result) => {
					if (result.isConfirmed) {
						hideAllTabs();
						showTabContent("Perguntas");
					}
				});
			};

			// Função para processar blocos de perguntas
			const processQuestionBlocks = (fileUrls = []) => {
				activeContent.querySelectorAll(".question-block").forEach((questionBlock, index) => {
					const inputElement = questionBlock.querySelector("input, select");
					if (inputElement) {
						const perguntaLabel = questionBlock.querySelector("label").innerText.trim();
						let resposta = inputElement.value.trim();

						// Verifica se o input é de arquivo e atribui a URL correta
						if (inputElement.type === "file" && fileUrls.length > 0) {
							resposta = fileUrls.shift();
						}

						const trainingPhrase = questionBlock.querySelector("label").dataset.questionBase;
						const fieldType = inputElement.tagName.toLowerCase() === "select" ? "select" : inputElement.type;

						chatbotOptions.push({
							pergunta: perguntaLabel,
							field_name: inputElement.name,
							resposta: resposta,
							training_phrase: trainingPhrase,
							field_type: fieldType
						});
					}
				});
				saveData(chatbotOptions);
			};

			// Processar upload de arquivos se houver
			if (fileInputs.length > 0) {
				const formData = new FormData();

				fileInputs.forEach((fileInput) => {
					if (fileInput.files.length > 0) {
						formData.append("files[]", fileInput.files[0]);
					}
				});

				formData.append("action", "upload_files_to_media_library");

				// Fazer upload dos arquivos via AJAX
				fetch(conciergeAjax.ajax_url, {
					method: "POST",
					body: formData,
				})
					.then((response) => response.json())
					.then((data) => {
						if (data.success) {
							// Processa blocos com URLs dos arquivos
							processQuestionBlocks(data.data.urls);
						} else {
							console.error("Falha ao enviar arquivos:", data.message);
						}
					})
					.catch((error) => {
						console.error("Erro na requisição de upload:", error);
					});
			} else {
				// Sem arquivos, apenas processa os blocos
				processQuestionBlocks();
			}
		}


		function saveQuestions() {
			const activeContent = document.querySelector(".tab-content:not(.hidden)"); // Aba visível
			const chatbotOptions = [];
			const fileInputs = activeContent.querySelectorAll('input[type="file"]');

			// Verificar se a aba visível foi encontrada
			if (!activeContent) {
				console.error("Aba ativa não encontrada");
				return;
			}

			// Função para salvar no localStorage
			const saveData = (chatbotOptions) => {
				const categoryNameElement = activeContent.querySelector("h2") || {
					innerText: activeContent.id.replace("-content", ""),
				};
				const categoryName = categoryNameElement.innerText.trim();

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
						showTabContent("Integrações");
					}
				});
			};

			// Função para processar blocos de perguntas
			const processQuestionBlocks = (fileUrls = []) => {
				activeContent.querySelectorAll(".question-block").forEach((questionBlock, index) => {
					const inputElement = questionBlock.querySelector("input, select");
					if (inputElement) {
						const perguntaLabel = questionBlock.querySelector("label").innerText.trim();
						let resposta = inputElement.value.trim();

						// Verifica se o input é de arquivo e atribui a URL correta
						if (inputElement.type === "file" && fileUrls.length > 0) {
							resposta = fileUrls.shift();
						}

						const trainingPhrase = questionBlock.querySelector("label").dataset.questionBase;
						const fieldType = inputElement.tagName.toLowerCase() === "select" ? "select" : inputElement.type;

						chatbotOptions.push({
							pergunta: perguntaLabel,
							field_name: inputElement.name,
							resposta: resposta,
							training_phrase: trainingPhrase,
							field_type: fieldType,
						});
					}
				});
				saveData(chatbotOptions);
			};

			// Processar upload de arquivos se houver
			if (fileInputs.length > 0) {
				const formData = new FormData();

				fileInputs.forEach((fileInput) => {
					if (fileInput.files.length > 0) {
						formData.append("files[]", fileInput.files[0]);
					}
				});

				formData.append("action", "upload_files_to_media_library");

				// Fazer upload dos arquivos via AJAX
				fetch(conciergeAjax.ajax_url, {
					method: "POST",
					body: formData,
				})
					.then((response) => response.json())
					.then((data) => {
						if (data.success) {
							// Processa blocos com URLs dos arquivos
							processQuestionBlocks(data.data.urls);
						} else {
							console.error("Falha ao enviar arquivos:", data.message);
						}
					})
					.catch((error) => {
						console.error("Erro na requisição de upload:", error);
					});
			} else {
				// Sem arquivos, apenas processa os blocos
				processQuestionBlocks();
			}
		}


		function saveIntegrations() {
			const activeContent = document.querySelector(".tab-content:not(.hidden)"); // Aba visível
			const chatbotOptions = [];
			const fileInputs = activeContent.querySelectorAll('input[type="file"]');

			// Verificar se a aba visível foi encontrada
			if (!activeContent) {
				console.error("Aba ativa não encontrada");
				return;
			}

			// Função para salvar no localStorage
			const saveData = (chatbotOptions) => {
				const categoryNameElement = activeContent.querySelector("h2") || {
					innerText: activeContent.id.replace("-content", ""),
				};
				const categoryName = categoryNameElement.innerText.trim();

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
						showTabContent("Aparência");
					}
				});
			};

			// Função para processar blocos de perguntas
			const processQuestionBlocks = (fileUrls = []) => {
				activeContent.querySelectorAll(".question-block").forEach((questionBlock, index) => {
					const inputElement = questionBlock.querySelector("input, select");
					if (inputElement) {
						const perguntaLabel = questionBlock.querySelector("label").innerText.trim();
						let resposta = inputElement.value.trim();

						// Verifica se o input é de arquivo e atribui a URL correta
						if (inputElement.type === "file" && fileUrls.length > 0) {
							resposta = fileUrls.shift();
						}

						const trainingPhrase = questionBlock.querySelector("label").dataset.questionBase;
						const fieldType = inputElement.tagName.toLowerCase() === "select" ? "select" : inputElement.type;

						chatbotOptions.push({
							pergunta: perguntaLabel,
							field_name: inputElement.name,
							resposta: resposta,
							training_phrase: trainingPhrase,
							field_type: fieldType,
						});
					}
				});
				saveData(chatbotOptions);
			};

			// Processar upload de arquivos se houver
			if (fileInputs.length > 0) {
				const formData = new FormData();

				fileInputs.forEach((fileInput) => {
					if (fileInput.files.length > 0) {
						formData.append("files[]", fileInput.files[0]);
					}
				});

				formData.append("action", "upload_files_to_media_library");

				// Fazer upload dos arquivos via AJAX
				fetch(conciergeAjax.ajax_url, {
					method: "POST",
					body: formData,
				})
					.then((response) => response.json())
					.then((data) => {
						if (data.success) {
							// Processa blocos com URLs dos arquivos
							processQuestionBlocks(data.data.urls);
						} else {
							console.error("Falha ao enviar arquivos:", data.message);
						}
					})
					.catch((error) => {
						console.error("Erro na requisição de upload:", error);
					});
			} else {
				// Sem arquivos, apenas processa os blocos
				processQuestionBlocks();
			}
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
					stopAllVideos();
					hideAllTabs();
				});
			}
		});

		const saveConfigButton = document.querySelector("button.saveConfigButton");
		if (saveConfigButton) {
			saveConfigButton.addEventListener("click", () => {
				if (validateCurrentTab(false)) {
					saveConfigurations();
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
				Swal.fire({
					title: 'Carregando...',
					text: 'Por favor, aguarde enquanto salvamos os dados.',
					allowOutsideClick: false,
					didOpen: () => {
						Swal.showLoading();
					}
				});
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
				// alert("Preencha todos os campos antes de salvar.");
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
								// field.value = item.resposta;
								if (field.type !== 'file') {
									field.value = item.resposta;
								}
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

				const localChatbotOptions = JSON.parse(localStorage.getItem("chatbotRespostas")) || {};
				const chatbotOptions = Object.values(localChatbotOptions).reduce((acc, val) => acc.concat(val), []);
				const chatbotName = localChatbotOptions["Configurações"][0]?.resposta;
				const chatbotWelcomeMessage = localChatbotOptions["Configurações"][1]?.resposta;

				const appearanceImageInput = document.querySelector("#appearance_image");
				const formData = new FormData();

				formData.append("action", "create_chatbot");
				formData.append("chatbot_name", chatbotName);
				formData.append("chatbot_welcome_message", chatbotWelcomeMessage);
				formData.append("chatbot_options", JSON.stringify(chatbotOptions));

				if (appearanceImageInput && appearanceImageInput.files.length > 0) {
					formData.append("chatbot_image", appearanceImageInput.files[0]);
				}

				Swal.fire({
					title: 'Tem certeza?',
					text: "Você deseja gerar o chatbot?",
					icon: 'warning',
					showCancelButton: true,
					confirmButtonColor: '#3085d6',
					cancelButtonColor: '#d33',
					confirmButtonText: 'Sim, gerar!'
				}).then((result) => {
					if (result.isConfirmed) {
						fetch(conciergeAjax.ajax_url, {
							method: "POST",
							body: formData,
						})
							.then((response) => response.json())
							.then((data) => { })
							.finally(() => {
								unlockNextTab();
								stopAllVideos();
								window.location.reload();
							})
							.catch((error) => {
								console.error("Erro:", error);
							});
					}
				});
			});

		}
		const downloadTabButton = document.querySelector('button[data-tab="Download"]');

		function checkAllTabsUnlocked() {
			let allUnlocked = true;
			buttons.forEach((button, index) => {
				if (index < buttons.length - 1 && button.dataset.locked === "true") {
					allUnlocked = false;
				}
			});
			return allUnlocked;
		}

		if (checkAllTabsUnlocked()) {
			downloadTabButton.dataset.locked = "false";
			downloadTabButton.classList.remove("opacity-50", "cursor-not-allowed");
		}

		const clipboardSection = document.getElementById("clipboardSection");
		const targetText = document.getElementById("targetText");
		const copyButton = document.getElementById("copyButton");
		const copyStatus = document.getElementById("copyStatus");
		const copyIcon = document.getElementById("copyIcon");
		const successIcon = document.getElementById("successIcon");

		// Função para copiar o texto para o clipboard
		function copyToClipboard() {
			const textToCopy = targetText.textContent;

			navigator.clipboard
				.writeText(textToCopy)
				.then(() => {
					// Atualiza o status visual para "copiado"
					copyStatus.textContent = "Copiado!";
					copyIcon.classList.add("hidden");
					successIcon.classList.remove("hidden");

					// Reseta o estado visual após 2 segundos
					setTimeout(() => {
						copyStatus.textContent = "Copiar";
						copyIcon.classList.remove("hidden");
						successIcon.classList.add("hidden");
					}, 2000);
				})
				.catch((err) => {
					console.error("Erro ao copiar para o clipboard: ", err);
				});
		}

		// Adiciona o evento de clique no botão de copiar
		copyButton.addEventListener("click", copyToClipboard);


	});

	document.addEventListener("DOMContentLoaded", () => {
		const toggleWelcomeMessage = document.getElementById("toggle_welcome_message");
		const welcomeMessageContainer = document.getElementById("welcome_message_container");

		if (toggleWelcomeMessage) {
			toggleWelcomeMessage.addEventListener("change", () => {
				const label = toggleWelcomeMessage.closest('.question-block').querySelector('label');
				if (toggleWelcomeMessage.checked) {
					welcomeMessageContainer.classList.remove("hidden");
					label.classList.add("hidden");
				} else {
					welcomeMessageContainer.classList.add("hidden");
					label.classList.remove("hidden");
				}
			});
		}
	});
</script>