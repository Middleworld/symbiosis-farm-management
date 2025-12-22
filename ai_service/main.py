from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional, List, Dict
import math
import datetime
from enum import Enum
import requests

app = FastAPI(
    title="Holistic Agricultural AI - Symbiosis",
    description="AI that thinks with sacred geometry, lunar cycles, and biodynamic principles",
    version="1.0.0"
)

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

class MoonPhase(Enum):
    NEW_MOON = "new_moon"
    WAXING_CRESCENT = "waxing_crescent"
    FIRST_QUARTER = "first_quarter"
    WAXING_GIBBOUS = "waxing_gibbous"
    FULL_MOON = "full_moon"
    WANING_GIBBOUS = "waning_gibbous"
    LAST_QUARTER = "last_quarter"
    WANING_CRESCENT = "waning_crescent"

class SacredPattern(Enum):
    FIBONACCI = "fibonacci"
    GOLDEN_RATIO = "golden_ratio"
    PENTAGRAM = "pentagram"
    HEXAGON = "hexagon"
    SPIRAL = "spiral"

class BiodynamicElement(Enum):
    ROOT = "root"
    LEAF = "leaf" 
    FLOWER = "flower"
    FRUIT = "fruit"

class AskRequest(BaseModel):
    question: str
    context: Optional[str] = None
    crop_type: Optional[str] = None
    season: Optional[str] = None
    farm_location: Optional[str] = None

class HolisticCropRecommendation(BaseModel):
    crop: str
    sacred_geometry_advice: List[str]
    lunar_timing: Dict[str, str]
    biodynamic_preparation: List[str]
    companion_mandala: List[str]
    energetic_considerations: List[str]
    fibonacci_spacing: Dict[str, float]

