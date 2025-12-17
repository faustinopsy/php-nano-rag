<?php

require_once __DIR__ . '/../vendor/autoload.php';

use NanoRag\VectorDb\Database;

try {
    $db = new Database('json_db_test.json');
    echo "--- Inserindo Dados Simulados ---\n";
    // Conceito: PHP (Eixo X)
    $db->addDocument("Eu amo programar em PHP Moderno.", [1.0, 0.0, 0.0], ['topico' => 'php']);
    // Conceito: Python (Eixo Y)
    $db->addDocument("Data Science é forte com Python.", [0.0, 1.0, 0.0], ['topico' => 'python']);
    // Conceito: Laravel (Quase Eixo X)
    $db->addDocument("Laravel é um framework PHP incrível.", [0.9, 0.1, 0.0], ['topico' => 'php']);
    echo "Total documentos: " . $db->count() . "\n\n";
    // 2. Simulando uma busca
    // O usuário busca algo relacionado a PHP puro (Vetor [1, 0, 0])
    echo "--- Buscando por 'PHP' (Vetor [1, 0, 0]) ---\n";
    $queryVector = [1.0, 0.0, 0.0];
    $results = $db->search($queryVector, 2); // Top 2
    foreach ($results as $item) {
        printf("Score: %.4f | Texto: %s\n", $item['score'], $item['text']);
    }

    //unlink('json_db_test.json');

    echo "\nFase 2 Concluída: Matemática vetorial e JSON storage funcionando.\n";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}