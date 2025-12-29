#!/usr/bin/env python3
"""
Public Chatbot API Service
Customer-facing chatbot with multiple AI models and RAG integration
Separate from Martin's admin AI system
"""

import os
import sys
import asyncio
import uuid
import time
import logging
from datetime import datetime
from typing import Dict, List, Optional
import requests
import json

from fastapi import FastAPI, HTTPException, Request, Depends
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from pydantic import BaseModel
import uvicorn

# Add current directory to path for imports
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

from rag.public_rag_service import create_rag_service

# Configure logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(name)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

# FastAPI app
app = FastAPI(
    title="Middle World Farms - Public Chatbot",
    description="Customer-facing AI chatbot for agricultural advice and support",
    version="1.0.0",
    docs_url="/docs",
    redoc_url="/redoc"
)

# CORS middleware for WordPress integration
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Configure for your WordPress domain in production
    allow_credentials=True,
    allow_methods=["GET", "POST", "OPTIONS"],
    allow_headers=["*"],
)

# Initialize RAG service
rag_service = None

@app.on_event("startup")
async def startup_event():
    """Initialize services on startup"""
    global rag_service
    logger.info("🚀 Starting Public Chatbot API...")
    
    try:
        rag_service = create_rag_service()
        if rag_service:
            logger.info("✅ RAG service initialized")
        else:
            logger.warning("⚠️ RAG service not available")
    except Exception as e:
        logger.error(f"❌ Startup error: {e}")

@app.on_event("shutdown") 
async def shutdown_event():
    """Cleanup on shutdown"""
    global rag_service
    if rag_service:
        rag_service.close()
        logger.info("✅ RAG service closed")

# Request/Response Models
class ChatRequest(BaseModel):
    message: str
    model: Optional[str] = "auto"  # auto, phi3, gemma2, tinyllama
    session_id: Optional[str] = None
    include_history: Optional[bool] = False

class ChatResponse(BaseModel):
    response: str
    model_used: str
    session_id: str
    timestamp: str
    response_time: float
    confidence: Optional[float] = None
    enhanced_prompt: Optional[str] = None  # Show the RAG-enhanced prompt
    rag_context_used: Optional[bool] = False  # Whether RAG was used

class ModelInfo(BaseModel):
    name: str
    description: str
    speed: str
    best_for: List[str]
    available: bool

# Available models configuration
MODELS_CONFIG = {
    "tinyllama": {
        "name": "TinyLlama",
        "description": "Fast, lightweight responses for quick questions",
        "speed": "Very Fast (3-6 seconds)",
        "best_for": ["Quick questions", "Basic advice", "General farming info"],
        "timeout": 15
    },
    "gemma2": {
        "name": "Gemma2", 
        "description": "Balanced model for detailed agricultural advice",
        "speed": "Medium (8-15 seconds)",
        "best_for": ["Detailed advice", "Technical questions", "Problem solving"],
        "timeout": 25
    },
    "phi3": {
        "name": "Phi-3 Mini",
        "description": "Advanced model for complex agricultural consulting",
        "speed": "Slower (15-30 seconds)",
        "best_for": ["Complex planning", "In-depth analysis", "Professional advice"],
        "timeout": 40
    }
}

def get_session_id(request: Request) -> str:
    """Get or create session ID"""
    session_id = request.headers.get("X-Session-ID")
    if not session_id:
        session_id = str(uuid.uuid4())
    return session_id

def select_best_model(message: str, preferred_model: str = "auto") -> str:
    """Select the best model based on message complexity and preference"""
    if preferred_model != "auto" and preferred_model in MODELS_CONFIG:
        return preferred_model
        
    # Auto-selection based on message characteristics
    message_length = len(message)
    word_count = len(message.split())
    
    # Simple heuristics for model selection
    if word_count <= 10 and message_length <= 100:
        return "tinyllama"  # Quick questions
    elif word_count <= 25 and message_length <= 300:
        return "gemma2"     # Medium complexity
    else:
        return "tinyllama"  # Default to fast model for public chatbot
        