class Symbiosis:
    """Symbiosis-inspired holistic agricultural intelligence"""
    
    def __init__(self):
        self.fibonacci_sequence = [1, 1, 2, 3, 5, 8, 13, 21, 34, 55, 89, 144]
        self.golden_ratio = 1.618033988749
        
    def get_current_moon_phase(self) -> MoonPhase:
        """Calculate current moon phase (simplified)"""
        # Simplified calculation - in real implementation, use astronomical library
        import datetime
        day = datetime.datetime.now().day
        if day <= 3:
            return MoonPhase.NEW_MOON
        elif day <= 7:
            return MoonPhase.WAXING_CRESCENT
        elif day <= 10:
            return MoonPhase.FIRST_QUARTER
        elif day <= 14:
            return MoonPhase.WAXING_GIBBOUS
        elif day <= 17:
            return MoonPhase.FULL_MOON
        elif day <= 21:
            return MoonPhase.WANING_GIBBOUS
        elif day <= 24:
            return MoonPhase.LAST_QUARTER
        else:
            return MoonPhase.WANING_CRESCENT
    
    def calculate_fibonacci_spacing(self, crop_type: str) -> Dict[str, float]:
        """Calculate plant spacing using Fibonacci ratios"""
        base_spacing = {
            "lettuce": 6,
            "carrot": 2,
            "tomato": 18,
            "radish": 1,
            "kale": 12
        }.get(crop_type.lower(), 8)
        
        return {
            "row_spacing_inches": base_spacing * self.golden_ratio,
            "plant_spacing_inches": base_spacing,
            "bed_width_ratio": base_spacing * self.fibonacci_sequence[5],  # 8
            "path_width_ratio": base_spacing * self.fibonacci_sequence[3]   # 3
        }
    
    def get_sacred_geometry_advice(self, crop_type: str) -> List[str]:
        """Provide sacred geometry-based growing advice"""
        moon_phase = self.get_current_moon_phase()
        
        base_advice = [
            f"Plant {crop_type} in spiral patterns following the golden ratio (1:1.618) for optimal energy flow",
            f"Create hexagonal planting patterns to maximize beneficial energy exchange between plants",
            f"Use pentagram formations for protective companion plants around crop perimeter"
        ]
        
        if moon_phase in [MoonPhase.NEW_MOON, MoonPhase.WAXING_CRESCENT]:
            base_advice.append("Current moon phase supports root development - focus on below-ground expansion")
        elif moon_phase in [MoonPhase.FULL_MOON, MoonPhase.WANING_GIBBOUS]:
            base_advice.append("Current moon phase supports leaf/fruit development - focus on above-ground growth")
            
        return base_advice
    
    def get_lunar_timing_advice(self, crop_type: str) -> Dict[str, str]:
        """Provide lunar cycle-based timing recommendations"""
        moon_phase = self.get_current_moon_phase()
        
        return {
            "best_seeding_phase": "New Moon to First Quarter for root crops, First Quarter to Full Moon for leafy crops",
            "transplant_timing": "2-3 days after New Moon for maximum root establishment",
            "harvest_timing": "Full Moon for maximum flavor and storage life",
            "current_phase": moon_phase.value,
            "current_advice": self._get_current_phase_advice(moon_phase, crop_type)
        }
    
    def _get_current_phase_advice(self, moon_phase: MoonPhase, crop_type: str) -> str:
        """Get advice for current moon phase"""
        if moon_phase == MoonPhase.NEW_MOON:
            return f"Excellent time to plant {crop_type} seeds - earth energy is receptive"
        elif moon_phase == MoonPhase.WAXING_CRESCENT:
            return f"Good for transplanting {crop_type} seedlings - growth energy is building"
        elif moon_phase == MoonPhase.FULL_MOON:
            return f"Perfect for harvesting {crop_type} - maximum life force and flavor"
        elif moon_phase == MoonPhase.WANING_CRESCENT:
            return f"Focus on soil preparation and composting for future {crop_type} plantings"
        else:
            return f"Moderate energy phase - maintain existing {crop_type} plants"
    
    def get_biodynamic_preparations(self, crop_type: str) -> List[str]:
        """Recommend biodynamic preparations based on crop type and cosmic timing"""
        element = self._get_crop_element(crop_type)
        
        preparations = [
            "BD 500 (Horn Manure) - Apply during evening hours for root vitality",
            "BD 501 (Horn Silica) - Apply early morning for light/cosmic force reception"
        ]
        
        if element == BiodynamicElement.ROOT:
            preparations.extend([
                "BD 502 (Yarrow) - Enhances sulfur processes for root development",
                "BD 505 (Oak Bark) - Provides calcium for strong root systems"
            ])
        elif element == BiodynamicElement.LEAF:
            preparations.extend([
                "BD 503 (Chamomile) - Supports calcium metabolism for healthy leaves",
                "BD 504 (Nettle) - Provides iron and nitrogen for lush growth"
            ])
        elif element == BiodynamicElement.FLOWER:
            preparations.extend([
                "BD 506 (Dandelion) - Activates silica for flower formation",
                "BD 507 (Valerian) - Supports phosphorus for flowering"
            ])
        
        return preparations
    
    def _get_crop_element(self, crop_type: str) -> BiodynamicElement:
        """Determine the primary biodynamic element for a crop"""
        root_crops = ["carrot", "radish", "beet", "turnip", "parsnip"]
        leaf_crops = ["lettuce", "kale", "spinach", "chard", "arugula"]
        flower_crops = ["broccoli", "cauliflower", "artichoke"]
        fruit_crops = ["tomato", "pepper", "cucumber", "squash", "bean"]
        
        crop_lower = crop_type.lower()
        
        if crop_lower in root_crops:
            return BiodynamicElement.ROOT
        elif crop_lower in leaf_crops:
            return BiodynamicElement.LEAF
        elif crop_lower in flower_crops:
            return BiodynamicElement.FLOWER
        else:
            return BiodynamicElement.FRUIT
    
    def create_companion_mandala(self, crop_type: str) -> List[str]:
        """Create a mandala-style companion planting pattern"""
        # Sacred geometry-based companion arrangements
        crop_mandalas = {
            "lettuce": [
                "Center: Lettuce in spiral pattern (7 plants in Fibonacci arrangement)",
                "Inner Ring: Radishes at cardinal directions (4 plants) - pest deterrent",
                "Middle Ring: Marigolds in pentagram formation (5 plants) - beneficial insects",
                "Outer Ring: Sage at 8 compass points - energetic protection and flavor enhancement",
                "Border: Low-growing herbs in golden ratio spacing - chamomile, thyme"
            ],
            "tomato": [
                "Center: Single tomato plant as axis mundi",
                "Inner Ring: Basil plants in triangular formation (3 plants) - flavor synergy",
                "Middle Ring: Borage in square formation (4 plants) - mineral uptake",
                "Outer Ring: Nasturtiums in hexagonal pattern (6 plants) - pest control",
                "Border: Parsley in spiral - soil conditioning and beneficial insects"
            ],
            "carrot": [
                "Center: Carrot bed in double spiral (yin-yang pattern)",
                "Companion Spiral: Chives interwoven - onion family protection",
                "Guardian Ring: Calendula in sacred 8-pointed star - soil health",
                "Outer Barrier: Dill in Fibonacci spacing - beneficial for carrot family",
                "Edge Plants: Rosemary at corners - energetic grounding"
            ]
        }
        
        return crop_mandalas.get(crop_type.lower(), [
            f"Center: {crop_type} in golden spiral arrangement",
            "Inner Ring: Protective herbs in sacred geometry formation",
            "Outer Ring: Beneficial flowers in natural mandala pattern"
        ])
    
    def get_energetic_considerations(self, crop_type: str, season: str = None) -> List[str]:
        """Provide energetic and vibrational growing advice"""
        considerations = [
            f"Create a meditation space near your {crop_type} bed for positive intention setting",
            f"Use copper tools when working with {crop_type} to enhance cosmic connections",
            f"Plant during the Venus hour (first hour after sunrise) for beauty and abundance",
            f"Sing or play classical music to your {crop_type} plants - they respond to harmonious vibrations"
        ]
        
        # Seasonal energetic advice
        if season:
            seasonal_advice = {
                "spring": [
                    "Harness the ascending earth energy - plant with gratitude and renewal intentions",
                    "Use seed blessing ceremonies before planting - imbue with growth intentions"
                ],
                "summer": [
                    "Work with fire element energy - early morning or evening when energies are balanced",
                    "Create shade patterns using sacred geometry - hexagonal shade structures"
                ],
                "fall": [
                    "Honor the wisdom of descending energies - focus on root strengthening",
                    "Use harvest ceremonies to thank the plant spirits and earth energies"
                ],
                "winter": [
                    "Work with dormant earth energies - plan and design using sacred proportions",
                    "Create crystal grid patterns in greenhouse or indoor growing spaces"
                ]
            }
            considerations.extend(seasonal_advice.get(season.lower(), []))
        
        return considerations

