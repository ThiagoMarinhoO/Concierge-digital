<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class StorageController {
    private static function getClient(): Client {
        return new Client([
            'headers' => [
                'Authorization' => 'Bearer ' . OPENAI_API_KEY,
                'OpenAI-Beta'   => 'assistants=v2'
            ]
        ]);
    }

    public static function uploadFile($filePath, string $purpose = 'assistants') {
        try {
            $client = self::getClient();

            $res = $client->request('POST', 'https://api.openai.com/v1/files', [
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => basename($filePath),
                    ],
                    [
                        'name'     => 'purpose',
                        'contents' => $purpose,
                    ],
                ]
            ]);

            $body = json_decode($res->getBody(), true);
            return $body;
        } catch (RequestException $e) {
            error_log('Erro ao enviar arquivo: ' . $e->getMessage());
            return null;
        }
    }

    public static function getFileContent($fileId) {
        try {
            $client = self::getClient();

            $res = $client->request('GET', "https://api.openai.com/v1/files/{$fileId}", []);
            
            $body = json_decode($res->getBody(), true);
            return $body;
        } catch (RequestException $e) {
            error_log('Erro ao enviar arquivo: ' . $e->getMessage());
            return null;
        }
    }

    public static function createVectorStore($name,) {
        try {
            $client = self::getClient();

            $res = $client->request('POST', 'https://api.openai.com/v1/vector_stores', [
                'json' => [
                    'name'      => $name,
                ]
            ]);

            return json_decode($res->getBody(), true);
        } catch (RequestException $e) {
            error_log('Erro ao criar vector store: ' . $e->getMessage());
            return null;
        }
    }

    public static function createVectorStoreFile($vector_store_id, string $file_id) {
        try {
            $client = self::getClient();

            $res = $client->request('POST', "https://api.openai.com/v1/vector_stores/{$vector_store_id}/files", [
                'json' => ['file_id' => $file_id]
            ]);

            return json_decode($res->getBody(), true);
        } catch (RequestException $e) {
            error_log('Erro ao associar arquivo ao vector store: ' . $e->getMessage());
            return null;
        }
    }

    public static function deleteVectorStoreFile($vector_store_id, $file_id) {
        try {
            $client = self::getClient();

            $res = $client->request('DELETE', "https://api.openai.com/v1/vector_stores/{$vector_store_id}/files/{$file_id}");
            return json_decode($res->getBody(), true);
        } catch (RequestException $e) {
            error_log('Erro ao deletar arquivo do vector store: ' . $e->getMessage());
            return null;
        }
    }
}
