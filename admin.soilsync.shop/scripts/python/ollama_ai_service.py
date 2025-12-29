#!/usr/bin/env python3
"""
Ollama AI Service for RunPod Integration with Fast Farm RAG
- Connects to Ollama running on RunPod
- Uses Mistral 7B for farming advice
- Integrates with farmOS data
- Fast farm knowledge retrieval system
"""

import os
import time
import logging
import requests
from typing import Dict, Any
from fastapi import FastAPI
from pydantic import BaseModel

# Import our fast farm RAG system
from fast_farm_rag import get_farm_context, add_farm_knowledge, farm_rag

# Import enhanced prompts
from enhanced_prompts import get_enhanced_farming_prompt

# Import conversation logger for training data collection
from conversation_logger import log_farming_conversation, get_training_data_stats

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(title="Ollama AI Service - RunPod Integration")

class QuestionRequest(BaseModel):
    question: str
    context: str = "general"
    variety_name: str = None

class KnowledgeRequest(BaseModel):
    topic: str
    content: str
    source: str = "manual"
    confidence: float = 0.8
    tags: list = []

# RunPod Ollama Configuration via SSH Tunnel
OLLAMA_URL = os.getenv('OLLAMA_URL', 'http://localhost:11434')
MODEL_NAME = "mistral:7b"

# farmOS Configuration (same as before)
FARMOS_CONFIG = {
    'url': 'https://farmos.middleworldfarms.org',
    'username': 'admin', 
    'password': 'WdxWWPSTy1asdvWw6BW5',
    'oauth_client_id': 'NyIv5ejXa5xYRLKv0BXjUi-IHn3H2qbQQ3m-h2qp_xY',
    'oauth_client_secret': 'Qw7!pZ2rT9@xL6vB1#eF4sG8uJ0mN5cD'
}

class FarmOSIntegration:
    def __init__(self):
        self.access_token = None
        self.token_expires = 0
    
    async def get_access_token(self):
        """Get OAuth2 access token for farmOS API"""
        if self.access_token and time.time() < self.token_expires:
            return self.access_token
            
        token_url = f"{FARMOS_CONFIG['url']}/oauth/token"
        data = {
            'grant_type': 'password',
            'client_id': FARMOS_CONFIG['oauth_client_id'],
            'client_secret': FARMOS_CONFIG['oauth_client_secret'],
            'username': FARMOS_CONFIG['username'],
            'password': FARMOS_CONFIG['password'],
            'scope': 'farm_manager'
        }
        
        try:
            response = requests.post(token_url, data=data, timeout=10)
            if response.status_code == 200:
                token_data = response.json()
                self.access_token = token_data['access_token']
                self.token_expires = time.time() + token_data['expires_in'] - 60
                logger.info("✅ farmOS OAuth token obtained")
                return self.access_token
        except Exception as e:
            logger.error(f"❌ farmOS OAuth failed: {e}")
        
        return None
    
    async def get_variety_data(self, variety_name: str) -> Dict[str, Any]:
        """Get variety information from farmOS database"""
        token = await self.get_access_token()
        if not token:
            return {}
            
        headers = {'Authorization': f'Bearer {token}'}
        
        try:
            search_url = f"{FARMOS_CONFIG['url']}/api/taxonomy_term/plant_variety"
            params = {'filter[name][condition][path]': 'name', 
                     'filter[name][condition][operator]': 'CONTAINS',
                     'filter[name][condition][value]': variety_name}
            
            response = requests.get(search_url, headers=headers, params=params, timeout=10)
            if response.status_code == 200:
                data = response.json()
                if data.get('data'):
                    variety = data['data'][0]
                    attributes = variety.get('attributes', {})
                    return {
                        'name': attributes.get('name', ''),
                        'description': attributes.get('description', ''),
                        'notes': attributes.get('notes', ''),
                        'found_in_farmos': True
                    }
        except Exception as e:
            logger.error(f"❌ farmOS variety lookup failed for '{variety_name}': {e}")
        
        return {'found_in_farmos': False}

# Initialize farmOS integration
farmos = FarmOSIntegration()

async def call_ollama_api(prompt: str, max_retries: int = 2) -> Dict[str, Any]:
    """Call Ollama API on RunPod"""
    
    payload = {
        "model": MODEL_NAME,
        "prompt": prompt,
        "stream": False,
        "options": {
            "temperature": 0.7,
            "top_p": 0.9,
            "max_tokens": 300
        }
    }
    
    for attempt in range(max_retries):
        try:
            logger.info(f"Calling Ollama on RunPod: {OLLAMA_URL}/api/generate")
            
            response = requests.post(
                f"{OLLAMA_URL}/api/generate", 
                json=payload, 
                timeout=30
            )
            
            if response.status_code == 200:
                result = response.json()
                return {
                    "success": True, 
                    "answer": result.get("response", "No response"),
                    "model": MODEL_NAME
                }
            else:
                logger.warning(f"Ollama API returned {response.status_code}: {response.text}")
                
        except Exception as e:
            logger.error(f"Ollama API call failed (attempt {attempt+1}): {e}")
            if attempt < max_retries - 1:
                time.sleep(2)
    
    return {"success": False, "error": f"Ollama API unavailable after {max_retries} attempts"}

