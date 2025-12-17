<?php

declare(strict_types=1);

namespace NanoRag\RagEngine;

use NanoRag\VectorDb\MathUtils;

class ShortTermMemory{
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Agora armazenamos 'chat_vectors' em vez de apenas strings
        if (!isset($_SESSION['chat_vectors'])) {
            $_SESSION['chat_vectors'] = [];
        }
    }

    /**
     * Adiciona uma interação à memória (Texto + Vetor)
     */
    public function add(string $role, string $content, array $vector): void {
        $_SESSION['chat_vectors'][] = [
            'role' => $role,
            'content' => $content,
            'vector' => $vector, 
            'timestamp' => time()
        ];
    }

    /**
     * Recupera apenas mensagens relevantes semanticamente (Para o AttentionLayer)
     */
    public function getRelevantHistory(array $currentQueryVector, float $threshold = 0.4): array{
        $relevant = [];
        $history = array_slice($_SESSION['chat_vectors'], -6); // Janela deslizante de 6

        foreach ($history as $msg) {
            // Verifica similaridade vetorial entre a pergunta atual e mensagens passadas
            $score = MathUtils::cosineSimilarity($currentQueryVector, $msg['vector']);
            // Se for relevante OU for a última mensagem (para manter fluxo imediato), inclui.
            if ($score >= $threshold || $msg === end($history)) {
                $relevant[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }
        }
        return $relevant;
    }

    /**
     * Retorna todo o histórico para exibição na UI (index.php).
     */
    public function getFullHistory(): array {
        return $_SESSION['chat_vectors'] ?? [];
    }

    /**
     * Método auxiliar para contar itens (usado no Dashboard)
     */
    public function count(): int{
        return count($_SESSION['chat_vectors'] ?? []);
    }

    public function clear(): void { 
        $_SESSION['chat_vectors'] = []; 
    }
}