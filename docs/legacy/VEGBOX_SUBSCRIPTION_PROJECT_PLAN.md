# Vegbox Subscription System Replacement Project Plan

## Executive Summary
Replace WooCommerce Subscriptions GPL Vault with a custom Laravel-based subscription system optimized for monthly billing with weekly vegbox deliveries. The new system will use the `laravelcm/laravel-subscriptions` package as foundation, extended with vegbox-specific features.

**Timeline**: 8-10 weeks  
**Budget**: £2,500-£5,000 (development time only)  
**Risk Level**: Medium  
**Business Impact**: High (core subscription functionality)

## Current System Analysis

### Pain Points with WooCommerce Subscriptions
- GPL Vault license dependency causes service outages
- Monthly billing + weekly delivery model not natively supported
- Complex workarounds required for delivery scheduling
- Action Scheduler dependency for renewals
- Limited flexibility for vegbox-specific business logic

### Successful Temporary Fix
- Created `SubscriptionScheduler` service replicating Action Scheduler logic
- Built `FixBrokenSubscriptions` command for manual intervention
- Currently stable with 0 broken subscriptions
- Provides 3-6 months breathing room

## Chosen Solution: Laravel Subscriptions Package

### Why This Package?
- **MIT Licensed**: Completely free, no vendor lock-in
- **Flexible Architecture**: Supports complex subscription models
- **Feature-Based**: Perfect for delivery frequencies as "features"
- **Laravel Native**: Seamless integration with existing codebase
- **Active Development**: Regular updates and community support

### Package Capabilities
```php
// Monthly billing plan
$plan = Plan::create([
    'name' => 'Vegbox Monthly',
    'price' => 42.00,
    'billing_period' => Interval::MONTH,
    'billing_interval' => 1,
]);

// Features for delivery scheduling
$plan->features()->create([
    'name' => 'weekly_deliveries',
    'value' => 4, // deliveries per month
    'resettable_period' => 'month'
]);
```

## Project Phases

### Phase 1: Planning & Setup (Week 1-2)
**Goal**: Establish solid foundation and requirements

#### Tasks:
1. **Daily Monitoring Setup** (Week 1)
   - Automate subscription audits
   - Set up alerting for broken subscriptions
   - Monitor payment processing success

2. **Package Installation** (Week 1)
   - Install `laravelcm/laravel-subscriptions`
   - Run migrations and publish config
   - Test basic functionality

3. **Data Model Design** (Week 2)
   - Design VegboxPlan model extending base Plan
   - Design VegboxSubscription model
   - Design DeliverySchedule integration
   - Map existing WooCommerce data structures

#### Success Criteria:
- Automated monitoring alerts working
- Package installed and basic tests passing
- Data model designs documented and approved

### Phase 2: Core Development (Week 3-6)
**Goal**: Build subscription management core

#### Tasks:
4. **Vegbox Data Models** (Week 3)
   - Create VegboxPlan model with delivery features
   - Create VegboxSubscription model
   - Implement delivery schedule integration
   - Add vegbox-specific validation rules

5. **Subscription Scheduling** (Week 4-5)
   - Implement monthly billing logic
   - Build weekly delivery scheduling
   - Create Laravel task scheduler integration
   - Handle fortnight Week A/B rotation

6. **Payment Integration** (Week 6)
   - Integrate with existing FundsService
   - Implement automated renewal processing
   - Add payment failure handling
   - Create payment retry logic

#### Success Criteria:
- All data models created and tested
- Subscription creation working end-to-end
- Payment processing integrated
- Basic admin interface functional

### Phase 3: Admin Interface & Testing (Week 7-8)
**Goal**: Build management tools and validate system

#### Tasks:
7. **Admin Management Interface** (Week 7)
   - Create subscription management dashboard
   - Build delivery schedule calendar view
   - Add customer subscription management
   - Implement bulk operations

8. **Integration Testing** (Week 8)
   - Test complete subscription lifecycle
   - Validate delivery scheduling accuracy
   - Test payment processing reliability
   - Performance testing with 18+ subscriptions

#### Success Criteria:
- Admin can manage all subscriptions
- Delivery schedules generate correctly
- Payment processing reliable
- System handles edge cases

### Phase 4: Migration & Go-Live (Week 9-10)
**Goal**: Migrate existing data and launch

#### Tasks:
9. **Data Migration** (Week 9)
   - Create migration scripts for existing subscriptions
   - Validate data integrity
   - Test migration with sample data
   - Prepare rollback procedures

10. **Go-Live & Monitoring** (Week 10)
    - Execute migration during low-traffic period
    - Monitor system for 48 hours
    - Validate all subscription renewals
    - Keep WooCommerce system as backup for 30 days

#### Success Criteria:
- All existing subscriptions migrated successfully
- No data loss or corruption
- System running reliably for 7 days
- Rollback procedures tested and ready

## Technical Architecture