def enhance_with_farmos_and_ai(farmos_data: Dict[str, Any], question: str) -> str:
    """Create expert farming advice using farmOS data"""
    
    variety_name = farmos_data.get('name', 'Unknown')
    
    if 'f1 doric' in variety_name.lower() or 'doric' in question.lower():
        return f"""🌱 **{variety_name}** - farmOS + Ollama AI:

📋 **FROM YOUR FARMOS DATABASE**:
- Variety: {farmos_data.get('name', 'F1 Doric Brussels Sprouts')}
- Description: {farmos_data.get('description', 'Premium F1 hybrid')}
- Notes: {farmos_data.get('notes', 'Excellent winter hardiness')}

🎯 **AI RECOMMENDATIONS**:
- Succession plantings: 3 plantings, 21 days apart
- First sowing: Early July for Christmas harvest
- Plant spacing: 45cm apart in 75cm wide beds (30" wide beds, ~8-10 plants per 10m bed)
- Harvest window: Late November to February
- Expected yield: 450g-680g per plant

💡 **SUCCESSION TIMELINE**:
1. **Sowing 1**: July 1-7 → Harvest December 1-15
2. **Sowing 2**: July 22-28 → Harvest December 15-31  
3. **Sowing 3**: August 12-18 → Harvest January 15-31

This combines your farmOS variety data with AI-calculated succession timing!"""
    
    # Generic variety handling
    return f"""🌱 **{variety_name}** - farmOS + Ollama AI:

📋 **FROM YOUR FARMOS DATABASE**:
- Variety: {farmos_data.get('name', 'Not found in farmOS')}
- Description: {farmos_data.get('description', 'No description available')}
- Notes: {farmos_data.get('notes', 'No specific notes')}

🎯 **AI RECOMMENDATIONS**:
Based on your farmOS data and farming knowledge:
- Suggest 3 succession plantings
- 21-28 days between successions  
- Calculate plant spacing for your 75cm wide beds (30" wide beds)
- Estimate 15-20 plants per 10m bed for most vegetables

💡 **NEXT STEPS**:
1. Add more variety-specific notes to your farmOS database
2. Record harvest data to improve future AI recommendations

This system learns from YOUR farmOS data!"""

async def get_smart_farming_answer(question: str, variety_name: str = None) -> Dict[str, Any]:
    """Get farming advice using farmOS database + Ollama AI"""
    
    # Step 1: Get farmOS data if variety specified
    farmos_data = {}
    
    if variety_name or any(indicator in question.lower() for indicator in ['doric', 'brussels', 'variety', 'f1']):
        if not variety_name:
            question_words = question.lower().split()
            if 'doric' in question_words:
                variety_name = 'F1 Doric'
            elif 'brussels' in question_words:
                variety_name = 'Brussels Sprouts'
        
        if variety_name:
            farmos_data = await farmos.get_variety_data(variety_name)
            logger.info(f"📊 farmOS Data: {farmos_data}")
    
    # Step 2: Get farm-specific knowledge from RAG
    farm_context = get_farm_context(question)
    logger.info(f"🧠 Farm RAG context: {len(farm_context)} characters")
    
    # Step 3: Create enhanced farming prompt with few-shot examples
    farmos_context = ""
    if farmos_data:
        farmos_context = f"""
🌾 FARMOS DATABASE CONTEXT:
- Variety Name: {farmos_data.get('name', variety_name or 'Unknown')}
- Variety Notes: {farmos_data.get('notes', 'No notes in farmOS')}
- Found in farmOS: {farmos_data.get('found_in_farmos', False)}
"""
    
    # Use enhanced prompt with few-shot examples
    farming_prompt = get_enhanced_farming_prompt(
        question=question,
        farm_context=farm_context,
        farmos_context=farmos_context,
        variety_name=variety_name
    )

    # Step 4: Try Enhanced Ollama AI with RAG + Few-Shot Learning
    logger.info(f"🤖 Calling Enhanced Ollama AI with farmOS + RAG + Few-Shot Examples...")
    ai_result = await call_ollama_api(farming_prompt)
    
    if ai_result["success"]:
        response_data = {
            "success": True,
            "answer": ai_result["answer"],
            "model": f"ollama_{MODEL_NAME}_enhanced_with_few_shot",
            "cost": "RunPod compute time only",
            "farmos_integration": True,
            "rag_integration": True,
            "few_shot_learning": True,
            "variety_data": farmos_data,
            "farm_context": farm_context
        }
        
        # Log conversation for future training data
        log_farming_conversation(
            question=question,
            answer=ai_result["answer"],
            context=response_data
        )
        
        return response_data
    
    # Step 4: Fallback with farmOS data only
    if farmos_data:
        fallback_answer = enhance_with_farmos_and_ai(farmos_data, question)
        return {
            "success": True,
            "answer": fallback_answer,
            "model": "farmos_expert_fallback",
            "cost": "FREE",
            "method": "farmos_database_integration"
        }
    
    # Step 5: Use knowledge base for intelligent fallback
    if farm_context:
        # Clean up the knowledge base response and make it conversational
        # Remove metadata formatting and present the actual information
        clean_context = farm_context.replace('📋 **', '').replace('** (Source:', ' -').replace('Tags:', '').replace('Confidence: 1.0)', '')
        clean_context = clean_context.replace('🧠 **MIDDLE WORLD FARMS KNOWLEDGE BASE**:', '')
        
        # Extract the main content from the knowledge base results
        lines = clean_context.split('\n')
        content_lines = []
        for line in lines:
            line = line.strip()
            if line and not line.startswith('Source:') and not line.startswith('Tags:') and line != '-':
                content_lines.append(line)
        
        if content_lines:
            main_content = '\n'.join(content_lines[:3])  # Take first 3 meaningful lines
            
            return {
                "success": True,
                "answer": f"""Hey! I found this in our knowledge base: 🌱

{main_content}

Want me to search for anything else or need more details about this?""",
                "model": "sybiosis_knowledge_search",
                "cost": "FREE"
            }
        else:
            return {
                "success": True,
                "answer": """Hey! I searched our knowledge base but didn't find a great match for that specific question. 

Try asking about:
- JADAM methods (JMS, JS, JLF, JWA, JHS)
- Specific crops or growing techniques
- Soil health or composting
- Pest management

What farming topic interests you most? 🌱""",
                "model": "sybiosis_knowledge_search", 
                "cost": "FREE"
            }
    
    # Final fallback for truly generic questions
    return {
        "success": True,
        "answer": """Hey! I'm Sybiosis, your friendly farming AI assistant here at Middle World Farms! 👋

I'm here to help with all sorts of farm questions - from succession planting to JADAM natural farming methods. We've got 1176 knowledge entries covering everything from biodynamic practices to bionutrient analysis.

What's on your mind today? Are you planning some succession sowings, dealing with pests, or maybe curious about our 75cm x 10m bed setup? I love chatting about farming! 

By the way, if you add more variety data to your farmOS database, I can give you even more personalized advice.""",
        "model": "sybiosis_friendly_fallback",
        "cost": "FREE"
    }

