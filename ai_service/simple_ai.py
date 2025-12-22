#!/usr/bin/env python3
"""
Simple AI Service for Symbiosis - No Dependencies Version
Just provides immediate responses without LLM complexity
"""

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from typing import Dict, Any
import json

app = FastAPI(title="Symbiosis AI Service - Simple")

# Enable CORS for browser requests
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.get("/")
async def root():
    return {
        "message": "Holistic Agricultural AI Service",
        "wisdom": "Where ancient farming wisdom meets modern intelligence",
        "features": [
            "Sacred geometry plant spacing",
            "Biodynamic calendar guidance", 
            "Energetic companion planting",
            "Cosmic timing optimization",
            "Living mandala garden design"
        ],
        "status": "simple_mode"
    }

@app.post("/ask")
async def ask_simple(payload: Dict):
    """Main ask endpoint - tries Ollama first, then falls back to smart responses"""
    try:
        question = payload.get("question", "").lower()
        crop = payload.get("crop_type", "")
        season = payload.get("season", "")
        
        # First try Ollama LLM
        try:
            import requests
            
            # Build context for LLM
            context_parts = []
            if crop:
                context_parts.append(f"Crop: {crop}")
            if season:
                context_parts.append(f"Season: {season}")
            
            # Create beginner-friendly, concise prompt
            if context_parts:
                enhanced_prompt = f"You are Symbiosis Mistral, a friendly agricultural AI helper. Context: {' | '.join(context_parts)}. Question: {question}. Provide a clear, concise answer in simple terms that a beginner farmer can easily understand and apply. Keep it practical and brief."
            else:
                enhanced_prompt = f"You are Symbiosis Mistral, a friendly agricultural AI helper. Question: {question}. Provide a clear, concise answer in simple terms that a beginner farmer can easily understand and apply. Keep it practical and brief."
            
            # Call Ollama with 90 second timeout
            print(f"Calling Ollama with prompt: {enhanced_prompt[:100]}...")
            ollama_response = requests.post(
                "http://localhost:11434/api/generate",
                json={
                    "model": "mistral",
                    "prompt": enhanced_prompt,
                    "stream": False
                },
                timeout=90  # Extended timeout to 90 seconds for testing
            )
            
            print(f"Ollama response status: {ollama_response.status_code}")
            
            if ollama_response.status_code == 200:
                ollama_data = ollama_response.json()
                ai_response = ollama_data.get('response', '').strip()
                
                if ai_response:  # Only return if we got a real response
                    return {
                        "success": True,
                        "answer": f"🤖 **Symbiosis Mistral (via Ollama):** {ai_response}",
                        "source": "ollama_llm"
                    }
                    
        except Exception as llm_error:
            print(f"LLM Error: {llm_error}")
        
        # Fallback to smart rule-based response
        response = generate_smart_response(question, crop, season)
        
        return {
            "success": True,
            "answer": response,
            "source": "symbiosis_smart"
        }
        
    except Exception as e:
        return {
            "success": True,
            "answer": "🌱 The farm wisdom flows eternally. Ask me about your agricultural needs!",
            "source": "fallback"
        }

@app.post("/ask-ollama")
async def ask_ollama(payload: Dict):
    """Endpoint that actually uses Ollama LLM"""
    try:
        question = payload.get("question", "")
        crop = payload.get("crop_type", "")
        season = payload.get("season", "")
        
        # Build context for LLM
        context_parts = []
        if crop:
            context_parts.append(f"Crop: {crop}")
        if season:
            context_parts.append(f"Season: {season}")
        
        # Create beginner-friendly prompt for LLM
        if context_parts:
            enhanced_prompt = f"You are Symbiosis Mistral, a friendly agricultural AI helper. Context: {' | '.join(context_parts)}. Question: {question}. Provide a clear, concise answer in simple terms that a beginner farmer can easily understand and apply. Keep it practical and brief."
        else:
            enhanced_prompt = f"You are Symbiosis Mistral, a friendly agricultural AI helper. Question: {question}. Provide a clear, concise answer in simple terms that a beginner farmer can easily understand and apply. Keep it practical and brief."
        
        # Call Ollama directly
        import requests
        
        ollama_response = requests.post(
            "http://localhost:11434/api/generate",
            json={
                "model": "mistral",
                "prompt": enhanced_prompt,
                "stream": False
            },
            timeout=30
        )
        
        if ollama_response.status_code == 200:
            ollama_data = ollama_response.json()
            ai_response = ollama_data.get('response', '').strip()
            
            return {
                "success": True,
                "answer": ai_response,
                "source": "ollama_llm"
            }
        else:
            # Fallback to rule-based if Ollama fails
            response = generate_smart_response(question.lower(), crop, season)
            return {
                "success": True,
                "answer": f"🤖 [Ollama unavailable, using smart fallback]: {response}",
                "source": "smart_fallback"
            }
            
    except Exception as e:
        # Fallback to rule-based if anything fails
        response = generate_smart_response(question.lower(), crop, season)
        return {
            "success": True,
            "answer": f"🌱 [LLM error, using wisdom]: {response}",
            "source": "error_fallback"
        }

