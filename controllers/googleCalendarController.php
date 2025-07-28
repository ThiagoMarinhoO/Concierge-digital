<?php
require_once plugin_dir_path(__DIR__) . 'vendor/autoload.php';

class GoogleCalendarController
{

    public function GetAccessToken($client_id, $redirect_uri, $client_secret, $code)
    {
        $url = 'https://accounts.google.com/o/oauth2/token';

        $curlPost = 'client_id=' . $client_id . '&redirect_uri=' . $redirect_uri . '&client_secret=' . $client_secret . '&code=' . $code . '&grant_type=authorization_code';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = json_decode(curl_exec($ch), true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code != 200)
            throw new Exception('Error : Failed to receieve access token');

        return $data;
    }

    public static function get_valid_access_token($user_id)
    {
        $token = get_user_meta($user_id, 'gcalendar_token', true);

        // error_log('tokenn');
        // error_log(print_r($token, true));

        if (!$token || empty($token['access_token'])) {
            error_log("Token não encontrado");
            return false;
        }

        $client = self::get_client();
        $client->setAccessToken($token);

        // Verifica se expirou
        if ($client->isAccessTokenExpired()) {
            if (!isset($token['refresh_token'])) {
                throw new Exception("Refresh token ausente. Reautentique o usuário.");
            }

            $client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
            $new_token = $client->getAccessToken();
            $new_token['refresh_token'] = $token['refresh_token'];
            update_user_meta($user_id, 'gcalendar_token', $new_token);
        }

        return $client->getAccessToken()['access_token'];
    }


