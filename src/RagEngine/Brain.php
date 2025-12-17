<?php

declare(strict_types=1);

namespace NanoRag\RagEngine;

use NanoRag\LlmClient\OllamaClient;
use NanoRag\VectorDb\Memory;

class Brain{
    private OllamaClient $llm;
    private Memory $longTermMemory;
    private ShortTermMemory $shortTermMemory;
    private AttentionLayer $attention;

    public function __construct(OllamaClient $llm, Memory $longTerm, ShortTermMemory $shortTerm)    {
        $this->llm = $llm;
        $this->longTermMemory = $longTerm;
        $this->shortTermMemory = $shortTerm;
        $this->attention = new AttentionLayer($longTerm, $shortTerm);
    }

    public function learn(string $content, array $metadata = []): string {
        $vector = $this->llm->getEmbedding($content);
        return $this->longTermMemory->addDocument($content, $vector, $metadata);
    }

    public function ask(string $question): string {
        $queryVector = $this->llm->getEmbedding($question);

        $focus = $this->attention->focus($queryVector, $question);

        $systemPrompt = "Você é o Nano RAG. ";
        
        switch ($focus['strategy']) {
            case 'meta_analysis': 
                $systemPrompt .= "O usuário está perguntando sobre o histórico da conversa.\n";
                $systemPrompt .= "Analise a lista numerada abaixo para responder EXATAMENTE qual foi a ordem das perguntas.\n";
                $systemPrompt .= "### HISTÓRICO COMPLETO E NUMERADO:\n" . $focus['short_term'];
                break;

            case 'retrieval':
                $systemPrompt .= "Use EXCLUSIVAMENTE os Fatos Recuperados abaixo para responder.\n";
                $systemPrompt .= "### FATOS RECUPERADOS:\n" . $focus['long_term'];
                break;

            case 'context_followup':
                $systemPrompt .= "Continue a conversa abaixo de forma natural.\n";
                $systemPrompt .= "### HISTÓRICO RELEVANTE:\n" . $focus['short_term'];
                break;

            case 'mixed':
                $systemPrompt .= "Responda usando os Fatos Recuperados, mantendo a coerência com o Histórico.\n";
                $systemPrompt .= "### FATOS:\n" . $focus['long_term'] . "\n";
                $systemPrompt .= "### HISTÓRICO:\n" . $focus['short_term'];
                break;
                
            default:
                $systemPrompt .= "Responda com seu conhecimento geral.";
                break;
        }

        $response = $this->llm->chat($question, $systemPrompt);

        $this->shortTermMemory->add('user', $question, $queryVector);
        $this->shortTermMemory->add('assistant', $response, $queryVector);

        return $response;
    }
    
    public function getLongTermMemorySize(): int { return $this->longTermMemory->count(); }
    public function getShortTermMemoryCount(): int { return $this->shortTermMemory->count(); }
}