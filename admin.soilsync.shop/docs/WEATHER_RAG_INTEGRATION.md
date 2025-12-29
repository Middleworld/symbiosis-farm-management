# 🌤️ Weather Data RAG Integration

This system integrates 45+ years of historical weather data into your AI/RAG (Retrieval-Augmented Generation) system for agricultural insights and recommendations.

## 🚀 Quick Start

### 1. Configure Environment Variables

Add these to your `.env` file:

```bash
# Weather API Keys
OPENWEATHER_API_KEY=your_openweather_api_key
MET_OFFICE_API_KEY=your_met_office_api_key

# Farm Location (for weather data)
FARM_LATITUDE=52.0
FARM_LONGITUDE=-2.0
```

### 2. Run Database Migrations

```bash
php artisan migrate
```

### 3. Ingest Historical Weather Data

Ingest 45 years of weather data (this may take several hours):

```bash
# Full ingestion (45 years)
php artisan weather:ingest-historical

# Custom date range
php artisan weather:ingest-historical --start-date=2000-01-01 --end-date=2024-12-31

# Dry run (see what would be done)
php artisan weather:ingest-historical --dry-run
```

### 4. Create RAG Dataset

```bash
php artisan ai:ingest weather_historical_rag
```

## 📊 Available AI Insights

Your AI can now answer questions like:

### Weather Patterns
- "What were the frost patterns in March over the last 10 years?"
- "How has rainfall changed in April over the decades?"
- "What's the average temperature for June across all years?"

### Agricultural Insights
- "When is the best time to plant carrots based on historical data?"
- "How many frost-free days do we typically have in spring?"
- "What's the optimal planting window for lettuce?"

### Climate Analysis
- "How have growing degree days changed over the last 20 years?"
- "What's the trend in spring frost frequency?"
- "How does this year's weather compare to historical averages?"

## 🔌 API Endpoints

### Weather Insights (AI-powered)
```http
POST /api/weather/insights
Content-Type: application/json

{
  "query": "When should I plant potatoes?",
  "context": {
    "crop": "potatoes",
    "location": "farm"
  }
}
```

### Historical Data
```http
GET /api/weather/historical?start_date=2020-01-01&end_date=2020-12-31
```

### Monthly Patterns
```http
GET /api/weather/patterns/monthly?month=3&start_year=2010&end_year=2020
```

### Growing Degree Days
```http
GET /api/weather/gdd?start_date=2020-01-01&end_date=2020-12-31&base_temp=10
```

### Frost Analysis
```http
GET /api/weather/frost?start_date=2020-01-01&end_date=2020-12-31
```

### Planting Suitability
```http
GET /api/weather/planting?start_date=2020-01-01&end_date=2020-12-31
```

### Statistics
```http
GET /api/weather/stats
```

## 🏗️ System Architecture

### Components

1. **WeatherDataIngestionService** - Bulk ingestion of historical weather data
2. **WeatherHistoricalData Model** - Database storage with advanced querying
3. **WeatherApiController** - REST API for weather data access
4. **AiDataAccessService** - Integration with existing AI data access layer
5. **AiIngestionService** - RAG dataset creation from weather data

### Database Schema

The `weather_historical_data` table stores:
- Daily weather observations (temp, humidity, pressure, wind, precipitation)
- Agricultural calculations (GDD, frost risk, planting suitability)
- Raw API responses for future analysis
- Geospatial indexing for location-based queries

### RAG Integration

Weather data is processed into RAG-ready chunks:
- **Yearly summaries** - Annual weather patterns
- **Monthly patterns** - Seasonal trends across years
- **Planting insights** - Agricultural recommendations
- **Climate analysis** - Long-term weather trends

## 📈 Data Sources

### Primary Sources
- **OpenWeatherMap API** - Historical weather data (main source)
- **Met Office API** - UK weather data (when available)

### Data Processing
- **Growing Degree Days (GDD)** - Temperature accumulation for crop growth
- **Frost Risk Assessment** - Cold damage potential
- **Planting Suitability** - Optimal planting conditions
- **Weather Pattern Analysis** - Historical trends and anomalies

## 🔧 Configuration

### API Rate Limits
- OpenWeatherMap: 1000 calls/day (free tier)
- Met Office: Varies by subscription
- Batch processing with automatic rate limiting

### Data Retention
- Full historical data retained in database
- RAG datasets refreshed monthly
- API responses cached for 24 hours

### Performance Optimization
- Database indexes on date, location, and analysis fields
- Chunked data processing for large datasets
- Background job processing for ingestion tasks

## 🚨 Troubleshooting

### Common Issues

**API Key Missing**
```bash
# Check environment variables
php artisan tinker
>>> env('OPENWEATHER_API_KEY')
```

**Database Connection**
```bash
# Test database
php artisan tinker
>>> DB::connection()->getPdo()
```

**Memory Issues**
```bash
# Increase memory for large ingestions
php -d memory_limit=512M artisan weather:ingest-historical
```

**Rate Limiting**
```bash
# Reduce batch size
php artisan weather:ingest-historical --batch-size=15
```

## 📚 Advanced Usage

### Custom Analysis
Extend the `WeatherDataIngestionService` for custom agricultural metrics:

```php
// Add custom analysis method
public function analyzeCustomMetric($crop, $conditions) {
    // Your custom analysis logic
}
```

### Integration with FarmOS
Weather data can be correlated with FarmOS logs:

```php
// Link weather to planting logs
$weatherDuringPlanting = WeatherHistoricalData::whereBetween('date', [
    $plantingLog->planting_date,
    $plantingLog->planting_date->addDays(30)
])->get();
```

### Machine Learning Integration
Use weather patterns for predictive modeling:

```php
// Train models on historical weather vs yield data
$trainingData = $this->getWeatherYieldCorrelation($cropType, $years);
```

## 🤝 Contributing

### Adding New Weather Metrics
1. Update the `WeatherHistoricalData` model
2. Add migration for new columns
3. Update `WeatherDataIngestionService::storeWeatherDataPoint()`
4. Add API endpoints in `WeatherApiController`
5. Update RAG content generation

### Custom AI Insights
1. Extend `WeatherDataIngestionService::getWeatherInsights()`
2. Add new analysis methods
3. Update AI data catalog configuration
4. Test with sample queries

---

**Ready to harness 45+ years of weather wisdom for your farm! 🌱**