def query_enhanced_farm_ai(question: str, timeout: int = 60) -> str:
    """Query the enhanced farm AI system (premium option)"""
    try:
        payload = {
            "question": question,
            "context": "public_chatbot"
        }
        
        response = requests.post(
            "http://localhost:8005/ask",
            json=payload,
            timeout=timeout
        )
        response.raise_for_status()
        
        result = response.json()
        if result.get("success"):
            return result.get("answer", "Enhanced AI temporarily unavailable")
        else:
            return "Enhanced AI temporarily unavailable - using backup model"
            
    except Exception as e:
        logger.error(f"Enhanced AI error: {e}")
        return "Enhanced AI temporarily unavailable - using backup model"

def query_ollama_model(model: str, prompt: str, timeout: int = 30) -> str:
    """Query Ollama model with specified prompt (backup/basic option)"""
    try:
        model_map = {
            "tinyllama": "tinyllama:latest",
            "gemma2": "gemma2:2b", 
            "phi3": "phi3:mini",
            "enhanced": "enhanced_farm_ai"  # Special marker for enhanced AI
        }
        
        # Use enhanced AI if requested
        if model == "enhanced" or model == "premium":
            return query_enhanced_farm_ai(prompt, timeout)
        
        ollama_model = model_map.get(model, "tinyllama:latest")
        
        payload = {
            "model": ollama_model,
            "prompt": prompt,
            "stream": False,
            "options": {
                "temperature": 0.7,
                "num_predict": 400,  # Optimized for Phi3 - good balance of completeness and speed
                "top_k": 40,
                "top_p": 0.9
            }
        }
        
        response = requests.post(
            "http://localhost:11434/api/generate",
            json=payload,
            timeout=timeout
        )
        response.raise_for_status()
        
        result = response.json()
        return result.get("response", "I apologize, but I couldn't generate a response.")
        
    except requests.exceptions.Timeout:
        logger.error(f"Model {model} timeout")
        return "I apologize for the delay. Please try asking a simpler question or try again later."
    except requests.exceptions.RequestException as e:
        logger.error(f"Model {model} request error: {e}")
        return "I'm currently experiencing technical difficulties. Please try again later."
    except Exception as e:
        logger.error(f"Model {model} error: {e}")
        return "I apologize, but I encountered an error. Please try again."

@app.get("/")
async def root():
    """Service status and information"""
    return {
        "service": "Middle World Farms Public Chatbot",
        "status": "operational",
        "available_models": list(MODELS_CONFIG.keys()),
        "features": ["RAG-enhanced responses", "Multiple AI models", "WordPress integration"],
        "endpoints": ["/chat", "/models", "/health", "/wordpress/chat"],
        "version": "1.0.0",
        "timestamp": datetime.now().isoformat()
    }

@app.get("/models", response_model=List[ModelInfo])
async def get_models():
    """Get available AI models and their capabilities"""
    models = []
    
    for model_key, config in MODELS_CONFIG.items():
        # Test model availability
        try:
            test_response = requests.get("http://localhost:11434/api/tags", timeout=5)
            available = test_response.status_code == 200
        except:
            available = False
            
        models.append(ModelInfo(
            name=config["name"],
            description=config["description"], 
            speed=config["speed"],
            best_for=config["best_for"],
            available=available
        ))
    
    return models

