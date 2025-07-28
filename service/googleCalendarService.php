<?php

require_once plugin_dir_path(__DIR__) . 'vendor/autoload.php';


class GoogleCalendarService
{
    public function getFreeSlots($accessToken, $intervalDays = 7) {}

    public static function generateAvailableSlots(array $busyTimes, int $daysAhead, string $timeZone, $userId = null): array
    {
        $settings = get_user_meta($userId, 'gcal_settings', true);

        $workStart = $settings['work_start'] ?? 9;
        $workEnd = $settings['work_end'] ?? 17;
        $slotDuration = $settings['slot_duration'] ?? 60; // Default to 60 minutes
        $availableDays = $settings['available_days'] ?? [1, 2, 3, 4, 5]; // Default to weekdays (Monday to Friday)

        $slots = [];
        $now = new DateTime('now', new DateTimeZone($timeZone));

        for ($i = 0; $i < $daysAhead; $i++) {
            $date = clone $now;
            $date->modify("+$i days");

            // if (in_array($date->format('N'), [6, 7])) {
            //     continue; // pula sábado (6) e domingo (7)
            // }
            if (!in_array((int) $date->format('N'), $availableDays)) {
                continue;
            }

            for ($hour = $workStart; $hour < $workEnd; $hour++) {
                for ($minute = 0; $minute < 60; $minute += $slotDuration) {
                    $start = clone $date;
                    $start->setTime($hour, $minute);
                    $end = clone $start;
                    $end->modify("+$slotDuration minutes");

                    $slotIsBusy = false;
                    foreach ($busyTimes as $busy) {
                        $busyStart = new DateTime($busy['start']);
                        $busyEnd = new DateTime($busy['end']);

                        if ($start < $busyEnd && $end > $busyStart) {
                            $slotIsBusy = true;
                            break;
                        }
                    }

                    if (!$slotIsBusy && $start > new DateTime('now', new DateTimeZone($timeZone))) {
                        $slots[] = [
                            'start' => $start->format(DateTime::RFC3339),
                            'end' => $end->format(DateTime::RFC3339)
                        ];
                    }
                }
            }
        }

        return $slots;
    }

    public static function getAvailableTimeSlots($accessToken, $daysAhead = 7, $userId = null)
    {
        $timeZone = 'America/Sao_Paulo';
        $now = new DateTime('now', new DateTimeZone($timeZone));
        $start = $now->format(DateTime::RFC3339);
        $end = $now->modify("+{$daysAhead} days")->format(DateTime::RFC3339);

        $url = 'https://www.googleapis.com/calendar/v3/freeBusy';

        $body = [
            'timeMin' => $start,
            'timeMax' => $end,
            'timeZone' => $timeZone,
            'items' => [
                ['id' => 'primary']
            ]
        ];

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($body)
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        error_log(print_r($data, true));

        if (!isset($data['calendars']['primary']['busy'])) {
            error_log('Erro ao consultar disponibilidade: ' . $response);
            return [];
        }

        $busyTimes = $data['calendars']['primary']['busy'];

        return self::generateAvailableSlots($busyTimes, $daysAhead, $timeZone, $userId);
    }

    public static function formatSlotsForMessage(array $slots): array
    {
        $formatted = [];
        $locale = 'pt_BR.utf8';
        setlocale(LC_TIME, $locale);

        foreach ($slots as $slot) {
            $start = new DateTime($slot['start']);
            $end = new DateTime($slot['end']);

            $diaSemana = strftime('%A', $start->getTimestamp()); // Segunda-feira
            $diaMes = $start->format('d/m');
            $hora = $start->format('H:i');
            $horaFim = $end->format('H:i');

            $formatted[] = "$diaSemana, $diaMes das $hora às $horaFim";
        }

        return $formatted;
    }

    // public static function CreateCalendarEvent($calendar_id, $summary, $all_day, $recurrence, $recurrence_end, $event_time, $event_timezone, $access_token, $attendees = [], $use_meet = false)
    // {
    //     $url_events = 'https://www.googleapis.com/calendar/v3/calendars/' . $calendar_id . '/events?conferenceDataVersion=1';

    //     $curlPost = [
    //         'summary' => $summary,
    //     ];

    //     // Datas
    //     if ($all_day == 1) {
    //         $curlPost['start'] = ['date' => $event_time['event_date']];
    //         $curlPost['end'] = ['date' => $event_time['event_date']];
    //     } else {
    //         $curlPost['start'] = ['dateTime' => $event_time['start_time'], 'timeZone' => $event_timezone];
    //         $curlPost['end'] = ['dateTime' => $event_time['end_time'], 'timeZone' => $event_timezone];
    //     }

    //     // Recorrência
    //     if ($recurrence == 1) {
    //         $curlPost['recurrence'] = ["RRULE:FREQ=WEEKLY;UNTIL=" . str_replace('-', '', $recurrence_end) . ";"];
    //     }

    //     // Convidados
    //     if (!empty($attendees)) {
    //         $curlPost['attendees'] = array_map(fn($email) => ['email' => $email], $attendees);
    //     }

