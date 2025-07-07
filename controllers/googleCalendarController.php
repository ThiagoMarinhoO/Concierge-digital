<?php
require_once plugin_dir_path(__DIR__) . 'vendor/autoload.php';

class GoogleCalendarController
{

    public function GetAccessToken($client_id, $redirect_uri, $client_secret, $code) {	
		$url = 'https://accounts.google.com/o/oauth2/token';			
		
		$curlPost = 'client_id=' . $client_id . '&redirect_uri=' . $redirect_uri . '&client_secret=' . $client_secret . '&code='. $code . '&grant_type=authorization_code';
		$ch = curl_init();		
		curl_setopt($ch, CURLOPT_URL, $url);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		
		curl_setopt($ch, CURLOPT_POST, 1);		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);	
		$data = json_decode(curl_exec($ch), true);
		$http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);		
		if($http_code != 200) 
			throw new Exception('Error : Failed to receieve access token');
			
		return $data;
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
            update_user_meta($user->ID, 'gcalendar_token', $token);
            wp_redirect(home_url('/assistente'));
        } else {
            wp_die('Usuário não encontrado no sistema.');
        }
        exit;
    }

    // public function GetCalendarsList($access_token) {
	// 	$url_parameters = array();

	// 	$url_parameters['fields'] = 'items(id,summary,timeZone)';
	// 	$url_parameters['minAccessRole'] = 'owner';

	// 	$url_calendars = 'https://www.googleapis.com/calendar/v3/users/me/calendarList?'. http_build_query($url_parameters);
		
	// 	$ch = curl_init();		
	// 	curl_setopt($ch, CURLOPT_URL, $url_calendars);		
	// 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
	// 	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $access_token));	
	// 	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);	
	// 	$data = json_decode(curl_exec($ch), true); //echo '<pre>';print_r($data);echo '</pre>';
	// 	$http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);		
	// 	if($http_code != 200) 
	// 		throw new Exception('Error : Failed to get calendars list');

	// 	return $data['items'];
	// }
}

add_action('wp_ajax_gcalendar_auth', ['GoogleCalendarController', 'gcalendar_auth']);
add_action('wp_ajax_gcalendar_callback', ['GoogleCalendarController', 'gcalendar_callback']);

add_action('rest_api_init', function () {
    register_rest_route('gcalendar/v1', '/callback', [
        'methods' => 'GET',
        'callback' => ['GoogleCalendarController', 'gcalendar_callback'],
        'permission_callback' => '__return_true',
    ]);
});