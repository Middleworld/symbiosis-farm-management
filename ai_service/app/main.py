# FastAPI main application with Symbiosis Agricultural Intelligence
# Integrates sacred geometry, biodynamic principles, and energetic plant wisdom

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from datetime import datetime, date, timedelta
from typing import Optional, List, Dict, Tuple
import json
import math
import os
import glob
from dotenv import load_dotenv

# Load environment variables FIRST
load_dotenv()

from app.models.enhanced_crop_intelligence import EnhancedCropIntelligence
from app.models.holistic_intelligence import SymbiosisFarmIntelligence
from app.services.openfarm_sync import OpenFarmSyncService
from app.services.rag_service import rag_service
from app.services.llm_service import LLMService

app = FastAPI(
    title="Symbiosis Agricultural AI",
    description="Sacred geometry and biodynamic intelligence for farming",
    version="1.0.0"
)

# Enable CORS for Laravel integration
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Configure for your Laravel domain in production
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialize AI services
enhanced_ai = EnhancedCropIntelligence()
symbiosis_ai = SymbiosisFarmIntelligence()
openfarm_sync = OpenFarmSyncService()
llm_service = LLMService()

# Pydantic models for request/response
class CropRecommendationRequest(BaseModel):
    crop_type: str
    planting_date: date
    farm_latitude: float
    farm_longitude: float
    previous_crops: Optional[List[str]] = []
    include_holistic: bool = True
    include_sacred_geometry: bool = True
    include_biodynamic: bool = True

class SuccessionPlanRequest(BaseModel):
    crop_type: str
    start_date: date
    succession_count: int
    interval_days: int
    farm_latitude: float
    farm_longitude: float
    available_beds: List[str]
    holistic_optimization: bool = True

class ChatMessage(BaseModel):
    role: str
    content: str

class ChatRequest(BaseModel):
    message: str
    conversation_history: Optional[List[ChatMessage]] = []

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
        ]
    }

@app.post("/api/v1/crop-recommendations")
async def get_crop_recommendations(request: CropRecommendationRequest):
    """
    Get comprehensive crop recommendations combining scientific data 
    with holistic agricultural wisdom
    """
    try:
        planting_datetime = datetime.combine(request.planting_date, datetime.min.time())
        farm_location = (request.farm_latitude, request.farm_longitude)
        
        # Get comprehensive analysis
        analysis = enhanced_ai.get_comprehensive_crop_analysis(
            crop_type=request.crop_type,
            planting_date=planting_datetime,
            farm_location=farm_location,
            previous_crops=request.previous_crops
        )
        
        return {
            "success": True,
            "crop": request.crop_type,
            "analysis": analysis,
            "ai_type": "holistic_enhanced",
            "wisdom_level": "sacred_geometry_integrated"
        }
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Analysis failed: {str(e)}")

@app.get("/api/v1/companions/{crop_type}")
async def get_companion_recommendations(crop_type: str, include_energetic: bool = True):
    """Get companion planting recommendations with energetic analysis"""
    
    try:
        # Traditional companions from OpenFarm
        traditional = enhanced_ai.get_traditional_companions(crop_type)
        
        # Energetic and sacred geometry companions
        if include_energetic:
            energetic = enhanced_ai.get_energetic_companions(crop_type)
            mandala_design = enhanced_ai.design_companion_mandala(crop_type)
        else:
            energetic = {}
            mandala_design = {}
        
        return {
            "success": True,
            "crop": crop_type,
            "traditional_companions": traditional,
            "energetic_companions": energetic,
            "sacred_mandala_design": mandala_design,
            "integration_approach": "holistic_scientific_synthesis"
        }
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Companion analysis failed: {str(e)}")

