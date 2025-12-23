#!/bin/bash

# AI Model Terminal Commands

# Function to ask any model a question
ask_ai() {
    local model="$1"
    local question="$2"
    
    if [ -z "$model" ] || [ -z "$question" ]; then
        echo "Usage: ask_ai <model> <question>"
        echo "Models: mistral, phi3:mini, gemma2:2b, tinyllama"
        return 1
    fi
    
    echo "🤖 Asking $model: $question"
    echo "⏱️  Please wait..."
    
    curl -s -X POST http://localhost:11434/api/generate \
        -H "Content-Type: application/json" \
        -d "{\"model\": \"$model\", \"prompt\": \"$question\", \"stream\": false}" \
        | jq -r '.response' 2>/dev/null || echo "Error: jq not installed or API failed"
}

# Quick model aliases
alias ask_mistral='ask_ai mistral'
alias ask_phi='ask_ai phi3:mini'  
alias ask_gemma='ask_ai gemma2:2b'
alias ask_tiny='ask_ai tinyllama'

# List models
alias list_models='ollama list'

# Model info
models_info() {
    echo "🤖 Available AI Models:"
    echo "1. mistral     - Best quality (4.4GB, 40-60s)"
    echo "2. phi3:mini   - Good balance (2.2GB, 15-30s)"
    echo "3. gemma2:2b   - Fast (1.6GB, 10-20s)"
    echo "4. tinyllama   - Fastest (637MB, 5-10s)"
    echo ""
    echo "Usage examples:"
    echo "  ask_mistral 'What is the best spacing for tomatoes?'"
    echo "  ask_tiny 'When to plant lettuce?'"
    echo "  ask_phi 'How to prevent blight?'"
}

echo "✅ AI Terminal Commands loaded!"
echo "Run 'models_info' to see usage examples"
