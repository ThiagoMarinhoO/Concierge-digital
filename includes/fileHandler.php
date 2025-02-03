<?php
add_action('wp_ajax_upload_files_to_media_library', 'handle_file_upload');

function handle_file_upload() {

	if (empty($_FILES['files'])) {
		wp_send_json_success(['message' => 'Nenhum arquivo enviado']);
		return;
	}

	$uploaded_urls = [];

	foreach ($_FILES['files']['name'] as $index => $name) {
		// Montar corretamente cada arquivo
		$file = [
			'name'     => $_FILES['files']['name'][$index],
			'type'     => $_FILES['files']['type'][$index],
			'tmp_name' => $_FILES['files']['tmp_name'][$index],
			'error'    => $_FILES['files']['error'][$index],
			'size'     => $_FILES['files']['size'][$index],
		];

		// Função segura para manusear o upload
		$upload = wp_handle_sideload($file, ['test_form' => false]);

		if (!isset($upload['error'])) {
			$file_url = $upload['url'];

			// Inserir na biblioteca de mídia
			$attachment = [
				'post_mime_type' => $upload['type'],
				'post_title'     => sanitize_file_name($file['name']),
				'post_content'   => '',
				'post_status'    => 'inherit',
			];

			$attachment_id = wp_insert_attachment($attachment, $upload['file']);

			if (!is_wp_error($attachment_id)) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $upload['file']));
				$uploaded_urls[] = wp_get_attachment_url($attachment_id);
			} else {
				wp_send_json_error(['message' => 'Erro ao inserir anexo na biblioteca de mídia']);
			}
		} else {
			wp_send_json_error(['message' => $upload['error']]);
		}
	}

	wp_send_json_success(['urls' => $uploaded_urls]);
}