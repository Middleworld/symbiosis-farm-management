# Succession Planning System - Complete Instructions

## Overview

The Succession Planning System is a revolutionary backward-planning tool for farmOS that enables intelligent crop succession management with real-time bed allocation, seasonal growth adjustments, and seamless quick-form integration.

## Core Features

### 🧠 Intelligent Seasonal Planning
The system automatically adjusts succession timing based on seasonal growing conditions:

- **Spring Harvests (Mar-Jun)**: 2-week sowing intervals with accelerating growth rates (each succession 10% faster)
- **Autumn Harvests (Aug-Nov)**: 1-week sowing intervals with decelerating growth rates (each succession 15% slower)
- **Summer/Winter**: 1.5-week intervals with moderate growth adjustments

### 🎯 Backward Planning Logic
- Start with desired harvest window
- System calculates optimal sowing dates based on seasonal growth patterns
- Each succession maintains appropriate spacing for continuous harvest flow

### 🏡 Real-Time Bed Allocation
- Visual drag-and-drop interface for assigning successions to beds
- Automatic conflict detection with existing farmOS plantings
- Timeline visualization showing bed occupancy and succession blocks

### 📝 Quick Form Integration
- One-click generation of farmOS quick forms for seeding, transplanting, and harvesting
- Pre-populated with calculated dates and succession metadata
- Direct links to farmOS data entry

## How to Use

### 1. Access the Succession Planner
Navigate to `/admin/farmos/succession-planning` in your farmOS admin interface.

### 2. Configure Your Plan
- **Crop Selection**: Choose crop type and variety from farmOS taxonomy
- **Harvest Window**: Set desired harvest start and end dates
- **Succession Count**: Specify number of successions (auto-calculated based on window)
- **AI Enhancement**: Enable AI analysis for optimal timing recommendations

### 3. Generate Successions
Click "Calculate Succession Plan" to generate:
- Seasonally-adjusted sowing dates
- Transplant timing (if applicable)
- Harvest dates with proper spacing
- Bed allocation recommendations

### 4. Allocate to Beds
- Drag succession cards from the sidebar to bed timelines
- System prevents conflicts with existing plantings
- Visual feedback shows valid/invalid drop zones
- Auto-updates succession status and generates quick form URLs

### 5. Execute with Quick Forms
- Each allocated succession generates three quick form URLs:
  - **Seeding**: Record sowing date and details
  - **Transplanting**: Track transplant operations
  - **Harvesting**: Log harvest data and yields

## Seasonal Logic Details

### Spring Succession Pattern
```
Succession 1: Sow early → Fast growth (45 days) → Harvest first
Succession 2: Sow +2 weeks → 10% faster (40 days) → Harvest +2 weeks
Succession 3: Sow +4 weeks → 20% faster (36 days) → Harvest +4 weeks
```
*Result*: Wider sowing spacing with accelerating harvests maintains continuous supply

### Autumn Succession Pattern
```
Succession 1: Sow early → Slow growth (45 days) → Harvest first
Succession 2: Sow +1 week → 15% slower (52 days) → Harvest +1 week
Succession 3: Sow +2 weeks → 30% slower (60 days) → Harvest +2 weeks
Succession 4: Sow +3 weeks → 45% slower (69 days) → Harvest +3 weeks
```
*Result*: Closer sowing spacing with decelerating growth creates evenly-spaced harvests despite cooling conditions

## Technical Architecture

### Backend Components
- **SuccessionPlanningController**: Main calculation engine
- **FarmOSApi**: Real-time bed data and conflict detection
- **SymbiosisAIService**: AI-enhanced planning recommendations
- **FarmOSQuickFormService**: URL generation and form integration

### Frontend Components
- **succession-planning.blade.php**: Main interface
- **Drag-and-drop API**: Native HTML5 with conflict validation
- **Timeline visualization**: SVG-based bed occupancy display
- **Real-time updates**: AJAX-powered bed allocation tracking

### Data Flow
1. User inputs → Controller validation → Seasonal calculations
2. FarmOS API queries → Bed availability assessment
3. AI analysis (optional) → Timing optimizations
4. Frontend rendering → Drag-and-drop initialization
5. Allocation updates → Quick form URL generation

## Troubleshooting

### Drag-and-Drop Not Working
**Issue**: Can't drag successions to timeline initially
**Solution**: Ensure succession plan is calculated first, then drag-and-drop initializes automatically

**Issue**: Dragging works after "Clear All Allocations"
**Cause**: Timing issue in initialization order (now fixed)
**Solution**: System now properly sequences: calculate → render timeline → initialize drag-drop

### Incorrect Succession Timing
**Issue**: Growth periods vary illogically
**Cause**: Original logic added offsets incorrectly
**Solution**: Now uses per-succession harvest dates as baseline

### Bed Conflicts Not Detected
**Issue**: System allows invalid allocations
**Cause**: FarmOS API data not loading
**Solution**: Check FarmOS connection and API credentials

### Quick Forms Not Generating
**Issue**: URLs missing or incorrect
**Cause**: Allocation data not saved properly
**Solution**: Ensure succession is properly allocated to bed before generating forms

## Configuration

### Environment Variables
```env
FARMOS_BASE_URL=https://your-farm.farmos.net
FARMOS_CLIENT_ID=your_client_id
FARMOS_CLIENT_SECRET=your_client_secret
```

### Crop Timing Presets
Located in `SuccessionPlanningController::getCropTimingPresets()`:
- Transplant days (seed to transplant)
- Harvest days (seed to harvest)
- Yield period (harvest window)

### AI Integration
- Uses SymbiosisAI service for enhanced recommendations
- Fallback to crop-specific presets if AI unavailable
- Confidence scoring for AI suggestions

## API Endpoints

### POST `/admin/farmos/succession-planning/calculate`
Calculate succession plan
```json
{
  "crop_id": "beets",
  "variety_id": "beets_red",
  "harvest_start": "2026-06-01",
  "harvest_end": "2026-08-15",
  "succession_count": 5,
  "use_ai": true
}
```

### Response
```json
{
  "success": true,
  "succession_plan": {
    "crop": {...},
    "variety": {...},
    "plantings": [
      {
        "succession_id": 1,
        "seeding_date": "2026-04-17",
        "transplant_date": "2026-05-10",
        "harvest_date": "2026-06-01",
        "bed_name": "Bed 1",
        "quick_form_urls": {
          "seeding": "...",
          "transplanting": "...",
          "harvest": "..."
        }
      }
    ]
  }
}
```

## Future Enhancements

### Planned Features
- **Weather Integration**: Real-time weather data for growth predictions
- **Yield Forecasting**: Historical data analysis for yield estimates
- **Multi-Crop Planning**: Simultaneous planning for multiple crops
- **Mobile Interface**: Touch-optimized drag-and-drop for tablets
- **Automated Scheduling**: Cron-based succession reminders

### Integration Opportunities
- **Inventory Management**: Link to seed inventory tracking
- **Labor Planning**: Staff scheduling based on succession timelines
- **Financial Planning**: Cost analysis and budget projections
- **Quality Tracking**: Link to post-harvest quality data

## Support

For technical issues or feature requests:
1. Check the troubleshooting section above
2. Review farmOS API connectivity
3. Verify crop taxonomy configuration
4. Contact development team with console error logs

---

*Last updated: September 23, 2025*
*System version: Succession Planning v2.0 with Seasonal Intelligence*</content>
<parameter name="filePath">/opt/sites/admin.middleworldfarms.org/docs/SUCCESSION_PLANNING_COMPLETE_INSTRUCTIONS.md