@app.post("/api/v1/succession-planning/holistic")
async def create_holistic_succession_plan(request: SuccessionPlanRequest):
    """
    Create succession plan with holistic optimization including:
    - Sacred geometry spacing
    - Cosmic timing adjustments  
    - Energetic bed rotation
    - Biodynamic calendar alignment
    """
    
    try:
        base_datetime = datetime.combine(request.start_date, datetime.min.time())
        farm_location = (request.farm_latitude, request.farm_longitude)
        
        # Generate holistic succession plan
        succession_plan = generate_holistic_succession_plan(
            crop_type=request.crop_type,
            start_date=base_datetime,
            succession_count=request.succession_count,
            interval_days=request.interval_days,
            farm_location=farm_location,
            available_beds=request.available_beds,
            holistic_optimization=request.holistic_optimization
        )
        
        return {
            "success": True,
            "plan": succession_plan,
            "optimization_type": "sacred_geometry_biodynamic",
            "cosmic_alignment": "optimized",
            "energy_flow": "harmonized"
        }
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Succession planning failed: {str(e)}")

@app.get("/api/v1/cosmic-timing/{crop_type}")
async def get_cosmic_timing(crop_type: str, target_date: date):
    """Get optimal planting timing based on cosmic influences"""
    
    try:
        target_datetime = datetime.combine(target_date, datetime.min.time())
        
        cosmic_timing = enhanced_ai.get_optimal_cosmic_timing(crop_type, target_datetime)
        
        return {
            "success": True,
            "crop": crop_type,
            "target_date": target_date.isoformat(),
            "cosmic_timing": cosmic_timing,
            "wisdom_tradition": "biodynamic_astronomy"
        }
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Cosmic timing analysis failed: {str(e)}")

@app.get("/api/v1/sacred-geometry/{crop_type}")
async def get_sacred_geometry_layout(crop_type: str, garden_size_sq_ft: float = 100):
    """Get sacred geometry-based garden layout"""
    
    try:
        geometry_layout = symbiosis_ai.get_sacred_geometry_spacing(crop_type)
        
        # Calculate layout for given garden size
        optimized_layout = calculate_sacred_garden_layout(
            crop_type, geometry_layout, garden_size_sq_ft
        )
        
        return {
            "success": True,
            "crop": crop_type,
            "sacred_geometry": geometry_layout,
            "optimized_layout": optimized_layout,
            "design_principles": "golden_ratio_fibonacci_hexagonal"
        }
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Sacred geometry calculation failed: {str(e)}")

@app.get("/api/v1/holistic-wisdom/{crop_type}")
async def get_holistic_wisdom(crop_type: str, current_date: date):
    """Get Symbiosis-style holistic wisdom and guidance"""
    
    try:
        current_datetime = datetime.combine(current_date, datetime.min.time())
        
        # Generate holistic wisdom explanation
        holistic_data = symbiosis_ai.get_holistic_planting_recommendations(
            crop_type, current_datetime, (0, 0)  # Generic location
        )
        
        wisdom_explanation = enhanced_ai.generate_wisdom_explanation(
            crop_type, current_datetime, holistic_data
        )
        
        # Additional energetic analysis
        energetic_profile = enhanced_ai.analyze_crop_energetics(crop_type)
        
        return {
            "success": True,
            "crop": crop_type,
            "holistic_wisdom": wisdom_explanation,
            "energetic_profile": energetic_profile,
            "sacred_guidance": holistic_data,
            "consciousness_level": "integrated_earth_cosmic_awareness"
        }
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Wisdom generation failed: {str(e)}")

@app.post("/api/v1/sync-openfarm")
async def sync_openfarm_data():
    """Sync latest data from OpenFarm API"""
    
    try:
        result = await openfarm_sync.sync_crop_data()
        return {
            "success": True,
            "synced_crops": result.get("crops_synced", 0),
            "timestamp": datetime.now().isoformat(),
            "data_source": "openfarm_api_enhanced"
        }
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"OpenFarm sync failed: {str(e)}")

@app.post("/api/v1/ingest/biodynamics")
async def ingest_biodynamics():
    """Ingest biodynamic_principles_core.txt into local vector store for retrieval."""
    try:
        source_path = "/opt/sites/admin.middleworldfarms.org/ai_service/biodynamic_principles_core.txt"
        with open(source_path, 'r') as f:
            text = f.read()
        chunks = LLMService.chunk_text(text, max_chars=1200, overlap=150)
        count = llm_service.ingest_corpus(chunks, source=os.path.basename(source_path))
        return {"success": True, "chunks": count, "source": os.path.basename(source_path)}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Ingestion failed: {str(e)}")

