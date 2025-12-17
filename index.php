<?php
require_once __DIR__ . '/vendor/autoload.php';

use NanoRag\LlmClient\OllamaClient;
use NanoRag\VectorDb\Memory;
use NanoRag\RagEngine\Brain;

// Aumenta tempo de execução para uploads grandes
set_time_limit(300); 

// --- Configuração ---
$dbFile = 'knowledge_base.json';
$llm = new OllamaClient(embedModel: 'nomic-embed-text', chatModel: 'llama3.2');
$db = new Memory($dbFile);
$brain = new Brain($llm, $db);

$message = '';
$answer = '';
$contextUsed = [];

// 1. Limpar Memória
if (isset($_POST['action']) && $_POST['action'] === 'clear') {
    if (file_exists($dbFile)) {
        unlink($dbFile);
        header("Refresh:0");
        exit;
    }
}

// 2. Upload e Aprendizado (Ingestão)
if (isset($_FILES['txt_file']) && $_FILES['txt_file']['error'] === UPLOAD_ERR_OK) {
    $content = file_get_contents($_FILES['txt_file']['tmp_name']);
    
    if (trim($content) === '') {
        $message = '<div class="alert alert-warning">O arquivo está vazio.</div>';
    } else {
        // ESTRATÉGIA DE CHUNKING:
        // Quebramos o texto por quebras de linha duplas (parágrafos)
        // Isso melhora muito a precisão da busca vetorial.
        $chunks = preg_split('/\n\s*\n/', $content);
        $count = 0;

        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);
            if (strlen($chunk) > 20) { // Ignora frases muito curtas
                // Adicionamos o nome do arquivo como metadado
                $brain->learn($chunk, ['source' => $_FILES['txt_file']['name']]);
                $count++;
            }
        }
        $message = '<div class="alert alert-success">Sucesso! A IA aprendeu ' . $count . ' novos parágrafos deste arquivo.</div>';
    }
}

// 3. Perguntar (RAG)
if (isset($_POST['question']) && !empty($_POST['question'])) {
    $question = trim($_POST['question']);
    // Chamamos o Brain, mas vamos capturar o que ele usou de contexto (simulação)
    // Na classe original não retornamos o contexto separado, então confiamos na resposta.
    $answer = $brain->ask($question);
}

$memorySize = $brain->getMemorySize();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nano RAG - PHP Vector Search</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .chat-box { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); min-height: 300px; }
        .ai-msg { background-color: #e9ecef; padding: 15px; border-radius: 15px; border-bottom-left-radius: 0; margin-bottom: 20px; }
        .user-msg { background-color: #0d6efd; color: white; padding: 15px; border-radius: 15px; border-bottom-right-radius: 0; margin-bottom: 20px; text-align: right; }
        .sidebar { background: #212529; color: #fff; min-height: 100vh; padding: 20px; }
        .stat-card { background: #343a40; padding: 15px; border-radius: 8px; margin-bottom: 15px; text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: #0d6efd; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 sidebar">
            <h3 class="mb-4">🤖 Nano RAG</h3>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $memorySize; ?></div>
                <div class="small">Vetores na Memória</div>
            </div>

            <hr>
            
            <h5>📂 Ingestão de Dados</h5>
            <p class="small text-muted">Envie arquivos .txt para ensinar a IA.</p>
            
            <form method="post" enctype="multipart/form-data" class="mb-4">
                <div class="mb-3">
                    <input type="file" name="txt_file" class="form-control form-control-sm" accept=".txt" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Ensinar IA</button>
            </form>

            <hr>
            
            <form method="post" onsubmit="return confirm('Tem certeza? Isso apaga todo o conhecimento.');">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn btn-danger btn-sm w-100">🗑️ Limpar Memória</button>
            </form>

            <div class="mt-4 small text-muted">
                Engine: PHP 8.2+<br>
                Model: Llama 3.2<br>
                Embed: Nomic-Embed
            </div>
        </div>

        <div class="col-md-9 p-5">
            <?php echo $message; ?>

            <div class="chat-box">
                <?php if ($answer): ?>
                    <div class="d-flex justify-content-end">
                        <div class="user-msg" style="max-width: 80%;">
                            <strong>Você:</strong><br>
                            <?php echo htmlspecialchars($_POST['question']); ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-start">
                        <div class="ai-msg" style="max-width: 80%;">
                            <strong>🤖 Nano RAG:</strong><br>
                            <?php echo nl2br(htmlspecialchars($answer)); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted mt-5">
                        <h4>Olá! 👋</h4>
                        <p>Eu sou um sistema RAG em PHP Puro.</p>
                        <p>Faça upload de um arquivo de texto ao lado e me faça perguntas sobre ele.</p>
                    </div>
                <?php endif; ?>
            </div>

            <form method="post" class="mt-4">
                <div class="input-group input-group-lg">
                    <input type="text" name="question" class="form-control" placeholder="Faça uma pergunta sobre os documentos..." required autofocus>
                    <button class="btn btn-success" type="submit">Enviar 🚀</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>