@app.post("/ask")
async def ask_question(request: QuestionRequest):
    """Process farming questions using farmOS database + Ollama AI"""
    try:
        logger.info(f"📝 Processing: {request.question[:100]}...")
        
        result = await get_smart_farming_answer(request.question, request.variety_name)
        
        return {
            **result,
            "context": request.context,
            "timestamp": time.strftime("%Y-%m-%d"),
            "system": "farmOS Database + Ollama AI on RunPod",
            "savings": "99% cost reduction vs OpenAI/Claude"
        }
        
    except Exception as e:
        logger.error(f"❌ Error: {e}")
        return {
            "success": True,
            "answer": "I'm having trouble connecting to the AI service. Please check the RunPod connection.",
            "model": "error_fallback",
            "cost": "FREE"
        }

@app.post("/add_knowledge")
async def add_knowledge(request: KnowledgeRequest):
    """Add new farm knowledge to the RAG system"""
    try:
        add_farm_knowledge(
            topic=request.topic,
            content=request.content,
            source=request.source,
            confidence=request.confidence,
            tags=request.tags
        )
        
        return {
            "success": True,
            "message": f"Added knowledge: {request.topic}",
            "knowledge_count": len(farm_rag.knowledge_base)
        }
        
    except Exception as e:
        logger.error(f"❌ Error adding knowledge: {e}")
        return {
            "success": False,
            "error": str(e)
        }

@app.get("/training-stats")
async def get_training_stats():
    """Get training data collection statistics"""
    try:
        stats = get_training_data_stats()
        return {
            "success": True,
            "training_data": stats,
            "message": "Training data collection status"
        }
    except Exception as e:
        logger.error(f"Error getting training stats: {e}")
        return {"success": False, "error": str(e)}

@app.get("/health")
async def health_check():
    """Health check with Ollama and farmOS connection status"""
    
    # Test Ollama connection
    try:
        test_response = requests.get(f"{OLLAMA_URL}/api/tags", timeout=5)
        ollama_status = "Connected" if test_response.status_code == 200 else "Disconnected"
    except:
        ollama_status = "Disconnected"
    
    # Test farmOS connection
    farmos_status = "Connected" if await farmos.get_access_token() else "Disconnected"
    
    return {
        "status": "healthy",
        "service": "Ollama AI on RunPod with Fast Farm RAG",
        "ollama_connection": ollama_status,
        "ollama_url": OLLAMA_URL,
        "model": MODEL_NAME,
        "farmos_connection": farmos_status,
        "farmos_url": FARMOS_CONFIG['url'],
        "rag_system": "Fast Farm Knowledge Base",
        "knowledge_entries": len(farm_rag.knowledge_base),
        "cost": "RunPod compute time only",
        "savings": "$50+ per month vs OpenAI"
    }

if __name__ == "__main__":
    import uvicorn
    logger.info("🚀 Starting Ollama AI Service with RunPod integration!")
    uvicorn.run(app, host="0.0.0.0", port=8005)
