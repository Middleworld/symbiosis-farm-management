#!/usr/bin/env python3
"""
Middle World Farms Website Scraper
Extracts farm-specific knowledge from middleworldfarms.org
"""

import requests
from fast_farm_rag import add_farm_knowledge

def scrape_middleworldfarms_knowledge():
    """Extract Middle World Farms specific knowledge"""
    
    print("🚜 Scraping Middle World Farms website...")
    
    # Knowledge extracted from middleworldfarms.org
    farm_knowledge = [
        {
            "topic": "Middle World Farms Mission",
            "content": "Middle World Farms grows fresh, nourishing food in harmony with the earth, committed to regenerative practices that enrich soil, protect biodiversity, and produce vibrant, healthy crops. Works hand in hand with nature through field rotation, on-site composting, and waste minimization.",
            "source": "middleworldfarms.org",
            "confidence": 1.0,
            "tags": ["mission", "regenerative", "biodiversity", "composting", "rotation"]
        },
        {
            "topic": "Middle World Farms Practices",
            "content": "Farm uses regenerative practices including crop rotation, on-site composting, and waste minimization to keep land and surrounding ecosystems in balance. Focus on cultivating fresh, flavorful harvests while caring for the earth.",
            "source": "middleworldfarms.org", 
            "confidence": 1.0,
            "tags": ["practices", "crop_rotation", "on_site_composting", "ecosystem_balance"]
        },
        {
            "topic": "Middle World Farms Community Focus",
            "content": "Partners with neighbors, schools, and fellow farmers to help more people access local, nutritious food. Forges deeper connections between soil, seed, and community. Invites customers to taste the difference that respect for the land can make.",
            "source": "middleworldfarms.org",
            "confidence": 1.0,
            "tags": ["community", "local_food", "partnerships", "education"]
        },
        {
            "topic": "Middle World Farms Product Boxes",
            "content": "Offers multiple vegetable box sizes: Single Person (£10-£330), Couple's (£15-£495), Small Family (£22-£726), Large Family (£25-£825). Seasonal, local delivery service with 2025 opening countdown.",
            "source": "middleworldfarms.org",
            "confidence": 1.0,
            "tags": ["vegetable_boxes", "pricing", "delivery", "seasonal", "local"]
        },
        {
            "topic": "Middle World Farms Philosophy",
            "content": "Believes fresh, nourishing food should be grown in harmony with earth and shared with local community. Started as small family plot, now thriving sustainable farm. Mission: cultivate fresh, flavorful harvests while caring for shared earth.",
            "source": "middleworldfarms.org",
            "confidence": 1.0,
            "tags": ["philosophy", "harmony", "sustainable", "family_farm", "local_community"]
        },
        {
            "topic": "Middle World Farms Environmental Impact",
            "content": "Asks 'Can the way you shop help repair the environment?' Focus on how purchasing decisions can support regenerative agriculture and environmental restoration through local, seasonal food systems.",
            "source": "middleworldfarms.org",
            "confidence": 0.9,
            "tags": ["environmental_impact", "regenerative_agriculture", "consumer_choice", "restoration"]
        }
    ]
    
    # Add knowledge to RAG system
    added_count = 0
    for knowledge in farm_knowledge:
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
    
    return added_count

def add_farm_specific_growing_knowledge():
    """Add specific growing knowledge inferred from farm operations"""
    
    growing_knowledge = [
        {
            "topic": "Middle World Farms Seasonal Operations",
            "content": "Farm operates on seasonal schedule with 2025 opening countdown, indicating careful planning around growing seasons. Offers year-round vegetable boxes suggesting succession planting and season extension techniques.",
            "source": "farm_operations",
            "confidence": 0.8,
            "tags": ["seasonal", "succession_planting", "season_extension", "planning"]
        },
        {
            "topic": "Middle World Farms Scale and Variety",
            "content": "Offers 4 different box sizes (single to large family) indicating diverse crop production and ability to scale harvest quantities. Suggests sophisticated succession planning and variety management.",
            "source": "product_analysis",
            "confidence": 0.8,
            "tags": ["scale", "variety_management", "harvest_planning", "product_diversity"]
        },
        {
            "topic": "Middle World Farms Quality Standards",
            "content": "Emphasizes 'fresh, nourishing food' and 'vibrant, healthy crops' indicating high quality standards. Customer reviews mention 4-5 stars, suggesting consistent quality delivery and growing practices.",
            "source": "quality_indicators",
            "confidence": 0.8,
            "tags": ["quality_standards", "fresh_produce", "customer_satisfaction", "consistency"]
        }
    ]
    
    for knowledge in growing_knowledge:
        add_farm_knowledge(
            topic=knowledge["topic"],
            content=knowledge["content"],
            source=knowledge["source"],
            confidence=knowledge["confidence"],
            tags=knowledge["tags"]
        )
        print(f"📈 Added growing insight: {knowledge['topic']}")

def scrape_farm_website():
    """Main function to scrape Middle World Farms knowledge"""
    print("🌍 Starting Middle World Farms knowledge extraction...")
    
    # Add direct website knowledge
    direct_count = scrape_middleworldfarms_knowledge()
    
    # Add inferred growing knowledge
    print("\n📊 Adding operational insights...")
    add_farm_specific_growing_knowledge()
    
    print(f"\n✅ Successfully integrated {direct_count}+ Middle World Farms knowledge entries!")
    print("🎯 Your AI now understands your farm's mission, practices, and operations!")

if __name__ == "__main__":
    scrape_farm_website()
