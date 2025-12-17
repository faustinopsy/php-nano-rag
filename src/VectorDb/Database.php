<?php

declare(strict_types=1);

namespace NanoRag\VectorDb;

use RuntimeException;

class Database{
    private array $documents = [];
    private string $filePath;

    public function __construct(string $filePath = 'vector_store.json'){
        $this->filePath = $filePath;
        $this->load();
    }

    public function addDocument(string $text, array $vector, array $metadata = []): string{
        $id = uniqid('doc_', true);
        $this->documents[$id] = [
            'id' => $id,
            'text' => $text,
            'vector' => $vector,
            'metadata' => $metadata,
            'created_at' => time()
        ];

        $this->save();
        return $id;
    }

    /**
     * Realiza a busca semântica
     * @param array $queryVector O vetor da pergunta do usuário
     * @param int $k Quantidade de resultados para retornar
     * @param float $minScore Score mínimo (0 a 1) para considerar relevante
     */
    public function search(array $queryVector, int $k = 3, float $minScore = 0.0): array{
        $results = [];

        foreach ($this->documents as $doc) {
            $score = MathUtils::cosineSimilarity($queryVector, $doc['vector']);

            if ($score >= $minScore) {
                // Injetamos o score no resultado para ordenação
                $doc['score'] = $score;
                // Removemos o vetor do resultado final para economizar memória na saída
                unset($doc['vector']); 
                $results[] = $doc;
            }
        }

        // Ordena: Maior score primeiro
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($results, 0, $k);
    }

    private function save(): void{
        $json = json_encode($this->documents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($this->filePath, $json) === false) {
            throw new RuntimeException("Falha ao salvar o banco de dados em: {$this->filePath}");
        }
    }

    private function load(): void{
        if (!file_exists($this->filePath)) {
            return;
        }
        $content = file_get_contents($this->filePath);
        $data = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            $this->documents = $data;
        }
    }
    
    public function count(): int{
        return count($this->documents);
    }
}