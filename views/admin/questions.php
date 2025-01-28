<h1>Gerenciador de Perguntas</h1>

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
<h2>Adicionar Pergunta</h2>
<form method="post">
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

    <label for="training_phrase">Frase de Treinamento:</label><br>
    <input type="text" id="training_phrase" name="training_phrase" required><br>

    <label for="question_categories">Categorias:</label><br>
    <select id="question_categories" name="question_categories[]" multiple>
        <?php foreach ($categories as $category): ?>
            <?php if ($category['title'] !== "Perguntas Fixas (criação do lead)"): ?>
                <option value="<?php echo esc_attr($category['id']); ?>"><?php echo esc_html($category['title']); ?></option>
            <?php endif; ?>
        <?php endforeach; ?>
    </select><br>
    <button type="submit" name="add_question">Adicionar Pergunta</button>
</form>

<h2>Adicionar Categoria</h2>
<form method="post">
    <label for="category_title">Título da Categoria:</label><br>
    <input type="text" id="category_title" name="category_title" required><br>
    <button type="submit" name="add_category">Adicionar Categoria</button>
</form>

<!-- Tabela de Categorias Existentes -->
<h2>Categorias Existentes</h2>
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

<!-- Tabela de Perguntas Existentes -->
<h2>Perguntas Existentes</h2>
<table>
    <thead>
        <tr>
            <th>Título</th>
            <th>Frase de treinamento</th>
            <th>Tipo de campo</th>
            <th>Categoria</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($questions as $question): ?>
            <?php if ($question['categories'] !== 'Perguntas Fixas (criação do lead)'): ?>
                <tr data-question-id="<?php echo esc_attr($question['id']); ?>">
                    <td class="title"><?php echo esc_html($question['title']); ?></td>
                    <td class="training-phrase"><?php echo esc_html($question['training_phrase']); ?></td>
                    <td class="field-type"><?php echo esc_html($question['field_type']); ?></td>
                    <td class="categories"><?php echo esc_html($question['categories']); ?></td>
                    <td class="actions">
                        <div style="display: flex; gap: 20px;">
                            <a href="javascript:void(0);" class="edit-btn">Editar</a>
                            <a href="javascript:void(0);" class="delete-btn"
                                onclick="deleteQuestion(<?php echo esc_attr($question['id']); ?>)">Excluir</a>
                        </div>
                    </td>
                </tr>
            <?php endif ?>
        <?php endforeach; ?>
        <?php if (empty($questions)): ?>
            <tr>
                <td colspan="2" style="text-align: center;">Nenhuma Resposta cadastrada</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Formulário de Adicionar Pergunta -->
<h2>Adicionar Pergunta à Categoria: Perguntas Fixas (criação do lead)</h2>
<form id="fixed-question-form">
    <label for="response">Resposta</label>
    <input type="text" id="response" name="response" required>
    <button type="submit">Adicionar Pergunta</button>
</form>

<!-- Tabela de Perguntas Fixas Existentes -->
<h2>Perguntas Fixas Existentes</h2>
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
        $questions = $question->getQuestionsByCategory('Perguntas Fixas (criação do lead)');

        foreach ($questions as $q): ?>
            <tr>
                <td><?php echo esc_html($q['response'] ?? 'Não definida'); ?></td>
                <td class="actions">
                    <a href="javascript:void(0);" onclick="deleteQuestion(<?php echo esc_attr($q['id']); ?>)">Excluir</a>
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
        const actionsCell = row.querySelector('.actions');

        // Armazena os valores originais
        const originalData = {
            title: titleCell.innerText,
            trainingPhrase: trainingPhraseCell.innerText,
            fieldType: fieldTypeCell.innerText,
            categories: categoriesCell.innerText
        };

        // Torna os campos editáveis
        titleCell.innerHTML = `<input type="text" value="${originalData.title}" />`;
        trainingPhraseCell.innerHTML = `<input type="text" value="${originalData.trainingPhrase}" />`;
        fieldTypeCell.innerHTML = `<input type="text" value="${originalData.fieldType}" />`;
        categoriesCell.innerHTML = `<input type="text" value="${originalData.categories}" />`;

        // Adiciona botões Salvar e Cancelar
        actionsCell.innerHTML = `
            <a href="javascript:void(0);" class="save-btn">Salvar</a>
            <a href="javascript:void(0);" class="cancel-btn">Cancelar</a>
        `;

        // Botão Salvar
        actionsCell.querySelector('.save-btn').addEventListener('click', () => {
            const newData = {
                question_id: questionId,
                title: titleCell.querySelector('input').value,
                training_phrase: trainingPhraseCell.querySelector('input').value,
                field_type: fieldTypeCell.querySelector('input').value,
                categories: categoriesCell.querySelector('input').value
            };

            fetch(conciergeAjax.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'edit_question',
                    question_id: newData.question_id,
                    title: newData.title,
                    training_phrase: newData.training_phrase,
                    field_type: newData.field_type,
                    categories: newData.categories
                })
            }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        titleCell.innerText = newData.title;
                        trainingPhraseCell.innerText = newData.training_phrase;
                        fieldTypeCell.innerText = newData.field_type;
                        categoriesCell.innerText = newData.categories;
                        actionsCell.innerHTML = `
                        <a href="javascript:void(0);" class="edit-btn">Editar</a>
                        <a href="javascript:void(0);" class="delete-btn" onclick="deleteQuestion(${questionId})">Excluir</a>
                    `;
                        location.reload();
                    } else {
                        console.log(data)
                    }
                })
                .catch(error => console.error('Erro ao salvar:', error));
        });

        // Botão Cancelar
        actionsCell.querySelector('.cancel-btn').addEventListener('click', () => {
            titleCell.innerText = originalData.title;
            trainingPhraseCell.innerText = originalData.trainingPhrase;
            fieldTypeCell.innerText = originalData.fieldType;
            categoriesCell.innerText = originalData.categories;
            actionsCell.innerHTML = `
                <a href="javascript:void(0);" class="edit-btn">Editar</a>
                <a href="javascript:void(0);" class="delete-btn" onclick="deleteQuestion(${questionId})">Excluir</a>
            `;
        });
    }

    // Listener para o botão Editar
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function () {
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

    document.getElementById('fixed-question-form').addEventListener('submit', function (e) {
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