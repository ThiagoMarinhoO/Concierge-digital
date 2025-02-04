<h1 style="font-size: 36px; font-weight: 600; color: #222;">Gerenciador de Perguntas</h1>

<!-- Estilos adicionados dentro da tag <style> -->
<style>
    .toplevel_page_question-manager h1,
    h2 {
        color: #2c3e50;
    }

    /* Estilo do formulário */
    .toplevel_page_question-manager form {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .toplevel_page_question-manager form label {
        font-weight: bold;
        margin-bottom: 6px;
    }

    .toplevel_page_question-manager form input[type="text"],
    .toplevel_page_question-manager form input[type="radio"],
    .toplevel_page_question-manager form select {
        padding: 8px;
        margin-top: 4px;
        border-radius: 4px;
        border: 1px solid #ccc;
        width: 100%;
        margin-bottom: 12px;
    }

    .toplevel_page_question-manager form input[type="radio"] {
        width: auto;
    }

    /* Tabela de Perguntas */
    .toplevel_page_question-manager table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .toplevel_page_question-manager table,
    .toplevel_page_question-manager th,
    .toplevel_page_question-manager td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
    }

    .toplevel_page_question-manager th {
        background-color: #f2f2f2;
        font-weight: bold;
    }

    .toplevel_page_question-manager tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .toplevel_page_question-manager tr:hover {
        background-color: #f1f1f1;
    }

    .toplevel_page_question-manager .actions {
        text-align: center;
    }

    .toplevel_page_question-manager .actions a {
        color: #e74c3c;
        text-decoration: none;
        font-weight: bold;
    }

    .edit-btn {
        color: rgb(6, 54, 212) !important;
    }

    .toplevel_page_question-manager .actions a:hover {
        text-decoration: underline;
    }

    /* Mensagens de sucesso e erro */
    .toplevel_page_question-manager .updated {
        background-color: #d4edda;
        color: #155724;
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 4px;
    }

    .toplevel_page_question-manager .error {
        background-color: #f8d7da;
        color: #721c24;
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
</style>

<!-- Formulário de Adicionar Pergunta -->
<form method="post">
    <h2>Adicionar Pergunta</h2>
    <label for="question_title">Título:</label><br>
    <input type="text" id="question_title" name="question_title" required><br>

    <label>Tipo de Campo:</label><br>
    <input type="radio" id="option_file" name="field_type" value="file"
        onclick="document.getElementById('selection_options').style.display='none'" required>
    <label for="option_file">Arquivo</label><br>
    <input type="radio" id="option_text" name="field_type" value="text"
        onclick="document.getElementById('selection_options').style.display='none'" required>
    <label for="option_text">Texto</label><br>
    <input type="radio" id="option_selection" name="field_type" value="selection"
        onclick="document.getElementById('selection_options').style.display='block'" required>
    <label for="option_selection">Seleção</label><br>

    <div id="selection_options" style="display:none;">
        <label for="selection_options_input">Opções (separadas por vírgulas sem espaços):</label><br>
        <input type="text" id="selection_options_input" name="selection_options_input"><br>
    </div>

    <div style="display: flex; flex-direction: column; justify-content: center;">
        <label>Campo obrigatório?</label>
        <div>
            <input type="radio" id="required-yes" class="required-field" name="required_field" value="Sim" required>
            <label for="required-yes">Sim</label>
            <input type="radio" id="required-no" class="required-field" name="required_field" value="Não" required>
            <label for="required-no">Não</label>
        </div>
    </div>

    <label for="training_phrase">Frase de Treinamento:</label><br>
    <input type="text" id="training_phrase" name="training_phrase" required><br>

    <label for="question_categories">Categorias:</label><br>
    <select id="question_categories" name="question_categories[]" multiple>
        <?php foreach ($categories as $category): ?>
            <?php if ($category['title'] !== "Regras Gerais"): ?>
                <option value="<?php echo esc_attr($category['id']); ?>"><?php echo esc_html($category['title']); ?></option>
            <?php endif; ?>
        <?php endforeach; ?>
    </select><br>
    <button type="submit" name="add_question">Adicionar Pergunta</button>
</form>

<form method="post">
    <h2>Adicionar Categoria</h2>
    <label for="category_title">Título da Categoria:</label><br>
    <input type="text" id="category_title" name="category_title" required><br>
    <button type="submit" name="add_category">Adicionar Categoria</button>
</form>

<div style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; margin-top: 30px; background-color: white;">
    <h2 style="font-size: 28px; font-weight: 600; color: #222;">Categorias Existentes</h2>
    <table>
        <thead>
            <tr>
                <th>Título</th>
                <th>N° de perguntas</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $question = new Question();

            foreach ($categories as $category): ?>
                <tr>
                    <td><?php echo esc_html($category['title']); ?></td>
                    <td><?php echo esc_html(count($question->getQuestionsByCategory($category['title']))); ?></td>
                    <td class="actions">
                        <a href="javascript:void(0);"
                            onclick="deleteCategory(<?php echo esc_attr($category['id']); ?>)">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Tabela de Perguntas Existentes -->
<div style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; margin-top: 30px; background-color: white;">
    <h2 style="font-size: 28px; font-weight: 600; color: #222;">Perguntas Existentes</h2>

    <?php
    $quests = new Question();
    $questoes = $quests->getAllQuestions();

    $questions_by_category = [];

    foreach ($questoes as $question) {
        $categories = !empty($question['categories']) ? explode(',', $question['categories']) : [];
        foreach ($categories as $category) {
            $category = trim($category);
            if ($category !== 'Regras Gerais') {
                $questions_by_category[$category][] = $question;
            }
        }
    }

    if (!empty($questions_by_category)) {
        foreach ($questions_by_category as $category => $category_questions) {
            echo "<h4 style='font-size: 18px; font-weight: 500; color: #222;'>{$category}</h4>";
            echo "<table>
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Frase de treinamento</th>
                        <th>Tipo de campo</th>
                        <th>Opções</th>
                        <th>Categoria</th>
                        <th>Obrigatório?</th>
                        <th>Prioridade</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>";

            foreach ($category_questions as $question) {
                echo "<tr data-question-id='" . esc_attr($question['id']) . "'>
                        <td class='title'>" . esc_html($question['title']) . "</td>
                        <td class='training-phrase'>" . esc_html($question['training_phrase']) . "</td>
                        <td class='field-type'>" . esc_html($question['field_type']) . "</td>
                        <td class='options-select'>" . esc_html($question['options']) . "</td>
                        <td class='categories'>" . esc_html($question['categories']) . "</td>
                        <td class='requiredFields'>" . esc_html($question['required_field']) . "</td>
                        <td class='priorityFields'>" . esc_html($question['prioridade']) . "</td>
                        <td class='actions'>
                            <div style='display: flex; gap: 20px;'>
                                <a href='javascript:void(0);' class='edit-btn'>Editar</a>
                                <a href='javascript:void(0);' class='delete-btn' onclick='deleteQuestion(" . esc_attr($question['id']) . ")'>Excluir</a>
                            </div>
                        </td>
                    </tr>";
            }

            echo "</tbody>
            </table>";
        }
    } else {
        echo "<p style='text-align: center;'>Nenhuma Resposta cadastrada</p>";
    }
    ?>
</div>

<!-- Formulário de Adicionar Pergunta -->
<form id="fixed-question-form" style="margin-top: 24px;">
        <h2 style="font-size: 20px; font-weight: 600; color: #222;">Adicionar Pergunta à Categoria: Regras Gerais</h2>
        <label for="response">Resposta</label>
        <input type="text" id="response" name="response" required>
        <button type="submit">Adicionar Pergunta</button>
    </form>

<!-- Tabela de Regras Gerais Existentes -->
<div style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; margin-top: 30px; background-color: white;">
    <h2 style="font-size: 20px; font-weight: 600; color: #222;">Regras Gerais Existentes</h2>
    <table>
        <thead>
            <tr>
                <th>Resposta</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $question = new Question();
            $questions = $question->getQuestionsByCategory('Regras Gerais');

            foreach ($questions as $q): ?>
                <tr data-question-id="<?php echo esc_attr($q['id']); ?>">
                    <td class="question-response"><?php echo esc_html($q['response'] ?? 'Não definida'); ?></td>
                    <td class="actions">
                        <div style="display: flex; gap: 20px;">
                            <a href="javascript:void(0);" class="edit-btn">Editar</a>
                            <a href="javascript:void(0);"
                                onclick="deleteQuestion(<?php echo esc_attr($q['id']); ?>)">Excluir</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($questions)): ?>
                <tr>
                    <td colspan="2" style="text-align: center;">Nenhuma Resposta cadastrada</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Script para Excluir Pergunta -->
