<?php

require_once __DIR__ . '/../vendor/autoload.php';

use NanoRag\LlmClient\OllamaClient;

// Ajuste os modelos aqui se você baixou outros nomes (ex: mistral, llama3, etc)
// Se estiver usando docker, mude localhost para o IP do container ou host.docker.internal
$client = new OllamaClient('http://localhost:11434', 'llama3.2', 'nomic-embed-text');

echo "--- Teste de Conexão com Ollama ---\n";

try {
    // 1. Teste de Embedding
    echo "1. Gerando vetor para a frase 'O PHP é infinito'...\n";
    $start = microtime(true);
    
    $vector = $client->getEmbedding("O PHP é infinito");
    
    $duration = microtime(true) - $start;
    $dimensao = count($vector);
    
    echo "Embedding gerado em " . number_format($duration, 2) . "s\n";
    echo "Dimensão do vetor: " . $dimensao . " (Isso confirma que o modelo está funcionando)\n\n";

    // 2. Teste de Chat
    echo "2. Enviando pergunta simples para o Chat...\n";
    echo "Perguntando: 'Qual a capital da França? Responda em 1 palavra.'\n";
    
    $response = $client->chat("Qual a capital da França? Responda em 1 palavra.");
    
    echo "Resposta da IA: " . trim($response) . "\n\n";

    echo "Fase 3 Concluída: PHP está controlando o Ollama com sucesso.\n";

} catch (Exception $e) {
    echo "Erro Crítico: " . $e->getMessage() . "\n";
    echo "DICA: Verifique se o Ollama está rodando e se você fez o 'ollama pull' dos modelos.\n";
}