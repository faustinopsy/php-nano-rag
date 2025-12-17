<?php

declare(strict_types=1);

namespace NanoRag\RagEngine;

use NanoRag\LlmClient\OllamaClient;
use NanoRag\VectorDb\Memory;

class Brain{
    private OllamaClient $llm;
    private Memory $longTermMemory;
    private ShortTermMemory $shortTermMemory;

    public function __construct(OllamaClient $llm, Memory $longTermMemory, ShortTermMemory $shortTermMemory){
        $this->llm = $llm;
        $this->longTermMemory = $longTermMemory;
        $this->shortTermMemory = $shortTermMemory;
    }

    public function learn(string $content, array $metadata = []): string {
        $vector = $this->llm->getEmbedding($content);
        return $this->longTermMemory->addDocument($content, $vector, $metadata);
    }

    public function ask(string $userQuestion): string{
        // 1. Registra a pergunta na memória de curto prazo
        $this->shortTermMemory->add('user', $userQuestion);

        // --- CAMADA DE ATENÇÃO (Lógica de Recuperação) ---
        // A. Busca na Memória de Longo Prazo (Fatos/Arquivos)
        $questionVector = $this->llm->getEmbedding($userQuestion);
        $relevantFacts = $this->longTermMemory->search($questionVector, 3, 0.25);
        
        $factsText = "";
        foreach ($relevantFacts as $fact) {
            $source = $fact['metadata']['source'] ?? 'desconhecido';
            $factsText .= "- [Fonte: $source] " . $fact['text'] . "\n";
        }
        if (empty($factsText)) $factsText = "Nenhum fato específico encontrado nos arquivos.";

        // B. Busca na Memória de Curto Prazo (Conversa Recente)
        $chatHistory = $this->shortTermMemory->getHistory(6);
        $historyText = "";
        foreach ($chatHistory as $msg) {
            $role = strtoupper($msg['role']);
            $historyText .= "$role: {$msg['content']}\n";
        }

        // --- ENGENHARIA DE PROMPT (O Prompt System Híbrido) ---
$systemPrompt = <<<PROMPT
Você é o Nano RAG, um assistente inteligente.

### INSTRUÇÕES:
1. Você possui duas fontes de informação:
   - **BASE DE CONHECIMENTO**: Fatos extraídos de arquivos enviados pelo usuário.
   - **HISTÓRICO DA CONVERSA**: O que já foi dito nesta sessão.
2. Use a BASE DE CONHECIMENTO para responder perguntas técnicas ou sobre o conteúdo dos arquivos.
3. Use o HISTÓRICO DA CONVERSA para entender o contexto, referências (como "ele", "aquilo") ou resumir o papo.
4. Se o usuário perguntar "O que eu perguntei antes?", olhe para o HISTÓRICO.

### BASE DE CONHECIMENTO (Memória Longa):
$factsText

### HISTÓRICO DA CONVERSA (Memória Curta):
$historyText

Responda ao usuário (USER) agora:
PROMPT;

        // 2. Envia para a IA (Note que enviamos apenas a pergunta atual no 'content', 
        // pois o histórico já foi injetado no system context acima)
        $response = $this->llm->chat($userQuestion, $systemPrompt);

        // 3. Registra a resposta na memória de curto prazo
        $this->shortTermMemory->add('assistant', $response);

        return $response;
    }

    public function getLongTermMemorySize(): int{
        return $this->longTermMemory->count();
    }
    
    public function getShortTermMemoryCount(): int{
        return count($this->shortTermMemory->getFullHistory());
    }
}