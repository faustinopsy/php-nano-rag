# Nano RAG for PHP

**Bring Retrieval-Augmented Generation (RAG) to shared hosting without complex vector databases.**

Nano RAG is a pure PHP library that implements a local Vector Database using flat JSON files and Cosine Similarity math. It bridges your PHP application with local LLMs (like Ollama) to create intelligent, context-aware chatbots.

## 🚀 Features
- **No Database Required:** Stores embeddings in local JSON files.
- **Pure PHP Math:** Native implementation of Cosine Similarity (Vector Search).
- **Ollama Integration:** Connects easily to local AI models via cURL.
- **Long-term Memory:** Teaches the AI new facts that persist across requests.

## 📦 Requirements
- PHP 8.2+
- Ollama running locally (or remote URL)
- Models: `nomic-embed-text` and `llama3.2` (or compatible)

## Quick Start

```php
use NanoRag\RagEngine\Brain;

$brain->learn("My secret password is 'Paamayim Nekudotayim'.");
echo $brain->ask("What is the secret password?");
```

## Innovation
Unlike huge frameworks like LangChain, Nano RAG is designed for the "PHP way": simple, drop-in, and effective for standard web hosting environments.