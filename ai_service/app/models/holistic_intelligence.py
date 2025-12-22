# Holistic Agricultural Intelligence - Sacred Geometry & Natural Patterns
# Inspired by biodynamic farming, permaculture, and natural cycles

import math
import random
from datetime import datetime, timedelta
from typing import Dict, List, Optional, Tuple, Union
from dataclasses import dataclass
from enum import Enum

class SymbiosisFarmIntelligence:
    """
    Agricultural AI that thinks in natural patterns, sacred geometry, 
    and symbiotic relationships between plants, cosmos, and earth energies
    """
    
    def __init__(self):
        self.golden_ratio = (1 + math.sqrt(5)) / 2  # φ = 1.618...
        self.lunar_cycle_days = 29.53
        self.seasonal_angles = {
            'spring_equinox': 0,
            'summer_solstice': 90, 
            'autumn_equinox': 180,
            'winter_solstice': 270
        }
    
    def get_holistic_planting_recommendations(self, crop_type: str, date: datetime, 
                                           farm_location: Tuple[float, float]) -> Dict:
        """
        Generate planting recommendations based on:
        - Lunar cycles and moon phases
        - Sacred geometry spacing
        - Planetary influences 
        - Natural energy flows
        - Companion plant relationships
        """
        
        recommendations = {
            'timing': self.get_cosmic_timing(crop_type, date),
            'spacing': self.get_sacred_geometry_spacing(crop_type),
            'companions': self.get_energetic_companions(crop_type),
            'bed_orientation': self.get_optimal_orientation(farm_location, date),
            'energy_flow': self.analyze_farm_energy_patterns(crop_type),
            'biodynamic_calendar': self.get_biodynamic_guidance(date),
            'holistic_reasoning': self.generate_holistic_explanation(crop_type, date)
        }
        
        return recommendations
    
    def get_cosmic_timing(self, crop_type: str, target_date: datetime) -> Dict:
        """Align planting with lunar cycles and planetary influences"""
        
        # Calculate moon phase
        moon_phase = self.calculate_moon_phase(target_date)
        
        # Biodynamic calendar considerations
        plant_part = self.get_dominant_plant_part(crop_type)
        optimal_days = self.get_biodynamic_days(target_date, plant_part)
        
        return {
            'moon_phase': moon_phase,
            'optimal_for': plant_part,
            'biodynamic_window': optimal_days,
            'energy_quality': self.assess_cosmic_energy(target_date),
            'recommended_adjustment': self.suggest_timing_adjustment(moon_phase, plant_part)
        }
    
    def get_sacred_geometry_spacing(self, crop_type: str) -> Dict:
        """Calculate plant spacing using sacred geometry principles"""
        
        base_spacing = self.get_base_spacing(crop_type)
        
        # Apply golden ratio for optimal energy flow
        golden_spacing = base_spacing * self.golden_ratio
        
        # Fibonacci spiral arrangement for companion plants
        fibonacci_pattern = self.generate_fibonacci_arrangement()
        
        # Hexagonal (sacred) patterns for maximum efficiency
        hexagonal_spacing = self.calculate_hexagonal_pattern(base_spacing)
        
        return {
            'traditional_spacing': f"{base_spacing} inches",
            'golden_ratio_spacing': f"{golden_spacing:.1f} inches",
            'fibonacci_arrangement': fibonacci_pattern,
            'hexagonal_pattern': hexagonal_spacing,
            'sacred_geometry_reasoning': self.explain_geometry_benefits(crop_type),
            'recommended': 'golden_ratio_spacing'  # Usually optimal
        }
    
    def get_energetic_companions(self, crop_type: str) -> Dict:
        """Find companion plants based on energetic and vibrational harmony"""
        
        # Traditional companions from OpenFarm data
        traditional = self.get_traditional_companions(crop_type)
        
        # Add energetic/vibrational analysis
        energetic_matches = self.analyze_plant_energetics(crop_type)
        
        # Sacred geometry arrangements
        geometric_companions = self.get_geometric_arrangements(crop_type)
        
        return {
            'traditional_companions': traditional,
            'energetic_matches': energetic_matches,
            'vibrational_harmony': self.assess_vibrational_compatibility(crop_type),
            'geometric_arrangements': geometric_companions,
            'holistic_reasoning': self.explain_companion_synergy(crop_type),
            'four_elements_balance': self.analyze_elemental_balance(crop_type)
        }
    
    def get_optimal_orientation(self, location: Tuple[float, float], date: datetime) -> Dict:
        """Calculate optimal bed orientation using sacred directions and energy flows"""
        
        lat, lon = location
        
        # Solar path calculations
        solar_angle = self.calculate_solar_path(lat, date)
        
        # Sacred directions (cardinal + intercardinal)
        sacred_directions = {
            'north': 0, 'northeast': 45, 'east': 90, 'southeast': 135,
            'south': 180, 'southwest': 225, 'west': 270, 'northwest': 315
        }
        
        # Magnetic field alignment
        magnetic_alignment = self.calculate_magnetic_field_alignment(lat, lon)
        
        return {
            'optimal_solar_orientation': f"{solar_angle:.1f}°",
            'sacred_direction_alignment': self.find_best_sacred_direction(solar_angle),
            'magnetic_field_consideration': magnetic_alignment,
            'seasonal_adjustment': self.get_seasonal_orientation_shift(date),
            'energy_flow_direction': self.calculate_natural_energy_flow(lat, lon),
            'holistic_reasoning': "Aligning with natural energy patterns enhances plant vitality"
        }
    
    def analyze_farm_energy_patterns(self, crop_type: str) -> Dict:
        """Analyze subtle energy flows and patterns across the farm"""
        
        return {
            'energy_centers': self.identify_farm_energy_centers(),
            'flow_patterns': self.map_energy_flow_patterns(),
            'optimal_zones': self.find_optimal_planting_zones(crop_type),
            'energy_enhancement': self.suggest_energy_enhancements(),
            'elemental_balance': self.assess_farm_elemental_balance(),
            'sacred_spaces': self.identify_sacred_growing_spaces()
        }
    
    def get_biodynamic_guidance(self, date: datetime) -> Dict:
        """Provide biodynamic calendar guidance"""
        
        constellation = self.get_moon_constellation(date)
        element = self.get_elemental_influence(constellation)
        
        return {
            'current_constellation': constellation,
            'elemental_influence': element,
            'plant_part_focus': self.get_plant_part_from_element(element),
            'biodynamic_preparations': self.suggest_biodynamic_preparations(element),
            'cosmic_quality': self.assess_cosmic_quality(date),
            'recommended_activities': self.get_biodynamic_activities(element)
        }
    
    def generate_holistic_explanation(self, crop_type: str, date: datetime) -> str:
        """Generate a holistic explanation of recommendations"""
        
        moon_phase = self.calculate_moon_phase(date)
        season = self.get_season(date)
        element = self.get_current_elemental_influence(date)
        
        explanation = f"""
        🌙 Holistic Planting Wisdom for {crop_type.title()}:
        
        The cosmos aligns favorably with {moon_phase} moon energy, creating optimal conditions 
        for {crop_type} cultivation. During this {season} period, the {element} elemental 
        influence supports {self.get_element_benefits(element, crop_type)}.
        
        Sacred geometry suggests golden ratio spacing ({self.get_base_spacing(crop_type) * self.golden_ratio:.1f} inches) 
        to create harmonious energy flow between plants. This natural pattern mirrors 
        the spiral formations found throughout nature - from nautilus shells to galaxy formations.
        
        The earth's energy patterns favor planting in {self.get_optimal_direction(date)} orientation, 
        aligning with natural magnetic fields and seasonal sun paths. This creates a 
        bioharmonious environment where plants can thrive in resonance with natural forces.
        
        Consider companion plantings that create elemental balance and energetic support,
        forming a living mandala in your growing space. Each plant becomes part of a 
        greater whole, contributing to the farm's overall vitality and abundance.
        """
        
        return explanation.strip()
    
    # Helper methods for calculations
    
    def calculate_moon_phase(self, date: datetime) -> str:
        """Calculate current moon phase"""
        # Simplified moon phase calculation
        days_since_new_moon = (date - datetime(2000, 1, 6)).days % self.lunar_cycle_days
        
        if days_since_new_moon < 2:
            return "New Moon"
        elif days_since_new_moon < 7:
            return "Waxing Crescent"
        elif days_since_new_moon < 10:
            return "First Quarter"
        elif days_since_new_moon < 15:
            return "Waxing Gibbous"
        elif days_since_new_moon < 17:
            return "Full Moon"
        elif days_since_new_moon < 22:
            return "Waning Gibbous"
        elif days_since_new_moon < 25:
            return "Last Quarter"
        else:
            return "Waning Crescent"
    
    def get_dominant_plant_part(self, crop_type: str) -> str:
        """Determine if crop is root, leaf, flower, or fruit dominant"""
        root_crops = ['carrot', 'radish', 'beet', 'turnip', 'potato']
        leaf_crops = ['lettuce', 'spinach', 'kale', 'chard', 'cabbage']
        flower_crops = ['broccoli', 'cauliflower', 'artichoke']
        fruit_crops = ['tomato', 'pepper', 'cucumber', 'squash', 'bean']
        
        crop_lower = crop_type.lower()
        
        if any(root in crop_lower for root in root_crops):
            return "root"
        elif any(leaf in crop_lower for leaf in leaf_crops):
            return "leaf"
        elif any(flower in crop_lower for flower in flower_crops):
            return "flower"
        elif any(fruit in crop_lower for fruit in fruit_crops):
            return "fruit"
        else:
            return "leaf"  # Default
    
    def get_base_spacing(self, crop_type: str) -> float:
        """Get traditional spacing for crop type"""
        spacing_guide = {
            'lettuce': 6,
            'carrot': 2,
            'radish': 1,
            'tomato': 18,
            'pepper': 12,
            'broccoli': 12,
            'spinach': 4,
            'kale': 8
        }
        return spacing_guide.get(crop_type.lower(), 6)
    
    def generate_fibonacci_arrangement(self) -> List[Tuple[float, float]]:
        """Generate Fibonacci spiral arrangement coordinates"""
        fibonacci_nums = [1, 1, 2, 3, 5, 8, 13, 21]
        coordinates = []
        
        for i, fib in enumerate(fibonacci_nums):
            angle = i * 137.5  # Golden angle in degrees
            radius = fib * 0.5
            x = radius * math.cos(math.radians(angle))
            y = radius * math.sin(math.radians(angle))
            coordinates.append((round(x, 1), round(y, 1)))
        
        return coordinates
    
    def calculate_hexagonal_pattern(self, base_spacing: float) -> Dict:
        """Calculate hexagonal planting pattern"""
        hex_spacing = base_spacing * math.sqrt(3) / 2
        
        return {
            'row_spacing': f"{hex_spacing:.1f} inches",
            'plant_spacing': f"{base_spacing} inches",
            'efficiency_gain': "15% more plants per area",
            'pattern': "hexagonal close-packing"
        }
    
    def get_season(self, date: datetime) -> str:
        """Get current season"""
        month = date.month
        if month in [12, 1, 2]:
            return "winter"
        elif month in [3, 4, 5]:
            return "spring"
        elif month in [6, 7, 8]:
            return "summer"
        else:
            return "autumn"
