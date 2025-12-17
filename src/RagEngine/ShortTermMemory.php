<?php

declare(strict_types=1);

namespace NanoRag\RagEngine;

class ShortTermMemory{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['conversation_history'])) {
            $_SESSION['conversation_history'] = [];
        }
    }

    /**
     * Adiciona uma interação à memória imediata
     */
    public function add(string $role, string $content): void {
        $_SESSION['conversation_history'][] = [
            'role' => $role,
            'content' => $content,
            'timestamp' => time()
        ];
    }

    /**
     * Recupera o histórico formatado para o LLM.
     * @param int $limit Limita às últimas N mensagens para não estourar o limite de tokens.
     */
    public function getHistory(int $limit = 6): array{
        return array_slice($_SESSION['conversation_history'], -$limit);
    }
    
    /**
     * Retorna o histórico bruto (para contar qual foi a 3ª pergunta, por exemplo)
     */
    public function getFullHistory(): array {
        return $_SESSION['conversation_history'];
    }

    public function clear(): void {
        $_SESSION['conversation_history'] = [];
    }
}