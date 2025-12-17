<?php
require_once __DIR__ . '/vendor/autoload.php';

use NanoRag\LlmClient\OllamaClient;
use NanoRag\VectorDb\Memory;
use NanoRag\RagEngine\ShortTermMemory;
use NanoRag\RagEngine\Brain;

set_time_limit(300); 

$dbFile = 'knowledge_base.json';

$llm = new OllamaClient(embedModel: 'nomic-embed-text', chatModel: 'llama3.2');
$longTerm = new Memory($dbFile); 
$shortTerm = new ShortTermMemory();
$brain = new Brain($llm, $longTerm, $shortTerm);

$message = '';
$answer = '';

if (isset($_POST['action'])) {
    if ($_POST['action'] === 'clear_db') {
        if (file_exists($dbFile)) unlink($dbFile);
        $message = '<div class="alert alert-danger">Memória de Longo Prazo (Arquivos) apagada.</div>';
        header("Refresh:1; url=index.php"); 
    }
    if ($_POST['action'] === 'clear_chat') {
        $shortTerm->clear(); // Limpa apenas a sessão
        $message = '<div class="alert alert-info">Memória de Curto Prazo (Chat) reiniciada.</div>';
    }
}

if (isset($_FILES['txt_file']) && $_FILES['txt_file']['error'] === UPLOAD_ERR_OK) {

    $content = file_get_contents($_FILES['txt_file']['tmp_name']);
    $chunks = preg_split('/\n\s*\n/', $content);
    foreach ($chunks as $chunk) {
        if (strlen(trim($chunk)) > 20) {
            $brain->learn($chunk, ['source' => $_FILES['txt_file']['name']]);
        }
    }
    $message = '<div class="alert alert-success">Arquivo aprendido!</div>';
}

if (isset($_POST['question']) && !empty($_POST['question'])) {
    $answer = $brain->ask($_POST['question']);
}

$chatHistory = $shortTerm->getFullHistory();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Nano RAG - Agente Conversacional</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .chat-container { height: 500px; overflow-y: auto; background: #f8f9fa; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .msg { margin-bottom: 15px; padding: 10px 15px; border-radius: 15px; max-width: 80%; }
        .msg-user { background-color: #0d6efd; color: white; margin-left: auto; text-align: right; border-bottom-right-radius: 2px; }
        .msg-assistant { background-color: #e9ecef; color: #333; margin-right: auto; text-align: left; border-bottom-left-radius: 2px; }
        .source-tag { font-size: 0.7em; color: #ccc; display: block; margin-top: 5px; }
    </style>
</head>
<body class="p-4">

<div class="container">
    <div class="row">
        <div class="col-md-4">
            <h3>🧠 Nano Brain</h3>
            <div class="card mb-3">
                <div class="card-body">
                    <h6>Status da Memória</h6>
                    <ul>
                        <li><strong>Longo Prazo:</strong> <?php echo $brain->getLongTermMemorySize(); ?> vetores</li>
                        <li><strong>Curto Prazo:</strong> <?php echo $brain->getShortTermMemoryCount(); ?> mensagens</li>
                    </ul>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data" class="mb-3">
                <label>Adicionar Conhecimento (.txt)</label>
                <input type="file" name="txt_file" class="form-control mb-2" required>
                <button type="submit" class="btn btn-sm btn-primary w-100">Ensinar</button>
            </form>

            <hr>
            
            <form method="post" class="d-flex gap-2">
                <button type="submit" name="action" value="clear_chat" class="btn btn-sm btn-outline-secondary flex-fill">Limpar Chat</button>
                <button type="submit" name="action" value="clear_db" class="btn btn-sm btn-outline-danger flex-fill">Apagar Arquivos</button>
            </form>
            <?php echo $message; ?>
        </div>

        <div class="col-md-8">
            <div class="chat-container mb-3" id="chatBox">
                <?php if (empty($chatHistory)): ?>
                    <p class="text-center text-muted mt-5">Inicie a conversa...</p>
                <?php else: ?>
                    <?php foreach ($chatHistory as $msg): ?>
                        <div class="msg msg-<?php echo $msg['role']; ?>">
                            <strong><?php echo $msg['role'] === 'user' ? 'Você' : 'Nano AI'; ?>:</strong><br>
                            <?php echo nl2br(htmlspecialchars($msg['content'])); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form method="post">
                <div class="input-group">
                    <input type="text" name="question" class="form-control" placeholder="Pergunte algo ou peça um resumo..." autofocus>
                    <button class="btn btn-success">Enviar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    var chatBox = document.getElementById("chatBox");
    chatBox.scrollTop = chatBox.scrollHeight;
</script>

</body>
</html>