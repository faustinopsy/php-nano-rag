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
#rag-loader-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(10, 25, 47, 0.85); 
    backdrop-filter: blur(5px);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}

.neural-orbit-loader {
    position: absolute;
    top: 28%;
    left: 45%;
    width: 120px;
    height: 120px;
}

.brain-core {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 40px;
    height: 40px;
    background: radial-gradient(circle, #00f2fe 0%, #4facfe 100%); 
    border-radius: 50%;
    transform: translate(-50%, -50%);
    box-shadow: 0 0 30px #00f2fe, 0 0 50px #4facfe; 
    animation: corePulse 2s ease-in-out infinite;
}

.orbit-ring {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 2px solid rgba(0, 242, 254, 0.1); 
    animation: ringRotate 3s linear infinite;
}

.data-node {
    position: absolute;
    width: 15px;
    height: 15px;
    background: #a855f7; 
    border-radius: 50%;
    box-shadow: 0 0 15px #a855f7;
}
.node-1 { top: 0; left: 50%; transform: translate(-50%, -50%); }
.node-2 { bottom: 0; left: 50%; transform: translate(-50%, 50%); background: #ff0080; box-shadow: 0 0 15px #ff0080; } /* Rosa */
.node-3 { left: 0; top: 50%; transform: translate(-50%, -50%); }
.loading-text {
    position: absolute;
    top: 18%;
    left: 43%;
    margin-top: 30px;
    color: #fff;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-weight: 300;
    letter-spacing: 2px;
    animation: textFade 2s ease-in-out infinite;
}
@keyframes corePulse {
    0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
    50% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.8; }
}

@keyframes ringRotate {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
@keyframes textFade {
    0%, 100% { opacity: 0.7; }
    50% { opacity: 1; }
}
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
<div id="rag-loader-overlay">
    <div class="neural-orbit-loader">
        <div class="brain-core"></div>
        <div class="orbit-ring">
            <div class="data-node node-1"></div>
            <div class="data-node node-2"></div>
            <div class="data-node node-3"></div>
        </div>
    </div>
    <div class="loading-text">PROCESSANDO RAG...</div>
</div>
<script>
   
    var chatBox = document.getElementById("chatBox");
    if(chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
    const loader = document.getElementById('rag-loader-overlay');
    const loadingText = loader.querySelector('.loading-text');

    function showLoader(text) {
        loadingText.innerText = text;
        loader.style.display = 'flex';
    }

    const uploadForm = document.querySelector('form[enctype="multipart/form-data"]');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function() {
            showLoader('ASSIMILANDO CONHECIMENTO...');
        });
    }

    const questionInput = document.querySelector('input[name="question"]');
    if (questionInput) {
        const chatForm = questionInput.closest('form');
        if (chatForm) {
            chatForm.addEventListener('submit', function() {
                if (questionInput.value.trim() !== '') {
                    showLoader('ACESSANDO MEMÓRIA & GERANDO RESPOSTA...');
                }
            });
        }
    }
    
    const clearForms = document.querySelectorAll('form:not([enctype])');
    clearForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (e.submitter && e.submitter.name === 'action') {
                 showLoader('LIMPANDO DADOS...');
            }
        });
    })
setTimeout(() => {
    loader.style.display = 'none';
}, 2000);
</script>
</body>
</html>