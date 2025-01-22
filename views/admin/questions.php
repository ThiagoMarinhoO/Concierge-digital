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
    <input type="radio" id="option_file" name="field_type" value="file" onclick="document.getElementById('selection_options').style.display='none'" required>
    <label for="option_file">Arquivo</label><br>
    <input type="radio" id="option_text" name="field_type" value="text" onclick="document.getElementById('selection_options').style.display='none'" required>
    <label for="option_text">Texto</label><br>
    <input type="radio" id="option_selection" name="field_type" value="selection" onclick="document.getElementById('selection_options').style.display='block'" required>
    <label for="option_selection">Seleção</label><br>

    <div id="selection_options" style="display:none;">
        <label for="selection_options_input">Opções (separadas por vírgulas):</label><br>
        <input type="text" id="selection_options_input" name="selection_options_input"><br>
    </div>

    <label for="training_phrase">Frase de Treinamento:</label><br>
    <input type="text" id="training_phrase" name="training_phrase" required><br>

    <label for="question_categories">Categorias:</label><br>
    <select id="question_categories" name="question_categories[]" multiple>
        <?php foreach ($categories as $category): ?>
            <option value="<?php echo esc_attr($category['id']); ?>"><?php echo esc_html($category['title']); ?></option>
        <?php endforeach; ?>
    </select><br>
    <button type="submit" name="add_question">Adicionar Pergunta</button>
</form>

<h2>Adicionar Categoria</h2>
<form method="post">';
    <label for="category_title">Título da Categoria:</label><br>
    <input type="text" id="category_title" name="category_title" required><br>
    <button type="submit" name="add_category">Adicionar Categoria</button>
</form>

<!-- Tabela de Perguntas Existentes -->
<h2>Perguntas Existentes</h2>
<table>
    <thead>
        <tr>
            <th>Título</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($questions as $question): ?>
            <tr>
                <td><?php echo esc_html($question['title']); ?></td>
                <td class="actions">
                        <a href="javascript:void(0);" onclick="deleteQuestion(<?php echo esc_attr($question['id']); ?>)">Excluir</a>
                </td>
            </tr>
        <?php endforeach; ?>
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
                    confirm('Pergunta excluída com sucesso.');
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
</script>