<div x-data="{modalIsOpen: false}">
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

<?php if (isset($_SESSION['chatbotOptions'])) : ?>

	<div class="flex flex-col items-center justify-center w-screen min-h-screen bg-gray-100 text-gray-800 p-10">
		<div class="flex flex-col flex-grow w-full max-w-xl bg-white shadow-xl rounded-lg overflow-hidden">
			<div class="flex flex-col flex-grow h-0 p-4 overflow-auto chatContainer">

			</div>
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
			document.querySelector('#enviarMensagem').addEventListener('click', (event) => {
				event.preventDefault();

				let currHour = new Date();

				const userMsgTemplate = `
					<div class="flex w-full mt-2 space-x-3 max-w-xs ml-auto justify-end">
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
				chatBox.scrollIntoView(false);

				// const payload = JSON.stringify({
				// 	"action": "concierge_chat",
				// 	"mensagem": document.querySelector(".mensagem").value
				// });

				const formData = new FormData();
				formData.append('action', 'concierge_chat');
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
						chatBox.scrollIntoView(false);

					})
					.catch((error) => {
						console.error('Error:', error);
						}).finally(() => {
						    // document.querySelector(".sendMessage").classList.remove('is-loading');
							document.querySelector("#enviarMensagem").classList.remove('opacity-90');
							document.querySelector("#enviarMensagem").disabled = false;
						
					});
			})
		});
	</script>

<?php else: ?>

	<div id="concierge-container">
		<form id="concierge-form" method="POST">
			<h2>Configuração do Chatbot</h2>

			<!-- Nome do(a) Concierge -->
			<label for="concierge-name">Qual nome será dado a concierge?</label>
			<textarea id="concierge-name" name="concierge_name"></textarea>

			<!-- Objetivo do assistente -->
			<label for="concierge-objective">Qual o principal objetivo do seu assistente virtual?</label>
			<select id="concierge-objective" name="concierge_objective" onchange="toggleAdditionalField('concierge-objective', 'other-objective-container')">
				<option value="Atendimento ao cliente">Atendimento ao cliente</option>
				<option value="Suporte técnico">Suporte técnico</option>
				<option value="Conversão de vendas">Conversão de vendas</option>
				<option value="Guia de produtos">Guia de produtos</option>
				<option value="Orientação de serviços">Orientação de serviços</option>
				<option value="Automação de respostas frequentes">Automação de respostas frequentes</option>
				<option value="Captura de leads qualificação de leads">Captura de leads qualificação de leads</option>
				<option value="Agendamento de serviços">Agendamento de serviços</option>
				<option value="Rastreamento de pedidos">Rastreamento de pedidos</option>
				<option value="Resolução de problemas">Resolução de problemas</option>
				<option value="Acompanhamento pós-venda">Acompanhamento pós-venda</option>
				<option value="Onboarding de novos clientes">Onboarding de novos clientes</option>
				<option value="Suporte de autosserviço">Suporte de autosserviço</option>
				<option value="Monitoramento de dúvidas frequentes">Monitoramento de dúvidas frequentes</option>
				<option value="Melhoria da experiência do usuário">Melhoria da experiência do usuário</option>
				<option value="Educação">Educação</option>
				<option value="Outros">Outros</option>
			</select>
			<div id="other-objective-container" style="display: none; margin-top: 10px;">
				<label for="other-objective">Por favor, descreva:</label>
				<input type="text" id="other-objective" name="other_objective" placeholder="Digite o objetivo">
			</div>

			<!-- Tarefas do assistente -->
			<label for="concierge-tasks">Quais tarefas o assistente deve desempenhar?</label>
			<select id="concierge-tasks" name="concierge-tasks" onchange="toggleAdditionalField('concierge-tasks', 'other-tone-container')">
				<option value="Responder dúvidas frequentes">Responder dúvidas frequentes</option>
				<option value="Guiar o cliente em compras">Guiar o cliente em compras</option>
				<option value="Resolver problemas">Resolver problemas</option>
				<option value=" Direcionar para atendimento humano"> Direcionar para atendimento humano</option>
				<option value="Outros">Outros</option>
			</select>
			<div id="other-tone-container" style="display: none; margin-top: 10px;">
				<label for="other-tasks">Por favor, descreva:</label>
				<input type="text" id="other-tasks" name="other_tasks" placeholder="Digite outras tarefas">
			</div>

			<!-- Tom e personalidade -->
			<label for="concierge-tone">Como você gostaria que o assistente se posicionasse em relação ao tom e à personalidade?</label>
			<select id="concierge-tone" name="concierge_tone" onchange="toggleAdditionalField('concierge-tone', 'other-tone-container')">
				<option value="Amigável">Amigável</option>
				<option value="Formal">Formal</option>
				<option value="Direto">Direto</option>
				<option value="Engraçado">Engraçado</option>
				<option value="Sarcástico">Sarcástico</option>
				<option value="Técnico">Técnico</option>
				<option value="Descontraído">Descontraído</option>
				<option value="Outros">Outros</option>
			</select>
			<div id="other-tone-container" style="display: none; margin-top: 10px;">
				<label for="other-tone">Por favor, descreva:</label>
				<input type="text" id="other-tone" name="other_tone" placeholder="Digite o tom desejado">
			</div>

			<!-- Início da interação -->
			<label for="concierge-initiation">Você prefere que o assistente inicie a interação via chat:</label>
			<select id="concierge-initiation" name="concierge_initiation">
				<option value="Automaticamente">Automaticamente</option>
				<option value="Apenas quando solicitado">Apenas quando solicitado</option>
			</select>

			<!-- Tipo de abordagem -->
			<label for="concierge-approach">Qual tipo de abordagem prefere?</label>
			<select id="concierge-approach" name="concierge_approach">
				<option value="Mais direta">Mais direta</option>
				<option value="Com contexto adicional">Com contexto adicional: oferecendo mais detalhes e explicações para situar melhor o cliente</option>
			</select>

			<!-- Nível de formalidade -->
			<label for="formal-level">Se você escolheu um tom formal, qual o nível de formalidade que deseja?</label>
			<select id="formal-level" name="formal_level">
				<option value="Alta formalidade">Alta formalidade: uma linguagem mais polida e conservadora.</option>
				<option value="Moderada">Moderada: formal, mas sem excesso de rigidez.</option>
				<option value="Leve formalidade">Leve formalidade: formal, mas acessível e amigável.</option>
			</select>

			<!-- Prioridade de conteúdo -->
			<label for="concierge-content-priority">Em termos de conteúdo, qual aspecto você considera de maior importância?</label>
			<select id="concierge-content-priority" name="concierge_content_priority">
				<option value="Clareza">Clareza</option>
				<option value="Precisão">Precisão</option>
				<option value="Simpatia">Simpatia</option>
			</select>

			<!-- Característica da marca -->
			<label for="brand-characteristic">Qual característica da marca você gostaria de refletir na comunicação?</label>
			<select id="brand-characteristic" name="brand_characteristic" onchange="toggleBrandDetails()">
				<option value="Inovação">Inovação: uma abordagem moderna e vanguardista.</option>
				<option value="Acessibilidade">Acessibilidade: linguagem inclusiva e fácil de entender.</option>
				<option value="Exclusividade">Exclusividade: tom sofisticado, destacando a unicidade da marca.</option>
				<option value="Tradição/Experiência">Tradição/Experiência: ênfase na história e confiança da marca.</option>
			</select>

			<!-- Termos e expressões -->
			<label for="concierge-custom-terms">Há termos, expressões ou palavras que você gostaria de incluir ou evitar?</label>
			<textarea id="concierge-custom-terms" name="concierge_custom_terms"></textarea>

			<!-- Público principal -->
			<label for="concierge-audience">Quem é o público principal dessa comunicação?</label>
			<select id="concierge-audience" name="concierge_audience" onchange="toggleAdditionalField('concierge-audience', 'other-audience-container')">
				<option value="Jovens adultos">Jovens adultos</option>
				<option value="Adultos">Adultos</option>
				<option value="Terceira idade">Terceira idade</option>
				<option value="Estudantes">Estudantes</option>
				<option value="Profissionais de Tecnologia">Profissionais de Tecnologia</option>
				<option value="Executivos">Executivos</option>
				<option value="Outros">Outros</option>
			</select>
			<div id="other-audience-container" style="display: none; margin-top: 10px;">
				<label for="other-audience">Por favor, descreva:</label>
				<input type="text" id="other-audience" name="other_audience" placeholder="Descreva seu público">
			</div>

			<!-- Nível de conhecimento -->
			<label for="concierge-knowledge-level">Qual é o nível de conhecimento desse público sobre os produtos ou serviços oferecidos?</label>
			<select id="concierge-knowledge-level" name="concierge_knowledge_level">
				<option value="Básico">Básico: pouco conhecimento; requer explicações mais detalhadas.</option>
				<option value="Intermediário">Intermediário: familiaridade básica, mas pode precisar de alguns esclarecimentos.</option>
				<option value="Avançado">Avançado: público que já conhece bem o produto ou serviço.</option>
			</select>

			<!-- Campo de upload de arquivos -->
			<label for="concierge-upload">Envie arquivos para personalização:</label>
			<input type="file" id="concierge-upload" name="file">

			<!-- Botão de envio -->
			<button type="submit" id="concierge-test-chatbot">Testar Chatbot</button>
		</form>

		<div id="concierge-test-result"></div>
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

			// Adiciona um evento ao formulário no submit
			form.addEventListener("submit", (event) => {
				event.preventDefault(); // Previne o comportamento padrão de envio do formulário

				const formData = new FormData(form);
				formData.append('action', 'save_chatbot_options');

				resultDiv.innerHTML = "Enviando...";

				// Envia os dados usando fetch
				fetch(conciergeAjax.ajax_url, {
						method: "POST",
						body: formData,
					})
					.then((response) => response.json())
					.then((data) => {
						// Exibe a resposta no div
						resultDiv.innerHTML = `
                <strong>Resposta do Servidor:</strong> ${data.data}
            `
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