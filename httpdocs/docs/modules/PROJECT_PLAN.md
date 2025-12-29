# Public Chatbot System - Project Plan

## 🎯 **Objective**
Create a separate public-facing chatbot for marketing and customer advice that:
- Uses existing Ollama models (phi3, gemma2, tinyllama) 
- Includes biodynamic farming knowledge via RAG
- Doesn't conflict with Martin's admin AI system
- Easy WordPress integration
- Separate ports and configuration

## 🏗️ **Architecture Design**

### **Separation Strategy**
```
Current Admin AI (Martin's):
├── Ports: 8005 (Phi-3), 8007 (TinyLlama)
├── Purpose: Internal admin succession planning
└── Database: Uses admin vector_db

New Public Chatbot:
├── Ports: 8080 (Main API), 8081 (Health/Status) 
├── Purpose: Public marketing & customer advice
├── Database: Uses public_chatbot_db (separate)
└── Models: Same Ollama models, different endpoints
```

### **Components to Build**
1. **Public Chatbot API Service** (port 8080)
2. **Separate Vector Database** (public_chatbot_db)
3. **WordPress Integration Endpoints**
4. **Model Selection Interface** (Gemma2, Phi-3, TinyLlama)
5. **Enhanced RAG System** (biodynamic + marketing data)
6. **Rate Limiting & Security** (public-facing protection)

## 📁 **Directory Structure**
```
/opt/sites/admin.middleworldfarms.org/public_chatbot/
├── chatbot_api.py              # Main FastAPI service
├── models/
│   ├── gemma2_service.py       # Gemma2 model handler
│   ├── phi3_service.py         # Phi-3 model handler  
│   └── tinyllama_service.py    # TinyLlama model handler
├── rag/
│   ├── public_rag_service.py   # Public RAG system
│   └── data/                   # Knowledge base files
├── wordpress/
│   ├── wp_integration.py       # WordPress API endpoints
│   └── wp_chatbot_widget.js    # Frontend widget
├── config/
│   ├── settings.py             # Configuration
│   └── .env                    # Environment variables
└── docs/
    ├── API_DOCUMENTATION.md    # API docs for WordPress
    └── INSTALLATION_GUIDE.md   # Setup instructions
```

## 🔧 **Technical Specifications**

### **Ports & Services**
- **8080**: Main chatbot API
- **8081**: Health check & status
- **Database**: `public_chatbot_db` (separate from admin)
- **Models**: Reuse existing Ollama on port 11434

### **API Endpoints**
```
GET  /                          # Service status
POST /chat                      # Main chat endpoint
POST /chat/gemma2               # Specific model selection
POST /chat/phi3                 # Specific model selection  
POST /chat/tinyllama            # Specific model selection
GET  /models                    # Available models
GET  /health                    # Health check
POST /wordpress/chat            # WordPress-optimized endpoint
GET  /wordpress/widget          # Widget configuration
```

### **Security Features**
- Rate limiting (per IP/session)
- Input sanitization
- Response filtering
- CORS configuration for WordPress
- API key protection (optional)

## 🗄️ **Database Strategy**

### **New Vector Database**
```sql
-- Separate database for public chatbot
CREATE DATABASE public_chatbot_db;
CREATE USER chatbot_user WITH PASSWORD 'secure_public_password';
GRANT ALL PRIVILEGES ON DATABASE public_chatbot_db TO chatbot_user;
```

### **Knowledge Categories**
1. **Biodynamic Farming** (existing data)
2. **Product Information** (seeds, tools, supplies)
3. **Growing Guides** (seasonal, regional advice)
4. **FAQ Responses** (common customer questions)
5. **Marketing Content** (benefits, features, comparisons)

## 🔌 **WordPress Integration**

### **Widget Features**
- Floating chat button
- Model selection dropdown
- Response history
- Mobile-responsive design
- Easy theme integration

### **Shortcode Support**
```php
[middleworld_chatbot model="auto"]
[middleworld_chatbot model="gemma2" theme="dark"]
[middleworld_chatbot model="tinyllama" max_history="10"]
```

## 📊 **Benefits of This Approach**

### **✅ No Conflicts**
- Separate ports from Martin's system
- Independent database and configuration  
- Own service management
- Isolated dependencies

### **✅ Resource Efficient**
- Reuses existing Ollama models
- Shared model weights (no duplication)
- Separate processing logic only

### **✅ Easy Maintenance**
- Independent updates and deployments
- Separate logs and monitoring
- Own backup/restore procedures

### **✅ WordPress Ready**
- REST API endpoints
- CORS configured
- Widget and shortcode support
- Theme integration hooks

## 🚀 **Implementation Priority**

### **Phase 1: Core System**
1. Set up separate vector database
2. Create main chatbot API service
3. Implement model handlers for all three AI models
4. Basic RAG integration with biodynamic data

### **Phase 2: WordPress Integration**
1. WordPress-optimized endpoints
2. Frontend widget development
3. Shortcode implementation
4. Theme integration testing

### **Phase 3: Enhancement**
1. Rate limiting and security
2. Analytics and logging
3. Advanced RAG features
4. Performance optimization

---

**Next Steps**: Begin with Phase 1 - setting up the separate database and core API service?
