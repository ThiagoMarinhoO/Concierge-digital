<div x-data="{modalIsOpen: false}" class="hidden">
	<button @click="modalIsOpen = true" type="button" class="fixed top-8 right-8 cursor-pointer whitespace-nowrap rounded-md bg-black px-4 py-2 text-center text-sm font-medium tracking-wide text-neutral-100 transition hover:opacity-75 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black active:opacity-100 active:outline-offset-0 dark:bg-white dark:text-black dark:focus-visible:outline-white">Open Modal</button>
	<div x-cloak x-show="modalIsOpen" x-transition.opacity.duration.200ms x-trap.inert.noscroll="modalIsOpen" @keydown.esc.window="modalIsOpen = false" @click.self="modalIsOpen = false" class="fixed inset-0 z-30 flex items-end justify-center bg-black/20 p-4 pb-8 backdrop-blur-md sm:items-center lg:p-8" role="dialog" aria-modal="true" aria-labelledby="defaultModalTitle">
		<!-- Modal Dialog -->
		<div x-show="modalIsOpen" x-transition:enter="transition ease-out duration-200 delay-100 motion-reduce:transition-opacity" x-transition:enter-start="opacity-0 scale-50" x-transition:enter-end="opacity-100 scale-100" class="flex max-w-lg flex-col gap-4 overflow-hidden rounded-md border border-neutral-300 bg-white text-neutral-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
			<!-- Dialog Header -->
			<div class="flex items-center justify-between border-b border-neutral-300 bg-neutral-50/60 p-4 dark:border-neutral-700 dark:bg-neutral-950/20">
				<h3 id="defaultModalTitle" class="font-semibold tracking-wide text-neutral-900 dark:text-white">Chatbot Opções</h3>
				<button @click="modalIsOpen = false" aria-label="close modal">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" stroke="currentColor" fill="none" stroke-width="1.4" class="w-5 h-5">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
					</svg>
				</button>
			</div>
			<!-- Dialog Body -->
			<div class="px-4 py-8">
				<?php
				if (isset($_SESSION['chatbotOptions'])) {
					echo '<pre>';
					print_r($_SESSION['chatbotOptions']);
					echo '</pre>';
				}
				?>
			</div>
			<div class="flex flex-col-reverse justify-between gap-2 border-t border-neutral-300 bg-neutral-50/60 p-4 dark:border-neutral-700 dark:bg-neutral-950/20 sm:flex-row sm:items-center md:justify-end">
				<button type="button" class="clearChatbot cursor-pointer whitespace-nowrap rounded-md bg-black px-4 py-2 text-center text-sm font-medium tracking-wide text-neutral-100 transition hover:opacity-75 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black active:opacity-100 active:outline-offset-0 dark:bg-white dark:text-black dark:focus-visible:outline-white">Limpar chatbot options</button>
			</div>
		</div>
	</div>
</div>

<script>
	document.addEventListener('DOMContentLoaded', () => {
		document.querySelector('.clearChatbot').addEventListener('click', () => {

			var formData = new FormData();
			formData.append('action', 'clear_session');


			fetch(conciergeAjax.ajax_url, {
				method: 'POST',
				body: formData
			}).then((data) => {
				window.location.reload();
			})
		})
	})
</script>



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



<!-- USUÁRIO PRECISA DE UM PAINEL PARA CADASTRAR CHATBOTS OU APRESENTAR OS EXISTENTES -->