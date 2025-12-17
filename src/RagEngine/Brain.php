<?php

declare(strict_types=1);

namespace NanoRag\RagEngine;

use NanoRag\LlmClient\OllamaClient;
use NanoRag\VectorDb\Database;

class Brain{
    private OllamaClient $llm;
    private Database $memory;

    public function __construct(OllamaClient $llm, Database $memory)    {
        $this->llm = $llm;
        $this->memory = $memory;
    }

    /**
     * Ensina algo novo para a IA (Memória de Longo Prazo)
     */
    public function learn(string $content, array $metadata = []): string{
        // 1. Transforma o texto em vetor
        $vector = $this->llm->getEmbedding($content);
        // 2. Salva no banco de dados vetorial
        return $this->memory->addDocument($content, $vector, $metadata);
    }

    /**
     * Pergunta algo para a IA usando o contexto aprendido
     */
    public function ask(string $question, int $contextLimit = 3): string{
        // 1. Entende a intenção da pergunta (vetor)
        $questionVector = $this->llm->getEmbedding($question);

        // 2. Busca no banco as 3 coisas mais parecidas com a pergunta
        // Definimos um score mínimo de 0.3 para evitar lixo irrelevante
        $relevantDocs = $this->memory->search($questionVector, $contextLimit, 0.3);

        // 3. Monta o texto de contexto (o "Prompt Engineering")
        $contextText = "";
        foreach ($relevantDocs as $doc) {
            $contextText .= "- " . $doc['text'] . "\n";
        }

        if (empty($contextText)) {
            $contextText = "Nenhuma informação relevante encontrada na memória interna.";
        }

        // 4. Cria o prompt final para o LLM
        $systemPrompt = "Você é um assistente útil e preciso. " .
            "Use APENAS o contexto fornecido abaixo para responder à pergunta do usuário. " .
            "Se a resposta não estiver no contexto, diga 'Não tenho essa informação na minha base de dados'. " .
            "Contexto:\n" . $contextText;

        // 5. Envia para o chat
        return $this->llm->chat($question, $systemPrompt);
    }
    
    /**
     * Retorna a quantidade de itens na memória
     */
    public function getMemorySize(): int{
        return $this->memory->count();
    }
}