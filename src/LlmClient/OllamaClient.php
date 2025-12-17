<?php

declare(strict_types=1);

namespace NanoRag\LlmClient;

use RuntimeException;

class OllamaClient
{
    private string $baseUrl;
    private string $chatModel;
    private string $embedModel;

    public function __construct(
        string $baseUrl = 'http://localhost:11434',
        string $chatModel = 'gemma3:latest',
        string $embedModel = 'nomic-embed-text'
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->chatModel = $chatModel;
        $this->embedModel = $embedModel;
    }

    /**
     * Gera o vetor (embedding) para um texto dado.
     * Retorna array de floats.
     */
    public function getEmbedding(string $text): array
    {
        $payload = [
            'model' => $this->embedModel,
            'prompt' => $text,
        ];

        $response = $this->executeRequest('/api/embeddings', $payload);

        if (!isset($response['embedding'])) {
            throw new RuntimeException("Ollama não retornou um embedding. Verifique se o modelo '{$this->embedModel}' está instalado.");
        }

        return $response['embedding'];
    }

    /**
     * Envia uma mensagem para o chat e retorna a resposta em texto.
     */
    public function chat(string $userMessage, string $systemContext = ''): string
    {
        $messages = [];

        // Adiciona contexto do sistema (a "personalidade" ou os dados recuperados do RAG)
        if (!empty($systemContext)) {
            $messages[] = ['role' => 'system', 'content' => $systemContext];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $payload = [
            'model' => $this->chatModel,
            'messages' => $messages,
            'stream' => false,
        ];

        $response = $this->executeRequest('/api/chat', $payload);

        if (!isset($response['message']['content'])) {
            throw new RuntimeException("Formato de resposta inesperado do Ollama.");
        }

        return $response['message']['content'];
    }

    /**
     * Método auxiliar privado para chamadas cURL
     */
    private function executeRequest(string $endpoint, array $data): array
    {
        $ch = curl_init($this->baseUrl . $endpoint);
        
        $jsonData = json_encode($data);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData)
            ],
            CURLOPT_TIMEOUT => 120,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($result === false) {
            throw new RuntimeException("Erro de conexão cURL: $error");
        }

        if ($httpCode >= 400) {
            throw new RuntimeException("Erro da API Ollama (HTTP $httpCode): $result");
        }

        $decoded = json_decode($result, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Erro ao decodificar JSON do Ollama.");
        }

        return $decoded;
    }
}