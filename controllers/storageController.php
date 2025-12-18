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
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'Sem resposta do servidor';
            error_log("❌ Erro upload OpenAI: {$e->getMessage()} | Response: {$errorBody}");
            return ['error' => $e->getMessage(), 'details' => $errorBody];
        }
    }

    /**
     * Upload com retry e exponential backoff
     * @param string $filePath Caminho do arquivo
     * @param int $maxRetries Número máximo de tentativas (padrão: 3)
     * @return array|null Retorna resposta da OpenAI ou null se todas tentativas falharem
     */
    public static function uploadFileWithRetry($filePath, int $maxRetries = 3) {
        $attempt = 0;
        $lastError = null;
        
        while ($attempt < $maxRetries) {
            error_log("📤 Upload tentativa " . ($attempt + 1) . "/{$maxRetries} para: " . basename($filePath));
            
            $result = self::uploadFile($filePath);
            
            // Sucesso: retornou ID do arquivo
            if ($result && !empty($result['id'])) {
                error_log("✅ Upload bem-sucedido na tentativa " . ($attempt + 1) . ": file_id={$result['id']}");
                return $result;
            }
            
            // Falha: guardar erro e aguardar antes de retentar
            $lastError = $result['error'] ?? 'Erro desconhecido';
            $attempt++;
            
            if ($attempt < $maxRetries) {
                $waitSeconds = pow(2, $attempt); // 2, 4, 8 segundos
                error_log("⚠️ Upload falhou: {$lastError}. Aguardando {$waitSeconds}s antes da próxima tentativa...");
                sleep($waitSeconds);
            }
        }
        
        error_log("❌ Upload falhou após {$maxRetries} tentativas. Último erro: {$lastError}");
        return null;
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