    public static function get_client(): Google_Client
    {

        $client = new Google_Client();
        $client->setClientId(GCALENDAR_CLIENT_ID);
        $client->setClientSecret(GCALENDAR_CLIENT_SECRET);
        $client->setRedirectUri(rest_url('gcalendar/v1/callback'));
        $client->addScope([
            Google_Service_Calendar::CALENDAR_EVENTS,
            Google_Service_Calendar::CALENDAR_READONLY,
            Google_Service_Oauth2::USERINFO_EMAIL
        ]);

        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    public static function gcalendar_auth()
    {
        $client = self::get_client();
        wp_redirect($client->createAuthUrl());
        exit;
    }

    public static function gcalendar_callback(WP_REST_Request $request)
    {
        $client = self::get_client();

        if (!isset($_GET['code'])) {
            wp_die('Código de autorização não encontrado.');
        }

        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        if (isset($token['error'])) {
            wp_die('Erro na autenticação: ' . esc_html($token['error_description']));
        }

        $client->setAccessToken($token);

        $oauth = new Google_Service_Oauth2($client);
        $info = $oauth->userinfo->get();

        $user = get_user_by('email', $info->email);

        if ($user) {
            // 👇 RESGATAR TOKEN ATUAL SALVO (caso não venha o refresh_token agora)
            $existing_token = get_user_meta($user->ID, 'gcalendar_token', true);

            if (!isset($token['refresh_token']) && isset($existing_token['refresh_token'])) {
                $token['refresh_token'] = $existing_token['refresh_token'];
            }

            update_user_meta($user->ID, 'gcalendar_token', $token);

            wp_redirect(home_url('/assistente'));
        } else {
            wp_die('Usuário não encontrado no sistema.');
        }
    }


    public static function GetCalendarsList()
    {
        $user_id = 52;

        if (!$user_id) {
            wp_send_json_error("Usuário não autenticado.");
            return;
        }

        try {
            $access_token = self::get_valid_access_token($user_id);

            $url_parameters = [
                'fields' => 'items(id,summary,timeZone)',
                'minAccessRole' => 'owner',
            ];

            $url_calendars = 'https://www.googleapis.com/calendar/v3/users/me/calendarList?' . http_build_query($url_parameters);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url_calendars);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            $data = json_decode(curl_exec($ch), true);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            error_log(print_r($data, true));

            if ($http_code != 200) {
                error_log('Erro ao buscar lista de calendários: ' . print_r($data, true));
                wp_send_json_error($data);
            }

            wp_send_json_success($data);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public static function getAgendasList($calendar_id)
    {
        $user_id = 52;

        if (!$user_id) {
            wp_send_json_error("Usuário não autenticado.");
            return;
        }

        $calendar_id = 'professionalmarinho@gmail.com';

        try {
            $access_token = self::get_valid_access_token($user_id);

            // $url_parameters = [
            //     'fields' => 'items(id,summary,timeZone)',
            //     'minAccessRole' => 'owner',
            // ];

            $url_calendars = "https://www.googleapis.com/calendar/v3/calendars/{$calendar_id}/events";
            // $url_calendars = "https://www.googleapis.com/calendar/v3/users/me/calendarList/{$calendar_id}";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url_calendars);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            $data = json_decode(curl_exec($ch), true);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            error_log(print_r($data, true));

            if ($http_code != 200) {
                error_log('Erro ao buscar lista de calendários: ' . print_r($data, true));
                wp_send_json_error($data);
            }

            wp_send_json_success($data);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function UpdateCalendarEvent($event_id, $calendar_id, $summary, $all_day, $event_time, $event_timezone, $access_token)
    {
        $url_events = 'https://www.googleapis.com/calendar/v3/calendars/' . $calendar_id . '/events/' . $event_id;

        $curlPost = array('summary' => $summary);
        if ($all_day == 1) {
            $curlPost['start'] = array('date' => $event_time['event_date']);
            $curlPost['end'] = array('date' => $event_time['event_date']);
        } else {
            $curlPost['start'] = array('dateTime' => $event_time['start_time'], 'timeZone' => $event_timezone);
            $curlPost['end'] = array('dateTime' => $event_time['end_time'], 'timeZone' => $event_timezone);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_events);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $access_token, 'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($curlPost));
        $data = json_decode(curl_exec($ch), true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        error_log(print_r($data, true));

        if ($http_code != 200) {
            error_log('Erro ao atualizar evento: ' . print_r($data, true));
            wp_send_json_error($data);
        }
    }

    public function DeleteCalendarEvent($event_id, $calendar_id, $access_token)
    {
        $url_events = 'https://www.googleapis.com/calendar/v3/calendars/' . $calendar_id . '/events/' . $event_id;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_events);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $access_token, 'Content-Type: application/json'));
        $data = json_decode(curl_exec($ch), true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        error_log(print_r($data, true));

        if ($http_code != 200) {
            error_log('Erro ao deletar evento: ' . print_r($data, true));
            wp_send_json_error($data);
        }
    }

    public static function handle_create_calendar_event()
    {
        try {
            $user_id = 52;
            $access_token = self::get_valid_access_token($user_id);

            $calendar_id = sanitize_text_field($_POST['calendar_id']);
            $summary = sanitize_text_field($_POST['summary']);
            $all_day = intval($_POST['all_day']);
            $event_timezone = sanitize_text_field($_POST['event_timezone']);
            $recurrence = intval($_POST['recurrence']);
            $recurrence_end = sanitize_text_field($_POST['recurrence_end']);

            $attendees = isset($_POST['attendees']) ? json_decode(stripslashes($_POST['attendees']), true) : [];
            $use_meet = isset($_POST['use_meet']) && $_POST['use_meet'] == '1';


            $event_time = [
                'event_date' => sanitize_text_field($_POST['event_date']),
                'start_time' => sanitize_text_field($_POST['start_time']),
                'end_time' => sanitize_text_field($_POST['end_time']),
            ];

            $instance = new self();
            $event_id = $instance->CreateCalendarEvent(
                $calendar_id,
                $summary,
                $all_day,
                $recurrence,
                $recurrence_end,
                $event_time,
                $event_timezone,
                $access_token,
                $attendees,
                $use_meet
            );

            wp_send_json_success(['event_id' => $event_id]);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public static function handle_delete_calendar_event()
    {
        try {
            $user_id = 52;
            $access_token = self::get_valid_access_token($user_id);

            $calendar_id = sanitize_text_field($_POST['calendar_id']);
            $event_id = sanitize_text_field($_POST['event_id']);

            $instance = new self();
            $event_id = $instance->DeleteCalendarEvent($event_id, $calendar_id, $access_token);

            wp_send_json_success();
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    // APAGAR
    public static function handle_freebusy()
    {
        try {
            $user_id = 52;
            $access_token = self::get_valid_access_token($user_id);

            $slots = GoogleCalendarService::getAvailableTimeSlots($access_token);

            wp_send_json_success($slots);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public static function createEvent($accessToken, $summary, $start, $end, $description = '', $attendees = [])
    {
        $url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';

        $event = [
            'summary' => $summary,
            'description' => $description,
            'start' => [
                'dateTime' => $start,
                'timeZone' => 'America/Sao_Paulo'
            ],
            'end' => [
                'dateTime' => $end,
                'timeZone' => 'America/Sao_Paulo'
            ],
        ];

        if (!empty($attendees)) {
            $event['attendees'] = array_map(fn($email) => ['email' => $email], $attendees);
        }

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($event)
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    // USEFULL
    public static function gcalendar_settings()
    {
        error_log('gcalendar_settings called');

        $workStart = isset($_POST['work_start']) ? intval($_POST['work_start']) : 9; // Default to 9 AM
        $workEnd = isset($_POST['work_end']) ? intval($_POST['work_end']) : 17; // Default to 5 PM
        $slotDuration = isset($_POST['slot_duration']) ? intval($_POST['slot_duration']) : 60; // Default to 60 minutes
        $availableDays = isset($_POST['available_days']) ? array_map('intval', $_POST['available_days']) : [1, 2, 3, 4, 5]; // Default to weekdays

        $user_id = get_current_user_id();

        $user_settings = [
            'work_start' => $workStart,
            'work_end' => $workEnd,
            'slot_duration' => $slotDuration,
            'available_days' => $availableDays,
            'time_zone' => 'America/Sao_Paulo',
        ];
        
        update_user_meta($user_id, 'gcal_settings', $user_settings);

        wp_send_json_success([
            'message' => 'Configurações salvas com sucesso.',
            'settings' => $user_settings
        ]);
    }
}

add_action('wp_ajax_gcalendar_settings', ['GoogleCalendarController', 'gcalendar_settings']);


add_action('wp_ajax_get_calendars_list', ['GoogleCalendarController', 'GetCalendarsList']);

add_action('wp_ajax_get_agendas_list', ['GoogleCalendarController', 'getAgendasList']);

// add_action('wp_ajax_create_calendar_event', ['GoogleCalendarController', 'handle_create_calendar_event']);
// add_action('wp_ajax_delete_calendar_event', ['GoogleCalendarController', 'handle_delete_calendar_event']);

add_action('wp_ajax_handle_freebusy', ['GoogleCalendarController', 'handle_freebusy']);


add_action('wp_ajax_gcalendar_auth', ['GoogleCalendarController', 'gcalendar_auth']);
add_action('wp_ajax_gcalendar_callback', ['GoogleCalendarController', 'gcalendar_callback']);

add_action('rest_api_init', function () {
    register_rest_route('gcalendar/v1', '/callback', [
        'methods' => 'GET',
        'callback' => ['GoogleCalendarController', 'gcalendar_callback'],
        'permission_callback' => '__return_true',
    ]);
});
