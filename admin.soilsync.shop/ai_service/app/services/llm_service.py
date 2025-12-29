# LLM and Retrieval Service for Symbiosis
# Provider-agnostic wrapper with simple local vector store (JSON) and cosine retrieval

import os
import json
import math
from typing import List, Dict, Any, Optional, Tuple
from dataclasses import dataclass

import numpy as np


# Default to local LLM (Ollama/LM Studio) and pgvector for vector DB
import requests

# Optional PostgreSQL import - will work without it
try:
    import psycopg2
    import psycopg2.extras
    POSTGRES_AVAILABLE = True
except ImportError:
    POSTGRES_AVAILABLE = False


@dataclass
class VectorRecord:
    text: str
    embedding: List[float]
    metadata: Dict[str, Any]



class PgVectorStore:
    """pgvector-based vector store for embeddings and texts."""
    def __init__(self):
        if not POSTGRES_AVAILABLE:
            raise ImportError("PostgreSQL is not available. Please install psycopg2-binary.")
            
        try:
            self.conn = psycopg2.connect(
                dbname=os.getenv('PGVECTOR_DB', 'vector_db'),
                user=os.getenv('PGVECTOR_USER', 'postgres'),
                password=os.getenv('PGVECTOR_PASSWORD', ''),
                host=os.getenv('PGVECTOR_HOST', 'localhost'),
                port=os.getenv('PGVECTOR_PORT', '5432'),
            )
            self._ensure_table()
        except Exception as e:
            print(f"Warning: Could not connect to PostgreSQL: {e}")
            self.conn = None

    def _ensure_table(self):
        with self.conn.cursor() as cur:
            cur.execute('''
                CREATE TABLE IF NOT EXISTS vectors (
                    id SERIAL PRIMARY KEY,
                    text TEXT,
                    embedding VECTOR(384),
                    metadata JSONB
                );
            ''')
            self.conn.commit()

    def add(self, texts: List[str], embeddings: List[List[float]], metadatas: Optional[List[Dict[str, Any]]] = None):
        if metadatas is None:
            metadatas = [{} for _ in texts]
        with self.conn.cursor() as cur:
            for t, e, m in zip(texts, embeddings, metadatas):
                cur.execute(
                    "INSERT INTO vectors (text, embedding, metadata) VALUES (%s, %s, %s)",
                    (t, e, json.dumps(m))
                )
            self.conn.commit()

    def query(self, query_embedding: List[float], top_k: int = 4) -> List[VectorRecord]:
        with self.conn.cursor(cursor_factory=psycopg2.extras.DictCursor) as cur:
            cur.execute(
                """
                SELECT text, embedding, metadata, (embedding <#> %s::vector) AS distance
                FROM vectors
                ORDER BY embedding <#> %s::vector ASC
                LIMIT %s
                """,
                (query_embedding, query_embedding, top_k)
            )
            rows = cur.fetchall()
            return [VectorRecord(text=row['text'], embedding=row['embedding'], metadata=row['metadata']) for row in rows]



class LLMService:
    """Provider-agnostic LLM wrapper with embeddings + simple retrieval (Ollama + pgvector)."""
    def __init__(self):
        self.provider = os.getenv('LLM_PROVIDER', 'ollama').lower()
        self.model = os.getenv('LLM_MODEL', 'mistral')
        self.ollama_url = os.getenv('OLLAMA_URL', 'http://localhost:11434')
        
        # Try to initialize vector store, but don't fail if unavailable
        try:
            if os.getenv('ENABLE_VECTOR_DB', 'true').lower() == 'true':
                self.store = PgVectorStore()
            else:
                self.store = None
        except Exception as e:
            print(f"Warning: Vector store unavailable: {e}")
            self.store = None
            
        self.embedding_model = os.getenv('EMBEDDING_MODEL', 'all-MiniLM-L6-v2')

    # ------------- Embeddings & Retrieval -------------
    def embed_texts(self, texts: List[str]) -> List[List[float]]:
        # Use sentence-transformers locally for embeddings
        from sentence_transformers import SentenceTransformer
        model = SentenceTransformer(self.embedding_model)
        return model.encode(texts, normalize_embeddings=True).tolist()

    def ingest_corpus(self, chunks: List[str], source: str = 'biodynamic_principles_core.txt') -> int:
        if not self.store:
            print("Warning: Vector store not available, skipping corpus ingestion")
            return 0
            
        embeddings = self.embed_texts(chunks)
        metadatas = [{"source": source, "chunk_index": i} for i in range(len(chunks))]
        self.store.add(chunks, embeddings, metadatas)
        return len(chunks)

    def retrieve_context(self, query: str, top_k: int = 4) -> List[VectorRecord]:
        if not self.store:
            print("Warning: Vector store not available, returning empty context")
            return []
            
        q_emb = self.embed_texts([query])[0]
        return self.store.query(q_emb, top_k=top_k)

    def chat(self, messages: List[Dict[str, str]]) -> str:
        # Compose prompt from messages
        prompt = "\n".join([f"{m['role'].capitalize()}: {m['content']}" for m in messages])
        data = {
            "model": self.model,
            "prompt": prompt,
            "stream": False
        }
        try:
            resp = requests.post(f"{self.ollama_url}/api/generate", json=data, timeout=120)
            resp.raise_for_status()
            result = resp.json()
            return result.get('response', '').strip()
        except Exception as e:
            return f"[Local LLM error: {e}]"

    # ------------- Utilities -------------
    @staticmethod
    def chunk_text(text: str, max_chars: int = 1000, overlap: int = 100) -> List[str]:
        """Simple paragraph-aware chunking with overlap."""
        if not text:
            return []
        paras = [p.strip() for p in text.split('\n') if p.strip()]
        chunks: List[str] = []
        buff: List[str] = []
        current_len = 0
        for p in paras:
            if current_len + len(p) + 1 <= max_chars:
                buff.append(p)
                current_len += len(p) + 1
            else:
                if buff:
                    chunks.append(' '.join(buff))
                    # start new buffer with overlap tail of previous
                    if overlap > 0 and chunks[-1]:
                        tail = chunks[-1][-overlap:]
                        buff = [tail, p]
                        current_len = len(tail) + 1 + len(p)
                    else:
                        buff = [p]
                        current_len = len(p)
                else:
                    chunks.append(p[:max_chars])
                    buff = [p[max_chars-overlap:]] if overlap > 0 else []
                    current_len = len(buff[0]) if buff else 0
        if buff:
            chunks.append(' '.join(buff))
        return chunks
