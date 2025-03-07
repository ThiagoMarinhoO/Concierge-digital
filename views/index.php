<?php

$question = new Question();
$chatbot = new Chatbot();
$user_id = get_current_user_id();

$categories = array_filter($question->getAllCategories(), function ($category) {
	return $category['title'] !== 'Regras gerais';
});


$questionsByCategory = [];
foreach ($categories as $category) {
	$categoryTitle = $category['title'];
	$questionsByCategory[$categoryTitle] = $question->getQuestionsByCategory($categoryTitle);
}

if (!empty($user_id)) {
	$userchatbot_id = $chatbot->getChatbotIdByUser($user_id);
	$assistant = $chatbot->getChatbotById($userchatbot_id, $user_id);
}

$user_has_chatbot = $chatbot->userHasChatbot($user_id);
?>
<div id="tabs-container" class="grid grid-cols-1 md:grid-cols-3 gap-4 relative">
	<?php $firstUnlocked = true; ?>
	<?php $i = 1; ?>
	<?php foreach ($categories as $index => $category): ?>
		<?php
		$tabName = str_replace(' ', '_', remover_acentos($category['title']));
		$tabNameText = $category['title'];
		$isLocked = !$firstUnlocked;

		$firstUnlocked = false;
		?>
		<button data-current="<?php echo $i === 1 ? 'true' : 'false' ?>" data-tab="<?= esc_attr($tabName) ?>" data-locked="<?= $isLocked ? 'true' : 'false' ?>"
			class="tab-btn rounded-md <?= $isLocked ? 'cursor-not-allowed opacity-50' : 'cursor-pointer' ?> p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent <?= $isLocked ? '' : 'hover:border-gray-800' ?> focus:outline-none" data-tab-num="<?php echo $i ?>">
			<?= esc_html($tabNameText) ?>
		</button>
		<?php $i++ ?>
	<!-- teste de branch -->
	<?php endforeach; ?>
	
	<button data-current="false" data-tab="Aparência" data-locked="true"
		class="tab-btn rounded-md cursor-not-allowed p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent opacity-50" data-tab-num="<?php echo $i++ ?>">
		Aparência
	</button>
	<button data-current="false" data-tab="Teste" data-locked="true"
		class="tab-btn rounded-md cursor-not-allowed p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent opacity-50" data-tab-num="<?php echo $i++ ?>">
		Teste
	</button>
	<button data-current="false" data-tab="Download" data-locked="true"
		class="tab-btn rounded-md cursor-not-allowed p-6 shadow-md bg-white text-gray-700 font-bold border-b-2 border-transparent opacity-50" data-tab-num="<?php echo $i++ ?>">
		Download
	</button>
</div>


