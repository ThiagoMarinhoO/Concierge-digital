<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ActiveCampaignService
{
    private $client;

    public function __construct($apiUrl, $apiKey)
    {
        $this->client = new Client([
            'base_uri' => "$apiUrl/api/3/",
            'headers' => [
                'Api-Token' => $apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    // public function __construct()
    // {
    //     $this->client = new Client([
    //         'base_uri' => "https://humans55020.api-us1.com/api/3/",
    //         'headers' => [
    //             'Api-Token' => "6a486c9bc211fb4efd9d1b1263d33bf9607059f353315665d18a1783f1ad098704d7ec57",
    //             'Content-Type' => 'application/json',
    //         ],
    //     ]);
    // }

    public function validateCredentials(): bool
    {
        try {
            $response = $this->client->request('GET', 'users/me');

            error_log('Validando credenciais...' . print_r(json_decode($response->getBody()->getContents(), true), true));
            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            error_log("Erro ao validar credenciais: " . $e->getMessage());
            return false;
        }
    }

    public function getContactIdByEmail(string $email): ?int
    {
        try {
            // Endpoint para buscar contatos por email
            $response = $this->client->request('GET', 'contacts', [
                'query' => [
                    'email' => $email
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            // Verifica se a busca retornou algum resultado
            if (!empty($data['contacts'])) {
                return (int)$data['contacts'][0]['id'];
            }
            return null;
        } catch (RequestException $e) {
            // Se houver qualquer erro na busca, loga e retorna null
            error_log("Erro ao buscar contato por email: " . $e->getMessage());
            return null;
        }
    }

    // public function createOrUpdateContact(string $email, string $firstName, string $phone): ?int
    // {
    //     // 1. Tenta buscar o contato existente.
    //     $contactId = $this->getContactIdByEmail($email);

    //     if ($contactId !== null) {
    //         // 2. Se o contato existe, realiza um UPDATE (PUT) para garantir que os dados estejam corretos.
    //         return $this->updateExistingContact($contactId, $email, $firstName, $phone);
    //     }

    //     // 3. Se o contato NÃO existe, tenta criar (POST).
    //     try {
    //         $response = $this->client->request('POST', 'contacts', [
    //             'json' => [
    //                 'contact' => [
    //                     'email' => $email,
    //                     'firstName' => $firstName,
    //                     'phone' => $phone,
    //                 ]
    //             ]
    //         ]);

    //         $data = json_decode($response->getBody()->getContents(), true);
    //         return $data['contact']['id'] ?? null;
    //     } catch (RequestException $e) {
    //         error_log("Erro ao criar contato: " . $e->getMessage());
    //         return null;
    //     }
    // }

    // private function updateExistingContact(int $id, string $email, string $firstName, string $phone): ?int
    // {
    //     try {
    //         $response = $this->client->request('PUT', "contacts/{$id}", [
    //             'json' => [
    //                 'contact' => [
    //                     'email' => $email,
    //                     'firstName' => $firstName,
    //                     'phone' => $phone,
    //                 ]
    //             ]
    //         ]);

    //         // Retorna o ID do contato que foi atualizado.
    //         return $id;
    //     } catch (RequestException $e) {
    //         error_log("Erro ao atualizar contato ID {$id}: " . $e->getMessage());
    //         return null;
    //     }
    // }

    public function createOrUpdateContact(string $firstName, string $email, string $phone)
    {
        $contactId = $this->getContactIdByEmail($email);
        if ($contactId) return $contactId;

        try {
            $response = $this->client->request('POST', 'contacts', [
                'json' => [
                    'contact' => [
                        'firstName' => $firstName,
                        'email' => $email,
                        'phone' => $phone,
                        'fieldValues' => []
                    ],
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 422) {
                $data = json_decode($response->getBody(), true);
                error_log('Erro Unprocessable Entity: ' . print_r($data, true));
            }

            if ($statusCode === 201 || $statusCode === 200) {
                $data = json_decode($response->getBody(), true);
                error_log('Contato criado/atualizado com sucesso: ' . print_r($data, true));
                return $data['contact']['id'] ?? null;
            }

            return null;
        } catch (RequestException $e) {
            error_log('Error creating or updating contact: ' . $e->getMessage());
            return null;
        }
    }

    public function createDealForContact(int $contactId, string $name, float $value = 00.0)
    {
        $stageId = null;

        // Buscar pipeline "Charlie Leads" ou criar novo
        $pipeline = null;
        //Listar todos os pipelines e filtrar pelo nome "Charlie Leads"
        try {
            $res = $this->client->request('GET', 'dealGroups?filters[title]=Charlie%20Leads', [
                'headers' => [
                    'accept' => 'application/json',
                ],
            ]);

            $resData = json_decode($res->getBody(), true);
            error_log('Pipeline encontrado com sucesso: ' . print_r($resData, true));
            $pipeline = $resData['dealGroups'][0] ?? null;
        } catch (RequestException $e) {
            error_log('Error fetching pipelines: ' . $e->getMessage());
        }
        // Se não houver criar um novo pipeline com o nome "Charlie Leads"
        if (!$pipeline['id']) {
            error_log('Não tem pipeline, criando um novo...');

            try {
                $res = $this->client->request('POST', 'dealGroups', [
                    'json' => [
                        'dealGroup' => [
                            'title' => 'Charlie Leads',
                            'currency' => 'brl',
                            'autoassign' => 1
                        ],
                    ],
                ]);

                $resData = json_decode($res->getBody(), true);
                error_log('Pipeline criado com sucesso: ' . print_r($resData, true));
                $pipeline = $resData['dealGroup'] ?? null;
            } catch (RequestException $e) {
                error_log('Error creating pipeline: ' . $e->getMessage());
            }
        }
        // Buscar stage "Contato Inicial" ou criar novo
        //Listar todos os stages e filtrar pelo nome "Contato Inicial"

        // Se não houver criar um novo stage com o nome "Contato Inicial"

        // Stage é sempre o "toContact"
        if ($pipeline['id']) {
            $stageId = $pipeline['stages'][0] ?? null;
        }

        error_log('Stage ID: ' . $stageId);

        try {
            $response = $this->client->request('POST', 'deals', [
                'json' => [
                    'deal' => [
                        'contact' => $contactId,
                        'title' => $name,
                        'status' => 0,
                        'value' => (int)($value * 100),
                        'currency' => 'brl',
                        'stage' => $stageId,
                        'group' => $pipeline['id'],
                    ],
                ],
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode === 201 || $statusCode === 200) {
                $data = json_decode($response->getBody(), true);
                error_log('Deal criado/atualizado com sucesso: ' . print_r($data, true));
                return $data['deal']['id'] ?? null;
            }

            return null;
        } catch (RequestException $e) {
            error_log('Error creating deal for contact: ' . $e->getMessage());
            return null;
        }
    }

    public function createDeal()
    {
        $response = $this->client->request('POST', 'deals', [
            'body' => '{"deal":{"status":0}}',
            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ],
        ]);

        return $response->getBody();
    }

    public function getDeal($id)
    {
        $response = $this->client->request('GET', "deals/{$id}", [
            'headers' => [
                'content-type' => 'application/json',
            ],
        ]);

        return $response->getBody();
    }

    public function deleteDeal($id)
    {
        $response = $this->client->request('DELETE', "deals/{$id}", [
            'headers' => [
                'content-type' => 'application/json',
            ],
        ]);

        return $response->getBody();
    }

    public function createContact()
    {
        $response = $this->client->request('POST', 'contacts', [
            'body' => '{"contact":{"email":"johndoe@example.com","firstName":"John","lastName":"Doe","phone":"7223224241","fieldValues":[{"field":"1","value":"The Value for First Field"},{"field":"6","value":"2008-01-20"}]}}',
            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ],
        ]);

        return $response->getBody();
    }
}