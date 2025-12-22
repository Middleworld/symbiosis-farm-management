#!/bin/bash

# Test script for WordPress subscription API endpoints
# Run this to verify Laravel API returns correct format

API_URL="https://admin.middleworldfarms.org:8444/api/subscriptions"
API_KEY="Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h"

echo "================================"
echo "Testing Laravel Subscription API"
echo "================================"
echo ""

# Test 1: Get user subscriptions (should return empty array if no subscriptions)
echo "Test 1: GET /api/subscriptions/user/1"
echo "--------------------------------------"
curl -s -X GET "${API_URL}/user/1" \
  -H "X-MWF-API-Key: ${API_KEY}" \
  -H "Content-Type: application/json" | jq '.'
echo ""
echo ""

# Test 2: Create subscription
echo "Test 2: POST /api/subscriptions (create)"
echo "--------------------------------------"
curl -s -X POST "${API_URL}" \
  -H "X-MWF-API-Key: ${API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "wordpress_user_id": 1,
    "wordpress_order_id": 99999,
    "product_id": 226082,
    "variation_id": 226085,
    "billing_period": "week",
    "billing_interval": 1,
    "billing_amount": 25.00,
    "delivery_day": "monday",
    "customer_email": "test@example.com",
    "payment_method": "stripe"
  }' | jq '.'
echo ""
echo ""

# Test 3: Get single subscription (use ID from test 2 or existing)
echo "Test 3: GET /api/subscriptions/1 (single subscription)"
echo "--------------------------------------"
curl -s -X GET "${API_URL}/1" \
  -H "X-MWF-API-Key: ${API_KEY}" \
  -H "Content-Type: application/json" | jq '.'
echo ""
echo ""

# Test 4: Test with invalid API key (should return 401)
echo "Test 4: Invalid API key (should fail)"
echo "--------------------------------------"
curl -s -X GET "${API_URL}/user/1" \
  -H "X-MWF-API-Key: wrong-key" \
  -H "Content-Type: application/json" | jq '.'
echo ""
echo ""

echo "================================"
echo "Tests Complete"
echo "================================"
echo ""
echo "✅ Check that responses match LARAVEL-API-FORMAT-REFERENCE.md"
echo "✅ All dates should be YYYY-MM-DD format"
echo "✅ billing_amount should be float (25.00 not \"25.00\")"
echo "✅ manage_url should be full URL"
echo ""
