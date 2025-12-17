<?php

declare(strict_types=1);

namespace NanoRag\VectorDb;

use RuntimeException;

class Memory{
    private array $documents = [];
    private string $filePath;

    public function __construct(string $filePath = 'cortex_memory.json'){
        $this->filePath = $filePath;
        $this->load();
    }
    
    public function addDocument(string $text, array $vector, array $metadata = []): string {
        $id = uniqid('mem_', true); // <-- Mudei prefixo de doc_ para mem_

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

    public function search(array $queryVector, int $k = 3, float $minScore = 0.0): array{
        $results = [];
        foreach ($this->documents as $doc) {
            $score = MathUtils::cosineSimilarity($queryVector, $doc['vector']);
            if ($score >= $minScore) {
                $doc['score'] = $score;
                unset($doc['vector']); 
                $results[] = $doc;
            }
        }
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($results, 0, $k);
    }

    private function save(): void {
        $json = json_encode($this->documents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->filePath, $json);
    }

    private function load(): void {
        if (file_exists($this->filePath)) {
            $this->documents = json_decode(file_get_contents($this->filePath), true) ?? [];
        }
    }
    
    public function count(): int {
        return count($this->documents);
    }
}