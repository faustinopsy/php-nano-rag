<?php

require_once __DIR__ . '/../vendor/autoload.php';

use NanoRag\LlmClient\OllamaClient;
use NanoRag\VectorDb\Database;
use NanoRag\RagEngine\Brain;

$dbFile = 'knowledge_base.json';
if (file_exists($dbFile)) { unlink($dbFile); } 

try {
    echo "--- Inicializando o Nano RAG ---\n";
    
    $client = new OllamaClient('http://localhost:11434', 'llama3.2', 'nomic-embed-text');
    $db = new Database($dbFile);
    $brain = new Brain($client, $db);

    // 1. Fase de Aprendizado
    echo "Ensinando fatos novos para a IA...\n";
    
    $fatos = [
        "O Nano RAG é uma biblioteca PHP para IA criada para rodar em hospedagem compartilhada.",
        "O criador do Nano RAG é o Engenheiro Sênior Faustino.",
        "A linguagem favorita do Faustino é PHP Puro, mas ele gosta de Python para dados.",
        "O segredo do universo é 42, mas no PHP é o operador Paamayim Nekudotayim."
    ];

    foreach ($fatos as $fato) {
        $brain->learn($fato, ['tipo' => 'fatos_curiosos']);
        echo ".";
    }
    echo "\nAprendizado concluído! Memória atual: " . $brain->getMemorySize() . " documentos.\n\n";

    // 2. Fase de Consulta (RAG)
    $perguntas = [
        "Quem criou o Nano RAG?", 
        "Qual a linguagem favorita do criador?",
        "O que é o Nano RAG?"
    ];

    echo "--- Iniciando Perguntas e Respostas ---\n";

    foreach ($perguntas as $pergunta) {
        echo "\nUsuário: $pergunta\n";
        
        $start = microtime(true);
        $resposta = $brain->ask($pergunta);
        $tempo = number_format(microtime(true) - $start, 2);
        
        echo "IA ($tempo s): $resposta\n";
        echo "--------------------------------------------------\n";
    }

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}