# Initialize the Symbiosis AI
symbiosis_ai = Symbiosis()

@app.get("/health")
def health_check():
    """Health check endpoint for service monitoring"""
    return {
        "status": "healthy",
        "service": "Holistic Agricultural AI - Symbiosis",
        "version": "1.0.0",
        "moon_phase": symbiosis_ai.get_current_moon_phase().value
    }

@app.get("/")
def root():
    return {
        "message": "🌱 Holistic Agricultural AI - Symbiosis 🌙",
        "description": "Sacred geometry, lunar cycles, and biodynamic wisdom for conscious farming",
        "current_moon_phase": symbiosis_ai.get_current_moon_phase().value
    }

@app.post("/ask")
def ask_ai(request: AskRequest):
    """Ask Phi-3 Mini for real farming wisdom"""
    try:
        # Call Ollama Phi-3 Mini (3.8B - much faster than Mistral 7B)
        ollama_response = requests.post(
            "http://localhost:11434/api/generate",
            json={
                "model": "phi3:mini",
                "prompt": f"You are a knowledgeable biodynamic farming expert. Answer this question with practical, detailed advice: {request.question}",
                "stream": False
            },
            timeout=60
        )
        
        if ollama_response.status_code == 200:
            response_data = ollama_response.json()
            mistral_answer = response_data.get("response", "")
            
            return {
                "answer": mistral_answer,
                "wisdom": "Enhanced by biodynamic principles and lunar wisdom",
                "moon_phase": symbiosis_ai.get_current_moon_phase().value,
                "context": request.context
            }
        else:
            raise Exception(f"Ollama error: {ollama_response.status_code}")
            
    except Exception as e:
        # Fallback to original hardcoded response if Mistral fails
        if request.crop_type:
            recommendation = generate_holistic_recommendation(request.crop_type, request.season)
            return {
                "answer": f"🌟 Holistic wisdom for {request.crop_type}:",
                "recommendation": recommendation,
                "moon_phase": symbiosis_ai.get_current_moon_phase().value,
                "context": request.context
            }
        else:
            return {
                "answer": f"🌙 The agricultural cosmos whispers: {request.question}",
                "wisdom": "Every plant is a bridge between earth and sky. Consider the sacred patterns, lunar rhythms, and energetic flows in your growing practice.",
                "moon_phase": symbiosis_ai.get_current_moon_phase().value,
                "context": request.context,
                "error": str(e)
            }