@app.post("/chat", response_model=ChatResponse)
async def chat(request: ChatRequest, http_request: Request):
    """Main chat endpoint for customer interactions"""
    start_time = time.time()
    session_id = request.session_id or get_session_id(http_request)
    
    logger.info(f"💬 Chat request: {request.message[:50]}... (Model: {request.model})")
    
    try:
        # Input validation
        if len(request.message) > 1000:
            raise HTTPException(status_code=400, detail="Message too long (max 1000 characters)")
            
        # Select model
        selected_model = select_best_model(request.message, request.model)
        model_config = MODELS_CONFIG[selected_model]
        
        # Enhance prompt with RAG if available
        enhanced_prompt = request.message
        rag_context_used = False
        if rag_service:
            try:
                enhanced_prompt = rag_service.enhance_prompt(request.message, selected_model)
                rag_context_used = True
                logger.info("✅ Prompt enhanced with RAG context")
                logger.info(f"🔍 Enhanced prompt length: {len(enhanced_prompt)} chars")
            except Exception as e:
                logger.warning(f"⚠️ RAG enhancement failed: {e}")
        
        # Get AI response
        ai_response = query_ollama_model(
            selected_model, 
            enhanced_prompt, 
            model_config["timeout"]
        )
        
        response_time = time.time() - start_time
        
        # Log conversation if RAG service available
        if rag_service:
            try:
                rag_service.log_conversation(
                    session_id, request.message, ai_response, 
                    selected_model, response_time
                )
            except Exception as e:
                logger.warning(f"⚠️ Conversation logging failed: {e}")
        
        logger.info(f"✅ Response generated in {response_time:.2f}s using {selected_model}")
        
        return ChatResponse(
            response=ai_response,
            model_used=selected_model,
            session_id=session_id,
            timestamp=datetime.now().isoformat(),
            response_time=response_time,
            enhanced_prompt=enhanced_prompt if rag_context_used else None,
            rag_context_used=rag_context_used
        )
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"❌ Chat error: {e}")
        raise HTTPException(status_code=500, detail="Sorry, I'm having technical difficulties. Please try again.")

@app.post("/chat/{model_name}")
async def chat_specific_model(model_name: str, request: ChatRequest, http_request: Request):
    """Chat with a specific model"""
    if model_name not in MODELS_CONFIG:
        raise HTTPException(status_code=400, detail=f"Model '{model_name}' not available")
    
    # Override model selection
    request.model = model_name
    return await chat(request, http_request)

@app.get("/health")
async def health_check():
    """Health check endpoint"""
    try:
        # Test Ollama connection
        ollama_response = requests.get("http://localhost:11434/api/version", timeout=5)
        ollama_status = "connected" if ollama_response.status_code == 200 else "disconnected"
    except:
        ollama_status = "disconnected"
    
    # Test RAG service
    rag_status = "connected" if rag_service else "disconnected"
    
    return {
        "status": "healthy",
        "service": "Public Chatbot",
        "ollama_connection": ollama_status,
        "rag_service": rag_status,
        "models_available": len(MODELS_CONFIG),
        "timestamp": datetime.now().isoformat()
    }

# WordPress Integration Endpoints
@app.post("/wordpress/chat")
async def wordpress_chat(request: ChatRequest, http_request: Request):
    """WordPress-optimized chat endpoint"""
    # Add WordPress-specific handling
    response = await chat(request, http_request)
    
    # Format response for WordPress
    return {
        "success": True,
        "data": {
            "message": response.response,
            "model": response.model_used,
            "session": response.session_id,
            "timestamp": response.timestamp,
            "response_time": f"{response.response_time:.2f}s"
        }
    }

@app.get("/wordpress/widget-config")
async def wordpress_widget_config():
    """Configuration for WordPress widget"""
    return {
        "api_endpoint": "/wordpress/chat",
        "models": [
            {"key": "auto", "name": "Auto-Select", "description": "Best model for your question"},
            {"key": "tinyllama", "name": "Quick Response", "description": "Fast answers"},
            {"key": "gemma2", "name": "Detailed Advice", "description": "Comprehensive guidance"}
        ],
        "max_message_length": 1000,
        "features": ["biodynamic_farming", "crop_advice", "sustainable_agriculture"]
    }

if __name__ == "__main__":
    # Load environment variables
    host = os.getenv("CHATBOT_HOST", "0.0.0.0")
    port = int(os.getenv("CHATBOT_PORT", "8090"))
    
    logger.info(f"🚀 Starting Public Chatbot on {host}:{port}")
    uvicorn.run(app, host=host, port=port)
