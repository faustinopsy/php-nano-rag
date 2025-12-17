<?php

require_once __DIR__ . '/../vendor/autoload.php';

use NanoRag\VectorDb\Check;

try {
    $check = new Check();
    echo $check->sayHello() . PHP_EOL;
    echo "Fase 1 Concluída: Autoload via Composer está operante." . PHP_EOL;
} catch (Throwable $e) {
    echo "Erro: " . $e->getMessage() . PHP_EOL;
    echo "Verifique se rodou o comando 'composer dump-autoload'." . PHP_EOL;
}