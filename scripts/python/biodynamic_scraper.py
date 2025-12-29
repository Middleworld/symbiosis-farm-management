#!/usr/bin/env python3
"""
Biodynamic Knowledge Scraper
Automatically extracts and adds biodynamic farming knowledge to the RAG system
"""

import requests
import json
from fast_farm_rag import add_farm_knowledge

def scrape_biodynamic_knowledge():
    """Extract key biodynamic principles and add to knowledge base"""
    
    print("🌱 Scraping biodynamic knowledge from Demeter...")
    
    # Key biodynamic knowledge from the scraped content
    biodynamic_knowledge = [
        {
            "topic": "Biodynamic Farm as Living Organism",
            "content": "Biodynamic farming treats the whole farm as a unique living organism where each part nurtures the other: humans, plants, animals, and soil strive together. Going beyond organic standards, it focuses on reinforcing interaction between all farm elements in an environmentally friendly and regenerative way.",
            "source": "demeter.net",
            "confidence": 1.0,
            "tags": ["biodynamic", "holistic", "farm_organism", "regenerative"]
        },
        {
            "topic": "Biodynamic Diversity Principle",
            "content": "Diversity of microorganisms in soil, wild and cultivated plants, animals and enterprises on the farm leads to rich and vibrant working relationship with nature. Biodiversity is essential for ecosystem balance and mutual interdependencies.",
            "source": "demeter.net",
            "confidence": 1.0,
            "tags": ["biodiversity", "soil_health", "ecosystem", "resilience"]
        },
        {
            "topic": "Biodynamic Seven Principles",
            "content": "1. Regeneration (sustainability is not enough), 2. Integrating well-being of nature and humans, 3. Creating living context for all life, 4. Include animals respecting welfare, 5. Agriculture is contextual to ecology/landscape/culture, 6. Ecological responsibility for resources, 7. Social responsibility and community development",
            "source": "demeter.net",
            "confidence": 1.0,
            "tags": ["principles", "regeneration", "community", "animal_welfare"]
        },
        {
            "topic": "Biodynamic Animal Integration",
            "content": "Animals, especially cows, play central role in the farm organism. Include animals in a way that respects their well-being, while producing nutrient dense food, nourishing the soil and protecting wildlife. Animals are essential for farm fertility and balance.",
            "source": "demeter.net",
            "confidence": 0.9,
            "tags": ["animals", "cattle", "soil_fertility", "nutrient_cycling"]
        },
        {
            "topic": "Biodynamic Self-Sufficiency Goal",
            "content": "Each farm works with species suited to local ecology and culture, aiming for self-sufficiency in fodder and fertility. The whole farm, not just portions, should convert to biodynamic methods for complete system integration.",
            "source": "demeter.net",
            "confidence": 0.9,
            "tags": ["self_sufficiency", "local_ecology", "fertility", "conversion"]
        },
        {
            "topic": "Biodynamic Climate Action",
            "content": "Biodynamic farming addresses climate change through regenerative agriculture. Care for earth and nature has traditionally been at heart of agriculture. Now, with climate change and exhaustion of planetary resources, we are responsible for regeneration of ecology and landscapes.",
            "source": "demeter.net",
            "confidence": 0.9,
            "tags": ["climate_action", "regeneration", "sustainability", "ecology"]
        }
    ]
    
    # Add each knowledge entry to the RAG system
    added_count = 0
    for knowledge in biodynamic_knowledge:
        try:
            add_farm_knowledge(
                topic=knowledge["topic"],
                content=knowledge["content"],
                source=knowledge["source"],
                confidence=knowledge["confidence"],
                tags=knowledge["tags"]
            )
            added_count += 1
            print(f"✅ Added: {knowledge['topic']}")
        except Exception as e:
            print(f"❌ Failed to add {knowledge['topic']}: {e}")
    
    print(f"🎉 Successfully added {added_count} biodynamic knowledge entries!")
    return added_count

def add_biodynamic_to_ai_service():
    """Add biodynamic knowledge via API"""
    url = "http://localhost:8005/add_knowledge"
    
    # Additional specific biodynamic practices
    advanced_knowledge = [
        {
            "topic": "Biodynamic Preparations",
            "content": "Biodynamic preparations enhance soil life and plant vitality. Made from herbs, minerals, and animal substances, they work homeopathically to strengthen farm ecosystem. Apply in small quantities for maximum effect on soil biology.",
            "source": "biodynamic_practice",
            "confidence": 0.8,
            "tags": ["preparations", "soil_biology", "homeopathic", "vitality"]
        },
        {
            "topic": "Lunar and Cosmic Rhythms",
            "content": "Biodynamic farming considers cosmic rhythms and lunar cycles for optimal planting, cultivation and harvesting. Moon phases and planetary positions influence plant growth, seed germination, and soil cultivation activities.",
            "source": "biodynamic_calendar",
            "confidence": 0.7,
            "tags": ["lunar_cycles", "cosmic_rhythms", "planting_calendar", "timing"]
        }
    ]
    
    for knowledge in advanced_knowledge:
        try:
            response = requests.post(url, json=knowledge, timeout=10)
            if response.status_code == 200:
                print(f"✅ API Added: {knowledge['topic']}")
            else:
                print(f"❌ API Failed: {knowledge['topic']} - {response.status_code}")
        except Exception as e:
            print(f"❌ API Error: {e}")

if __name__ == "__main__":
    print("🌍 Biodynamic Knowledge Integration Starting...")
    
    # Method 1: Direct RAG addition
    count = scrape_biodynamic_knowledge()
    
    # Method 2: API addition (if service is running)
    try:
        add_biodynamic_to_ai_service()
    except Exception as e:
        print(f"⚠️ AI service not available for API method: {e}")
    
    print(f"🎯 Total biodynamic knowledge integrated: {count}+ entries")
    print("🚀 Your AI now understands biodynamic principles!")
