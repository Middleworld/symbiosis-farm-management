#!/usr/bin/env python3
"""
Fast Farm Knowledge Base for Middle World Farms
Lightweight RAG system using simple text matching + embeddings
"""

import json
import os
from typing import Dict, List, Any
from dataclasses import dataclass, asdict
from datetime import datetime

@dataclass
class FarmKnowledge:
    """Farm-specific knowledge entry"""
    topic: str
    content: str
    source: str  # 'harvest_data', 'garden_notes', 'variety_info', 'spacing'
    confidence: float  # 0-1, how reliable this info is
    last_updated: str
    tags: List[str]

class FastFarmRAG:
    """Lightning-fast farm knowledge retrieval"""
    
    def __init__(self):
        self.knowledge_file = "/opt/sites/admin.middleworldfarms.org/farm_knowledge.json"
        self.knowledge_base: List[FarmKnowledge] = []
        self.load_knowledge()
        self.seed_initial_knowledge()
    
    def load_knowledge(self):
        """Load existing knowledge from file"""
        if os.path.exists(self.knowledge_file):
            try:
                with open(self.knowledge_file, 'r') as f:
                    data = json.load(f)
                    self.knowledge_base = [
                        FarmKnowledge(**item) for item in data
                    ]
                print(f"✅ Loaded {len(self.knowledge_base)} knowledge entries")
            except Exception as e:
                print(f"⚠️ Error loading knowledge: {e}")
                self.knowledge_base = []
        else:
            self.knowledge_base = []
    
    def save_knowledge(self):
        """Save knowledge to file"""
        try:
            data = [asdict(kb) for kb in self.knowledge_base]
            with open(self.knowledge_file, 'w') as f:
                json.dump(data, f, indent=2)
            print(f"💾 Saved {len(self.knowledge_base)} knowledge entries")
        except Exception as e:
            print(f"❌ Error saving knowledge: {e}")
    
    def seed_initial_knowledge(self):
        """Add initial Middle World Farms knowledge"""
        if len(self.knowledge_base) > 0:
            return  # Already seeded
        
        initial_knowledge = [
            FarmKnowledge(
                topic="Brussels Sprouts Bed Capacity",
                content="75cm wide x 10m long beds can fit 52 Brussels sprouts plants when nets are not needed. Parasitic wasps provide natural pest control, eliminating need for protective netting.",
                source="garden_notes",
                confidence=1.0,
                last_updated="2025-08-22",
                tags=["brussels_sprouts", "bed_capacity", "spacing", "pest_control"]
            ),
            FarmKnowledge(
                topic="F1 Doric Christmas Harvest",
                content="F1 Doric Brussels sprouts should be planted June 15th - July 15th for Christmas harvest. Optimal sowing date is July 1st for best December yields.",
                source="variety_info",
                confidence=0.9,
                last_updated="2025-08-22",
                tags=["f1_doric", "brussels_sprouts", "christmas_harvest", "timing"]
            ),
            FarmKnowledge(
                topic="Middle World Farms Bed Dimensions",
                content="Standard beds are 75cm (30 inches) wide by 10m long. This is different from metric calculations that assume 30cm wide beds.",
                source="garden_notes",
                confidence=1.0,
                last_updated="2025-08-22",
                tags=["bed_dimensions", "measurements", "spacing"]
            ),
            FarmKnowledge(
                topic="Parasitic Wasp Population",
                content="Parasitic wasps are abundant at Middle World Farms, providing excellent natural pest control. This reduces need for protective netting on crops.",
                source="pest_monitoring",
                confidence=0.9,
                last_updated="2025-08-22",
                tags=["beneficial_insects", "pest_control", "natural_farming"]
            ),
            FarmKnowledge(
                topic="Brussels Sprouts Spacing",
                content="Brussels sprouts need 45-60cm spacing between plants. In 75cm wide beds, plant 2 rows with 45cm spacing along rows for optimal growth.",
                source="spacing_guide",
                confidence=0.9,
                last_updated="2025-08-22",
                tags=["brussels_sprouts", "spacing", "plant_density"]
            ),
            FarmKnowledge(
                topic="JWA Safety Guidelines",
                content="Making JADAM Wetting Agent (JWA) requires safety precautions. Potassium hydroxide (KOH) is caustic - wear gloves, eye protection, and work in ventilated area. Heat oil gently (don't let it smoke), add KOH slowly while stirring. Keep children and pets away during mixing. Store finished JWA safely labeled.",
                source="safety_guidelines",
                confidence=1.0,
                last_updated="2025-08-22",
                tags=["jwa", "safety", "koh", "potassium_hydroxide", "protective_equipment"]
            ),
            FarmKnowledge(
                topic="JS Safety Guidelines",
                content="Making JADAM Sulfur (JS) is DANGEROUS - the sulfur + KOH reaction generates intense heat that can cause severe burns. ESSENTIAL: wear chemical-resistant gloves, safety goggles, long sleeves. Work outdoors or with excellent ventilation. Add KOH to sulfur very slowly while stirring constantly. Keep water nearby for emergencies. The mixture gets extremely hot - can reach boiling temperatures. Never lean over the container while mixing.",
                source="safety_guidelines", 
                confidence=1.0,
                last_updated="2025-08-22",
                tags=["js", "safety", "burns", "heat", "koh", "sulfur", "protective_equipment", "danger"]
            )
        ]
        
        self.knowledge_base.extend(initial_knowledge)
        self.save_knowledge()
        print(f"🌱 Seeded {len(initial_knowledge)} initial knowledge entries")
    
    def search_knowledge(self, query: str, max_results: int = 3) -> List[FarmKnowledge]:
        """Improved knowledge search that actually works"""
        query_lower = query.lower().strip()
        
        # Extract key terms from the query
        query_terms = set(query_lower.split())
        
        # Remove common stop words that don't help with search
        stop_words = {'is', 'it', 'to', 'make', 'how', 'what', 'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'for', 'with', 'by', 'should', 'i', 'be', 'worried', 'safe'}
        meaningful_terms = query_terms - stop_words
        
        # Add known variations for important farm terms
        expanded_terms = set(meaningful_terms)
        for term in meaningful_terms:
            if 'jwa' in term or 'wetting' in term or 'agent' in term:
                expanded_terms.update(['jwa', 'wetting', 'agent', 'wetting_agent', 'jadam'])
            elif 'jadam' in term:
                expanded_terms.update(['jwa', 'jms', 'js', 'jhs', 'jlf'])
            elif 'sulfur' in term or 'sulpher' in term:
                expanded_terms.update(['sulfur', 'js', 'jadam'])
        
        scored_results = []
        
        for kb in self.knowledge_base:
            score = 0
            topic_lower = kb.topic.lower()
            content_lower = kb.content.lower()
            tags_lower = ' '.join(kb.tags).lower()
            
            # High priority: exact topic matches
            for term in expanded_terms:
                if term in topic_lower:
                    score += 10
            
            # Medium priority: content matches
            for term in expanded_terms:
                if term in content_lower:
                    score += 3
            
            # Lower priority: tag matches  
            for term in expanded_terms:
                if term in tags_lower:
                    score += 2
            
            # Boost for JADAM-related queries
            if any(jadam_term in query_lower for jadam_term in ['jadam', 'jwa', 'jms', 'js', 'jhs', 'jlf']):
                if any(jadam_term in topic_lower for jadam_term in ['jadam', 'jwa', 'jms', 'js', 'jhs', 'jlf']):
                    score += 15
            
            # Apply confidence multiplier
            score *= kb.confidence
            
            if score > 0:
                scored_results.append((score, kb))
        
        # Sort by score (highest first) and return top results
        scored_results.sort(key=lambda x: x[0], reverse=True)
        return [kb for _, kb in scored_results[:max_results]]
    
    def add_knowledge(self, topic: str, content: str, source: str, 
                     confidence: float = 0.8, tags: List[str] = None):
        """Add new knowledge entry"""
        if tags is None:
            tags = []
        
        new_knowledge = FarmKnowledge(
            topic=topic,
            content=content,
            source=source,
            confidence=confidence,
            last_updated=datetime.now().strftime("%Y-%m-%d"),
            tags=tags
        )
        
        self.knowledge_base.append(new_knowledge)
        self.save_knowledge()
        print(f"📝 Added knowledge: {topic}")
    
    def get_context_for_query(self, query: str) -> str:
        """Get formatted context for AI prompt"""
        relevant_knowledge = self.search_knowledge(query, max_results=3)
        
        if not relevant_knowledge:
            return ""
        
        context_parts = []
        for kb in relevant_knowledge:
            context_parts.append(f"""
📋 **{kb.topic}** (Source: {kb.source}, Confidence: {kb.confidence:.1f})
{kb.content}
Tags: {', '.join(kb.tags)}
""")
        
        return f"""
🧠 **MIDDLE WORLD FARMS KNOWLEDGE BASE**:
{''.join(context_parts)}
---
Use this farm-specific knowledge to provide accurate, customized advice.
"""

# Global instance
farm_rag = FastFarmRAG()

def get_farm_context(query: str) -> str:
    """Quick function to get farm context"""
    return farm_rag.get_context_for_query(query)

def add_farm_knowledge(topic: str, content: str, source: str = "manual", 
                      confidence: float = 0.8, tags: List[str] = None):
    """Quick function to add knowledge"""
    farm_rag.add_knowledge(topic, content, source, confidence, tags)

if __name__ == "__main__":
    # Test the system
    print("🧪 Testing Fast Farm RAG...")
    
    test_queries = [
        "Brussels sprouts in 75cm bed",
        "F1 Doric Christmas harvest",
        "parasitic wasps pest control",
        "bed dimensions spacing"
    ]
    
    for query in test_queries:
        print(f"\n🔍 Query: {query}")
        context = get_farm_context(query)
        print(context[:200] + "..." if len(context) > 200 else context)
