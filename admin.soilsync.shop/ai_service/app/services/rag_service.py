# RAG (Retrieval-Augmented Generation) Service
# Integrates vector search with LLM responses for enhanced agricultural intelligence

import os
import json
from typing import List, Dict, Any, Optional
from datetime import datetime

from app.services.llm_service import LLMService, PgVectorStore

class RAGService:
    """Retrieval-Augmented Generation service for agricultural knowledge"""

    def __init__(self):
        self.llm_service = LLMService()
        self.vector_store = PgVectorStore()
        self.knowledge_ingested = False

        # Initialize knowledge base
        self._initialize_knowledge_base()

    def _initialize_knowledge_base(self):
        """Initialize the agricultural knowledge base"""
        try:
            # Check if we have any documents in the vector store
            count = self._get_document_count()
            self.knowledge_ingested = count > 0
        except Exception as e:
            print(f"Warning: Could not initialize knowledge base: {e}")
            self.knowledge_ingested = False

    def _get_document_count(self) -> int:
        """Get the number of documents in the vector store"""
        if not self.vector_store.conn:
            return 0

        try:
            with self.vector_store.conn.cursor() as cursor:
                cursor.execute("SELECT COUNT(*) FROM general_knowledge")
                result = cursor.fetchone()
                return result[0] if result else 0
        except Exception:
            return 0

    def get_augmented_response(self, user_message: str, conversation_history: Optional[List[Dict[str, str]]] = None) -> Dict[str, Any]:
        """Get an augmented response using retrieved knowledge"""
        try:
            # Use the user message as the query
            query = user_message

            # Retrieve relevant knowledge
            relevant_docs = self._retrieve_relevant_knowledge(query, limit=3)

            # Build augmented prompt
            augmented_prompt = self._build_augmented_prompt(query, relevant_docs, conversation_history)

            # Get LLM response by calling the main AI service
            import requests
            ai_response = requests.post(
                'http://localhost:8005/ask',
                json={'question': augmented_prompt},
                timeout=60
            )
            
            if ai_response.status_code == 200:
                response_data = ai_response.json()
                response = response_data.get('answer', augmented_prompt)
            else:
                response = f"AI Service Error: {ai_response.status_code}"

            return {
                "response": response,
                "sources": [doc["metadata"] for doc in relevant_docs],
                "confidence": len(relevant_docs) / 3.0,  # Simple confidence score
                "rag_enabled": True
            }

        except Exception as e:
            print(f"RAG augmentation failed: {e}")
            return self.get_fallback_wisdom(user_message)

    def _retrieve_relevant_knowledge(self, query: str, limit: int = 3) -> List[Dict[str, Any]]:
        """Retrieve relevant knowledge documents"""
        if not self.vector_store.conn:
            return []

        try:
            # Generate embedding for the query
            query_embedding = self.llm_service.embed_texts([query])[0]

            # Search for similar documents in general_knowledge table
            with self.vector_store.conn.cursor(cursor_factory=psycopg2.extras.DictCursor) as cursor:
                cursor.execute(
                    """
                    SELECT id, title, content, source, page_number, chunk_index,
                           (embedding <#> %s::vector) AS distance
                    FROM general_knowledge
                    WHERE embedding IS NOT NULL
                    ORDER BY embedding <#> %s::vector ASC
                    LIMIT %s
                    """,
                    (query_embedding, query_embedding, limit)
                )
                rows = cursor.fetchall()

                # Convert to expected format
                return [
                    {
                        "text": row['content'],
                        "metadata": {
                            "id": row['id'],
                            "title": row['title'],
                            "source": row['source'],
                            "page_number": row['page_number'],
                            "chunk_index": row['chunk_index'],
                            "distance": row['distance']
                        }
                    }
                    for row in rows
                ]

        except Exception as e:
            print(f"Knowledge retrieval failed: {e}")
            return []

    def _build_augmented_prompt(self, query: str, relevant_docs: List[Dict[str, Any]],
                               context: Optional[Dict[str, Any]] = None) -> str:
        """Build an augmented prompt with retrieved knowledge"""

        # Base prompt
        prompt = f"""You are a holistic agricultural AI assistant with access to specialized knowledge.

Query: {query}

"""

        # Add context if provided
        if context:
            prompt += f"Context: {json.dumps(context)}\n\n"

        # Add retrieved knowledge
        if relevant_docs:
            prompt += "Relevant Knowledge:\n"
            for i, doc in enumerate(relevant_docs, 1):
                prompt += f"{i}. {doc['text'][:500]}...\n"
            prompt += "\n"

        prompt += """Please provide a comprehensive, practical response that incorporates both general agricultural wisdom and the specific knowledge provided above. Focus on biodynamic principles, sacred geometry, and sustainable farming practices."""

        return prompt

    def get_fallback_wisdom(self, query: str) -> Dict[str, Any]:
        """Provide fallback wisdom when RAG fails"""
        return {
            "response": f"🌱 Agricultural wisdom for: {query}\n\nWhile my knowledge base is being cultivated, here are some general principles:\n\n• Consider the lunar cycle and biodynamic preparations\n• Use companion planting with sacred geometry patterns\n• Build soil health through microbial diversity\n• Observe and work with natural rhythms\n\nSpecific recommendations would be more precise with access to my full knowledge garden.",
            "sources": [],
            "confidence": 0.0,
            "rag_enabled": False,
            "fallback": True
        }

    def ingest_knowledge(self, documents: List[Dict[str, Any]]) -> bool:
        """Ingest new knowledge documents into the vector store"""
        try:
            success_count = 0

            for doc in documents:
                text = doc.get("text", "")
                metadata = doc.get("metadata", {})

                if text:
                    # Generate embedding
                    embedding = self.llm_service.embed_texts([text])[0]

                    # Add to vector store
                    self.vector_store.add([text], [embedding], [metadata])
                    success_count += 1

            self.knowledge_ingested = success_count > 0
            return success_count > 0

        except Exception as e:
            print(f"Knowledge ingestion failed: {e}")
            return False

    def get_contextual_help(self, query: str, page_context: str = "") -> Dict[str, Any]:
        """Get contextual help based on page context and user query"""
        try:
            # Build enhanced query with page context
            enhanced_query = query
            if page_context:
                enhanced_query = f"Context: {page_context}. Help needed: {query}"

            # Retrieve relevant context from admin docs
            context_records = []
            
            # First try to get context from admin-docs specifically
            if self.llm_service.store:
                admin_context = self.llm_service.retrieve_context(f"admin-docs {enhanced_query}", top_k=3)
                context_records.extend(admin_context)

            # If no admin context, try general query
            if not context_records:
                general_context = self.llm_service.retrieve_context(enhanced_query, top_k=3)
                context_records.extend(general_context)

            # Build context string
            context_text = ""
            sources = []
            
            for record in context_records:
                context_text += f"\n{record.text}"
                sources.append(record.metadata.get('source', 'unknown'))

            # Generate response using LLM
            if context_text.strip():
                prompt = f"""You are an expert AI assistant helping users with the Middle World Farms admin system setup.

Current Page Context: {page_context}
User Question: {query}

Relevant Documentation:
{context_text}

Please provide clear, step-by-step guidance based on the documentation above. Focus on being helpful, accurate, and concise. If the documentation doesn't cover the specific question, provide general best practices for the admin system.

Response:"""

                response = self.llm_service.chat([{"role": "user", "content": prompt}])
                
                return {
                    "response": response,
                    "sources": list(set(sources)),  # Remove duplicates
                    "context_found": len(context_records) > 0,
                    "page_context": page_context
                }
            else:
                # Fallback response when no context found
                return {
                    "response": f"I don't have specific documentation for '{page_context}' yet, but I can help you navigate the admin system. What specific aspect are you trying to set up or configure?",
                    "sources": [],
                    "context_found": False,
                    "page_context": page_context
                }

        except Exception as e:
            print(f"Contextual help error: {e}")
            return {
                "response": f"I'm having trouble accessing the help system right now. Please try again, or check the documentation files in the docs folder for '{page_context}'.",
                "sources": [],
                "context_found": False,
                "error": str(e)
            }

    def get_knowledge_stats(self) -> Dict[str, Any]:
        """Get statistics about the knowledge base"""
        return {
            "documents_count": self._get_document_count(),
            "knowledge_ingested": self.knowledge_ingested,
            "vector_store_available": self.vector_store.conn is not None,
            "last_updated": datetime.now().isoformat()
        }


# Create singleton instance
rag_service = RAGService()