def generate_smart_response(question: str, crop: str = "", season: str = "") -> str:
    """Generate intelligent responses based on keywords and context"""
    
    # Companion planting responses
    if any(word in question for word in ["companion", "plant with", "together", "pair"]):
        if crop == "tomato" or "tomato" in question:
            return """🍅 For tomatoes, I recommend these sacred companions:

**Protective Circle (Outer Ring):**
- Basil: Repels aphids and enhances flavor through aromatic synergy
- Marigolds: Creates a golden mandala of protection against nematodes
- Nasturtiums: Attracts beneficial insects in spiral patterns

**Sacred Geometry Spacing:** Use golden ratio (1:1.618) for distances. Plant basil 8 inches from tomatoes, marigolds 13 inches away in hexagonal patterns.

**Lunar Timing:** Plant companions during waxing moon for growth energy. Current moon phase favors this sacred partnership! 🌙"""

        elif crop == "carrot" or "carrot" in question:
            return """🥕 Carrots thrive in these biodynamic partnerships:

**Earth Element Allies:**
- Chives: Underground harmony - roots don't compete
- Radishes: Break soil for carrot penetration in Fibonacci spirals
- Sage: Aromatic protection in the herb spiral

**Sacred Pattern:** Plant carrots in triangular beds with companions at the vertices. Use 3-5-8 inch spacing following nature's mathematics.

**Cosmic Timing:** Root crops planted during waning moon (earth-drawing energy) develop stronger connections to soil minerals."""

        else:
            return f"""🌿 For {crop or 'your crops'}, consider these holistic partnerships:

**Universal Companions:**
- Herbs in spirals: Basil, oregano, thyme create protective aromatics
- Flowers in mandalas: Marigolds, nasturtiums, calendula for beneficial insects
- Alliums in circles: Onions, garlic, chives for natural pest deterrence

**Sacred Geometry:** Arrange in hexagonal patterns - nature's most efficient shape. Golden ratio spacing creates energetic harmony."""

    # Timing and planting questions
    elif any(word in question for word in ["when", "timing", "plant", "seed", "start"]):
        moon_phase = get_current_moon_phase()
        return f"""🌙 **Current Moon Phase: {moon_phase}**

{get_moon_timing_advice(moon_phase, crop, season)}

**Sacred Calendar Guidance:**
- **New Moon:** Perfect for setting intentions and planting seeds
- **Waxing Moon:** Transplant and nurture growth
- **Full Moon:** Harvest at peak vitality
- **Waning Moon:** Root development and soil preparation

**Season-Specific Wisdom ({season or 'current season'}):**
{get_seasonal_advice(season, crop)}"""

    # Sacred geometry and spacing
    elif any(word in question for word in ["spacing", "geometry", "pattern", "layout", "design"]):
        return f"""📐 **Sacred Geometry for {crop or 'Your Garden'}:**

**Golden Ratio Spacing (1:1.618):**
- Primary plants: 1 unit apart
- Secondary companions: 1.618 units 
- Tertiary elements: 2.618 units (golden spiral)

**Fibonacci Patterns:**
- Row spacing: 1, 1, 2, 3, 5, 8, 13 inches
- Creates natural harmony and optimal resource use

**Mandala Garden Design:**
- Center: Primary crop in sacred circle
- Ring 1: Close companions (protection herbs)
- Ring 2: Beneficial flowers (insect allies) 
- Ring 3: Support crops (nitrogen fixers)

**Hexagonal Efficiency:** Nature's perfect pattern - maximizes space while creating energetic flow between plants."""

    # General farming wisdom
    elif any(word in question for word in ["help", "advice", "wisdom", "guidance"]):
        return f"""🌟 **Symbiosis Agricultural Wisdom:**

Welcome to holistic farming! Here's guidance for {crop or 'your farm'}:

**Core Principles:**
- **Listen to the Land:** Every field has its own energy signature
- **Work with Cycles:** Moon, seasons, natural rhythms guide timing
- **Sacred Patterns:** Use geometry found in nature (spirals, hexagons, golden ratio)
- **Living Systems:** Create beneficial relationships between all elements

**Immediate Actions:**
1. Observe soil energy through plant indicators
2. Plan companion relationships using mandala patterns  
3. Time activities with lunar and seasonal cycles
4. Build biodiversity through polyculture design

**Cosmic Connection:** Remember that plants are antennae for cosmic forces. Honor this partnership! ✨"""

    else:
        # Default wise response
        return f"""🌱 **Agricultural Wisdom for {crop or 'Your Journey'}:**

Every question holds the seed of deeper understanding. Whether you're asking about {crop or 'your crops'}, remember these eternal principles:

**Sacred Agriculture Fundamentals:**
- Plants are cosmic antennae receiving stellar influences
- Soil is a living organism requiring respect and nourishment  
- Timing aligns with universal rhythms (moon, planets, seasons)
- Sacred geometry creates harmony between all elements

**Practical Wisdom:** Combine ancient knowledge with observation. Your land will teach you its secrets when you listen with reverence.

Ask me specifically about companion planting, timing, spacing, or any farming wisdom you seek! 🌙✨"""

