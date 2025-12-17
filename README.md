# Nano RAG for PHP

Nano RAG é uma biblioteca PHP de código aberto que implementa uma arquitetura de Geração Aumentada por Recuperação (RAG - Retrieval-Augmented Generation) utilizando puramente PHP e armazenamento em arquivos locais (JSON).

O projeto foi desenhado para ambientes onde bancos de dados vetoriais complexos (como Pinecone ou Milvus) não estão disponíveis, permitindo a criação de Agentes de IA com memória persistente e contextual em servidores de hospedagem compartilhada.

## Parte 1: Guia de Instalação e Uso

### Pré-requisitos do Sistema

1. **PHP 8.2** ou superior.
2. **Composer** para gerenciamento de dependências.
3. **Ollama** rodando localmente ou em um servidor acessível via rede.

### Configuração do Ollama

O Nano RAG depende do Ollama para processamento de linguagem natural. Você precisa instalar o servidor e dois modelos específicos: um para gerar vetores (Embeddings) e outro para o chat (LLM).

1. Baixe e instale o Ollama em ollama.com.
2. Abra seu terminal e execute os seguintes comandos para baixar os modelos necessários:

    ollama pull nomic-embed-text
    ollama pull llama3.2

*Nota: Você pode utilizar outros modelos, mas deve atualizar a configuração na instanciação da classe OllamaClient.*

### Instalação da Biblioteca

Na raiz do seu projeto, execute a instalação das dependências (atualmente configurado para autoload PSR-4):

    composer install

Para iniciar a interface web de demonstração:

1. Inicie um servidor PHP local: php -S localhost:8000
2. Acesse no navegador: http://localhost:8000

### Como Utilizar

O sistema opera através de um fluxo de ingestão (aprendizado) e consulta (chat).

#### 1. Ingestão de Dados (Aprendizado)
Para que a IA responda sobre seus dados, você deve "ensinar" o sistema enviando arquivos de texto (.txt).
* O sistema lê o arquivo.
* O texto é dividido em fragmentos menores (chunks).
* Cada fragmento é convertido em um vetor matemático e salvo na **Memória de Longo Prazo**.

#### 2. Realizando Consultas
Ao fazer uma pergunta, o sistema utiliza uma **Camada de Atenção** para decidir se deve buscar a resposta nos arquivos que você enviou ou no histórico da conversa atual.

---

## Parte 2: Aspectos Técnicos e Arquitetura

O Nano RAG diferencia-se por sua arquitetura modular inspirada em processos cognitivos, dividida em três pilares principais gerenciados por um orquestrador central.

### 1. O Cérebro (Brain Class)
A classe `Brain` atua como o controlador central. Ela não armazena dados, mas orquestra o fluxo de informações entre o cliente LLM (Ollama), as memórias e a camada de decisão. É responsável por receber o input do usuário e devolver a resposta final processada.

### 2. Memória de Longo Prazo (Long-Term Memory)
* **Função:** Armazenamento persistente de fatos e conhecimento "cristalizado".
* **Implementação:** Utiliza arquivos JSON locais (`knowledge_base.json`).
* **Técnica:** Armazena o texto original junto com seu **Embedding Vector**.
* **Recuperação:** Utiliza o algoritmo de **Similaridade de Cosseno** para encontrar matematicamente qual texto no banco de dados é mais próximo da pergunta do usuário. Funciona como uma biblioteca estática.

### 3. Memória de Curto Prazo (Short-Term Memory)
* **Função:** Manter o contexto da conversa atual (sessão), permitindo que a IA entenda referências como "ele", "aquilo" ou "a resposta anterior".
* **Implementação:** Baseada em sessões PHP (`$_SESSION`), mas enriquecida com vetores.
* **Diferencial:** Diferente de arrays simples, esta memória armazena o vetor de cada mensagem trocada. Isso permite que o sistema busque no histórico não apenas por ordem cronológica, mas por relevância semântica.

### 4. Camada de Atenção (Attention Layer)
Este é o componente mais complexo do sistema, atuando como um filtro lógico (Gatekeeper) antes de acionar a Inteligência Artificial. Ela resolve o problema de alucinação evitando enviar contexto irrelevante para o modelo.

A camada de atenção decide dinamicamente a estratégia de resposta:
* **Estratégia Retrieval:** Se a pergunta do usuário tem alta similaridade matemática com dados do JSON, a atenção foca na Memória de Longo Prazo.
* **Estratégia Contextual:** Se a pergunta se conecta semanticamente com a frase anterior, a atenção foca na Memória de Curto Prazo.
* **Estratégia Meta-Analysis:** Se o usuário pergunta sobre a própria conversa (ex: "O que eu perguntei antes?", "Resuma o chat"), a camada ignora a matemática e recupera o histórico linear bruto, simulando uma memória sequencial.

### Conceitos Fundamentais: Vetores e Embeddings

Para que o PHP realize buscas semânticas sem um banco de dados externo, utilizamos o conceito de Embeddings.

* **O que é um Embedding?**
    É uma representação numérica de um texto. O modelo `nomic-embed-text` transforma uma frase como "O PHP é uma linguagem de script" em uma lista de 768 números flutuantes (ex: `[0.123, -0.542, 0.991, ...]`).

* **Por que isso é necessário?**
    Computadores não entendem significado, apenas números. Ao transformar texto em números, podemos calcular a distância geométrica entre duas frases.
    * A frase "Eu gosto de maçã" terá um vetor matematicamente muito próximo de "Adoro frutas".
    * A frase "Eu gosto de maçã" terá um vetor distante de "O servidor caiu".

O Nano RAG calcula essas distâncias nativamente em PHP para determinar o que é relevante para responder ao usuário.