### Data Model Structure
```
VegboxPlan (extends Plan)
├── name: "Monthly Vegbox"
├── price: 42.00
├── billing_period: MONTH
├── billing_interval: 1
└── features:
    ├── weekly_deliveries: 4
    ├── delivery_frequency: weekly/fortnightly
    └── week_assignment: A/B/auto

VegboxSubscription (extends Subscription)
├── plan_id: relationship
├── subscriber_id: user/customer
├── status: active/cancelled/past_due
├── trial_ends_at: date
├── ends_at: date
└── delivery_schedule: JSON

DeliverySchedule
├── subscription_id
├── delivery_dates: JSON array
├── week_type: A/B/weekly
├── next_delivery: date
└── delivery_history: JSON
```

### Key Integration Points
- **Existing FundsService**: Payment processing
- **DeliveryScheduleService**: Delivery coordination
- **WpApiService**: Customer data access
- **SubscriptionAuditService**: Monitoring and reporting

## Risk Assessment & Mitigation

### High Risk Items
1. **Data Migration Complexity**
   - **Risk**: Losing subscription data during migration
   - **Mitigation**: Comprehensive testing, backup procedures, phased migration

2. **Payment Processing Integration**
   - **Risk**: Breaking existing payment flows
   - **Mitigation**: Extensive testing, gradual rollout, rollback procedures

3. **Delivery Scheduling Accuracy**
   - **Risk**: Incorrect delivery assignments causing customer dissatisfaction
   - **Mitigation**: Multiple validation layers, manual override capabilities

### Medium Risk Items
1. **Learning Curve**: New package architecture
2. **Performance**: Handling 18+ subscriptions efficiently
3. **Edge Cases**: Complex fortnight/week scenarios

### Low Risk Items
1. **Package Stability**: MIT licensed, actively maintained
2. **Laravel Integration**: Native framework compatibility

## Success Metrics

### Functional Requirements
- ✅ Create monthly subscriptions with weekly deliveries
- ✅ Handle fortnight Week A/B rotation automatically
- ✅ Process payments through existing FundsService
- ✅ Generate accurate delivery schedules
- ✅ Admin interface for subscription management
- ✅ Automated renewal processing
- ✅ Migration of all existing subscriptions

### Performance Requirements
- ✅ Subscription creation: <2 seconds
- ✅ Delivery schedule generation: <5 seconds
- ✅ Payment processing: <10 seconds
- ✅ Admin dashboard load: <3 seconds

### Reliability Requirements
- ✅ 99.9% uptime for subscription processing
- ✅ Zero data loss during migration
- ✅ All existing subscriptions migrated successfully
- ✅ Payment success rate >95%

## Resource Requirements

### Development Team
- **Lead Developer**: 8-10 weeks full-time
- **Code Review**: Weekly sessions
- **Testing**: Dedicated testing phase

### Infrastructure
- **Existing Server**: No additional hardware needed
- **Database**: Existing MySQL sufficient
- **Backup**: Enhanced backup procedures during migration

### Tools & Dependencies
- `laravelcm/laravel-subscriptions`: Core package
- Existing Laravel ecosystem
- Current testing frameworks
- Monitoring tools (already implemented)

## Timeline & Milestones

```
Week 1-2: Planning & Setup
├── Day 1-2: Monitoring setup
├── Day 3-4: Package installation
└── Day 5-10: Data model design

Week 3-6: Core Development
├── Week 3: Data models
├── Week 4-5: Scheduling system
└── Week 6: Payment integration

Week 7-8: Admin & Testing
├── Week 7: Admin interface
└── Week 8: Integration testing

Week 9-10: Migration & Launch
├── Week 9: Data migration
└── Week 10: Go-live & monitoring
```

## Communication Plan

### Weekly Updates
- Monday: Progress update and blockers
- Friday: Weekly summary and next week planning

### Key Stakeholders
- **Project Owner**: Daily status updates
- **Development Team**: Technical discussions
- **Operations**: Infrastructure and deployment coordination

### Documentation
- Daily progress logs
- Weekly milestone reports
- Technical architecture documentation
- User manuals for admin interface

## Contingency Plans

### Rollback Procedures
1. **Immediate Rollback**: Switch back to WooCommerce within 1 hour
2. **Data Recovery**: Restore from pre-migration backups
3. **Partial Rollback**: Keep new system for new subscriptions only

### Risk Triggers
- **Stop Development**: If critical bugs found in testing
- **Delay Launch**: If migration testing shows >5% failure rate
- **Scope Reduction**: Remove non-critical features if timeline slips

## Budget Breakdown

### Development Costs (Primary)
- **Senior Laravel Developer**: £400-600/day × 40-50 days = £16,000-30,000
- **Testing & QA**: £200-300/day × 10 days = £2,000-3,000
- **Project Management**: £300/day × 5 days = £1,500

### Infrastructure Costs (Minimal)
- **Additional Server Resources**: £0 (existing sufficient)
- **Backup Storage**: £50-100/month (temporary)
- **Monitoring Tools**: £0 (existing tools)

### Total Estimated Cost: £19,500-£33,600

*Note: Actual costs will be significantly lower as this is development time only. No software licenses or third-party services required.*

## Next Steps

1. **Week 1 Kickoff**: Set up daily monitoring and install package
2. **Week 2 Planning**: Finalize data models and integration approach
3. **Ongoing**: Weekly milestone reviews and adjustment as needed

---

**Document Version**: 1.0  
**Date**: November 4, 2025  
**Author**: AI Development Assistant  
**Approved By**: [Pending]