<div id="tabs-content-container" class="min-h-[560px]">
	<input type="hidden" name="chatbotId" id="chatbotID" value="<?php echo $userchatbot_id ?>">
	<input type="hidden" name="hasChat" id="hasChatbot" value="<?php echo $user_has_chatbot ?>">
	<?php foreach ($categories as $category): ?>
		<?php $tabName = str_replace(' ', '_', remover_acentos($category['title'])); ?>
		<div id="<?php echo $tabName ?>-content" class="tab-content hidden absolute inset-0 bg-white p-4">
			<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
			<p><?php echo $category['title'] ?></p>
			<?php if ($category['has_tabs']) : ?>
				<div x-data="{ selectedTab: 'fast' }" class="w-full">
					<div x-on:keydown.right.prevent="$focus.wrap().next()" x-on:keydown.left.prevent="$focus.wrap().previous()" class="flex gap-2" role="tablist" aria-label="tab options">
						<button x-on:click="selectedTab = 'fast'" x-bind:aria-selected="selectedTab === 'fast'" x-bind:tabindex="selectedTab === 'fast' ? '0' : '-1'" x-bind:class="selectedTab === 'fast' ? 'font-bold text-black border-b-2 border-black dark:border-white dark:text-white' : 'text-neutral-600 font-medium dark:text-neutral-300 dark:hover:border-b-neutral-300 dark:hover:text-white hover:border-b-2 hover:border-b-neutral-800 hover:text-neutral-900'" class="h-min px-4 py-2 text-sm" type="button" role="tab" aria-controls="tabpanelFast">Rápida</button>
						<button x-on:click="selectedTab = 'custom'" x-bind:aria-selected="selectedTab === 'custom'" x-bind:tabindex="selectedTab === 'custom' ? '0' : '-1'" x-bind:class="selectedTab === 'custom' ? 'font-bold text-black border-b-2 border-black dark:border-white dark:text-white' : 'text-neutral-600 font-medium dark:text-neutral-300 dark:hover:border-b-neutral-300 dark:hover:text-white hover:border-b-2 hover:border-b-neutral-800 hover:text-neutral-900'" class="h-min px-4 py-2 text-sm" type="button" role="tab" aria-controls="tabpanelCustom">Personalizada</button>
					</div>
					<div class="px-2 py-4 text-neutral-600 dark:text-neutral-300">
						<div x-cloak x-show="selectedTab === 'fast'" id="tabpanelfast" role="tabpanel" aria-label="fast">
							<div>
								<?php foreach ($questionsByCategory[$category['title']] as $index => $question): ?>
									<?php if (!empty($questionsByCategory)): ?>
										<div class="question-block">
											<label for="question-<?php echo esc_attr($index); ?>"
												data-question-base="<?php echo esc_attr($question['training_phrase']); ?>">
												<?php echo esc_html($question['title']); ?>
											</label>
											<?php
											$options = json_decode($question['options'], true);
											$field_type = $question['field_type']; // Verifica o tipo de campo
											$required = $question['required_field'] == 'Sim' ? 'required' : '';
											$name = $question['objective'] == 'nome' ? 'assistent-name' : '';
											$wellcome_message = $question['objective'] == 'boas-vindas' ? 'assistent-message' : '';
											?>
											<?php if ($field_type === 'selection' && !empty($options) && is_array($options)): ?>
												<!-- Campo do tipo seleção -->
												<select class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2 <?php echo $name . ' ' . $wellcome_message ?>"
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
													class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2 <?php echo $name . ' ' . $wellcome_message ?>" <?php echo $required ?>>
											<?php else: ?>
												<!-- Campo do tipo texto (padrão) -->
												<input type="text" id="question-<?php echo esc_attr($index); ?>"
													name="question_<?php echo esc_attr($question['id']); ?>"
													class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2 <?php echo $name . $wellcome_message ?>"
													placeholde="<?php echo esc_attr($question['training_phrase']); ?>" <?php echo $required ?>>
											<?php endif; ?>
										</div>
									<?php else: ?>
										<p>Nenhuma pergunta cadastrada no momento.</p>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						</div>
						<div x-cloak x-show="selectedTab === 'custom'" id="tabpanelcustom" role="tabpanel" aria-label="custom">
							<div class="question-block flex flex-col gap-2">
								<label for="prompt">Escreva aqui seu prompt:</label>
								<textarea name="" id="prompt"></textarea>
							</div>
						</div>
					</div>
				</div>
			<?php else : ?>
				<div class="flex items-center justify-center gap-12">
					<div>
						<?php foreach ($questionsByCategory[$category['title']] as $index => $question): ?>
							<?php if (!empty($questionsByCategory)): ?>
								<div class="question-block">
									<label for="question-<?php echo esc_attr($index); ?>"
										data-question-base="<?php echo esc_attr($question['training_phrase']); ?>">
										<?php echo esc_html($question['title']); ?>
									</label>
									<?php
									$options = json_decode($question['options'], true);
									$field_type = $question['field_type']; // Verifica o tipo de campo
									$required = $question['required_field'] == 'Sim' ? 'required' : '';
									$name = $question['objective'] == 'nome' ? 'assistent-name' : '';
									$wellcome_message = $question['objective'] == 'boas-vindas' ? 'assistent-message' : '';
									?>
									<?php if ($field_type === 'selection' && !empty($options) && is_array($options)): ?>
										<!-- Campo do tipo seleção -->
										<select class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2 <?php echo $name . ' ' . $wellcome_message ?>"
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
											class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2 <?php echo $name . ' ' . $wellcome_message ?>" <?php echo $required ?>>
									<?php else: ?>
										<!-- Campo do tipo texto (padrão) -->
										<input type="text" id="question-<?php echo esc_attr($index); ?>"
											name="question_<?php echo esc_attr($question['id']); ?>"
											class="py-2 px-2.5 border border-gray-100 rounded-lg w-full my-2 <?php echo $name . $wellcome_message ?>"
											placeholde="<?php echo esc_attr($question['training_phrase']); ?>" <?php echo $required ?>>
									<?php endif; ?>
								</div>
							<?php else: ?>
								<p>Nenhuma pergunta cadastrada no momento.</p>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
					<?php if ($category['video_url'] != ''): ?>
						<div class="">
							<div class="video-container mb-4">
								<video controls class="w-full rounded-lg size-64">
									<source
										src="<?php echo $category['video_url'] ?>"
										type="video/mp4">
								</video>
							</div>
						</div>
					<?php endif ?>
				</div>
			<?php endif; ?>
			<div class="flex justify-center mt-10">
				<button class="saveButton px-4 py-2.5 bg-green-400 rounded-full">Salvar</button>
			</div>
		</div>
	<?php endforeach ?>
	<div id="Aparência-content" class="tab-content hidden absolute inset-0 bg-white p-4">
		<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
		<p>Aparência</p>
		<div class="input-container mb-4 flex items-center justify-center gap-12">
			<div class="question-block">
				<label for="appearance_image" class="block font-medium text-gray-700 mb-2">
					Adicione a foto do seu assistente virtual:
				</label>
				<input type="file" name="appearance_image" id="appearance_image"
					class="py-2 px-2.5 border border-gray-100 rounded-lg w-full" accept="image/*">
			</div>
		</div>
		<div class="flex justify-center mt-10">
		    <button class="saveAparenciaButton px-4 py-2.5 bg-green-400 rounded-full">Salvar</button>
		</div>
	</div>
	<div id="Teste-content" class="tab-content hidden absolute inset-0 bg-white p-4">
		<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
		<?php
		$existing_assistant = new Chatbot();
		$assistants = $existing_assistant->getAllChatbots();

		$usage = UsageService::usagePercentages();
		$total_usage = $usage['total'];

		if ($user_has_chatbot): ?>
			<div class="relative flex flex-col items-center justify-center min-h-screen text-gray-800 p-10">

				<div class="limit_token p-4 w-[400px]">

					<div class="flex justify-between mb-1">
						<span class="text-base font-medium text-[#13072E]">Limite de Token</span>
						<span class="text-sm font-medium text-[#13072E] usage-percentage-number"><?php echo intval($total_usage) . '%'; ?></span>
					</div>
					<div class="flex w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
						<div class="h-2.5 rounded-full transition duration-300 usage-percentage-bar" style="width: <?php echo intval($total_usage) . '%'; ?>; background: linear-gradient(90deg, #ffbee6, #b3aaff);"></div>
					</div>

				</div>

				<div class="flex flex-col flex-grow w-full max-w-xl bg-white shadow-xl rounded-lg overflow-hidden">

					<!-- Select para selecionar o chatbot -->
					<div class="p-4 bg-gray-200">
						<label for="chatbot-selector" class="block text-sm font-medium text-gray-700">Selecione o
							assistente virtual:</label>
						<select id="chatbot-selector"
							class="block w-full py-2 mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
							<?php foreach ($assistants as $bot): ?>
								<option value="<?php echo esc_attr($bot->id); ?>">
									<?php echo esc_html($bot->chatbot_name); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<!-- Container do chat -->
					<div class="flex flex-col flex-grow h-0 p-4 overflow-auto chatContainer"
						data-assistant-id="<?php echo esc_attr($assistants[0]->id); ?>" data-session-id="">
						
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
					<!-- <form action="" method="POST" id="deleteChatbotForm">
						<button type="submit" name="delete_chatbot" class="bg-red-600 text-white p-2 mt-4 rounded">Resetar assistente virtual</button>
					</form> -->
					<button class="back-btn bg-red-600 text-white p-2 mt-4 rounded">Editar assistente virtual</button>
				</div>


			</div>
		<?php else: ?>
			<button class="generateChatbot px-4 py-2.5 bg-green-400">Gerar chatbot</button>
		<?php endif; ?>
	</div>
	<div id="Download-content" class="tab-content hidden absolute inset-0 bg-white p-4">
		<button class="back-btn bg-gray-300 text-gray-700 py-2 px-4 rounded mb-4">Voltar</button>
		<p>Download</p>
		<button type="button" name="" id="gerar-link" class="mb-4 rounded">Gerar link</button>
		<div class="flex items-center justify-center gap-12">
			<div class="w-1/2">
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
