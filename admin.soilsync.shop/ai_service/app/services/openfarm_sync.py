# OpenFarm Data Synchronization Service
# Downloads and processes crop data from OpenFarm API with holistic enhancements

import asyncio
import aiohttp
import json
import logging
from datetime import datetime
from typing import Dict, List, Optional
import os

logger = logging.getLogger(__name__)

class OpenFarmSyncService:
    """
    Service to sync crop data from OpenFarm API and enhance it 
    with holistic agricultural wisdom
    """
    
    def __init__(self):
        self.base_url = "https://openfarm.cc/api/v1"
        self.data_dir = "/app/data"
        self.ensure_data_directory()
    
    def ensure_data_directory(self):
        """Ensure data directory exists"""
        os.makedirs(self.data_dir, exist_ok=True)
    
    async def sync_crop_data(self) -> Dict:
        """Main sync function to download and process OpenFarm data"""
        
        logger.info("Starting OpenFarm data synchronization...")
        
        try:
            # Sync basic crop data
            crops_synced = await self.sync_crops_with_companions()
            
            # Enhance with holistic data
            enhanced_count = await self.enhance_with_holistic_data()
            
            # Generate companion matrix
            matrix_generated = await self.generate_companion_matrix()
            
            # Create sacred geometry templates
            geometry_templates = await self.create_sacred_geometry_templates()
            
            result = {
                "success": True,
                "timestamp": datetime.now().isoformat(),
                "crops_synced": crops_synced,
                "holistic_enhancements": enhanced_count,
                "companion_matrix_generated": matrix_generated,
                "sacred_geometry_templates": len(geometry_templates),
                "data_location": self.data_dir
            }
            
            logger.info(f"Sync completed successfully: {result}")
            return result
            
        except Exception as e:
            logger.error(f"Sync failed: {str(e)}")
            raise
    
    async def sync_crops_with_companions(self) -> int:
        """Download crops data with companion relationships"""
        
        crops_data = {}
        page = 1
        total_synced = 0
        
        async with aiohttp.ClientSession() as session:
            while True:
                url = f"{self.base_url}/crops"
                params = {
                    "page": page,
                    "include": "companions",
                    "per_page": 50
                }
                
                logger.info(f"Fetching crops page {page}...")
                
                async with session.get(url, params=params) as response:
                    if response.status != 200:
                        logger.warning(f"Failed to fetch page {page}: {response.status}")
                        break
                    
                    data = await response.json()
                    
                    if not data.get("data"):
                        break
                    
                    # Process crops from this page
                    for crop in data["data"]:
                        crop_data = self.process_crop_data(crop, data.get("included", []))
                        crop_name = crop_data["name"].lower()
                        crops_data[crop_name] = crop_data
                        total_synced += 1
                    
                    page += 1
                    
                    # Limit for testing - remove in production
                    if page > 10:  # Only sync first 10 pages for testing
                        break
        
        # Save processed crop data
        output_file = os.path.join(self.data_dir, "openfarm_crops.json")
        with open(output_file, 'w') as f:
            json.dump(crops_data, f, indent=2)
        
        logger.info(f"Saved {total_synced} crops to {output_file}")
        return total_synced
    
    def process_crop_data(self, crop: Dict, included: List[Dict]) -> Dict:
        """Process individual crop data and extract companion relationships"""
        
        attributes = crop.get("attributes", {})
        
        # Extract companion relationships
        companions = self.extract_companions(crop, included)
        
        # Create enhanced crop data structure
        enhanced_crop = {
            "openfarm_id": crop.get("id"),
            "name": attributes.get("name", ""),
            "binomial_name": attributes.get("binomial_name", ""),
            "description": attributes.get("description", ""),
            "sun_requirements": attributes.get("sun_requirements", ""),
            "sowing_method": attributes.get("sowing_method", ""),
            "spread": attributes.get("spread"),
            "row_spacing": attributes.get("row_spacing"),
            "height": attributes.get("height"),
            "days_to_maturity": attributes.get("days_to_maturity"),
            "companions": companions,
            "sync_timestamp": datetime.now().isoformat(),
            
            # Prepare for holistic enhancements
            "holistic_profile": {
                "elemental_classification": self.classify_elemental_type(attributes),
                "growth_energy": self.assess_growth_energy(attributes),
                "sacred_associations": self.get_sacred_associations(attributes.get("name", "")),
                "biodynamic_category": self.get_biodynamic_category(attributes)
            }
        }
        
        return enhanced_crop
    
    def extract_companions(self, crop: Dict, included: List[Dict]) -> Dict:
        """Extract companion plant relationships"""
        
        companions = {
            "beneficial": [],
            "antagonistic": [],
            "neutral": []
        }
        
        # Look for companion relationships in included data
        companion_ids = []
        relationships = crop.get("relationships", {})
        
        if "companions" in relationships:
            companion_data = relationships["companions"].get("data", [])
            companion_ids = [comp["id"] for comp in companion_data]
        
        # Find companion details in included array
        for item in included:
            if item.get("type") == "crops" and item.get("id") in companion_ids:
                companion_name = item.get("attributes", {}).get("name", "")
                if companion_name:
                    # For now, classify all as beneficial (OpenFarm doesn't specify type)
                    companions["beneficial"].append({
                        "name": companion_name.lower(),
                        "display_name": companion_name,
                        "openfarm_id": item.get("id"),
                        "relationship_type": "beneficial"  # Default from OpenFarm
                    })
        
        return companions
    
    def classify_elemental_type(self, attributes: Dict) -> str:
        """Classify plant into elemental categories (Earth, Water, Fire, Air)"""
        
        name = attributes.get("name", "").lower()
        description = attributes.get("description", "").lower()
        
        # Root vegetables = Earth
        if any(word in name for word in ["carrot", "beet", "radish", "turnip", "potato", "root"]):
            return "earth"
        
        # Leafy greens = Air  
        if any(word in name for word in ["lettuce", "spinach", "kale", "chard", "leaf"]):
            return "air"
        
        # Fruits and heat-loving plants = Fire
        if any(word in name for word in ["tomato", "pepper", "chili", "fruit", "hot"]):
            return "fire"
        
        # Water-loving plants = Water
        if any(word in name for word in ["cucumber", "celery", "watercress", "water"]):
            return "water"
        
        # Default classification
        return "earth"  # Most stable default
    
    def assess_growth_energy(self, attributes: Dict) -> str:
        """Assess the growth energy pattern of the plant"""
        
        days_to_maturity = attributes.get("days_to_maturity")
        
        if days_to_maturity:
            if days_to_maturity < 30:
                return "fast_vital"
            elif days_to_maturity < 60:
                return "moderate_steady"
            elif days_to_maturity < 120:
                return "slow_deep"
            else:
                return "long_perennial"
        
        return "moderate_steady"
    
    def get_sacred_associations(self, plant_name: str) -> Dict:
        """Get traditional sacred/medicinal associations"""
        
        sacred_plants = {
            "sage": {"tradition": "purification", "element": "air", "chakra": "throat"},
            "lavender": {"tradition": "peace", "element": "air", "chakra": "crown"},
            "rosemary": {"tradition": "remembrance", "element": "fire", "chakra": "third_eye"},
            "basil": {"tradition": "abundance", "element": "fire", "chakra": "solar_plexus"},
            "mint": {"tradition": "prosperity", "element": "water", "chakra": "heart"},
            "thyme": {"tradition": "courage", "element": "air", "chakra": "throat"}
        }
        
        name_lower = plant_name.lower()
        
        for sacred_plant, associations in sacred_plants.items():
            if sacred_plant in name_lower:
                return associations
        
        # Default associations based on plant type
        return {
            "tradition": "nourishment",
            "element": "earth", 
            "chakra": "root"
        }
    
    def get_biodynamic_category(self, attributes: Dict) -> str:
        """Classify plant for biodynamic calendar"""
        
        name = attributes.get("name", "").lower()
        
        # Root day plants
        if any(word in name for word in ["carrot", "beet", "radish", "turnip", "potato", "onion"]):
            return "root_day"
        
        # Leaf day plants  
        if any(word in name for word in ["lettuce", "spinach", "kale", "chard", "cabbage", "herb"]):
            return "leaf_day"
        
        # Flower day plants
        if any(word in name for word in ["broccoli", "cauliflower", "flower", "artichoke"]):
            return "flower_day"
        
        # Fruit day plants
        if any(word in name for word in ["tomato", "pepper", "cucumber", "squash", "bean", "pea"]):
            return "fruit_day"
        
        return "leaf_day"  # Default
    
    async def enhance_with_holistic_data(self) -> int:
        """Enhance OpenFarm data with additional holistic information"""
        
        # Load existing crop data
        crops_file = os.path.join(self.data_dir, "openfarm_crops.json")
        
        if not os.path.exists(crops_file):
            logger.warning("No crops data found to enhance")
            return 0
        
        with open(crops_file, 'r') as f:
            crops_data = json.load(f)
        
        enhanced_count = 0
        
        for crop_name, crop_data in crops_data.items():
            # Add additional holistic data
            crop_data["holistic_profile"].update({
                "moon_phase_preference": self.get_moon_phase_preference(crop_data),
                "planetary_association": self.get_planetary_association(crop_data),
                "sacred_geometry_pattern": self.get_sacred_geometry_pattern(crop_data),
                "companion_mandala_design": self.design_companion_mandala(crop_data)
            })
            
            enhanced_count += 1
        
        # Save enhanced data
        with open(crops_file, 'w') as f:
            json.dump(crops_data, f, indent=2)
        
        logger.info(f"Enhanced {enhanced_count} crops with holistic data")
        return enhanced_count
    
    async def generate_companion_matrix(self) -> bool:
        """Generate companion planting matrix for quick lookups"""
        
        crops_file = os.path.join(self.data_dir, "openfarm_crops.json")
        
        if not os.path.exists(crops_file):
            return False
        
        with open(crops_file, 'r') as f:
            crops_data = json.load(f)
        
        # Create companion matrix
        companion_matrix = {}
        
        for crop_name, crop_data in crops_data.items():
            companions = crop_data.get("companions", {})
            beneficial = [comp["name"] for comp in companions.get("beneficial", [])]
            
            companion_matrix[crop_name] = {
                "beneficial": beneficial,
                "antagonistic": companions.get("antagonistic", []),
                "elemental_type": crop_data.get("holistic_profile", {}).get("elemental_classification", "earth")
            }
        
        # Save companion matrix
        matrix_file = os.path.join(self.data_dir, "companion_matrix.json")
        with open(matrix_file, 'w') as f:
            json.dump(companion_matrix, f, indent=2)
        
        logger.info(f"Generated companion matrix with {len(companion_matrix)} crops")
        return True
    
    async def create_sacred_geometry_templates(self) -> Dict:
        """Create sacred geometry layout templates"""
        
        templates = {
            "golden_spiral": {
                "description": "Golden ratio spiral layout",
                "pattern": "fibonacci_spiral",
                "spacing_multiplier": 1.618,
                "use_cases": ["herb_gardens", "small_plots", "mandala_gardens"]
            },
            "hexagonal_sacred": {
                "description": "Hexagonal close-packing with sacred proportions",
                "pattern": "hexagonal_grid",
                "spacing_multiplier": 1.732,  # sqrt(3)
                "use_cases": ["row_crops", "efficient_spacing", "large_plots"]
            },
            "mandala_circle": {
                "description": "Circular mandala with center focus",
                "pattern": "concentric_circles",
                "spacing_multiplier": 1.414,  # sqrt(2)
                "use_cases": ["companion_planting", "sacred_gardens", "permaculture"]
            }
        }
        
        # Save templates
        templates_file = os.path.join(self.data_dir, "sacred_geometry_templates.json")
        with open(templates_file, 'w') as f:
            json.dump(templates, f, indent=2)
        
        logger.info(f"Created {len(templates)} sacred geometry templates")
        return templates
    
    # Helper methods for holistic enhancements
    
    def get_moon_phase_preference(self, crop_data: Dict) -> str:
        """Determine preferred moon phase for planting"""
        
        biodynamic_cat = crop_data.get("holistic_profile", {}).get("biodynamic_category", "leaf_day")
        
        preferences = {
            "root_day": "waning_moon",    # Descending energy for roots
            "leaf_day": "waxing_moon",    # Ascending energy for leaves
            "flower_day": "waxing_moon",  # Ascending energy for flowers
            "fruit_day": "full_moon"      # Peak energy for fruiting
        }
        
        return preferences.get(biodynamic_cat, "waxing_moon")
    
    def get_planetary_association(self, crop_data: Dict) -> str:
        """Get traditional planetary association"""
        
        elemental_type = crop_data.get("holistic_profile", {}).get("elemental_classification", "earth")
        
        planetary_associations = {
            "earth": "Saturn",   # Grounding, structure
            "water": "Moon",     # Cycles, emotions, growth
            "fire": "Mars",      # Energy, action, heat
            "air": "Mercury"     # Communication, quick growth
        }
        
        return planetary_associations.get(elemental_type, "Earth")
    
    def get_sacred_geometry_pattern(self, crop_data: Dict) -> str:
        """Recommend sacred geometry pattern for crop"""
        
        growth_energy = crop_data.get("holistic_profile", {}).get("growth_energy", "moderate_steady")
        
        pattern_recommendations = {
            "fast_vital": "fibonacci_spiral",     # Dynamic growth
            "moderate_steady": "hexagonal_grid",  # Stable, efficient
            "slow_deep": "mandala_circle",        # Deep, centered growth
            "long_perennial": "golden_spiral"     # Long-term sacred pattern
        }
        
        return pattern_recommendations.get(growth_energy, "hexagonal_grid")
    
    def design_companion_mandala(self, crop_data: Dict) -> Dict:
        """Design basic companion mandala structure"""
        
        companions = crop_data.get("companions", {}).get("beneficial", [])
        
        if len(companions) >= 6:
            return {
                "center": crop_data["name"],
                "inner_circle": companions[:3],
                "outer_circle": companions[3:6],
                "pattern": "sacred_hexagon"
            }
        elif len(companions) >= 3:
            return {
                "center": crop_data["name"], 
                "inner_circle": companions[:3],
                "pattern": "triangle_sacred"
            }
        else:
            return {
                "center": crop_data["name"],
                "companions": companions,
                "pattern": "simple_circle"
            }
