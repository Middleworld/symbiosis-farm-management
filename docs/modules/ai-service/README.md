# To run the AI service locally:
# 1. Install dependencies:
#    pip install -r requirements.txt
# 2. Start the server:
#    uvicorn main:app --reload --host 0.0.0.0 --port 8001
#
# The service will be available at http://localhost:8001
# Test with: curl -X POST http://localhost:8001/ask -H "Content-Type: application/json" -d '{"question": "What should I plant next week?"}'
