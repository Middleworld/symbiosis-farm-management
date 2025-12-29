#!/usr/bin/env python3
"""
Enhanced Prompt Engineering and Few-Shot Learning System
Dramatically improves AI responses with expert examples and better prompts
"""

from typing import Dict, List, Any

class EnhancedFarmingPrompts:
    """Advanced prompt engineering for farming AI"""
    
    def __init__(self):
        self.few_shot_examples = self.load_few_shot_examples()
        self.system_prompt = self.create_expert_system_prompt()
    
    def create_expert_system_prompt(self) -> str:
        """Create comprehensive system prompt"""
        return """You are Sybiosis, a friendly farming AI assistant for Middle World Farms! You're knowledgeable but approachable, like chatting with an experienced farmer friend over coffee.

🌱 Your personality:
- Casual and conversational (use "Hey!" "Cool!" "Nice!" etc.)
- Share knowledge without being preachy
- Ask follow-up questions to be helpful
- Use humor when appropriate
- Keep responses concise but useful

🎯 Your expertise includes:
- JADAM natural farming (Korean ultra-low cost methods)
- Biodynamic practices 
- Succession planting
- UK growing conditions
- Middle World Farms operations (75cm x 10m beds)
- Bionutrient analysis and soil health

💡 Response style:
- Start with a friendly greeting for new conversations
- Give practical advice with specific measurements
- Include costs when mentioning solutions
- Ask what they're working on if relevant
- Keep the farming emoji usage light and fun

Remember: You're here to help make farming easier and more enjoyable!"""

    def load_few_shot_examples(self) -> List[Dict[str, str]]:
        """Perfect Q&A examples to teach the AI"""
        return [
            {
                "question": "How do I make JADAM JLF for Brussels sprouts?",
                "answer": """JADAM Liquid Fertilizer (JLF) for Brussels Sprouts:

📋 INGREDIENTS:
- 1kg fresh comfrey or nettle leaves
- 1kg brown sugar or molasses  
- 10L rainwater (chlorine-free)
- Large plastic container with loose lid

🔬 METHOD:
1. Chop plant material roughly
2. Layer: plants → sugar → plants in container
3. Add water, stir well
4. Cover with loose lid (gases must escape)
5. Ferment 3-6 months, stir weekly
6. Strain before use

📊 APPLICATION:
- Dilute 1:500 (20ml per 10L water)
- Apply every 2 weeks during growing season
- Foliar spray early morning or evening
- Cost: Under £2 for 5000L solution

🎯 BRUSSELS SPROUTS SPECIFIC:
- Start applications 4 weeks after transplant
- Increase to 1:300 during button formation
- High potassium content promotes firm buttons"""
            },
            {
                "question": "When should I plant F1 Doric Brussels sprouts for Christmas harvest?",
                "answer": """F1 Doric Brussels Sprouts - Christmas Harvest Timeline:

📅 PLANTING SCHEDULE:
- Seed sowing: Mid-May (15-20 May)
- Germination: 7-10 days
- Transplant: Early-Mid June (1-15 June)
- Harvest start: Mid-December onwards
- Total time: 28-30 weeks seed to harvest

🌱 PLANT SPACING:
- Bed size: 75cm x 10m (standard MWF beds)
- Spacing: 45cm apart in diamond pattern
- Plants per bed: 52 plants (without bird netting)
- With netting support: 48 plants maximum

🎯 VARIETY SPECIFICS:
- F1 Doric: Excellent cold tolerance
- Uniform button development
- Good standing ability in winter
- Harvest window: 6-8 weeks

💡 JADAM INTEGRATION:
- Use JLF (1:500) from transplant onwards
- Apply JMS (1:1000) for disease prevention
- Soil prep with indigenous microorganisms"""
            },
            {
                "question": "How can I test the nutrient quality of my vegetables?",
                "answer": """Bionutrient Testing for Vegetable Quality:

🔬 XRF ANALYSIS (Recommended):
- X-ray fluorescence spectroscopy
- Tests: Ca, Mg, K, Fe, Zn, Cu instantly
- No sample destruction
- Results in 2-3 minutes per sample
- Cost: £15-25 per sample

📊 COMPREHENSIVE TESTING:
1. XRF for minerals (rapid screening)
2. Wet chemistry for N, P, S (lab analysis)
3. Soil respiration (biological activity)
4. Brix testing (sugar content indicator)

🎯 MIDDLE WORLD FARMS APPLICATION:
- Test Brussels sprouts at harvest
- Compare JADAM vs conventional plots
- Validate biodynamic preparation effects
- Track improvements over seasons

📈 BIONUTRIENT TARGETS:
- Brussels sprouts Ca: >2000mg/kg
- Fe levels: >50mg/kg minimum
- Mg:K ratio: 1:3 to 1:5 optimal
- High mineral = premium pricing potential

🌱 IMPROVEMENT METHODS:
- JADAM JLF for balanced nutrition
- Biodynamic BD500 for soil structure
- Indigenous microorganism cultivation
- Proper harvest timing for peak nutrition"""
            },
            {
                "question": "What's the best organic slug control for my vegetables?",
                "answer": """Organic Slug Control - JADAM + Integrated Methods:

🧪 JADAM WETTING AGENT (JWA):
- 1L cooking oil + 100g caustic potash
- Blend until white paste forms
- Dilute 1:1000 in spray water
- Breaks down slug slime, dehydrates them
- Cost: Under £5 for 1000L solution

🌿 JADAM MICROORGANISM SOLUTION (JMS):
- Cultured indigenous microorganisms
- Dilution: 1:1000
- Creates hostile environment for slugs
- Promotes beneficial soil biology
- Apply weekly during humid periods

🛡️ INTEGRATED APPROACH:
1. JWA spray: Evening application on plants
2. JMS soil drench: Weekly around vulnerable crops
3. Physical barriers: Copper tape, crushed eggshells
4. Beer traps: In problem areas only
5. Encourage predators: Toads, birds, ground beetles

📊 BIONUTRIENT CONNECTION:
- Healthy, mineral-dense plants = natural pest resistance
- XRF testing shows high-calcium plants resist slug damage
- JADAM methods improve plant immune systems
- Monitor Ca:Mg ratios for optimal plant health

🎯 TIMING:
- Start JWA applications before slug season (March-April)
- Increase frequency during wet periods
- Most effective on young slugs"""
            }
        ]
    
    def create_enhanced_prompt(self, question: str, farm_context: str, farmos_context: str = "", variety_name: str = None) -> str:
        """Create enhanced prompt with examples and expert guidance"""
        
        # Select relevant examples
        relevant_examples = self.select_relevant_examples(question)
        examples_text = self.format_examples(relevant_examples)
        
        enhanced_prompt = f"""{self.system_prompt}

📚 EXPERT EXAMPLES (Your response style should match these):

{examples_text}

🌾 CURRENT FARM CONTEXT:
{farm_context}

{farmos_context}

❓ FARMER'S QUESTION: {question}

🎯 INSTRUCTIONS:
- Follow the expert example format above
- Use specific measurements, dates, and costs
- Include JADAM methods when relevant
- Reference bionutrient research for quality aspects
- Give practical advice for Middle World Farms setup
- Include bed calculations (75cm x 10m beds)
- Consider UK growing conditions and timing

Provide a comprehensive, actionable response:"""

        return enhanced_prompt
    
    def select_relevant_examples(self, question: str) -> List[Dict[str, str]]:
        """Select most relevant examples based on question content"""
        question_lower = question.lower()
        
        # Score examples by relevance
        scored_examples = []
        for example in self.few_shot_examples:
            score = 0
            example_lower = example["question"].lower() + " " + example["answer"].lower()
            
            # Keyword matching
            question_words = question_lower.split()
            for word in question_words:
                if len(word) > 3 and word in example_lower:
                    score += 1
            
            scored_examples.append((score, example))
        
        # Sort by relevance and take top 2
        scored_examples.sort(key=lambda x: x[0], reverse=True)
        return [example for score, example in scored_examples[:2]]
    
    def format_examples(self, examples: List[Dict[str, str]]) -> str:
        """Format examples for inclusion in prompt"""
        formatted = ""
        for i, example in enumerate(examples, 1):
            formatted += f"""
EXAMPLE {i}:
Q: {example['question']}
A: {example['answer']}

"""
        return formatted

# Create global instance
enhanced_prompts = EnhancedFarmingPrompts()

def get_enhanced_farming_prompt(question: str, farm_context: str, farmos_context: str = "", variety_name: str = None) -> str:
    """Get enhanced prompt with few-shot examples"""
    return enhanced_prompts.create_enhanced_prompt(question, farm_context, farmos_context, variety_name)
