<?php
add_action('wp_ajax_upload_files_to_media_library', 'handle_file_upload');

function handle_file_upload()
{
    if (empty($_FILES['files'])) {
        wp_send_json_error(['message' => 'Nenhum arquivo enviado']);
    }

    $assistant_id = isset($_POST['assistant_id']) ? sanitize_text_field($_POST['assistant_id']) : null;
    $assistant_id = (!empty($assistant_id) && $assistant_id !== 'undefined' && $assistant_id !== 'null') ? $assistant_id : null;
    $assistant_name = isset($_POST['assistant_name']) ? sanitize_text_field($_POST['assistant_name']) : null;
    if (!$assistant_name) {
        wp_send_json_error(['message' => 'Por favor, informe o nome do assistente antes de enviar arquivos.']);
    }

    $vector_store_label = "Vector Store para {$assistant_name}";
    global $wpdb;

    $table_stores = $wpdb->prefix . 'vector_stores';

    $vector_store_id = null;

    if ($assistant_id) {
        error_log("Buscando vector store para assistente ID: $assistant_id");

        $vector_store_id = null;

        $vector_store = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_stores WHERE assistant_id = %s",
            $assistant_id
        ));

        $vector_store_id = $vector_store ? $vector_store->vector_store_id : null;


        if (!$vector_store) {
            error_log("Criando vector store para assistente ID: $assistant_id");
            // Criar novo
            $created = StorageController::createVectorStore($vector_store_label);
            if (!$created || empty($created['id'])) {
                wp_send_json_error(['message' => 'Falha ao criar vector store.']);
            }
            
            $vector_store_id = $created['id'];
            
            $wpdb->insert($table_stores, [
                'name' => $vector_store_label,
                'assistant_id' => $assistant_id,
                'vector_store_id' => $vector_store_id
            ]);
        }
    } else {
        error_log("Buscando vector store para Name: $vector_store_label");
        $vector_store = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_stores WHERE name = %s",
            $vector_store_label
        ));

        if ($vector_store) {
            $vector_store_id = $vector_store->vector_store_id;
        } else {
            error_log("Criando vector store para Name: $vector_store_label");
            // Criar novo
            $created = StorageController::createVectorStore($vector_store_label);
            if (!$created || empty($created['id'])) {
                wp_send_json_error(['message' => 'Falha ao criar vector store.']);
            }
            $vector_store_id = $created['id'];
            $wpdb->insert($table_stores, [
                'name' => $vector_store_label,
                'vector_store_id' => $vector_store_id
            ]);
        }
    }


    $uploaded_urls = [];
    $table_files = $wpdb->prefix . 'vector_files';

    $question_ids = isset($_POST['questionIds']) ? $_POST['questionIds'] : [];

    foreach ($_FILES['files']['name'] as $index => $name) {
        $file = [
            'name'     => $_FILES['files']['name'][$index],
            'type'     => $_FILES['files']['type'][$index],
            'tmp_name' => $_FILES['files']['tmp_name'][$index],
            'error'    => $_FILES['files']['error'][$index],
            'size'     => $_FILES['files']['size'][$index],
        ];

        $upload = wp_handle_sideload($file, ['test_form' => false]);
        if (isset($upload['error'])) {
            wp_send_json_error(['message' => $upload['error']]);
        }

        $allowed_types = ['text/plain', 'application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

        if (in_array($file['type'], $allowed_types)) {

            if (!$vector_store_id) {
                wp_send_json_error(['message' => 'Vector store não encontrado ou criado.']);
            }

            // 🔍 VERIFICAR SE ARQUIVO COM MESMO NOME JÁ EXISTE NO VECTOR STORE
            // Remove sufixos do WordPress (-1, -2, etc) para comparação
            $original_filename = $file['name'];
            $filename_base = pathinfo($original_filename, PATHINFO_FILENAME);
            $filename_ext = pathinfo($original_filename, PATHINFO_EXTENSION);
            
            // Remove possíveis sufixos numéricos do WordPress (ex: "arquivo-1" -> "arquivo")
            $filename_clean = preg_replace('/-\d+$/', '', $filename_base);
            
            $existing_file = $wpdb->get_row($wpdb->prepare(
                "SELECT file_id, file_url FROM {$table_files} 
                 WHERE vector_store_id = %s 
                 AND (file_url LIKE %s OR file_url LIKE %s)",
                $vector_store_id,
                '%/' . $filename_base . '.' . $filename_ext,
                '%/' . $filename_clean . '.' . $filename_ext
            ));

            if ($existing_file) {
                // Arquivo já existe - pular upload e usar ID existente
                error_log("⏭️ SKIP: Arquivo já existe no Vector Store: {$existing_file->file_url} - file_id={$existing_file->file_id}");
                $file_id = $existing_file->file_id;
                
                // Retornar URL existente em vez da nova
                $uploaded_urls[] = [
                    'url' => $existing_file->file_url,
                    'id'  => isset($question_ids[$index]) ? $question_ids[$index] : null,
                    'file_id' => $file_id,
                    'skipped' => true
                ];
                continue; // Pular para próximo arquivo
            }

            // 2️⃣ Upload para OpenAI (arquivo novo)
            $fileResponse = StorageController::uploadFile($upload['file']);
            if (!$fileResponse || empty($fileResponse['id'])) {
                wp_send_json_error(['message' => 'Erro ao enviar arquivo para o vector store']);
            }

            $file_id = $fileResponse['id'];

            // 3️⃣ Adicionar ao vector store
            StorageController::createVectorStoreFile($vector_store_id, $file_id);

            // 4️⃣ Registrar no banco
            $wpdb->insert($table_files, [
                'file_id' => $file_id,
                'vector_store_id' => $vector_store_id,
                'file_url' => $upload['url']
            ]);
            
            error_log("✅ Novo arquivo adicionado ao Vector Store: {$upload['url']} - file_id={$file_id}");
        }

        $uploaded_urls[] = [
            'url' => $upload['url'],
            'id'  => isset($question_ids[$index]) ? $question_ids[$index] : null,
            'file_id' => $file_id
        ];
    }

    wp_send_json_success([
        'message' => 'Arquivos enviados e vinculados ao Vector Store com sucesso!',
        'vector_store_id' => $vector_store_id,
        'urls' => $uploaded_urls
    ]);
}

