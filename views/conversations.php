<?php
require_once CONCIERGE_DIGITAL_PATH . '/lib/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class ConversationsComponent
{
    public function __construct() {}

    public static function getConversations(
        string $assistantId,
        ?string $instanceName,
        int $perPage = 10,
        int $page = 1,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $conversations = [];

        // ====== Web ======
        $webResult = Message::findAllWithFilters(['assistant_id' => $assistantId], 500, 0);
        $webMessages = $webResult['messages'];

        $groupedWeb = [];
        foreach ($webMessages as $msg) {
            $groupedWeb[$msg->getThreadId()][] = $msg;
        }

        foreach ($groupedWeb as $threadId => $msgs) {
            $first = reset($msgs);
            $last = end($msgs);

            $conversations[] = [
                'id'       => $threadId,
                'canal'    => 'web',
                'status'   => 'finalizada',
                'lead'     => [
                    'nome'     => $first->getName() ?? null,
                    'telefone' => $first->getPhone() ?? null,
                ],
                'titulo'   => $first->getMessage(),
                'dataHora' => $last->getDateTime()->format('Y/m/d H:i:s'),

                // Inclui as mensagens no resultado
                'messages' => array_map(function ($m) {
                    return [
                        'from_me' => $m->getFromMe(),
                        'text'    => $m->getMessage(),
                        'date'    => $m->getDateTime()->format('d/m/Y H:i:s'),
                    ];
                }, $msgs),
            ];
        }

        // ====== WhatsApp ======
        if ($instanceName) {
            $waMessages = WhatsappMessage::findByInstanceName($instanceName);

            $groupedWa = [];
            foreach ($waMessages as $msg) {
                // Agrupando por threadId em vez de remoteJid
                $groupedWa[$msg->threadId][] = $msg;
            }

            foreach ($groupedWa as $threadId => $msgs) {
                $first = reset($msgs);
                $last = end($msgs);

                // Ajusta dataHora para horário de Brasília
                $dateTimeBr = clone $last->dateTime;
                $dateTimeBr->setTimezone(new DateTimeZone('America/Sao_Paulo'));

                $conversations[] = [
                    'id'       => $threadId,
                    'canal'    => 'whatsapp',
                    'status'   => 'finalizada',
                    'lead'     => [
                        'nome'     => $first->pushName ?? null,
                        'telefone' => $first->remoteJid ?? null,
                    ],
                    'titulo'   => $first->message,
                    'dataHora' => $dateTimeBr->format('Y/m/d H:i:s'),

                    // Inclui mensagens
                    'messages' => array_map(function ($m) {
                        $dtBr = clone $m->dateTime;
                        $dtBr->setTimezone(new DateTimeZone('America/Sao_Paulo'));
                        return [
                            'from_me' => $m->fromMe ?? 0,
                            'text'    => $m->message,
                            'date'    => $dtBr->format('d/m/Y H:i:s'),
                        ];
                    }, $msgs),
                ];
            }
        }

        if ($startDate && $endDate) {
            $start = strtotime($startDate . " 00:00:00");
            $end   = strtotime($endDate . " 23:59:59");

            $conversations = array_filter($conversations, function ($conv) use ($start, $end) {
                $ts = strtotime($conv['dataHora']);
                return $ts >= $start && $ts <= $end;
            });
        }

        // Ordenar por data mais recente
        usort($conversations, fn($a, $b) => strtotime($b['dataHora']) <=> strtotime($a['dataHora']));

        // return $conversations;

        // Paginação
        $total = count($conversations);
        $offset = ($page - 1) * $perPage;
        $paginated = array_slice($conversations, $offset, $perPage);

        return [
            'data' => $paginated,
            'total' => $total,
            'perPage' => $perPage,
            'page' => $page,
            'pages' => ceil($total / $perPage),
        ];
    }

    public static function download()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error('Você precisa estar logado para acessar esta funcionalidade.', 403);
            return;
        }

        if (!isset($_POST['conversationId']) || empty($_POST['conversationId'])) {
            wp_send_json_error('Dados da conversa não fornecidos.', 400);
            return;
        }

        $messages = json_decode(stripslashes($_POST['messages']), true);

        if (empty($messages)) {
            wp_send_json_error('Nenhuma mensagem encontrada.', 404);
            return;
        }

        // Monta HTML
        $html = '<html><head>
            <style>
                body { font-family: Roboto, sans-serif; font-size: 12px; }
                .chat-container { width: 100%; }
                .msg { margin: 8px 0; padding: 10px; border-radius: 10px; max-width: 70%; width: auto;}
                .msgText { white-space: pre-wrap; }
                .sent { background: #DCF8C6; margin-left: auto; text-align: right; }
                .received { background: #FFF; border: 1px solid #ccc; text-align: left; }
                .time { display: block; font-size: 10px; color: #666; margin-top: 3px; }
            </style>
        </head><body><div class="chat-container">';

        foreach ($messages as $msg) {
            error_log(print_r($msg, true));
            $class = $msg['from_me'] ? 'sent' : 'received';
            $text  = htmlspecialchars($msg['text']);
            $dt = DateTime::createFromFormat(
                'd/m/Y H:i:s',
                $msg['date'],
                new DateTimeZone('UTC')
            );

            if (!$dt) {
                // Loga erro de parsing
                error_log("Erro ao parsear data: {$msg['date']}");
                continue;
            }

            $dt->setTimezone(new DateTimeZone('America/Sao_Paulo'));
            $time = $dt->format("d/m/Y H:i");

            $html .= "<div class='msg {$class}'>
                    <span class='msgText'>{$text}</span>
                    <span class='time'>{$time}</span>
                  </div>";
        }

        $html .= '</div></body></html>';

        // Configura Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Força download
        $dompdf->stream("conversation-{$_POST['conversationId']}.pdf", ["Attachment" => true]);
        exit;
    }

    public static function render()
    {
        if (!is_user_logged_in()) {
            return '<p class="text-white font-bold">Você precisa estar logado para acessar esta página.</p>';
        }

        ob_start();
        ?>
        <div id="conversations-wrapper" class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <div class="flex">
                <div id="filters" class="mb-4 flex gap-2">
                    <input type="text" id="messages-range" class="border p-1 rounded" placeholder="Selecione ..." />
                    <button id="apply-filter" class="bg-blue-500 text-white px-3 py-1 rounded">
                        Filtrar
                    </button>
                </div>

            </div>
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead>
                    <tr>
                        <th class="px-6 py-3">Canal</th>
                        <th class="px-6 py-3">Id</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Lead</th>
                        <th class="px-6 py-3">Titulo</th>
                        <th class="px-6 py-3">Data e Hora</th>
                        <th class="px-6 py-3">Ações</th>
                    </tr>
                </thead>
                <tbody id="conversations-body">
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <div id="pagination" class="flex justify-center mt-4 space-x-2"></div>

        <?php
        return ob_get_clean();
    }
}

add_shortcode('conversations_component', [ConversationsComponent::class, 'render']);
add_action('wp_ajax_conversations_download', [ConversationsComponent::class, 'download']);
add_action('wp_ajax_conversations_get', [ConversationsComponent::class, 'getConversations']);

add_action('wp_ajax_get_conversations', function () {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Você precisa estar logado.']);
    }

    $assistant = new Chatbot();
    $assistantId = $assistant->getChatbotIdByUser(get_current_user_id());

    $instance = WhatsappInstance::findByUserId(get_current_user_id());

    $startDate = $_POST['startDate'] ?? null;
    $endDate   = $_POST['endDate'] ?? null;

    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $perPage = 10;

    $result = ConversationsComponent::getConversations(
        $assistantId,
        $instance && method_exists($instance, 'getInstanceName') ? $instance->getInstanceName() : null,
        $perPage,
        $page,
        $startDate,
        $endDate
    );

    wp_send_json_success($result);
});

?>