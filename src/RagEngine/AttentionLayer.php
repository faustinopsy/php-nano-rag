<?php

declare(strict_types=1);

namespace NanoRag\RagEngine;

use NanoRag\VectorDb\Memory;

class AttentionLayer
{
    private Memory $longTerm;
    private ShortTermMemory $shortTerm;

    public function __construct(Memory $longTerm, ShortTermMemory $shortTerm)
    {
        $this->longTerm = $longTerm;
        $this->shortTerm = $shortTerm;
    }

    public function focus(array $queryVector, string $rawQuestion): array
    {
        $context = [
            'long_term' => '',
            'short_term' => '',
            'strategy' => 'general_chat'
        ];

        // 1. DETECÇÃO DE META-QUERY 
        // Se o usuário pergunta sobre a ordem, resumo ou o passado, ignoramos vetores
        // e pegamos o histórico linear bruto.
        if ($this->isMetaQuery($rawQuestion)) {
            $context['strategy'] = 'meta_analysis';
            
            // Pega as últimas 10 mensagens na ordem exata (Linear)
            // Isso simula o comportamento sequencial da LSTM
            $fullHistory = $this->shortTerm->getFullHistory(); 
            $recentHistory = array_slice($fullHistory, -10); 

            foreach ($recentHistory as $index => $msg) {
                // Adiciona um índice explícito para a IA saber contar (1ª, 2ª, 3ª...)
                $realIndex = array_search($msg, $fullHistory) + 1; // Indexação baseada em 1
                $role = strtoupper($msg['role']);
                $context['short_term'] .= "[Mensagem #{$realIndex}] $role: {$msg['content']}\n";
            }
            
            return $context;
        }

        // --- Fluxo Padrão (Busca Semântica) ---
        // 2. GATE DE LONGO PRAZO
        $facts = $this->longTerm->search($queryVector, 3, 0.30);
        if (!empty($facts)) {
            $context['strategy'] = 'retrieval';
            foreach ($facts as $fact) {
                $context['long_term'] .= "- " . $fact['text'] . "\n";
            }
        }

        // 3. GATE DE CURTO PRAZO (Híbrido: Recência + Vetor)
        // Agora sempre incluímos a ÚLTIMA mensagem para manter o fio da meada,
        // mais as semanticamente relevantes.
        $history = $this->shortTerm->getRelevantHistory($queryVector, 0.35);
        
        if (!empty($history)) {
            $context['strategy'] = ($context['strategy'] === 'retrieval') ? 'mixed' : 'context_followup';
            foreach ($history as $msg) {
                $role = strtoupper($msg['role']);
                $context['short_term'] .= "$role: {$msg['content']}\n";
            }
        }

        return $context;
    }

    /**
     * Uma heurística simples para detectar se o usuário quer falar sobre o histórico.
     * Em um sistema maior, isso seria outro classificador IA.
     */
    private function isMetaQuery(string $text): bool
    {
        $text = mb_strtolower($text);
        $keywords = [
            'minha terceira', 'minha segunda', 'minha primeira', 'última pergunta',
            'o que eu disse', 'o que eu perguntei', 'resuma', 'resumo',
            'conversamos', 'falei antes', 'minhas perguntas'
        ];

        foreach ($keywords as $word) {
            if (str_contains($text, $word)) {
                return true;
            }
        }
        return false;
    }
}