def get_current_moon_phase() -> str:
    """Return current moon phase - simplified for demo"""
    import datetime
    # Simplified calculation - in production would use actual lunar data
    day = datetime.datetime.now().day
    if day < 8:
        return "New Moon"
    elif day < 15:
        return "Waxing Crescent"
    elif day < 22:
        return "Full Moon"
    else:
        return "Waning Moon"

def get_moon_timing_advice(phase: str, crop: str, season: str) -> str:
    """Get moon phase specific advice"""
    advice_map = {
        "New Moon": f"🌑 Excellent time to plant {crop or 'seeds'} with intention and blessing ceremonies.",
        "Waxing Crescent": f"🌒 Perfect for transplanting {crop or 'seedlings'} - growth energy is building.",
        "Full Moon": f"🌕 Ideal for harvesting {crop or 'crops'} at peak vitality and life force.",
        "Waning Moon": f"🌘 Good time for root development of {crop or 'crops'} and soil preparation."
    }
    return advice_map.get(phase, f"Work with natural rhythms for {crop or 'your crops'}.")

def get_seasonal_advice(season: str, crop: str) -> str:
    """Get season-specific advice"""
    if season == "spring":
        return f"Spring energy awakens! Perfect time for {crop or 'leafy greens and early crops'}. Plant with rising earth energy."
    elif season == "summer":
        return f"Summer's fire element supports {crop or 'fruiting plants'}. Focus on maintaining and nurturing growth."
    elif season == "fall":
        return f"Autumn prepares for rest. Good time for {crop or 'root crops and winter preparation'}."
    else:
        return f"Winter's contemplation period. Plan next season's {crop or 'crop rotations'} and restore soil energy."

@app.post("/api/v1/contextual-help")
async def contextual_help(payload: Dict):
    """Contextual help endpoint for admin pages"""
    try:
        page_context = payload.get("page_context", "")
        question = payload.get("question", "")
        current_section = payload.get("current_section", "")
        
        # Handle shipping classes context
        if page_context == "shipping-classes":
            if "explain" in question.lower() or "what" in question.lower():
                response = """📦 **Shipping Classes Create Page**

This page allows you to create new shipping classes for your WooCommerce store. Shipping classes help you organize products by delivery requirements and costs.

**Key Fields:**
- **Name**: Display name for the shipping class (e.g., "Fragile Items", "Heavy Equipment")
- **Description**: Optional details about this shipping class
- **Cost**: Base shipping cost for this class
- **Is Farm Collection**: Check if this class allows farm pickup instead of delivery

**Usage:**
- Assign shipping classes to products in WooCommerce
- Different classes can have different shipping rates
- Farm collection bypasses delivery costs
- Helps organize your delivery schedule and logistics

**Tips:**
- Use descriptive names that help customers understand shipping options
- Consider weight, size, and fragility when creating classes
- Farm collection is great for local customers who prefer pickup"""
            else:
                response = f"For shipping classes, you asked: '{question}'. This page manages WooCommerce shipping classifications for organizing delivery costs and methods."
        
        # Handle other contexts
        elif page_context == "succession-planning":
            response = "Succession planning helps you schedule crop plantings in sequence for continuous harvest throughout the season."
        elif page_context == "user-management":
            response = "User management handles customer accounts, permissions, and access control for the admin system."
        else:
            response = f"This is the {page_context.replace('-', ' ')} section. {question} - I'm here to help with admin tasks!"
        
        return {
            "success": True,
            "response": response,
            "sources": ["admin_help"],
            "context_found": True
        }
        
    except Exception as e:
        return {
            "success": False,
            "response": "Sorry, I encountered an error. Please try again.",
            "sources": [],
            "context_found": False,
            "error": str(e)
        }

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8005)
