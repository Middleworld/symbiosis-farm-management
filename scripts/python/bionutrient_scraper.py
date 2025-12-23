#!/usr/bin/env python3
"""
Comprehensive Bionutrient Institute Knowledge Scraper
Systematically extracts all detailed research from BI documentation
"""

import requests
import time
import re
from typing import List, Dict, Set
from urllib.parse import urljoin, urlparse
from fast_farm_rag import add_farm_knowledge

class BionutrientScraper:
    def __init__(self):
        self.base_url = "https://our-sci.gitlab.io/bionutrient-institute/bi-docs/"
        self.visited_urls: Set[str] = set()
        self.extracted_knowledge = []
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (compatible; BionutrientScraper/1.0)'
        })
    
    def discover_all_pages(self) -> List[str]:
        """Discover all pages in the BI documentation"""
        print("🔍 Discovering all Bionutrient Institute pages...")
        
        # Known major sections from the initial scan
        known_sections = [
            "Data_Explorer/",
            "2020%20Final%20Report/", 
            "Grains_Report/",
            "Food_Desert_Report/",
            "Hydroponic_Report/",
            "blueberry_report/",
            "2019_report/",
            "2018_report/",
            "mou/",
            "lab_communication_channels/",
            "meeting_notes/",
            "2021_plan/",
            "2021_methods/", 
            "2020_plan/",
            "2020_methods/",
            "2020_survey_design/",
            "intake/",
            "full_spec/",
            "extraction/",
            "wet_chem_assays/",
            "moisture_content/",
            "soil_grinding/",
            "soil_scanning/",
            "soil_respiration/",
            "soil_ph/",
            "loi/",
            "glassware_cleaning/",
            "acid_bath/",
            "lab_equipment/",
            "new_lab_checklist/",
            "xrf_size/"
        ]
        
        urls = [self.base_url]  # Start with main page
        for section in known_sections:
            urls.append(urljoin(self.base_url, section))
        
        print(f"📋 Found {len(urls)} initial pages to scrape")
        return urls
    
    def extract_page_content(self, url: str) -> Dict[str, str]:
        """Extract meaningful content from a single page"""
        try:
            print(f"📖 Scraping: {url}")
            response = self.session.get(url, timeout=15)
            response.raise_for_status()
            
            html_content = response.text
            
            # Extract title
            title_match = re.search(r'<title>(.*?)</title>', html_content, re.IGNORECASE)
            title = title_match.group(1) if title_match else url.split('/')[-2]
            
            # Extract main content (remove HTML tags)
            content = re.sub(r'<script.*?</script>', '', html_content, flags=re.DOTALL | re.IGNORECASE)
            content = re.sub(r'<style.*?</style>', '', content, flags=re.DOTALL | re.IGNORECASE)
            content = re.sub(r'<[^>]+>', ' ', content)
            content = re.sub(r'\s+', ' ', content).strip()
            
            # Filter out navigation and boilerplate
            content_lines = content.split('\n')
            meaningful_lines = []
            
            for line in content_lines:
                line = line.strip()
                if (len(line) > 30 and 
                    not line.startswith('©') and
                    'mkdocs' not in line.lower() and
                    'read the docs' not in line.lower() and
                    'gitlab' not in line.lower()):
                    meaningful_lines.append(line)
            
            clean_content = ' '.join(meaningful_lines)
            
            return {
                'title': title,
                'content': clean_content[:2000],  # Limit content length
                'url': url
            }
            
        except Exception as e:
            print(f"❌ Error scraping {url}: {e}")
            return None
    
    def categorize_content(self, title: str, content: str, url: str) -> Dict[str, any]:
        """Categorize and tag bionutrient content"""
        
        tags = ["bionutrient", "research"]
        confidence = 0.8
        source = "bionutrient_institute"
        
        # Categorize by content type
        if any(term in title.lower() or term in content.lower() for term in 
               ["method", "procedure", "protocol", "assay"]):
            tags.extend(["methods", "procedures"])
            confidence = 0.9
            
        if any(term in title.lower() or term in content.lower() for term in 
               ["report", "results", "findings", "analysis"]):
            tags.extend(["results", "analysis"])
            
        if any(term in title.lower() or term in content.lower() for term in 
               ["soil", "mineral", "nutrient", "element"]):
            tags.extend(["soil_analysis", "minerals"])
            
        if any(term in title.lower() or term in content.lower() for term in 
               ["equipment", "lab", "instrument"]):
            tags.extend(["equipment", "laboratory"])
            
        if any(term in title.lower() or term in content.lower() for term in 
               ["tomato", "blueberry", "grain", "crop"]):
            tags.extend(["crops", "varieties"])
            
        if any(term in title.lower() or term in content.lower() for term in 
               ["hydroponic", "organic", "conventional"]):
            tags.extend(["growing_methods"])
            
        # Year-based tagging
        year_match = re.search(r'20(18|19|20|21)', title)
        if year_match:
            tags.append(f"year_{year_match.group(0)}")
        
        return {
            "topic": title,
            "content": content,
            "source": source,
            "confidence": confidence,
            "tags": tags,
            "url": url
        }
    
    def chunk_large_content(self, content: str, max_size: int = 800) -> List[str]:
        """Break large content into digestible chunks"""
        if len(content) <= max_size:
            return [content]
        
        # Split by sentences/paragraphs
        chunks = []
        sentences = re.split(r'[.!?]+', content)
        
        current_chunk = ""
        for sentence in sentences:
            if len(current_chunk + sentence) > max_size and current_chunk:
                chunks.append(current_chunk.strip())
                current_chunk = sentence
            else:
                current_chunk += sentence + ". "
        
        if current_chunk.strip():
            chunks.append(current_chunk.strip())
        
        return chunks
    
    def scrape_all_bionutrient_knowledge(self) -> int:
        """Main scraping function"""
        print("🧬 Starting comprehensive Bionutrient Institute scraping...")
        
        urls = self.discover_all_pages()
        total_knowledge = 0
        
        for i, url in enumerate(urls):
            if url in self.visited_urls:
                continue
                
            print(f"🔬 Processing {i+1}/{len(urls)}: {url.split('/')[-2] if '/' in url else url}")
            
            page_data = self.extract_page_content(url)
            if not page_data:
                continue
            
            self.visited_urls.add(url)
            
            # Categorize the content
            knowledge_entry = self.categorize_content(
                page_data['title'], 
                page_data['content'], 
                page_data['url']
            )
            
            # Chunk large content
            content_chunks = self.chunk_large_content(knowledge_entry['content'])
            
            for j, chunk in enumerate(content_chunks):
                if len(chunk) > 50:  # Only meaningful chunks
                    chunk_title = knowledge_entry['topic']
                    if len(content_chunks) > 1:
                        chunk_title += f" (Part {j+1})"
                    
                    add_farm_knowledge(
                        topic=chunk_title,
                        content=chunk,
                        source=knowledge_entry['source'],
                        confidence=knowledge_entry['confidence'],
                        tags=knowledge_entry['tags']
                    )
                    
                    total_knowledge += 1
                    print(f"✅ Added: {chunk_title[:60]}...")
            
            # Rate limiting to be respectful
            time.sleep(1)
            
            # Progress update
            if i % 5 == 0:
                print(f"⚡ Progress: {i+1}/{len(urls)} pages, {total_knowledge} knowledge entries")
        
        return total_knowledge
    
    def add_bionutrient_integration_knowledge(self):
        """Add knowledge about integrating bionutrient methods with farming"""
        
        integration_knowledge = [
            {
                "topic": "Bionutrient Analysis for Middle World Farms",
                "content": "Bionutrient Institute methods can analyze nutrient density in Middle World Farms crops. XRF spectrometry provides rapid mineral analysis of vegetables. Soil respiration tests indicate biological activity. These methods help optimize growing practices for maximum nutrition.",
                "source": "mwf_bionutrient_integration",
                "confidence": 0.9,
                "tags": ["bionutrient", "xrf", "soil_testing", "nutrition_analysis"]
            },
            {
                "topic": "JADAM + Bionutrient Monitoring",
                "content": "Combine JADAM ultra-low cost methods with bionutrient testing to prove nutritional quality. JLF and JMS applications can be validated using BI lab methods. Track mineral content improvements from indigenous microorganism applications.",
                "source": "jadam_bionutrient_synergy", 
                "confidence": 0.8,
                "tags": ["jadam", "bionutrient", "validation", "quality_testing"]
            },
            {
                "topic": "Brussels Sprouts Bionutrient Optimization",
                "content": "Use bionutrient methods to optimize Brussels sprouts mineral content. Test soil amendments and growing methods. Compare conventional vs JADAM vs biodynamic approaches using XRF analysis. Target high levels of calcium, magnesium, and trace minerals.",
                "source": "brussels_bionutrient_plan",
                "confidence": 0.9,
                "tags": ["brussels_sprouts", "optimization", "mineral_analysis", "growing_methods"]
            }
        ]
        
        for knowledge in integration_knowledge:
            add_farm_knowledge(
                topic=knowledge["topic"],
                content=knowledge["content"],
                source=knowledge["source"],
                confidence=knowledge["confidence"],
                tags=knowledge["tags"]
            )
            print(f"🔗 Added integration: {knowledge['topic']}")

def main():
    print("🧬 Bionutrient Institute Comprehensive Knowledge Extraction")
    print("📊 This will extract detailed scientific research and methods")
    
    scraper = BionutrientScraper()
    
    # Extract all bionutrient knowledge
    total_extracted = scraper.scrape_all_bionutrient_knowledge()
    
    # Add integration knowledge
    print("\n🔗 Adding bionutrient integration knowledge...")
    scraper.add_bionutrient_integration_knowledge()
    
    print(f"\n🎯 Bionutrient Knowledge Extraction Complete!")
    print(f"📈 Total research entries extracted: {total_extracted + 3}")
    print(f"🔬 Your AI now understands advanced bionutrient analysis!")
    print(f"🌱 Scientific methods integrated with farming practices!")
    
    return total_extracted

if __name__ == "__main__":
    main()