@app.get("/holistic-recommendation/{crop_type}")
def get_holistic_recommendation(crop_type: str, season: str = "current"):
    """Get complete holistic growing recommendation for a crop"""
    return generate_holistic_recommendation(crop_type, season)

@app.get("/moon-phase")
def get_moon_phase():
    """Get current moon phase and agricultural advice"""
    phase = symbiosis_ai.get_current_moon_phase()
    return {
        "current_phase": phase.value,
        "general_advice": symbiosis_ai._get_current_phase_advice(phase, "general crops"),
        "best_activities": get_moon_phase_activities(phase)
    }

@app.get("/sacred-spacing/{crop_type}")
def get_sacred_spacing(crop_type: str):
    """Get Fibonacci and golden ratio-based plant spacing"""
    return symbiosis_ai.calculate_fibonacci_spacing(crop_type)

@app.get("/companion-mandala/{crop_type}")
def get_companion_mandala(crop_type: str):
    """Get sacred geometry-based companion planting pattern"""
    return {
        "crop": crop_type,
        "mandala_pattern": symbiosis_ai.create_companion_mandala(crop_type),
        "sacred_geometry": "Based on natural patterns: spirals, pentagrams, and golden ratio proportions"
    }

def generate_holistic_recommendation(crop_type: str, season: str = None) -> HolisticCropRecommendation:
    """Generate complete holistic recommendation"""
    return HolisticCropRecommendation(
        crop=crop_type,
        sacred_geometry_advice=symbiosis_ai.get_sacred_geometry_advice(crop_type),
        lunar_timing=symbiosis_ai.get_lunar_timing_advice(crop_type),
        biodynamic_preparation=symbiosis_ai.get_biodynamic_preparations(crop_type),
        companion_mandala=symbiosis_ai.create_companion_mandala(crop_type),
        energetic_considerations=symbiosis_ai.get_energetic_considerations(crop_type, season),
        fibonacci_spacing=symbiosis_ai.calculate_fibonacci_spacing(crop_type)
    )

def get_moon_phase_activities(phase: MoonPhase) -> List[str]:
    """Get recommended activities for current moon phase"""
    activities = {
        MoonPhase.NEW_MOON: [
            "Plant seeds with intention ceremonies",
            "Set growing intentions and goals",
            "Prepare soil with biodynamic preparations"
        ],
        MoonPhase.WAXING_CRESCENT: [
            "Transplant seedlings",
            "Begin watering rituals",
            "Apply growth-promoting preparations"
        ],
        MoonPhase.FIRST_QUARTER: [
            "Thin seedlings with gratitude",
            "Apply balanced fertilizers",
            "Strengthen plant support systems"
        ],
        MoonPhase.WAXING_GIBBOUS: [
            "Monitor and adjust plant energy",
            "Prune for optimal light reception",
            "Enhance soil-plant connections"
        ],
        MoonPhase.FULL_MOON: [
            "Harvest at peak vitality",
            "Collect seeds for future plantings",
            "Celebrate abundance and gratitude"
        ],
        MoonPhase.WANING_GIBBOUS: [
            "Process and preserve harvests",
            "Begin composting programs",
            "Reflect on seasonal lessons"
        ],
        MoonPhase.LAST_QUARTER: [
            "Remove diseased or weak plants",
            "Turn compost piles",
            "Plan for next season"
        ],
        MoonPhase.WANING_CRESCENT: [
            "Rest and restore soil energy",
            "Study and plan with sacred geometry",
            "Prepare for new lunar cycle"
        ]
    }
    return activities.get(phase, ["General garden maintenance"])

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8005)