@app.post("/api/v1/ingest/admin-docs")
async def ingest_admin_docs():
    """Ingest all markdown documentation files from docs folder into vector store."""
    try:
        docs_path = "/opt/sites/admin.soilsync.shop/docs"
        md_files = glob.glob(os.path.join(docs_path, "*.md"))
        
        if not md_files:
            return {"success": False, "message": "No markdown files found in docs folder"}
        
        total_chunks = 0
        processed_files = []
        
        for md_file in md_files:
            try:
                with open(md_file, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                # Skip empty files
                if not content.strip():
                    continue
                
                # Chunk the content
                chunks = LLMService.chunk_text(content, max_chars=1200, overlap=150)
                
                # Ingest with filename as source
                filename = os.path.basename(md_file)
                count = llm_service.ingest_corpus(chunks, source=f"admin-docs/{filename}")
                total_chunks += count
                processed_files.append({"file": filename, "chunks": count})
                
            except Exception as e:
                print(f"Error processing {md_file}: {e}")
                continue
        
        return {
            "success": True, 
            "total_chunks": total_chunks, 
            "files_processed": len(processed_files),
            "files": processed_files
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Admin docs ingestion failed: {str(e)}")

@app.post("/api/v1/contextual-help")
async def get_contextual_help(request: dict):
    """Get contextual help based on current page and user query."""
    try:
        page_context = request.get("page_context", "")
        user_question = request.get("question", "")
        current_section = request.get("current_section", "")
        
        # Build context-aware query
        context_parts = []
        if page_context:
            context_parts.append(f"page: {page_context}")
        if current_section:
            context_parts.append(f"section: {current_section}")
        if user_question:
            context_parts.append(f"question: {user_question}")
        
        query = " ".join(context_parts)
        
        # Get RAG-enhanced response
        response = rag_service.get_contextual_help(query, page_context)
        
        return {
            "success": True,
            "help": response,
            "context": {
                "page": page_context,
                "section": current_section,
                "query": query
            }
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Contextual help failed: {str(e)}")

@app.post("/api/v1/chat")
async def chat_with_rag(request: ChatRequest):
    """Advanced chat endpoint with RAG-enhanced responses"""
    try:
        # Convert conversation history to dict format
        conversation_history = []
        for msg in request.conversation_history:
            conversation_history.append({"role": msg.role, "content": msg.content})
        
        # Get RAG-enhanced response
        response = rag_service.get_augmented_response(
            user_message=request.message,
            conversation_history=conversation_history
        )
        
        if response:
            return {
                "success": True,
                "response": response,
                "source": "llm_with_rag",
                "biodynamic_knowledge": rag_service.knowledge_ingested
            }
        else:
            # Fallback to rule-based wisdom
            fallback_response = rag_service.get_fallback_wisdom(request.message)
            return {
                "success": True,
                "response": fallback_response,
                "source": "fallback_wisdom",
                "biodynamic_knowledge": False
            }
            
    except Exception as e:
        # Final fallback
        fallback_response = rag_service.get_fallback_wisdom(request.message)
        return {
            "success": True,
            "response": fallback_response,
            "source": "error_fallback",
            "error": str(e),
            "biodynamic_knowledge": False
        }

@app.post("/ask")
async def ask_compatibility(payload: Dict):
    """Backward-compatible endpoint for existing UI"""
    try:
        question = payload.get("question", "")
        crop = payload.get("crop_type", "")
        season = payload.get("season", "")
        
        # Build enhanced message with context
        context_parts = []
        if crop:
            context_parts.append(f"Crop: {crop}")
        if season:
            context_parts.append(f"Season: {season}")
        
        enhanced_message = question
        if context_parts:
            enhanced_message = f"{' | '.join(context_parts)} | {question}"
        
        # Use RAG service
        response = rag_service.get_augmented_response(enhanced_message)
        
        if response:
            return {
                "success": True,
                "answer": response,
                "source": "llm_rag"
            }
        else:
            fallback = rag_service.get_fallback_wisdom(question)
            return {
                "success": True,
                "answer": fallback,
                "source": "fallback"
            }
            
    except Exception as e:
        fallback = rag_service.get_fallback_wisdom(payload.get("question", ""))
        return {
            "success": True,
            "answer": fallback,
            "source": "error_fallback"
        }

@app.post("/ask-test")
async def ask_test(payload: Dict):
    """Minimal test endpoint that doesn't use LLM"""
    try:
        question = payload.get("question", "Hello")
        crop = payload.get("crop_type", "")
        
        # Simple response without LLM
        if crop:
            response = f"🌱 For {crop}: I recommend considering companion planting with herbs like basil or flowers like marigolds. Plant during favorable moon phases for best results."
        else:
            response = "🌟 Welcome to Symbiosis! I'm your holistic agricultural AI. Ask me about crops, planting timing, or sacred farming wisdom."
        
        return {
            "success": True,
            "answer": response,
            "source": "test_response"
        }
        
    except Exception as e:
        return {
            "success": True,
            "answer": "🌱 The farm wisdom flows eternally. Ask me about your agricultural needs!",
            "source": "fallback"
        }
async def ask_simple(payload: Dict):
    """Simple endpoint that bypasses RAG and goes directly to LLM"""
    try:
        question = payload.get("question", "Hello")
        crop = payload.get("crop_type", "")
        season = payload.get("season", "")
        
        # Create enhanced prompt
        if crop and season:
            enhanced_question = f"You are Symbiosis, a holistic agricultural AI. The user is asking about {crop} in {season} season. Question: {question}. Provide wise, practical farming advice."
        elif crop:
            enhanced_question = f"You are Symbiosis, a holistic agricultural AI. The user is asking about {crop}. Question: {question}. Provide wise, practical farming advice."
        else:
            enhanced_question = f"You are Symbiosis, a holistic agricultural AI. Question: {question}. Provide wise, practical farming advice."
        
        # Use LLM service directly
        messages = [{"role": "user", "content": enhanced_question}]
        response = llm_service.chat(messages)
        
        return {
            "success": True,
            "answer": response,
            "source": "direct_llm"
        }
        
    except Exception as e:
        return {
            "success": True,
            "answer": f"🌱 The farm spirit whispers: '{str(e)}' - but the wisdom continues to flow. Ask again when the cosmic timing aligns.",
            "source": "error_fallback"
        }

# Helper functions

def generate_holistic_succession_plan(crop_type: str, start_date: datetime, 
                                    succession_count: int, interval_days: int,
                                    farm_location: Tuple[float, float], 
                                    available_beds: List[str],
                                    holistic_optimization: bool) -> Dict:
    """Generate succession plan with holistic considerations"""
    
    successions = []
    current_date = start_date
    
    for i in range(succession_count):
        # Get holistic recommendations for this planting
        holistic_rec = symbiosis_ai.get_holistic_planting_recommendations(
            crop_type, current_date, farm_location
        )
        
        # Adjust timing based on cosmic influences
        if holistic_optimization:
            cosmic_timing = enhanced_ai.get_optimal_cosmic_timing(crop_type, current_date)
            # Adjust date if cosmic timing suggests better window
            adjusted_date = adjust_for_cosmic_timing(current_date, cosmic_timing)
        else:
            adjusted_date = current_date
        
        # Select bed based on energetic considerations
        optimal_bed = select_energetically_optimal_bed(
            available_beds, crop_type, i, holistic_rec
        )
        
        succession = {
            "succession_number": i + 1,
            "planned_date": adjusted_date.isoformat(),
            "original_date": current_date.isoformat(),
            "cosmic_adjustment": adjusted_date != current_date,
            "optimal_bed": optimal_bed,
            "moon_phase": symbiosis_ai.calculate_moon_phase(adjusted_date),
            "biodynamic_guidance": holistic_rec.get("biodynamic_calendar", {}),
            "sacred_spacing": holistic_rec.get("spacing", {}),
            "companion_suggestions": holistic_rec.get("companions", {}),
            "energy_flow_direction": holistic_rec.get("bed_orientation", {}),
            "holistic_notes": holistic_rec.get("holistic_reasoning", "")
        }
        
        successions.append(succession)
        
        # Move to next succession date
        current_date += timedelta(days=interval_days)
    
    return {
        "crop_type": crop_type,
        "total_successions": succession_count,
        "start_date": start_date.isoformat(),
        "successions": successions,
        "holistic_optimization": holistic_optimization,
        "overall_energy_pattern": analyze_overall_succession_energy(successions),
        "sacred_geometry_notes": generate_succession_geometry_notes(crop_type, succession_count)
    }

def calculate_sacred_garden_layout(crop_type: str, geometry_data: Dict, 
                                 garden_size: float) -> Dict:
    """Calculate sacred geometry layout for given garden size"""
    
    golden_spacing = float(geometry_data.get("golden_ratio_spacing", "6").split()[0])
    
    # Calculate how many plants fit in sacred pattern
    area_per_plant = golden_spacing ** 2
    max_plants = int(garden_size * 144 / area_per_plant)  # Convert sq ft to sq inches
    
    # Generate golden spiral or fibonacci arrangement
    if max_plants <= 21:  # Use fibonacci spiral
        layout_pattern = "fibonacci_spiral"
        coordinates = symbiosis_ai.generate_fibonacci_arrangement()
    else:  # Use hexagonal close-packing
        layout_pattern = "hexagonal_sacred"
        coordinates = generate_hexagonal_sacred_layout(golden_spacing, max_plants)
    
    return {
        "layout_pattern": layout_pattern,
        "optimal_plant_count": max_plants,
        "spacing_inches": golden_spacing,
        "plant_coordinates": coordinates[:max_plants],
        "sacred_ratios_applied": ["golden_ratio", "fibonacci_sequence"],
        "energy_flow_direction": "clockwise_spiral"
    }

def adjust_for_cosmic_timing(base_date: datetime, cosmic_timing: Dict) -> datetime:
    """Adjust planting date based on cosmic timing recommendations"""
    
    # Simple adjustment based on moon phase
    lunar_guidance = cosmic_timing.get("lunar_guidance", {})
    recommended_adjustment = lunar_guidance.get("recommended_adjustment", 0)
    
    if isinstance(recommended_adjustment, (int, float)):
        return base_date + timedelta(days=recommended_adjustment)
    
    return base_date

def select_energetically_optimal_bed(available_beds: List[str], crop_type: str, 
                                   succession_number: int, holistic_rec: Dict) -> str:
    """Select bed based on energetic and rotational considerations"""
    
    if not available_beds:
        return "bed_1"
    
    # Simple rotation for succession plantings
    bed_index = succession_number % len(available_beds)
    return available_beds[bed_index]

def analyze_overall_succession_energy(successions: List[Dict]) -> Dict:
    """Analyze the overall energetic pattern of the succession plan"""
    
    moon_phases = [s.get("moon_phase", "") for s in successions]
    
    return {
        "moon_phase_diversity": len(set(moon_phases)),
        "energetic_balance": "harmonious" if len(set(moon_phases)) > 2 else "focused",
        "cosmic_rhythm": "aligned_with_lunar_cycles",
        "overall_energy_quality": "high_vitality"
    }

def generate_succession_geometry_notes(crop_type: str, count: int) -> str:
    """Generate notes about sacred geometry in succession planting"""
    
    return f"""
    Sacred succession pattern for {crop_type}:
    - {count} plantings create a temporal fibonacci sequence
    - Each succession adds to the garden's energetic mandala
    - Golden ratio timing enhances natural rhythm alignment
    - Creates abundance spiral in the growing space
    """

def generate_hexagonal_sacred_layout(spacing: float, max_plants: int) -> List[Tuple[float, float]]:
    """Generate hexagonal layout coordinates"""
    
    coordinates = []
    row = 0
    plants_placed = 0
    
    while plants_placed < max_plants:
        # Calculate plants in this row
        plants_in_row = 1 if row == 0 else 6 * row
        
        for i in range(min(plants_in_row, max_plants - plants_placed)):
            if row == 0:
                x, y = 0, 0
            else:
                angle = (360 / plants_in_row) * i
                radius = spacing * row
                x = radius * math.cos(math.radians(angle))
                y = radius * math.sin(math.radians(angle))
            
            coordinates.append((round(x, 1), round(y, 1)))
            plants_placed += 1
            
            if plants_placed >= max_plants:
                break
        
        row += 1
    
    return coordinates

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
