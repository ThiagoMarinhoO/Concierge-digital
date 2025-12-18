<?php

class GoogleCalendarComponent
{
    public function __construct()
    {
    }

    /**
     * Renderiza o conteúdo do componente Google Calendar.
     * Este método é chamado quando o shortcode [google_calendar] é utilizado.
     *
     * @return string O HTML renderizado do componente.
     */
    public static function render()
    {
        ob_start();

        $user_id = get_current_user_id();

        // $gcalendar_auth = get_user_meta($user_id, 'gcalendar_auth', true);
        
        $is_connected = GoogleCalendarController::get_valid_access_token($user_id);

        $settings = get_user_meta($user_id, 'gcal_settings', true);
        if (!is_array($settings)) {
            $settings = [];
        }

        $work_start = isset($settings['work_start']) && !empty($settings['work_start'])
                      ? str_pad($settings['work_start'], 2, '0', STR_PAD_LEFT) . ':00' 
                      : '09:00';
        $work_end   = isset($settings['work_end']) && !empty($settings['work_end'])
                      ? str_pad($settings['work_end'], 2, '0', STR_PAD_LEFT) . ':00' 
                      : '17:00';
        
        $settings['slot_duration'] = isset($settings['slot_duration']) ? $settings['slot_duration'] : 30;

        $settings['available_days'] = isset($settings['available_days']) && is_array($settings['available_days'])
                                    ? $settings['available_days']
                                    : [];

        ?>

        <div class="google-calendar-component-wrapper">
            <?php if ($is_connected) : ?>
                <form id="calendar-settings" class="max-w-xl mx-auto p-6 bg-white shadow rounded-xl space-y-6">
                    <h5 class="text-xl font-bold text-gray-800 border-b pb-4 mb-4">Configurações de Disponibilidade ✨</h5>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="work_start" class="block text-sm font-medium text-gray-700">Início do expediente</label>
                            <input type="time" id="work_start" name="work_start" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                value="<?php echo esc_attr($work_start); ?>" />
                        </div>
                        <div>
                            <label for="work_end" class="block text-sm font-medium text-gray-700">Fim do expediente</label>
                            <input type="time" id="work_end" name="work_end" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                value="<?php echo esc_attr($work_end); ?>" />
                        </div>
                    </div>

                    <div>
                        <label for="slot_duration" class="block text-sm font-medium text-gray-700">Duração da reunião (min)</label>
                        <select id="slot_duration" name="slot_duration" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <?php foreach ([15, 30, 45, 60] as $duration) : ?>
                                <option value="<?php echo esc_attr($duration); ?>" 
                                    <?php selected($settings['slot_duration'], $duration); ?>>
                                    <?php echo esc_html($duration); ?> minutos
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <span class="block text-sm font-medium text-gray-700 mb-2">Dias disponíveis</span>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <?php
                            $dias_semana = [
                                1 => 'Segunda-feira', // Dia 1 no PHP é segunda (ISO-8601)
                                2 => 'Terça-feira',
                                3 => 'Quarta-feira',
                                4 => 'Quinta-feira',
                                5 => 'Sexta-feira',
                                6 => 'Sábado',
                                7 => 'Domingo',
                            ];
                            foreach ($dias_semana as $num => $label) : ?>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="available_days[]" value="<?php echo esc_attr($num); ?>"
                                        class="form-checkbox text-blue-600 rounded focus:ring-blue-500"
                                        <?php checked(in_array($num, $settings['available_days'])); ?> />
                                    <span class="ml-2 text-gray-800"><?php echo esc_html($label); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div>
                        <button type="submit" 
                                class="w-full bg-blue-600 text-white font-bold py-2.5 px-4 rounded-md 
                                       hover:bg-blue-700 transition duration-300 ease-in-out 
                                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Salvar Configurações
                        </button>
                    </div>
                </form>
            <?php else : ?>
                <div class="text-center p-8 bg-white shadow rounded-xl">
                    <p class="text-lg text-gray-700 mb-4">Seu calendário ainda não está conectado ao Google.</p>
                    <?php 
                    // Certifique-se de que GoogleCalendarController::get_client() e createAuthUrl() existam
                    // e sejam acessíveis. Se 'GoogleCalendarController' for de outro arquivo,
                    // ele precisa ter sido incluído ou estar carregado antes.
                    $auth_url = '#'; // Fallback URL
                    if (class_exists('GoogleCalendarController') && method_exists('GoogleCalendarController', 'get_client')) {
                        try {
                            $client = GoogleCalendarController::get_client();
                            if ($client) {
                                $auth_url = $client->createAuthUrl();
                            }
                        } catch (Exception $e) {
                            // Log the error or handle it gracefully
                            error_log('Erro ao obter URL de autenticação do Google Calendar: ' . $e->getMessage());
                            $auth_url = '#gcalendar-error'; // Fallback em caso de erro
                        }
                    }
                    ?>
                    <a href="<?php echo esc_url($auth_url); ?>" 
                       id="gcalendar-connect" 
                       class="gcalendar-tooltip inline-block bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-300 ease-in-out">
                        Conectar com Google Calendar 🚀
                    </a>
                    <p class="text-sm text-gray-500 mt-2">Você será redirecionado para o Google para autorizar.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php
        // Retorna todo o conteúdo capturado pelo buffer de saída.
        return ob_get_clean();
    }
}

// Registra o shortcode.
// Quando o WordPress encontrar [google_calendar], ele chamará o método 'render' da classe GoogleCalendarComponent.
add_shortcode('google_calendar_component', [GoogleCalendarComponent::class, 'render']);

?>