add_action('wp_ajax_delete_vector_store_file', 'delete_vector_store_file');

function delete_vector_store_file()
{
    error_log('Deletando arquivo do vector store...');

    global $wpdb;
    $table_files = $wpdb->prefix . 'vector_files';

    if (empty($_POST['file_url'])) {
        wp_send_json_error(['message' => 'Nenhum file_id fornecido']);
    }

    $file_url = sanitize_text_field($_POST['file_url']);
    error_log("Deletando arquivo do vector store: $file_url");

    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_files} WHERE file_url = %s",
        $file_url
    ));
    error_log(print_r($row, true));

    if (!$row) {
        error_log("❌ Arquivo não encontrado na tabela wp_vector_files para URL: $file_url");
        wp_send_json_error(['message' => 'Arquivo não encontrado no banco de dados. Pode ter sido enviado antes do Vector Store ser configurado.']);
    }

    $file_id = $row->file_id;
    error_log("Deletando arquivo do vector store: $file_id");

    $res = StorageController::deleteVectorStoreFile($row->vector_store_id, $file_id);
    error_log(print_r($res, true));

    $deleted = $wpdb->delete($table_files, ['file_url' => $file_url]);
    error_log(print_r($deleted, true));

    if ($deleted === false) {
        wp_send_json_error(['message' => 'Erro ao deletar o arquivo do banco de dados']);
    } elseif ($deleted === 0) {
        wp_send_json_error(['message' => 'Nenhum arquivo encontrado com o file_url fornecido']);
    } else {
        wp_send_json_success(['message' => 'Arquivo deletado com sucesso']);
    }
}
