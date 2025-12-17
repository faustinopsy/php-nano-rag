<?php

declare(strict_types=1);

namespace NanoRag\VectorDb;

use InvalidArgumentException;

class MathUtils{
    /**
     * Calcula a Similaridade de Cosseno entre dois vetores.
     * Retorna um float entre -1 e 1 (onde 1 é idêntico).
     */
    public static function cosineSimilarity(array $vecA, array $vecB): float{
        if (count($vecA) !== count($vecB)) {
            throw new InvalidArgumentException("Os vetores devem ter a mesma dimensão.");
        }

        $dotProduct = 0.0;
        $magnitudeA = 0.0;
        $magnitudeB = 0.0;

        foreach ($vecA as $i => $valA) {
            $valB = $vecB[$i] ?? 0.0;
            $dotProduct += $valA * $valB;
            $magnitudeA += $valA * $valA;
            $magnitudeB += $valB * $valB;
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        if ($magnitudeA * $magnitudeB == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }
}