<?php

class ChatPage
{
    public static function render()
    {
        // Lê filtros via GET
        $filters = [
            'assistant_id' => $_GET['assistant_id'] ?? '',
            'thread_id'    => $_GET['thread_id'] ?? '',
            'name'         => $_GET['name'] ?? '',
            'phone'        => $_GET['phone'] ?? '',
            'message'      => $_GET['message'] ?? '',
        ];

        // Paginação
        $currentPage = max(1, intval($_GET['paged'] ?? 1));
        $perPage = 50;
        $offset = ($currentPage - 1) * $perPage;

        // Busca dados
        $result = Message::findAllWithFilters(array_filter($filters), $perPage, $offset);
        $messages = $result['messages'];
        $total = $result['total'];
        $totalPages = ceil($total / $perPage);

        // Gera base da URL de filtros (sem duplicar `paged`)
        $baseUrl = admin_url('admin.php?page=chat');
        $queryString = http_build_query(array_merge($filters));
        $baseUrlWithFilters = $queryString ? "$baseUrl&$queryString" : $baseUrl;

        ?>
        <div class="wrap">
            <h1>Mensagens</h1>

            <form method="get" style="margin-bottom: 20px;">
                <input type="hidden" name="page" value="chat" />

                <input type="text" name="assistant_id" placeholder="Assistant ID" value="<?php echo esc_attr($filters['assistant_id']); ?>" />
                <input type="text" name="thread_id" placeholder="Thread ID" value="<?php echo esc_attr($filters['thread_id']); ?>" />
                <input type="text" name="name" placeholder="Nome" value="<?php echo esc_attr($filters['name']); ?>" />
                <input type="text" name="phone" placeholder="Telefone" value="<?php echo esc_attr($filters['phone']); ?>" />
                <input type="text" name="message" placeholder="Trecho da Mensagem" value="<?php echo esc_attr($filters['message']); ?>" />

                <button type="submit" class="button">Filtrar</button>
            </form>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>Mensagem</th>
                        <th>Thread ID</th>
                        <th>Assistant ID</th>
                        <th>Do Assistente?</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($messages)): ?>
                        <tr><td colspan="7">Nenhuma mensagem encontrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <tr>
                                <td><?php echo esc_html($msg->getDateTime()->format('d/m/Y H:i')); ?></td>
                                <td><?php echo esc_html($msg->getName() ?? '-'); ?></td>
                                <td><?php echo esc_html($msg->getPhone() ?? '-'); ?></td>
                                <td><?php echo esc_html($msg->getMessage()); ?></td>
                                <td><?php echo esc_html($msg->getThreadId() ?? '-'); ?></td>
                                <td><?php echo esc_html($msg->getAssistantId()); ?></td>
                                <td><?php echo $msg->getFromMe() ? 'Sim' : 'Não'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
                <div style="margin-top: 20px;">
                    <?php if ($currentPage > 1): ?>
                        <a href="<?php echo esc_url($baseUrlWithFilters . '&paged=' . ($currentPage - 1)); ?>" class="button">« Anterior</a>
                    <?php endif; ?>

                    <span style="margin: 0 10px;">Página <?php echo $currentPage; ?> de <?php echo $totalPages; ?></span>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="<?php echo esc_url($baseUrlWithFilters . '&paged=' . ($currentPage + 1)); ?>" class="button">Próximo »</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

add_action('admin_menu', function () {
    add_menu_page(
        'Banco de Conversas',
        'Conversas',
        'manage_options',
        'chat',
        [ChatPage::class, 'render'],
        'dashicons-text',
        50
    );
});