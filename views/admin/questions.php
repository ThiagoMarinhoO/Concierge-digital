<div class="wrap">
    <h1 class="text-5xl text-green-500">Gerenciar Perguntas do Chatbot</h1>
    <form method="post">
        <table class="form-table">
            <tr>
                <th><label for="question_title">Título da Pergunta</label></th>
                <td><input type="text" id="question_title" name="question_title" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="training_phrase">Frase de Treinamento</label></th>
                <td><textarea id="training_phrase" name="training_phrase" rows="5" class="large-text" required></textarea></td>
            </tr>
            <tr>
                <th><label for="question_options">Opções de Resposta (opcional)</label></th>
                <td>
                    <div id="options-wrapper">
                        <input type="text" name="question_options[]" class="regular-text">
                    </div>
                    <button type="button" class="button" id="add-option-button">Adicionar Opção</button>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="Salvar Pergunta">
        </p>
    </form>
    <script>
        document.getElementById('add-option-button').addEventListener('click', function() {
            const wrapper = document.getElementById('options-wrapper');
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'question_options[]';
            input.className = 'regular-text';
            wrapper.appendChild(input);
        });
    </script>
</div>