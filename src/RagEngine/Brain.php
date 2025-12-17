<?php

declare(strict_types=1);

namespace NanoRag\RagEngine;

use NanoRag\LlmClient\OllamaClient;
use NanoRag\VectorDb\Memory; 

class Brain{
    private OllamaClient $llm;
    private Memory $memory;

    public function __construct(OllamaClient $llm, Memory $memory) {
        $this->llm = $llm;
        $this->memory = $memory;
    }

    public function learn(string $content, array $metadata = []): string{
        $vector = $this->llm->getEmbedding($content);
        return $this->memory->addDocument($content, $vector, $metadata);
    }

    public function ask(string $question, int $contextLimit = 3): string {
        $questionVector = $this->llm->getEmbedding($question);
        $relevantMemories = $this->memory->search($questionVector, $contextLimit, 0.25);

        $contextText = "";
        foreach ($relevantMemories as $mem) {
            $contextText .= "- " . $mem['text'] . "\n";
        }

        if (empty($contextText)) {
            $contextText = "Nenhuma memória relevante encontrada.";
        }

        $systemPrompt = "Use as seguintes memórias recuperadas para responder à pergunta do usuário:\n" . $contextText;

        return $this->llm->chat($question, $systemPrompt);
    }
    
    public function getMemorySize(): int {
        return $this->memory->count();
    }
}