<h1 style="font-size: 36px; font-weight: 600; color: #222;">Assistentes</h1>

<!-- Estilos adicionados dentro da tag <style> -->
<style>
    .toplevel_page_assistants-manager h1,
    h2 {
        color: #2c3e50;
    }

    /* Estilo do formulário */
    .toplevel_page_assistants-manager form {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .toplevel_page_assistants-manager form label {
        font-weight: bold;
        margin-bottom: 6px;
    }

    .toplevel_page_assistants-manager form input[type="text"],
    .toplevel_page_assistants-manager form input[type="radio"],
    .toplevel_page_assistants-manager form select {
        padding: 8px;
        margin-top: 4px;
        border-radius: 4px;
        border: 1px solid #ccc;
        width: 100%;
        margin-bottom: 12px;
    }

    .toplevel_page_assistants-manager form input[type="radio"] {
        width: auto;
    }

    /* Tabela de Perguntas */
    .toplevel_page_assistants-manager table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .toplevel_page_assistants-manager table,
    .toplevel_page_assistants-manager th,
    .toplevel_page_assistants-manager td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
    }

    .toplevel_page_assistants-manager th {
        background-color: #f2f2f2;
        font-weight: bold;
    }

    .toplevel_page_assistants-manager tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .toplevel_page_assistants-manager tr:hover {
        background-color: #f1f1f1;
    }

    .toplevel_page_assistants-manager .actions {
        text-align: center;
    }

    .toplevel_page_assistants-manager .actions a {
        color: #e74c3c;
        text-decoration: none;
        font-weight: bold;
    }


    .toplevel_page_assistants-manager td.actions button {
        all: unset;
        text-decoration: underline;
        color: #721c24;
        font-size: 14px;
        font-weight: 500;
    }
</style>

<!-- Tabela de Regras Gerais Existentes -->
<div style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; margin-top: 30px; background-color: white;">
    <table>
        <thead>
            <tr>
                <th>Assistente</th>
                <th>Nome do assistente</th>
                <th>Usuario</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $assistant = new Chatbot();
            $assistants = $assistant->getAllChatbotsInDB();

            // var_dump($assistants);

            foreach ($assistants as $a): ?>
                <?php
                    $assistant_meta = json_decode($a->assistant, true);
                ?>
                <tr data-question-id="<?php echo esc_attr($a->id); ?>">
                    <td class="assistant-id"><?php echo esc_html($a->id); ?></td>
                    <td class="assistant-name"><?php echo esc_html($assistant_meta['name']); ?></td>
                    <?php
                        $assistant_user = get_userdata($a->user_id);
                    ?>
                    <td class="assistant-userId"><?php echo $assistant_user->user_login; ?></td>
                    <td class="actions">
                        <div style="display: flex; gap: 20px;">
                            <button type="button" class="deleteAssistant"
                                onclick="deleteAssistant('<?php echo esc_js($a->id); ?>')">Excluir</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($assistants)): ?>
                <tr>
                    <td colspan="4" style="text-align: center;">Nenhuma assistente cadastrado</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Script para Excluir Pergunta -->
<script>
    var conciergeAjax = <?php echo json_encode(array('ajax_url' => admin_url('admin-ajax.php'))); ?>;

    async function deleteAssistant(assistantId) {
        if (confirm('Tem certeza que deseja excluir este assistente ?')) {

            try {
                Swal.fire({
                    title: 'Excluindo assistente...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const response = await fetch(conciergeAjax.ajax_url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'delete_assistant',
                            assistant_id: assistantId
                        })
                    });

                const { data } = await response.json();

                if (data.deleted) {
                    Swal.fire({
                        title: `Assistente excluído com sucesso!`,
                        icon: "success"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Erro ao excluir o assistente.',
                        icon: 'error'
                    });
                }

            } catch (e) {
                console.log('Erro: ', e);
                Swal.fire({
                    title: 'Erro ao excluir o assistente.',
                    icon: 'error'
                });
            }


            // fetch(conciergeAjax.ajax_url, {
            //         method: 'POST',
            //         headers: {
            //             'Content-Type': 'application/x-www-form-urlencoded'
            //         },
            //         body: new URLSearchParams({
            //             action: 'delete_question',
            //             question_id: questionId
            //         })
            //     })
            //     .then(response => response.json())
            //     .then(data => {
            //         if (data.success) {
            //             Swal.fire({
            //                 title: `Pergunta excluída com sucesso!`,
            //                 icon: "success"
            //             });
            //             location.reload();
            //         } else {
            //             alert('Erro ao excluir a pergunta.');
            //         }
            //     })
            //     .catch(error => {
            //         console.error('Erro:', error);
            //         alert('Erro ao excluir a pergunta.');
            //     });
        }
    }

    // function deleteCategory(categoryId) {
    //     if (confirm('Tem certeza que deseja excluir esta categoria?')) {
    //         fetch(conciergeAjax.ajax_url, {
    //                 method: 'POST',
    //                 body: new URLSearchParams({
    //                     action: 'delete_category',
    //                     category_id: categoryId
    //                 })
    //             })
    //             .then(response => response.json())
    //             .then(data => {
    //                 if (data.success) {
    //                     Swal.fire({
    //                         title: `Categoria excluída com sucesso!`,
    //                         icon: "success"
    //                     });
    //                     location.reload();
    //                 } else {
    //                     alert('Erro ao excluir a categoria.');
    //                 }
    //             })
    //             .catch(error => {
    //                 console.error('Erro:', error);
    //                 alert('Erro ao excluir a categoria.');
    //             });
    //     }
    // }

    // document.getElementById('fixed-question-form').addEventListener('submit', function(e) {
    //     e.preventDefault();

    //     const responseInput = document.getElementById('response').value;

    //     if (!responseInput) {
    //         Swal.fire({
    //             icon: 'error',
    //             title: 'Erro',
    //             text: 'O campo de resposta é obrigatório.',
    //         });
    //         return;
    //     }

    //     // URL do AJAX
    //     const ajaxUrl = "<?php echo admin_url('admin-ajax.php'); ?>";

    //     // Fazendo o fetch
    //     fetch(ajaxUrl, {
    //             method: 'POST',
    //             body: new URLSearchParams({
    //                 action: 'add_fixed_question',
    //                 response: responseInput
    //             })
    //         })
    //         .then(response => response.json())
    //         .then(data => {
    //             if (data.success) {
    //                 Swal.fire({
    //                     icon: 'success',
    //                     title: 'Sucesso',
    //                     text: data.data.message,
    //                 }).then((result) => {
    //                     if (result.isConfirmed) {
    //                         location.reload();
    //                     }
    //                 });

    //             } else {
    //                 Swal.fire({
    //                     icon: 'error',
    //                     title: 'Erro',
    //                     text: data.data.message,
    //                 });
    //             }
    //         })
    //         .catch(error => {
    //             console.error('Erro:', error);
    //             Swal.fire({
    //                 icon: 'error',
    //                 title: 'Erro',
    //                 text: 'Ocorreu um erro. Tente novamente.',
    //             });
    //         });
    // });
</script>