    //     // Meet
    //     if ($use_meet) {
    //         $curlPost['conferenceData'] = [
    //             'createRequest' => [
    //                 'conferenceSolutionKey' => [
    //                     'type' => 'hangoutsMeet'
    //                 ],
    //                 'requestId' => uniqid()
    //             ]
    //         ];
    //     }

    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $url_events);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //     curl_setopt($ch, CURLOPT_POST, 1);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //         'Authorization: Bearer ' . $access_token,
    //         'Content-Type: application/json'
    //     ]);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($curlPost));
    //     $data = json_decode(curl_exec($ch), true);
    //     $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    //     if ($http_code != 200) {
    //         error_log(print_r($data, true));
    //         throw new Exception('Erro ao criar evento');
    //     }

    //     return $data['id'];
    // }

    // public static function createEvent($accessToken, $summary, $start, $end, $email = '', $name = '', $description = '', $attendees = [], $use_meet = false)
    // {
    //     $url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events?conferenceDataVersion=1';

    //     $event = [
    //         'summary' => $summary,
    //         'description' => $description,
    //         'start' => [
    //             'dateTime' => $start,
    //             'timeZone' => 'America/Sao_Paulo'
    //         ],
    //         'end' => [
    //             'dateTime' => $end,
    //             'timeZone' => 'America/Sao_Paulo'
    //         ],
    //     ];

    //     // Adiciona o convidado, se email for válido
    //     if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    //         $attendee = ['email' => $email];
    //         if (!empty($name)) {
    //             $attendee['displayName'] = $name;
    //         }

    //         $event['attendees'] = [$attendee];
    //     }

    //     // Meet
    //     if ($use_meet) {
    //         $event['conferenceData'] = [
    //             'createRequest' => [
    //                 'conferenceSolutionKey' => [
    //                     'type' => 'hangoutsMeet'
    //                 ],
    //                 'requestId' => uniqid()
    //             ]
    //         ];
    //     }

    //     $headers = [
    //         'Authorization: Bearer ' . $accessToken,
    //         'Content-Type: application/json'
    //     ];

    //     $ch = curl_init($url);

    //     curl_setopt_array($ch, [
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_POST => true,
    //         CURLOPT_HTTPHEADER => $headers,
    //         CURLOPT_POSTFIELDS => json_encode($event)
    //     ]);

    //     $response = curl_exec($ch);
    //     error_log(print_r('respostaaa', true));
    //     error_log(print_r($response, true));
    //     curl_close($ch);

    //     return json_decode($response, true);
    // }

    public static function getUserEmail($accessToken)
    {
        $url = "https://www.googleapis.com/oauth2/v1/userinfo?alt=json";
        $headers = [
            "Authorization: Bearer $accessToken"
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        return $data['email'] ?? null;
    }

    public static function createEventWithClient($accessToken, $summary, $start, $end, $attendees = [], $description = '', $useMeet = false)
    {
        $client = new Google_Client();
        $client->setAccessToken($accessToken);

        $service = new Google_Service_Calendar($client);

        // Criação do evento
        $event = new Google_Service_Calendar_Event([
            'summary' => $summary,
            'description' => $description,
            'start' => new Google_Service_Calendar_EventDateTime([
                'dateTime' => $start,
                'timeZone' => 'America/Sao_Paulo'
            ]),
            'end' => new Google_Service_Calendar_EventDateTime([
                'dateTime' => $end,
                'timeZone' => 'America/Sao_Paulo'
            ]),
        ]);

        // Attendees
        if (!empty($attendees)) {
            $event->setAttendees(array_map(function ($att) {
                $a = new Google_Service_Calendar_EventAttendee();
                $a->setEmail($att['email']);
                if (!empty($att['displayName'])) {
                    $a->setDisplayName($att['displayName']);
                }
                return $a;
            }, $attendees));
        }

        // Meet
        if ($useMeet) {
            $conferenceRequest = new Google_Service_Calendar_CreateConferenceRequest([
                'requestId' => uniqid(),
                'conferenceSolutionKey' => new Google_Service_Calendar_ConferenceSolutionKey([
                    'type' => 'hangoutsMeet'
                ])
            ]);

            $event->setConferenceData(new Google_Service_Calendar_ConferenceData([
                'createRequest' => $conferenceRequest
            ]));
        }

        // Criação do evento
        $createdEvent = $service->events->insert('primary', $event, [
            'conferenceDataVersion' => 1,
            'sendUpdates' => 'all', // para notificar participantes
        ]);

        return $createdEvent;
    }

    public static function findEventByAttendee(string $accessToken, string $email, string $name = ''): ?array
    {
        $client = new \Google_Client();
        $client->setAccessToken($accessToken);

        $service = new \Google_Service_Calendar($client);

        $now = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format(DateTime::RFC3339);

        $params = [
            'timeMin' => $now,
            'maxResults' => 10,
            'singleEvents' => true,
            'orderBy' => 'startTime'
        ];

        $events = $service->events->listEvents('primary', $params);

        foreach ($events->getItems() as $event) {
            $attendees = $event->getAttendees();

            if ($attendees) {
                foreach ($attendees as $attendee) {
                    if (
                        strtolower($attendee->getEmail()) === strtolower($email) &&
                        (empty($name) || stripos($attendee->getDisplayName(), $name) !== false)
                    ) {
                        return [
                            'id' => $event->getId(),
                            'start' => $event->getStart()->getDateTime(),
                            'summary' => $event->getSummary(),
                        ];
                    }
                }
            }
        }

        return null;
    }

    public static function deleteEvent(string $accessToken, string $eventId): bool
    {
        try {
            $client = new \Google_Client();
            $client->setAccessToken($accessToken);

            $service = new \Google_Service_Calendar($client);

            $service->events->delete('primary', $eventId);

            return true;
        } catch (Exception $e) {
            error_log('Erro ao excluir evento: ' . $e->getMessage());
            return false;
        }
    }
}