<script>
    var conciergeAjax = <?php echo json_encode(array('ajax_url' => admin_url('admin-ajax.php'))); ?>;

    function deleteQuestion(questionId) {
        if (confirm('Tem certeza que deseja excluir esta pergunta?')) {
            fetch(conciergeAjax.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'delete_question',
                        question_id: questionId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: `Pergunta excluída com sucesso!`,
                            icon: "success"
                        });
                        location.reload();
                    } else {
                        alert('Erro ao excluir a pergunta.');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao excluir a pergunta.');
                });
        }
    }

    function editQuestion(row) {
        const questionId = row.dataset.questionId;
        const titleCell = row.querySelector('.title');
        const trainingPhraseCell = row.querySelector('.training-phrase');
        const fieldTypeCell = row.querySelector('.field-type');
        const categoriesCell = row.querySelector('.categories');
        const optionsCell = row.querySelector('.options-select');
        const actionsCell = row.querySelector('.actions');
        const questionResponseCell = row.querySelector('.question-response');
        const requiredFieldCell = row.querySelector('.requiredFields');
        const priorityFieldCell = row.querySelector('.priorityFields');

        const title = titleCell ? titleCell.innerText : null;
        const training = trainingPhraseCell ? trainingPhraseCell.innerText : null;
        const fieldType = fieldTypeCell ? fieldTypeCell.innerText : null;
        const categories = categoriesCell ? categoriesCell.innerText : 'Regras Gerais';
        const questionResponse = questionResponseCell ? questionResponseCell.innerText : null;
        const requiredField = requiredFieldCell ? requiredFieldCell.innerText : null;
        const priorityField = priorityFieldCell ? priorityFieldCell.innerText : null;
        const options = optionsCell ? optionsCell.innerText : null;

        const originalData = {
            title: title,
            trainingPhrase: training,
            fieldType: fieldType,
            categories: categories,
            questionResponse: questionResponse,
            options: options ? JSON.parse(options) : [],
            requiredField: requiredField,
            priorityField: priorityField
        };

        if (titleCell) titleCell.innerHTML = `<input type="text" value="${originalData.title || ''}" />`;
        if (trainingPhraseCell) trainingPhraseCell.innerHTML = `<input type="text" value="${originalData.trainingPhrase || ''}" />`;
        if (fieldTypeCell) fieldTypeCell.innerHTML = `<input type="text" value="${originalData.fieldType || ''}" />`;
        if (categoriesCell) categoriesCell.innerHTML = `<input type="text" value="${originalData.categories || ''}" />`;
        if (questionResponseCell) questionResponseCell.innerHTML = `<input type="text" value="${originalData.questionResponse || ''}" />`;
        if (requiredFieldCell) requiredFieldCell.innerHTML = `<input type="text" value="${originalData.requiredField || ''}" />`;
        if (priorityFieldCell) priorityFieldCell.innerHTML = `<input type="number" value="${originalData.priorityField || ''}" />`;
        if (optionsCell) optionsCell.innerHTML = `<input type="text" value='${JSON.stringify(originalData.options)}' />`;

        actionsCell.innerHTML = `
        <a href="javascript:void(0);" class="save-btn">Salvar</a>
        <a href="javascript:void(0);" class="cancel-btn">Cancelar</a>
    `;

        // Botão Salvar
        actionsCell.querySelector('.save-btn').addEventListener('click', () => {
            const newData = {
                question_id: questionId,
                title: titleCell ? titleCell.querySelector('input').value : null,
                training_phrase: trainingPhraseCell ? trainingPhraseCell.querySelector('input').value : null,
                field_type: fieldTypeCell ? fieldTypeCell.querySelector('input').value : null,
                categories: categoriesCell ? categoriesCell.querySelector('input').value : 'Regras Gerais',
                questionResponse: questionResponseCell ? questionResponseCell.querySelector('input').value : null,
                options: optionsCell ? JSON.parse(optionsCell.querySelector('input').value || '[]') : [],
                requiredField: requiredFieldCell ? requiredFieldCell.querySelector('input').value : null,
                priorityField: priorityFieldCell ? priorityFieldCell.querySelector('input').value : null,
            };

            const bodyData = newData.categories == "Regras Gerais" ?
                new URLSearchParams({
                    action: 'edit_question',
                    question_id: newData.question_id,
                    responseQuestion: newData.questionResponse,
                    categories: newData.categories
                }) :
                new URLSearchParams({
                    action: 'edit_question',
                    question_id: newData.question_id,
                    title: newData.title,
                    training_phrase: newData.training_phrase,
                    field_type: newData.field_type,
                    categories: newData.categories,
                    options: JSON.stringify(newData.options),
                    responseQuestion: newData.questionResponse,
                    requiredField: newData.requiredField,
                    priorityField: newData.priorityField
                });

            fetch(conciergeAjax.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: bodyData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (titleCell) titleCell.innerText = newData.title;
                        if (trainingPhraseCell) trainingPhraseCell.innerText = newData.training_phrase;
                        if (fieldTypeCell) fieldTypeCell.innerText = newData.field_type;
                        if (categoriesCell) categoriesCell.innerText = newData.categories;
                        if (questionResponseCell) questionResponseCell.innerText = newData.questionResponse;
                        if (requiredFieldCell) requiredFieldCell.innerText = newData.requiredField;
                        if (priorityFieldCell) priorityFieldCell.innerText = newData.priorityField;
                        if (optionsCell) optionsCell.innerText = JSON.stringify(newData.options);

                        actionsCell.innerHTML = `
                        <a href="javascript:void(0);" class="edit-btn">Editar</a>
                        <a href="javascript:void(0);" class="delete-btn" onclick="deleteQuestion(${questionId})">Excluir</a>
                    `;
                        location.reload();
                    } else {
                        console.error(data);
                    }
                })
                .catch(error => console.error('Erro ao salvar:', error));
        });

        // Botão Cancelar
        actionsCell.querySelector('.cancel-btn').addEventListener('click', () => {
            if (titleCell) titleCell.innerText = originalData.title;
            if (trainingPhraseCell) trainingPhraseCell.innerText = originalData.trainingPhrase;
            if (fieldTypeCell) fieldTypeCell.innerText = originalData.fieldType;
            if (categoriesCell) categoriesCell.innerText = originalData.categories;
            if (questionResponseCell) questionResponseCell.innerText = originalData.questionResponse;
            if (requiredFieldCell) requiredFieldCell.innerText = originalData.requiredField;
            if (priorityFieldCell) priorityFieldCell.innerText = originalData.priorityField;
            if (optionsCell) optionsCell.innerText = JSON.stringify(originalData.options);
            actionsCell.innerHTML = `
            <a href="javascript:void(0);" class="edit-btn">Editar</a>
            <a href="javascript:void(0);" class="delete-btn" onclick="deleteQuestion(${questionId})">Excluir</a>
        `;

            // Reanexar o evento ao botão Editar
            actionsCell.querySelector('.edit-btn').addEventListener('click', function() {
                const row = this.closest('tr');
                editQuestion(row);
            });
        });
    }


    // Listener para o botão Editar
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            editQuestion(row);
        });
    });


    function deleteCategory(categoryId) {
        if (confirm('Tem certeza que deseja excluir esta categoria?')) {
            fetch(conciergeAjax.ajax_url, {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'delete_category',
                        category_id: categoryId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: `Categoria excluída com sucesso!`,
                            icon: "success"
                        });
                        location.reload();
                    } else {
                        alert('Erro ao excluir a categoria.');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao excluir a categoria.');
                });
        }
    }

    document.getElementById('fixed-question-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const responseInput = document.getElementById('response').value;

        if (!responseInput) {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'O campo de resposta é obrigatório.',
            });
            return;
        }

        // URL do AJAX
        const ajaxUrl = "<?php echo admin_url('admin-ajax.php'); ?>";

        // Fazendo o fetch
        fetch(ajaxUrl, {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'add_fixed_question',
                    response: responseInput
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso',
                        text: data.data.message,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload();
                        }
                    });

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: data.data.message,
                    });
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Ocorreu um erro. Tente novamente.',
                });
            });
    });
</script>