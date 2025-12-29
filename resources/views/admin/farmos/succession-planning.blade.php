@extends('layouts.app')

@section('title', 'farmOS Succession Planner - Revolutionary Backward Planning')

@section('page-title', 'farmOS Succession Planner')

@section('header-hint')
Only needed if FarmOS varieties have changed
@endsection

@section('page-header')
    <div class="d-flex justify-content-between align-items-center w-100">
        <div>
            <button id="syncVarietiesBtn" class="btn btn-sm btn-light" onclick="syncFarmOSVarieties()" title="Sync varieties from FarmOS - Only needed if FarmOS varieties have changed">
                <i class="fas fa-sync-alt"></i> Sync
            </button>
        </div>
        <div class="text-center flex-grow-1">
            <p class="lead mb-0">Revolutionary backward planning from harvest windows • Real farmOS taxonomy • AI-powered intelligence</p>
        </div>
        <div></div>
    </div>
@endsection

@section('styles')
<!-- Force page to start at top -->
<style>
    html, body {
        scroll-behavior: auto !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Ensure page always starts at top */
    html {
        scroll-restoration: manual;
    }
</style>

<!-- Immediate scroll to top - runs before anything else -->
<script>
    // Force scroll to top IMMEDIATELY - before DOMContentLoaded
    if (history.scrollRestoration) {
        history.scrollRestoration = 'manual';
    }
    window.scrollTo(0, 0);
    if (document.documentElement) {
        document.documentElement.scrollTop = 0;
    }
    if (document.body) {
        document.body.scrollTop = 0;
    }
</script>

<!-- Timeline Visualization Styles -->

<!-- Sortable.js for drag and drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<!-- Succession Planner Module -->
<script src="{{ asset('js/succession-planner.js') }}?v={{ time() }}"></script>
<style>
    .succession-planner-container {
        padding: 20px;
    }

    .hero-section {
        background: linear-gradient(135deg, var(--primary-color, #28a745) 0%, var(--success-color, #198754) 100%);
        color: white;
        padding: 2rem;
        margin-bottom: 2rem;
        border-radius: 1rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .hero-section h1 {
        font-size: 2.5rem;
        font-weight: 300;
        margin-bottom: 0.5rem;
    }

    .hero-section .subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
    }

    .planning-card {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        border: none;
        margin-bottom: 2rem;
    }

    .planning-section {
        padding: 1.5rem;
        border-bottom: 1px solid #e9ecef;
    }

    .planning-section:last-child {
        border-bottom: none;
    }

    .planning-section h3 {
        color: #212529;
        font-size: 1.3rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .section-icon {
        color: #28a745;
    }

    .harvest-window {
        background: linear-gradient(45deg, #e3f2fd, #f3e5f5);
        border: 2px dashed #0dcaf0;
        border-radius: 1rem;
        padding: 1.5rem;
        margin: 1rem 0;
    }

    .farmos-timeline-container {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-top: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        min-height: 500px;
    }

    .farmos-timeline-iframe {
        width: 100%;
        height: 600px;
        border: none;
        border-radius: 0.5rem;
    }

    /* Timeline Visualization Styles */
    .timeline-visualization {
        width: 100%;
        position: relative;
        padding: 20px 0;
    }

    .timeline-axis {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0;
        padding: 10px 20px;
        border-bottom: 2px solid #dee2e6;
        position: sticky;
        top: 0;
        background: white;
        z-index: 100;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .timeline-axis::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, #28a745, #ffc107, #dc3545);
    }

    .timeline-month {
        text-align: center;
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
    }

    .timeline-tasks {
        position: relative;
        min-height: 300px;
    }

    .timeline-task {
        position: absolute;
        height: 40px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        padding: 0 10px;
        color: white;
        font-size: 0.85rem;
        font-weight: 500;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        cursor: pointer;
        transition: all 0.2s ease;
        border: 2px solid rgba(255,255,255,0.3);
    }

    .timeline-task:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    }

    .timeline-task.seeding {
        background: linear-gradient(135deg, #28a745, #20c997);
    }

    .timeline-task.transplanting {
        background: linear-gradient(135deg, #ffc107, #fd7e14);
    }

    .timeline-task.harvest {
        background: linear-gradient(135deg, #dc3545, #fd7e14);
    }

    .timeline-task.growth {
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.7), rgba(255, 193, 7, 0.7));
        border-style: dashed;
    }

    .timeline-legend {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
    }

    .legend-color {
        width: 16px;
        height: 16px;
        border-radius: 3px;
    }

    .harvest-window-selector {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border: 2px solid #dee2e6;
        border-radius: 15px;
        padding: 20px;
        margin: 20px 0;
    }

    .range-indicator {
        position: relative;
        margin-bottom: 15px;
        border-radius: 10px;
        overflow: hidden;
    }

    .range-indicator .progress {
        border-radius: 10px;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    }

    .range-indicator.max-range {
        background: linear-gradient(90deg, rgba(13, 202, 240, 0.1), rgba(13, 202, 240, 0.05));
        border: 1px solid rgba(13, 202, 240, 0.2);
    }

    .range-indicator.ai-range {
        background: linear-gradient(90deg, rgba(255, 193, 7, 0.1), rgba(255, 193, 7, 0.05));
        border: 1px solid rgba(255, 193, 7, 0.2);
    }

    .range-indicator.user-range {
        background: linear-gradient(90deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
        border: 1px solid rgba(40, 167, 69, 0.2);
    }

    .range-handle {
        position: absolute;
        top: 0;
        width: 20px;
        height: 100%;
        background: #28a745;
        cursor: ew-resize;
        border-radius: 3px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 12px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        transition: all 0.2s ease;
    }

    .range-handle:hover {
        background: #218838;
        transform: scale(1.1);
    }

    .range-handle.start {
        border-radius: 3px 0 0 3px;
    }

    .range-handle.end {
        border-radius: 0 3px 3px 0;
    }

    .calendar-month {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 10px;
        text-align: center;
        min-height: 80px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .calendar-month:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .calendar-month.optimal {
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
        border-color: rgba(40, 167, 69, 0.3);
    }

    .calendar-month.extended {
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 193, 7, 0.05));
        border-color: rgba(255, 193, 7, 0.3);
    }

    .calendar-month.selected {
        background: linear-gradient(135deg, rgba(0, 123, 255, 0.1), rgba(0, 123, 255, 0.05));
        border-color: rgba(0, 123, 255, 0.3);
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.2);
    }

    /* Weekly Calendar Styles */
    .month-header {
        margin-bottom: 10px;
        height: 100%;
    }

    .month-header h6 {
        font-size: 0.9rem;
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 8px;
    }

    .week-badge {
        position: relative;
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-width: 70px;
        padding: 8px 10px;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        background: white;
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }

    .week-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        border-color: #0d6efd;
    }

    .week-number {
        font-size: 0.85rem;
        font-weight: 600;
        color: #495057;
    }

    .week-dates {
        font-size: 0.7rem;
        color: #6c757d;
        margin-top: 2px;
    }

    .week-marker {
        position: absolute;
        top: -8px;
        font-size: 1.2rem;
        line-height: 1;
    }

    .week-badge.week-unavailable {
        background: #f8f9fa;
        border-color: #e9ecef;
        opacity: 0.5;
        cursor: not-allowed;
    }

    .week-badge.week-unavailable:hover {
        transform: none;
        box-shadow: none;
        border-color: #e9ecef;
    }

    .week-badge.week-optimal {
        background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(23, 162, 184, 0.05));
        border-color: rgba(23, 162, 184, 0.5);
    }

    .week-badge.week-extended {
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 193, 7, 0.05));
        border-color: rgba(255, 193, 7, 0.5);
    }

    .week-badge.week-selected {
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.2), rgba(40, 167, 69, 0.1));
        border-color: rgba(40, 167, 69, 0.8);
        box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.2);
    }

    .week-badge.week-selected .week-number {
        color: #28a745;
        font-weight: 700;
    }

    .week-badge.week-selecting {
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.2), rgba(13, 110, 253, 0.1));
        border-color: rgba(13, 110, 253, 0.8);
        animation: pulse 1s infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    .succession-preview {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-top: 10px;
    }

    .succession-item {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .succession-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .succession-label {
        font-weight: 600;
        color: #495057;
    }

    .succession-date {
        font-size: 0.875rem;
        font-weight: 500;
        color: #495057;
    }

    .method-badge {
        background: #f8f9fa;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.75rem;
        border: 1px solid #dee2e6;
    }
        color: #6c757d;
    }

    .harvest-window-info {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
        padding: 10px;
        margin-top: 10px;
        color: #495057;
    }

    .harvest-window-info.ai-calculated {
        background: linear-gradient(45deg, rgba(255, 193, 7, 0.1), rgba(255, 235, 59, 0.1));
        border-left: 3px solid #ffc107;
    }

    .ai-chat-section {
        background: linear-gradient(45deg, #fff3cd, #f0f9ff);
        border: 1px solid #ffc107;
        border-radius: 1rem;
        padding: 1.5rem;
        margin: 1rem 0;
    }

    .ai-chat-input {
        border: 2px solid #28a745;
        border-radius: 25px;
        padding: 12px 20px;
        resize: none;
    }

    .ai-chat-input:focus {
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        border-color: #198754;
    }

    .variety-info-section {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 1rem;
        padding: 1.5rem;
        border: 1px solid #dee2e6;
    }

    .variety-photo-container {
        min-height: 220px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .variety-photo {
        width: 100%;
        height: auto;
        border: 2px solid #dee2e6;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        object-fit: cover;
    }

    .variety-description {
        line-height: 1.5;
        font-style: italic;
    }

    .ai-response {
        background: rgba(40, 167, 69, 0.1);
        border-left: 4px solid #28a745;
        padding: 15px;
        border-radius: 0.5rem;
        margin: 15px 0;
        font-style: italic;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .status-connected {
        background-color: rgba(25, 135, 84, 0.1);
        color: #198754;
    }

    .status-disconnected {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    .status-light {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        animation: pulse 2s infinite;
    }

    .status-light.online {
        background-color: #28a745;
        box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
    }

    .status-light.offline {
        background-color: #dc3545;
        box-shadow: 0 0 10px rgba(220, 53, 69, 0.5);
        animation: none;
    }

    .status-light.checking {
        background-color: #ffc107;
        box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }

    .succession-card {
        border: 2px solid #e9ecef;
        border-radius: 0.5rem;
        padding: 1rem;
        margin: 0.5rem 0;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .succession-card:hover {
        border-color: #28a745;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
    }

    .succession-card.overdue {
        border-color: #dc3545;
        background-color: rgba(220, 53, 69, 0.05);
    }

    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        backdrop-filter: blur(3px);
    }

    .succession-tabs {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-top: 2rem;
    }

    .tab-navigation {
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        overflow-x: auto;
    }

    .tab-button {
        background: none;
        border: none;
        padding: 1rem 1.5rem;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease;
        white-space: nowrap;
        font-weight: 500;
        color: #6c757d;
    }

    .tab-button:hover {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }

    .tab-button.active {
        background: white;
        color: #28a745;
        border-bottom-color: #28a745;
    }

    .tab-button.completed {
        color: #198754;
    }

    .tab-button.completed::after {
        content: ' ✓';
        font-weight: bold;
    }

    .tab-content {
        min-height: 800px;
        background: white;
    }

    .tab-pane {
        display: none;
        padding: 2rem;
    }

    .tab-pane.active {
        display: block;
    }

    .quick-form-container {
        background: #f8f9fa;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .succession-info {
        background: linear-gradient(45deg, #e3f2fd, #f3e5f5);
        border: 2px dashed #0dcaf0;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .succession-info h5 {
        color: #0d6efd;
        margin-bottom: 0.5rem;
    }

    .succession-info p {
        margin-bottom: 0.25rem;
        color: #495057;
    }

    .quick-form-error {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        border-radius: 0.5rem;
        padding: 2rem;
        text-align: center;
        color: #721c24;
    }

    /* Thicker green border for auto-filled spacing values */
    input.border-success {
        border-width: 3px !important;
        border-color: #198754 !important;
    }

    .form-content {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-top: 1rem;
        max-height: 600px;
        overflow-y: auto;
    }

    .form-loading {
        color: #6c757d;
    }

    .tab-button.overdue {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    .tab-button.overdue.active {
        background: #dc3545;
        color: white;
    }

    /* Responsive design for mobile */
    @media (max-width: 768px) {
        .tab-navigation {
            flex-wrap: wrap;
        }

        .tab-button {
            flex: 1 1 auto;
            min-width: 120px;
            font-size: 0.9rem;
            padding: 0.75rem 1rem;
        }

        .succession-info {
            padding: 0.75rem;
        }
    }

    /* Bed Occupancy Timeline Styles */
    .bed-occupancy-timeline {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        margin: 20px 0;
    }

    .timeline-header {
        text-align: center;
        margin-bottom: 20px;
    }

    .timeline-header h5 {
        color: #28a745;
        margin-bottom: 5px;
    }

    .beds-container {
        position: relative;
        margin-top: 10px;
        padding-top: 10px;
    }

    .bed-row {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        min-height: 40px;
        position: relative;
    }

    .bed-block:not(:last-child) {
        margin-bottom: 30px;
        position: relative;
    }

    .bed-block:not(:last-child)::after {
        content: '';
        position: absolute;
        bottom: -15px;
        left: 0;
        right: 0;
        height: 6px;
        background: repeating-linear-gradient(
            90deg,
            #8B4513 0px,
            #8B4513 4px,
            #228B22 4px,
            #228B22 8px,
            #8B4513 8px,
            #8B4513 12px
        );
        border-radius: 3px;
        opacity: 0.8;
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }



    .block-timeline-header .timeline-month {
        font-size: 0.8rem;
        padding: 4px 0;
    }

    .bed-label {
        width: 120px;
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
        padding-right: 15px;
        text-align: right;
        flex-shrink: 0;
    }

    .bed-timeline {
        flex: 1;
        position: relative;
        height: 32px;
        background: #f8f9fa;
        border-radius: 6px;
        border: 1px solid #dee2e6;
        overflow: hidden;
        cursor: pointer;
    }

    .bed-occupancy-block {
        position: absolute;
        height: 100%;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 500;
        color: white;
        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        border: 1px solid rgba(255,255,255,0.3);
        transition: all 0.2s ease;
        cursor: pointer;
        pointer-events: auto; /* Changed to allow hover */
    }

    .bed-occupancy-block:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        z-index: 100; /* Bring to front on hover */
    }
    
    .bed-occupancy-block:hover .bed-tooltip {
        display: block;
    }

    .bed-tooltip {
        display: none;
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: #333;
        color: white;
        padding: 10px 12px;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        white-space: nowrap;
        z-index: 1000;
        margin-bottom: 8px;
        font-size: 0.85rem;
        line-height: 1.5;
        text-align: left;
        min-width: 220px;
    }
    
    .bed-tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 6px solid transparent;
        border-top-color: #333;
    }
    
    .tooltip-title {
        font-weight: bold;
        margin-bottom: 6px;
        font-size: 0.95rem;
        border-bottom: 1px solid rgba(255,255,255,0.2);
        padding-bottom: 4px;
    }
    
    .tooltip-dates {
        margin: 6px 0;
        font-size: 0.8rem;
        color: #e0e0e0;
    }
    
    .tooltip-status {
        margin-top: 6px;
        font-size: 0.8rem;
        color: #4ade80;
    }
    
    .tooltip-notes {
        margin-top: 8px;
        padding-top: 6px;
        border-top: 1px solid rgba(255,255,255,0.2);
        font-size: 0.75rem;
        font-style: italic;
        color: #d0d0d0;
        white-space: normal;
        max-width: 250px;
    }

    .bed-occupancy-block.active {
        background: linear-gradient(135deg, #28a745, #20c997);
    }

    .bed-occupancy-block.completed {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        opacity: 0.8;
    }

    .bed-occupancy-block.available {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border: 2px dashed #dee2e6;
    }

    .crop-label {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        padding: 0 4px;
    }

    .timeline-indicators {
        position: absolute;
        top: 0;
        left: 120px;
        right: 0;
        height: 100%;
        pointer-events: none;
    }

    .succession-indicator {
        position: absolute;
        top: -8px;
        transform: translateX(-50%);
        z-index: 10;
        animation: pulse 2s infinite;
    }

    .succession-indicator i {
        font-size: 16px;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
    }

    @keyframes pulse {
        0% { transform: translateX(-50%) scale(1); }
        50% { transform: translateX(-50%) scale(1.2); }
        100% { transform: translateX(-50%) scale(1); }
    }

    .timeline-legend .legend-color.active {
        background: linear-gradient(135deg, #28a745, #20c997);
    }

    .timeline-legend .legend-color.completed {
        background: linear-gradient(135deg, #6c757d, #5a6268);
    }

    .timeline-legend .legend-color.available {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border: 1px dashed #dee2e6;
    }

    /* Block grouping styles */
    .bed-block {
        margin-bottom: 25px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        overflow: hidden;
    }

    .bed-block:last-child {
        margin-bottom: 0;
    }

    .bed-block-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        padding: 12px 15px;
        border-bottom: 1px solid #dee2e6;
    }

    .bed-block-header h6 {
        margin: 0;
        color: #495057;
        font-weight: 600;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 4px;
        flex-wrap: nowrap;
        white-space: nowrap;
    }

    .hedgerow-icon {
        color: #28a745;
        font-size: 1.1em;
        margin: 0 2px;
    }

    .bed-block-header i {
        color: #6c757d;
        margin-right: 8px;
    }

    .hedgerow-indicator {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.8em;
        color: #6c757d;
    }

    .hedgerow-indicator i {
        font-size: 0.9em;
    }

    .hedgerow-divider {
        margin: 25px 0;
        padding: 0;
        height: 80px;
        background-image: url('/hedgerow.png');
        background-repeat: repeat-x;
        background-position: center center;
        background-size: auto 80px;
        position: relative;
        overflow: hidden;
        border-top: 2px solid #c3e6cb;
        border-bottom: 2px solid #c3e6cb;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .hedgerow-visual {
        display: none; /* Hide the icon-based visual, we're using the image now */
    }

    .hedgerow-tree {
        color: #28a745;
        font-size: 1.2em;
        margin: 0 3px;
    }

    /* Responsive adjustments for hedgerow icons */
    @media (max-width: 768px) {
        .hedgerow-icon {
            font-size: 1em;
            margin: 0 1px;
        }

        .hedgerow-tree {
            font-size: 1.1em;
            margin: 0 2px;
        }

        .bed-block-header h6 {
            gap: 2px;
            font-size: 0.9rem;
        }

        .hedgerow-visual {
            gap: 6px;
            padding: 6px 12px;
            font-size: 0.8em;
        }
    }

    @media (min-width: 1200px) {
        .hedgerow-icon {
            font-size: 1.3em;
            margin: 0 3px;
        }

        .hedgerow-tree {
            font-size: 1.4em;
            margin: 0 4px;
        }

        .bed-block-header h6 {
            gap: 6px;
        }

        .hedgerow-visual {
            gap: 12px;
            padding: 10px 20px;
        }
    }

    .hedgerow-visual i {
        color: #28a745;
        font-size: 1em;
    }

    .hedgerow-text {
        font-weight: 500;
        letter-spacing: 0.5px;
    }

    .bed-block-content {
        padding: 15px;
    }

    .bed-block-content .bed-row {
        margin-bottom: 12px;
    }

    .bed-block-content .bed-row:last-child {
        margin-bottom: 0;
    }

    /* Succession Planning Sidebar Styles */
    .succession-list {
        max-height: 400px;
        overflow-y: auto;
    }

    .succession-item {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 8px;
        cursor: grab;
        transition: all 0.2s ease;
        position: relative;
    }

    .succession-item:hover {
        border-color: #28a745;
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
        transform: translateY(-1px);
    }

    .succession-item.dragging {
        opacity: 0.2 !important;
        transform: rotate(3deg) scale(0.95);
        cursor: grabbing;
        background: #6c757d !important;
        border: 2px dashed #495057 !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
        filter: grayscale(100%) brightness(0.8);
    }

    .succession-item.dragging .succession-title {
        color: #adb5bd !important;
        text-decoration: line-through;
    }

    .succession-item.dragging .succession-dates {
        opacity: 0.5;
    }

    .succession-item.allocated {
        opacity: 0.6 !important;
        background: #d6d8db !important;
        border: 2px dashed #adb5bd !important;
        pointer-events: none !important;
    }

    .succession-item.allocated .succession-title {
        color: #6c757d !important;
        text-decoration: line-through;
    }
    
    .succession-item.allocated .succession-dates {
        opacity: 0.6;
    }

    .bed-allocation-badge {
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        margin-left: 8px;
        padding: 4px 10px !important;
        border-radius: 12px;
        transition: all 0.2s ease;
        pointer-events: auto !important;
        white-space: nowrap;
    }

    .bed-allocation-badge:hover {
        background-color: #dc3545 !important;
        transform: scale(1.05);
    }

    .succession-item .succession-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 8px;
        position: relative;
    }

    .succession-item .succession-title-section {
        flex: 1;
    }

    .succession-item .succession-title {
        font-weight: 600;
        color: #28a745;
        font-size: 0.9rem;
    }

    .succession-item .succession-dates {
        font-size: 0.8rem;
        color: #6c757d;
    }

    .succession-item .succession-dates .date-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2px;
    }

    .succession-item .succession-dates .date-label {
        font-weight: 500;
    }

    .succession-item .succession-dates .date-value {
        color: #495057;
    }

    /* Bed drop zones */
    .bed-timeline.drop-target {
        background: linear-gradient(45deg, #d4edda, #f8f9fa) !important;
        border: 3px dashed #28a745 !important;
        cursor: copy;
        box-shadow: inset 0 0 10px rgba(40, 167, 69, 0.3);
        transform: scale(1.02);
        transition: all 0.2s ease;
    }

    .bed-timeline.drop-active {
        background: linear-gradient(45deg, #28a745, #20c997) !important;
        border: 3px solid #28a745 !important;
        cursor: copy;
        box-shadow: inset 0 0 15px rgba(40, 167, 69, 0.5), 0 0 20px rgba(40, 167, 69, 0.3);
        transform: scale(1.05);
        animation: pulse 1.5s infinite;
    }

    .bed-timeline.drop-conflict {
        background: linear-gradient(45deg, #f8d7da, #f5c6cb) !important;
        border: 3px solid #dc3545 !important;
        cursor: not-allowed;
        box-shadow: inset 0 0 15px rgba(220, 53, 69, 0.5);
        transform: scale(1.02);
    }

    @keyframes pulse {
        0% { box-shadow: inset 0 0 15px rgba(40, 167, 69, 0.5), 0 0 20px rgba(40, 167, 69, 0.3); }
        50% { box-shadow: inset 0 0 20px rgba(40, 167, 69, 0.7), 0 0 30px rgba(40, 167, 69, 0.5); }
        100% { box-shadow: inset 0 0 15px rgba(40, 167, 69, 0.5), 0 0 20px rgba(40, 167, 69, 0.3); }
    }

    /* Drag preview indicator */
    .drag-preview {
        position: absolute;
        top: 0;
        height: 100%;
        background: rgba(40, 167, 69, 0.3);
        border: 2px solid #28a745;
        border-radius: 4px;
        pointer-events: none;
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: preview-pulse 1s infinite;
    }

    .drag-preview-content {
        background: rgba(255, 255, 255, 0.9);
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 0.75rem;
        color: #28a745;
        font-weight: 600;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    @keyframes preview-pulse {
        0% { background: rgba(40, 167, 69, 0.3); }
        50% { background: rgba(40, 167, 69, 0.5); }
        100% { background: rgba(40, 167, 69, 0.3); }
    }

    /* Succession allocation blocks */
    .succession-block-container {
        position: absolute;
        height: 100%;
        display: flex;
        cursor: move;
        transition: all 0.2s ease;
        z-index: 5;
    }

    .succession-growing-block {
        background: linear-gradient(135deg, #17a2b8, #138496);
        border-radius: 4px 0 0 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        border: 2px solid rgba(255,255,255,0.3);
        transition: all 0.2s ease;
    }

    .succession-harvest-block {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        border-radius: 0 4px 4px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 600;
        color: #212529;
        text-shadow: 0 1px 2px rgba(255,255,255,0.5);
        border: 2px solid rgba(255,255,255,0.5);
        transition: all 0.2s ease;
    }

    .succession-growing-block:hover,
    .succession-harvest-block:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }

    .succession-block-container.dragging .succession-growing-block,
    .succession-block-container.dragging .succession-harvest-block {
        opacity: 0.7;
        transform: rotate(2deg) scale(1.05);
    }

    .succession-allocation-block .succession-label {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        padding: 0 2px;
    }

    /* Conflict error states */
    .bed-timeline.conflict-error {
        background: linear-gradient(45deg, #f8d7da, #f5c6cb);
        border: 2px solid #dc3545 !important;
        animation: conflict-pulse 0.5s ease-in-out;
    }

    .conflict-message {
        position: absolute;
        top: -30px;
        left: 50%;
        transform: translateX(-50%);
        background: #dc3545;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .conflict-message::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 4px solid transparent;
        border-top-color: #dc3545;
    }

    @keyframes conflict-pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
    }

    /* Enhanced drop zone feedback */
    .bed-timeline.drop-target {
        background: linear-gradient(45deg, #d1ecf1, #bee5eb);
        border: 2px dashed #17a2b8 !important;
        transition: all 0.2s ease;
    }

    .bed-timeline.drop-active {
        background: linear-gradient(45deg, #17a2b8, #138496);
        border: 2px solid #17a2b8 !important;
        transform: scale(1.01);
    }

    .bed-timeline.drop-active::before {
        content: '📍 Drop succession here';
        position: absolute;
        top: -25px;
        left: 50%;
        transform: translateX(-50%);
        background: #17a2b8;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .bed-timeline.drop-active::after {
        content: '';
        position: absolute;
        top: -21px;
        left: 50%;
        transform: translateX(-50%);
        border: 4px solid transparent;
        border-top-color: #17a2b8;
        z-index: 10;
    }

    /* Conflict drop state */
    .bed-timeline.drop-conflict {
        background: linear-gradient(45deg, #f8d7da, #f5c6cb);
        border: 2px dashed #dc3545 !important;
        cursor: not-allowed;
    }

    .bed-timeline.drop-conflict::before {
        content: '🚫 Bed occupied';
        position: absolute;
        top: -25px;
        left: 50%;
        transform: translateX(-50%);
        background: #dc3545;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .bed-timeline.drop-conflict::after {
        content: '';
        position: absolute;
        top: -21px;
        left: 50%;
        transform: translateX(-50%);
        border: 4px solid transparent;
        border-top-color: #dc3545;
        z-index: 10;
    }
</style>
@endsection

@section('content')
<div class="succession-planner-container">
    <!-- Cache buster for development -->
    <script>console.log('🔄 Cache buster: 1756750327-FIXED-' + Date.now() + ' - VISUAL TIMELINE - FarmOS succession planner');</script>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <!-- Loading overlay -->
    <div class="loading-overlay d-none" id="loadingOverlay">
        <div class="text-center">
            <div class="spinner-grow text-success" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Processing with Holistic AI...</p>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Planning Interface and Timeline -->
        <div class="col-lg-8">
            <!-- Season/Year Selection -->
            <div class="planning-card mb-3">
                <div class="planning-section">
                    <h3>
                        <i class="fas fa-calendar section-icon"></i>
                        Planning Season & Year
                    </h3>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="planningYear" class="form-label">Planning Year</label>
                            <select class="form-select" id="planningYear" name="planningYear">
                                <option value="2024" {{ date('Y') == '2024' ? 'selected' : '' }}>2024</option>
                                <option value="2025" {{ date('Y') == '2025' ? 'selected' : '' }}>2025</option>
                                <option value="2026" {{ date('Y') == '2026' ? 'selected' : '' }}>2026</option>
                                <option value="2027" {{ date('Y') == '2027' ? 'selected' : '' }}>2027</option>
                                <option value="2028" {{ date('Y') == '2028' ? 'selected' : '' }}>2028</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="planningSeason" class="form-label">Primary Season</label>
                            <select class="form-select" id="planningSeason" name="planningSeason">
                                <option value="spring">Spring (Mar-May)</option>
                                <option value="summer">Summer (Jun-Aug)</option>
                                <option value="fall" selected>Fall (Sep-Nov)</option>
                                <option value="winter">Winter (Dec-Feb)</option>
                                <option value="year-round">Year-Round Planning</option>
                            </select>
                        </div>
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        This sets the timeline view and helps AI provide season-appropriate succession planning advice.
                    </small>
                </div>
            </div>

            <div class="planning-card">
                <!-- Step 1: Crop Selection from farmOS -->
                <div class="planning-section">
                    <h3>
                        <i class="fas fa-leaf section-icon"></i>
                        Choose Your Crop from farmOS Taxonomy
                    </h3>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="cropSelect" class="form-label">Crop Type</label>
                            <select class="form-select" id="cropSelect" name="cropSelect" required>
                                <option value="">Select a crop type...</option>
                                @if(isset($cropData['types']) && count($cropData['types']) > 0)
                                    @foreach($cropData['types'] as $crop)
                                        <option value="{{ $crop['id'] }}" data-name="{{ $crop['name'] }}">
                                            {{ $crop['name'] }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="varietySelect" class="form-label">Variety</label>
                            <select class="form-select" id="varietySelect" name="varietySelect">
                                <option value="">Select a variety...</option>
                                @if(isset($cropData['varieties']) && count($cropData['varieties']) > 0)
                                    @foreach($cropData['varieties'] as $variety)
                                        <option value="{{ $variety['id'] }}" data-crop="{{ $variety['parent_id'] ?? '' }}" data-name="{{ $variety['name'] }}">
                                            {{ $variety['name'] }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    
                    <!-- Varietal Succession Option - Enhanced & Prominent -->
                    <div class="row mt-4" id="varietalSuccessionSection" style="display:none;">
                        <div class="col-12">
                            <div class="card border-success shadow-sm">
                                <div class="card-header bg-success bg-opacity-10 border-success">
                                    <h6 class="mb-0 text-success">
                                        <i class="fas fa-calendar-alt"></i> Extended Season Option
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="useVarietalSuccession" 
                                               onchange="toggleVarietalSuccession()" style="width: 3em; height: 1.5em; cursor: pointer;">
                                        <label class="form-check-label ms-2" for="useVarietalSuccession" style="cursor: pointer;">
                                            <strong class="fs-5 text-success">Use Varietal Succession</strong>
                                        </label>
                                    </div>
                                    <div class="alert alert-success mb-0 py-2">
                                        <small>
                                            <i class="fas fa-info-circle"></i> 
                                            <strong>What is this?</strong> Plant different varieties (early, mid, late season) to extend your harvest window. 
                                            <strong>Highly recommended for harvests longer than 90 days!</strong>
                                        </small>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-lightbulb text-warning"></i> 
                                            <strong>Why use it?</strong> Each variety is optimized for its growing period, giving you continuous harvests 
                                            instead of everything maturing at once.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Varietal Succession Variety Selectors - 3 VARIETY VERSION -->
                    <div class="row mt-3" id="varietalSuccessionVarieties" style="display:none;">
                        <div class="col-12">
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-lightbulb"></i> <strong>Varietal Succession:</strong> Select early, mid, and late varieties to extend your harvest season. Each variety is optimized for its growing period!
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="earlyVarietySelect" class="form-label">
                                <i class="fas fa-seedling text-success"></i> Early Variety
                                <small class="text-muted d-block">Fast maturity, early harvest</small>
                            </label>
                            <select class="form-select" id="earlyVarietySelect" name="earlyVarietySelect">
                                <option value="">Select early variety...</option>
                            </select>
                            <div class="mt-2">
                                <label for="earlyBedsCount" class="form-label small">
                                    <i class="fas fa-bed"></i> Beds/Successions
                                </label>
                                <input type="number" class="form-control form-control-sm" id="earlyBedsCount" name="earlyBedsCount" value="1" min="1" max="10" placeholder="1">
                                <small class="text-muted">How many beds for this variety?</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="midVarietySelect" class="form-label">
                                <i class="fas fa-leaf text-primary"></i> Mid Variety
                                <small class="text-muted d-block">Medium maturity, main season</small>
                            </label>
                            <select class="form-select" id="midVarietySelect" name="midVarietySelect">
                                <option value="">Select mid variety...</option>
                            </select>
                            <div class="mt-2">
                                <label for="midBedsCount" class="form-label small">
                                    <i class="fas fa-bed"></i> Beds/Successions
                                </label>
                                <input type="number" class="form-control form-control-sm" id="midBedsCount" name="midBedsCount" value="1" min="1" max="10" placeholder="1">
                                <small class="text-muted">How many beds for this variety?</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="lateVarietySelect" class="form-label">
                                <i class="fas fa-tree text-warning"></i> Late Variety
                                <small class="text-muted d-block">Slow maturity, extended harvest</small>
                            </label>
                            <select class="form-select" id="lateVarietySelect" name="lateVarietySelect">
                                <option value="">Select late variety...</option>
                            </select>
                            <div class="mt-2">
                                <label for="lateBedsCount" class="form-label small">
                                    <i class="fas fa-bed"></i> Beds/Successions
                                </label>
                                <input type="number" class="form-control form-control-sm" id="lateBedsCount" name="lateBedsCount" value="1" min="1" max="10" placeholder="1">
                                <small class="text-muted">How many beds for this variety?</small>
                            </div>
                        </div>
                        
                        <!-- Total Successions Summary -->
                        <div class="col-12 mt-3">
                            <div class="alert alert-success mb-0 d-flex justify-content-between align-items-center" id="varietalSuccessionSummary">
                                <div>
                                    <i class="fas fa-calculator"></i> <strong>Total Successions:</strong> 
                                    <span id="totalSuccessionsDisplay">3</span> beds 
                                    (<span id="earlyCountDisplay">1</span> early + 
                                    <span id="midCountDisplay">1</span> mid + 
                                    <span id="lateCountDisplay">1</span> late)
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" id="recalculateVarietalBtn" onclick="recalculateVarietalSuccession()">
                                    <i class="fas fa-sync-alt"></i> Recalculate Plan
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Varietal Succession Variety Selectors - 2 VARIETY VERSION (for Broad Beans, etc.) -->
                    <div class="row mt-3" id="varietalSuccessionVarieties2V" style="display:none;">
                        <div class="col-12">
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-lightbulb"></i> <strong>2-Season Varietal Succession:</strong> This crop has 2 planting seasons. Select varieties for autumn/winter and spring planting!
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="earlyVarietySelect2V" class="form-label">
                                <i class="fas fa-leaf-maple text-warning"></i> Autumn/Winter Variety
                                <small class="text-muted d-block">Overwinter for early spring harvest</small>
                            </label>
                            <select class="form-select" id="earlyVarietySelect2V" name="earlyVarietySelect2V">
                                <option value="">Select autumn/winter variety...</option>
                            </select>
                            <div class="mt-2">
                                <label for="earlyBedsCount2V" class="form-label small">
                                    <i class="fas fa-bed"></i> Beds/Successions
                                </label>
                                <input type="number" class="form-control form-control-sm" id="earlyBedsCount2V" name="earlyBedsCount2V" value="1" min="1" max="10" placeholder="1">
                                <small class="text-muted">How many beds for this variety?</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="midVarietySelect2V" class="form-label">
                                <i class="fas fa-seedling text-success"></i> Spring Variety
                                <small class="text-muted d-block">Spring sowing for summer harvest</small>
                            </label>
                            <select class="form-select" id="midVarietySelect2V" name="midVarietySelect2V">
                                <option value="">Select spring variety...</option>
                            </select>
                            <div class="mt-2">
                                <label for="midBedsCount2V" class="form-label small">
                                    <i class="fas fa-bed"></i> Beds/Successions
                                </label>
                                <input type="number" class="form-control form-control-sm" id="midBedsCount2V" name="midBedsCount2V" value="1" min="1" max="10" placeholder="1">
                                <small class="text-muted">How many beds for this variety?</small>
                            </div>
                        </div>
                        
                        <!-- Total Successions Summary for 2V -->
                        <div class="col-12 mt-3">
                            <div class="alert alert-success mb-0 d-flex justify-content-between align-items-center" id="varietalSuccessionSummary2V">
                                <div>
                                    <i class="fas fa-calculator"></i> <strong>Total Successions:</strong> 
                                    <span id="totalSuccessionsDisplay2V">2</span> beds 
                                    (<span id="earlyCountDisplay2V">1</span> autumn/winter + 
                                    <span id="midCountDisplay2V">1</span> spring)
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" id="recalculateVarietalBtn2V" onclick="recalculateVarietalSuccession2V()">
                                    <i class="fas fa-sync-alt"></i> Recalculate Plan
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Planting Method Selection -->
                    <div class="row mt-3" id="plantingMethodRow" style="display: none;">
                        <div class="col-md-12">
                            <label class="form-label">
                                <i class="fas fa-seedling text-primary"></i>
                                Planting Method
                            </label>
                            <div class="btn-group w-100" role="group" aria-label="Planting method">
                                <input type="radio" class="btn-check" name="plantingMethod" id="methodDirectSow" value="direct" autocomplete="off">
                                <label class="btn btn-outline-success" for="methodDirectSow">
                                    <i class="fas fa-hand-holding-seedling"></i> Direct Sow
                                </label>
                                
                                <input type="radio" class="btn-check" name="plantingMethod" id="methodTransplant" value="transplant" autocomplete="off">
                                <label class="btn btn-outline-primary" for="methodTransplant">
                                    <i class="fas fa-seedling"></i> Transplant
                                </label>
                                
                                <input type="radio" class="btn-check" name="plantingMethod" id="methodEither" value="either" autocomplete="off" checked>
                                <label class="btn btn-outline-info" for="methodEither">
                                    <i class="fas fa-exchange-alt"></i> Either (Auto)
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1">
                                <i class="fas fa-info-circle"></i> 
                                <span id="plantingMethodHint">This affects timing, quantities, and succession dates. Choose based on your growing setup.</span>
                            </small>
                            
                            <!-- Method Warning Alert -->
                            <div id="plantingMethodWarning" class="alert alert-warning py-2 px-3 mt-2" style="display: none; font-size: 0.85rem;">
                                <!-- Warning message will appear here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Bed Dimensions & Plant Spacing -->
                <div class="planning-section">
                    <h3>
                        <i class="fas fa-ruler-combined section-icon"></i>
                        Bed Dimensions & Plant Spacing
                    </h3>
                    <p class="text-muted mb-3">
                        <i class="fas fa-info-circle"></i> Define your bed size and how densely you want to plant. 
                        These values calculate seed/transplant quantities.
                    </p>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="bedLength" class="form-label">
                                <i class="fas fa-ruler-horizontal text-info"></i>
                                Bed Length (meters)
                            </label>
                            <input type="number" class="form-control" id="bedLength" name="bedLength" placeholder="e.g., 11" min="1" step="0.1">
                        </div>
                        <div class="col-md-6">
                            <label for="bedWidth" class="form-label">
                                <i class="fas fa-ruler-vertical text-info"></i>
                                Bed Width (meters)
                            </label>
                            <input type="number" class="form-control" id="bedWidth" name="bedWidth" placeholder="e.g., 0.75" min="0.1" step="0.01">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="inRowSpacing" class="form-label">
                                <i class="fas fa-arrows-alt-h text-success"></i>
                                In-Row Spacing (cm)
                                <small class="text-muted d-block">Distance between plants in a row</small>
                            </label>
                            <input type="number" class="form-control" id="inRowSpacing" name="inRowSpacing" placeholder="e.g., 15" min="1" step="1" value="15">
                        </div>
                        <div class="col-md-6">
                            <label for="betweenRowSpacing" class="form-label">
                                <i class="fas fa-arrows-alt-v text-success"></i>
                                Between-Row Spacing (cm)
                                <small class="text-muted d-block">Distance between rows</small>
                            </label>
                            <input type="number" class="form-control" id="betweenRowSpacing" name="betweenRowSpacing" placeholder="e.g., 20" min="1" step="1" value="20">
                        </div>
                    </div>

                        <!-- Density Preset Selector for Brassicas -->
                        <div id="brassicaDensityPreset" class="alert alert-info mb-3" style="display: none;">
                            <h6 class="mb-2">
                                <i class="fas fa-layer-group"></i> 
                                Quick Row Spacing for Your <span id="densityBedWidth">75</span>cm Bed
                            </h6>
                            <p class="small mb-2 text-muted">
                                <i class="fas fa-info-circle"></i> Choose how many rows to fit across your bed width
                            </p>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-primary density-preset" data-between-row="45">
                                    <strong id="preset2rowsLabel">2 Rows</strong><br>
                                    <small>45cm between rows</small>
                                </button>
                                <button type="button" class="btn btn-outline-primary density-preset" data-between-row="30">
                                    <strong id="preset3rowsLabel">3 Rows</strong><br>
                                    <small>30cm between rows</small>
                                </button>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="fas fa-lightbulb"></i> 
                                These presets adjust the "Between-Row Spacing" below. 45cm = conservative spacing (default), 30cm = dense planting.
                            </small>
                        </div>
                    </div>

                <!-- Harvest Window Section - Hidden until variety selected -->
                <div id="harvestWindowSection" class="card shadow-sm mt-4" style="display: none;">
                    <div class="card-body bg-light">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-calendar-check text-success"></i>
                            Harvest Window Planning
                        </h5>
                        
                        <!-- Selected Harvest Window Display -->
                        <div id="selectedHarvestWindowDisplay" class="alert alert-success mb-3" style="display: none;">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Harvest Start Date</small>
                                    <strong id="displayHarvestStart">Not selected</strong>
                                    <input type="hidden" id="harvestStart" name="harvestStart">
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Harvest End Date</small>
                                    <strong id="displayHarvestEnd">Not selected</strong>
                                    <input type="hidden" id="harvestEnd" name="harvestEnd">
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle"></i> Click weeks on calendar below to change your harvest window
                            </small>
                        </div>
                        
                        <!-- Quick Adjust Buttons -->
                        <div class="mt-3 d-flex gap-2 justify-content-center flex-wrap">
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="useMaximumHarvestWindow()" title="Use the full possible harvest window for this crop">
                                <i class="fas fa-expand"></i> Use Maximum
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="useAIRecommendedWindow()" title="Use AI's recommended 80% window">
                                <i class="fas fa-brain"></i> Use AI Recommended
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="useConservativeWindow()" title="Use a shorter, safer harvest window">
                                <i class="fas fa-shield-alt"></i> Conservative
                            </button>
                        </div>
                        
                        <!-- Dynamic Succession Count Display -->
                            <div id="dynamicSuccessionDisplay" class="mt-3 p-3 bg-success bg-opacity-10 border border-success rounded" style="display: none;">
                                <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                                    <i class="fas fa-seedling text-success"></i>
                                    <strong class="text-success">Successions Needed:</strong>
                                    <span id="successionCount" class="badge bg-success fs-6">0</span>
                                </div>
                                
                                <!-- Succession Interval Selector -->
                                <div class="mb-3">
                                    <label for="successionIntervalSelect" class="form-label small fw-bold">
                                        <i class="fas fa-calendar-week"></i> Time Between Successions:
                                    </label>
                                    <select id="successionIntervalSelect" class="form-select form-select-sm" onchange="updateSuccessionFromInterval()">
                                        <option value="7">Every Week (7 days)</option>
                                        <option value="10">10 Days</option>
                                        <option value="14" selected>Every 2 Weeks (14 days)</option>
                                        <option value="21">Every 3 Weeks (21 days)</option>
                                        <option value="28">Every 4 Weeks (28 days)</option>
                                        <option value="35">Every 5 Weeks (35 days)</option>
                                        <option value="42">Every 6 Weeks (42 days)</option>
                                        <option value="56">Every 8 Weeks (56 days)</option>
                                        <option value="custom">Custom...</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">
                                        Auto-populated from crop database. Adjust as needed.
                                    </small>
                                </div>
                                
                                <!-- Custom Interval Input (hidden by default) -->
                                <div id="customIntervalInput" class="mb-3" style="display: none;">
                                    <label for="customIntervalDays" class="form-label small fw-bold">Custom Interval (days):</label>
                                    <input type="number" id="customIntervalDays" class="form-control form-control-sm" min="1" max="365" value="21" onchange="updateSuccessionFromCustomInterval()">
                                </div>
                                
                                <!-- Succession Interval Display -->
                                <div class="alert alert-info py-2 mb-2" role="alert">
                                    <small id="successionIntervalDisplay" class="mb-0">
                                        <i class="fas fa-info-circle"></i> Planting interval: ~3 weeks
                                    </small>
                                </div>
                                
                                <!-- Manual Adjusters (secondary option) -->
                                <div class="mt-2">
                                    <small class="text-muted d-block mb-2">Or manually adjust:</small>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="adjustSuccessionCount(-1)" title="Remove one succession">
                                            <i class="fas fa-minus"></i> Remove 1
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="adjustSuccessionCount(1)" title="Add one succession">
                                            <i class="fas fa-plus"></i> Add 1
                                        </button>
                                    </div>
                                </div>
                                
                                <small class="text-muted d-block mt-2">
                                    Quick forms auto-update when you adjust settings
                                </small>
                                
                                <!-- Crop-Specific Guidance -->
                                <div id="cropGuidanceDisplay" class="mt-2">
                                    <!-- Guidance tips will be displayed here -->
                                </div>
                            </div>

                            <!-- Calendar Grid View -->
                            <div id="harvestCalendar" class="mt-4" style="display: none;">
                                <h6 class="text-center mb-3">
                                    <i class="fas fa-calendar-alt text-primary"></i>
                                    Harvest Calendar Overview
                                </h6>
                                <div class="row g-2" id="calendarGrid">
                                    <!-- Calendar weeks will be populated here -->
                                </div>
                                <div class="mt-3 text-center">
                                    <small class="text-muted">
                                        <strong>Click weeks to select harvest window:</strong><br>
                                        <span class="badge bg-info me-2">AI Optimal</span>
                                        <span class="badge bg-warning me-2">Extended</span>
                                        <span class="badge bg-success me-2">Selected</span>
                                        <span class="badge bg-secondary">Unavailable</span>
                                    </small>
                                    <div class="mt-2">
                                        <small class="text-muted"><em>Click first week for start 🌱, click second week for end 🏁</em></small>
                                    </div>
                                </div>
                            </div>
                    </div>
                </div> <!-- End harvestWindowSection card -->

            <!-- Results Section -->
            <div id="resultsSection" style="display: none;">
                <!-- FarmOS Timeline Chart -->
            <div class="farmos-timeline-container">
                <h4><i class="fas fa-chart-gantt text-success"></i> FarmOS Succession Timeline</h4>
                <p class="text-muted">Interactive Gantt chart showing planting dates and harvest windows from FarmOS</p>
                <div id="farmosTimelineContainer">
                    <div class="text-center p-5 bg-light rounded">
                        <i class="fas fa-calendar-week text-muted" style="font-size: 3rem;"></i>
                        <p class="mt-3 text-muted">
                            <strong>Select harvest dates above to generate timeline</strong><br>
                            <small>Click two weeks on the harvest calendar to set your harvest window</small>
                        </p>
                    </div>
                </div>
            </div> <!-- End farmos-timeline-container -->
            </div> <!-- End resultsSection -->

                <!-- Quick Forms Tabs - This replaces the old summary cards -->
                <div id="quickFormTabsContainer" class="succession-tabs" style="display: none;">
                    <div class="text-center p-5 bg-light rounded" id="quickFormsPlaceholder">
                        <i class="fas fa-clipboard-list text-muted" style="font-size: 3rem;"></i>
                        <p class="mt-3 text-muted">
                            <strong>Quick Forms will appear here</strong><br>
                            <small>After selecting harvest dates, you'll see forms for each succession planting</small>
                        </p>
                    </div>
                    <div class="tab-navigation" id="tabNavigation">
                        <!-- Tab buttons will be populated here -->
                    </div>
                    <div class="tab-content" id="tabContent">
                        <!-- Tab panes will be populated here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Succession Planning Sidebar -->
        <div class="col-lg-4">
            <!-- AI Chat Integration -->
            <div id="aiChatSection" class="planning-card">
                <div class="planning-section">
                    <h3>
                        <span class="text-success fw-bold" style="font-size: 1rem;">Symbi<i class="fas fa-robot section-icon" style="font-size: 1.2rem;"></i>sis</span>
                        AI Succession Advisor
                    </h3>
                    
                    <!-- AI Status -->
                    <div class="mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div id="aiStatusLight" class="status-light" title="AI Service Status"></div>
                            <small id="aiStatusText" class="text-muted">Checking AI...</small>
                        </div>
                    </div>

                    <!-- Chat Messages Area -->
                    <div id="chatMessages" class="mb-3" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px; padding: 15px; background: #f8f9fa;">
                        <div class="text-muted text-center">
                            <span class="text-success fw-bold" style="font-size: 1.5rem;">Symbi<i class="fas fa-robot fa-2x text-success" style="font-size: 2rem;"></i>sis</span>
                            <p><strong>AI Advisor Ready</strong></p>
                            <p class="small">Ask me about succession planning, crop timing, or growing advice!</p>
                        </div>
                    </div>

                    <!-- Chat Input -->
                    <div class="mb-2">
                        <textarea class="form-control" id="chatInput" rows="2" 
                            placeholder="Ask a question... (e.g., 'What's the best succession interval for lettuce?')"></textarea>
                    </div>
                    
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-primary flex-grow-1" id="analyzePlanBtn" onclick="askAIAboutPlan()" title="Analyze succession plan with AI recommendations">
                            <i class="fas fa-brain"></i> Analyze Succession Plan
                        </button>
                        <button class="btn btn-success" id="sendChatBtn" title="Send your question to AI">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>

                    <!-- AI Response Area with Accept/Reject Buttons -->
                    <div id="aiResponseArea" class="mt-3" style="display: none;">
                        <!-- AI recommendations and action buttons will appear here -->
                    </div>
                </div>
            </div>

            <!-- Variety Information Display -->
            <div class="planning-card mt-3">
                <div class="planning-section">
                    <h3>
                        <i class="fas fa-leaf section-icon"></i>
                        Variety Information
                    </h3>
                    
                    <div id="varietyInfoContainer" class="variety-info-section" style="display: none;">
                        <!-- Variety Photo -->
                        <div class="variety-photo-container mb-3 text-center">
                            <img id="varietyPhoto" src="" alt="Variety Photo" class="variety-photo img-fluid rounded" 
                                 style="max-height: 200px; max-width: 100%; object-fit: cover;">
                            <div id="noPhotoMessage" class="text-muted small mt-2" style="display: none;">
                                <i class="fas fa-image"></i> No photo available
                            </div>
                        </div>
                        
                        <!-- Variety Details -->
                        <div class="variety-details">
                            <!-- Varietal Succession Carousel Controls -->
                            <div id="varietalSuccessionControls" class="d-flex justify-content-between align-items-center mb-2" style="display: none !important;">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="prevVarietyBtn">
                                    <i class="fas fa-chevron-left"></i> <span id="prevVarietyLabel">Prev</span>
                                </button>
                                <div class="text-center flex-grow-1 mx-3">
                                    <span class="badge bg-success" id="currentVarietyBadge">Early Variety (1/3)</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="nextVarietyBtn">
                                    <span id="nextVarietyLabel">Next</span> <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                            
                            <h5 id="varietyName" class="text-primary mb-2"></h5>
                            <div id="varietyDescription" class="variety-description small text-muted mb-3"></div>
                            
                            <!-- Additional Variety Info -->
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>Crop Type:</strong><br>
                                    <span id="varietyCropType" class="text-muted small"></span>
                                </div>
                                <div class="col-6">
                                    <strong>Variety ID:</strong><br>
                                    <span id="varietyId" class="text-muted small"></span>
                                </div>
                            </div>
                            
                            <!-- Crop-Specific Details -->
                            <div id="cropSpecifics" class="crop-specifics mt-3 pt-3 border-top" style="display: none;">
                                <h6 class="text-secondary mb-2"><i class="fas fa-info-circle"></i> Growing Details</h6>
                                <div class="row g-2 small">
                                    <div class="col-md-6" id="maturityDaysContainer" style="display: none;">
                                        <div class="bg-light p-2 rounded">
                                            <i class="fas fa-calendar-day text-success"></i>
                                            <strong>Days to Maturity:</strong>
                                            <span id="maturityDays" class="text-primary fw-bold"></span> days
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="propagationDaysContainer" style="display: none;">
                                        <div class="bg-light p-2 rounded">
                                            <i class="fas fa-seedling text-info"></i>
                                            <strong>Propagation Days:</strong>
                                            <span id="propagationDays" class="text-primary fw-bold"></span> days
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="harvestWindowContainer" style="display: none;">
                                        <div class="bg-light p-2 rounded">
                                            <i class="fas fa-calendar-check text-warning"></i>
                                            <strong>Harvest Window:</strong>
                                            <span id="harvestWindowDays" class="text-primary fw-bold"></span> days
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="seasonTypeContainer" style="display: none;">
                                        <div class="bg-light p-2 rounded">
                                            <i class="fas fa-sun text-warning"></i>
                                            <strong>Season:</strong>
                                            <span id="seasonType" class="text-primary fw-bold"></span>
                                        </div>
                                    </div>
                                    <div class="col-12" id="spacingContainer" style="display: none;">
                                        <div class="bg-light p-2 rounded">
                                            <i class="fas fa-ruler text-secondary"></i>
                                            <strong>Spacing:</strong>
                                            <span id="inRowSpacingDisplay" class="text-primary fw-bold"></span> cm in-row
                                            <span class="mx-1">×</span>
                                            <span id="betweenRowSpacingDisplay" class="text-primary fw-bold"></span> cm between-row
                                        </div>
                                    </div>
                                    <div class="col-12" id="frostToleranceContainer" style="display: none;">
                                        <div class="bg-light p-2 rounded">
                                            <i class="fas fa-snowflake text-primary"></i>
                                            <strong>Frost Tolerance:</strong>
                                            <span id="frostTolerance" class="text-primary fw-bold"></span>
                                        </div>
                                    </div>
                                    <div class="col-12" id="plantingMethodContainer" style="display: none;">
                                        <div class="bg-light p-2 rounded">
                                            <i class="fas fa-tools text-success"></i>
                                            <strong>Planting Method:</strong>
                                            <span id="plantingMethodDisplay" class="text-primary fw-bold"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Loading State -->
                        <div id="varietyLoading" class="text-center py-3" style="display: none;">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="text-muted small mt-2">Loading variety information...</div>
                        </div>
                        
                        <!-- Error State (hidden - using local database is not an error) -->
                        <div id="varietyError" class="alert alert-warning py-2 small" style="display: none;">
                            <!-- Error message removed - local database is preferred -->
                        </div>
                    </div>
                    
                    <!-- Succession Preview -->
                    <div id="successionPreviewContainer" class="succession-preview-section mt-4" style="display: none;">
                        <!-- Preview content will be populated by JavaScript -->
                    </div>
                    
                    <!-- No Variety Selected State -->
                    <div id="noVarietySelected" class="text-center py-4 text-muted">
                        <i class="fas fa-seedling fa-2x mb-2"></i>
                        <div>Select a variety to see detailed information from FarmOS</div>
                    </div>
                </div>
            </div>

            <!-- Succession Planning Sidebar (below variety, sticky on scroll) -->
            <div id="successionSidebar" class="planning-card mt-3 sticky-top" style="display: none; top: 20px;">
                <div class="planning-section">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="mb-0">
                            <i class="fas fa-tasks text-success"></i>
                            Succession Planning
                        </h4>
                        <span id="sidebarSuccessionCount" class="badge bg-primary">0 Successions</span>
                    </div>

                    <div class="succession-list" id="successionList">
                        <!-- Successions will be populated here as draggable items -->
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-seedling fa-2x mb-2"></i>
                            <p>Calculate a succession plan to see successions here</p>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="d-grid gap-2 mb-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="syncVarietiesFromFarmOS()" title="Sync latest variety data from FarmOS">
                                <i class="fas fa-sync"></i> Sync Varieties from FarmOS
                            </button>
                            <button class="btn btn-outline-danger btn-sm" onclick="clearAllAllocations()" title="Clear all bed allocations to start fresh">
                                <i class="fas fa-trash"></i> Clear All Allocations
                            </button>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Drag successions onto beds in the timeline to allocate them
                        </small>
                    </div>

                    <!-- Submit All Records Button -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-grid">
                            <button type="button" class="btn btn-success btn-lg" onclick="submitAllQuickForms()">
                                <i class="fas fa-save"></i> Submit All Records
                            </button>
                        </div>
                        <p class="text-muted text-center mt-2 mb-0">
                            <small>Submit all planting records to FarmOS</small>
                        </p>
                    </div>

                    <!-- Page Navigation Buttons -->
                    <div class="mt-3 pt-3 border-top">
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-success btn-sm w-100" onclick="scrollToQuickForms()">
                                    <i class="fas fa-arrow-down"></i> Quick Forms
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-success btn-sm w-100" onclick="scrollToTop()">
                                    <i class="fas fa-arrow-up"></i> Back to Top
                                </button>
                            </div>
                        </div>
                        <small class="text-muted text-center d-block mt-2">Quick navigation</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cache busting version: 1756750327-FIXED -->
<script>
    // Force cache busting - this script loaded at: ' + new Date().toISOString()
    console.log('🔄 Succession Planner Loading - Version: 1756750327-FIXED-' + Date.now());

    // Force cache busting by adding timestamp to all dynamic requests
    const CACHE_BUSTER = Date.now();

    // Safely parse JSON data with fallbacks
    let cropTypes = [];
    let cropVarieties = [];
    let availableBeds = [];

    try {
        cropTypes = @json($cropData['types'] ?? []);
    } catch (e) {
        console.warn('Failed to parse cropTypes JSON, using fallback:', e);
        cropTypes = [
            {id: 'lettuce', name: 'Lettuce', label: 'Lettuce'},
            {id: 'carrot', name: 'Carrot', label: 'Carrot'},
            {id: 'radish', name: 'Radish', label: 'Radish'},
            {id: 'spinach', name: 'Spinach', label: 'Spinach'},
            {id: 'kale', name: 'Kale', label: 'Kale'},
            {id: 'arugula', name: 'Arugula', label: 'Arugula'},
            {id: 'beets', name: 'Beets', label: 'Beets'}
        ];
    }

    try {
        cropVarieties = @json($cropData['varieties'] ?? []);
    } catch (e) {
        console.warn('Failed to parse cropVarieties JSON, using fallback:', e);
        cropVarieties = [
            {id: 'carrot_nantes', name: 'Nantes', parent_id: 'carrot', crop_type: 'carrot'},
            {id: 'carrot_chantenay', name: 'Chantenay', parent_id: 'carrot', crop_type: 'carrot'},
            {id: 'lettuce_buttercrunch', name: 'Buttercrunch', parent_id: 'lettuce', crop_type: 'lettuce'},
            {id: 'lettuce_romaine', name: 'Romaine', parent_id: 'lettuce', crop_type: 'lettuce'}
        ];
    }

    try {
        availableBeds = @json($availableBeds ?? []);
    } catch (e) {
        console.warn('Failed to parse availableBeds JSON, using fallback:', e);
        availableBeds = [];
    }

    // Debug: Log crop data counts
    console.log('🌾 Loaded crop types:', cropTypes.length);
    console.log('🥕 Loaded varieties:', cropVarieties.length);
    // if (cropTypes.length > 0) {
    //     console.log('📊 First crop type:', cropTypes[0]);
    // }
    // if (cropVarieties.length > 0) {
    //     console.log('📊 First variety:', cropVarieties[0]);
        const carrotVarieties = cropVarieties.filter(v => v.crop_type === 'Carrot');
        console.log('🥕 Carrot varieties found:', carrotVarieties.length);
    //     if (carrotVarieties.length > 0) {
    //         console.log('📊 First Carrot variety:', carrotVarieties[0]);
    //     }
    // }

    // Global API base (always use same origin/protocol to avoid mixed-content)
    const API_BASE = window.location.origin + '/admin/farmos/succession-planning';
    const FARMOS_BASE = "{{ config('services.farmos.url', '') }}";

    // SuccessionPlanner will be initialized in DOMContentLoaded below

    let currentSuccessionPlan = null;
    let isDragging = false;
    let dragHandle = null;
    let dragStartX = 0;
    let initialBarLeft = 0; // Track initial bar position when drag starts
    let initialBarWidth = 0; // Track initial bar width when drag starts
    let cropId = null; // Track selected crop ID for variety filtering
    // Shared controllers to cancel stale AI requests
    let __aiCalcController = null;
    let __aiChatController = null;
    // Store last AI harvest info for overlay rendering
    let __lastAIHarvestInfo = null;

    // Export succession plan as CSV
    function exportSuccessionPlan() {
        if (!harvestWindowData.userStart || !harvestWindowData.userEnd) {
            alert('Please set a harvest window first');
            return;
        }

        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        const cropName = cropSelect?.options[cropSelect.selectedIndex]?.text || '';
        const varietyName = varietySelect?.options[varietySelect.selectedIndex]?.text || '';

        const start = new Date(harvestWindowData.userStart);
        const end = new Date(harvestWindowData.userEnd);
        const duration = Math.ceil((end - start) / (1000 * 60 * 60 * 24));

        const avgSuccessionInterval = getSuccessionInterval(cropName.toLowerCase(), varietyName.toLowerCase());
        const successions = Math.max(1, Math.ceil(duration / avgSuccessionInterval));

        // Generate CSV content
        let csvContent = 'Succession,Crop,Variety,Sowing Date,Transplant Date,Harvest Date,Method\n';

        for (let i = 0; i < successions; i++) {
            const successionData = calculateSuccessionDates(start, i, avgSuccessionInterval, cropName.toLowerCase(), varietyName.toLowerCase());

            const sowDateStr = successionData.sowDate.toISOString().split('T')[0];
            const transplantDateStr = successionData.transplantDate ? successionData.transplantDate.toISOString().split('T')[0] : '';
            const harvestDateStr = successionData.harvestDate.toISOString().split('T')[0];

            csvContent += `${i + 1},"${cropName}","${varietyName}","${sowDateStr}","${transplantDateStr}","${harvestDateStr}","${successionData.method}"\n`;
        }

        // Download CSV file
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `succession-plan-${cropName.replace(/\s+/g, '-')}-${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        console.log('📊 Exported succession plan as CSV');
    }

    // Update export button state based on whether there's a plan to export
    function updateExportButton() {
        try {
            const exportBtn = document.querySelector('button[onclick="exportSuccessionPlan()"]');
            console.log('🔍 Looking for export button:', exportBtn);
            
            if (exportBtn) {
                if (currentSuccessionPlan && currentSuccessionPlan.plantings && currentSuccessionPlan.plantings.length > 0) {
                    exportBtn.disabled = false;
                    exportBtn.classList.remove('disabled');
                    console.log('✅ Export button enabled - plan available');
                } else {
                    exportBtn.disabled = true;
                    exportBtn.classList.add('disabled');
                    console.log('🚫 Export button disabled - no plan available');
                }
            } else {
                console.warn('⚠️ Export button not found in DOM');
            }
        } catch (error) {
            console.error('❌ Error in updateExportButton:', error);
        }
    }

    // Filter the variety dropdown by selected crop - HANDLED BY SuccessionPlanner

    // Fetch variety information from FarmOS API
    async function fetchVarietyInfo(varietyId) {
        if (!varietyId) return null;

        try {
            console.log('🌱 Fetching variety info for ID:', varietyId);
            console.log('🌐 Making request to:', `/admin/farmos/succession-planning/varieties/${varietyId}`);
            
            // Use the existing succession planning variety endpoint
            const response = await fetch(`/admin/farmos/succession-planning/varieties/${varietyId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            });

            console.log('📡 Response status:', response.status);
            console.log('📡 Response headers:', Object.fromEntries(response.headers.entries()));

            if (!response.ok) {
                const errorText = await response.text();
                console.error('❌ API Error Response:', errorText);
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('📋 Variety info received:', data);
            
            if (data.success && data.variety) {
                return data.variety;
            } else {
                console.warn('⚠️ Variety API returned success=false or no variety data');
                console.warn('⚠️ Full response:', data);
                return null;
            }
        } catch (error) {
            console.error('❌ Error fetching variety info:', error);
            console.error('❌ Error details:', {
                message: error.message,
                stack: error.stack
            });
            return null;
        }
    }

    // Display variety information in the UI
    function displayVarietyInfo(varietyData) {
        // Store globally for succession calculations
        window.currentVarietyData = varietyData;
        console.log('💾 Stored variety data globally:', varietyData);
        
        const container = document.getElementById('varietyInfoContainer');
        const loading = document.getElementById('varietyLoading');
        const error = document.getElementById('varietyError');
        const noSelection = document.getElementById('noVarietySelected');

        // Hide loading and error states
        loading.style.display = 'none';
        error.style.display = 'none';
        noSelection.style.display = 'none';

        if (!varietyData) {
            container.style.display = 'none';
            noSelection.style.display = 'block';
            return;
        }

        // Show container
        container.style.display = 'block';

        // Update variety name
        const nameEl = document.getElementById('varietyName');
        nameEl.textContent = varietyData.name || varietyData.title || 'Unknown Variety';

        // Update description - prioritize catalog description over generated notes
        const descEl = document.getElementById('varietyDescription');
        let description = '';
        
        // Show catalog description first (from Moles Seeds or other sources)
        if (varietyData.description) {
            description += varietyData.description;
        }
        
        // Optionally append harvest notes if they don't look auto-generated
        if (varietyData.harvest_notes && !varietyData.harvest_notes.includes('Estimated harvest window')) {
            if (description) description += '\n\n';
            description += varietyData.harvest_notes;
        }
        
        if (!description) {
            description = 'No description available';
        }
        
        descEl.textContent = description;

        // Update crop type
        const cropTypeEl = document.getElementById('varietyCropType');
        cropTypeEl.textContent = varietyData.crop_family || varietyData.plant_type || 'Unknown';

        // Check for season-specific planting recommendations (e.g., autumn-sown broad beans)
        const varietyName = (varietyData.name || varietyData.title || '').toLowerCase();
        const cropFamily = (varietyData.crop_family || varietyData.plant_type || '').toLowerCase();
        let seasonalNotice = '';
        
        // Broad beans - autumn vs spring varieties
        if (cropFamily.includes('bean') && (cropFamily.includes('broad') || varietyName.includes('broad'))) {
            if (varietyName.includes('aquadulce') || varietyName.includes('bunyard')) {
                seasonalNotice = `
                    <div class="alert alert-info mt-3" role="alert">
                        <i class="fas fa-snowflake"></i> <strong>Autumn-Sown Variety</strong>
                        <p class="mb-0 mt-2">This variety (${varietyData.name}) is typically <strong>sown in October-November</strong> for harvest in May-June. 
                        It overwinters in the ground and provides early spring crops. If planning for spring planting, consider a spring variety like 'The Sutton' or 'Duet' instead.</p>
                    </div>
                `;
            } else if (varietyName.includes('duet')) {
                seasonalNotice = `
                    <div class="alert alert-primary mt-3" role="alert">
                        <i class="fas fa-calendar-alt"></i> <strong>Extended Season Variety</strong>
                        <p class="mb-0 mt-2">This variety (${varietyData.name}) offers an <strong>extended harvest window from May to October</strong>. 
                        Sow in <strong>February-March</strong> for spring cropping, or <strong>June-July</strong> for autumn harvest. Compact growth makes it ideal for small spaces.</p>
                    </div>
                `;
            } else if (varietyName.includes('sutton') || varietyName.includes('stereo')) {
                seasonalNotice = `
                    <div class="alert alert-success mt-3" role="alert">
                        <i class="fas fa-seedling"></i> <strong>Spring-Sown Variety</strong>
                        <p class="mb-0 mt-2">This variety (${varietyData.name}) is suitable for <strong>spring sowing (February-April)</strong> for harvest in June-August.</p>
                    </div>
                `;
            }
        }
        
        // Insert seasonal notice after variety description
        if (seasonalNotice) {
            const descContainer = descEl.parentElement;
            // Remove any existing seasonal notice
            const existingNotice = descContainer.querySelector('.seasonal-planting-notice');
            if (existingNotice) existingNotice.remove();
            
            // Add new notice
            const noticeDiv = document.createElement('div');
            noticeDiv.className = 'seasonal-planting-notice';
            noticeDiv.innerHTML = seasonalNotice;
            descContainer.appendChild(noticeDiv);
        }

        // Update variety ID
        const idEl = document.getElementById('varietyId');
        idEl.textContent = varietyData.farmos_id || varietyData.id || 'N/A';

        // Populate crop-specific details
        const cropSpecificsSection = document.getElementById('cropSpecifics');
        let hasSpecificDetails = false;

        // Days to Maturity
        if (varietyData.maturity_days && varietyData.maturity_days > 0) {
            document.getElementById('maturityDays').textContent = varietyData.maturity_days;
            document.getElementById('maturityDaysContainer').style.display = 'block';
            hasSpecificDetails = true;
        } else {
            document.getElementById('maturityDaysContainer').style.display = 'none';
        }

        // Propagation/Transplant Days
        if (varietyData.propagation_days && varietyData.propagation_days > 0) {
            document.getElementById('propagationDays').textContent = varietyData.propagation_days;
            document.getElementById('propagationDaysContainer').style.display = 'block';
            hasSpecificDetails = true;
        } else if (varietyData.transplant_days && varietyData.transplant_days > 0) {
            document.getElementById('propagationDays').textContent = varietyData.transplant_days;
            document.getElementById('propagationDaysContainer').style.display = 'block';
            hasSpecificDetails = true;
        } else {
            document.getElementById('propagationDaysContainer').style.display = 'none';
        }

        // Harvest Window
        if (varietyData.harvest_window_days && varietyData.harvest_window_days > 0) {
            document.getElementById('harvestWindowDays').textContent = varietyData.harvest_window_days;
            document.getElementById('harvestWindowContainer').style.display = 'block';
            hasSpecificDetails = true;
        } else {
            document.getElementById('harvestWindowContainer').style.display = 'none';
        }

        // Season Type
        if (varietyData.season_type || varietyData.season) {
            const season = varietyData.season_type || varietyData.season;
            document.getElementById('seasonType').textContent = season.charAt(0).toUpperCase() + season.slice(1);
            document.getElementById('seasonTypeContainer').style.display = 'block';
            hasSpecificDetails = true;
        } else {
            document.getElementById('seasonTypeContainer').style.display = 'none';
        }

        // Spacing (combined in-row and between-row on one line)
        const hasInRowSpacing = varietyData.in_row_spacing_cm && varietyData.in_row_spacing_cm > 0;
        const hasBetweenRowSpacing = varietyData.between_row_spacing_cm && varietyData.between_row_spacing_cm > 0;
        
        if (hasInRowSpacing || hasBetweenRowSpacing) {
            if (hasInRowSpacing) {
                document.getElementById('inRowSpacingDisplay').textContent = varietyData.in_row_spacing_cm;
            } else {
                document.getElementById('inRowSpacingDisplay').textContent = 'N/A';
            }
            
            if (hasBetweenRowSpacing) {
                document.getElementById('betweenRowSpacingDisplay').textContent = varietyData.between_row_spacing_cm;
            } else {
                document.getElementById('betweenRowSpacingDisplay').textContent = 'N/A';
            }
            
            document.getElementById('spacingContainer').style.display = 'block';
            hasSpecificDetails = true;
        } else {
            document.getElementById('spacingContainer').style.display = 'none';
        }

        // Frost Tolerance
        if (varietyData.frost_tolerance) {
            document.getElementById('frostTolerance').textContent = varietyData.frost_tolerance;
            document.getElementById('frostToleranceContainer').style.display = 'block';
            hasSpecificDetails = true;
        } else {
            document.getElementById('frostToleranceContainer').style.display = 'none';
        }

        // Planting Method
        if (varietyData.planting_method) {
            const method = varietyData.planting_method.charAt(0).toUpperCase() + varietyData.planting_method.slice(1);
            document.getElementById('plantingMethodDisplay').textContent = method;
            document.getElementById('plantingMethodContainer').style.display = 'block';
            hasSpecificDetails = true;
        } else {
            document.getElementById('plantingMethodContainer').style.display = 'none';
        }

        // Show/hide the crop specifics section based on whether we have any details
        if (hasSpecificDetails) {
            cropSpecificsSection.style.display = 'block';
        } else {
            cropSpecificsSection.style.display = 'none';
        }

        // Handle photo - check if variety has photo data
        const photoEl = document.getElementById('varietyPhoto');
        const noPhotoEl = document.getElementById('noPhotoMessage');

        // Check for photo in variety data (could be from FarmOS or admin DB)
        let photoUrl = null;
        let photoAlt = '';

        if (varietyData.image_url) {
            // Check if it's a file ID (UUID format) or a direct URL
            if (varietyData.image_url.match(/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i)) {
                // It's a file ID, use proxy route
                photoUrl = '/admin/farmos/variety-image/' + varietyData.image_url;
            } else {
                // It's a direct URL
                photoUrl = varietyData.image_url;
            }
            photoAlt = varietyData.image_alt_text || varietyData.name || 'Variety image';
        } else if (varietyData.photo) {
            photoUrl = varietyData.photo;
        } else if (varietyData.image) {
            photoUrl = varietyData.image;
        } else if (varietyData.farmos_data && varietyData.farmos_data.attributes && varietyData.farmos_data.attributes.image) {
            photoUrl = varietyData.farmos_data.attributes.image;
        }

        if (photoUrl) {
            photoEl.src = photoUrl;
            photoEl.alt = photoAlt;
            photoEl.style.display = 'block';
            noPhotoEl.style.display = 'none';
        } else {
            // No photo available - show placeholder image
            console.log('🖼️ No photo URL found, displaying placeholder image');
            // Simple, guaranteed-to-be-visible placeholder: bright blue rectangle with text
            photoEl.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(`
                <svg width="600" height="400" xmlns="http://www.w3.org/2000/svg">
                    <rect width="600" height="400" fill="#e3f2fd"/>
                    <rect x="20" y="20" width="560" height="360" fill="none" stroke="#1976d2" stroke-width="4" rx="10"/>
                    <text x="300" y="180" font-family="Arial, sans-serif" font-size="32" fill="#1976d2" text-anchor="middle" font-weight="bold">🌱 NO PHOTO AVAILABLE 🌱</text>
                    <text x="300" y="240" font-family="Arial, sans-serif" font-size="24" fill="#666" text-anchor="middle">Variety Image Not Found</text>
                </svg>
            `);
            photoEl.alt = 'No variety photo available';
            photoEl.style.display = 'block';
            photoEl.style.backgroundColor = '#e3f2fd'; // Light blue background for visibility
            noPhotoEl.style.display = 'none';
            console.log('✅ Placeholder image set, display:', photoEl.style.display);
            console.log('✅ Placeholder src length:', photoEl.src.length);
        }

        // Auto-populate spacing fields from database
        const inRowSpacingInput = document.getElementById('inRowSpacing');
        const betweenRowSpacingInput = document.getElementById('betweenRowSpacing');
        
        if (varietyData.in_row_spacing_cm && varietyData.in_row_spacing_cm > 0) {
            inRowSpacingInput.value = varietyData.in_row_spacing_cm;
            // Add visual indicator that value is from database
            inRowSpacingInput.classList.add('border-success');
            inRowSpacingInput.title = `Auto-filled from database: ${varietyData.in_row_spacing_cm} cm`;
            // console.log('✅ Auto-populated in-row spacing:', varietyData.in_row_spacing_cm, 'cm');
        } else {
            // Keep default value, remove database indicator
            inRowSpacingInput.classList.remove('border-success');
            inRowSpacingInput.title = 'Default spacing - adjust as needed';
            // console.log('ℹ️ No in-row spacing in database, using default');
        }
        
        if (varietyData.between_row_spacing_cm && varietyData.between_row_spacing_cm > 0) {
            betweenRowSpacingInput.value = varietyData.between_row_spacing_cm;
            // Add visual indicator that value is from database
            betweenRowSpacingInput.classList.add('border-success');
            betweenRowSpacingInput.title = `Auto-filled from database: ${varietyData.between_row_spacing_cm} cm`;
            // console.log('✅ Auto-populated between-row spacing:', varietyData.between_row_spacing_cm, 'cm');
        } else {
            // Keep default value, remove database indicator
            betweenRowSpacingInput.classList.remove('border-success');
            betweenRowSpacingInput.title = 'Default spacing - adjust as needed';
            console.log('ℹ️ No between-row spacing in database, using default');
        }
        
        // Show density preset selector for brassicas
        const cropName = document.getElementById('cropSelect')?.options[document.getElementById('cropSelect').selectedIndex]?.text?.toLowerCase() || '';
        const densityPreset = document.getElementById('brassicaDensityPreset');
        
        if (cropName.includes('brussels') || cropName.includes('cabbage') || 
            cropName.includes('broccoli') || cropName.includes('cauliflower')) {
            densityPreset.style.display = 'block';
            // Check if function exists before calling (it's defined later in the file)
            if (typeof updateDensityPresetDisplay === 'function') {
                updateDensityPresetDisplay(); // Update the preset display with current bed width
            }
            console.log('🥬 Brassica detected - showing density preset options');
        } else {
            densityPreset.style.display = 'none';
        }
        
        // Auto-populate succession interval dropdown from database
        const successionIntervalSelect = document.getElementById('successionIntervalSelect');
        const customIntervalInput = document.getElementById('customIntervalInput');
        const customIntervalDays = document.getElementById('customIntervalDays');
        
        if (successionIntervalSelect) {
            try {
                let intervalToSet = null;
                
                // First, try to get succession_interval from variety data
                if (varietyData.succession_interval && varietyData.succession_interval > 0) {
                    intervalToSet = varietyData.succession_interval;
                    // console.log('✅ Found succession interval in variety data:', intervalToSet, 'days');
                } else {
                    // Fall back to getSuccessionInterval function based on crop type
                    const varietyName = (varietyData.name || '').toLowerCase();
                    intervalToSet = getSuccessionInterval(cropName, varietyName);
                    // console.log('ℹ️ Using default succession interval for crop type:', intervalToSet, 'days');
                }
                
                // Try to match with dropdown options
                const matchingOption = Array.from(successionIntervalSelect.options).find(
                    option => option.value == intervalToSet
                );
                
                if (matchingOption) {
                    successionIntervalSelect.value = intervalToSet;
                    if (customIntervalInput) customIntervalInput.style.display = 'none';
                    // console.log('✅ Auto-populated succession interval dropdown:', intervalToSet, 'days');
                } else {
                    // Use custom option for non-standard intervals
                    successionIntervalSelect.value = 'custom';
                    if (customIntervalDays) customIntervalDays.value = intervalToSet;
                    if (customIntervalInput) customIntervalInput.style.display = 'block';
                    // console.log('✅ Set custom succession interval:', intervalToSet, 'days');
                }
                
                // Add visual indicator that value is from database
                if (varietyData.succession_interval && varietyData.succession_interval > 0) {
                    successionIntervalSelect.classList.add('border-success');
                    successionIntervalSelect.title = `Auto-filled from database: ${intervalToSet} days`;
                } else {
                    successionIntervalSelect.classList.remove('border-success');
                    successionIntervalSelect.title = 'Default interval based on crop type - adjust as needed';
                }
            } catch (err) {
                console.error('❌ Error setting succession interval:', err);
            }
        }
        
        // Log planting method for reference (could be used for overseeding calculations later)
        if (varietyData.planting_method) {
            console.log('🌱 Planting method:', varietyData.planting_method);
        }

        // Show and configure planting method selector
        const plantingMethodRow = document.getElementById('plantingMethodRow');
        if (plantingMethodRow) {
            plantingMethodRow.style.display = 'block';
            
            // Set default based on variety data
            const method = varietyData.planting_method?.toLowerCase() || 'either';
            const methodRadio = document.getElementById(
                method === 'direct' ? 'methodDirectSow' :
                method === 'transplant' ? 'methodTransplant' :
                'methodEither'
            );
            
            if (methodRadio) {
                methodRadio.checked = true;
                
                // Only trigger change event if not suppressed (e.g., not from carousel navigation)
                if (!window.__suppressPlantingMethodChange) {
                    // Trigger change event to update hint and calculations
                    const event = new Event('change', { bubbles: true });
                    methodRadio.dispatchEvent(event);
                }
                
                console.log('🌱 Set planting method to:', method);
            }
        }

        // console.log('✅ Variety information displayed');
    }

    // NOTE: setHarvestWindowFromVarietyData() function removed
    // We're using AI-calculated harvest windows instead of corrupted database dates
    // Database was corrupted on Oct 18, 2025 with generic "Oct 1 - Nov 30" fallback values
    // AI produces better results using maturity_days + season_type + horticultural knowledge

    // Handle variety selection and fetch/display info
    async function handleVarietySelection(varietyId) {
        console.log('🎯 handleVarietySelection called with ID:', varietyId);
        
        const container = document.getElementById('varietyInfoContainer');
        const loading = document.getElementById('varietyLoading');
        const error = document.getElementById('varietyError');
        const noSelection = document.getElementById('noVarietySelected');
        const harvestWindowSection = document.getElementById('harvestWindowSection');
        const resultsSection = document.getElementById('resultsSection');
        const quickFormTabsContainer = document.getElementById('quickFormTabsContainer');

        if (!varietyId) {
            // No variety selected
            console.log('📝 No variety selected, showing default state');
            container.style.display = 'none';
            loading.style.display = 'none';
            error.style.display = 'none';
            noSelection.style.display = 'block';
            if (harvestWindowSection) harvestWindowSection.style.display = 'none';
            if (resultsSection) resultsSection.style.display = 'none';
            if (quickFormTabsContainer) quickFormTabsContainer.style.display = 'none';
            return;
        }

        // Show loading state
        console.log('⏳ Showing loading state');
        container.style.display = 'block';
        loading.style.display = 'block';
        error.style.display = 'none';
        noSelection.style.display = 'none';
        if (harvestWindowSection) harvestWindowSection.style.display = 'block';
        
        // Show results section and quick forms immediately
        if (resultsSection) resultsSection.style.display = 'block';
        if (quickFormTabsContainer) quickFormTabsContainer.style.display = 'block';
        // console.log('✅ Timeline and Quick Forms sections shown immediately');

        try {
            console.log('🔍 Calling fetchVarietyInfo...');
            // Fetch variety information
            const varietyData = await fetchVarietyInfo(varietyId);
            console.log('📊 Variety data result:', varietyData);
            
            if (varietyData) {
                // console.log('✅ Displaying variety data');
                displayVarietyInfo(varietyData);
                
                // Show varietal succession option if variety has season_type
                const varietalSection = document.getElementById('varietalSuccessionSection');
                if (varietyData.season_type && varietalSection) {
                    varietalSection.style.display = 'block';
                    console.log('🌱 Varietal succession option shown for', varietyData.season_type, 'variety');
                }
                
                // 🤖 AI AUTO-CALCULATION ENABLED 🤖
                // AI produces BETTER harvest windows than corrupted database (Oct 18, 2025 event)
                // AI uses: maturity_days + season_type + horticultural knowledge + RAG database
                // Database has generic "Oct 1 - Nov 30" fallback values for most varieties
                // Results: AI calculated Aug 24, Oct 11, Dec 26 for Brussels sprouts (realistic!)
                //          vs Database's corrupted Oct 1 - Nov 30 for all varieties (wrong!)
                await calculateAIHarvestWindow();
                console.log('✅ AI harvest window calculation triggered (AI has full control)');
            } else {
                // Show error state
                console.log('❌ No variety data received, showing error');
                loading.style.display = 'none';
                error.style.display = 'block';
                container.style.display = 'block';
            }
        } catch (err) {
            console.error('❌ Error in handleVarietySelection:', err);
            loading.style.display = 'none';
            error.style.display = 'block';
            container.style.display = 'block';
        }
    }

    // Varietal Succession Functions
    function toggleVarietalSuccession() {
        const checkbox = document.getElementById('useVarietalSuccession');
        const varietiesSection = document.getElementById('varietalSuccessionVarieties');
        const varietiesSection2V = document.getElementById('varietalSuccessionVarieties2V');
        const singleVarietySelect = document.getElementById('varietySelect');
        
        if (checkbox.checked) {
            // Detect how many season types are available for this crop
            const cropSelect = document.getElementById('cropSelect');
            const varietySelect = document.getElementById('varietySelect');
            const currentCropId = cropSelect?.value;
            const currentVarietyId = varietySelect?.value;
            
            if (!currentCropId) {
                console.warn('⚠️ No crop selected');
                checkbox.checked = false;
                return;
            }
            
            console.log('🔍 Current crop ID:', currentCropId);
            console.log('🔍 Current variety ID:', currentVarietyId);
            
            // Find the actual plant_type_id to use for grouping varieties
            let plantTypeIdToUse = currentCropId;
            
            // If a variety is selected, get its plant_type_id to find all sibling varieties
            if (currentVarietyId) {
                const selectedVariety = cropVarieties.find(v => v.id === currentVarietyId);
                if (selectedVariety?.plant_type_id) {
                    plantTypeIdToUse = selectedVariety.plant_type_id;
                    console.log('🔍 Using variety plant_type_id:', plantTypeIdToUse);
                }
            }
            
            // Count available season types for this crop family
            const currentCropVarieties = cropVarieties.filter(v => v.plant_type_id === plantTypeIdToUse);
            console.log('📦 Found varieties with plant_type_id:', currentCropVarieties.length);
            
            const seasonTypes = new Set(
                currentCropVarieties
                    .map(v => v.season_type)
                    .filter(s => s && s !== 'all_season')
            );
            
            const hasEarly = seasonTypes.has('early');
            const hasMid = seasonTypes.has('mid');
            const hasLate = seasonTypes.has('late');
            const seasonCount = seasonTypes.size;
            
            console.log(`🔍 Crop has ${seasonCount} season types:`, {hasEarly, hasMid, hasLate});
            console.log('📊 Season types found:', Array.from(seasonTypes));
            
            // Show appropriate interface based on season count
            if (seasonCount === 2 && hasEarly && hasMid && !hasLate) {
                // Show 2-variety interface (broad beans, etc.)
                varietiesSection.style.display = 'none';
                varietiesSection2V.style.display = 'flex';
                populateVarietalSuccessionDropdowns2V();
                updateVarietalSuccessionSummary2V();
                console.log('✓ 2-variety succession enabled (autumn/winter + spring)');
            } else {
                // Show standard 3-variety interface
                varietiesSection.style.display = 'flex';
                varietiesSection2V.style.display = 'none';
                populateVarietalSuccessionDropdowns();
                updateVarietalSuccessionSummary();
                console.log('✓ Varietal succession enabled - please select all 3 varieties');
            }
        } else {
            varietiesSection.style.display = 'none';
            varietiesSection2V.style.display = 'none';
            
            // Recalculate with single variety if harvest window is set
            if (harvestWindowData.userStart && harvestWindowData.userEnd) {
                console.log('🔄 Recalculating with single variety...');
                calculateSuccessionPlan();
            }
        }
        
        console.log('🔄 Varietal succession toggled:', checkbox.checked);
    }
    
    function updateVarietalSuccessionSummary() {
        const earlyCount = parseInt(document.getElementById('earlyBedsCount')?.value) || 1;
        const midCount = parseInt(document.getElementById('midBedsCount')?.value) || 1;
        const lateCount = parseInt(document.getElementById('lateBedsCount')?.value) || 1;
        const totalCount = earlyCount + midCount + lateCount;
        
        document.getElementById('earlyCountDisplay').textContent = earlyCount;
        document.getElementById('midCountDisplay').textContent = midCount;
        document.getElementById('lateCountDisplay').textContent = lateCount;
        document.getElementById('totalSuccessionsDisplay').textContent = totalCount;
        
        console.log(`📊 Updated succession summary: ${totalCount} total (${earlyCount} early + ${midCount} mid + ${lateCount} late)`);
    }
    
    function recalculateVarietalSuccession() {
        console.log('🔄 Recalculating varietal succession plan with updated bed counts...');
        
        // Validate that all varieties are selected
        const earlyVarietyId = document.getElementById('earlyVarietySelect')?.value;
        const midVarietyId = document.getElementById('midVarietySelect')?.value;
        const lateVarietyId = document.getElementById('lateVarietySelect')?.value;
        
        if (!earlyVarietyId || !midVarietyId || !lateVarietyId) {
            showToast('Please select all three varieties (early, mid, late) before recalculating', 'warning');
            return;
        }
        
        // Check harvest window is set
        if (!harvestWindowData.userStart || !harvestWindowData.userEnd) {
            showToast('Please set harvest window dates before recalculating', 'warning');
            return;
        }
        
        // Trigger the succession plan calculation
        calculateSuccessionPlan();
        
        showToast('Recalculating succession plan with updated bed counts...', 'info');
    }
    
    async function populateVarietalSuccessionDropdowns() {
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        const currentVarietyId = varietySelect.value;
        
        if (!cropSelect.value) {
            console.warn('⚠️ No crop selected');
            return;
        }
        
        console.log('🌱 Populating varietal succession dropdowns for crop:', cropSelect.value);
        console.log('🔍 Current variety ID:', currentVarietyId);
        
        try {
            // Get current variety to find crop family
            const currentVariety = cropVarieties.find(v => v.id === cropSelect.value);
            if (!currentVariety) {
                console.warn('⚠️ Current crop not found');
                return;
            }
            
            console.log('📦 Current crop:', currentVariety);
            
            // Get all varieties that share the same plant_type as the selected variety
            const selectedVariety = cropVarieties.find(v => v.id === currentVarietyId);
            if (!selectedVariety || !selectedVariety.plant_type_id) {
                console.warn('⚠️ Selected variety or plant_type_id not found');
                return;
            }
            
            console.log('📦 Selected variety:', selectedVariety);
            console.log('🔍 Looking for siblings with plant_type_id:', selectedVariety.plant_type_id);
            
            const relatedVarieties = cropVarieties.filter(v => 
                v.plant_type_id === selectedVariety.plant_type_id && 
                v.season_type
            );
            
            console.log(`📦 Found ${relatedVarieties.length} related varieties with season_type`);
            
            // Group by season_type
            const early = relatedVarieties
                .filter(v => v.season_type === 'early')
                .map(v => ({
                    id: v.id,
                    name: v.name,
                    season_type: v.season_type,
                    maturity_days: v.maturity_days,
                    harvest_window_days: v.harvest_window_days
                }))
                .sort((a, b) => (a.maturity_days || 0) - (b.maturity_days || 0));
            
            const mid = relatedVarieties
                .filter(v => v.season_type === 'mid')
                .map(v => ({
                    id: v.id,
                    name: v.name,
                    season_type: v.season_type,
                    maturity_days: v.maturity_days,
                    harvest_window_days: v.harvest_window_days
                }))
                .sort((a, b) => (a.maturity_days || 0) - (b.maturity_days || 0));
            
            const late = relatedVarieties
                .filter(v => v.season_type === 'late')
                .map(v => ({
                    id: v.id,
                    name: v.name,
                    season_type: v.season_type,
                    maturity_days: v.maturity_days,
                    harvest_window_days: v.harvest_window_days
                }))
                .sort((a, b) => (a.maturity_days || 0) - (b.maturity_days || 0));
            
            console.log(`📊 Found varieties - Early: ${early.length}, Mid: ${mid.length}, Late: ${late.length}`);
            
            // Get current variety's season_type
            const currentVarietyData = cropVarieties.find(v => v.id === currentVarietyId);
            const currentSeasonType = currentVarietyData?.season_type;
            
            // Populate each dropdown
            populateSeasonDropdown('earlyVarietySelect', early, currentSeasonType === 'early' ? currentVarietyId : null);
            populateSeasonDropdown('midVarietySelect', mid, currentSeasonType === 'mid' ? currentVarietyId : null);
            populateSeasonDropdown('lateVarietySelect', late, currentSeasonType === 'late' ? currentVarietyId : null);
            
            console.log('✅ Varietal succession dropdowns populated');
            
            // Set up event listeners for the dropdowns
            setupVarietalSuccessionListeners();
            
            // Check if all 3 varieties are now selected (after pre-selection)
            checkVarietalSuccessionComplete();
            
        } catch (error) {
            console.error('❌ Error populating varietal succession dropdowns:', error);
            showToast('Failed to load varieties by season', 'error');
        }
    }
    
    function setupVarietalSuccessionListeners() {
        const earlySelect = document.getElementById('earlyVarietySelect');
        const midSelect = document.getElementById('midVarietySelect');
        const lateSelect = document.getElementById('lateVarietySelect');
        
        console.log('🔧 Setting up varietal succession event listeners');
        
        // Remove any existing listeners by cloning and replacing (preserve selected values)
        if (earlySelect) {
            const oldValue = earlySelect.value; // Save the selected value
            const newEarly = earlySelect.cloneNode(true);
            newEarly.value = oldValue; // Restore the selected value
            earlySelect.parentNode.replaceChild(newEarly, earlySelect);
            newEarly.addEventListener('change', function() {
                console.log('🌱 Early variety changed to:', this.value);
                checkVarietalSuccessionComplete();
            });
            console.log('✓ Early variety listener added');
        }
        
        if (midSelect) {
            const oldValue = midSelect.value; // Save the selected value
            const newMid = midSelect.cloneNode(true);
            newMid.value = oldValue; // Restore the selected value
            midSelect.parentNode.replaceChild(newMid, midSelect);
            newMid.addEventListener('change', function() {
                console.log('🌿 Mid variety changed to:', this.value);
                checkVarietalSuccessionComplete();
            });
            console.log('✓ Mid variety listener added');
        }
        
        if (lateSelect) {
            const oldValue = lateSelect.value; // Save the selected value
            const newLate = lateSelect.cloneNode(true);
            newLate.value = oldValue; // Restore the selected value
            lateSelect.parentNode.replaceChild(newLate, lateSelect);
            newLate.addEventListener('change', function() {
                console.log('🍂 Late variety changed to:', this.value);
                checkVarietalSuccessionComplete();
            });
            console.log('✓ Late variety listener added');
        }
        
        console.log('✅ Varietal succession listeners set up');
    }
    
    function checkVarietalSuccessionComplete() {
        const earlySelect = document.getElementById('earlyVarietySelect');
        const midSelect = document.getElementById('midVarietySelect');
        const lateSelect = document.getElementById('lateVarietySelect');
        const checkbox = document.getElementById('useVarietalSuccession');
        
        console.log('🔍 checkVarietalSuccessionComplete called');
        console.log('📊 Dropdown elements:', {
            early: !!earlySelect,
            mid: !!midSelect,
            late: !!lateSelect,
            checkbox: !!checkbox,
            checked: checkbox?.checked
        });
        
        if (!checkbox?.checked) {
            console.log('⏸️ Checkbox not checked, skipping');
            return;
        }
        
        const earlyValue = earlySelect?.value || '';
        const midValue = midSelect?.value || '';
        const lateValue = lateSelect?.value || '';
        
        console.log('📊 Dropdown values:', {
            early: earlyValue,
            mid: midValue,
            late: lateValue
        });
        
        const hasAll = earlyValue && midValue && lateValue;
        console.log(`🔍 Varietal succession check - Early: ${!!earlyValue}, Mid: ${!!midValue}, Late: ${!!lateValue}, hasAll: ${hasAll}`);
        
        if (hasAll) {
            console.log('✅ All 3 varieties selected, loading variety info and recalculating...');
            
            // Load all 3 variety descriptions for the carousel
            loadVarietalSuccessionInfo(earlyValue, midValue, lateValue);
            
            // Proceed with calculation
            
            calculateSuccessionPlan();
        } else {
            console.log('⏳ Waiting for all 3 varieties to be selected...');
            
            // Hide varietal succession controls if not all selected
            const controls = document.getElementById('varietalSuccessionControls');
            if (controls) {
                controls.style.display = 'none';
            }
        }
    }
    
    // Global variable to store varietal succession info for carousel
    let varietalSuccessionData = [];
    let currentVarietyIndex = 0;

    async function loadVarietalSuccessionInfo(earlyId, midId, lateId) {
        console.log('🔄 Loading variety info for all 3 varieties:', { earlyId, midId, lateId });
        
        try {
            // Fetch all 3 varieties in parallel
            const [earlyData, midData, lateData] = await Promise.all([
                fetchVarietyInfo(earlyId),
                fetchVarietyInfo(midId),
                fetchVarietyInfo(lateId)
            ]);
            
            varietalSuccessionData = [
                { type: 'early', data: earlyData, label: 'Early Variety' },
                { type: 'mid', data: midData, label: 'Mid-Season Variety' },
                { type: 'late', data: lateData, label: 'Late Variety' }
            ];
            
            console.log('✅ All 3 varieties loaded:', varietalSuccessionData);
            
            // Show the carousel controls
            const controls = document.getElementById('varietalSuccessionControls');
            if (controls) {
                controls.style.display = 'flex !important';
                controls.classList.remove('d-none');
                controls.setAttribute('style', 'display: flex !important;');
            }
            
            // Display the first variety
            currentVarietyIndex = 0;
            displayVarietyFromCarousel(0);
            
        } catch (error) {
            console.error('❌ Error loading varietal succession info:', error);
        }
    }

    function displayVarietyFromCarousel(index) {
        if (!varietalSuccessionData || varietalSuccessionData.length === 0) {
            console.warn('⚠️ No varietal succession data available');
            return;
        }
        
        const varietyInfo = varietalSuccessionData[index];
        console.log(`📱 Displaying variety ${index + 1}/3:`, varietyInfo.label, varietyInfo.data?.name);
        
        // Set flag to prevent cascade when switching carousel slides
        window.__suppressPlantingMethodChange = true;
        
        // Update the variety display using the correct function
        displayVarietyInfo(varietyInfo.data);
        
        // Clear flag after a brief delay to allow event to be suppressed
        setTimeout(() => {
            window.__suppressPlantingMethodChange = false;
        }, 100);
        
        // Update carousel badge
        const badge = document.getElementById('currentVarietyBadge');
        if (badge) {
            badge.textContent = `${varietyInfo.label} (${index + 1}/3)`;
            badge.className = `badge ${index === 0 ? 'bg-success' : index === 1 ? 'bg-warning' : 'bg-info'}`;
        }
        
        // Update button labels
        const prevLabel = document.getElementById('prevVarietyLabel');
        const nextLabel = document.getElementById('nextVarietyLabel');
        
        if (prevLabel) {
            const prevIndex = index === 0 ? 2 : index - 1;
            prevLabel.textContent = varietalSuccessionData[prevIndex].label.split(' ')[0]; // Just "Early", "Mid", or "Late"
        }
        
        if (nextLabel) {
            const nextIndex = (index + 1) % 3;
            nextLabel.textContent = varietalSuccessionData[nextIndex].label.split(' ')[0];
        }
    }

    function switchVarietyInfo(direction, event) {
        // Prevent any form submission or page navigation
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        if (!varietalSuccessionData || varietalSuccessionData.length === 0) {
            console.warn('⚠️ No varietal succession data to switch');
            return false;
        }
        
        const maxIndex = varietalSuccessionData.length - 1;
        
        if (direction === 'next') {
            currentVarietyIndex = (currentVarietyIndex + 1) % varietalSuccessionData.length;
        } else if (direction === 'prev') {
            currentVarietyIndex = currentVarietyIndex === 0 ? maxIndex : currentVarietyIndex - 1;
        }
        
        console.log(`🔄 Switching to variety ${currentVarietyIndex + 1}/${varietalSuccessionData.length}`);
        
        // Check if we're in 2-variety mode
        const varietiesSection2V = document.getElementById('varietalSuccessionVarieties2V');
        const is2VarietyMode = varietiesSection2V && varietiesSection2V.style.display !== 'none';
        
        if (is2VarietyMode) {
            displayVarietyFromCarousel2V(currentVarietyIndex);
        } else {
            displayVarietyFromCarousel(currentVarietyIndex);
        }
        
        return false; // Extra safeguard to prevent form submission
    }

    function populateSeasonDropdown(selectId, varieties, preselectedId = null) {
        console.log(`📋 Populating ${selectId} with ${varieties?.length || 0} varieties`);
        
        const select = document.getElementById(selectId);
        if (!select) {
            console.error(`❌ Dropdown ${selectId} not found`);
            return;
        }
        
        select.innerHTML = '<option value="">Select variety...</option>';
        
        if (!varieties || varieties.length === 0) {
            console.warn(`⚠️ No varieties provided for ${selectId}`);
            return;
        }
        
        varieties.forEach(variety => {
            const option = document.createElement('option');
            option.value = variety.id;
            option.textContent = `${variety.name} (${variety.maturity_days} days)`;
            option.dataset.maturityDays = variety.maturity_days;
            option.dataset.harvestWindowDays = variety.harvest_window_days || 30;
            
            if (preselectedId && variety.id === preselectedId) {
                option.selected = true;
                console.log(`✓ Pre-selected ${variety.name} in ${selectId}`);
            }
            
            select.appendChild(option);
        });
        
        console.log(`✅ ${selectId} populated with ${varieties.length} options`);
    }

    // helper to call AI but avoid blocking UI if route missing
    async function awaitMaybeCalculateAI(varietyId) {
        try {
            await calculateAIHarvestWindow();
        } catch (e) {
            console.warn('AI calculation skipped or failed:', e);
        }
    }

    // ============================================================================
    // 2-VARIETY SUCCESSION FUNCTIONS (for crops with 2 planting seasons)
    // ============================================================================

    async function populateVarietalSuccessionDropdowns2V() {
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        const currentVarietyId = varietySelect.value;
        
        if (!cropSelect.value) {
            console.warn('⚠️ No crop selected');
            return;
        }
        
        console.log('🌱 Populating 2-variety succession dropdowns for crop:', cropSelect.value);
        console.log('🔍 Current variety ID:', currentVarietyId);
        
        try {
            // Get all varieties that share the same plant_type as the selected variety
            const selectedVariety = cropVarieties.find(v => v.id === currentVarietyId);
            if (!selectedVariety || !selectedVariety.plant_type_id) {
                console.warn('⚠️ Selected variety or plant_type_id not found');
                return;
            }
            
            console.log('📦 Selected variety:', selectedVariety);
            console.log('🔍 Looking for siblings with plant_type_id:', selectedVariety.plant_type_id);
            
            const relatedVarieties = cropVarieties.filter(v => 
                v.plant_type_id === selectedVariety.plant_type_id && 
                v.season_type
            );
            
            console.log(`📦 Found ${relatedVarieties.length} related varieties with season_type`);
            
            // Group by season_type (only early and mid for 2-variety crops)
            const early = relatedVarieties
                .filter(v => v.season_type === 'early')
                .map(v => ({
                    id: v.id,
                    name: v.name,
                    season_type: v.season_type,
                    maturity_days: v.maturity_days,
                    harvest_window_days: v.harvest_window_days
                }))
                .sort((a, b) => (a.maturity_days || 0) - (b.maturity_days || 0));
            
            const mid = relatedVarieties
                .filter(v => v.season_type === 'mid')
                .map(v => ({
                    id: v.id,
                    name: v.name,
                    season_type: v.season_type,
                    maturity_days: v.maturity_days,
                    harvest_window_days: v.harvest_window_days
                }))
                .sort((a, b) => (a.maturity_days || 0) - (b.maturity_days || 0));
            
            console.log(`📊 Found varieties - Early (autumn/winter): ${early.length}, Mid (spring): ${mid.length}`);
            
            // Get current variety's season_type
            const currentVarietyData = cropVarieties.find(v => v.id === currentVarietyId);
            const currentSeasonType = currentVarietyData?.season_type;
            
            // Populate each dropdown
            populateSeasonDropdown('earlyVarietySelect2V', early, currentSeasonType === 'early' ? currentVarietyId : null);
            populateSeasonDropdown('midVarietySelect2V', mid, currentSeasonType === 'mid' ? currentVarietyId : null);
            
            console.log('✅ 2-variety succession dropdowns populated');
            
            // Set up event listeners for the dropdowns
            setupVarietalSuccessionListeners2V();
            
            // Set up bed count listeners
            const earlyBedsInput = document.getElementById('earlyBedsCount2V');
            const midBedsInput = document.getElementById('midBedsCount2V');
            
            if (earlyBedsInput) {
                earlyBedsInput.addEventListener('input', updateVarietalSuccessionSummary2V);
            }
            if (midBedsInput) {
                midBedsInput.addEventListener('input', updateVarietalSuccessionSummary2V);
            }
            
            // Check if both varieties are now selected (after pre-selection)
            checkVarietalSuccessionComplete2V();
            
        } catch (error) {
            console.error('❌ Error populating 2-variety succession dropdowns:', error);
            showToast('Failed to load varieties by season', 'error');
        }
    }

    function setupVarietalSuccessionListeners2V() {
        const earlySelect = document.getElementById('earlyVarietySelect2V');
        const midSelect = document.getElementById('midVarietySelect2V');
        
        console.log('🔧 Setting up 2-variety succession event listeners');
        
        // Remove any existing listeners by cloning and replacing (preserve selected values)
        if (earlySelect) {
            const oldValue = earlySelect.value;
            const newEarly = earlySelect.cloneNode(true);
            newEarly.value = oldValue;
            earlySelect.parentNode.replaceChild(newEarly, earlySelect);
            newEarly.addEventListener('change', function() {
                console.log('🍂 Autumn/winter variety changed to:', this.value);
                checkVarietalSuccessionComplete2V();
            });
            console.log('✓ Autumn/winter variety listener added');
        }
        
        if (midSelect) {
            const oldValue = midSelect.value;
            const newMid = midSelect.cloneNode(true);
            newMid.value = oldValue;
            midSelect.parentNode.replaceChild(newMid, midSelect);
            newMid.addEventListener('change', function() {
                console.log('🌱 Spring variety changed to:', this.value);
                checkVarietalSuccessionComplete2V();
            });
            console.log('✓ Spring variety listener added');
        }
        
        console.log('✅ 2-variety succession listeners set up');
    }

    function checkVarietalSuccessionComplete2V() {
        const earlySelect = document.getElementById('earlyVarietySelect2V');
        const midSelect = document.getElementById('midVarietySelect2V');
        const checkbox = document.getElementById('useVarietalSuccession');
        
        console.log('🔍 checkVarietalSuccessionComplete2V called');
        console.log('📊 Dropdown elements:', {
            early: !!earlySelect,
            mid: !!midSelect,
            checkbox: !!checkbox,
            checked: checkbox?.checked
        });
        
        if (!checkbox?.checked) {
            console.log('⏸️ Checkbox not checked, skipping');
            return;
        }
        
        const earlyValue = earlySelect?.value || '';
        const midValue = midSelect?.value || '';
        
        console.log('📊 Dropdown values:', {
            early: earlyValue,
            mid: midValue
        });
        
        const hasBoth = earlyValue && midValue;
        console.log(`🔍 2-variety succession check - Early: ${!!earlyValue}, Mid: ${!!midValue}, hasBoth: ${hasBoth}`);
        
        if (hasBoth) {
            console.log('✅ Both varieties selected, loading variety info and recalculating...');
            
            // Load both variety descriptions for the carousel
            loadVarietalSuccessionInfo2V(earlyValue, midValue);
            
            // Proceed with calculation
            calculateSuccessionPlan();
        } else {
            console.log('⏳ Waiting for both varieties to be selected...');
            
            // Hide varietal succession controls if not all selected
            const controls = document.getElementById('varietalSuccessionControls');
            if (controls) {
                controls.style.display = 'none';
            }
        }
    }

    async function loadVarietalSuccessionInfo2V(earlyId, midId) {
        console.log('🔄 Loading variety info for 2 varieties:', { earlyId, midId });
        
        try {
            // Fetch both varieties in parallel
            const [earlyData, midData] = await Promise.all([
                fetchVarietyInfo(earlyId),
                fetchVarietyInfo(midId)
            ]);
            
            varietalSuccessionData = [
                { type: 'early', data: earlyData, label: 'Autumn/Winter Variety' },
                { type: 'mid', data: midData, label: 'Spring Variety' }
            ];
            
            console.log('✅ Both varieties loaded:', varietalSuccessionData);
            
            // Show the carousel controls
            const controls = document.getElementById('varietalSuccessionControls');
            if (controls) {
                controls.style.display = 'flex !important';
                controls.classList.remove('d-none');
                controls.setAttribute('style', 'display: flex !important;');
            }
            
            // Display the first variety
            currentVarietyIndex = 0;
            displayVarietyFromCarousel2V(0);
            
        } catch (error) {
            console.error('❌ Error loading 2-variety succession info:', error);
        }
    }

    function displayVarietyFromCarousel2V(index) {
        if (!varietalSuccessionData || varietalSuccessionData.length === 0) {
            console.warn('⚠️ No varietal succession data available');
            return;
        }
        
        const varietyInfo = varietalSuccessionData[index];
        console.log(`📱 Displaying variety ${index + 1}/2:`, varietyInfo.label, varietyInfo.data?.name);
        
        // Set flag to prevent cascade when switching carousel slides
        window.__suppressPlantingMethodChange = true;
        
        // Update the variety display using the correct function
        displayVarietyInfo(varietyInfo.data);
        
        // Clear flag after a brief delay to allow event to be suppressed
        setTimeout(() => {
            window.__suppressPlantingMethodChange = false;
        }, 100);
        
        // Update carousel badge
        const badge = document.getElementById('currentVarietyBadge');
        if (badge) {
            badge.textContent = `${varietyInfo.label} (${index + 1}/2)`;
            badge.className = `badge ${index === 0 ? 'bg-warning' : 'bg-success'}`;
        }
        
        // Update button labels
        const prevLabel = document.getElementById('prevVarietyLabel');
        const nextLabel = document.getElementById('nextVarietyLabel');
        
        if (prevLabel) {
            const prevIndex = index === 0 ? 1 : 0;
            const prevLabelText = varietalSuccessionData[prevIndex].label.includes('Autumn') ? 'Autumn/Winter' : 'Spring';
            prevLabel.textContent = prevLabelText;
        }
        
        if (nextLabel) {
            const nextIndex = (index + 1) % 2;
            const nextLabelText = varietalSuccessionData[nextIndex].label.includes('Autumn') ? 'Autumn/Winter' : 'Spring';
            nextLabel.textContent = nextLabelText;
        }
    }

    function updateVarietalSuccessionSummary2V() {
        const earlyCount = parseInt(document.getElementById('earlyBedsCount2V')?.value) || 1;
        const midCount = parseInt(document.getElementById('midBedsCount2V')?.value) || 1;
        const totalCount = earlyCount + midCount;
        
        document.getElementById('earlyCountDisplay2V').textContent = earlyCount;
        document.getElementById('midCountDisplay2V').textContent = midCount;
        document.getElementById('totalSuccessionsDisplay2V').textContent = totalCount;
        
        console.log(`📊 Updated 2-variety succession summary: ${totalCount} total (${earlyCount} autumn/winter + ${midCount} spring)`);
    }

    function recalculateVarietalSuccession2V() {
        console.log('🔄 Recalculating 2-variety succession plan with updated bed counts...');
        
        // Validate that both varieties are selected
        const earlyVarietyId = document.getElementById('earlyVarietySelect2V')?.value;
        const midVarietyId = document.getElementById('midVarietySelect2V')?.value;
        
        if (!earlyVarietyId || !midVarietyId) {
            showToast('Please select both varieties (autumn/winter and spring) before recalculating', 'warning');
            return;
        }
        
        // Check harvest window is set
        if (!harvestWindowData.userStart || !harvestWindowData.userEnd) {
            showToast('Please set harvest window dates before recalculating', 'warning');
            return;
        }
        
        // Trigger the succession plan calculation
        calculateSuccessionPlan();
        
        showToast('Recalculating succession plan with updated bed counts...', 'info');
    }

    // ============================================================================
    // END 2-VARIETY SUCCESSION FUNCTIONS
    // ============================================================================

    function setupDragFunctionality() {
        // Try to find the timeline element (could be harvestTimeline, timeline-container, or farmos-timeline-container)
        let timeline = document.getElementById('harvestTimeline');
        if (!timeline) {
            timeline = document.querySelector('.timeline-container');
        }
        if (!timeline) {
            timeline = document.querySelector('.farmos-timeline-container');
        }
        if (!timeline) {
            console.warn('⚠️ No timeline element found for drag setup - drag functionality disabled');
            return;
        }

        console.log('✅ Setting up drag functionality on timeline:', timeline);        // Cache rect to reduce forced reflow
        let timelineRect = null;
        const computeRect = () => { timelineRect = timeline.getBoundingClientRect(); };
        computeRect();
        // Use ResizeObserver when available to avoid global resize/layout thrash
        if (window.ResizeObserver) {
            const ro = new ResizeObserver(() => computeRect());
            ro.observe(timeline);
        } else {
            window.addEventListener('resize', computeRect);
        }

        // rAF throttle for mousemove
        let pending = false;
        let lastEvent = null;
        const onMouseMove = (e) => {
            lastEvent = e;
            if (pending) return;
            pending = true;
            requestAnimationFrame(() => {
                pending = false;
                if (!lastEvent) return;
                handleMouseMove(lastEvent, timelineRect);
            });
        };

        // Handle mouse events for drag handles
        timeline.addEventListener('mousedown', handleMouseDown, { passive: false });
        document.addEventListener('mousemove', onMouseMove, { passive: false });
        document.addEventListener('mouseup', handleMouseUp, { passive: false });
        
        console.log('✅ Drag event listeners attached');
    }

    function initializeHarvestBar() {
        // Set default dates and initialize the harvest bar
        setDefaultDates();
        setupDragFunctionality();
        // Ensure AI max window overlay exists under the bar
        const timeline = document.getElementById('harvestTimeline');
        if (timeline && !document.getElementById('aiMaxWindowBand')) {
            const band = document.createElement('div');
            band.id = 'aiMaxWindowBand';
            band.className = 'position-absolute';
            band.style.top = '38px';
            band.style.height = '26px';
            band.style.left = '0%';
            band.style.width = '0%';
            band.style.borderRadius = '6px';
            band.style.background = 'rgba(33, 150, 243, 0.15)';
            band.style.border = '1px dashed rgba(33, 150, 243, 0.4)';
            band.style.pointerEvents = 'none';
            timeline.appendChild(band);
        }
        console.log('✅ Harvest bar initialized with default dates');
    }

    function setDefaultDates() {
        // Get selected planning year and season
        const planningYear = document.getElementById('planningYear').value;
        const planningSeason = document.getElementById('planningSeason').value;
        
        let startDate, endDate;
        
        // Set dates based on selected season and year
        switch(planningSeason) {
            case 'spring':
                startDate = new Date(planningYear, 2, 15); // March 15
                endDate = new Date(planningYear, 4, 15);   // May 15
                break;
            case 'summer':
                startDate = new Date(planningYear, 5, 15); // June 15
                endDate = new Date(planningYear, 7, 15);   // August 15
                break;
            case 'fall':
                startDate = new Date(planningYear, 8, 15); // September 15
                endDate = new Date(planningYear, 10, 15);  // November 15
                break;
            case 'winter':
                startDate = new Date(planningYear, 11, 15); // December 15
                endDate = new Date(parseInt(planningYear) + 1, 1, 15); // February 15 next year
                break;
            default: // year-round
                startDate = new Date(planningYear, 2, 1);  // March 1
                endDate = new Date(planningYear, 10, 30);  // November 30
        }
        
        // Update harvestWindowData instead of removed inputs
        harvestWindowData.userStart = startDate.toISOString().split('T')[0];
        harvestWindowData.userEnd = endDate.toISOString().split('T')[0];
        
        // Update the timeline months to show the correct year
        updateTimelineMonths(parseInt(planningYear));
        
        // Update the drag bar to match
        updateDragBar();
        
        console.log(`📅 Set default dates for ${planningSeason} ${planningYear}: ${startDate.toDateString()} - ${endDate.toDateString()}`);
    }

    function updateTimelineMonths(year) {
        // Update the timeline months to show the correct year dates
        const monthsContainer = document.querySelector('.timeline-months');
        if (monthsContainer) {
            // The months are static labels, but we could enhance this to show actual dates
            // For now, just update the tooltip or data attributes if needed
            monthsContainer.setAttribute('data-year', year);
            console.log(`📆 Timeline updated for year ${year}`);
        }
    }

    function setupSeasonYearHandlers() {
        // Add event listeners for season and year changes
        document.getElementById('planningYear').addEventListener('change', function() {
            console.log('📅 Planning year changed to:', this.value);
            setDefaultDates();
            updateHarvestWindowDisplay(); // Update the new harvest window selector
        });
        
        document.getElementById('planningSeason').addEventListener('change', function() {
            console.log('🌱 Planning season changed to:', this.value);
            setDefaultDates();
        });
    }

    function setupCropVarietyHandlers() {
        // Add event listener for crop type changes
        document.getElementById('cropSelect').addEventListener('change', function() {
            console.log('🌾 Crop type changed to:', this.value);
            // Filter varieties based on selected crop type
            filterVarietiesByCrop(this.value);
            // Clear variety selection
            const varietySelect = document.getElementById('varietySelect');
            varietySelect.value = '';
            
            // Clear varietal succession data when crop changes
            const varietalCheckbox = document.getElementById('useVarietalSuccession');
            if (varietalCheckbox && varietalCheckbox.checked) {
                varietalCheckbox.checked = false;
                const varietiesSection = document.getElementById('varietalSuccessionVarieties');
                if (varietiesSection) {
                    varietiesSection.style.display = 'none';
                }
                console.log('🧹 Cleared varietal succession data for new crop');
            }
            
            // Clear the dropdown values
            const earlySelect = document.getElementById('earlyVarietySelect');
            const midSelect = document.getElementById('midVarietySelect');
            const lateSelect = document.getElementById('lateVarietySelect');
            if (earlySelect) earlySelect.value = '';
            if (midSelect) midSelect.value = '';
            if (lateSelect) lateSelect.value = '';
            
            handleVarietySelection(null);
        });

        // Add event listener for variety changes
        document.getElementById('varietySelect').addEventListener('change', function() {
            console.log('🥕 Variety changed to:', this.value);
            handleVarietySelection(this.value);
        });
        
        // Note: Varietal succession dropdown listeners are set up in setupVarietalSuccessionListeners()
        // which is called when the dropdowns are populated
    }

    function filterVarietiesByCrop(cropTypeId) {
        const varietySelect = document.getElementById('varietySelect');
        const options = varietySelect.querySelectorAll('option');

        console.log(`🔍 Filtering varieties for crop type ID: "${cropTypeId}"`);
        console.log(`📊 Total variety options: ${options.length}`);

        let visibleCount = 0;
        options.forEach(option => {
            if (!option.value) {
                // Keep the "Select a variety..." option
                option.style.display = 'block';
                return;
            }

            const optionCropType = option.getAttribute('data-crop');
            const optionName = option.getAttribute('data-name');
            const match = !cropTypeId || optionCropType === cropTypeId;
            
            console.log(`  Option: "${optionName}" (value: ${option.value}) - data-crop: "${optionCropType}" - Match: ${match}`);
            
            if (match) {
                option.style.display = 'block';
                visibleCount++;
            } else {
                option.style.display = 'none';
            }
        });

        console.log(`✅ Filtered varieties for crop type: ${cropTypeId}, visible varieties: ${visibleCount}`);
    }

    function handleMouseDown(e) {
        console.log('🖱️ Mouse down event triggered', e.target);
        
        const handle = e.target.closest('.drag-handle');
        const dragBar = document.getElementById('dragHarvestBar');
        
        if (!handle) {
            // Check if clicking on the bar itself
            const bar = e.target.closest('.drag-harvest-bar');
            if (bar && dragBar) {
                console.log('🟢 Dragging whole bar');
                isDragging = true;
                dragHandle = 'whole';
                dragStartX = e.clientX;
                initialBarLeft = parseFloat(dragBar.style.left) || 20;
                initialBarWidth = parseFloat(dragBar.style.width) || 40;
                e.preventDefault();
                document.body.style.cursor = 'grabbing';
            } else {
                console.log('❌ No drag target found');
            }
            return;
        }
        
        console.log('🟢 Dragging handle:', handle.dataset.handle);
        isDragging = true;
        dragHandle = handle.dataset.handle;
        dragStartX = e.clientX;
        
        if (dragBar) {
            initialBarLeft = parseFloat(dragBar.style.left) || 20;
            initialBarWidth = parseFloat(dragBar.style.width) || 40;
        }
        
        e.preventDefault();
        e.stopPropagation();
        document.body.style.cursor = 'grabbing';
    }

    function handleMouseMove(e, cachedRect = null) {
        if (!isDragging || !dragHandle) return;
        
        const timeline = document.getElementById('harvestTimeline');
        const rect = cachedRect || timeline.getBoundingClientRect();
        const timelineWidth = rect.width - 40; // Account for padding
        
        // Calculate the delta movement in pixels
        const deltaX = e.clientX - dragStartX;
        // Convert to percentage
        const deltaPercentage = (deltaX / timelineWidth) * 100;
        
        if (dragHandle === 'whole') {
            // Move the entire bar by the delta amount
            const dragBar = document.getElementById('dragHarvestBar');
            const newLeft = Math.max(0, Math.min(100 - initialBarWidth, initialBarLeft + deltaPercentage));
            
            dragBar.style.left = newLeft + '%';
            dragBar.style.width = initialBarWidth + '%';
            updateDateDisplays();
            updateDateInputsFromBar();
        } else {
            // For handle dragging, calculate the new position based on mouse position
            const mouseX = e.clientX - rect.left - 20; // Account for padding
            const percentage = Math.max(0, Math.min(100, (mouseX / timelineWidth) * 100));
            updateHandlePosition(dragHandle, percentage);
        }
        
        e.preventDefault();
    }

    function handleMouseUp(e) {
        if (isDragging) {
            isDragging = false;
            dragHandle = null;
            document.body.style.cursor = 'default';
            
            // Final update of date inputs
            updateDateInputsFromBar();
        }
    }

    function handleTouchStart(e) {
        const handle = e.target.closest('.drag-handle');
        if (!handle) return;
        
        const touch = e.touches[0];
        handleMouseDown({ target: handle, clientX: touch.clientX, preventDefault: () => e.preventDefault() });
    }

    function handleTouchMove(e) {
        if (!isDragging) return;
        const touch = e.touches[0];
        handleMouseMove({ clientX: touch.clientX, preventDefault: () => e.preventDefault() });
    }

    function handleTouchEnd(e) {
        handleMouseUp(e);
    }

    function updateHandlePosition(handle, percentage) {
        const dragBar = document.getElementById('dragHarvestBar');
        if (!dragBar) return;
        
        const currentLeft = parseFloat(dragBar.style.left) || 20;
        const currentWidth = parseFloat(dragBar.style.width) || 40;
        const currentRight = currentLeft + currentWidth;
        
        if (handle === 'start') {
            // Move start handle, adjust bar position and width
            const maxLeft = currentRight - 5; // Minimum 5% width
            const newLeft = Math.min(percentage, maxLeft);
            const newWidth = currentRight - newLeft;
            
            dragBar.style.left = newLeft + '%';
            dragBar.style.width = newWidth + '%';
        } else if (handle === 'end') {
            // Move end handle, adjust bar width only
            const minRight = currentLeft + 5; // Minimum 5% width
            const newRight = Math.max(percentage, minRight);
            const newWidth = newRight - currentLeft;
            
            dragBar.style.width = newWidth + '%';
        }
        
        updateDateDisplays();
        updateDateInputsFromBar();
    }

    function updateDateDisplays() {
        const dragBar = document.getElementById('dragHarvestBar');
        const left = parseFloat(dragBar.style.left) || 20;
        const width = parseFloat(dragBar.style.width) || 40;
        const right = left + width;
        
        const startDate = percentageToDate(left);
        const endDate = percentageToDate(right);
        
        document.getElementById('startDateDisplay').textContent = startDate.toLocaleDateString();
        document.getElementById('endDateDisplay').textContent = endDate.toLocaleDateString();
    }

    function percentageToDate(percentage) {
        const planningYear = document.getElementById('planningYear').value || new Date().getFullYear();
        const yearStart = new Date(planningYear, 0, 1); // January 1st of planning year
        const yearEnd = new Date(planningYear, 11, 31); // December 31st of planning year
        
        const totalDays = (yearEnd - yearStart) / (1000 * 60 * 60 * 24);
        const dayOfYear = (percentage / 100) * totalDays;
        
        const resultDate = new Date(yearStart);
        resultDate.setDate(yearStart.getDate() + Math.round(dayOfYear));
        
        return resultDate;
    }

    function dateToPercentage(date) {
        const planningYear = document.getElementById('planningYear').value || date.getFullYear();
        const yearStart = new Date(planningYear, 0, 1); // January 1st of planning year
        const yearEnd = new Date(planningYear, 11, 31); // December 31st of planning year
        
        // Handle dates that extend into the next year (for extended harvest windows)
        let adjustedDate = date;
        if (date.getFullYear() > planningYear) {
            // If date is in next year, treat it as December 31st of current year for percentage calculation
            adjustedDate = new Date(planningYear, 11, 31);
        } else if (date.getFullYear() < planningYear) {
            // If date is in previous year, treat it as January 1st of current year
            adjustedDate = new Date(planningYear, 0, 1);
        }
        
        const totalDays = (yearEnd - yearStart) / (1000 * 60 * 60 * 24);
        const dayOfYear = (adjustedDate - yearStart) / (1000 * 60 * 60 * 24);
        
        const percentage = Math.max(0, Math.min(100, (dayOfYear / totalDays) * 100));
        return percentage;
    }

    function updateDragBar() {
        // Use harvestWindowData instead of removed input elements
        const harvestStart = harvestWindowData.userStart;
        const harvestEnd = harvestWindowData.userEnd;
        
        if (!harvestStart || !harvestEnd) return;
        
        const startDate = new Date(harvestStart);
        const endDate = new Date(harvestEnd);
        
        const startPercentage = dateToPercentage(startDate);
        const endPercentage = dateToPercentage(endDate);
        const width = Math.max(5, endPercentage - startPercentage); // Min 5% width
        
        const dragBar = document.getElementById('dragHarvestBar');
        if (dragBar) {
            requestAnimationFrame(() => {
                dragBar.style.left = startPercentage + '%';
                dragBar.style.width = width + '%';
                dragBar.style.display = 'block';
                updateDateDisplays();
                updateDateInputsFromBar();
            });
        }
    }

    function updateDateInputsFromBar() {
        const dragBar = document.getElementById('dragHarvestBar');
        if (!dragBar) return;
        
        const left = parseFloat(dragBar.style.left) || 20;
        const width = parseFloat(dragBar.style.width) || 40;
        const right = left + width;
        
        const startDate = percentageToDate(left);
        const endDate = percentageToDate(right);
        
        // Update the form inputs
        const startInput = document.getElementById('harvestStart');
        const endInput = document.getElementById('harvestEnd');
        
        if (startInput) startInput.value = startDate.toISOString().split('T')[0];
        if (endInput) endInput.value = endDate.toISOString().split('T')[0];
        
        // Update harvestWindowData so succession count recalculates
        harvestWindowData.userStart = startDate.toISOString().split('T')[0];
        harvestWindowData.userEnd = endDate.toISOString().split('T')[0];
        
        console.log('📊 Harvest bar moved - updating succession count:', {
            start: harvestWindowData.userStart,
            end: harvestWindowData.userEnd
        });
        
        // Recalculate succession count based on new harvest window
        updateSuccessionImpact();
    }

    // Extend harvest window by maximum 20%
    // Smart harvest window presets
    function useMaximumHarvestWindow() {
        if (!harvestWindowData.maxStart || !harvestWindowData.maxEnd) {
            console.warn('❌ No maximum harvest window data available');
            return;
        }
        
        const startInput = document.getElementById('harvestStart');
        const endInput = document.getElementById('harvestEnd');
        
        if (!startInput || !endInput) {
            console.warn('❌ Harvest window inputs not found');
            return;
        }
        
        // Set to maximum possible window
        startInput.value = harvestWindowData.maxStart;
        endInput.value = harvestWindowData.maxEnd;
        
        // Update user selection in global data
        harvestWindowData.userStart = harvestWindowData.maxStart;
        harvestWindowData.userEnd = harvestWindowData.maxEnd;
        
        // Update UI
        updateDragBar();
        updateHarvestWindowDisplay();
        
        console.log('✅ Set to maximum harvest window:', harvestWindowData.maxStart, '-', harvestWindowData.maxEnd);
        
        // Show notification
        showHarvestWindowNotification('Using maximum possible harvest window', 'success');
    }

    function useAIRecommendedWindow() {
        if (!harvestWindowData.aiStart || !harvestWindowData.aiEnd) {
            console.warn('❌ No AI recommended window data available');
            return;
        }
        
        const startInput = document.getElementById('harvestStart');
        const endInput = document.getElementById('harvestEnd');
        
        if (!startInput || !endInput) {
            console.warn('❌ Harvest window inputs not found');
            return;
        }
        
        // Set to AI recommended window (80% of maximum)
        startInput.value = harvestWindowData.aiStart;
        endInput.value = harvestWindowData.aiEnd;
        
        // Update user selection in global data
        harvestWindowData.userStart = harvestWindowData.aiStart;
        harvestWindowData.userEnd = harvestWindowData.aiEnd;
        
        // Update UI
        updateDragBar();
        updateHarvestWindowDisplay();
        
        console.log('✅ Set to AI recommended window:', harvestWindowData.aiStart, '-', harvestWindowData.aiEnd);
        
        // Show notification
        showHarvestWindowNotification('Using AI recommended harvest window (80% of maximum)', 'primary');
    }

    function useConservativeWindow() {
        if (!harvestWindowData.maxStart || !harvestWindowData.maxEnd) {
            console.warn('❌ No harvest window data available');
            return;
        }
        
        const startInput = document.getElementById('harvestStart');
        const endInput = document.getElementById('harvestEnd');
        
        if (!startInput || !endInput) {
            console.warn('❌ Harvest window inputs not found');
            return;
        }
        
        // Conservative = 60% of maximum duration from max start
        const maxStartDate = new Date(harvestWindowData.maxStart);
        const maxEndDate = new Date(harvestWindowData.maxEnd);
        const maxDuration = maxEndDate - maxStartDate;
        const conservativeDuration = maxDuration * 0.6;
        const conservativeEndDate = new Date(maxStartDate.getTime() + conservativeDuration);
        
        // Set conservative window
        startInput.value = harvestWindowData.maxStart;
        endInput.value = conservativeEndDate.toISOString().split('T')[0];
        
        // Update user selection in global data
        harvestWindowData.userStart = harvestWindowData.maxStart;
        harvestWindowData.userEnd = conservativeEndDate.toISOString().split('T')[0];
        
        // Update UI
        updateDragBar();
        updateHarvestWindowDisplay();
        
        console.log('✅ Set to conservative window:', harvestWindowData.userStart, '-', harvestWindowData.userEnd);
        
        // Show notification
        showHarvestWindowNotification('Using conservative harvest window (60% of maximum - safer for beginners)', 'info');
    }

    function showHarvestWindowNotification(message, type = 'success') {
        // Create toast notification
        const toastHTML = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true" style="position: fixed; top: 80px; right: 20px; z-index: 9999;">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-check-circle me-2"></i> ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        // Append to body
        const toastContainer = document.createElement('div');
        toastContainer.innerHTML = toastHTML;
        document.body.appendChild(toastContainer);
        
        // Show toast
        const toastElement = toastContainer.querySelector('.toast');
        const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
        toast.show();
        
        // Remove from DOM after hidden
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastContainer.remove();
        });
    }

    // Reduce harvest window to minimum 1 week (kept for backward compatibility)
    function reduceHarvestWindow() {
        const startInput = document.getElementById('harvestStart');
        const endInput = document.getElementById('harvestEnd');
        
        if (!startInput || !endInput || !startInput.value) {
            console.warn('Cannot reduce harvest window: missing date inputs');
            return;
        }
        
        const startDate = new Date(startInput.value);
        const minEndDate = new Date(startDate);
        minEndDate.setDate(minEndDate.getDate() + 7); // Minimum 1 week
        
        endInput.value = minEndDate.toISOString().split('T')[0];
        updateDragBar();
        
        // Update display
        const aiHarvestDetails = document.getElementById('aiHarvestDetails');
        if (aiHarvestDetails) {
            let existingHTML = aiHarvestDetails.innerHTML;
            existingHTML = existingHTML.replace(
                /<div class="text-muted small mt-2">[\s\S]*?<\/div>/,
                `<div class="alert alert-info small mt-2">
                    <i class="fas fa-info-circle"></i> Reduced to minimum 1-week harvest window
                </div>
                <div class="text-muted small mt-2">
                    <i class="fas fa-clock"></i> Reduced ${new Date().toLocaleTimeString()}
                </div>`
            );
            aiHarvestDetails.innerHTML = existingHTML;
        }
        
        console.log('📉 Reduced harvest window to 1 week');
    }

    // Reset harvest window to AI-calculated maximum
    function resetHarvestWindow() {
        // Re-run the AI calculation to get the maximum window
        calculateAIHarvestWindow();
        console.log('🔄 Reset harvest window to AI maximum');
    }

    // Helper function to adjust harvest dates to the selected planning year
    function adjustHarvestDatesToSelectedYear(harvestInfo) {
        if (!harvestInfo) return harvestInfo;
        
        const selectedYear = document.getElementById('planningYear')?.value || new Date().getFullYear();
        
        if (harvestInfo.maximum_start) {
            const parts = harvestInfo.maximum_start.split('-');
            if (parts.length === 3) {
                harvestInfo.maximum_start = `${selectedYear}-${parts[1]}-${parts[2]}`;
                console.log(`📅 Adjusted JSON start date to selected year: ${harvestInfo.maximum_start}`);
            }
        }
        
        if (harvestInfo.maximum_end) {
            const parts = harvestInfo.maximum_end.split('-');
            if (parts.length === 3) {
                harvestInfo.maximum_end = `${selectedYear}-${parts[1]}-${parts[2]}`;
                console.log(`📅 Adjusted JSON end date to selected year: ${harvestInfo.maximum_end}`);
            }
        }
        
        if (harvestInfo.yield_peak) {
            const parts = harvestInfo.yield_peak.split('-');
            if (parts.length === 3) {
                harvestInfo.yield_peak = `${selectedYear}-${parts[1]}-${parts[2]}`;
                console.log(`📅 Adjusted JSON yield peak date to selected year: ${harvestInfo.yield_peak}`);
            }
        }
        
        return harvestInfo;
    }

    // Calculate AI harvest window - main function for getting maximum possible harvest
    async function calculateAIHarvestWindow() {
        let harvestInfo = null; // Declare at function level
        let cropName = null;
        let varietyName = null;
        let contextPayload = null;

        try {
            console.log('🤖 calculateAIHarvestWindow() called');

            // Abort previous in-flight calculation before starting a new one
            if (__aiCalcController) {
                try { __aiCalcController.abort(); } catch (_) {}
            }
            __aiCalcController = new AbortController();

            const cropSelect = document.getElementById('cropSelect');
            const varietySelect = document.getElementById('varietySelect');

            if (!cropSelect || !cropSelect.value) {
                console.log('❌ No crop selected, aborting AI calculation');
                return;
            }

            cropName = cropSelect.options[cropSelect.selectedIndex].text;
            varietyName = varietySelect && varietySelect.value ? varietySelect.options[varietySelect.selectedIndex].text : null;
            const varietyId = varietySelect && varietySelect.value ? varietySelect.value : null;

            // Try to fetch full variety metadata from farmOS to pass to AI
            let varietyMeta = null;
            if (varietyId) {
                try {
                    const metaResp = await fetch(`${API_BASE}/varieties/${varietyId}?_cb=${CACHE_BUSTER}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: __aiCalcController.signal });
                    if (metaResp.ok) varietyMeta = await metaResp.json();
                } catch (e) {
                    console.warn('Could not fetch variety metadata for AI context:', e);
                }
            }

            contextPayload = {
                crop: cropName,
                variety: varietyName || null,
                variety_meta: varietyMeta,
                planning_year: document.getElementById('planningYear')?.value || new Date().getFullYear(),
                planning_season: document.getElementById('planningSeason')?.value || null,
                request_type: 'maximum_harvest_window'
            };

            // Build a concise prompt for harvest windows
            const prompt = `Calculate the maximum harvest window for ${cropName} ${varietyName || ''} in the UK for ${contextPayload.planning_year}.

Return ONLY a JSON object:
{
  "maximum_start": "YYYY-MM-DD",
  "maximum_end": "YYYY-MM-DD",
  "days_to_harvest": 60,
  "yield_peak": "YYYY-MM-DD",
  "notes": "Brief explanation",
  "extended_window": {"max_extension_days": 30, "risk_level": "low"}
}

Use these guidelines:
- Carrots: May 1 - December 31
- Beets: June 1 - December 31  
- Lettuce: March 1 - November 30
- Radishes: April 1 - October 31
- Onions: July 1 - September 30
- Brussels Sprouts: October 1 - March 31
- Broad Beans (Aquadulce, Bunyard's Exhibition - Autumn sown): October 15 - June 30 (sow Oct-Nov, harvest May-Jun)
- Broad Beans (Spring sown like The Sutton, Stereo): April 15 - July 31 (sow Feb-Apr, harvest Jun-Aug)
- Broad Beans (Duet - Extended season): May 1 - October 31 (sow Feb-Mar OR Jun-Jul for extended harvest)

For broad beans, if variety name contains "Aquadulce" or "Bunyard", use autumn sowing dates. If "Duet", use extended season window. Otherwise assume spring sowing.

Calculate for ${contextPayload.planning_year}.`;

            // Use same-origin Laravel route that exists in this app (chat endpoint)
            const chatUrl = window.location.origin + '/admin/farmos/succession-planning/chat';

            // Debug: log payload and endpoint
            console.log('🛰️ AI request ->', { chatUrl, prompt, context: contextPayload });
            console.log('🔍 Question text check:', {
                'contains_harvest_window': prompt.toLowerCase().includes('harvest window'),
                'contains_maximum_start': prompt.toLowerCase().includes('maximum_start'),
                'contains_json_object': prompt.toLowerCase().includes('json object'),
                'prompt_preview': prompt.substring(0, 200) + '...'
            });

            // Timeout to abort long-running requests
            const timeoutId = setTimeout(() => { try { __aiCalcController.abort(); } catch(_){} }, 60000); // 60s timeout

            const response = await fetch(chatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ 
                    message: prompt, 
                    context: contextPayload 
                }),
                signal: __aiCalcController.signal
            });

            clearTimeout(timeoutId);

            console.log('🛰️ AI response status:', response.status);

            if (!response.ok) {
                // Try to read body for debugging
                let text = '';
                try { text = await response.text(); } catch (e) { text = `<failed to read body: ${e}>`; }
                console.error('Failed to fetch AI response:', response.status, response.statusText, text);
                return;
            }

            const data = await response.json();
            console.log('🛰️ AI raw response:', data);
            console.log('🛰️ AI answer content:', data.answer);

            // Prefer structured harvest window from backend; else use AI answer parsing
            if (data && (data.maximum_start || data.optimal_window_days || data.peak_harvest_days)) {
                // Build a normalized harvestInfo object from structured backend response
                let maxStart = data.maximum_start || null;
                const duration = data.optimal_window_days || data.maximum_harvest_days || null;
                const peakDays = data.peak_harvest_days || null;
                let maxEnd = data.maximum_end || null;
                
                // Adjust dates to use the selected planning year
                const selectedYear = document.getElementById('planningYear')?.value || new Date().getFullYear();
                if (maxStart) {
                    const parts = maxStart.split('-');
                    if (parts.length === 3) {
                        maxStart = `${selectedYear}-${parts[1]}-${parts[2]}`;
                        console.log(`📅 Adjusted AI start date to selected year: ${maxStart}`);
                    }
                }
                if (maxEnd) {
                    const parts = maxEnd.split('-');
                    if (parts.length === 3) {
                        maxEnd = `${selectedYear}-${parts[1]}-${parts[2]}`;
                        console.log(`📅 Adjusted AI end date to selected year: ${maxEnd}`);
                    }
                }
                
                if (!maxEnd && maxStart && duration) {
                    const d = new Date(maxStart);
                    d.setDate(d.getDate() + Number(duration));
                    maxEnd = d.toISOString().split('T')[0];
                }
                harvestInfo = {
                    maximum_start: maxStart,
                    maximum_end: maxEnd,
                    days_to_harvest: peakDays, // display "Days to Harvest" as peak days to first harvest
                    extended_window: {
                        max_extension_days: Math.round((duration || peakDays || 0) * 0.2) || 14,
                        risk_level: 'moderate'
                    },
                    notes: Array.isArray(data.recommendations) ? data.recommendations.join('; ') : ''
                };
            } else {
                // Legacy path: parse AI free text answer
                try {
                    if (typeof data.answer === 'string') {
                        harvestInfo = JSON.parse(data.answer);
                        console.log('✅ Successfully parsed JSON from backend:', harvestInfo);
                    } else if (typeof data.answer === 'object') {
                        harvestInfo = data.answer;
                    } else {
                        throw new Error('Unexpected answer format');
                    }
                    
                    // Adjust parsed JSON dates to selected year
                    if (harvestInfo) {
                        harvestInfo = adjustHarvestDatesToSelectedYear(harvestInfo);
                    }
                } catch (e) {
                    console.warn('Failed to parse JSON from backend, falling back to text parsing:', e);
                    const aiText = String(data.answer || data.wisdom || 'No response');
                    harvestInfo = parseHarvestWindow(aiText, cropName, varietyName);
                }
            }

        } catch (error) {
            if (error.name === 'AbortError') {
                console.error('AI request timed out');
            } else {
                console.error('Error calculating AI harvest window:', error);
            }
            // Continue to fallback logic below
        }

        // Always provide fallback harvest windows if AI failed or returned incomplete data
        if (!harvestInfo || !harvestInfo.maximum_start || !harvestInfo.maximum_end) {
            console.log('🔄 Using fallback harvest window for:', cropName);

            // Simple fallback based on crop type
            const year = contextPayload.planning_year || new Date().getFullYear();
            let fallbackInfo = null;

            switch (cropName.toLowerCase()) {
                case 'carrots':
                case 'carrot':
                    fallbackInfo = {
                        maximum_start: `${year}-05-01`,
                        maximum_end: `${year}-12-31`,
                        days_to_harvest: 70,
                        yield_peak: `${year}-08-15`,
                        notes: 'Carrot harvest window (fallback)',
                        extended_window: { max_extension_days: 30, risk_level: 'low' }
                    };
                    break;
                case 'beets':
                case 'beetroot':
                    fallbackInfo = {
                        maximum_start: `${year}-06-01`,
                        maximum_end: `${year}-12-31`,
                        days_to_harvest: 60,
                        yield_peak: `${year}-09-15`,
                        notes: 'Beet harvest window (fallback)',
                        extended_window: { max_extension_days: 45, risk_level: 'low' }
                    };
                    break;
                case 'lettuce':
                    fallbackInfo = {
                        maximum_start: `${year}-03-01`,
                        maximum_end: `${year}-11-30`,
                        days_to_harvest: 45,
                        yield_peak: `${year}-06-15`,
                        notes: 'Lettuce harvest window (fallback)',
                        extended_window: { max_extension_days: 60, risk_level: 'moderate' }
                    };
                    break;
                case 'radish':
                case 'radishes':
                    fallbackInfo = {
                        maximum_start: `${year}-04-01`,
                        maximum_end: `${year}-10-31`,
                        days_to_harvest: 25,
                        yield_peak: `${year}-06-15`,
                        notes: 'Radish harvest window (fallback)',
                        extended_window: { max_extension_days: 30, risk_level: 'low' }
                    };
                    break;
                case 'onion':
                case 'onions':
                    fallbackInfo = {
                        maximum_start: `${year}-07-01`,
                        maximum_end: `${year}-09-30`,
                        days_to_harvest: 100,
                        yield_peak: `${year}-08-15`,
                        notes: 'Onion harvest window (fallback)',
                        extended_window: { max_extension_days: 15, risk_level: 'low' }
                    };
                    break;
                default:
                    fallbackInfo = {
                        maximum_start: `${year}-05-01`,
                        maximum_end: `${year}-10-31`,
                        days_to_harvest: 60,
                        yield_peak: `${year}-07-15`,
                        notes: 'Default harvest window (fallback)',
                        extended_window: { max_extension_days: 30, risk_level: 'moderate' }
                    };
            }

            harvestInfo = fallbackInfo;
            console.log('✅ Fallback harvest info applied:', harvestInfo);
        }

        // Display and apply the harvest information
        displayAIHarvestWindow(harvestInfo, cropName, varietyName);

        console.log('📊 Final harvestInfo:', harvestInfo);

        // Update the new harvest window selector with AI data
        updateHarvestWindowData(harvestInfo);

        // Force update the drag bar and timeline
        requestAnimationFrame(() => {
            updateDragBar();
            updateTimelineMonths(document.getElementById('planningYear').value || new Date().getFullYear());
            console.log('🔄 Drag bar and timeline updated for maximum harvest window');
        });

        console.log('✅ Harvest window calculation completed');
    }

    // Display AI harvest window information in the UI
    function displayAIHarvestWindow(harvestInfo, cropName, varietyName) {
        console.log('🎨 Displaying AI harvest window:', harvestInfo);
        
        const harvestWindowInfo = document.getElementById('harvestWindowInfo');
        const aiHarvestDetails = document.getElementById('aiHarvestDetails');
        
        if (!harvestWindowInfo || !aiHarvestDetails) {
            console.warn('AI harvest window display elements not found');
            return;
        }
        
        // Show the harvest window info section
        harvestWindowInfo.style.display = 'block';
        
        // Build the details HTML
        let detailsHTML = '';
        
        if (harvestInfo.maximum_start && harvestInfo.maximum_end) {
            const startDate = new Date(harvestInfo.maximum_start);
            const endDate = new Date(harvestInfo.maximum_end);
            const durationDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
            
            detailsHTML += `<div class="mb-2">
                <strong>Maximum Possible Harvest:</strong> ${startDate.toLocaleDateString()} - ${endDate.toLocaleDateString()}
                <br><small class="text-muted">Duration: ${durationDays} days</small>
            </div>`;
        }
        
        if (harvestInfo.days_to_harvest) {
            detailsHTML += `<div class="mb-2">
                <strong>Days to Harvest:</strong> ${harvestInfo.days_to_harvest} days
            </div>`;
        }
        
        if (harvestInfo.yield_peak) {
            const peakDate = new Date(harvestInfo.yield_peak);
            detailsHTML += `<div class="mb-2">
                <strong>Peak Yield:</strong> ${peakDate.toLocaleDateString()}
            </div>`;
        }
        
        if (harvestInfo.extended_window) {
            const extensionDays = harvestInfo.extended_window.max_extension_days || Math.round((harvestInfo.days_to_harvest || 60) * 0.2);
            const riskLevel = harvestInfo.extended_window.risk_level || 'moderate';
            
            detailsHTML += `<div class="mb-2">
                <strong>Extension Options:</strong> Up to ${extensionDays} days (${riskLevel} risk)
            </div>`;
        }
        
        if (harvestInfo.notes) {
            detailsHTML += `<div class="mb-2">
                <strong>Notes:</strong> ${harvestInfo.notes}
            </div>`;
        }
        
        // Add harvest window controls
        detailsHTML += `<div class="mt-3">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-success" onclick="extendHarvestWindow()">
                    <i class="fas fa-plus"></i> Extend 20%
                </button>
                <button type="button" class="btn btn-outline-warning" onclick="reduceHarvestWindow()">
                    <i class="fas fa-minus"></i> Reduce to 1 Week
                </button>
                <button type="button" class="btn btn-outline-info" onclick="resetHarvestWindow()">
                    <i class="fas fa-undo"></i> Reset to Max
                </button>
            </div>
        </div>`;
        
        // Add a timestamp
        detailsHTML += `<div class="text-muted small mt-2">
            <i class="fas fa-clock"></i> Calculated ${new Date().toLocaleTimeString()}
        </div>`;
        
        aiHarvestDetails.innerHTML = detailsHTML;

        // Render AI max window overlay band behind the drag bar
        try {
            const band = document.getElementById('aiMaxWindowBand');
            if (band && harvestInfo.maximum_start && harvestInfo.maximum_end) {
                const sPct = dateToPercentage(new Date(harvestInfo.maximum_start));
                const ePct = dateToPercentage(new Date(harvestInfo.maximum_end));
                band.style.left = Math.max(0, Math.min(100, sPct)) + '%';
                band.style.width = Math.max(0, Math.min(100, ePct - sPct)) + '%';
                band.style.display = 'block';
            }
        } catch (_) {}
        
        // Update AI chat context with harvest window information
        updateAIChatContext(harvestInfo, cropName, varietyName);
        
        // Show the analyze plan button
        const analyzeBtn = document.getElementById('analyzePlanBtn');
        if (analyzeBtn) {
            analyzeBtn.style.display = 'inline-block';
        }
        
        console.log('✅ AI harvest window displayed successfully');
    }

    // Fallback parser: try to extract YYYY-MM-DD dates and numbers from a free-text answer
    function parseHarvestWindow(answerText, cropName, varietyName) {
        try {
            if (!answerText || typeof answerText !== 'string') return null;

            const text = answerText.replace(/\s+/g, ' ').trim();
            const selectedYear = parseInt(document.getElementById('planningYear')?.value || new Date().getFullYear(), 10);
            
            const dateRegex = /(20\d{2})-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])/g; // YYYY-MM-DD
            const dates = [...text.matchAll(dateRegex)].map(m => m[0]);

            // Convert dates to use the selected planning year
            let maximum_start = null;
            let maximum_end = null;
            if (dates.length >= 2) {
                // Parse the month and day from the AI dates but use the selected year
                const startParts = dates[0].split('-');
                const endParts = dates[dates.length - 1].split('-');
                maximum_start = `${selectedYear}-${startParts[1]}-${startParts[2]}`;
                maximum_end = `${selectedYear}-${endParts[1]}-${endParts[2]}`;
                
                console.log(`📅 Adjusted AI dates to selected year ${selectedYear}: ${maximum_start} - ${maximum_end}`);
            }

            // Month name range detection (e.g., "June–November", "May to December")
            if (!maximum_start || !maximum_end) {
                const months = {
                    january: 0, february: 1, march: 2, april: 3, may: 4, june: 5,
                    july: 6, august: 7, september: 8, october: 9, november: 10, december: 11,
                    jan: 0, feb: 1, mar: 2, apr: 3, jun: 5, jul: 6, aug: 7, sep: 8, sept: 8, oct: 9, nov: 10, dec: 11
                };
                const monthPattern = /(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)/i;
                const rangePattern = new RegExp(`${monthPattern.source}\s*(?:-|–|—|to|through)\s*${monthPattern.source}`, 'i');
                const m = text.match(rangePattern);
                if (m && m[1] && m[2]) {
                    const year = parseInt(document.getElementById('planningYear')?.value || new Date().getFullYear(), 10);
                    const startMonth = months[m[1].toLowerCase()];
                    const endMonth = months[m[2].toLowerCase()];
                    if (startMonth != null && endMonth != null) {
                        const start = new Date(year, startMonth, 1);
                        const end = new Date(year, endMonth + 1, 0); // last day of end month
                        maximum_start = maximum_start || start.toISOString().split('T')[0];
                        maximum_end = maximum_end || end.toISOString().split('T')[0];
                    }
                }
            }

            // Handle phrases like "early/mid/late Month"
            if (!maximum_start || !maximum_end) {
                const emlPattern = /(early|mid|late)\s+(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)/ig;
                const monthsIdx = { jan:0,january:0,feb:1,february:1,mar:2,march:2,apr:3,april:3,may:4,jun:5,june:5,jul:6,july:6,aug:7,august:7,sep:8,sept:8,september:8,oct:9,october:9,nov:10,november:10,dec:11,december:11 };
                let match;
                const hits = [];
                while ((match = emlPattern.exec(text.toLowerCase())) !== null) {
                    const when = match[1];
                    const monKey = match[2];
                    const mIdx = monthsIdx[monKey] ?? monthsIdx[monKey.slice(0,3)];
                    if (mIdx != null) {
                        const year = parseInt(document.getElementById('planningYear')?.value || new Date().getFullYear(), 10);
                        let day = 15;
                        if (when === 'early') day = 5; else if (when === 'mid') day = 15; else if (when === 'late') day = 25;
                        const d = new Date(year, mIdx, day);
                        hits.push(d);
                    }
                }

                if (hits.length > 0) {
                    const sortedHits = hits.sort((a, b) => a - b);
                    maximum_start = maximum_start || sortedHits[0].toISOString().split('T')[0];
                    maximum_end = maximum_end || sortedHits[sortedHits.length - 1].toISOString().split('T')[0];
                }
            }

            // Extract numbers that might be days to harvest
            let dth = null;
            const numberRegex = /(\d{1,3})/g;
            const numbers = [...text.matchAll(numberRegex)].map(m => parseInt(m[1]));
            if (numbers.length > 0) {
                // Filter reasonable harvest days (30-300)
                const validNumbers = numbers.filter(n => n >= 30 && n <= 300);
                if (validNumbers.length > 0) {
                    dth = Math.min(...validNumbers); // Take the smallest reasonable number
                }
            }

            const yield_peak = maximum_start; // Assume peak is at start for simplicity
            const notes = text.length > 100 ? text.substring(0, 100) + '...' : text;

            const result = {
                maximum_start: maximum_start,
                maximum_end: maximum_end,
                days_to_harvest: dth || 60,
                yield_peak: yield_peak,
                notes: notes,
                extended_window: { max_extension_days: 30, risk_level: 'moderate' },
                crop: cropName || null,
                variety: varietyName || null
            };

            // Ensure at least something usable
            if (!result.maximum_start || !result.maximum_end) {
                // Provide a conservative synthetic window around the selected season defaults
                const year = parseInt(document.getElementById('planningYear')?.value || new Date().getFullYear(), 10);
                const start = new Date(year, 7, 1); // Aug 1
                const end = new Date(year, 10, 30); // Nov 30
                result.maximum_start = start.toISOString().split('T')[0];
                result.maximum_end = end.toISOString().split('T')[0];
                if (!result.days_to_harvest) result.days_to_harvest = 60;
            }

            return result;
        } catch (e) {
            console.warn('parseHarvestWindow failed:', e);
            return null;
        }
    }

    // Persist/restore state
    function savePlannerState() {
        try {
            const state = {
                // Don't save crop and variety selections - use placeholders instead
                // crop: document.getElementById('cropSelect')?.value || '',
                // variety: document.getElementById('varietySelect')?.value || '',
                year: document.getElementById('planningYear')?.value || '',
                season: document.getElementById('planningSeason')?.value || '',
                hStart: harvestWindowData.userStart || '',
                hEnd: harvestWindowData.userEnd || ''
            };
            localStorage.setItem('sp_state', JSON.stringify(state));
        } catch (_) {}
    }

    function restorePlannerState() {
        try {
            const raw = localStorage.getItem('sp_state');
            if (!raw) return;
            const s = JSON.parse(raw);
            if (s.year) document.getElementById('planningYear').value = s.year;
            if (s.season) document.getElementById('planningSeason').value = s.season;
            // Don't restore crop and variety selections - use placeholders instead
            // if (s.crop) {
            //     document.getElementById('cropSelect').value = s.crop;
            //     updateVarieties();
            //     if (s.variety) {
            //         const vSel = document.getElementById('varietySelect');
            //         const opt = Array.from(vSel.options).find(o => o.value === s.variety);
            //         if (opt) vSel.value = s.variety;
            //     }
            // }
            
            // ⚠️ DO NOT restore harvest window dates from localStorage
            // Those dates are stale from old testing (e.g., Nov 1 - Feb 28)
            // AI will calculate fresh harvest windows based on selected variety
            // if (s.hStart) harvestWindowData.userStart = s.hStart;
            // if (s.hEnd) harvestWindowData.userEnd = s.hEnd;
            
            console.log('📦 Restored planner state (year, season only - NOT harvest dates)');
        } catch (_) {}
    }
    
    // Clear stale localStorage harvest window data
    function clearStaleHarvestWindows() {
        try {
            const raw = localStorage.getItem('sp_state');
            if (raw) {
                const s = JSON.parse(raw);
                // Keep year and season, but remove harvest dates
                delete s.hStart;
                delete s.hEnd;
                localStorage.setItem('sp_state', JSON.stringify(s));
                console.log('🧹 Cleared stale harvest window dates from localStorage');
            }
        } catch (e) {
            console.warn('⚠️ Could not clear stale localStorage:', e);
        }
    }
    
    // Update AI chat context with current plan information
    function updateAIChatContext(harvestInfo, cropName, varietyName) {
        const contextDiv = document.getElementById('aiPlanContext');
        const detailsDiv = document.getElementById('planContextDetails');
        
        if (!contextDiv || !detailsDiv) return;
        
        let contextHTML = '<div class="mb-2">' +
            '<strong>Crop:</strong> ' + (cropName || 'Unknown') + '<br>' +
            '<strong>Variety:</strong> ' + (varietyName || 'Generic') +
        '</div>';
        
        if (harvestInfo.maximum_start && harvestInfo.maximum_end) {
            const startDate = new Date(harvestInfo.maximum_start);
            const endDate = new Date(harvestInfo.maximum_end);
            const durationDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));

            contextHTML += '<div class="mb-2">' +
                '<strong>Harvest Window:</strong> ' + startDate.toLocaleDateString() + ' - ' + endDate.toLocaleDateString() + '<br>' +
                '<small>Duration: ' + durationDays + ' days</small>' +
            '</div>';
        }

        if (harvestInfo.notes) {
            contextHTML += '<div class="mb-2">' +
                '<strong>AI Notes:</strong> <em>' + harvestInfo.notes + '</em>' +
            '</div>';
        }
        
        detailsDiv.innerHTML = contextHTML;
        contextDiv.style.display = 'block';
        
        console.log('📝 AI chat context updated with harvest window information');
    }
    
    // Test function to verify AI context integration
    window.testAIContext = function() {
        const context = getCurrentPlanContext();
        console.log('🧪 AI Context Test:', context);
        return context;
    };
    
    // Utility functions for notifications
    function showError(message) {
        console.error('❌ Error:', message);
        alert('Error: ' + message);
    }
    
    function showSuccess(message) {
        console.log('✅ Success:', message);
        alert('Success: ' + message);
    }
    
    function showLoading(show) {
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            if (show) {
                loadingOverlay.classList.remove('d-none');
            } else {
                loadingOverlay.classList.add('d-none');
            }
        }
    }
    
    function showToast(message, type = 'info') {
        console.log(`🍞 Toast (${type}):`, message);
        // For now, just use alert. You could enhance this with a proper toast system
        alert(`${type.toUpperCase()}: ${message}`);
    }

    // Sync FarmOS Varieties
    async function syncFarmOSVarieties() {
        const btn = document.getElementById('syncVarietiesBtn');
        const originalContent = btn.innerHTML;
        
        try {
            // Show loading state
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
            
            const response = await fetch('{{ route('admin.farmos.sync-varieties') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast('Plant varieties synced successfully from FarmOS!', 'success');
                console.log('✅ Sync output:', result.output);
            } else {
                showToast('Failed to sync varieties: ' + result.message, 'error');
            }
            
        } catch (error) {
            console.error('❌ Sync error:', error);
            showToast('Error syncing varieties from FarmOS', 'error');
        } finally {
            // Restore button
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }
    }

    // Week navigation functions
    // ----- Missing helpers (lightweight, safe fallbacks) -----
    // Quick connectivity check placeholder (non-blocking)
    async function testConnections() {
        try {
            updateAIStatus('checking', 'Verifying service…');
            // Optional: shallow ping to same-origin to avoid CORS; skip network to stay fast
            await new Promise(r => setTimeout(r, 100));
            updateAIStatus('online', 'AI ready');
        } catch (e) {
            console.warn('Connectivity check failed:', e);
            updateAIStatus('offline', 'Service unavailable');
        }
    }

    function setupAIStatusMonitoring() {
        // Initial status
        updateAIStatus('checking', 'Checking…');
        // Wire refresh button
        const btn = document.getElementById('refreshAIStatus');
        if (btn) btn.addEventListener('click', () => testConnections());
        // One initial check
        testConnections();
    }

    function updateAIStatus(status, details = '') {
        const light = document.getElementById('aiStatusLight');
        const text = document.getElementById('aiStatusText');
        const extra = document.getElementById('aiStatusDetails');
        if (light) {
            light.classList.remove('online', 'offline', 'checking');
            light.classList.add(status);
        }
        if (text) {
            text.textContent = status === 'online' ? 'AI Connected' : status === 'offline' ? 'AI Offline' : 'Checking AI service…';
        }
        if (extra) {
            extra.textContent = details || '';
        }
    }

    function getCurrentPlanContext() {
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        return {
            crop: cropSelect?.value || null,
            crop_name: cropSelect?.options[cropSelect.selectedIndex]?.text || null,
            variety: varietySelect?.value || null,
            variety_name: varietySelect?.options[varietySelect.selectedIndex]?.text || null,
            harvest_start: harvestWindowData.userStart || null,
            harvest_end: harvestWindowData.userEnd || null,
            plan: currentSuccessionPlan || null
        };
    }

    function addToastStyles() {
        // Stub: using alert-based toasts; no-op to avoid ReferenceError
        return;
    }

    function addKeyboardNavigation() {
        // Lightweight: number keys 1-9 switch tabs if present
        document.addEventListener('keydown', (e) => {
            if (e.altKey || e.ctrlKey || e.metaKey) return;
            const n = parseInt(e.key, 10);
            if (!isNaN(n) && n >= 1 && n <= 9) {
                const btns = document.querySelectorAll('.tab-button');
                const target = btns[n - 1];
                if (target) target.click?.();
            }
        });
    }

    function askQuickQuestion(type) {
        const input = document.getElementById('aiChatInput');
        if (!input) return;
        const context = getCurrentPlanContext();
        const cropName = context.crop_name || 'my crop';
        const topics = {
            'succession-timing': `What is the optimal succession timing for ${cropName}?`,
            'companion-plants': `What are good companion plants for ${cropName}?`,
            'lunar-timing': `Any lunar cycle timing tips for ${cropName}?`,
            'harvest-optimization': `How can I optimize the harvest window for ${cropName}?`
        };
        input.value = topics[type] || `Give me quick succession tips for ${cropName}`;
        askHolisticAI();
    }

    // AI request state to prevent duplicate rapid sends
    let __aiInFlight = false;
    let __aiLastMsg = '';
    let __aiLastSentAt = 0;

    // Send message to AI chat (throttled + in-flight guard)
    async function askHolisticAI() {
        const chatInput = document.getElementById('aiChatInput');
        const message = chatInput.value.trim();
        
        if (!message) {
            console.warn('No message to send to AI');
            return;
        }

        const now = Date.now();
        if (__aiInFlight) {
            console.warn('AI request already in progress; skipped');
            return;
        }
        if (message === __aiLastMsg && (now - __aiLastSentAt) < 1500) {
            console.warn('Duplicate AI message throttled');
            return;
        }
        __aiInFlight = true;
        __aiLastMsg = message;
        __aiLastSentAt = now;
        
        console.log('🤖 Sending message to AI:', message);
        
        // Display the user's question in the chat area
        const aiResponseArea = document.getElementById('aiResponseArea');
        if (aiResponseArea) {
            const userMessageDiv = document.createElement('div');
            userMessageDiv.className = 'ai-response mt-3';
            userMessageDiv.innerHTML = `
                <div class="d-flex align-items-start">
                    <div class="flex-shrink-0 me-2">
                        <i class="fas fa-user text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <strong>You:</strong><br>
                        ${message.replace(/\n/g, '<br>')}
                    </div>
                </div>
            `;
            
            // Insert the user message at the top
            aiResponseArea.insertBefore(userMessageDiv, aiResponseArea.firstChild);
            
            // Hide the welcome message if it exists
            const welcomeMessage = document.getElementById('welcomeMessage');
            if (welcomeMessage) {
                welcomeMessage.style.display = 'none';
            }
            
            // Clear the input
            chatInput.value = '';
        }
        
        // Show loading state
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.classList.remove('d-none');
        }
        // Disable AI-related buttons while request is in-flight
        const aiButtons = Array.from(document.querySelectorAll('button[onclick="askHolisticAI()"], #analyzePlanBtn'));
        aiButtons.forEach(b => { try { b.disabled = true; } catch(_){} });
        
        // Abort previous chat request if any, then create a new controller
        if (__aiChatController) {
            try { __aiChatController.abort(); } catch(_){}
        }
        __aiChatController = new AbortController();
        const chatTimeoutId = setTimeout(() => { try { __aiChatController.abort(); } catch(_){} }, 10000);

        try {
            const requestBody = { question: message };
            console.log('📤 Sending chat request:', requestBody);
            
            const response = await fetch(window.location.origin + '/admin/farmos/succession-planning/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(requestBody),
                signal: __aiChatController.signal
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                console.error('❌ Server error:', response.status, errorData);
                throw new Error(`HTTP ${response.status}: ${errorData.message || response.statusText}`);
            }
            
            const data = await response.json();
            console.log('🤖 AI response received:', data);
            console.log('📝 Answer field:', data.answer);
            console.log('✅ Has answer?', !!data.answer);
            
            // Display the AI response in the chat area
            if (data.answer) {
                console.log('💬 Displaying AI answer in chat area');
                const aiResponseArea = document.getElementById('aiResponseArea');
                if (aiResponseArea) {
                    console.log('✅ Found aiResponseArea element');
                    // Create a new response element
                    const responseDiv = document.createElement('div');
                    responseDiv.className = 'ai-response mt-3';
                    responseDiv.innerHTML = `
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0 me-2">
                                <i class="fas fa-robot text-warning"></i>
                            </div>
                            <div class="flex-grow-1">
                                <strong>AI Advisor:</strong><br>
                                ${data.answer.replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    `;
                    
                    console.log('📦 Created response div, inserting into DOM');
                    // Insert the AI response right after the user message (second position)
                    const firstChild = aiResponseArea.firstChild;
                    if (firstChild) {
                        aiResponseArea.insertBefore(responseDiv, firstChild.nextSibling);
                    } else {
                        aiResponseArea.appendChild(responseDiv);
                    }
                    
                    console.log('✅ AI response inserted into DOM');
                    // Scroll to the new response
                    responseDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    console.error('❌ Could not find aiResponseArea element');
                }
            } else {
                console.warn('⚠️ No answer field in response data');
            }
            
        } catch (error) {
            if (error?.name === 'AbortError') {
                console.warn('AI chat aborted');
                return;
            }
            console.error('Error sending message to AI:', error);
            alert('Error communicating with AI: ' + error.message);
        } finally {
            clearTimeout(chatTimeoutId);
            __aiChatController = null;
            // Hide loading state
            if (loadingOverlay) {
                loadingOverlay.classList.add('d-none');
            }
            // Re-enable buttons and clear in-flight flag
            aiButtons.forEach(b => { try { b.disabled = false; } catch(_){} });
            __aiInFlight = false;
        }
    }

    /**
     * Calculate plant quantities based on bed dimensions and spacing
     * @param {number} bedLength - Bed length in meters
     * @param {number} bedWidth - Bed width in meters
     * @param {number} inRowSpacing - In-row spacing in cm
     * @param {number} betweenRowSpacing - Between-row spacing in cm
     * @param {string} method - Planting method: 'direct' or 'transplant'
     * @returns {object} Calculated quantities for seeding and transplanting
     */
    function calculatePlantQuantity(bedLength, bedWidth, inRowSpacing, betweenRowSpacing, method = 'direct') {
        // Convert measurements to consistent units (cm)
        const lengthCm = bedLength * 100;
        const widthCm = bedWidth * 100;
        
        // Minimum margin for cultivation access (10cm each side)
        const minimumMarginCm = 10;
        
        // Calculate number of rows that fit in the bed width
        // Must maintain minimum 10cm margins on each side for cultivation
        // Logic: bed width minus minimum margins = available space for rows
        // Then: available space minus row spacing = remaining for extra margins
        // Example: 75cm bed with 45cm between-row spacing:
        //   - Available: 75cm - (2 × 10cm) = 55cm
        //   - Rows: floor(55 / 45) + 1 = 2 rows
        //   - Gap space: (2-1) × 45cm = 45cm
        //   - Total margins: 75cm - 45cm = 30cm
        //   - Each margin: 30cm ÷ 2 = 15cm ✅ (exceeds 10cm minimum)
        const availableWidth = widthCm - (2 * minimumMarginCm);
        const numberOfRows = Math.max(1, Math.floor(availableWidth / betweenRowSpacing) + 1);
        
        // Calculate number of plants per row  
        // In-row spacing is the GAP between plants
        // Example: 1100cm bed with 40cm gaps = floor(1100/40) = 27 plants
        const plantsPerRow = Math.floor(lengthCm / inRowSpacing);
        
        // Total plants in bed
        const totalPlants = numberOfRows * plantsPerRow;
        
        // For direct seeding, we overseed by 20-50% to account for germination rate
        // For transplanting, we use the actual plant count
        let seedingQuantity = totalPlants;
        let transplantQuantity = totalPlants;
        
        if (method === 'direct') {
            seedingQuantity = Math.ceil(totalPlants * 1.3); // 30% overseeding
        } else if (method === 'transplant') {
            seedingQuantity = Math.ceil(totalPlants * 1.2); // 20% extra for transplant trays
        }
        
        return {
            totalPlants: totalPlants,
            seedingQuantity: seedingQuantity,
            transplantQuantity: transplantQuantity,
            numberOfRows: numberOfRows,
            plantsPerRow: plantsPerRow,
            bedArea: (bedLength * bedWidth).toFixed(2) // m²
        };
    }

    /**
     * Generate succession plan locally using variety data from database
     * No API calls - uses data already loaded in JavaScript
     */
    function generateLocalSuccessionPlan(payload, cropName, varietyName) {
        const harvestStart = new Date(payload.harvest_start);
        const harvestEnd = new Date(payload.harvest_end);
        const successionCount = payload.succession_count;
        
        const plantings = [];
        
        // Get maturity days from current variety info if available
        const maturityDays = window.currentVarietyData?.maturity_days || 45;
        // Use harvest_window_days (how long harvest lasts for ONE succession) from database
        const harvestWindowDays = window.currentVarietyData?.harvest_window_days || 30;
        
        console.log(`🌱 Using variety maturity: ${maturityDays} days, harvest window duration: ${harvestWindowDays} days (database)`);
        
        // Get bed dimensions and spacing for quantity calculations
        const bedLength = parseFloat(document.getElementById('bedLength')?.value) || 30; // default 30m
        const bedWidthMeters = parseFloat(document.getElementById('bedWidth')?.value) || 0.75; // default 0.75m
        const bedWidthCm = bedWidthMeters * 100; // Convert meters to cm for calculations
        const bedWidth = bedWidthMeters; // Keep as meters
        const inRowSpacing = parseFloat(document.getElementById('inRowSpacing')?.value) || 15; // default 15cm
        const betweenRowSpacing = parseFloat(document.getElementById('betweenRowSpacing')?.value) || 20; // default 20cm
        
        // Get selected planting method from radio buttons
        const methodRadio = document.querySelector('input[name="plantingMethod"]:checked');
        let selectedMethod = methodRadio?.value || 'either';
        
        // Determine actual planting method
        let plantingMethod;
        if (selectedMethod === 'direct') {
            plantingMethod = 'direct';
        } else if (selectedMethod === 'transplant') {
            plantingMethod = 'transplant';
        } else {
            // Auto mode: determine based on crop type or variety data
            const varietyMethod = window.currentVarietyData?.planting_method?.toLowerCase();
            if (varietyMethod === 'direct' || varietyMethod === 'transplant') {
                plantingMethod = varietyMethod;
            } else {
                // Fallback to crop-based logic
                const isTransplant = cropName.includes('brussels') || cropName.includes('cabbage') || 
                                    cropName.includes('broccoli') || cropName.includes('cauliflower') ||
                                    cropName.includes('tomato') || cropName.includes('pepper');
                plantingMethod = isTransplant ? 'transplant' : 'direct';
            }
        }
        
        console.log(`🌱 Planting method: ${plantingMethod} (selected: ${selectedMethod})`);
        
        // Calculate quantities based on bed dimensions
        const quantities = calculatePlantQuantity(bedLength, bedWidth, inRowSpacing, betweenRowSpacing, plantingMethod);
        
        console.log(`📏 Calculated quantities for ${bedLength}m x ${bedWidthCm}cm bed:`, quantities);
        
        // Check if using varietal succession
        const useVarietalSuccession = payload.varietal_succession;
        
        // Calculate extended harvest window for varietal succession
        let totalHarvestDuration = harvestWindowDays; // Default to single variety
        let harvestSpacing = 0;
        
        if (useVarietalSuccession) {
            console.log('🌱 Using varietal succession with varieties:', {
                early: `${useVarietalSuccession.earlyBedsCount}x ${useVarietalSuccession.early?.name} (${useVarietalSuccession.early?.harvest_window_days} days)`,
                mid: `${useVarietalSuccession.midBedsCount}x ${useVarietalSuccession.mid?.name} (${useVarietalSuccession.mid?.harvest_window_days} days)`,
                late: `${useVarietalSuccession.lateBedsCount}x ${useVarietalSuccession.late?.name} (${useVarietalSuccession.late?.harvest_window_days} days)`
            });
            
            // Get the actual harvest duration from the date range
            const harvestDuration = Math.ceil((harvestEnd - harvestStart) / (1000 * 60 * 60 * 24));
            totalHarvestDuration = harvestDuration;
            
            // Get harvest windows for each variety type
            const earlyWindow = useVarietalSuccession.early?.harvest_window_days || 30;
            const midWindow = useVarietalSuccession.mid?.harvest_window_days || 45;
            const lateWindow = useVarietalSuccession.late?.harvest_window_days || 120;
            
            // Find the longest harvest window among the varieties being used
            const earlyBedsCount = useVarietalSuccession.earlyBedsCount || 0;
            const midBedsCount = useVarietalSuccession.midBedsCount || 0;
            const lateBedsCount = useVarietalSuccession.lateBedsCount || 0;
            
            const longestWindow = Math.max(
                earlyBedsCount > 0 ? earlyWindow : 0,
                midBedsCount > 0 ? midWindow : 0,
                lateBedsCount > 0 ? lateWindow : 0
            );
            
            // Calculate spacing to ensure continuous coverage
            // For varietal succession, we need overlapping harvests to fill the entire period
            // Formula: total_duration / succession_count gives even distribution
            // But we need to account for the harvest window duration
            
            // Check if harvest window is long enough for the number of successions
            if (harvestDuration < longestWindow) {
                console.warn(`⚠️ Harvest window (${harvestDuration}d) is shorter than longest variety window (${longestWindow}d)!`);
                console.warn(`⚠️ This will cause overlapping/compressed timing. Consider expanding harvest dates or reducing successions.`);
            }
            
            // Calculate spacing for continuous coverage
            // If we have enough room, space them to overlap nicely
            // Otherwise, compress them as much as possible
            if (harvestDuration >= longestWindow) {
                // Normal case: space to fill the period with overlap
                harvestSpacing = successionCount > 1 ? 
                    Math.floor((harvestDuration - longestWindow) / (successionCount - 1)) : 0;
            } else {
                // Compressed case: harvest window too short, just space evenly
                harvestSpacing = successionCount > 1 ? 
                    Math.floor(harvestDuration / successionCount) : 0;
            }
            
            console.log(`📊 Varietal succession timeline: ${harvestDuration} days to cover (${earlyWindow}d early / ${midWindow}d mid / ${lateWindow}d late windows)`);
            console.log(`📊 Succession spacing: ${harvestSpacing} days between starts for ${successionCount} successions (longest window: ${longestWindow}d)`);
        } else {
            // Single variety: use standard calculation
            const harvestDuration = Math.ceil((harvestEnd - harvestStart) / (1000 * 60 * 60 * 24));
            totalHarvestDuration = harvestDuration;
            
            // Space successions so their harvest windows OVERLAP to cover the entire duration
            // Formula: (total duration - harvest window) / (number of gaps between successions)
            harvestSpacing = successionCount > 1 ? 
                Math.floor((harvestDuration - harvestWindowDays) / (successionCount - 1)) : 0;
            
            console.log(`📊 Succession spacing: ${harvestSpacing} days between harvest starts (${harvestDuration} day window, ${harvestWindowDays} day harvest duration, ${successionCount} successions)`);
        }
        
        for (let i = 0; i < successionCount; i++) {
            // Get variety-specific data if using varietal succession
            let currentVarietyForSuccession, currentMaturityDays, currentHarvestWindowDays, currentVarietyName;
            
            if (useVarietalSuccession) {
                // Determine which variety to use based on beds count
                // Early beds: 0 to earlyBedsCount-1
                // Mid beds: earlyBedsCount to earlyBedsCount+midBedsCount-1
                // Late beds: earlyBedsCount+midBedsCount to end
                const earlyBedsCount = useVarietalSuccession.earlyBedsCount || 1;
                const midBedsCount = useVarietalSuccession.midBedsCount || 1;
                const lateBedsCount = useVarietalSuccession.lateBedsCount || 1;
                
                if (i < earlyBedsCount) {
                    // Early variety beds
                    currentVarietyForSuccession = useVarietalSuccession.early;
                } else if (i < earlyBedsCount + midBedsCount) {
                    // Mid variety beds
                    currentVarietyForSuccession = useVarietalSuccession.mid;
                } else {
                    // Late variety beds
                    currentVarietyForSuccession = useVarietalSuccession.late;
                }
                
                currentMaturityDays = currentVarietyForSuccession?.maturity_days || maturityDays;
                currentHarvestWindowDays = currentVarietyForSuccession?.harvest_window_days || harvestWindowDays;
                currentVarietyName = currentVarietyForSuccession?.name || varietyName;
                
                console.log(`🌱 Succession ${i + 1}: Using ${currentVarietyName} (${currentMaturityDays} days maturity)`);
            } else {
                // Use the same variety for all successions
                currentMaturityDays = maturityDays;
                currentHarvestWindowDays = harvestWindowDays;
                currentVarietyName = varietyName;
            }
            
            // 🔧 FIX: For Brussels sprouts and other long-season crops, use advanced seasonal algorithm
            // that respects transplant windows instead of simple backwards calculation
            let seedingDate, transplantDate, successionHarvestDate, harvestEndDate;
            
            const cropNameLower = cropName.toLowerCase();
            if (cropNameLower.includes('brussels') || cropNameLower.includes('cabbage') || 
                cropNameLower.includes('broccoli') || cropNameLower.includes('cauliflower') || 
                currentMaturityDays >= 100) {
                // Use the advanced seasonal algorithm via calculateSuccessionDates
                // Pass the user's requested succession count AND variety-specific maturity/harvest window
                const successionDates = calculateSuccessionDates(
                    harvestStart, 
                    i, 
                    harvestSpacing, 
                    cropNameLower, 
                    currentVarietyName.toLowerCase(), 
                    successionCount,
                    currentMaturityDays,           // Pass variety-specific maturity days
                    currentHarvestWindowDays       // Pass variety-specific harvest window days
                );
                
                seedingDate = successionDates.sowDate;
                transplantDate = successionDates.transplantDate;
                successionHarvestDate = successionDates.harvestDate;
                
                // Calculate harvest end date using THIS variety's harvest_window_days
                harvestEndDate = new Date(successionHarvestDate);
                harvestEndDate.setDate(harvestEndDate.getDate() + currentHarvestWindowDays);
            } else {
                // For short-season crops, use simple backwards calculation
                successionHarvestDate = new Date(harvestStart);
                successionHarvestDate.setDate(successionHarvestDate.getDate() + (i * harvestSpacing));
                
                // Calculate seeding date (work backwards from harvest using THIS variety's maturity)
                seedingDate = new Date(successionHarvestDate);
                seedingDate.setDate(seedingDate.getDate() - currentMaturityDays);
                
                // For transplanted crops, calculate transplant date
                transplantDate = null;
                if (plantingMethod === 'transplant') {
                    transplantDate = new Date(seedingDate);
                    transplantDate.setDate(transplantDate.getDate() + 35); // ~5 weeks from seed to transplant
                }
                
                // Calculate harvest end date using THIS variety's harvest_window_days
                harvestEndDate = new Date(successionHarvestDate);
                harvestEndDate.setDate(harvestEndDate.getDate() + currentHarvestWindowDays);
            }
            
            plantings.push({
                succession_id: i + 1,
                succession_number: i + 1,
                seeding_date: seedingDate.toISOString().split('T')[0],
                transplant_date: transplantDate ? transplantDate.toISOString().split('T')[0] : null,
                harvest_date: successionHarvestDate.toISOString().split('T')[0],
                harvest_end_date: harvestEndDate.toISOString().split('T')[0],
                bed_name: 'Unassigned',
                crop_name: cropName,
                variety_name: currentVarietyName,
                variety_id: currentVarietyForSuccession?.id || payload.variety_id,
                // Add calculated quantities
                seeding_quantity: quantities.seedingQuantity,
                transplant_quantity: quantities.transplantQuantity,
                total_plants: quantities.totalPlants,
                planting_method: plantingMethod,
                // Add calculation breakdown for display
                plants_per_row: quantities.plantsPerRow,
                number_of_rows: quantities.numberOfRows,
                bed_length: payload.bed_length,
                bed_width: payload.bed_width,
                in_row_spacing: payload.in_row_spacing,
                between_row_spacing: payload.between_row_spacing
            });
            
            console.log(`📦 Succession ${i + 1} created with variety: "${currentVarietyName}"`);
        }
        
        return {
            crop: { id: payload.crop_id, name: cropName },
            variety: { id: payload.variety_id, name: varietyName },
            harvest_start: payload.harvest_start,
            harvest_end: payload.harvest_end,
            plantings: plantings,
            total_successions: successionCount
        };
    }

    async function calculateSuccessionPlan() {
        console.log('🎯 calculateSuccessionPlan called');
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');

        console.log('📊 Form values:', {
            crop: cropSelect?.value,
            variety: varietySelect?.value,
            harvestStart: harvestWindowData.userStart,
            harvestEnd: harvestWindowData.userEnd
        });

        if (!cropSelect?.value || !harvestWindowData.userStart || !harvestWindowData.userEnd) {
            console.log('⏭️ Skipping calculation - dates not ready yet');
            return; // Silently return without warning
        }

        // Calculate succession count based on harvest duration and crop type
        const start = new Date(harvestWindowData.userStart);
        const end = new Date(harvestWindowData.userEnd);
        const duration = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
        const cropName = cropSelect?.options[cropSelect.selectedIndex]?.text || '';
        const varietyName = varietySelect?.options[varietySelect.selectedIndex]?.text || '';  // Keep original capitalization
        const cropNameLower = cropName.toLowerCase();
        const varietyNameLower = varietyName.toLowerCase();
        const avgSuccessionInterval = getSuccessionInterval(cropNameLower, varietyNameLower);
        let successionCount = Math.max(1, Math.ceil(duration / avgSuccessionInterval));

        // For crops with transplant windows, also consider transplant window constraints
        if (cropNameLower.includes('brussels') || cropNameLower.includes('cabbage') ||
            cropNameLower.includes('broccoli') || cropNameLower.includes('cauliflower')) {
            // Get crop timing to check transplant window
            const cropTiming = getCropTiming(cropNameLower, varietyNameLower);
            if (cropTiming.transplantWindow) {
                const transplantWindowDays = 61; // March 15 - May 15 is approximately 61 days
                const transplantInterval = cropTiming.daysToTransplant || 35;

                // For Brussels sprouts, allow more successions since sowing dates can overlap
                let maxByTransplantWindow;
                if (cropName.toLowerCase().includes('brussels')) {
                    // For single variety: 1-2 successions is optimal
                    // For varietal succession (early/mid/late): will be set to 3 later
                    maxByTransplantWindow = 1;  // Default to 1 for single variety Brussels sprouts
                } else {
                    // For other crops, use the conservative calculation
                    const minDaysPerSuccession = transplantInterval + 14; // 35 days + 2 weeks buffer
                    maxByTransplantWindow = Math.max(1, Math.floor(transplantWindowDays / minDaysPerSuccession));
                }

                // Reduce successions if transplant window can't support them
                successionCount = Math.min(successionCount, maxByTransplantWindow);
            }
        }

        // Check if varietal succession is enabled
        const useVarietalSuccession = document.getElementById('useVarietalSuccession')?.checked || false;
        let varietalSuccessionData = null;
        
        if (useVarietalSuccession) {
            // Check if 2-variety interface is visible
            const varietiesSection2V = document.getElementById('varietalSuccessionVarieties2V');
            const is2VarietyMode = varietiesSection2V && varietiesSection2V.style.display !== 'none';
            
            if (is2VarietyMode) {
                // 2-VARIETY MODE (Broad Beans, Onions, etc.)
                const earlyVarietyId = document.getElementById('earlyVarietySelect2V')?.value;
                const midVarietyId = document.getElementById('midVarietySelect2V')?.value;
                
                // Get beds/successions count for each variety
                const earlyBedsCount = parseInt(document.getElementById('earlyBedsCount2V')?.value) || 1;
                const midBedsCount = parseInt(document.getElementById('midBedsCount2V')?.value) || 1;
                
                console.log('🔍 2-Variety succession IDs:', { earlyVarietyId, midVarietyId });
                console.log('🛏️ Beds per variety:', { earlyBedsCount, midBedsCount });
                
                if (earlyVarietyId && midVarietyId) {
                    // Get variety details from cropVarieties
                    const earlyVariety = cropVarieties.find(v => v.id === earlyVarietyId);
                    const midVariety = cropVarieties.find(v => v.id === midVarietyId);
                    
                    console.log('🔍 Found varieties:', {
                        early: earlyVariety?.name,
                        mid: midVariety?.name
                    });
                    
                    varietalSuccessionData = {
                        early: earlyVariety,
                        mid: midVariety,
                        late: null,  // No late variety in 2-variety mode
                        earlyBedsCount: earlyBedsCount,
                        midBedsCount: midBedsCount,
                        lateBedsCount: 0
                    };
                    
                    // Calculate total successions based on beds count
                    successionCount = earlyBedsCount + midBedsCount;
                    
                    console.log('🌱 2-Variety succession enabled:', varietalSuccessionData);
                    console.log(`📊 Total successions: ${successionCount} (${earlyBedsCount} autumn/winter + ${midBedsCount} spring)`);
                } else {
                    console.warn('⚠️ 2-Variety succession enabled but not both varieties selected');
                }
            } else {
                // 3-VARIETY MODE (Brussels Sprouts, etc.)
                const earlyVarietyId = document.getElementById('earlyVarietySelect')?.value;
                const midVarietyId = document.getElementById('midVarietySelect')?.value;
                const lateVarietyId = document.getElementById('lateVarietySelect')?.value;
                
                // Get beds/successions count for each variety
                const earlyBedsCount = parseInt(document.getElementById('earlyBedsCount')?.value) || 1;
                const midBedsCount = parseInt(document.getElementById('midBedsCount')?.value) || 1;
                const lateBedsCount = parseInt(document.getElementById('lateBedsCount')?.value) || 1;
                
                console.log('🔍 Varietal succession IDs:', { earlyVarietyId, midVarietyId, lateVarietyId });
                console.log('🛏️ Beds per variety:', { earlyBedsCount, midBedsCount, lateBedsCount });
                
                if (earlyVarietyId && midVarietyId && lateVarietyId) {
                    // Get variety details from cropVarieties
                    const earlyVariety = cropVarieties.find(v => v.id === earlyVarietyId);
                    const midVariety = cropVarieties.find(v => v.id === midVarietyId);
                    const lateVariety = cropVarieties.find(v => v.id === lateVarietyId);
                    
                    console.log('🔍 Found varieties:', {
                        early: earlyVariety?.name,
                        mid: midVariety?.name,
                        late: lateVariety?.name
                    });
                    
                    varietalSuccessionData = {
                        early: earlyVariety,
                        mid: midVariety,
                        late: lateVariety,
                        earlyBedsCount: earlyBedsCount,
                        midBedsCount: midBedsCount,
                        lateBedsCount: lateBedsCount
                    };
                    
                    // Calculate total successions based on beds count
                    successionCount = earlyBedsCount + midBedsCount + lateBedsCount;
                    
                    console.log('🌱 Varietal succession enabled:', varietalSuccessionData);
                    console.log(`📊 Total successions: ${successionCount} (${earlyBedsCount} early + ${midBedsCount} mid + ${lateBedsCount} late)`);
                } else {
                    console.warn('⚠️ Varietal succession enabled but not all varieties selected');
                }
            }
        }

        const payload = {
            crop_id: cropSelect.value,
            variety_id: varietySelect?.value || null,
            harvest_start: harvestWindowData.userStart,
            harvest_end: harvestWindowData.userEnd,
            bed_ids: [], // Beds will be assigned via drag-and-drop on timeline
            succession_count: successionCount,
            use_ai: true,
            varietal_succession: varietalSuccessionData,
            // Add bed dimensions and spacing for calculations
            bed_length: parseFloat(document.getElementById('bedLength')?.value) || 30,
            bed_width: parseFloat(document.getElementById('bedWidth')?.value) || 75, // in cm
            in_row_spacing: parseFloat(document.getElementById('inRowSpacing')?.value) || 15,
            between_row_spacing: parseFloat(document.getElementById('betweenRowSpacing')?.value) || 20
        };

        console.log('📦 Generating local succession plan (no API call):', payload);

        // Clear any previous allocations when generating a new plan
        localStorage.removeItem('bedAllocations');
        console.log('🗑️ Cleared previous allocations for new succession plan');

        showLoading(true);
        try {
            // Generate succession plan locally using variety data from database
            const successionPlan = generateLocalSuccessionPlan(payload, cropName, varietyName);
            console.log('✅ Local succession plan generated:', successionPlan);

            currentSuccessionPlan = successionPlan;
            console.log('✅ Succession plan received:', currentSuccessionPlan);
            
            // Populate succession sidebar with draggable cards
            console.log('� Populating succession sidebar...');
            console.log('📊 Plantings in plan:', currentSuccessionPlan.plantings?.length);
            if (typeof populateSuccessionSidebar === 'function') {
                console.log('✅ Calling populateSuccessionSidebar...');
                populateSuccessionSidebar(currentSuccessionPlan);
                console.log('✅ populateSuccessionSidebar completed');
            } else {
                console.error('❌ populateSuccessionSidebar function not found!');
            }
            
            console.log('🗓️ Rendering FarmOS timeline...');
            await renderFarmOSTimeline(currentSuccessionPlan);
            console.log('📝 Rendering quick form tabs...');
            renderQuickFormTabs(currentSuccessionPlan);
            
            // Initialize drag and drop after both timeline and sidebar are ready
            requestAnimationFrame(() => {
                initializeDragAndDrop();
                console.log('🔄 Drag and drop initialized after plan calculation');
            });
            
            document.getElementById('resultsSection').style.display = 'block';
            
            // Delay updateExportButton to ensure DOM is ready
            requestAnimationFrame(() => {
                updateExportButton();
            });
            
            // testQuickFormUrls(); // Function not defined
        } catch (e) {
            console.error('Failed to calculate plan:', e);
            showToast('Failed to calculate plan', 'error');
        } finally {
            showLoading(false);
        }
    }

    function renderSuccessionSummary(plan) {
        console.log('🎨 renderSuccessionSummary called with plan:', plan);
        const container = document.getElementById('successionSummary');
        if (!container) {
            console.error('❌ successionSummary container not found!');
            return;
        }
        const plantings = plan.plantings || [];
        console.log(`📊 Rendering ${plantings.length} succession cards`);
        const items = plantings.map((p, i) => {
            return `<div class="col-md-4">
                <div class="succession-card" onclick="switchTab(${i})" role="button" aria-label="Open succession ${i+1}">
                    <div class="d-flex justify-content-between">
                        <strong>Succession ${i+1}</strong>
                        <span class="badge bg-light text-dark">${p.bed_name || 'Unassigned'}</span>
                    </div>
                    <div class="mt-2 small text-muted">
                        Seeding: ${p.seeding_date || '-'}<br>
                        ${p.transplant_date ? 'Transplant: ' + p.transplant_date + '<br>' : ''}
                        Harvest: ${p.harvest_date}${p.harvest_end_date ? ' → ' + p.harvest_end_date : ''}
                    </div>
                </div>
            </div>`;
        });
        container.innerHTML = items.join('');
    }

    function renderQuickFormTabs(plan) {
        console.log('🔧 Rendering Quick Form tabs for plan:', plan);
        console.log('📊 Total plantings to render:', plan.plantings?.length);
        
        // Use the existing Quick Form tabs container
        const tabsWrap = document.getElementById('quickFormTabsContainer');
        if (!tabsWrap) {
            console.error('❌ Quick Form tabs container not found');
            return;
        }

        console.log('✅ Found tabs container, plantings:', plan.plantings);
        
        const nav = document.getElementById('tabNavigation');
        const content = document.getElementById('tabContent');
        const placeholder = document.getElementById('quickFormsPlaceholder');
        
        if (!nav || !content) {
            console.error('❌ Tab navigation or content elements not found');
            return;
        }

        // Clear existing content
        nav.innerHTML = '';
        content.innerHTML = '';

        if (!plan.plantings || plan.plantings.length === 0) {
            console.warn('⚠️ No plantings found in plan');
            nav.innerHTML = '<div class="alert alert-warning">No succession plantings generated</div>';
            content.innerHTML = '';
            if (placeholder) placeholder.style.display = 'none';
            tabsWrap.style.display = 'block';
            return;
        }
        
        // Hide placeholder, show actual tabs
        if (placeholder) placeholder.style.display = 'none';

        console.log(`🔄 About to loop through ${plan.plantings.length} plantings...`);
        (plan.plantings || []).forEach((p, i) => {
            console.log(`🔄 Processing planting ${i+1}/${plan.plantings.length}:`, p);
            
            // Button
            const btn = document.createElement('button');
            btn.className = 'tab-button' + (i === 0 ? ' active' : '');
            btn.type = 'button';
            btn.textContent = `Succession ${i+1}${p.variety_name ? `: ${p.variety_name}` : ''}`;
            btn.addEventListener('click', () => switchTab(i));
            nav.appendChild(btn);

            // Pane
            const pane = document.createElement('div');
            pane.id = `tab-${i}`;
            pane.className = 'tab-pane' + (i === 0 ? ' active' : '');

            const info = document.createElement('div');
            info.className = 'succession-info';
            
            // Calculate timeline visualization
            const seedDate = p.seeding_date ? new Date(p.seeding_date) : null;
            const transplantDate = p.transplant_date ? new Date(p.transplant_date) : null;
            const harvestStart = p.harvest_date ? new Date(p.harvest_date) : null;
            const harvestEnd = p.harvest_end_date ? new Date(p.harvest_end_date) : harvestStart;
            
            let timelineHTML = '';
            if (seedDate && harvestStart) {
                const totalDays = Math.ceil((harvestEnd - seedDate) / (1000 * 60 * 60 * 24));
                const seedToTransplant = transplantDate ? Math.ceil((transplantDate - seedDate) / (1000 * 60 * 60 * 24)) : 0;
                const transplantToHarvest = transplantDate ? Math.ceil((harvestStart - transplantDate) / (1000 * 60 * 60 * 24)) : 0;
                const seedToHarvest = Math.ceil((harvestStart - seedDate) / (1000 * 60 * 60 * 24));
                const harvestDuration = harvestEnd ? Math.ceil((harvestEnd - harvestStart) / (1000 * 60 * 60 * 24)) : 0;
                
                const seedPercent = transplantDate ? (seedToTransplant / totalDays * 100) : (seedToHarvest / totalDays * 100);
                const transplantPercent = transplantDate ? (transplantToHarvest / totalDays * 100) : 0;
                const harvestPercent = (harvestDuration / totalDays * 100);
                
                timelineHTML = `
                    <div class="succession-timeline mb-3" style="margin-top: 15px;">
                        <div style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; margin-bottom: 5px;">
                            <span style="font-weight: 600; color: #28a745;">🌱 Seed</span>
                            ${transplantDate ? '<span style="color: #999;">→</span><span style="font-weight: 600; color: #17a2b8;">🌿 Transplant</span>' : ''}
                            <span style="color: #999;">→</span>
                            <span style="font-weight: 600; color: #ffc107;">🌾 Harvest</span>
                            <span style="margin-left: auto; color: #666; font-size: 0.8rem;">${totalDays} days total</span>
                        </div>
                        <div style="position: relative; height: 30px; background: #f0f0f0; border-radius: 4px; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);">
                            ${transplantDate ? `
                                <div style="position: absolute; left: 0; height: 100%; width: ${seedPercent}%; background: linear-gradient(135deg, #28a745, #20c997); display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem; font-weight: 600; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">
                                    ${seedToTransplant}d
                                </div>
                                <div style="position: absolute; left: ${seedPercent}%; height: 100%; width: ${transplantPercent}%; background: linear-gradient(135deg, #17a2b8, #20c9b8); display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem; font-weight: 600; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">
                                    ${transplantToHarvest}d
                                </div>
                                <div style="position: absolute; right: 0; height: 100%; width: ${harvestPercent}%; background: linear-gradient(135deg, #ffc107, #ffb300); display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem; font-weight: 600; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">
                                    ${harvestDuration}d
                                </div>
                            ` : `
                                <div style="position: absolute; left: 0; height: 100%; width: ${100 - harvestPercent}%; background: linear-gradient(135deg, #28a745, #20c997); display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem; font-weight: 600; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">
                                    ${seedToHarvest}d
                                </div>
                                <div style="position: absolute; right: 0; height: 100%; width: ${harvestPercent}%; background: linear-gradient(135deg, #ffc107, #ffb300); display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem; font-weight: 600; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">
                                    ${harvestDuration}d
                                </div>
                            `}
                        </div>
                    </div>
                `;
            }
            
            info.innerHTML = `<h5>Details</h5>
                ${timelineHTML}
                <p><strong>Bed:</strong> ${p.bed_name || 'Unassigned'}</p>
                <p><strong>Seeding:</strong> ${p.seeding_date || '-'}</p>
                ${p.transplant_date ? `<p><strong>Transplant:</strong> ${p.transplant_date}</p>` : ''}
                <p><strong>Harvest:</strong> ${p.harvest_date}${p.harvest_end_date ? ' → ' + p.harvest_end_date : ''}</p>`;
            pane.appendChild(info);

            // Generate URLs for all quick form types
            const baseUrl = window.location.origin + '/admin/farmos';
            const quickFormUrls = {
                seeding: baseUrl + '/quick/seeding?' + new URLSearchParams({
                    crop_name: p.crop_name || '',
                    variety_name: p.variety_name || '',
                    bed_name: p.bed_name || '',
                    quantity: p.quantity || '',
                    succession_number: p.succession_id || 1,
                    seeding_date: p.seeding_date || '',
                    season: p.season || ''
                }).toString(),
                transplanting: baseUrl + '/quick/transplant?' + new URLSearchParams({
                    crop_name: p.crop_name || '',
                    variety_name: p.variety_name || '',
                    bed_name: p.bed_name || '',
                    quantity: p.quantity || '',
                    succession_number: p.succession_id || 1,
                    transplant_date: p.transplant_date || '',
                    season: p.season || ''
                }).toString(),
                harvest: baseUrl + '/quick/harvest?' + new URLSearchParams({
                    crop_name: p.crop_name || '',
                    variety_name: p.variety_name || '',
                    bed_name: p.bed_name || '',
                    quantity: p.quantity || '',
                    succession_number: p.succession_id || 1,
                    harvest_date: p.harvest_date || '',
                    season: p.season || ''
                }).toString()
            };

            // Determine default checkbox states based on planting method
            const hasTransplant = !!p.transplant_date;
            const seedingChecked = true; // Always check seeding (needed for both direct and transplant)
            const transplantChecked = hasTransplant; // Transplant: check transplanting
            const harvestChecked = true; // Always check harvest by default

            // Display quick form buttons that toggle form sections
            pane.innerHTML += `
                <div class="quick-form-container">
                    <h6>Quick Forms</h6>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input log-type-checkbox" type="checkbox" id="seeding-enabled-${i}" ${seedingChecked ? 'checked' : ''} onchange="toggleQuickForm(${i}, 'seeding')">
                            <label class="form-check-label" for="seeding-enabled-${i}">
                                <strong>Seeding</strong> - Record when seeds are planted
                            </label>
                        </div>
                        ${p.transplant_date ? `<div class="form-check">
                            <input class="form-check-input log-type-checkbox" type="checkbox" id="transplanting-enabled-${i}" ${transplantChecked ? 'checked' : ''} onchange="toggleQuickForm(${i}, 'transplanting')">
                            <label class="form-check-label" for="transplanting-enabled-${i}">
                                <strong>Transplanting</strong> - Record when seedlings are transplanted
                            </label>
                        </div>` : ''}
                        <div class="form-check">
                            <input class="form-check-input log-type-checkbox" type="checkbox" id="harvest-enabled-${i}" ${harvestChecked ? 'checked' : ''} onchange="toggleQuickForm(${i}, 'harvest')">
                            <label class="form-check-label" for="harvest-enabled-${i}">
                                <strong>Harvest</strong> - Record harvest dates and quantities
                            </label>
                        </div>
                    </div>
                    <div class="alert alert-info mt-2">
                        <small>Check boxes to fill out forms directly here</small>
                    </div>
                </div>

                <!-- Season Selection -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="mb-3"><i class="fas fa-calendar-alt text-primary"></i> Season</h5>
                        <div class="mb-3">
                            <label class="form-label">What season(s) will this be part of? *</label>
                            <input type="text" class="form-control" name="plantings[${i}][season]"
                                   value="${p.season || (new Date().getFullYear() + ' Succession')}" required
                                   placeholder="e.g., 2025, 2025 Summer, 2025 Succession">
                            <div class="form-text">This will be prepended to the plant asset name for organization.</div>
                        </div>
                    </div>
                </div>

                <!-- Crops/Varieties Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="mb-3"><i class="fas fa-leaf text-success"></i> Crop/Variety</h5>
                        <div class="mb-3">
                            <input type="text" class="form-control" name="plantings[${i}][crop_variety]"
                                   value="${p.variety_name || p.crop_name || ''}" required
                                   placeholder="Enter crop/variety (e.g., Lettuce, Carrot, Tomato)">
                            <div class="form-text">Enter the crop or variety name for this planting.</div>
                        </div>
                    </div>
                </div>

                <!-- Embedded Quick Form Sections -->
                <div id="quick-form-seeding-${i}" class="embedded-quick-form" style="display: block;">
                    <div class="form-content">
                        <h6><i class="fas fa-seedling text-success"></i> Seeding Form</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Seeding Date *</label>
                                <input type="datetime-local" class="form-control" name="plantings[${i}][seeding][date]"
                                       value="${p.seeding_date ? new Date(p.seeding_date).toISOString().slice(0, 16) : ''}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Completed</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="plantings[${i}][seeding][done]" value="1">
                                    <label class="form-check-label">Mark as completed</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location *</label>
                            <input type="text" class="form-control" name="plantings[${i}][seeding][location]"
                                   value="${p.transplant_date ? 'Propagation' : (p.bed_name || '')}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity *</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="number" class="form-control" name="plantings[${i}][seeding][quantity][value]"
                                           value="${p.seeding_quantity || 100}" step="1" min="0" required>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" name="plantings[${i}][seeding][quantity][units]">
                                        <option value="seeds" selected>Seeds</option>
                                        <option value="plants">Plants</option>
                                        <option value="grams">Grams</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" name="plantings[${i}][seeding][quantity][measure]">
                                        <option value="count" selected>Count</option>
                                        <option value="weight">Weight</option>
                                    </select>
                                </div>
                            </div>
                            ${p.seeding_quantity ? `
                                <div class="mt-2">
                                    <small class="text-muted d-block">
                                        <strong>Calculated:</strong> ${p.total_plants || ''} plants with ${p.planting_method === 'direct' ? '30%' : '20%'} overseeding
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-calculator"></i> 
                                        ${p.bed_length || '?'}m × ${p.bed_width || '?'}m bed: 
                                        <strong>${p.plants_per_row || '?'} plants/row</strong> × <strong>${p.number_of_rows || '?'} rows</strong> = ${p.total_plants || '?'} plants
                                    </small>
                                    <small class="text-muted d-block">
                                        (${p.in_row_spacing || '?'}cm in-row spacing, ${p.between_row_spacing || '?'}cm between-row spacing)
                                    </small>
                                    <small class="text-muted fst-italic">
                                        <i class="fas fa-info-circle"></i> Rows calculated as: floor(bed width ÷ row spacing)
                                    </small>
                                </div>
                            ` : ''}
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="plantings[${i}][seeding][notes]" rows="2">Seeding for succession #${p.succession_id || 1}</textarea>
                        </div>
                    </div>
                </div>

                ${p.transplant_date ? `
                <div id="quick-form-transplanting-${i}" class="embedded-quick-form" style="display: block;">
                    <div class="form-content">
                        <h6><i class="fas fa-shipping-fast text-warning"></i> Transplanting Form</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Transplant Date *</label>
                                <input type="datetime-local" class="form-control" name="plantings[${i}][transplanting][date]"
                                       value="${p.transplant_date ? new Date(p.transplant_date).toISOString().slice(0, 16) : ''}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Completed</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="plantings[${i}][transplanting][done]" value="1">
                                    <label class="form-check-label">Mark as completed</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location *</label>
                            <input type="text" class="form-control" name="plantings[${i}][transplanting][location]"
                                   value="${p.bed_name || ''}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity *</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="number" class="form-control" name="plantings[${i}][transplanting][quantity][value]"
                                           value="${p.transplant_quantity || p.total_plants || 100}" step="1" min="0" required>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" name="plantings[${i}][transplanting][quantity][units]">
                                        <option value="plants" selected>Plants</option>
                                        <option value="seeds">Seeds</option>
                                        <option value="grams">Grams</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" name="plantings[${i}][transplanting][quantity][measure]">
                                        <option value="count" selected>Count</option>
                                        <option value="weight">Weight</option>
                                    </select>
                                </div>
                            </div>
                            ${p.transplant_quantity ? `
                                <div class="mt-2">
                                    <small class="text-muted d-block">
                                        <strong>Calculated:</strong> ${p.total_plants || ''} plants
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-calculator"></i> 
                                        ${p.bed_length || '?'}m × ${p.bed_width || '?'}cm bed: 
                                        <strong>${p.plants_per_row || '?'} plants/row</strong> × <strong>${p.number_of_rows || '?'} rows</strong> = ${p.total_plants || '?'} plants
                                    </small>
                                    <small class="text-muted">
                                        (${p.in_row_spacing || '?'}cm in-row spacing, ${p.between_row_spacing || '?'}cm between-row spacing)
                                    </small>
                                </div>
                            ` : ''}
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="plantings[${i}][transplanting][notes]" rows="2">Transplanting for succession #${p.succession_id || 1}</textarea>
                        </div>
                    </div>
                </div>
                ` : ''}

                <div id="quick-form-harvest-${i}" class="embedded-quick-form" style="display: block;">
                    <div class="form-content">
                        <h6><i class="fas fa-shopping-basket text-danger"></i> Harvest Form</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Harvest Date *</label>
                                <input type="datetime-local" class="form-control" name="plantings[${i}][harvest][date]"
                                       value="${p.harvest_date ? new Date(p.harvest_date).toISOString().slice(0, 16) : ''}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Completed</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="plantings[${i}][harvest][done]" value="1">
                                    <label class="form-check-label">Mark as completed</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity *</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="number" class="form-control" name="plantings[${i}][harvest][quantity][value]"
                                           value="0" step="1" min="0" required>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" name="plantings[${i}][harvest][quantity][units]">
                                        <option value="grams">Grams</option>
                                        <option value="pounds">Pounds</option>
                                        <option value="kilograms" selected>Kilograms</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" name="plantings[${i}][harvest][quantity][measure]">
                                        <option value="weight" selected>Weight</option>
                                        <option value="count">Count</option>
                                    </select>
                                </div>
                            </div>
                            <small class="text-muted">Weight will be recorded on harvest day</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="plantings[${i}][harvest][notes]" rows="2">Harvest for succession #${p.succession_id || 1}</textarea>
                        </div>
                    </div>
                </div>
            `;

            content.appendChild(pane);
        });

        // Show the tabs container
        // console.log('✅ Showing tabs container');
        tabsWrap.style.display = 'block';

        // Initialize form visibility based on default checkbox states
        (plan.plantings || []).forEach((p, i) => {
            const hasTransplant = !!p.transplant_date;
            
            // Always show seeding and harvest forms
            toggleQuickForm(i, 'seeding', true);
            toggleQuickForm(i, 'harvest', true);
            
            // Show transplanting form only if there's a transplant date
            if (hasTransplant) {
                toggleQuickForm(i, 'transplanting', true);
            }
        });
    }

    /**
     * Switch between quick form tabs
     */
    function switchTab(index) {
        console.log('🔄 Switching to tab:', index);
        
        // Update tab buttons
        const buttons = document.querySelectorAll('#tabNavigation .tab-button');
        buttons.forEach((btn, i) => {
            if (i === index) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
        
        // Update tab panes
        const panes = document.querySelectorAll('#tabContent .tab-pane');
        panes.forEach((pane, i) => {
            if (i === index) {
                pane.classList.add('active');
            } else {
                pane.classList.remove('active');
            }
        });
    }

    async function renderFarmOSTimeline(plan) {
        console.log('� renderFarmOSTimeline called with plan:', plan);
        console.log('�🔧 Rendering FarmOS timeline for plan:', plan);

        const container = document.getElementById('farmosTimelineContainer');
        console.log('🔍 Looking for container #farmosTimelineContainer:', container);
        if (!container) {
            console.error('❌ FarmOS timeline container not found');
            return;
        }
        // console.log('✅ Found container, current content:', container.innerHTML.substring(0, 100) + '...');

        try {
            // Show loading state
            container.innerHTML = `
                <div class="text-center p-4">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading timeline...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading FarmOS bed occupancy data...</p>
                </div>
            `;

            console.log('📊 Fetching FarmOS bed and planting data...');
            // Fetch bed occupancy data from FarmOS
            const bedData = await fetchFarmOSBedData(plan);
            // console.log('✅ Bed data fetched:', bedData);

            // Create comprehensive bed occupancy timeline
            const timelineHtml = createBedOccupancyTimeline(plan, bedData);
            // console.log('✅ Timeline HTML created, length:', timelineHtml.length);

            container.innerHTML = timelineHtml;
            console.log('🎯 Bed occupancy timeline rendered successfully!');

            // Drag and drop will be initialized centrally after both timeline and sidebar are ready

        } catch (error) {
            console.error('❌ Error rendering FarmOS bed occupancy timeline:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> FarmOS Data Unavailable</h5>
                    <p>Unable to load real bed occupancy data from FarmOS. Please check your FarmOS connection and credentials.</p>
                    <p><small>Error: ${error.message}</small></p>
                    <div class="mt-3">
                        <button class="btn btn-outline-primary btn-sm" onclick="renderFarmOSTimeline(window.currentSuccessionPlan)">
                            <i class="fas fa-sync"></i> Retry
                        </button>
                    </div>
                </div>
            `;
        }
    }

    function createTimelineVisualization(plan) {
        if (!plan.plantings || plan.plantings.length === 0) {
            return `
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> No Timeline Data</h5>
                    <p>Generate a succession plan to see the timeline visualization.</p>
                </div>
            `;
        }

        // Calculate timeline bounds
        const allDates = [];
        plan.plantings.forEach(planting => {
            if (planting.seeding_date) allDates.push(new Date(planting.seeding_date));
            if (planting.transplant_date) allDates.push(new Date(planting.transplant_date));
            if (planting.harvest_date) allDates.push(new Date(planting.harvest_date));
            if (planting.harvest_end_date) allDates.push(new Date(planting.harvest_end_date));
        });

        if (allDates.length === 0) {
            return `
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle"></i> No Dates Available</h5>
                    <p>The succession plan doesn't have date information yet.</p>
                </div>
            `;
        }

        const minDate = new Date(Math.min(...allDates));
        const maxDate = new Date(Math.max(...allDates));

        // Extend timeline by 2 weeks on each side for better visualization
        minDate.setDate(minDate.getDate() - 14);
        maxDate.setDate(maxDate.getDate() + 14);

        const totalDays = Math.ceil((maxDate - minDate) / (1000 * 60 * 60 * 24));

        // Create month labels
        const months = [];
        const current = new Date(minDate);
        while (current <= maxDate) {
            months.push({
                label: current.toLocaleDateString('en-US', { month: 'short', year: 'numeric' }),
                date: new Date(current)
            });
            current.setMonth(current.getMonth() + 1);
        }

        // Create timeline tasks
        const tasks = [];
        plan.plantings.forEach((planting, index) => {
            const successionNum = index + 1;
            const cropName = planting.crop_name || 'Unknown Crop';

            // Seeding task
            if (planting.seeding_date) {
                const seedingDate = new Date(planting.seeding_date);
                const left = ((seedingDate - minDate) / (maxDate - minDate)) * 100;
                tasks.push({
                    id: `seeding-${successionNum}`,
                    type: 'seeding',
                    label: `Sow ${cropName}`,
                    succession: successionNum,
                    left: Math.max(0, Math.min(95, left)),
                    date: seedingDate
                });
            }

            // Transplanting task
            if (planting.transplant_date) {
                const transplantDate = new Date(planting.transplant_date);
                const left = ((transplantDate - minDate) / (maxDate - minDate)) * 100;
                tasks.push({
                    id: `transplant-${successionNum}`,
                    type: 'transplanting',
                    label: `Transplant ${cropName}`,
                    succession: successionNum,
                    left: Math.max(0, Math.min(95, left)),
                    date: transplantDate
                });
            }

            // Growth period (from seeding/transplant to harvest)
            if (planting.harvest_date) {
                const harvestDate = new Date(planting.harvest_date);
                const startDate = planting.transplant_date ? new Date(planting.transplant_date) : (planting.seeding_date ? new Date(planting.seeding_date) : harvestDate);
                const left = ((startDate - minDate) / (maxDate - minDate)) * 100;
                const width = ((harvestDate - startDate) / (maxDate - minDate)) * 100;

                if (width > 0) {
                    tasks.push({
                        id: `growth-${successionNum}`,
                        type: 'growth',
                        label: `${cropName} Growth`,
                        succession: successionNum,
                        left: Math.max(0, left),
                        width: Math.max(5, Math.min(100 - left, width)),
                        date: startDate
                    });
                }
            }

            // Harvest task
            if (planting.harvest_date) {
                const harvestDate = new Date(planting.harvest_date);
                const left = ((harvestDate - minDate) / (maxDate - minDate)) * 100;
                tasks.push({
                    id: `harvest-${successionNum}`,
                    type: 'harvest',
                    label: `Harvest ${cropName}`,
                    succession: successionNum,
                    left: Math.max(0, Math.min(95, left)),
                    date: harvestDate
                });
            }
        });

        // Sort tasks by date for proper layering
        tasks.sort((a, b) => a.date - b.date);

        return `
            <div class="timeline-visualization">
                <div class="timeline-axis">
                    ${months.map(month => `<div class="timeline-month">${month.label}</div>`).join('')}
                </div>

                <div class="timeline-tasks">
                    ${tasks.map(task => `
                        <div class="timeline-task ${task.type}"
                             style="left: ${task.left}%; ${task.width ? `width: ${task.width}%;` : 'width: 120px;'} top: ${(task.succession - 1) * 50 + 10}px;"
                             title="${task.label} - ${task.date.toLocaleDateString()}">
                            <span>${task.label}</span>
                        </div>
                    `).join('')}
                </div>

                <div class="timeline-legend">
                    <div class="legend-item">
                        <div class="legend-color seeding"></div>
                        <span>Seeding</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color transplanting"></div>
                        <span>Transplanting</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color growth"></div>
                        <span>Growth Period</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color harvest"></div>
                        <span>Harvest</span>
                    </div>
                </div>
            </div>
        `;
    }

    async function fetchFarmOSBedData(plan) {
        console.log('🌐 Fetching real FarmOS bed occupancy data from API...');

        // Calculate date range from succession plan for API request
        const allDates = [];
        if (plan.plantings) {
            plan.plantings.forEach(planting => {
                if (planting.seeding_date) allDates.push(new Date(planting.seeding_date));
                if (planting.transplant_date) allDates.push(new Date(planting.transplant_date));
                if (planting.harvest_date) allDates.push(new Date(planting.harvest_date));
                if (planting.harvest_end_date) allDates.push(new Date(planting.harvest_end_date));
            });
        }

        const minDate = allDates.length > 0 ? new Date(Math.min(...allDates)) : new Date();
        const maxDate = allDates.length > 0 ? new Date(Math.max(...allDates)) : new Date();

        // Call the real FarmOS API endpoint
        const response = await fetch(`${API_BASE}/bed-occupancy?start_date=${minDate.toISOString().split('T')[0]}&end_date=${maxDate.toISOString().split('T')[0]}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`FarmOS API request failed: ${response.status} ${response.statusText} - ${errorText}`);
        }

        const data = await response.json();

        if (data.error || (data.success !== undefined && data.success === false)) {
            throw new Error(data.message || data.error || `FarmOS API error: ${response.status}`);
        }

        // console.log('✅ Successfully fetched real FarmOS bed data:', {
        //     beds: data.data?.beds?.length || 0,
        //     plantings: data.data?.plantings?.length || 0,
        //     sampleBed: data.data?.beds?.[0],
        //     samplePlanting: data.data?.plantings?.[0]
        // });

        return data;
    }

    function createBedOccupancyTimeline(plan, bedData) {
        console.log('🔍 createBedOccupancyTimeline called with bedData:', bedData);
        
        // Handle API response structure: {success: true, data: {beds: [...], plantings: [...]}}
        const actualBedData = bedData.data || bedData;
        console.log('🔍 actualBedData:', actualBedData);
        
        if (!actualBedData || !actualBedData.beds || actualBedData.beds.length === 0) {
            return `
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> No FarmOS Beds Found</h5>
                    <p>Your FarmOS instance doesn't have any beds (land assets) configured yet.</p>
                    <p>Create some beds in FarmOS first, then the timeline will show real bed occupancy data.</p>
                </div>
            `;
        }

        // Calculate timeline bounds from succession plan dates
        const allDates = [];
        if (plan.plantings) {
            plan.plantings.forEach(planting => {
                if (planting.seeding_date) allDates.push(new Date(planting.seeding_date));
                if (planting.transplant_date) allDates.push(new Date(planting.transplant_date));
                if (planting.harvest_date) allDates.push(new Date(planting.harvest_date));
                if (planting.harvest_end_date) allDates.push(new Date(planting.harvest_end_date));
            });
        }

        // Include existing planting dates
        if (actualBedData.plantings) {
            actualBedData.plantings.forEach(planting => {
                if (planting.start_date) allDates.push(new Date(planting.start_date));
                if (planting.end_date) allDates.push(new Date(planting.end_date));
            });
        }

        if (allDates.length === 0) {
            return `
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> No Timeline Data</h5>
                    <p>Generate a succession plan to see bed availability over time.</p>
                </div>
            `;
        }

        const minDate = new Date(Math.min(...allDates));
        const maxDate = new Date(Math.max(...allDates));

        // Extend timeline by 2 months on each side for better context
        // This prevents succession blocks from being clamped to 0% when dates are outside range
        minDate.setMonth(minDate.getMonth() - 2);
        maxDate.setMonth(maxDate.getMonth() + 2);

        // Ensure timeline spans at least the current calendar year to prevent positioning bugs
        const now = new Date();
        const yearStart = new Date(now.getFullYear(), 0, 1);  // January 1st
        const yearEnd = new Date(now.getFullYear(), 11, 31);   // December 31st
        
        if (minDate > yearStart) minDate.setTime(yearStart.getTime());
        if (maxDate < yearEnd) maxDate.setTime(yearEnd.getTime());

        console.log('📅 Timeline date range calculated:', {
            minDate: minDate.toISOString().split('T')[0],
            maxDate: maxDate.toISOString().split('T')[0],
            totalDates: allDates.length
        });

        // Create month labels
        const months = [];
        const current = new Date(minDate);
        while (current <= maxDate) {
            months.push({
                label: current.toLocaleDateString('en-US', { month: 'short', year: 'numeric' }),
                date: new Date(current)
            });
            current.setMonth(current.getMonth() + 1);
        }

        // Group beds by block
        const bedsByBlock = {};
        actualBedData.beds.forEach(bed => {
            const block = bed.block || 'Block Unknown';
            if (!bedsByBlock[block]) {
                bedsByBlock[block] = [];
            }
            bedsByBlock[block].push(bed);
        });

        // Sort blocks numerically, keeping "Block Unknown" if no other blocks exist
        const sortedBlocks = Object.keys(bedsByBlock)
            .sort((a, b) => {
                // Put "Block Unknown" at the end
                if (a === 'Block Unknown') return 1;
                if (b === 'Block Unknown') return -1;

                const aNum = parseInt(a.replace('Block ', '')) || 999;
                const bNum = parseInt(b.replace('Block ', '')) || 999;
                return aNum - bNum;
            });

        // If we only have "Block Unknown", show it; otherwise filter it out
        const finalBlocks = sortedBlocks.length > 1
            ? sortedBlocks.filter(blockName => blockName !== 'Block Unknown')
            : sortedBlocks;

        // Create bed rows grouped by block
        const bedRows = finalBlocks.map(blockName => {
            const blockBeds = bedsByBlock[blockName];

            // Sort beds within block by bed number
            blockBeds.sort((a, b) => {
                const aMatch = a.name.match(/\/(\d+)/);
                const bMatch = b.name.match(/\/(\d+)/);
                const aNum = aMatch ? parseInt(aMatch[1]) : 999;
                const bNum = bMatch ? parseInt(bMatch[1]) : 999;
                return aNum - bNum;
            });

            const blockBedRows = blockBeds.map(bed => {
                // Match plantings by bed name (e.g., "1/1"), not UUID
                const bedPlantings = actualBedData.plantings.filter(p => p.bed_id === bed.name);
                
                // console.log(`🔍 Bed ${bed.name} (id: ${bed.id}):`, {
                //     bedPlantingsFound: bedPlantings.length,
                //     allPlantings: actualBedData.plantings.length,
                //     samplePlanting: actualBedData.plantings[0]
                // });

                // Create occupancy blocks for this bed
                const occupancyBlocks = bedPlantings.flatMap(planting => {
                    const blocks = [];
                    const varietyText = planting.variety ? ` (${planting.variety})` : '';
                    const notesText = planting.notes ? ` | ${planting.notes}` : '';
                    
                    // Determine start date: seeding for direct-seeded, transplant for transplanted
                    const bedOccupancyStart = planting.is_direct_seeded && planting.seeding_date 
                        ? planting.seeding_date 
                        : planting.transplant_date || planting.start_date;
                    
                    const harvestStart = planting.harvest_date;
                    const harvestEnd = planting.end_date;
                    
                    if (!bedOccupancyStart) {
                        return blocks; // Skip if we don't know when it started occupying the bed
                    }
                    
                    const occupancyStartDate = new Date(bedOccupancyStart);
                    
                    // GREEN BLOCK: Growing phase (bed occupancy start → harvest start)
                    if (harvestStart) {
                        const harvestStartDate = new Date(harvestStart);
                        
                        const growingLeft = ((occupancyStartDate - minDate) / (maxDate - minDate)) * 100;
                        const growingWidth = ((harvestStartDate - occupancyStartDate) / (maxDate - minDate)) * 100;
                        
                        const startLabel = planting.is_direct_seeded ? 'Seeded' : 'Transplanted';
                        const growingTooltip = `${planting.crop}${varietyText}\n${startLabel}: ${bedOccupancyStart}\nMatures: ${harvestStart}${notesText}`;
                        
                        blocks.push(`
                            <div class="bed-occupancy-block growing"
                                 style="left: ${Math.max(0, growingLeft)}%; width: ${Math.max(2, Math.min(100 - growingLeft, growingWidth))}%; background: linear-gradient(135deg, #28a745, #20c997);"
                                 title="${growingTooltip}">
                                <span class="crop-label">${planting.crop}${varietyText}</span>
                            </div>
                        `);
                        
                        // YELLOW BLOCK: Harvest window (harvest start → harvest end)
                        if (harvestEnd) {
                            const harvestEndDate = new Date(harvestEnd);
                            const harvestLeft = ((harvestStartDate - minDate) / (maxDate - minDate)) * 100;
                            const harvestWidth = ((harvestEndDate - harvestStartDate) / (maxDate - minDate)) * 100;
                            
                            const harvestTooltip = `${planting.crop}${varietyText}\nHarvest Window: ${harvestStart} → ${harvestEnd}${notesText}`;
                            
                            blocks.push(`
                                <div class="bed-occupancy-block harvesting"
                                     style="left: ${Math.max(0, harvestLeft)}%; width: ${Math.max(2, Math.min(100 - harvestLeft, harvestWidth))}%; background: linear-gradient(135deg, #ffc107, #ffb300);"
                                     title="${harvestTooltip}">
                                    <span class="crop-label">🌾 ${planting.crop}</span>
                                </div>
                            `);
                        }
                    } else {
                        // No harvest date yet - show single green block for growing
                        const growingLeft = ((occupancyStartDate - minDate) / (maxDate - minDate)) * 100;
                        const growingWidth = ((new Date(maxDate) - occupancyStartDate) / (maxDate - minDate)) * 100;
                        
                        const startLabel = planting.is_direct_seeded ? 'Seeded' : 'Transplanted';
                        const tooltip = `${planting.crop}${varietyText}\n${startLabel}: ${bedOccupancyStart}\nStill growing${notesText}`;
                        
                        blocks.push(`
                            <div class="bed-occupancy-block growing"
                                 style="left: ${Math.max(0, growingLeft)}%; width: ${Math.max(2, Math.min(100 - growingLeft, growingWidth))}%; background: linear-gradient(135deg, #28a745, #20c997);"
                                 title="${tooltip}">
                                <span class="crop-label">${planting.crop}${varietyText}</span>
                            </div>
                        `);
                    }
                    
                    return blocks;
                }).join('');

                return `
                    <div class="bed-row">
                        <div class="bed-label">${bed.name}</div>
                        <div class="bed-timeline" data-bed-id="${bed.id}" data-bed-name="${bed.name}">
                            ${occupancyBlocks}
                        </div>
                    </div>
                `;
            }).join('');

            return `
                <div class="bed-block">
                    <div class="bed-block-header">
                        <h6>
                            <i class="fas fa-tree hedgerow-icon"></i>
                            <i class="fas fa-tree hedgerow-icon"></i>
                            ${blockName}
                            <i class="fas fa-tree hedgerow-icon"></i>
                            <i class="fas fa-tree hedgerow-icon"></i>
                        </h6>
                        <div class="hedgerow-indicator">
                            <small class="text-muted">Hedgerow Boundary</small>
                        </div>
                    </div>
                    <div class="bed-block-content">
                        ${blockBedRows}
                    </div>
                </div>
                <div class="hedgerow-divider">
                    <div class="hedgerow-visual">
                        <i class="fas fa-tree hedgerow-tree"></i>
                        <i class="fas fa-tree hedgerow-tree"></i>
                        <span class="hedgerow-text">Hedgerow Boundary</span>
                        <i class="fas fa-tree hedgerow-tree"></i>
                        <i class="fas fa-tree hedgerow-tree"></i>
                    </div>
                </div>
            `;
        }).join('');

        // Add succession planning indicators
        const successionIndicators = [];
        if (plan.plantings) {
            plan.plantings.forEach((planting, index) => {
                const harvestDate = planting.harvest_date ? new Date(planting.harvest_date) : null;
                if (harvestDate) {
                    const left = ((harvestDate - minDate) / (maxDate - minDate)) * 100;
                    successionIndicators.push(`
                        <div class="succession-indicator"
                             style="left: ${left}%;"
                             title="Succession ${index + 1} Harvest: ${planting.crop_name || 'Unknown'} on ${planting.harvest_date}">
                            <i class="fas fa-star text-warning"></i>
                        </div>
                    `);
                }
            });
        }

        return `
            <div class="bed-occupancy-timeline" data-min-date="${minDate.toISOString()}" data-max-date="${maxDate.toISOString()}">
                <div class="timeline-header">
                    <h5><i class="fas fa-seedling text-success"></i> Real FarmOS Bed Occupancy</h5>
                    <p class="text-muted small">Live bed availability from your FarmOS database • Yellow stars show planned harvest dates</p>
                </div>

                <div class="timeline-axis">
                    ${months.map(month => `<div class="timeline-month">${month.label}</div>`).join('')}
                </div>

                <div class="beds-container">
                    ${bedRows}
                </div>

                <div class="timeline-indicators">
                    ${successionIndicators.join('')}
                </div>

                <div class="timeline-legend">
                    <div class="legend-item">
                        <div class="legend-color active"></div>
                        <span>Currently Planted</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color completed"></div>
                        <span>Recently Harvested</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color available"></div>
                        <span>Available</span>
                    </div>
                    <div class="legend-item">
                        <i class="fas fa-star text-warning"></i>
                        <span>Succession Harvest</span>
                    </div>
                </div>
            </div>
        `;

        // Initialize drag and drop for the newly created timeline
        requestAnimationFrame(() => {
            // Re-attach drop listeners to bed timelines (only if not already attached)
            document.querySelectorAll('.bed-timeline').forEach(timeline => {
                // Check if listeners are already attached to avoid duplicates
                if (!timeline.hasAttribute('data-listeners-attached')) {
                    timeline.addEventListener('dragover', handleDragOver);
                    timeline.addEventListener('dragleave', handleDragLeave);
                    timeline.addEventListener('drop', handleDrop);
                    timeline.setAttribute('data-listeners-attached', 'true');
                    console.log('✅ Attached drop listeners to bed timeline');
                }
            });
        });
    }

    // Initialize drag and drop for successions
    function initializeDragAndDrop() {
        // Remove existing listeners first to avoid duplicates
        document.querySelectorAll('.succession-item[draggable="true"]').forEach(item => {
            item.removeEventListener('dragstart', handleDragStart);
            item.removeEventListener('dragend', handleDragEnd);
        });

        document.querySelectorAll('.bed-timeline').forEach(timeline => {
            timeline.removeEventListener('dragover', handleDragOver);
            timeline.removeEventListener('dragleave', handleDragLeave);
            timeline.removeEventListener('drop', handleDrop);
        });

        const successionItems = document.querySelectorAll('.succession-item[draggable="true"]');
        const bedTimelines = document.querySelectorAll('.bed-timeline');

        successionItems.forEach(item => {
            item.addEventListener('dragstart', handleDragStart);
            item.addEventListener('dragend', handleDragEnd);
        });

        bedTimelines.forEach(timeline => {
            timeline.addEventListener('dragover', handleDragOver);
            timeline.addEventListener('dragleave', handleDragLeave);
            timeline.addEventListener('drop', handleDrop);
        });
    }

    function handleDragStart(e) {
        // Safety check: ensure element has succession index
        if (!e.target.dataset.successionIndex) {
            console.error('❌ Drag started on element without successionIndex:', e.target);
            e.preventDefault();
            return;
        }
        
        e.dataTransfer.setData('text/plain', e.target.dataset.successionIndex);
        e.target.classList.add('dragging');

        // Add visual feedback to potential drop targets
        document.querySelectorAll('.bed-timeline').forEach(timeline => {
            timeline.classList.add('drop-target');
        });
    }

    function handleDragEnd(e) {
        // Safety check: ensure element has classList
        if (!e.target || !e.target.classList) {
            console.error('❌ Drag ended on invalid element:', e.target);
            return;
        }
        
        e.target.classList.remove('dragging');

        // Remove visual feedback from drop targets
        document.querySelectorAll('.bed-timeline').forEach(timeline => {
            timeline.classList.remove('drop-target', 'drop-active', 'drop-conflict');
            removeDragPreview(timeline);
        });
    }

    function handleDragOver(e) {
        e.preventDefault();

        const dragType = e.dataTransfer.getData('text/plain');
        const bedTimeline = e.currentTarget;
        const bedId = bedTimeline.dataset.bedId;

        let successionData;

        if (dragType === 'block-move') {
            // Moving an existing block
            const jsonData = e.dataTransfer.getData('application/json');
            if (!jsonData || jsonData === 'undefined') {
                console.log('⚠️ No allocation data for drag preview');
                return;
            }
            const allocationData = JSON.parse(jsonData);
            successionData = {
                sowDate: new Date(allocationData.sowDate),
                transplantDate: allocationData.transplantDate ? new Date(allocationData.transplantDate) : null,
                harvestDate: new Date(allocationData.harvestDate),
                method: allocationData.method
            };
        } else {
            // Dragging a new succession from sidebar
            const successionIndex = dragType;
            const successionItem = document.querySelector(`[data-succession-index="${successionIndex}"]`);
            if (!successionItem) return;

            successionData = JSON.parse(successionItem.dataset.successionData);
            successionData.sowDate = new Date(successionData.sowDate);
            if (successionData.transplantDate) {
                successionData.transplantDate = new Date(successionData.transplantDate);
            }
            successionData.harvestDate = new Date(successionData.harvestDate);
        }

        // Check for conflicts
        if (checkBedConflicts(bedId, successionData)) {
            // Show conflict state
            bedTimeline.classList.remove('drop-active');
            bedTimeline.classList.add('drop-conflict');
            removeDragPreview(bedTimeline);
        } else {
            // Show valid drop state
            bedTimeline.classList.remove('drop-conflict');
            bedTimeline.classList.add('drop-active');
            showDragPreview(bedTimeline, successionData, bedId);
        }
    }

    function handleDragLeave(e) {
        e.currentTarget.classList.remove('drop-active', 'drop-conflict');
        removeDragPreview(e.currentTarget);
    }

    function showDragPreview(bedTimeline, successionData, bedId) {
        // Remove any existing preview
        removeDragPreview(bedTimeline);

        // Calculate position based on succession dates
        const timelineStart = new Date(bedTimeline.dataset.startDate);
        const timelineEnd = new Date(bedTimeline.dataset.endDate);
        const totalDays = (timelineEnd - timelineStart) / (1000 * 60 * 60 * 24);

        const startDate = successionData.sowDate;
        const endDate = successionData.harvestDate;

        const startOffset = (startDate - timelineStart) / (1000 * 60 * 60 * 24);
        const duration = (endDate - startDate) / (1000 * 60 * 60 * 24);

        const leftPercent = (startOffset / totalDays) * 100;
        const widthPercent = (duration / totalDays) * 100;

        // Create preview element
        const preview = document.createElement('div');
        preview.className = 'drag-preview';
        preview.style.left = `${Math.max(0, leftPercent)}%`;
        preview.style.width = `${Math.min(100 - leftPercent, widthPercent)}%`;
        preview.innerHTML = `
            <div class="drag-preview-content">
                <small>Drop here</small>
            </div>
        `;

        bedTimeline.appendChild(preview);
    }

    function removeDragPreview(bedTimeline) {
        const existingPreview = bedTimeline.querySelector('.drag-preview');
        if (existingPreview) {
            existingPreview.remove();
        }
    }

    function handleDrop(e) {
        e.preventDefault();
        e.currentTarget.classList.remove('drop-active', 'drop-target');

        console.log('🎯 Drop event triggered on bed timeline');

        const dragType = e.dataTransfer.getData('text/plain');
        console.log('📋 Drag type:', dragType);

        const bedTimeline = e.currentTarget;
        const bedRow = bedTimeline.closest('.bed-row');
        const bedName = bedRow.querySelector('.bed-label').textContent;
        const bedId = bedTimeline.dataset.bedId;

        console.log('🏡 Drop target - Bed name:', bedName, 'Bed ID:', bedId);

        if (dragType === 'block-move') {
            // Moving an existing succession block
            const jsonData = e.dataTransfer.getData('application/json');
            if (!jsonData || jsonData === 'undefined') {
                console.error('❌ No allocation data found for block move');
                return;
            }
            const allocationData = JSON.parse(jsonData);
            console.log('🏗️ Moving existing block:', allocationData);

            // Check if dropping on the same bed
            if (allocationData.bedId === bedId) {
                console.log('📍 Dropped on same bed, no change needed');
                return;
            }

            // Check for conflicts
            if (checkBedConflicts(bedId, {
                sowDate: new Date(allocationData.sowDate),
                transplantDate: allocationData.transplantDate ? new Date(allocationData.transplantDate) : null,
                harvestDate: new Date(allocationData.harvestDate),
                method: allocationData.method
            })) {
                showConflictError(bedRow);
                return;
            }

            // Update allocation
            let allocations = JSON.parse(localStorage.getItem('bedAllocations') || '[]');
            const existingAllocation = allocations.find(a => a.successionIndex === allocationData.successionIndex && a.bedId === allocationData.bedId);
            if (existingAllocation) {
                existingAllocation.bedId = bedId;
                existingAllocation.bedName = bedName;
                localStorage.setItem('bedAllocations', JSON.stringify(allocations));

                // Update succession item badge
                const successionItem = document.querySelector(`[data-succession-index="${allocationData.successionIndex - 1}"]`);
                if (successionItem) {
                    const badge = successionItem.querySelector('.bed-allocation-badge');
                    if (badge) {
                        badge.textContent = `Allocated to ${bedName}`;
                    }
                    
                    // Update the Details section in the tab pane
                    const tabPane = document.querySelector(`#tab-${allocationData.successionIndex - 1}`);
                    if (tabPane) {
                        const successionInfo = tabPane.querySelector('.succession-info');
                        if (successionInfo) {
                            // Find the bed paragraph (first paragraph in the info section)
                            const paragraphs = successionInfo.querySelectorAll('p');
                            if (paragraphs.length > 0) {
                                paragraphs[0].innerHTML = `<strong>Bed:</strong> ${bedName}`;
                                // console.log('✅ Updated Details section bed to:', bedName, 'for succession', allocationData.successionIndex);
                            }
                        }
                        
                        // Check if this is a transplant method
                        const isTransplant = allocationData.transplantDate || allocationData.method?.toLowerCase().includes('transplant');
                        
                        // Update location in seeding form
                        const seedingLocationInput = tabPane.querySelector(`input[name="plantings[${allocationData.successionIndex - 1}][seeding][location]"]`);
                        if (seedingLocationInput) {
                            // If transplant, seeding location should be "Propagation", otherwise use bed name
                            seedingLocationInput.value = isTransplant ? 'Propagation' : bedName;
                            // console.log('✅ Updated seeding location to:', isTransplant ? 'Propagation' : bedName);
                        }
                        
                        // Update location in transplant form (only if it's a transplant)
                        const transplantLocationInput = tabPane.querySelector(`input[name="plantings[${allocationData.successionIndex - 1}][transplanting][location]"]`);
                        if (transplantLocationInput) {
                            transplantLocationInput.value = bedName;
                            // console.log('✅ Updated transplant location to:', bedName);
                        }
                    }
                }

                // Remove old block and create new one
                const oldBlock = document.querySelector(`[data-succession-index="${allocationData.successionIndex - 1}"][data-allocation-data*="${allocationData.bedId}"]`);
                if (oldBlock) {
                    oldBlock.remove();
                }

                // Create new block on target bed
                // Use existing harvestEndDate if available, otherwise add 2 weeks to harvestDate
                const harvestEndDate = allocationData.harvestEndDate 
                    ? new Date(allocationData.harvestEndDate)
                    : new Date(new Date(allocationData.harvestDate).getTime() + (14 * 24 * 60 * 60 * 1000));
                
                createSuccessionBlock(bedTimeline, {
                    successionNumber: allocationData.successionIndex,
                    sowDate: new Date(allocationData.sowDate),
                    transplantDate: allocationData.transplantDate ? new Date(allocationData.transplantDate) : null,
                    harvestDate: new Date(allocationData.harvestDate),
                    method: allocationData.method
                }, new Date(allocationData.occupationStart), new Date(allocationData.harvestDate), harvestEndDate);

                console.log('✅ Moved succession block to new bed:', bedName);
            }
        } else {
            // Dropping a new succession from sidebar
            const successionIndex = dragType;
            console.log('📋 Succession index from drag data:', successionIndex);

            // Safety check: ensure successionIndex is valid
            if (successionIndex === undefined || successionIndex === null || successionIndex === '') {
                console.error('❌ Invalid succession index from drag data:', successionIndex);
                return;
            }

            // Get succession data
            const successionItem = document.querySelector(`[data-succession-index="${successionIndex}"]`);
            if (!successionItem) {
                console.error('❌ Succession item not found for index:', successionIndex);
                console.log('Available succession items:', document.querySelectorAll('[data-succession-index]').length);
                return;
            }

            const successionData = JSON.parse(successionItem.dataset.successionData);
            console.log('🌱 Succession data:', successionData);

            // Convert ISO date strings back to Date objects
            successionData.sowDate = new Date(successionData.sowDate);
            if (successionData.transplantDate) {
                successionData.transplantDate = new Date(successionData.transplantDate);
            }
            successionData.harvestDate = new Date(successionData.harvestDate);

            // Check for conflicts with existing plantings
            if (checkBedConflicts(bedId, successionData)) {
                showConflictError(bedRow);
                return;
            }

            // Allocate succession to bed with proper positioning
            allocateSuccessionToBed(bedName, bedId, successionData, successionIndex, bedTimeline);

            // Immediately update the succession card UI
            successionItem.classList.add('allocated');
            successionItem.dataset.allocationData = JSON.stringify({
                bedName: bedName,
                bedId: bedId,
                successionIndex: parseInt(successionIndex) + 1
            });
            
            // Add bed badge immediately
            const header = successionItem.querySelector('.succession-header');
            if (header) {
                // Remove existing badge if present
                const existingBadge = header.querySelector('.bed-allocation-badge');
                if (existingBadge) {
                    existingBadge.remove();
                }
                
                // Add new badge
                const badge = document.createElement('span');
                badge.className = 'bed-allocation-badge badge bg-success';
                badge.innerHTML = `<i class="fas fa-map-marker-alt"></i> ${bedName}`;
                
                // Update the Details section in the tab pane
                const tabPane = document.querySelector(`#tab-${successionIndex}`);
                if (tabPane) {
                    const successionInfo = tabPane.querySelector('.succession-info');
                    if (successionInfo) {
                        // Find the bed paragraph (first paragraph in the info section)
                        const paragraphs = successionInfo.querySelectorAll('p');
                        if (paragraphs.length > 0) {
                            paragraphs[0].innerHTML = `<strong>Bed:</strong> ${bedName}`;
                            // console.log('✅ Updated Details section bed to:', bedName);
                        }
                    }
                    
                    // Check if this is a transplant method
                    const isTransplant = successionData.transplantDate || successionData.method?.toLowerCase().includes('transplant');
                    
                    // Update location in seeding form
                    const seedingLocationInput = tabPane.querySelector(`input[name="plantings[${successionIndex}][seeding][location]"]`);
                    if (seedingLocationInput) {
                        // If transplant, seeding location should be "Propagation", otherwise use bed name
                        seedingLocationInput.value = isTransplant ? 'Propagation' : bedName;
                        // console.log('✅ Updated seeding location to:', isTransplant ? 'Propagation' : bedName);
                    }
                    
                    // Update location in transplant form (only if it's a transplant)
                    const transplantLocationInput = tabPane.querySelector(`input[name="plantings[${successionIndex}][transplanting][location]"]`);
                    if (transplantLocationInput) {
                        transplantLocationInput.value = bedName;
                        // console.log('✅ Updated transplant location to:', bedName);
                    }
                }
                badge.title = 'Click to remove allocation';
                badge.style.cursor = 'pointer';
                badge.onclick = (e) => {
                    e.stopPropagation();
                    removeSuccessionAllocation(successionIndex);
                };
                header.appendChild(badge);
                
                // console.log('✅ Added bed badge to succession card:', bedName);
            }

            // Visual feedback
            showAllocationFeedback(bedRow, successionData);
        }
    }

    function allocateSuccessionToBed(bedName, bedId, successionData, successionIndex, bedTimeline) {
        // Determine occupation start date based on planting method
        const occupationStart = successionData.method.toLowerCase().includes('transplant') && successionData.transplantDate
            ? successionData.transplantDate
            : successionData.sowDate;

        // Determine occupation end date - include harvest period (add 2 weeks buffer for harvest)
        const harvestEndDate = new Date(successionData.harvestDate);
        harvestEndDate.setDate(harvestEndDate.getDate() + 14); // Add 2 weeks for harvest period
        const occupationEnd = harvestEndDate;

        // Create visual succession block on the timeline with harvest window
        createSuccessionBlock(bedTimeline, successionData, occupationStart, successionData.harvestDate, occupationEnd);

        // Store allocation
        const allocation = {
            bedName: bedName,
            bedId: bedId,
            successionIndex: parseInt(successionIndex) + 1,
            sowDate: successionData.sowDate.toISOString().split('T')[0],
            transplantDate: successionData.transplantDate ? successionData.transplantDate.toISOString().split('T')[0] : null,
            harvestDate: successionData.harvestDate.toISOString().split('T')[0],
            harvestEndDate: occupationEnd.toISOString().split('T')[0],
            occupationStart: occupationStart.toISOString().split('T')[0],
            occupationEnd: occupationEnd.toISOString().split('T')[0],
            method: successionData.method
        };

        // Store in localStorage for now (could be replaced with API call)
        let allocations = JSON.parse(localStorage.getItem('bedAllocations') || '[]');
        allocations.push(allocation);
        localStorage.setItem('bedAllocations', JSON.stringify(allocations));

        // Mark succession as allocated and add bed badge
        const successionItem = document.querySelector(`[data-succession-index="${successionIndex}"]`);
        if (successionItem) {
            successionItem.classList.add('allocated');

            // Add bed allocation badge
            const header = successionItem.querySelector('.succession-header');
            if (header) {
                // Remove existing badge if present
                const existingBadge = header.querySelector('.bed-allocation-badge');
                if (existingBadge) {
                    existingBadge.remove();
                }

                // Add new badge
                const badge = document.createElement('span');
                badge.className = 'bed-allocation-badge badge bg-success';
                badge.textContent = `Allocated to ${bedName}`;
                badge.title = `Click to remove allocation`;
                badge.style.cursor = 'pointer';
                badge.onclick = () => removeSuccessionAllocation(successionIndex);
                header.appendChild(badge);
            }

            // Store allocation data on the element for quickforms
            successionItem.dataset.allocationData = JSON.stringify(allocation);
        }

        // console.log('✅ Allocated succession to bed:', allocation);
    }

    function removeSuccessionAllocation(successionIndex) {
        // Remove from localStorage
        let allocations = JSON.parse(localStorage.getItem('bedAllocations') || '[]');
        allocations = allocations.filter(a => a.successionIndex !== parseInt(successionIndex) + 1);
        localStorage.setItem('bedAllocations', JSON.stringify(allocations));

        // Remove visual allocation from timeline
        const successionBlocks = document.querySelectorAll('.succession-allocation-block');
        successionBlocks.forEach(block => {
            if (block.querySelector('.succession-label')?.textContent === `S${parseInt(successionIndex) + 1}`) {
                block.remove();
            }
        });

        // Reset succession item appearance
        const successionItem = document.querySelector(`[data-succession-index="${successionIndex}"]`);
        if (successionItem) {
            successionItem.classList.remove('allocated');
            const badge = successionItem.querySelector('.bed-allocation-badge');
            if (badge) {
                badge.remove();
            }
            delete successionItem.dataset.allocationData;
        }

        console.log('🗑️ Removed allocation for succession:', parseInt(successionIndex) + 1);
    }

    function clearAllAllocations() {
        if (confirm('Are you sure you want to clear all bed allocations? This will allow you to manually reassign successions by dragging.')) {
            localStorage.removeItem('bedAllocations');

            // Remove all visual allocation blocks from timelines
            document.querySelectorAll('.succession-allocation-block, .succession-block-container').forEach(block => {
                block.remove();
            });

            // Reset all succession items to unallocated state
            document.querySelectorAll('.succession-item').forEach(item => {
                item.classList.remove('allocated');
                const badge = item.querySelector('.bed-allocation-badge');
                if (badge) badge.remove();
                delete item.dataset.allocationData;
            });

            console.log('🗑️ Cleared all bed allocations');
            showToast('All allocations cleared. You can now manually assign successions by dragging.', 'info');
        }
    }

    function getSuccessionAllocation(successionIndex) {
        // Return allocation data for a specific succession
        const allocations = JSON.parse(localStorage.getItem('bedAllocations') || '[]');
        return allocations.find(a => a.successionIndex === parseInt(successionIndex) + 1);
    }

    function checkBedConflicts(bedId, successionData) {
        // Get existing allocations for this bed
        const allocations = JSON.parse(localStorage.getItem('bedAllocations') || '[]');
        const bedAllocations = allocations.filter(a => a.bedId === bedId);

        // Determine occupation period for the new succession (including harvest time)
        const occupationStart = successionData.method.toLowerCase().includes('transplant') && successionData.transplantDate
            ? successionData.transplantDate
            : successionData.sowDate;
        const harvestEndDate = new Date(successionData.harvestDate);
        harvestEndDate.setDate(harvestEndDate.getDate() + 14); // Add 2 weeks for harvest period
        const occupationEnd = harvestEndDate;

        // Check for overlaps with existing allocations
        for (const allocation of bedAllocations) {
            const existingStart = new Date(allocation.occupationStart);
            const existingEnd = new Date(allocation.occupationEnd || allocation.harvestDate);

            // Check for time overlap
            if (occupationStart < existingEnd && occupationEnd > existingStart) {
                return true; // Conflict found
            }
        }

        return false; // No conflicts
    }

    function showConflictError(bedRow) {
        // Visual feedback for conflict
        const timeline = bedRow.querySelector('.bed-timeline');
        timeline.classList.add('conflict-error');

        // Add error message
        const errorMsg = document.createElement('div');
        errorMsg.className = 'conflict-message';
        errorMsg.textContent = '❌ Bed occupied during this period';
        timeline.appendChild(errorMsg);

        // Remove after 3 seconds
        setTimeout(() => {
            timeline.classList.remove('conflict-error');
            if (errorMsg.parentNode) {
                errorMsg.remove();
            }
        }, 3000);
    }

    function createSuccessionBlock(bedTimeline, successionData, startDate, harvestDate, endDate) {
        console.log('🎨 Creating succession block:', {
            successionNumber: successionData.successionNumber,
            startDate: startDate.toISOString(),
            harvestDate: harvestDate.toISOString(),
            endDate: endDate.toISOString()
        });

        // Get timeline date range from data attributes
        const timelineContainer = bedTimeline.closest('.bed-occupancy-timeline');
        const minDateStr = timelineContainer.dataset.minDate;
        const maxDateStr = timelineContainer.dataset.maxDate;

        console.log('📅 Timeline date range:', { minDateStr, maxDateStr });

        if (!minDateStr || !maxDateStr) {
            console.error('Timeline date range not found');
            return;
        }

        const timelineStart = new Date(minDateStr);
        const timelineEnd = new Date(maxDateStr);

        console.log('📅 Parsed timeline dates:', {
            timelineStart: timelineStart.toISOString(),
            timelineEnd: timelineEnd.toISOString()
        });

        // Calculate positions for growing period and harvest window
        const totalDuration = timelineEnd - timelineStart;

        // Growing period (from start to harvest)
        const growingLeft = ((startDate - timelineStart) / totalDuration) * 100;
        const growingWidth = ((harvestDate - startDate) / totalDuration) * 100;

        // Harvest window (from harvest to end)
        const harvestLeft = ((harvestDate - timelineStart) / totalDuration) * 100;
        const harvestWidth = ((endDate - harvestDate) / totalDuration) * 100;

        console.log('📐 Calculated positions:', {
            growingLeft, growingWidth,
            harvestLeft, harvestWidth,
            totalDuration,
            startDate: startDate.toISOString(),
            harvestDate: harvestDate.toISOString(),
            endDate: endDate.toISOString(),
            timelineStart: timelineStart.toISOString(),
            timelineEnd: timelineEnd.toISOString()
        });

        // Create container for the succession block
        const blockContainer = document.createElement('div');
        blockContainer.className = 'succession-block-container';
        blockContainer.style.left = `${Math.max(0, growingLeft)}%`;
        blockContainer.style.width = `${Math.max(2, Math.min(100 - growingLeft, growingWidth + harvestWidth))}%`;

        // Growing period block
        if (growingWidth > 0) {
            const growingBlock = document.createElement('div');
            growingBlock.className = 'succession-growing-block';
            growingBlock.style.left = '0%';
            growingBlock.style.width = growingWidth > 0 ? `${(growingWidth / (growingWidth + harvestWidth)) * 100}%` : '100%';
            growingBlock.title = `Succession ${successionData.successionNumber} - Growing Period (${startDate.toLocaleDateString()} - ${harvestDate.toLocaleDateString()})`;

            const growingLabel = document.createElement('span');
            growingLabel.className = 'succession-label';
            growingLabel.textContent = `S${successionData.successionNumber}`;
            growingBlock.appendChild(growingLabel);
            blockContainer.appendChild(growingBlock);
        }

        // Harvest window block
        if (harvestWidth > 0) {
            const harvestBlock = document.createElement('div');
            harvestBlock.className = 'succession-harvest-block';
            harvestBlock.style.left = growingWidth > 0 ? `${(growingWidth / (growingWidth + harvestWidth)) * 100}%` : '0%';
            harvestBlock.style.width = harvestWidth > 0 ? `${(harvestWidth / (growingWidth + harvestWidth)) * 100}%` : '0%';
            harvestBlock.title = `Succession ${successionData.successionNumber} - Harvest Window (${harvestDate.toLocaleDateString()} - ${endDate.toLocaleDateString()})`;

            const harvestLabel = document.createElement('span');
            harvestLabel.className = 'succession-label';
            harvestLabel.textContent = `H${successionData.successionNumber}`;
            harvestBlock.appendChild(harvestLabel);
            blockContainer.appendChild(harvestBlock);
        }

        // Add drag functionality to the container
        blockContainer.draggable = true;
        blockContainer.dataset.successionIndex = successionData.successionNumber - 1;
        blockContainer.dataset.allocationData = JSON.stringify({
            bedName: bedTimeline.closest('.bed-row').querySelector('.bed-label').textContent,
            bedId: bedTimeline.dataset.bedId,
            successionIndex: successionData.successionNumber,
            sowDate: successionData.sowDate.toISOString().split('T')[0],
            transplantDate: successionData.transplantDate ? successionData.transplantDate.toISOString().split('T')[0] : null,
            harvestDate: successionData.harvestDate.toISOString().split('T')[0],
            harvestEndDate: endDate.toISOString().split('T')[0],
            occupationStart: startDate.toISOString().split('T')[0],
            occupationEnd: endDate.toISOString().split('T')[0],
            method: successionData.method
        });

        // Add drag functionality
        blockContainer.addEventListener('dragstart', handleBlockDragStart);
        blockContainer.addEventListener('dragend', handleBlockDragEnd);

        // Add right-click delete
        blockContainer.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            if (confirm(`Remove Succession ${successionData.successionNumber} from this bed?`)) {
                removeSuccessionBlock(blockContainer);
            }
        });

        bedTimeline.appendChild(blockContainer);
        // console.log('✅ Succession block with harvest window added to timeline');
    }

    function handleBlockDragStart(e) {
        e.dataTransfer.setData('text/plain', 'block-move');
        e.dataTransfer.setData('application/json', e.target.dataset.allocationData);
        e.target.classList.add('dragging');
        console.log('🏗️ Started dragging succession block');
    }

    function handleBlockDragEnd(e) {
        e.target.classList.remove('dragging');
        document.querySelectorAll('.bed-timeline').forEach(timeline => {
            timeline.classList.remove('drop-target', 'drop-active', 'drop-conflict');
        });
        console.log('🏗️ Finished dragging succession block');
    }

    function removeSuccessionBlock(block) {
        const allocationData = JSON.parse(block.dataset.allocationData);
        const successionIndex = allocationData.successionIndex - 1;

        // Remove from localStorage
        let allocations = JSON.parse(localStorage.getItem('bedAllocations') || '[]');
        allocations = allocations.filter(a => !(a.successionIndex === allocationData.successionIndex && a.bedId === allocationData.bedId));
        localStorage.setItem('bedAllocations', JSON.stringify(allocations));

        // Remove the block
        block.remove();

        // Reset succession item appearance
        const successionItem = document.querySelector(`[data-succession-index="${successionIndex}"]`);
        if (successionItem) {
            successionItem.classList.remove('allocated');
            const badge = successionItem.querySelector('.bed-allocation-badge');
            if (badge) {
                badge.remove();
            }
            delete successionItem.dataset.allocationData;
        }

        console.log('🗑️ Removed succession block:', allocationData);
    }

    function showAllocationFeedback(bedRow, successionData) {
        // Add visual indicator
        const timeline = bedRow.querySelector('.bed-timeline');
        const indicator = document.createElement('div');
        indicator.className = 'allocated-succession';
        indicator.textContent = `Succession ${successionData.successionNumber || 'N/A'}`;
        timeline.appendChild(indicator);

        // Remove after 3 seconds
        setTimeout(() => {
            if (indicator.parentNode) {
                indicator.remove();
            }
        }, 3000);
    }

    function toggleQuickForm(successionIndex, formType, forceShow = null) {
        const checkbox = document.getElementById(`${formType}-enabled-${successionIndex}`);
        const formElement = document.getElementById(`quick-form-${formType}-${successionIndex}`);

        if (checkbox && formElement) {
            // If forceShow is specified, use it; otherwise check the checkbox state
            const shouldShow = forceShow !== null ? forceShow : checkbox.checked;
            
            if (shouldShow) {
                formElement.style.display = 'block';
            } else {
                formElement.style.display = 'none';
            }
        }
    }



    async function toggleForm(formId, url) {
        const formContainer = document.getElementById(formId);
        const button = event.target.closest('button');

        if (formContainer.style.display === 'none' || formContainer.style.display === '') {
            // Show the form
            formContainer.style.display = 'block';
            button.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Form';
            button.classList.remove('btn-success');
            button.classList.add('btn-warning');

            // Load form content if not already loaded
            if (!formContainer.dataset.loaded) {
                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        const html = await response.text();
                        // Extract the form content (remove layout wrapper)
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const formContent = doc.querySelector('.container') || doc.body;

                        if (formContent) {
                            formContainer.innerHTML = formContent.innerHTML;
                            formContainer.dataset.loaded = 'true';
                        } else {
                            formContainer.innerHTML = '<div class="alert alert-warning">Could not load form content</div>';
                        }
                    } else {
                        formContainer.innerHTML = '<div class="alert alert-danger">Failed to load form</div>';
                    }
                } catch (error) {
                    console.error('Error loading form:', error);
                    formContainer.innerHTML = '<div class="alert alert-danger">Error loading form</div>';
                }
            }
        } else {
            // Hide the form
            formContainer.style.display = 'none';
            button.innerHTML = '<i class="fas fa-eye"></i> Show Form';
            button.classList.remove('btn-warning');
            button.classList.add('btn-success');
        }
    }

    function copyLink(encodedUrl) {
        const url = decodeURIComponent(encodedUrl);
        navigator.clipboard.writeText(url).then(() => {
            // Show a brief success message
            const notification = document.createElement('div');
            notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                <i class="fas fa-check"></i> Link copied to clipboard!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        }).catch(err => {
            console.error('Failed to copy link:', err);
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = url;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            const notification = document.createElement('div');
            notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                <i class="fas fa-check"></i> Link copied to clipboard!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        });
    }

    async function askAIAboutPlan() {
        if (!currentSuccessionPlan) {
            showToast('Please calculate a succession plan first', 'warning');
            return;
        }

        const analyzePlanBtn = document.getElementById('analyzePlanBtn');
        const originalText = analyzePlanBtn.innerHTML;
        
        try {
            // Show loading state
            analyzePlanBtn.disabled = true;
            analyzePlanBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyzing...';

            const plan = currentSuccessionPlan;
            
            // Check if this is a varietal succession (different varieties used)
            const uniqueVarieties = [...new Set(plan.plantings.map(p => p.variety_name))];
            const isVarietalSuccession = uniqueVarieties.length > 1;
            
            // Build detailed context object for backend
            const context = {
                has_plan: true,
                plan: {
                    crop_name: plan.plantings[0]?.crop_name || 'Unknown',
                    variety_name: isVarietalSuccession 
                        ? `Varietal Succession: ${uniqueVarieties.join(', ')}` 
                        : (plan.plantings[0]?.variety_name || 'Standard'),
                    is_varietal_succession: isVarietalSuccession,
                    varieties_used: uniqueVarieties,
                    total_successions: plan.plantings.length,
                    harvest_window_start: plan.harvest_start,
                    harvest_window_end: plan.harvest_end,
                    bed_length: plan.plantings[0]?.bed_length || 5,
                    bed_width: plan.plantings[0]?.bed_width || 75,
                    in_row_spacing: plan.plantings[0]?.in_row_spacing || 30,
                    between_row_spacing: plan.plantings[0]?.between_row_spacing || 45,
                    planting_method: plan.plantings[0]?.transplant_date ? 'Transplant' : 'Direct Sow',
                    plantings: plan.plantings.map(p => ({
                        succession_number: p.succession_number,
                        variety_name: p.variety_name,
                        seeding_date: p.seeding_date,
                        transplant_date: p.transplant_date || null,
                        harvest_date: p.harvest_date,
                        harvest_end_date: p.harvest_end_date || null,
                        total_plants: p.total_plants,
                        bed_name: p.bed_name
                    }))
                }
            };

            const prompt = `Analyze this succession planting plan and provide specific, actionable recommendations.`;

            console.log('🧠 Sending plan analysis request to AI with full context...');
            console.log('📦 Context:', context);
            console.log('🌱 Varietal succession:', isVarietalSuccession, '- Varieties:', uniqueVarieties);

            // Call the chat API with proper context
            const response = await fetch('/admin/farmos/succession-planning/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ 
                    question: prompt,
                    crop_type: plan.plantings[0]?.crop_name?.toLowerCase() || null,
                    context: context
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('✅ AI response received:', data);

            if (data.answer) {
                // Display response with Accept/Modify buttons
                displayAIResponse(data.answer);
            } else {
                throw new Error('No answer received from AI');
            }

        } catch (error) {
            console.error('❌ Error analyzing plan:', error);
            showToast('Failed to analyze plan: ' + error.message, 'error');
        } finally {
            // Restore button state
            analyzePlanBtn.disabled = false;
            analyzePlanBtn.innerHTML = originalText;
        }
    }

    function buildPlanContextForAI() {
        if (!currentSuccessionPlan) return 'No plan available';

        const plan = currentSuccessionPlan;
        let context = `Crop: ${plan.crop?.name || 'Unknown'}
Variety: ${plan.variety?.name || 'Standard'}
Harvest Window: ${plan.harvest_start} to ${plan.harvest_end}
Total Successions: ${plan.total_successions || 0}

Plantings:`;

        if (plan.plantings && plan.plantings.length > 0) {
            plan.plantings.forEach((p, i) => {
                context += `\n${i+1}. Succession ${p.succession_number || i+1}
   - Bed: ${p.bed_name || 'Unassigned'}
   - Seeding: ${p.seeding_date || 'Not set'}
   - Transplant: ${p.transplant_date || 'Not set'}
   - Harvest: ${p.harvest_date || 'Not set'}${p.harvest_end_date ? ' to ' + p.harvest_end_date : ''}`;
            });
        } else {
            context += '\nNo plantings generated yet';
        }

        return context;
    }

    function submitViaAPI(logType, plantingData) {
        console.log(`🚀 Submitting ${logType} via API for planting:`, plantingData);

        // Show loading state
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        button.disabled = true;

        // Prepare data for API submission
        const apiData = {
            crop_name: plantingData.crop_name,
            variety_name: plantingData.variety_name,
            bed_name: plantingData.bed_name,
            quantity: plantingData.quantity,
            succession_number: plantingData.succession_number
        };

        // Add date based on log type
        switch(logType) {
            case 'seeding':
                apiData.seeding_date = plantingData.seeding_date;
                break;
            case 'transplant':
                apiData.transplant_date = plantingData.transplant_date;
                break;
            case 'harvest':
                apiData.harvest_date = plantingData.harvest_date;
                break;
        }

        // Make API call
        fetch('/admin/farmos/succession-planning/submit-log', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                log_type: logType,
                data: apiData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                button.innerHTML = '<i class="fas fa-check"></i> Submitted!';
                button.className = 'btn btn-success btn-sm';
                console.log(`✅ ${logType} log created successfully:`, data);

                // Reset button after 3 seconds
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.className = 'btn btn-success btn-sm';
                    button.disabled = false;
                }, 3000);
            } else {
                // Show API submission failed, suggest manual entry
                button.innerHTML = '<i class="fas fa-exclamation-triangle"></i> API Failed';
                button.className = 'btn btn-warning btn-sm';
                console.warn(`⚠️ ${logType} API submission failed:`, data.message);

                // Reset button after 3 seconds
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.className = 'btn btn-warning btn-sm';
                    button.disabled = false;
                }, 3000);
            }
        })
        .catch(error => {
            console.error(`❌ ${logType} API submission failed:`, error);
            button.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Failed';
            button.className = 'btn btn-danger btn-sm';

            // Reset button after 3 seconds
            setTimeout(() => {
                button.innerHTML = originalText;
                button.className = 'btn btn-success btn-sm';
                button.disabled = false;
            }, 3000);
        });
    }

    // ===== BATCH SUCCESSION PLANNING =====

    // Process multiple varieties at once
    async function batchProcessSuccessions(varietyIds) {
        console.log(`🔄 Batch processing ${varietyIds.length} varieties...`);

        const results = [];
        for (const varietyId of varietyIds) {
            try {
                // Auto-select variety
                const varietySelect = document.getElementById('varietySelect');
                varietySelect.value = varietyId;
                varietySelect.dispatchEvent(new Event('change'));

                // Wait for UI to update
                await new Promise(resolve => setTimeout(resolve, 500));

                // Calculate succession plan
                await calculateSuccessionPlan();

                // Wait for calculation
                await new Promise(resolve => setTimeout(resolve, 1000));

                // Capture results
                const plan = getCurrentSuccessionPlan();
                results.push({
                    varietyId,
                    varietyName: plan?.variety?.name || 'Unknown',
                    successions: plan?.plantings?.length || 0,
                    status: 'completed'
                });

            } catch (error) {
                console.error(`❌ Failed to process variety ${varietyId}:`, error);
                results.push({
                    varietyId,
                    status: 'failed',
                    error: error.message
                });
            }
        }

        console.log(`✅ Batch processing complete:`, results);
        return results;
    }

    // Quick setup for similar varieties
    function quickSetupForVarietyFamily(cropType, varietyIds) {
        console.log(`🚀 Quick setup for ${cropType} family: ${varietyIds.length} varieties`);

        // Use first variety to establish baseline settings
        const baselineVarietyId = varietyIds[0];

        // Process all varieties with same settings
        return batchProcessSuccessions(varietyIds);
    }

    // ===== EFFICIENCY FEATURES =====

    // Auto-detect and process all varieties of same crop type
    async function processAllVarietiesOfCrop(cropType) {
        console.log(`🔍 Finding all ${cropType} varieties...`);

        const varietySelect = document.getElementById('varietySelect');
        const cropSelect = document.getElementById('cropSelect');

        // Find crop option
        let cropValue = '';
        for (let i = 0; i < cropSelect.options.length; i++) {
            if (cropSelect.options[i].text.toLowerCase().includes(cropType.toLowerCase())) {
                cropValue = cropSelect.options[i].value;
                break;
            }
        }

        if (!cropValue) {
            console.error(`❌ Crop type "${cropType}" not found`);
            return [];
        }

        // Select crop
        cropSelect.value = cropValue;
        cropSelect.dispatchEvent(new Event('change'));

        // Wait for varieties to load
        await new Promise(resolve => setTimeout(resolve, 1000));

        // Get all variety IDs
        const varietyIds = [];
        for (let i = 0; i < varietySelect.options.length; i++) {
            const option = varietySelect.options[i];
            if (option.value && option.value !== '') {
                varietyIds.push(option.value);
            }
        }

        console.log(`📋 Found ${varietyIds.length} ${cropType} varieties`);
        return await batchProcessSuccessions(varietyIds);
    }

    // Create succession templates for crop families
    const successionTemplates = {
        'brassicas': {
            successions: 3,
            spacing: 30, // days between transplant dates
            notes: 'Cool season crop, good for succession planting'
        },
        'leafy_greens': {
            successions: 4,
            spacing: 14, // days between transplant dates
            notes: 'Quick growing, high succession frequency'
        },
        'root_vegetables': {
            successions: 2,
            spacing: 21, // days between transplant dates
            notes: 'Long growing season, fewer successions needed'
        }
    };

    // Apply template settings
    function applySuccessionTemplate(templateName) {
        const template = successionTemplates[templateName];
        if (!template) {
            console.error(`❌ Template "${templateName}" not found`);
            return false;
        }

        console.log(`📋 Applying ${templateName} template:`, template);
        // Template would adjust succession count and spacing
        return true;
    }

    // ===== USAGE EXAMPLES =====
    /*
    🚀 QUICK START COMMANDS (run in browser console):

    // Process all Brussels sprouts varieties at once
    processAllVarietiesOfCrop('Brussels Sprouts').then(results => {
        console.log('Batch results:', results);
    });

    // Process specific varieties
    batchProcessSuccessions(['variety-id-1', 'variety-id-2', 'variety-id-3']);

    // Apply template for brassicas
    applySuccessionTemplate('brassicas');

    // Quick setup for similar varieties
    quickSetupForVarietyFamily('Brussels Sprouts', ['id1', 'id2', 'id3']);
    */

    console.log('🎯 Efficiency features loaded! Use processAllVarietiesOfCrop() or batchProcessSuccessions()');

    // Global variables for harvest window management
    let harvestWindowData = {
        maxStart: null,
        maxEnd: null,
        aiStart: null,
        aiEnd: null,
        userStart: null,
        userEnd: null,
        selectedYear: new Date().getFullYear()
    };

    // Initialize the new harvest window selector
    function initializeHarvestWindowSelector() {
        console.log('🎯 Initializing new harvest window selector');

        // Set up range handle event listeners
        setupRangeHandles();

        // Initialize with default dates
        updateHarvestWindowDisplay();
    }

    // Set up drag handles for range adjustment
    function setupRangeHandles() {
        const startHandle = document.getElementById('rangeStartHandle');
        const endHandle = document.getElementById('rangeEndHandle');

        if (!startHandle || !endHandle) return;

        let isDragging = false;
        let dragHandle = null;
        let startX = 0;
        let initialLeft = 0;

        function handleMouseDown(e, handle) {
            isDragging = true;
            dragHandle = handle;
            startX = e.clientX;
            const progressBar = document.querySelector('#userSelectedRange .progress');
            const rect = progressBar.getBoundingClientRect();
            initialLeft = rect.left;
            document.body.style.cursor = 'ew-resize';
            e.preventDefault();
        }

        function handleMouseMove(e) {
            if (!isDragging || !dragHandle) return;

            const progressBar = document.querySelector('#userSelectedRange .progress');
            const rect = progressBar.getBoundingClientRect();
            const deltaX = e.clientX - startX;
            const percentage = Math.max(0, Math.min(100, (deltaX / rect.width) * 100));

            if (dragHandle === 'start') {
                adjustUserRange('start', percentage);
            } else if (dragHandle === 'end') {
                adjustUserRange('end', percentage);
            }
        }

        function handleMouseUp() {
            isDragging = false;
            dragHandle = null;
            document.body.style.cursor = 'default';
            updateDateInputsFromRange();
        }

        startHandle.addEventListener('mousedown', (e) => handleMouseDown(e, 'start'));
        endHandle.addEventListener('mousedown', (e) => handleMouseDown(e, 'end'));
        document.addEventListener('mousemove', handleMouseMove);
        document.addEventListener('mouseup', handleMouseUp);
    }

    // Update the harvest window display with new data
    function updateHarvestWindowDisplay() {
        const year = document.getElementById('planningYear').value || new Date().getFullYear();
        harvestWindowData.selectedYear = year;

        // Show maximum possible range
        if (harvestWindowData.maxStart && harvestWindowData.maxEnd) {
            displayMaxRange();
        }

        // Show AI recommended range
        if (harvestWindowData.aiStart && harvestWindowData.aiEnd) {
            displayAIRange();
        }

        // Show user selected range
        if (harvestWindowData.userStart && harvestWindowData.userEnd) {
            displayUserRange();
        }

        // Update calendar grid
        updateCalendarGrid();

        // Update succession impact
        updateSuccessionImpact();
    }

    // Display maximum possible harvest range
    function displayMaxRange() {
        const maxRangeDiv = document.getElementById('maxHarvestRange');
        const maxRangeBar = document.getElementById('maxRangeBar');
        const maxRangeDates = document.getElementById('maxRangeDates');

        if (!maxRangeDiv || !maxRangeBar || !maxRangeDates) return;

        const startDate = new Date(harvestWindowData.maxStart);
        const endDate = new Date(harvestWindowData.maxEnd);
        const duration = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));

        maxRangeDates.textContent = `${startDate.toLocaleDateString()} - ${endDate.toLocaleDateString()} (${duration} days)`;
        maxRangeDiv.style.display = 'block';
    }

    // Display AI recommended harvest range
    function displayAIRange() {
        const aiRangeDiv = document.getElementById('aiRecommendedRange');
        const aiRangeBar = document.getElementById('aiRangeBar');
        const aiRangeDates = document.getElementById('aiRangeDates');

        if (!aiRangeDiv || !aiRangeBar || !aiRangeDates) {
            console.warn('❌ displayAIRange: Missing DOM elements');
            return;
        }

        console.log('🤖 displayAIRange called with data:', {
            aiStart: harvestWindowData.aiStart,
            aiEnd: harvestWindowData.aiEnd,
            maxStart: harvestWindowData.maxStart,
            maxEnd: harvestWindowData.maxEnd
        });

        const startDate = new Date(harvestWindowData.aiStart);
        const endDate = new Date(harvestWindowData.aiEnd);
        const duration = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));

        // Calculate position within max range
        const maxStart = new Date(harvestWindowData.maxStart);
        const maxEnd = new Date(harvestWindowData.maxEnd);
        const maxDuration = maxEnd - maxStart;
        const aiStartOffset = startDate - maxStart;
        const aiDuration = endDate - startDate;

        const leftPercent = (aiStartOffset / maxDuration) * 100;
        const widthPercent = (aiDuration / maxDuration) * 100;

        console.log('📊 AI range calculations:', {
            startDate: startDate.toISOString(),
            endDate: endDate.toISOString(),
            aiStartOffset,
            aiDuration,
            maxDuration,
            leftPercent,
            widthPercent
        });

        aiRangeBar.style.marginLeft = `${leftPercent}%`;
        aiRangeBar.style.width = `${widthPercent}%`;
        aiRangeDates.textContent = `${startDate.toLocaleDateString()} - ${endDate.toLocaleDateString()} (${duration} days)`;
        aiRangeDiv.style.display = 'block';

        console.log('✅ AI range displayed');
    }

    // Display user selected harvest range
    function displayUserRange() {
        const userRangeDiv = document.getElementById('userSelectedRange');
        const userRangeBar = document.getElementById('userRangeBar');
        const userRangeDates = document.getElementById('userRangeDates');
        const startHandle = document.getElementById('rangeStartHandle');
        const endHandle = document.getElementById('rangeEndHandle');

        if (!userRangeDiv || !userRangeBar || !userRangeDates) {
            console.warn('❌ displayUserRange: Missing DOM elements');
            return;
        }

        console.log('🎨 displayUserRange called with data:', {
            userStart: harvestWindowData.userStart,
            userEnd: harvestWindowData.userEnd,
            maxStart: harvestWindowData.maxStart,
            maxEnd: harvestWindowData.maxEnd
        });

        const startDate = new Date(harvestWindowData.userStart);
        const endDate = new Date(harvestWindowData.userEnd);
        const duration = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));

        // Calculate position within max range
        const maxStart = new Date(harvestWindowData.maxStart);
        const maxEnd = new Date(harvestWindowData.maxEnd);
        const maxDuration = maxEnd - maxStart;
        const userStartOffset = startDate - maxStart;
        const userDuration = endDate - startDate;

        const leftPercent = (userStartOffset / maxDuration) * 100;
        const widthPercent = (userDuration / maxDuration) * 100;

        console.log('📊 User range calculations:', {
            startDate: startDate.toISOString(),
            endDate: endDate.toISOString(),
            maxStart: maxStart.toISOString(),
            maxEnd: maxEnd.toISOString(),
            userStartOffset,
            userDuration,
            maxDuration,
            leftPercent,
            widthPercent
        });

        userRangeBar.style.marginLeft = `${leftPercent}%`;
        userRangeBar.style.width = `${widthPercent}%`;

        if (startHandle) startHandle.style.left = `${leftPercent}%`;
        if (endHandle) endHandle.style.left = `${leftPercent + widthPercent}%`;

        userRangeDates.textContent = `${startDate.toLocaleDateString()} - ${endDate.toLocaleDateString()} (${duration} days)`;
        userRangeDiv.style.display = 'block';

        console.log('✅ User range displayed');
    }

    // Adjust user selected range
    function adjustUserRange(handle, percentage) {
        const maxStart = new Date(harvestWindowData.maxStart);
        const maxEnd = new Date(harvestWindowData.maxEnd);
        const maxDuration = maxEnd - maxStart;

        const newDate = new Date(maxStart.getTime() + (percentage / 100) * maxDuration);

        if (handle === 'start') {
            harvestWindowData.userStart = newDate.toISOString().split('T')[0];
            // Ensure start doesn't go past end
            if (new Date(harvestWindowData.userStart) >= new Date(harvestWindowData.userEnd)) {
                harvestWindowData.userStart = harvestWindowData.userEnd;
            }
        } else if (handle === 'end') {
            harvestWindowData.userEnd = newDate.toISOString().split('T')[0];
            // Ensure end doesn't go before start
            if (new Date(harvestWindowData.userEnd) <= new Date(harvestWindowData.userStart)) {
                harvestWindowData.userEnd = harvestWindowData.userStart;
            }
        }

        displayUserRange();
        updateSuccessionImpact();
    }

    // Update date inputs from range selection
    function updateDateInputsFromRange() {
        const startInput = document.getElementById('harvestStart');
        const endInput = document.getElementById('harvestEnd');

        if (startInput && harvestWindowData.userStart) {
            startInput.value = harvestWindowData.userStart;
        }
        if (endInput && harvestWindowData.userEnd) {
            endInput.value = harvestWindowData.userEnd;
        }
    }

    // Extend harvest window by maximum 20%
    function extendHarvestWindow() {
        if (!harvestWindowData.aiStart || !harvestWindowData.aiEnd) {
            console.warn('No AI range to extend from');
            return;
        }

        const aiStart = new Date(harvestWindowData.aiStart);
        const aiEnd = new Date(harvestWindowData.aiEnd);
        const aiDuration = aiEnd - aiStart;
        const maxExtension = aiDuration * 0.2; // 20% maximum

        const newEnd = new Date(aiEnd.getTime() + maxExtension);
        const maxPossibleEnd = new Date(harvestWindowData.maxEnd);

        // Don't extend beyond maximum possible
        const finalEnd = newEnd > maxPossibleEnd ? maxPossibleEnd : newEnd;

        harvestWindowData.userStart = harvestWindowData.aiStart;
        harvestWindowData.userEnd = finalEnd.toISOString().split('T')[0];

        updateHarvestWindowDisplay();
        updateDateInputsFromRange();

        console.log('📈 Extended harvest window by up to 20%');
    }

    // Optimize harvest window (set to AI recommended)
    function optimizeHarvestWindow() {
        if (!harvestWindowData.aiStart || !harvestWindowData.aiEnd) {
            console.warn('No AI range to optimize to');
            return;
        }

        harvestWindowData.userStart = harvestWindowData.aiStart;
        harvestWindowData.userEnd = harvestWindowData.aiEnd;

        updateHarvestWindowDisplay();
        updateDateInputsFromRange();

        console.log('🎯 Optimized harvest window to AI recommendation');
    }

    // Shorten harvest window (reduce successions)
    function shortenHarvestWindow() {
        if (!harvestWindowData.userStart || !harvestWindowData.userEnd) {
            console.warn('No user range to shorten');
            return;
        }

        const start = new Date(harvestWindowData.userStart);
        const end = new Date(harvestWindowData.userEnd);
        const currentDuration = end - start;

        // Reduce by 25% to decrease number of successions
        const newDuration = currentDuration * 0.75;
        const newEnd = new Date(start.getTime() + newDuration);

        harvestWindowData.userEnd = newEnd.toISOString().split('T')[0];

        updateHarvestWindowDisplay();
        updateDateInputsFromRange();

        console.log('📉 Shortened harvest window to reduce successions');
    }

    // Adjust succession count by modifying harvest window duration
    function adjustSuccessionCount(change) {
        const countBadge = document.getElementById('successionCount');
        const intervalDisplay = document.getElementById('successionIntervalDisplay');
        
        if (!countBadge || !harvestWindowData.userStart || !harvestWindowData.userEnd) {
            console.warn('❌ Cannot adjust succession count: missing data');
            return;
        }
        
        // Get current succession count and interval
        const currentCount = parseInt(countBadge.textContent) || 1;
        const targetCount = Math.max(1, currentCount + change); // Minimum 1 succession
        
        // Get crop-specific succession interval
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        const cropName = cropSelect?.options[cropSelect.selectedIndex]?.text?.toLowerCase() || '';
        const varietyName = varietySelect?.options[varietySelect.selectedIndex]?.text?.toLowerCase() || '';
        const avgInterval = getSuccessionInterval(cropName, varietyName);
        
        // Calculate new harvest window duration to achieve target succession count
        const targetDuration = targetCount * avgInterval;
        
        // Adjust end date to achieve target duration
        const startDate = new Date(harvestWindowData.userStart);
        const newEndDate = new Date(startDate.getTime() + (targetDuration * 24 * 60 * 60 * 1000));
        
        // Don't exceed maximum possible window
        const maxEndDate = new Date(harvestWindowData.maxEnd);
        if (newEndDate > maxEndDate) {
            console.warn('⚠️ Cannot add more successions - would exceed maximum harvest window');
            showHarvestWindowNotification(
                'Cannot add more successions - already at maximum harvest window',
                'warning'
            );
            return;
        }
        
        // Don't go below minimum (7 days)
        if (targetDuration < 7) {
            console.warn('⚠️ Cannot reduce further - minimum 1 succession with 7 day window');
            return;
        }
        
        // Update harvest window
        harvestWindowData.userEnd = newEndDate.toISOString().split('T')[0];
        
        // Update UI
        updateDragBar();
        updateHarvestWindowDisplay();
        
        // Show interval info
        if (intervalDisplay) {
            const weeks = Math.floor(avgInterval / 7);
            const days = avgInterval % 7;
            let intervalText = '';
            if (weeks > 0) {
                intervalText = `${weeks} week${weeks > 1 ? 's' : ''}`;
                if (days > 0) intervalText += ` ${days} day${days > 1 ? 's' : ''}`;
            } else {
                intervalText = `${days} day${days > 1 ? 's' : ''}`;
            }
            intervalDisplay.textContent = `Planting interval: ~${intervalText}`;
        }
        
        console.log(`📊 Adjusted harvest window for ${targetCount} successions (${avgInterval} day interval)`);
        
        // Show notification
        showHarvestWindowNotification(
            `${change > 0 ? 'Extended' : 'Shortened'} harvest window to ${targetCount} succession${targetCount > 1 ? 's' : ''}`,
            'info'
        );
    }

    function updateSuccessionFromInterval() {
        const dropdown = document.getElementById('successionIntervalSelect');
        const customInput = document.getElementById('customIntervalInput');
        const countBadge = document.getElementById('successionCount');
        const intervalDisplay = document.getElementById('successionIntervalDisplay');
        
        if (!dropdown || !harvestWindowData.userStart || !harvestWindowData.userEnd) {
            console.warn('❌ Cannot update succession interval: missing data');
            return;
        }
        
        const selectedValue = dropdown.value;
        
        // Show/hide custom input
        if (selectedValue === 'custom') {
            if (customInput) customInput.style.display = 'block';
            return; // Wait for custom input
        } else {
            if (customInput) customInput.style.display = 'none';
        }
        
        const interval = parseInt(selectedValue);
        
        // Calculate harvest window duration in days
        const startDate = new Date(harvestWindowData.userStart);
        const endDate = new Date(harvestWindowData.userEnd);
        const durationMs = endDate - startDate;
        const durationDays = Math.ceil(durationMs / (1000 * 60 * 60 * 24));
        
        // Calculate new succession count based on interval
        const newCount = Math.max(1, Math.ceil(durationDays / interval));
        
        // Update succession count badge
        if (countBadge) {
            countBadge.textContent = newCount;
        }
        
        // Update interval display
        if (intervalDisplay) {
            const weeks = Math.floor(interval / 7);
            const days = interval % 7;
            let intervalText = '';
            if (weeks > 0) {
                intervalText = `${weeks} week${weeks > 1 ? 's' : ''}`;
                if (days > 0) intervalText += ` ${days} day${days > 1 ? 's' : ''}`;
            } else {
                intervalText = `${days} day${days > 1 ? 's' : ''}`;
            }
            intervalDisplay.textContent = `Planting interval: ~${intervalText}`;
        }
        
        console.log(`🔄 Updated succession interval to ${interval} days (${newCount} successions)`);
        
        // Trigger plan recalculation
        updateSuccessionImpact();
    }

    function updateSuccessionFromCustomInterval() {
        const customInput = document.getElementById('customIntervalDays');
        const countBadge = document.getElementById('successionCount');
        const intervalDisplay = document.getElementById('successionIntervalDisplay');
        
        if (!customInput || !harvestWindowData.userStart || !harvestWindowData.userEnd) {
            console.warn('❌ Cannot update custom interval: missing data');
            return;
        }
        
        const interval = parseInt(customInput.value);
        
        // Validate range
        if (isNaN(interval) || interval < 1 || interval > 365) {
            showHarvestWindowNotification('Please enter a valid interval between 1 and 365 days', 'warning');
            return;
        }
        
        // Calculate harvest window duration in days
        const startDate = new Date(harvestWindowData.userStart);
        const endDate = new Date(harvestWindowData.userEnd);
        const durationMs = endDate - startDate;
        const durationDays = Math.ceil(durationMs / (1000 * 60 * 60 * 24));
        
        // Calculate new succession count based on interval
        const newCount = Math.max(1, Math.ceil(durationDays / interval));
        
        // Update succession count badge
        if (countBadge) {
            countBadge.textContent = newCount;
        }
        
        // Update interval display
        if (intervalDisplay) {
            const weeks = Math.floor(interval / 7);
            const days = interval % 7;
            let intervalText = '';
            if (weeks > 0) {
                intervalText = `${weeks} week${weeks > 1 ? 's' : ''}`;
                if (days > 0) intervalText += ` ${days} day${days > 1 ? 's' : ''}`;
            } else {
                intervalText = `${days} day${days > 1 ? 's' : ''}`;
            }
            intervalDisplay.textContent = `Planting interval: ~${intervalText}`;
        }
        
        console.log(`🔄 Updated custom succession interval to ${interval} days (${newCount} successions)`);
        
        // Trigger plan recalculation
        updateSuccessionImpact();
    }

    function populateSuccessionSidebar(plan) {
        const successionList = document.getElementById('successionList');
        const successionSidebar = document.getElementById('successionSidebar');
        const aiChatSection = document.getElementById('aiChatSection');

        if (!successionList || !successionSidebar || !aiChatSection) return;

        const plantings = plan.plantings || [];
        
        if (plantings.length > 0) {
            // Get existing allocations BEFORE generating HTML
            const allocations = JSON.parse(localStorage.getItem('bedAllocations') || '[]');
            console.log('📦 Found existing allocations:', allocations);
            console.log('📦 Number of allocations:', allocations.length);
            
            // Debug: log each allocation
            allocations.forEach((alloc, idx) => {
                console.log(`  Allocation ${idx}:`, {
                    successionIndex: alloc.successionIndex,
                    bedName: alloc.bedName,
                    bedId: alloc.bedId
                });
            });
            
            let sidebarHTML = '';

            plantings.forEach((planting, i) => {
                const sowDate = planting.seeding_date ? new Date(planting.seeding_date) : null;
                const transplantDate = planting.transplant_date ? new Date(planting.transplant_date) : null;
                const harvestDate = planting.harvest_date ? new Date(planting.harvest_date) : null;

                const successionDataForJson = {
                    successionNumber: i + 1,
                    sowDate: sowDate ? sowDate.toISOString() : null,
                    transplantDate: transplantDate ? transplantDate.toISOString() : null,
                    harvestDate: harvestDate ? harvestDate.toISOString() : null,
                    method: planting.planting_method || planting.method || 'Direct Sow'
                };

                // Check if this succession is allocated
                const allocation = allocations.find(a => a.successionIndex === i + 1);
                const isAllocated = !!allocation;
                
                console.log(`Succession ${i + 1}: allocated=${isAllocated}`, allocation);

                sidebarHTML += `
                    <div class="succession-item ${isAllocated ? 'allocated' : ''}" draggable="true" data-succession-index="${i}" data-succession-data='${JSON.stringify(successionDataForJson)}' ${isAllocated ? `data-allocation-data='${JSON.stringify(allocation)}'` : ''}>
                        <div class="succession-header">
                            <div class="succession-title-section">
                                <span class="succession-title">Succession ${i + 1}${planting.variety_name ? `: ${planting.variety_name}` : ''}</span>
                                <small class="text-muted">${planting.planting_method || planting.method || 'Direct Sow'}</small>
                            </div>
                            ${isAllocated ? `
                            <span class="bed-allocation-badge badge bg-success" onclick="removeSuccessionAllocation(${i})" style="cursor: pointer;" title="Click to remove allocation">
                                <i class="fas fa-map-marker-alt"></i> ${allocation.bedName}
                            </span>
                            ` : ''}
                        </div>
                        <div class="succession-dates">
                            ${sowDate ? `
                            <div class="date-row">
                                <span class="date-label">Sow:</span>
                                <span class="date-value">${sowDate.toLocaleDateString()}</span>
                            </div>
                            ` : ''}
                            ${transplantDate ? `
                            <div class="date-row">
                                <span class="date-label">Transplant:</span>
                                <span class="date-value">${transplantDate.toLocaleDateString()}</span>
                            </div>
                            ` : ''}
                            ${harvestDate ? `
                            <div class="date-row">
                                <span class="date-label">Harvest:</span>
                                <span class="date-value">${harvestDate.toLocaleDateString()}</span>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            });

            successionList.innerHTML = sidebarHTML;
            successionSidebar.style.display = 'block';
            // Keep AI chat visible - it's now above succession sidebar
            
            // Update succession count badge
            const countBadge = document.getElementById('sidebarSuccessionCount');
            if (countBadge) {
                countBadge.textContent = `${plantings.length} Succession${plantings.length !== 1 ? 's' : ''}`;
            }

            console.log('📝 Succession sidebar populated with', plantings.length, 'successions');

            // Drag and drop will be initialized centrally after both timeline and sidebar are ready
        } else {
            successionSidebar.style.display = 'none';
            // AI chat stays visible
        }
    }

    // Display crop-specific guidance tips and warnings
    function displayCropGuidance(cropName, varietyName, successionCount, durationDays) {
        const guidanceDisplay = document.getElementById('cropGuidanceDisplay');
        if (!guidanceDisplay) return;
        
        let guidanceHTML = '';
        const crop = cropName.toLowerCase();
        
        // Crop-specific recommendations
        const cropGuidance = {
            'cucumber': {
                optimal: 2,
                max: 3,
                tips: {
                    low: '✅ Perfect! Cucumbers produce continuously for 6-8 weeks. 2-3 successions is ideal.',
                    optimal: '✅ This harvest window is optimal for outdoor cucumbers like Marketmore.',
                    high: '⚠️ Cucumbers typically only need 2-3 successions per season. Consider reducing.',
                    tooMany: '❌ Too many successions! Cucumbers have long harvest windows. Use 2-3 successions max.'
                }
            },
            'tomato': {
                optimal: 2,
                max: 3,
                tips: {
                    low: '✅ Good! Tomatoes produce for extended periods. 2-3 successions works well.',
                    optimal: '✅ Optimal succession count for continuous tomato harvest.',
                    high: '⚠️ Tomatoes have long harvest periods. Consider fewer successions.',
                    tooMany: '❌ Too many! Tomatoes produce continuously - 2-3 successions is sufficient.'
                }
            },
            'lettuce': {
                optimal: 7,
                max: 12,
                tips: {
                    low: 'ℹ️ Lettuce benefits from frequent successions (every 2 weeks) for continuous harvest.',
                    optimal: '✅ Great! Fortnightly lettuce successions ensure continuous fresh leaves.',
                    high: '⚠️ Many successions, but lettuce is fast-growing. Monitor for bolting in summer.',
                    tooMany: '⚠️ Very frequent plantings. Ensure you can manage this workload.'
                }
            },
            'carrot': {
                optimal: 6,
                max: 10,
                tips: {
                    low: 'ℹ️ Carrots store well in ground. Consider more successions for continuous harvest.',
                    optimal: '✅ Good succession planning for continuous carrot harvest.',
                    high: '⚠️ Many carrot successions. Ensure adequate storage or market demand.',
                    tooMany: '⚠️ Very frequent plantings. Carrots can be stored - fewer successions may work.'
                }
            },
            'radish': {
                optimal: 10,
                max: 15,
                tips: {
                    low: 'ℹ️ Radishes are quick! Weekly successions possible for continuous harvest.',
                    optimal: '✅ Perfect for fast-turnover radish production.',
                    high: '⚠️ Very frequent! Ensure you have market demand for this volume.',
                    tooMany: '⚠️ Extremely frequent plantings. Consider if this workload is manageable.'
                }
            },
            'zucchini': {
                optimal: 3,
                max: 4,
                tips: {
                    low: '✅ Good! Zucchini plants produce heavily for several weeks.',
                    optimal: '✅ Optimal for continuous zucchini harvest throughout season.',
                    high: '⚠️ Zucchini plants are very productive. Fewer successions may suffice.',
                    tooMany: '❌ Too many! Each zucchini plant produces prolifically. Reduce successions.'
                }
            },
            'courgette': {
                optimal: 3,
                max: 4,
                tips: {
                    low: '✅ Good! Courgette plants produce heavily for several weeks.',
                    optimal: '✅ Optimal for continuous courgette harvest throughout season.',
                    high: '⚠️ Courgette plants are very productive. Fewer successions may suffice.',
                    tooMany: '❌ Too many! Each courgette plant produces 10-20+ fruits. Reduce successions.'
                }
            },
            'aubergine': {
                optimal: 1,
                max: 2,
                tips: {
                    low: '✅ Perfect! Most growers need just 1 aubergine planting per season.',
                    optimal: '✅ 2 successions can extend harvest if you have a long season.',
                    high: '⚠️ Aubergines produce continuously for months. More successions rarely needed.',
                    tooMany: '❌ Too many! Each aubergine plant produces 10-20+ fruits over many weeks.'
                }
            },
            'eggplant': {
                optimal: 1,
                max: 2,
                tips: {
                    low: '✅ Perfect! Most growers need just 1 eggplant planting per season.',
                    optimal: '✅ 2 successions can extend harvest if you have a long season.',
                    high: '⚠️ Eggplants produce continuously for months. More successions rarely needed.',
                    tooMany: '❌ Too many! Each eggplant produces 10-20+ fruits over many weeks.'
                }
            }
        };
        
        // Get guidance for this crop
        const guidance = cropGuidance[crop];
        
        if (guidance) {
            let tipType, tipMessage;
            
            if (successionCount <= guidance.optimal) {
                tipType = 'low';
                tipMessage = guidance.tips.low;
            } else if (successionCount <= guidance.optimal + 1) {
                tipType = 'optimal';
                tipMessage = guidance.tips.optimal;
            } else if (successionCount <= guidance.max) {
                tipType = 'high';
                tipMessage = guidance.tips.high;
            } else {
                tipType = 'tooMany';
                tipMessage = guidance.tips.tooMany;
            }
            
            // Determine alert class
            let alertClass = 'alert-info';
            if (tipMessage.startsWith('✅')) alertClass = 'alert-success';
            else if (tipMessage.startsWith('⚠️')) alertClass = 'alert-warning';
            else if (tipMessage.startsWith('❌')) alertClass = 'alert-danger';
            
            guidanceHTML = `
                <div class="alert ${alertClass} py-2 px-3 mb-0 mt-2" style="font-size: 0.85rem;">
                    ${tipMessage}
                </div>
            `;
        } else {
            // Generic guidance for crops without specific rules
            if (successionCount > 8) {
                guidanceHTML = `
                    <div class="alert alert-warning py-2 px-3 mb-0 mt-2" style="font-size: 0.85rem;">
                        ℹ️ ${successionCount} successions is quite frequent. Ensure this matches your growing capacity.
                    </div>
                `;
            }
        }
        
        guidanceDisplay.innerHTML = guidanceHTML;
    }

    // Update succession impact preview
    function updateSuccessionImpact() {
        console.log('🔄 updateSuccessionImpact called');
        
        const impactDiv = document.getElementById('successionImpact');
        const countBadge = document.getElementById('successionCount');
        const previewDiv = document.getElementById('successionPreview');

        // Only require countBadge, others are optional for enhanced display
        if (!countBadge) {
            console.warn('⚠️ Missing succession count badge element');
            return;
        }
        
        if (!harvestWindowData.userStart || !harvestWindowData.userEnd) {
            console.warn('⚠️ Missing harvest window data:', harvestWindowData);
            return;
        }

        const start = new Date(harvestWindowData.userStart);
        const end = new Date(harvestWindowData.userEnd);
        const duration = Math.ceil((end - start) / (1000 * 60 * 60 * 24));

        console.log('📊 Calculating succession impact - duration:', duration, 'days');

        // Get crop information for better calculations
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        const cropName = cropSelect?.options[cropSelect.selectedIndex]?.text?.toLowerCase() || '';
        const varietyName = varietySelect?.options[varietySelect.selectedIndex]?.text?.toLowerCase() || '';

        // Estimate number of successions based on duration
        // Typical succession interval varies by crop
        const avgSuccessionInterval = getSuccessionInterval(cropName, varietyName);
        let successions = Math.max(1, Math.ceil(duration / avgSuccessionInterval));

        // For crops with transplant windows, consider transplant window constraints
        // NOTE: Only apply this for brussels sprouts which truly have a narrow transplant window
        if (cropName.toLowerCase().includes('brussels')) {
            // Get crop timing to check transplant window
            const cropTiming = getCropTiming(cropName, varietyName);
            if (cropTiming.transplantWindow) {
                const transplantWindowDays = 61; // March 15 - May 15 is approximately 61 days
                const transplantInterval = cropTiming.daysToTransplant || 35;

                // Calculate maximum successions that fit in transplant window
                // Use half the transplant interval as spacing to allow overlap of growing periods
                const minDaysPerSuccession = Math.floor(transplantInterval / 2); // e.g., 35 days / 2 = 17.5 day spacing
                const maxByTransplantWindow = Math.max(1, Math.floor(transplantWindowDays / minDaysPerSuccession));

                console.log(`🌱 Transplant window analysis: ${transplantWindowDays} days, ${transplantInterval} day interval, ${minDaysPerSuccession} day spacing`);
                console.log(`📊 Maximum realistic successions: ${maxByTransplantWindow}`);

                // Reduce successions if transplant window can't support them
                successions = Math.min(successions, maxByTransplantWindow);
                console.log(`🌱 Adjusted successions from harvest-based to ${successions} (transplant window constraint)`);
            }
        }

        countBadge.textContent = `${successions} Succession${successions > 1 ? 's' : ''}`;

        // Update sidebar count
        const sidebarCountBadge = document.getElementById('sidebarSuccessionCount');
        if (sidebarCountBadge) {
            sidebarCountBadge.textContent = `${successions} Succession${successions > 1 ? 's' : ''}`;
        }

        // Show and update the dynamic succession display
        const dynamicDisplay = document.getElementById('dynamicSuccessionDisplay');
        if (dynamicDisplay) {
            dynamicDisplay.style.display = 'block';
        }

        // Add crop-specific guidance warnings/tips
        displayCropGuidance(cropName, varietyName, successions, duration);

        console.log(`📊 Updated succession count: ${successions} based on ${duration} day harvest window`);

        // Calculate seed/transplant amounts based on bed dimensions
        const bedLength = parseFloat(document.getElementById('bedLength')?.value) || 0;
        const bedWidthMeters = parseFloat(document.getElementById('bedWidth')?.value) || 0;
        const bedWidthCm = bedWidthMeters * 100; // Convert meters to cm
        const bedWidth = bedWidthMeters; // Keep as meters
        const bedArea = bedLength * bedWidth; // in square meters

        let seedInfoHTML = '';
        if (bedArea > 0) {
            // Get seed/transplant requirements for this crop
            const seedRequirements = getSeedRequirements(cropName, varietyName);

            if (seedRequirements) {
                const totalSeeds = Math.ceil(bedArea * seedRequirements.seedsPerSqFt * successions);
                const totalTransplants = seedRequirements.transplantsPerSqFt ?
                    Math.ceil(bedArea * seedRequirements.transplantsPerSqFt * successions) : 0;

                seedInfoHTML = `
                    <div class="seed-calculations mt-3 p-3 bg-light rounded">
                        <h6 class="text-info mb-2">
                            <i class="fas fa-seedling"></i>
                            Seed & Transplant Requirements (${bedLength}m × ${bedWidth}m = ${bedArea} sq m)
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="seed-info">
                                    <strong>Seeds needed:</strong> ${totalSeeds.toLocaleString()} ${seedRequirements.seedUnit || 'seeds'}
                                    <br><small class="text-muted">(${seedRequirements.seedsPerSqFt} per sq m × ${successions} successions)</small>
                                </div>
                            </div>
                            ${totalTransplants > 0 ? `
                            <div class="col-md-6">
                                <div class="seed-info">
                                    <strong>Transplants needed:</strong> ${totalTransplants.toLocaleString()}
                                    <br><small class="text-muted">(${seedRequirements.transplantsPerSqFt} per sq m × ${successions} successions)</small>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            }
        }

        // Generate detailed succession preview for both locations
        let previewHTML = seedInfoHTML;
        let sidebarHTML = '';

        for (let i = 0; i < successions; i++) {
            const successionData = calculateSuccessionDates(start, i, avgSuccessionInterval, cropName, varietyName);

            // Original preview format
            previewHTML += `
                <div class="succession-item">
                    <div class="succession-header">
                        <span class="succession-label">Succession ${i + 1}</span>
                        <small class="text-muted method-badge">${successionData.method}</small>
                    </div>
                    <div class="succession-timeline">
                        <div class="timeline-step">
                            <small class="text-muted">Sow</small>
                            <div class="succession-date">${successionData.sowDate.toLocaleDateString()}</div>
                        </div>
                        ${successionData.transplantDate ? `
                        <div class="timeline-step transplant-step">
                            <small class="text-warning">Transplant</small>
                            <div class="succession-date">${successionData.transplantDate.toLocaleDateString()}</div>
                        </div>
                        ` : ''}
                        <div class="timeline-step harvest-step">
                            <small class="text-success">Harvest</small>
                            <div class="succession-date">${successionData.harvestDate.toLocaleDateString()}</div>
                        </div>
                    </div>
                </div>
            `;

            // Sidebar draggable format
            const successionDataForJson = {
                successionNumber: i + 1,
                sowDate: successionData.sowDate.toISOString(),
                transplantDate: successionData.transplantDate ? successionData.transplantDate.toISOString() : null,
                harvestDate: successionData.harvestDate.toISOString(),
                method: successionData.method
            };

            sidebarHTML += `
                <div class="succession-item" draggable="true" data-succession-index="${i}" data-succession-data='${JSON.stringify(successionDataForJson)}'>
                    <div class="succession-header">
                        <div class="succession-title-section">
                            <span class="succession-title">Succession ${i + 1}</span>
                            <small class="text-muted">${successionData.method}</small>
                        </div>
                    </div>
                    <div class="succession-dates">
                        <div class="date-row">
                            <span class="date-label">Sow:</span>
                            <span class="date-value">${successionData.sowDate.toLocaleDateString()}</span>
                        </div>
                        ${successionData.transplantDate ? `
                        <div class="date-row">
                            <span class="date-label">Transplant:</span>
                            <span class="date-value">${successionData.transplantDate.toLocaleDateString()}</span>
                        </div>
                        ` : ''}
                        <div class="date-row">
                            <span class="date-label">Harvest:</span>
                            <span class="date-value">${successionData.harvestDate.toLocaleDateString()}</span>
                        </div>
                    </div>
                </div>
            `;
        }

        // Update preview div if it exists
        if (previewDiv) {
            previewDiv.innerHTML = previewHTML;
        }
        
        // Show impact div if it exists
        if (impactDiv) {
            impactDiv.style.display = 'block';
        }

        // Update sidebar
        const successionList = document.getElementById('successionList');
        const successionSidebar = document.getElementById('successionSidebar');
        const aiChatSection = document.getElementById('aiChatSection');

        if (successionList && successionSidebar && aiChatSection) {
            if (successions > 0) {
                successionList.innerHTML = sidebarHTML;
                successionSidebar.style.display = 'block';
                aiChatSection.style.display = 'block'; // Keep AI chat visible

                console.log('📝 Sidebar HTML created:', sidebarHTML.substring(0, 200) + '...');

                // Initialize drag and drop
                initializeDragAndDrop();
            } else {
                successionSidebar.style.display = 'none';
                aiChatSection.style.display = 'block';
            }
        }
    }

    // Get succession interval based on crop type
    function getSuccessionInterval(cropName, varietyName) {
        // Default intervals by crop type
        const intervals = {
            'carrot': 42, // 6 weeks (storage crop - can stay in ground 4-6 weeks after maturity)
            'beetroot': 21, // 3 weeks
            'lettuce': 14, // 2 weeks (fortnightly)
            'radish': 7, // 1 week
            'onion': 21, // 3 weeks
            'spinach': 10, // 10 days
            'kale': 14, // 2 weeks
            'chard': 14, // 2 weeks
            'pak choi': 10, // 10 days
            'cabbage': 21, // 3 weeks
            'broccoli': 21, // 3 weeks
            'cauliflower': 21, // 3 weeks
            'brussels sprouts': 30, // 4 weeks (long harvest window means wider spacing)
            'brussel sprouts': 30, // 4 weeks (alternate spelling)
            'peas': 14, // 2 weeks
            'beans': 14, // 2 weeks
            'tomato': 21, // 3 weeks
            'pepper': 21, // 3 weeks
            'cucumber': 60, // ~8 weeks (3 successions for continuous harvest: early, mid, late)
            'zucchini': 45, // ~6 weeks (very productive - 2-3 successions sufficient)
            'courgette': 45, // ~6 weeks (same as zucchini - very productive plants)
            'aubergine': 90, // ~12 weeks (long season crop, 1-2 successions typical)
            'eggplant': 90, // ~12 weeks (same as aubergine - continuous producer once established)
            'corn': 14, // 2 weeks
            'potato': 21, // 3 weeks
            'garlic': 30, // 4 weeks
            'leek': 21, // 3 weeks
            'celery': 21, // 3 weeks
            'fennel': 14, // 2 weeks
            'herbs': 14 // 2 weeks
        };

        // Check for specific crop matches
        for (const [crop, interval] of Object.entries(intervals)) {
            if (cropName.includes(crop) || varietyName.includes(crop)) {
                return interval;
            }
        }

        return 21; // Default 3 weeks
    }

    function getSeedRequirements(cropName, varietyName) {
        // Seed and transplant requirements per square foot
        const requirements = {
            'carrot': { seedsPerSqFt: 80, seedUnit: 'seeds', transplantsPerSqFt: null },
            'beetroot': { seedsPerSqFt: 16, seedUnit: 'seeds', transplantsPerSqFt: null },
            'lettuce': { seedsPerSqFt: 4, seedUnit: 'seeds', transplantsPerSqFt: 4 },
            'radish': { seedsPerSqFt: 16, seedUnit: 'seeds', transplantsPerSqFt: null },
            'onion': { seedsPerSqFt: 32, seedUnit: 'seeds', transplantsPerSqFt: null },
            'spinach': { seedsPerSqFt: 8, seedUnit: 'seeds', transplantsPerSqFt: null },
            'kale': { seedsPerSqFt: 4, seedUnit: 'seeds', transplantsPerSqFt: 4 },
            'chard': { seedsPerSqFt: 4, seedUnit: 'seeds', transplantsPerSqFt: 4 },
            'pak choi': { seedsPerSqFt: 4, seedUnit: 'seeds', transplantsPerSqFt: 4 },
            'cabbage': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: 1 },
            'broccoli': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: 1 },
            'cauliflower': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: 1 },
            'peas': { seedsPerSqFt: 8, seedUnit: 'seeds', transplantsPerSqFt: null },
            'beans': { seedsPerSqFt: 6, seedUnit: 'seeds', transplantsPerSqFt: null },
            'tomato': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: 1 },
            'pepper': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: 1 },
            'cucumber': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: 1 },
            'zucchini': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: 1 },
            'corn': { seedsPerSqFt: 4, seedUnit: 'seeds', transplantsPerSqFt: null },
            'potato': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: null },
            'garlic': { seedsPerSqFt: 4, seedUnit: 'cloves', transplantsPerSqFt: null },
            'leek': { seedsPerSqFt: 16, seedUnit: 'seeds', transplantsPerSqFt: 16 },
            'celery': { seedsPerSqFt: 4, seedUnit: 'seeds', transplantsPerSqFt: 4 },
            'fennel': { seedsPerSqFt: 4, seedUnit: 'seeds', transplantsPerSqFt: 4 },
            'brussels sprouts': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: 1 },
            'brussel sprouts': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: 1 },
            'herbs': { seedsPerSqFt: 4, seedUnit: 'seeds', transplantsPerSqFt: null }
        };

        // Check for specific crop matches
        for (const [crop, req] of Object.entries(requirements)) {
            if (cropName.includes(crop) || varietyName.includes(crop)) {
                return req;
            }
        }

        // Default requirements
        return { seedsPerSqFt: 4, seedUnit: 'seeds', transplantsPerSqFt: null };
    }

    // Calculate detailed succession dates including sowing and transplant
    function calculateSuccessionDates(harvestStart, successionIndex, interval, cropName, varietyName, totalSuccessions = 1, varietyMaturityDays = null, varietyHarvestWindowDays = null) {
        // Get crop-specific timing data
        const cropTiming = getCropTiming(cropName, varietyName);

        // For long-season crops like Brussels sprouts, use advanced seasonal planning
        if (cropName.toLowerCase().includes('brussels') || cropTiming.daysToHarvest >= 100) {
            // Advanced seasonal algorithm for Brussels sprouts
            const harvestYear = harvestStart.getFullYear();

            // Use crop-specific transplant window from database (fallback to default)
            let plantingWindowStart, plantingWindowEnd;
            if (cropTiming.transplantWindow) {
                // Database-driven transplant window
                plantingWindowStart = new Date(harvestYear, cropTiming.transplantWindow.startMonth, cropTiming.transplantWindow.startDay);
                plantingWindowEnd = new Date(harvestYear, cropTiming.transplantWindow.endMonth, cropTiming.transplantWindow.endDay);
                console.log(`🌱 Using database transplant window for ${cropName}: ${cropTiming.transplantWindow.description}`);
            } else {
                // Fallback to default window for crops without database entry
                plantingWindowStart = new Date(harvestYear, 2, 15); // March 15
                plantingWindowEnd = new Date(harvestYear, 4, 15); // May 15
                console.log(`⚠️ No transplant window in database for ${cropName}, using default: March 15 - May 15`);
            }

            const plantingWindowDays = (plantingWindowEnd - plantingWindowStart) / (24 * 60 * 60 * 1000);

            // Get transplant interval from crop timing data
            const transplantInterval = cropTiming.daysToTransplant || 21; // Default 21 days if not specified

            // USER REQUESTED SUCCESSIONS: Divide transplant window by the user's requested count
            // This ensures that if user wants 3 successions, the transplant window is divided into 3 parts
            const requestedSuccessions = totalSuccessions || 1;  // Default to 1 if not provided
            
            console.log(`🌱 ${cropName} transplant window: ${plantingWindowDays.toFixed(0)} days`);
            console.log(`� User requested ${requestedSuccessions} succession(s) - dividing transplant window accordingly`);

            // 🚨 FIX: Use the user's requested succession count to divide transplant window
            if (successionIndex >= requestedSuccessions) {
                console.warn(`⚠️ Succession ${successionIndex + 1} exceeds requested count (${requestedSuccessions})`);
            }

            // For Brussels sprouts, ensure transplant dates stay within the optimal window
            // NEW ALGORITHM: Evenly space transplant dates across transplant window, then calculate sowing dates
            // This ensures all successions are properly distributed across the available transplant period

            console.log(`🌱 ${cropName} transplant window: ${plantingWindowStart.toLocaleDateString()} - ${plantingWindowEnd.toLocaleDateString()} (${plantingWindowDays.toFixed(0)} days)`);

            // Evenly space transplant dates across the transplant window
            let transplantDate;
            if (requestedSuccessions > 1) {
                // Distribute transplants evenly across the window based on user's requested count
                const spacing = plantingWindowDays / (requestedSuccessions - 1);
                transplantDate = new Date(plantingWindowStart.getTime() + (successionIndex * spacing * 24 * 60 * 60 * 1000));
            } else {
                // Single succession: use middle of window
                transplantDate = new Date(plantingWindowStart.getTime() + (plantingWindowDays / 2 * 24 * 60 * 60 * 1000));
            }

            console.log(`🎯 Succession ${successionIndex + 1}/${requestedSuccessions}: transplant ${transplantDate.toLocaleDateString()}`);

            // Calculate sowing date by subtracting transplant interval from transplant date
            let plantingDate = new Date(transplantDate.getTime() - (transplantInterval * 24 * 60 * 60 * 1000));

            // Ensure sowing date doesn't go before the start of the transplant window
            if (plantingDate < plantingWindowStart) {
                plantingDate = new Date(plantingWindowStart.getTime());
                // Recalculate transplant date from adjusted sowing date
                transplantDate = new Date(plantingDate.getTime() + (transplantInterval * 24 * 60 * 60 * 1000));
                console.log(`⚠️ Succession ${successionIndex + 1} sowing date adjusted, transplant recalculated`);
            }

            // Ensure transplant date doesn't exceed the transplant window
            if (transplantDate > plantingWindowEnd) {
                transplantDate = new Date(plantingWindowEnd.getTime());
                // Recalculate sowing date from adjusted transplant date
                plantingDate = new Date(transplantDate.getTime() - (transplantInterval * 24 * 60 * 60 * 1000));
                console.log(`⚠️ Succession ${successionIndex + 1} transplant date capped, sowing recalculated`);
            }

            // Seasonal growth rate adjustment based on summer solstice (June 21st)
            // Use passed variety maturity days if provided, otherwise fall back to crop timing
            const baseDaysToHarvest = varietyMaturityDays || cropTiming.daysToHarvest;
            const plantingMonth = plantingDate.getMonth();
            const plantingDay = plantingDate.getDate();
            let seasonalMultiplier = 1.0;

            // Calculate days from summer solstice (June 21st)
            const summerSolstice = new Date(harvestYear, 5, 21); // June 21st
            const daysFromSolstice = Math.floor((plantingDate - summerSolstice) / (24 * 60 * 60 * 1000));

            // Growth rate based on daylight trend from solstice
            if (daysFromSolstice <= 0) {
                // Before June 21st: Days getting longer (increasing sunlight)
                const daysBeforeSolstice = Math.abs(daysFromSolstice);
                if (daysBeforeSolstice <= 30) {
                    seasonalMultiplier = 0.95; // Slightly faster (optimal increasing daylight)
                } else if (daysBeforeSolstice <= 60) {
                    seasonalMultiplier = 0.9; // Faster (good increasing daylight)
                } else {
                    seasonalMultiplier = 0.85; // Much faster (excellent spring conditions)
                }
            } else {
                // After June 21st: Days getting shorter (decreasing sunlight)
                if (daysFromSolstice <= 30) {
                    seasonalMultiplier = 1.05; // Slightly slower (minimal daylight decrease)
                } else if (daysFromSolstice <= 60) {
                    seasonalMultiplier = 1.1; // Slower (noticeable daylight decrease)
                } else if (daysFromSolstice <= 90) {
                    seasonalMultiplier = 1.2; // Much slower (significant daylight decrease)
                } else {
                    seasonalMultiplier = 1.3; // Very slow (severe daylight decrease)
                }
            }

            const adjustedDaysToHarvest = Math.round(baseDaysToHarvest * seasonalMultiplier);

            // CORRECTED: Calculate harvest date based on VARIETY-SPECIFIC maturity, not user's broad window
            // Each variety has its own maturity period and harvest window duration
            // Calculate from transplant date + maturity days, then add variety harvest window
            
            // Calculate when THIS succession's harvest should start (transplant + maturity)
            const varietyHarvestStart = new Date(transplantDate.getTime() + (adjustedDaysToHarvest * 24 * 60 * 60 * 1000));
            
            // Get variety's harvest window duration - use passed value if provided, otherwise fall back to database/default
            const actualVarietyHarvestWindowDays = varietyHarvestWindowDays || window.currentVarietyData?.harvest_window_days || cropTiming.harvestWindowDays || 60;
            
            // Calculate when THIS succession's harvest should end
            const varietyHarvestEnd = new Date(varietyHarvestStart.getTime() + (actualVarietyHarvestWindowDays * 24 * 60 * 60 * 1000));
            
            // Space harvest dates across the VARIETY-SPECIFIC harvest window for this succession
            const harvestSpacing = actualVarietyHarvestWindowDays / Math.max(1, requestedSuccessions - 1);
            let harvestDate = new Date(varietyHarvestStart.getTime() + (successionIndex * harvestSpacing * 24 * 60 * 60 * 1000));
            
            // Ensure harvest date doesn't exceed variety's harvest window
            if (harvestDate > varietyHarvestEnd) {
                harvestDate = new Date(varietyHarvestEnd.getTime());
            }

            console.log(`🌱 Succession ${successionIndex + 1} - VARIETY-SPECIFIC Harvest Planning:`, {
                varietyName: varietyName || 'Unknown',
                seasonType: window.currentVarietyData?.season_type || 'unknown',
                maturityDays: adjustedDaysToHarvest,
                plantingDate: plantingDate.toLocaleDateString(),
                transplantDate: transplantDate.toLocaleDateString(),
                harvestDate: harvestDate.toLocaleDateString(),
                varietyHarvestWindow: `${varietyHarvestStart.toLocaleDateString()} - ${varietyHarvestEnd.toLocaleDateString()}`,
                harvestWindowDays: actualVarietyHarvestWindowDays
            });

            return {
                sowDate: plantingDate,
                transplantDate: transplantDate,
                harvestDate: harvestDate,
                method: cropTiming.method || 'Direct sow'
            };
        }

        // For short-season crops that don't use advanced seasonal planning
        // Calculate harvest date for this succession based on interval spacing
        const fallbackHarvestDate = new Date(harvestStart.getTime() + (successionIndex * interval * 24 * 60 * 60 * 1000));

        // Calculate sowing date (working backwards from harvest)
        const fallbackSowDate = new Date(fallbackHarvestDate.getTime() - (cropTiming.daysToHarvest * 24 * 60 * 60 * 1000));

        // Calculate transplant date if applicable
        let fallbackTransplantDate = null;
        if (cropTiming.daysToTransplant) {
            fallbackTransplantDate = new Date(fallbackSowDate.getTime() + (cropTiming.daysToTransplant * 24 * 60 * 60 * 1000));
        }

        return {
            sowDate: fallbackSowDate,
            transplantDate: fallbackTransplantDate,
            harvestDate: fallbackHarvestDate,
            method: cropTiming.method || 'Direct sow'
        };
    }

    // Get crop-specific timing information
    function getCropTiming(cropName, varietyName) {
        const cropLower = cropName.toLowerCase();
        const varietyLower = varietyName.toLowerCase();

        // First, check if we have variety-specific data from FarmOS
        if (window.cropData && window.cropData.varieties) {
            const variety = window.cropData.varieties.find(v =>
                v.name && v.name.toLowerCase().includes(varietyLower) &&
                v.crop_type && v.crop_type.toLowerCase().includes(cropLower)
            );

            if (variety && variety.transplant_month_start && variety.transplant_month_end) {
                console.log(`🌱 Using FarmOS transplant window for ${variety.name}: months ${variety.transplant_month_start}-${variety.transplant_month_end}`);

                // Calculate transplant window from month numbers
                const startMonth = variety.transplant_month_start - 1; // Convert to 0-indexed
                const endMonth = variety.transplant_month_end - 1; // Convert to 0-indexed

                return {
                    daysToHarvest: variety.harvest_days || variety.maturity_days || 60,
                    daysToTransplant: variety.propagation_days || 35,
                    method: variety.propagation_days ? 'Transplant seedlings' : 'Direct sow',
                    transplantWindow: {
                        startMonth: startMonth,
                        startDay: 1, // Default to 1st of the month
                        endMonth: endMonth,
                        endDay: 15, // Default to 15th of the month
                        description: `Month ${variety.transplant_month_start} - Month ${variety.transplant_month_end} (FarmOS data)`
                    }
                };
            }
        }

        // Fallback to hardcoded timing data if no FarmOS data available
        const timingData = {
            // Root vegetables
            'carrot': {
                daysToHarvest: 70,
                daysToTransplant: null,
                method: 'Direct sow'
            },
            'beetroot': {
                daysToHarvest: 55,
                daysToTransplant: null,
                method: 'Direct sow'
            },
            'radish': {
                daysToHarvest: 25,
                daysToTransplant: null,
                method: 'Direct sow'
            },
            'potato': {
                daysToHarvest: 90,
                daysToTransplant: null,
                method: 'Plant seed potatoes'
            },
            'onion': {
                daysToHarvest: 120,
                daysToTransplant: 35,
                method: 'Transplant seedlings'
            },
            'garlic': {
                daysToHarvest: 240,
                daysToTransplant: null,
                method: 'Plant cloves'
            },
            'leek': {
                daysToHarvest: 120,
                daysToTransplant: 35,
                method: 'Transplant seedlings'
            },

            // Leafy greens
            'lettuce': {
                daysToHarvest: 45,
                daysToTransplant: 21,
                method: 'Transplant seedlings'
            },
            'spinach': {
                daysToHarvest: 40,
                daysToTransplant: null,
                method: 'Direct sow'
            },
            'kale': {
                daysToHarvest: 60,
                daysToTransplant: 28,
                method: 'Transplant seedlings'
            },
            'chard': {
                daysToHarvest: 50,
                daysToTransplant: 21,
                method: 'Transplant seedlings'
            },
            'pak choi': {
                daysToHarvest: 35,
                daysToTransplant: null,
                method: 'Direct sow'
            },

            // Brassicas
            'cabbage': {
                daysToHarvest: 80,
                daysToTransplant: 35,
                method: 'Transplant seedlings'
            },
            'broccoli': {
                daysToHarvest: 70,
                daysToTransplant: 35,
                method: 'Transplant seedlings'
            },
            'cauliflower': {
                daysToHarvest: 75,
                daysToTransplant: 35,
                method: 'Transplant seedlings'
            },

            // Legumes
            'peas': {
                daysToHarvest: 60,
                daysToTransplant: null,
                method: 'Direct sow'
            },
            'beans': {
                daysToHarvest: 55,
                daysToTransplant: null,
                method: 'Direct sow'
            },

            // Fruiting vegetables
            'tomato': {
                daysToHarvest: 75,
                daysToTransplant: 42,
                method: 'Transplant seedlings'
            },
            'pepper': {
                daysToHarvest: 80,
                daysToTransplant: 42,
                method: 'Transplant seedlings'
            },
            'cucumber': {
                daysToHarvest: 55,
                daysToTransplant: 21,
                method: 'Transplant seedlings'
            },
            'zucchini': {
                daysToHarvest: 50,
                daysToTransplant: 21,
                method: 'Transplant seedlings'
            },

            // Other
            'corn': {
                daysToHarvest: 75,
                daysToTransplant: null,
                method: 'Direct sow'
            },
            'celery': {
                daysToHarvest: 100,
                daysToTransplant: 42,
                method: 'Transplant seedlings'
            },
            'fennel': {
                daysToHarvest: 80,
                daysToTransplant: 35,
                method: 'Transplant seedlings'
            },
            'brussels sprouts': {
                daysToHarvest: 110,
                daysToTransplant: 35,
                method: 'Transplant seedlings',
                transplantWindow: {
                    startMonth: 4,  // May (0-indexed, FarmOS month 5)
                    startDay: 1,
                    endMonth: 5,    // June (0-indexed, FarmOS month 6)
                    endDay: 15,
                    description: 'May 1 - June 15 (FarmOS transplant window)'
                }
            },
            'brussels': {
                daysToHarvest: 110,
                daysToTransplant: 35,
                method: 'Transplant seedlings',
                transplantWindow: {
                    startMonth: 4,  // May
                    startDay: 1,
                    endMonth: 5,    // June
                    endDay: 15,
                    description: 'May 1 - June 15 (FarmOS transplant window)'
                }
            },
            'cabbage': {
                daysToHarvest: 80,
                daysToTransplant: 35,
                method: 'Transplant seedlings',
                transplantWindow: {
                    startMonth: 2,  // March
                    startDay: 1,
                    endMonth: 4,    // May
                    endDay: 15,
                    description: 'March 1 - May 15 (spring transplanting)'
                }
            },
            'broccoli': {
                daysToHarvest: 70,
                daysToTransplant: 35,
                method: 'Transplant seedlings',
                transplantWindow: {
                    startMonth: 2,  // March
                    startDay: 15,
                    endMonth: 5,    // June
                    endDay: 15,
                    description: 'March 15 - June 15 (extended spring)'
                }
            },
            'lettuce': {
                daysToHarvest: 45,
                daysToTransplant: 21,
                method: 'Transplant seedlings',
                transplantWindow: {
                    startMonth: 1,  // February
                    startDay: 15,
                    endMonth: 4,    // May
                    endDay: 30,
                    description: 'February 15 - May 30 (cool season transplanting)'
                }
            },
            'tomato': {
                daysToHarvest: 75,
                daysToTransplant: 42,
                method: 'Transplant seedlings',
                transplantWindow: {
                    startMonth: 3,  // April
                    startDay: 1,
                    endMonth: 5,    // June
                    endDay: 15,
                    description: 'April 1 - June 15 (after last frost)'
                }
            },
            'pepper': {
                daysToHarvest: 80,
                daysToTransplant: 42,
                method: 'Transplant seedlings',
                transplantWindow: {
                    startMonth: 3,  // April
                    startDay: 15,
                    endMonth: 5,    // June
                    endDay: 1,
                    description: 'April 15 - June 1 (warm season transplanting)'
                }
            },
            'celery': {
                daysToHarvest: 100,
                daysToTransplant: 42,
                method: 'Transplant seedlings',
                transplantWindow: {
                    startMonth: 2,  // March
                    startDay: 1,
                    endMonth: 4,    // May
                    endDay: 15,
                    description: 'March 1 - May 15 (cool season transplanting)'
                }
            }
        };

        // Check for specific crop matches
        for (const [crop, timing] of Object.entries(timingData)) {
            if (cropLower.includes(crop) || varietyLower.includes(crop)) {
                return timing;
            }
        }

        // Default timing for unknown crops
        return {
            daysToHarvest: 60,
            daysToTransplant: null,
            method: 'Direct sow',
            transplantWindow: {
                startMonth: 2,  // March
                startDay: 15,
                endMonth: 4,    // May
                endDay: 15,
                description: 'March 15 - May 15 (default transplanting window)'
            }
        };
    }

    // Update calendar grid visualization
    function updateCalendarGrid() {
        const calendarDiv = document.getElementById('calendarGrid');
        if (!calendarDiv) return;

        const baseYear = parseInt(harvestWindowData.selectedYear);
        let calendarHTML = '';

        // Check if harvest window spans multiple years
        const hasMultiYearHarvest = harvestWindowData.userEnd &&
            new Date(harvestWindowData.userEnd).getFullYear() > baseYear;

        if (hasMultiYearHarvest) {
            console.log('🌍 Multi-year harvest detected - showing weekly calendar for both years');
            calendarHTML = generateWeeklyCalendar(baseYear, baseYear + 1);
        } else {
            // Single year calendar
            calendarHTML = generateWeeklyCalendar(baseYear);
        }

        calendarDiv.innerHTML = calendarHTML;
        document.getElementById('harvestCalendar').style.display = 'block';
    }

    // Generate weekly calendar with ISO week numbers grouped by month
    function generateWeeklyCalendar(startYear, endYear = null) {
        const years = endYear ? [startYear, endYear] : [startYear];
        const months = ['January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December'];
        let html = '';

        years.forEach(year => {
            months.forEach((monthName, monthIndex) => {
                const weeksInMonth = getWeeksInMonth(year, monthIndex);
                
                if (weeksInMonth.length === 0) return; // Skip if no weeks
                
                // Month header
                html += `
                    <div class="col-12 col-lg-6 col-xl-4 mt-3">
                        <div class="month-header p-3 border rounded bg-light">
                            <h6 class="text-uppercase text-muted mb-3 fw-bold">
                                <i class="fas fa-calendar-alt me-2"></i>${monthName} ${year}
                            </h6>
                            <div class="d-grid gap-2" style="grid-template-columns: repeat(auto-fit, minmax(70px, 1fr));">
                `;
                
                // Week badges for this month
                weeksInMonth.forEach(weekInfo => {
                    const weekClass = getWeekClass(weekInfo.startDate, weekInfo.endDate);
                    const isStart = isWeekStart(weekInfo.startDate);
                    const isEnd = isWeekEnd(weekInfo.endDate);
                    
                    html += `
                        <div class="week-badge ${weekClass}" 
                             data-week="${weekInfo.isoWeek}" 
                             data-start="${weekInfo.startDate.toISOString().split('T')[0]}"
                             data-end="${weekInfo.endDate.toISOString().split('T')[0]}"
                             onclick="selectWeek(this)"
                             title="Week ${weekInfo.isoWeek}: ${formatDate(weekInfo.startDate)} - ${formatDate(weekInfo.endDate)}">
                            <div class="week-number">Wk ${weekInfo.isoWeek}</div>
                            <div class="week-dates">${weekInfo.startDate.getDate()}-${weekInfo.endDate.getDate()}</div>
                            ${isStart ? '<div class="week-marker">🌱</div>' : ''}
                            ${isEnd ? '<div class="week-marker">🏁</div>' : ''}
                        </div>
                    `;
                });
                
                html += `
                            </div>
                        </div>
                    </div>
                `;
            });
        });

        return html;
    }

    // Get all ISO weeks that overlap with a specific month
    function getWeeksInMonth(year, month) {
        const weeks = [];
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        
        let currentDate = new Date(firstDay);
        
        while (currentDate <= lastDay) {
            const isoWeek = getISOWeek(currentDate);
            const weekStart = getWeekStart(currentDate);
            const weekEnd = getWeekEnd(currentDate);
            
            // Only add if this week hasn't been added yet (check by ISO week number)
            if (!weeks.find(w => w.isoWeek === isoWeek)) {
                weeks.push({
                    isoWeek: isoWeek,
                    startDate: weekStart,
                    endDate: weekEnd
                });
            }
            
            // Move to next week
            currentDate = new Date(weekEnd);
            currentDate.setDate(currentDate.getDate() + 1);
        }
        
        return weeks;
    }

    // Get ISO week number for a date
    function getISOWeek(date) {
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    }

    // Get start of ISO week (Monday)
    function getWeekStart(date) {
        const d = new Date(date);
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1); // Adjust when day is Sunday
        return new Date(d.setDate(diff));
    }

    // Get end of ISO week (Sunday)
    function getWeekEnd(date) {
        const start = getWeekStart(date);
        const end = new Date(start);
        end.setDate(start.getDate() + 6);
        return end;
    }

    // Determine CSS class for week badge based on harvest window ranges
    function getWeekClass(weekStart, weekEnd) {
        if (!harvestWindowData.maxStart || !harvestWindowData.maxEnd) {
            return 'week-unavailable';
        }
        
        const maxStart = new Date(harvestWindowData.maxStart);
        const maxEnd = new Date(harvestWindowData.maxEnd);
        
        // Check if week overlaps with max range
        if (weekStart > maxEnd || weekEnd < maxStart) {
            return 'week-unavailable';
        }
        
        // Check if in user selected range
        if (harvestWindowData.userStart && harvestWindowData.userEnd) {
            const userStart = new Date(harvestWindowData.userStart);
            const userEnd = new Date(harvestWindowData.userEnd);
            
            if (weekStart <= userEnd && weekEnd >= userStart) {
                return 'week-selected';
            }
        }
        
        // Check if in AI recommended range
        if (harvestWindowData.aiStart && harvestWindowData.aiEnd) {
            const aiStart = new Date(harvestWindowData.aiStart);
            const aiEnd = new Date(harvestWindowData.aiEnd);
            
            if (weekStart <= aiEnd && weekEnd >= aiStart) {
                return 'week-optimal';
            }
        }
        
        // Available but not selected
        return 'week-extended';
    }

    // Check if this week contains the harvest start date
    function isWeekStart(weekStart) {
        if (!harvestWindowData.userStart) return false;
        const userStart = new Date(harvestWindowData.userStart);
        const weekEnd = getWeekEnd(weekStart);
        return weekStart <= userStart && userStart <= weekEnd;
    }

    // Check if this week contains the harvest end date
    function isWeekEnd(weekEnd) {
        if (!harvestWindowData.userEnd) return false;
        const userEnd = new Date(harvestWindowData.userEnd);
        const weekStart = getWeekStart(weekEnd);
        return weekStart <= userEnd && userEnd <= weekEnd;
    }

    // Handle week selection (click on week badge)
    function selectWeek(weekElement) {
        const weekStart = weekElement.dataset.start;
        const weekEnd = weekElement.dataset.end;
        const weekNumber = weekElement.dataset.week;
        
        console.log(`📅 Week ${weekNumber} clicked:`, weekStart, 'to', weekEnd);
        
        // Simple two-click selection: first click = start, second click = end
        if (!window.harvestSelectionStart) {
            // First click - set start
            window.harvestSelectionStart = weekStart;
            weekElement.classList.add('week-selecting');
            console.log('🌱 Harvest start set to:', weekStart);
        } else {
            // Second click - set end and trigger calculation
            const startDate = new Date(window.harvestSelectionStart);
            const endDate = new Date(weekEnd);
            
            if (endDate < startDate) {
                // Clicked earlier week - swap
                harvestWindowData.userStart = weekEnd;
                harvestWindowData.userEnd = window.harvestSelectionStart;
            } else {
                harvestWindowData.userStart = window.harvestSelectionStart;
                harvestWindowData.userEnd = weekEnd;
            }
            
            // Update hidden inputs for compatibility
            const startInput = document.getElementById('harvestStart');
            const endInput = document.getElementById('harvestEnd');
            if (startInput) startInput.value = harvestWindowData.userStart;
            if (endInput) endInput.value = harvestWindowData.userEnd;
            
            // Update visible display
            const displayStart = document.getElementById('displayHarvestStart');
            const displayEnd = document.getElementById('displayHarvestEnd');
            const displayContainer = document.getElementById('selectedHarvestWindowDisplay');
            if (displayStart && displayEnd && displayContainer) {
                displayStart.textContent = new Date(harvestWindowData.userStart).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                displayEnd.textContent = new Date(harvestWindowData.userEnd).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                displayContainer.style.display = 'block';
            }
            
            // Clear selection state
            delete window.harvestSelectionStart;
            document.querySelectorAll('.week-selecting').forEach(el => el.classList.remove('week-selecting'));
            
            // Update display
            updateHarvestWindowDisplay();
            updateCalendarGrid();
            
            // Trigger succession calculation
            console.log('🏁 Harvest window selected:', harvestWindowData.userStart, 'to', harvestWindowData.userEnd);
            
            // Check if varietal succession is enabled
            const varietalToggle = document.getElementById('varietalSuccessionToggle');
            if (varietalToggle && varietalToggle.checked) {
                // Varietal succession is enabled - trigger full recalculation
                console.log('🔄 Varietal succession active - triggering full recalculation with new harvest window');
                calculateSuccessionPlan();
            } else {
                // Normal mode - just update the impact preview
                updateSuccessionImpact();
            }
        }
    }

    // Format date for display
    function formatDate(date) {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return `${date.getDate()} ${months[date.getMonth()]}`;
    }

    // Helper function to generate HTML for a single month (OLD - kept for backwards compatibility)
    function generateMonthHTML(month, index, year) {
        let monthClass = 'calendar-month';
        let monthTitle = `${month} ${year}`;

        // Determine if this month is within different ranges
        if (isMonthInRange(index, harvestWindowData.maxStart, harvestWindowData.maxEnd, year)) {
            if (isMonthInRange(index, harvestWindowData.userStart, harvestWindowData.userEnd, year)) {
                monthClass += ' selected';
                monthTitle += ' - Selected Harvest Period';
            } else if (isMonthInRange(index, harvestWindowData.aiStart, harvestWindowData.aiEnd, year)) {
                monthClass += ' optimal';
                monthTitle += ' - Optimal Harvest Period';
            } else {
                monthClass += ' extended';
                monthTitle += ' - Extended Harvest Period';
            }
        }

        return `
            <div class="col-6 col-md-4 col-lg-3 mb-3">
                <div class="${monthClass}" title="${monthTitle}">
                    <strong>${month}</strong>
                    <div class="mt-1">${year}</div>
                </div>
            </div>
        `;
    }

    // Helper function to check if a month is within a date range
    function isMonthInRange(monthIndex, startDate, endDate, year = null) {
        if (!startDate || !endDate) return false;

        // Use provided year or fallback to selectedYear
        const targetYear = year || harvestWindowData.selectedYear;
        const monthStart = new Date(targetYear, monthIndex, 1);
        const monthEnd = new Date(targetYear, monthIndex + 1, 0);

        const rangeStart = new Date(startDate);
        const rangeEnd = new Date(endDate);

        return monthStart <= rangeEnd && monthEnd >= rangeStart;
    }

    // Update harvest window data from AI results
    function updateHarvestWindowData(aiResult) {
        if (!aiResult) return;

        console.log('📊 updateHarvestWindowData called with:', aiResult);

        // Only update max range if AI provides better data than current
        // Don't override manually set crop-specific dates
        if (aiResult.maximum_start && aiResult.maximum_end) {
            const aiMaxStart = new Date(aiResult.maximum_start);
            const aiMaxEnd = new Date(aiResult.maximum_end);

            // Only update if AI dates are significantly different (more than 30 days)
            // This prevents AI from overriding correct crop-specific dates
            if (harvestWindowData.maxStart && harvestWindowData.maxEnd) {
                const currentMaxStart = new Date(harvestWindowData.maxStart);
                const currentMaxEnd = new Date(harvestWindowData.maxEnd);

                const aiDuration = aiMaxEnd - aiMaxStart;
                const currentDuration = currentMaxEnd - currentMaxStart;

                if (Math.abs(aiDuration - currentDuration) > (30 * 24 * 60 * 60 * 1000)) { // 30 days
                    console.log('⚠️ AI dates differ significantly from current, updating max range');
                    harvestWindowData.maxStart = aiResult.maximum_start;
                    harvestWindowData.maxEnd = aiResult.maximum_end;
                } else {
                    console.log('✅ AI dates similar to current, keeping existing max range');
                }
            } else {
                // No existing data, use AI results
                harvestWindowData.maxStart = aiResult.maximum_start;
                harvestWindowData.maxEnd = aiResult.maximum_end;
            }
        }

        // Always update AI recommended range
        if (harvestWindowData.maxStart && harvestWindowData.maxEnd) {
            const maxStart = new Date(harvestWindowData.maxStart);
            const maxEnd = new Date(harvestWindowData.maxEnd);
            const maxDuration = maxEnd - maxStart;

            harvestWindowData.aiStart = harvestWindowData.maxStart;
            harvestWindowData.aiEnd = new Date(maxStart.getTime() + (maxDuration * 0.8)).toISOString().split('T')[0];

            // Only set user range to AI recommendation if not already set
            if (!harvestWindowData.userStart || !harvestWindowData.userEnd) {
                harvestWindowData.userStart = harvestWindowData.aiStart;
                harvestWindowData.userEnd = harvestWindowData.aiEnd;
                
                console.log('🤖 Auto-set to AI recommended window:', harvestWindowData.aiStart, 'to', harvestWindowData.aiEnd);
                
                // Update hidden inputs for compatibility
                const startInput = document.getElementById('harvestStart');
                const endInput = document.getElementById('harvestEnd');
                if (startInput) startInput.value = harvestWindowData.aiStart;
                if (endInput) endInput.value = harvestWindowData.aiEnd;
                
                // Update visible display
                const displayStart = document.getElementById('displayHarvestStart');
                const displayEnd = document.getElementById('displayHarvestEnd');
                const displayContainer = document.getElementById('selectedHarvestWindowDisplay');
                if (displayStart && displayEnd && displayContainer) {
                    displayStart.textContent = new Date(harvestWindowData.aiStart).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                    displayEnd.textContent = new Date(harvestWindowData.aiEnd).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                    displayContainer.style.display = 'block';
                }
                
                // Update the calendar grid to show the selected weeks
                updateCalendarGrid();
                
                // Trigger full succession calculation directly (not just preview)
                if (document.getElementById('cropSelect')?.value) {
                    console.log('🚀 Auto-triggering FULL succession calculation with AI window');
                    // Use setTimeout to ensure DOM is updated first
                    setTimeout(() => {
                        calculateSuccessionPlan();
                    }, 100);
                }
            }
        }

        console.log('📊 Final harvestWindowData:', harvestWindowData);
        updateHarvestWindowDisplay();
    }

    function toggleQuickForm(successionIndex, formType, forceShow = null) {
        const checkbox = document.getElementById(`${formType}-enabled-${successionIndex}`);
        const formElement = document.getElementById(`quick-form-${formType}-${successionIndex}`);

        if (checkbox && formElement) {
            // If forceShow is specified, use it; otherwise check the checkbox state
            const shouldShow = forceShow !== null ? forceShow : checkbox.checked;
            
            if (shouldShow) {
                formElement.style.display = 'block';
            } else {
                formElement.style.display = 'none';
            }
        }
    }

    /**
     * Scroll to the quick forms section
     */
    function scrollToQuickForms() {
        const quickFormsContainer = document.getElementById('quickFormTabsContainer');
        if (quickFormsContainer) {
            quickFormsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    /**
     * Scroll to the top of the page
     */
    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    async function submitAllQuickForms() {
        // Collect all form data
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        // Get all planting data
        const plantings = [];
        const tabPanes = document.querySelectorAll('#tabContent .tab-pane');

        tabPanes.forEach((pane, index) => {
            const planting = {
                succession_index: index,
                season: '',
                crop_variety: '',
                logs: {}
            };

            // Get season and crop variety
            const seasonInput = pane.querySelector(`input[name="plantings[${index}][season]"]`);
            const cropInput = pane.querySelector(`input[name="plantings[${index}][crop_variety]"]`);
            planting.season = seasonInput ? seasonInput.value : '';
            planting.crop_variety = cropInput ? cropInput.value : '';

            // Check each form type
            ['seeding', 'transplanting', 'harvest'].forEach(formType => {
                const checkbox = document.getElementById(`${formType}-enabled-${index}`);
                if (checkbox && checkbox.checked) {
                    // Form is enabled, collect its data
                    const formElement = document.getElementById(`quick-form-${formType}-${index}`);
                    if (formElement) {
                        const formDataObj = {};
                        const inputs = formElement.querySelectorAll('input, select, textarea');
                        inputs.forEach(input => {
                            if (input.name) {
                                const nameParts = input.name.replace(`plantings[${index}][${formType}][`, '').replace(']', '').split('][');
                                let current = formDataObj;
                                for (let i = 0; i < nameParts.length - 1; i++) {
                                    if (!current[nameParts[i]]) current[nameParts[i]] = {};
                                    current = current[nameParts[i]];
                                }
                                
                                // Log quantity fields for debugging
                                if (input.name.includes('quantity][value]')) {
                                    console.log(`📊 Submitting ${formType} for succession ${index + 1}: quantity = ${input.value} (field: ${input.name})`);
                                }
                                
                                // Handle checkboxes specially - include them even if unchecked
                                if (input.type === 'checkbox') {
                                    current[nameParts[nameParts.length - 1]] = input.checked ? '1' : '0';
                                } else if (input.value) {
                                    current[nameParts[nameParts.length - 1]] = input.value;
                                }
                            }
                        });
                        planting.logs[formType] = formDataObj;
                    }
                }
            });

            if (Object.keys(planting.logs).length > 0) {
                plantings.push(planting);
            }
        });

        if (plantings.length === 0) {
            alert('No forms have been filled out. Please check at least one form type and fill out the required fields.');
            return;
        }

        // Submit to backend
        try {
            showLoading(true);
            const response = await fetch('/admin/farmos/succession-planning/submit-all-logs', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ plantings })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                showToast('All planting records submitted successfully!', 'success');
                // Hide all forms and uncheck all checkboxes
                document.querySelectorAll('.embedded-quick-form').forEach(form => {
                    form.style.display = 'none';
                });
                document.querySelectorAll('.log-type-checkbox').forEach(checkbox => {
                    checkbox.checked = false;
                });
            } else {
                showToast('Failed to submit planting records: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Submit error:', error);
            showToast('Error submitting planting records', 'error');
        } finally {
            showLoading(false);
        }
    }

    // Bed Dimensions Persistence (localStorage)
    // Save only bed dimensions - spacing and dates auto-fill per crop
    function saveBedDimensions() {
        const bedLength = document.getElementById('bedLength')?.value;
        const bedWidth = document.getElementById('bedWidth')?.value;
        
        if (bedLength) {
            localStorage.setItem('farmBedLength', bedLength);
        }
        if (bedWidth) {
            localStorage.setItem('farmBedWidth', bedWidth);
        }
        
        console.log('💾 Saved bed dimensions:', { bedLength, bedWidth });
    }

    function loadBedDimensions() {
        const savedBedLength = localStorage.getItem('farmBedLength');
        const savedBedWidth = localStorage.getItem('farmBedWidth');
        
        const bedLengthInput = document.getElementById('bedLength');
        const bedWidthInput = document.getElementById('bedWidth');
        
        if (savedBedLength && bedLengthInput && !bedLengthInput.value) {
            bedLengthInput.value = savedBedLength;
            console.log('📂 Loaded bed length:', savedBedLength, 'm');
        }
        
        if (savedBedWidth && bedWidthInput && !bedWidthInput.value) {
            // Convert old cm values to meters if necessary
            let bedWidthMeters = savedBedWidth;
            if (savedBedWidth > 10) { // Likely in cm (beds are typically < 2m wide)
                bedWidthMeters = savedBedWidth / 100;
                console.log('📂 Converting saved bed width from cm to meters:', savedBedWidth, 'cm →', bedWidthMeters, 'm');
            }
            bedWidthInput.value = bedWidthMeters;
            console.log('📂 Loaded bed width:', bedWidthMeters, 'm');
        }
    }

    // Initialize Succession Planner when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        // AGGRESSIVE scroll to top - prevent browser auto-scroll restoration
        // Run immediately on DOMContentLoaded
        window.scrollTo(0, 0);
        document.documentElement.scrollTop = 0;
        document.body.scrollTop = 0;

        // Prevent any scroll events during initial page load
        let scrollLocked = true;
        const preventScroll = (e) => {
            if (scrollLocked) {
                window.scrollTo(0, 0);
            }
        };
        window.addEventListener('scroll', preventScroll, { passive: false });

        // Additional scroll resets at intervals
        setTimeout(() => { window.scrollTo(0, 0); }, 10);
        setTimeout(() => { window.scrollTo(0, 0); }, 50);
        setTimeout(() => { window.scrollTo(0, 0); }, 100);
        setTimeout(() => { 
            window.scrollTo(0, 0);
            // Unlock scrolling after page is fully loaded
            scrollLocked = false;
            window.removeEventListener('scroll', preventScroll);
        }, 500);

        console.log('🌱 Initializing Succession Planning Interface...');
        
        // 🧹 Clear stale localStorage harvest window dates (Nov 1 - Feb 28 from old testing)
        // AI will calculate fresh harvest windows based on selected variety
        clearStaleHarvestWindows();

        // Initialize varietal succession carousel button event listeners
        const prevVarietyBtn = document.getElementById('prevVarietyBtn');
        const nextVarietyBtn = document.getElementById('nextVarietyBtn');
        
        if (prevVarietyBtn) {
            prevVarietyBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                switchVarietyInfo('prev', e);
                return false;
            });
        }
        
        if (nextVarietyBtn) {
            nextVarietyBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                switchVarietyInfo('next', e);
                return false;
            });
        }

        // Create SuccessionPlanner instance with configuration
        const successionPlanner = new SuccessionPlanner({
            cropTypes: @json($cropData['types'] ?? []),
            cropVarieties: @json($cropData['varieties'] ?? []),
            availableBeds: @json($availableBeds ?? []),
            farmosBase: '{{ config("services.farmos.url") }}'
        });

        // Initialize the planner
        successionPlanner.initialize().catch(error => {
            console.error('❌ Failed to initialize Succession Planner:', error);
        });

        // Initialize the new harvest window selector
        initializeHarvestWindowSelector();

        // Load saved bed dimensions from localStorage
        loadBedDimensions();

        // Add event listeners to save bed dimensions when changed
        const bedLengthInput = document.getElementById('bedLength');
        const bedWidthInput = document.getElementById('bedWidth');
        const inRowSpacingInput = document.getElementById('inRowSpacing');
        const betweenRowSpacingInput = document.getElementById('betweenRowSpacing');
        
        // Function to update density preset display with current bed width
        function updateDensityPresetDisplay() {
            const bedWidthMeters = parseFloat(bedWidthInput?.value) || 0.75;
            const bedWidthCm = bedWidthMeters * 100; // Convert meters to cm
            const densityBedWidthSpan = document.getElementById('densityBedWidth');
            const preset2rowsLabel = document.getElementById('preset2rowsLabel');
            const preset3rowsLabel = document.getElementById('preset3rowsLabel');
            
            if (densityBedWidthSpan) {
                densityBedWidthSpan.textContent = bedWidthCm;
            }
            
            // Calculate actual rows for each preset
            const rows2 = Math.floor(bedWidthCm / 40) + 1;
            const rows3 = Math.floor(bedWidthCm / 30) + 1;
            
            // Update button labels with calculated row counts
            if (preset2rowsLabel) {
                preset2rowsLabel.textContent = `${rows2} Rows`;
            }
            if (preset3rowsLabel) {
                preset3rowsLabel.textContent = `${rows3} Rows`;
            }
        }
        
        // Function to recalculate and update displayed quantities when inputs change
        function updateDisplayedQuantities() {
            if (!currentSuccessionPlan || !currentSuccessionPlan.plantings) {
                return; // No plan to update
            }

            console.log('🔄 Recalculating plant quantities with updated bed dimensions/spacing...');

            const bedLength = parseFloat(bedLengthInput?.value) || 10;
            const bedWidthMeters = parseFloat(bedWidthInput?.value) || 0.75;
            const bedWidthCm = bedWidthMeters * 100; // Convert meters to cm
            const bedWidth = bedWidthMeters; // Keep as meters
            const inRowSpacing = parseFloat(inRowSpacingInput?.value) || 15;
            const betweenRowSpacing = parseFloat(betweenRowSpacingInput?.value) || 20;

            // Recalculate quantities for all plantings
            currentSuccessionPlan.plantings.forEach(planting => {
                const quantities = calculatePlantQuantity(bedLength, bedWidth, inRowSpacing, betweenRowSpacing, planting.planting_method);
                
                // Update the planting object
                planting.bed_length = bedLength;
                planting.bed_width = bedWidthCm;
                planting.in_row_spacing = inRowSpacing;
                planting.between_row_spacing = betweenRowSpacing;
                planting.number_of_rows = quantities.numberOfRows;
                planting.plants_per_row = quantities.plantsPerRow;
                planting.total_plants = quantities.totalPlants;
                
                // CRITICAL: Also update the form input quantities
                planting.seeding_quantity = quantities.seedingQuantity;
                planting.transplant_quantity = quantities.totalPlants;
                
                console.log(`📊 Updated planting ${planting.succession_number || '?'}: ${quantities.numberOfRows} rows × ${quantities.plantsPerRow} plants = ${quantities.totalPlants} total (seeding: ${quantities.seedingQuantity})`);
            });

            // Re-render the quick form tabs to show updated quantities
            renderQuickFormTabs(currentSuccessionPlan);

            console.log('✅ Plant quantities updated with new dimensions');
        }
        
        if (bedLengthInput) {
            bedLengthInput.addEventListener('change', () => {
                saveBedDimensions();
                updateDisplayedQuantities();
            });
        }
        if (bedWidthInput) {
            bedWidthInput.addEventListener('change', () => {
                saveBedDimensions();
                updateDensityPresetDisplay(); // Update preset display
                updateDisplayedQuantities();
            });
            // Also update on input (real-time as you type)
            bedWidthInput.addEventListener('input', () => {
                updateDensityPresetDisplay();
            });
        }
        if (inRowSpacingInput) {
            inRowSpacingInput.addEventListener('change', updateDisplayedQuantities);
        }
        if (betweenRowSpacingInput) {
            betweenRowSpacingInput.addEventListener('change', updateDisplayedQuantities);
        }
        
        // Add event listeners for varietal succession beds count inputs
        const earlyBedsInput = document.getElementById('earlyBedsCount');
        const midBedsInput = document.getElementById('midBedsCount');
        const lateBedsInput = document.getElementById('lateBedsCount');
        
        if (earlyBedsInput) {
            earlyBedsInput.addEventListener('change', updateVarietalSuccessionSummary);
            earlyBedsInput.addEventListener('input', updateVarietalSuccessionSummary);
        }
        if (midBedsInput) {
            midBedsInput.addEventListener('change', updateVarietalSuccessionSummary);
            midBedsInput.addEventListener('input', updateVarietalSuccessionSummary);
        }
        if (lateBedsInput) {
            lateBedsInput.addEventListener('change', updateVarietalSuccessionSummary);
            lateBedsInput.addEventListener('input', updateVarietalSuccessionSummary);
        }

        // Add event listeners for density preset buttons
        const densityPresetButtons = document.querySelectorAll('.density-preset');
        densityPresetButtons.forEach(button => {
            button.addEventListener('click', function() {
                const rows = this.dataset.rows;
                const betweenRowSpacing = this.dataset.betweenRow;
                
                console.log(`🎯 Density preset clicked: ${betweenRowSpacing}cm between-row spacing`);
                
                // Update the between-row spacing input
                const betweenRowInput = document.getElementById('betweenRowSpacing');
                betweenRowInput.value = betweenRowSpacing;
                
                // Visual feedback
                densityPresetButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Calculate and show preview
                const bedWidthMeters = parseFloat(document.getElementById('bedWidth')?.value) || 0.75;
                const bedWidthCm = bedWidthMeters * 100; // Convert meters to cm
                const actualRows = Math.floor(bedWidthCm / betweenRowSpacing) + 1;
                
                console.log(`🥬 Density preset selected: ${rows} rows (${betweenRowSpacing}cm spacing) = ${actualRows} actual rows on ${bedWidthCm}cm bed`);
                
                // ALWAYS trigger recalculation (even if no plan yet, for when plan is generated)
                console.log('📊 Triggering quantity recalculation...');
                updateDisplayedQuantities();
                
                // Also dispatch change event for any other listeners
                const event = new Event('change');
                betweenRowInput.dispatchEvent(event);
            });
        });

        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    // AI Chat Functions - REMOVED DUPLICATE, using version at line 3170 instead

    function getCurrentPlanContext() {
        // Build context from current succession planning state
        const context = {};

        // Add harvest window info
        if (harvestWindowData.userStart && harvestWindowData.userEnd) {
            context.harvest_window = {
                start: harvestWindowData.userStart,
                end: harvestWindowData.userEnd
            };
        }

        // Add current crop selection
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        if (cropSelect && cropSelect.value) {
            context.crop = cropSelect.options[cropSelect.selectedIndex].text;
        }
        if (varietySelect && varietySelect.value) {
            context.variety = varietySelect.options[varietySelect.selectedIndex].text;
        }

        // Add current succession plan if available
        if (currentSuccessionPlan) {
            context.succession_plan = {
                total_successions: currentSuccessionPlan.total_successions || 0,
                plantings_count: currentSuccessionPlan.plantings ? currentSuccessionPlan.plantings.length : 0
            };
        }

        return context;
    }

    function displayAIResponse(response) {
        const responseArea = document.getElementById('aiResponseArea');
        if (!responseArea) {
            console.warn('⚠️ AI Response Area not found in DOM');
            return;
        }

        // Show the response area
        responseArea.style.display = 'block';

        // Store response for recommendation parsing
        lastAIResponse = response;
        
        // Format the response with proper HTML
        const formattedResponse = response.replace(/\n/g, '<br>');
        
        // Parse to check if there are ACTIONABLE recommendations
        const recommendations = parseAIRecommendations(response);
        
        // Check what types of recommendations we have
        const hasRemove = recommendations.some(r => r.action === 'remove');
        const hasTiming = recommendations.some(r => r.action === 'adjust_timing');
        const hasSpacing = recommendations.some(r => r.action === 'adjust_spacing');
        const hasCompanions = recommendations.some(r => r.action === 'add_companion');
        
        // Only timing and removal are truly actionable (can be applied automatically)
        const hasActionableItems = hasRemove || hasTiming;
        
        // Check for global spacing suggestions (not tied to specific succession)
        const lowerResponse = response.toLowerCase();
        const hasGlobalSpacing = (lowerResponse.includes('spacing') || lowerResponse.includes('row')) && 
                                 (lowerResponse.includes('cm') || lowerResponse.includes('centimeter')) &&
                                 !hasSpacing; // Global if not in specific succession recommendations
        
        // Check if AI says plan is good/solid/adequate
        const isPlanGood = (lowerResponse.includes('appears adequate') || 
                           lowerResponse.includes('looks good') ||
                           lowerResponse.includes('plan is solid') ||
                           lowerResponse.includes('well planned') ||
                           lowerResponse.includes('spacing is adequate')) &&
                          !hasActionableItems;
        
        let actionButtons = '';
        if (hasActionableItems && currentSuccessionPlan) {
            // Count only actionable items
            const actionableCount = recommendations.filter(r => r.action === 'remove' || r.action === 'adjust_timing').length;
            
            // Show Accept button only if there are actionable items
            actionButtons = `
                <div class="mt-3 border-top pt-3">
                    <div class="alert alert-warning mb-2">
                        <i class="fas fa-exclamation-triangle"></i> <strong>${actionableCount} actionable change(s) detected</strong>
                        ${hasRemove ? '<br><small>• Remove successions</small>' : ''}
                        ${hasTiming ? '<br><small>• Adjust timing/dates</small>' : ''}
                    </div>
                    <button class="btn btn-success btn-sm me-2" onclick="acceptAIRecommendations()">
                        <i class="fas fa-check"></i> Accept & Apply Changes
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="modifyRecommendations()">
                        <i class="fas fa-edit"></i> Request Modifications
                    </button>
                </div>
            `;
        } else if ((hasCompanions || hasGlobalSpacing || hasSpacing) && currentSuccessionPlan) {
            // Has suggestions but they're manual (companion plants, spacing tweaks)
            let suggestionTypes = [];
            if (hasCompanions) suggestionTypes.push('🌿 Companion planting');
            if (hasGlobalSpacing || hasSpacing) suggestionTypes.push('📏 Spacing optimization');
            
            actionButtons = `
                <div class="mt-3 border-top pt-3">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-lightbulb"></i> <strong>Suggestions for Consideration</strong><br>
                        <small>${suggestionTypes.join(' • ')}</small><br>
                        <small class="text-muted">These recommendations require manual adjustment and cannot be applied automatically.</small>
                    </div>
                </div>
            `;
        } else if (isPlanGood && currentSuccessionPlan) {
            // Show confirmation message if plan is good
            actionButtons = `
                <div class="mt-3 border-top pt-3">
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle"></i> <strong>Your succession plan looks solid!</strong><br>
                        <small>AI analysis found no critical issues. The recommendations above are informational suggestions for optional improvements.</small>
                    </div>
                </div>
            `;
        } else if (currentSuccessionPlan) {
            // Generic message for informational responses
            actionButtons = `
                <div class="mt-3 border-top pt-3">
                    <div class="alert alert-light mb-0">
                        <i class="fas fa-info-circle"></i> <strong>Informational Advice</strong><br>
                        <small>The suggestions above are for general planning consideration.</small>
                    </div>
                </div>
            `;
        }
        
        responseArea.innerHTML = `
            <div class="ai-response">
                <div class="d-flex align-items-start">
                    <div class="ai-avatar me-2">
                        <i class="fas fa-robot text-primary fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="ai-message p-3 bg-light rounded border">
                            ${formattedResponse}
                        </div>
                        ${actionButtons}
                        <small class="text-muted mt-1 d-block">
                            <i class="fas fa-clock"></i> ${new Date().toLocaleTimeString()}
                        </small>
                    </div>
                </div>
            </div>
        `;
    }

    async function getQuickAdvice() {
        const context = getCurrentPlanContext();
        let prompt = "Provide quick succession planning advice for UK organic vegetable production.";

        if (context.crop) {
            prompt += ` Focus on ${context.crop}`;
            if (context.variety) {
                prompt += ` (${context.variety})`;
            }
        }

        prompt += " Keep it brief and actionable.";

        await askHolisticAI(prompt, 'quick_advice');
    }

    function askQuickQuestion(questionType) {
        const questions = {
            'succession-timing': "What's the optimal timing between successions for continuous harvest?",
            'companion-plants': "What are good companion plants for this crop in a UK climate?",
            'lunar-timing': "How can lunar cycles affect planting timing?",
            'harvest-optimization': "How can I optimize my harvest schedule for market demand?"
        };

        const question = questions[questionType];
        if (question) {
            askHolisticAI(question, questionType);
        }
    }

    // AI Status Functions
    async function checkAIStatus() {
        try {
            const response = await fetch('/admin/farmos/succession-planning/ai-status', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (response.ok && result.success !== undefined) {
                updateAIStatusDisplay(result);
            } else {
                updateAIStatusDisplay({ available: false, message: 'Unable to check AI status' });
            }
        } catch (error) {
            console.error('AI status check failed:', error);
            updateAIStatusDisplay({ available: false, message: 'Connection failed' });
        }
    }

    function updateAIStatusDisplay(status) {
        const statusLight = document.getElementById('aiStatusLight');
        const statusText = document.getElementById('aiStatusText');
        const statusDetails = document.getElementById('aiStatusDetails');

        if (!statusLight || !statusText) return;

        // Check for ai_available from backend response
        const isAvailable = status.ai_available || status.available || false;

        // Update status light
        statusLight.classList.remove('online', 'offline', 'checking');
        if (isAvailable) {
            statusLight.classList.add('online');
        } else {
            statusLight.classList.add('offline');
        }

        // Update status text
        statusText.textContent = isAvailable ? 'AI Service Online' : 'AI Service Offline';

        // Update details
        if (statusDetails) {
            statusDetails.textContent = status.message || '';
        }

        console.log('🤖 AI Status updated:', status);
    }

    async function refreshAIStatus() {
        const statusLight = document.getElementById('aiStatusLight');
        const statusText = document.getElementById('aiStatusText');

        if (statusLight) {
            statusLight.classList.remove('online', 'offline');
            statusLight.classList.add('checking');
        }

        if (statusText) {
            statusText.textContent = 'Checking AI service...';
        }

        await checkAIStatus();
    }

    // Initialize AI status checking
    document.addEventListener('DOMContentLoaded', function() {
        // Check AI status on page load
        checkAIStatus();

        // Set up periodic status checking (every 30 seconds)
        setInterval(checkAIStatus, 30000);

        // Set up event handlers - DISABLED: succession-planner.js handles this now
        // setupSeasonYearHandlers();
        // setupCropVarietyHandlers();
    });

    // ========================================================================
    // CLEAN REBUILT AI CHAT - Simple and reliable
    // ========================================================================
    
    const chatMessages = document.getElementById('chatMessages');
    const chatInput = document.getElementById('chatInput');
    const sendChatBtn = document.getElementById('sendChatBtn');
    
    // Send message on button click
    sendChatBtn.addEventListener('click', sendChatMessage);
    
    // Send message on Enter (but allow Shift+Enter for new lines)
    chatInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendChatMessage();
        }
    });
    
    async function sendChatMessage() {
        const message = chatInput.value.trim();
        if (!message) return;
        
        // Clear input
        chatInput.value = '';
        
        // Add user message to chat
        addMessageToChat('user', message);
        
        // Show loading message
        const loadingId = addMessageToChat('ai', '💭 Thinking...');
        
        try {
            // Gather context from the current succession plan
            const context = {
                has_plan: currentSuccessionPlan && currentSuccessionPlan.plantings && currentSuccessionPlan.plantings.length > 0,
                plan: null
            };
            
            // If we have a plan, include relevant details
            if (context.has_plan) {
                const plan = currentSuccessionPlan;
                context.plan = {
                    variety_name: plan.plantings[0]?.variety_name || 'Unknown',
                    crop_name: plan.plantings[0]?.crop_name || 'Unknown',
                    total_successions: plan.plantings.length,
                    harvest_window_start: plan.harvest_start,
                    harvest_window_end: plan.harvest_end,
                    bed_length: plan.plantings[0]?.bed_length,
                    bed_width: plan.plantings[0]?.bed_width,
                    in_row_spacing: plan.plantings[0]?.in_row_spacing,
                    between_row_spacing: plan.plantings[0]?.between_row_spacing,
                    planting_method: plan.plantings[0]?.planting_method,
                    plantings: plan.plantings.map((p, i) => ({
                        succession_number: i + 1,
                        seeding_date: p.seeding_date,
                        transplant_date: p.transplant_date,
                        harvest_date: p.harvest_date,
                        quantity: p.quantity,
                        bed_name: p.bed_name
                    }))
                };
            }
            
            const response = await fetch('/admin/farmos/succession-planning/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ 
                    question: message,
                    crop_type: context.plan?.crop_name,
                    context: context
                })
            });
            
            const data = await response.json();
            
            // Remove loading message
            document.getElementById(loadingId)?.remove();
            
            if (data.success && data.answer) {
                addMessageToChat('ai', data.answer);
            } else {
                addMessageToChat('ai', '❌ Sorry, I couldn\'t generate a response. Please try again.');
            }
            
        } catch (error) {
            console.error('Chat error:', error);
            document.getElementById(loadingId)?.remove();
            addMessageToChat('ai', '❌ Error: ' + error.message);
        }
    }
    
    function addMessageToChat(sender, message) {
        const msgId = 'msg-' + Date.now();
        const isUser = sender === 'user';
        
        const msgDiv = document.createElement('div');
        msgDiv.id = msgId;
        msgDiv.className = `mb-3 ${isUser ? 'text-end' : ''}`;
        msgDiv.innerHTML = `
            <div class="d-inline-block text-start" style="max-width: 85%;">
                <div class="d-flex align-items-start gap-2 ${isUser ? 'flex-row-reverse' : ''}">
                    <div class="flex-shrink-0">
                        ${isUser ? 
                            '<i class="fas fa-user text-primary"></i>' : 
                            '<span class="text-success fw-bold" style="font-size: 0.9rem;">Symbi<i class="fas fa-robot" style="font-size: 1.1rem;"></i>sis</span>'
                        }
                    </div>
                    <div class="flex-grow-1 p-2 rounded" style="background: ${isUser ? '#e3f2fd' : '#f1f8e9'};">
                        <small class="fw-bold">${isUser ? 'You' : 'AI Advisor'}</small><br>
                        ${message.replace(/\n/g, '<br>')}
                    </div>
                </div>
            </div>
        `;
        
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        return msgId;
    }
    
    // ========================================================================
    // AI RECOMMENDATION ACCEPTANCE
    // ========================================================================
    
    // Store the last AI response for parsing recommendations
    let lastAIResponse = null;
    let lastAIRecommendations = null;
    
    /**
     * Accept AI recommendations and apply them to the succession plan
     */
    function acceptAIRecommendations() {
        if (!currentSuccessionPlan) {
            showToast('No succession plan available to modify', 'warning');
            return;
        }
        
        if (!lastAIResponse) {
            showToast('No AI recommendations to apply', 'warning');
            return;
        }
        
        // Parse AI response for actionable recommendations
        const recommendations = parseAIRecommendations(lastAIResponse);
        lastAIRecommendations = recommendations;
        
        // Build recommendation summary for modal
        let recommendationSummary = '';
        if (recommendations.length > 0) {
            recommendationSummary = '<div class="alert alert-warning small mb-3"><strong>Detected Changes:</strong><ul class="mb-0 mt-2">';
            recommendations.forEach(rec => {
                if (rec.action === 'remove') {
                    recommendationSummary += `<li>❌ Remove succession ${rec.successionNumber}</li>`;
                } else if (rec.action === 'adjust_spacing') {
                    recommendationSummary += `<li>📏 Adjust spacing for succession ${rec.successionNumber}</li>`;
                } else if (rec.action === 'adjust_timing') {
                    if (rec.delayDays !== undefined) {
                        const direction = rec.delayDays > 0 ? 'Delay' : 'Advance';
                        const days = Math.abs(rec.delayDays);
                        const units = days === 7 ? '1 week' : days === 14 ? '2 weeks' : days === 21 ? '3 weeks' : `${days} days`;
                        recommendationSummary += `<li>📅 ${direction} succession ${rec.successionNumber} by ${units}</li>`;
                    } else {
                        recommendationSummary += `<li>⏰ Adjust timing for succession ${rec.successionNumber}</li>`;
                    }
                } else if (rec.action === 'add_companion') {
                    recommendationSummary += `<li>🌿 Add companion: ${rec.companion}</li>`;
                }
            });
            recommendationSummary += '</ul></div>';
        } else {
            recommendationSummary = '<div class="alert alert-info small mb-3"><i class="fas fa-info-circle"></i> No specific structural changes detected. Accepting will mark plan as reviewed.</div>';
        }
        
        // Show confirmation modal
        const confirmModal = `
            <div class="modal fade" id="acceptRecommendationsModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-check-circle"></i> Accept AI Recommendations
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">
                                <strong>Ready to apply AI recommendations?</strong>
                            </p>
                            
                            ${recommendationSummary}
                            
                            <p class="text-muted small mb-3">
                                The AI has analyzed your succession plan using our knowledge base of:
                            </p>
                            <ul class="small text-muted mb-3">
                                <li>39 companion planting relationships</li>
                                <li>22 crop rotation patterns</li>
                                <li>15 UK planting calendar entries</li>
                            </ul>
                            <div class="alert alert-info small mb-0">
                                <i class="fas fa-info-circle"></i>
                                <strong>Note:</strong> Changes will be applied to your current plan. You can review and make further manual adjustments after acceptance.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="button" class="btn btn-success" onclick="confirmAcceptRecommendations()">
                                <i class="fas fa-check"></i> Accept & Apply Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if present
        document.getElementById('acceptRecommendationsModal')?.remove();
        
        // Add modal to page
        document.body.insertAdjacentHTML('beforeend', confirmModal);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('acceptRecommendationsModal'));
        modal.show();
    }
    
    /**
     * Parse AI response text to extract actionable recommendations
     */
    function parseAIRecommendations(responseText) {
        const recommendations = [];
        const text = responseText.toLowerCase();
        
        // Detect remove/skip succession patterns
        const removePatterns = [
            /(?:remove|skip|eliminate|drop)\s+(?:succession\s+)?(\d+)/gi,
            /succession\s+(\d+)\s+(?:should be|could be|can be)?\s*(?:removed|skipped|eliminated|dropped)/gi,
            /(?:not recommended|don't recommend|avoid)\s+succession\s+(\d+)/gi,
            /succession\s+(\d+)\s+(?:is\s+)?(?:not\s+)?(?:necessary|needed|recommended)/gi
        ];
        
        removePatterns.forEach(pattern => {
            let match;
            const regex = new RegExp(pattern);
            while ((match = regex.exec(text)) !== null) {
                const successionNum = parseInt(match[1]);
                if (successionNum && !recommendations.some(r => r.action === 'remove' && r.successionNumber === successionNum)) {
                    recommendations.push({
                        action: 'remove',
                        successionNumber: successionNum,
                        reason: 'AI suggested removal'
                    });
                }
            }
        });
        
        // Detect spacing adjustments
        const spacingPatterns = [
            /(?:increase|widen|expand)\s+spacing.*?succession\s+(\d+)/gi,
            /succession\s+(\d+).*?(?:increase|widen|expand)\s+spacing/gi,
            /(?:reduce|narrow|decrease)\s+spacing.*?succession\s+(\d+)/gi,
            /succession\s+(\d+).*?(?:too\s+)?(?:close|tight|crowded)/gi
        ];
        
        spacingPatterns.forEach(pattern => {
            let match;
            const regex = new RegExp(pattern);
            while ((match = regex.exec(text)) !== null) {
                const successionNum = parseInt(match[1]);
                if (successionNum && !recommendations.some(r => r.successionNumber === successionNum)) {
                    recommendations.push({
                        action: 'adjust_spacing',
                        successionNumber: successionNum,
                        reason: 'AI suggested spacing adjustment'
                    });
                }
            }
        });
        
        // Detect timing adjustments with specific delays
        // Pattern 1: "delay succession X by Y days/weeks"
        const delayWithAmountPattern = /(?:delay|postpone|move back|push back)\s+succession\s+(\d+)\s+by\s+(\d+)\s+(day|week|month)s?/gi;
        let match;
        while ((match = delayWithAmountPattern.exec(text)) !== null) {
            const successionNum = parseInt(match[1]);
            const amount = parseInt(match[2]);
            const unit = match[3];
            
            let days = amount;
            if (unit === 'week') days = amount * 7;
            if (unit === 'month') days = amount * 30;
            
            recommendations.push({
                action: 'adjust_timing',
                successionNumber: successionNum,
                delayDays: days,
                reason: `AI suggested ${amount} ${unit}${amount > 1 ? 's' : ''} delay`
            });
        }
        
        // Pattern 2: "advance succession X by Y days/weeks"
        const advanceWithAmountPattern = /(?:advance|bring forward|move forward|move up)\s+succession\s+(\d+)\s+by\s+(\d+)\s+(day|week|month)s?/gi;
        while ((match = advanceWithAmountPattern.exec(text)) !== null) {
            const successionNum = parseInt(match[1]);
            const amount = parseInt(match[2]);
            const unit = match[3];
            
            let days = amount;
            if (unit === 'week') days = amount * 7;
            if (unit === 'month') days = amount * 30;
            
            recommendations.push({
                action: 'adjust_timing',
                successionNumber: successionNum,
                delayDays: -days, // Negative for advance
                reason: `AI suggested ${amount} ${unit}${amount > 1 ? 's' : ''} advance`
            });
        }
        
        // Pattern 3: Generic timing issues (flag for review if no specific amount)
        const timingPatterns = [
            /succession\s+(\d+).*?(?:too\s+)?(?:early|soon)/gi,
            /succession\s+(\d+).*?(?:too\s+)?late/gi,
            /(?:delay|postpone)\s+succession\s+(\d+)(?!\s+by)/gi, // delay without "by X days"
            /(?:advance|bring forward)\s+succession\s+(\d+)(?!\s+by)/gi
        ];
        
        timingPatterns.forEach(pattern => {
            let match;
            const regex = new RegExp(pattern);
            while ((match = regex.exec(text)) !== null) {
                const successionNum = parseInt(match[1]);
                if (successionNum && !recommendations.some(r => r.successionNumber === successionNum && r.action === 'adjust_timing')) {
                    // Default: suggest 1 week delay for "too early", 1 week advance for "too late"
                    const isTooEarly = /too\s+(?:early|soon)|delay|postpone/i.test(match[0]);
                    const defaultDays = isTooEarly ? 7 : -7;
                    
                    recommendations.push({
                        action: 'adjust_timing',
                        successionNumber: successionNum,
                        delayDays: defaultDays,
                        reason: `AI suggested timing adjustment (default: ${Math.abs(defaultDays)} days ${isTooEarly ? 'delay' : 'advance'})`
                    });
                }
            }
        });
        
        // Detect companion plant suggestions
        const companionPatterns = [
            /(?:plant|add|include|interplant)\s+(\w+)\s+(?:as\s+)?(?:companion|intercrop|between)/gi,
            /(?:companion|intercrop).*?(\w+)/gi
        ];
        
        companionPatterns.forEach(pattern => {
            let match;
            const regex = new RegExp(pattern);
            while ((match = regex.exec(text)) !== null) {
                const companion = match[1];
                if (companion && companion.length > 2 && !recommendations.some(r => r.action === 'add_companion' && r.companion === companion)) {
                    // Filter out common words
                    const excludeWords = ['the', 'and', 'for', 'with', 'that', 'this', 'plant', 'crop', 'succession', 'week', 'weeks', 'day', 'days'];
                    if (!excludeWords.includes(companion.toLowerCase())) {
                        recommendations.push({
                            action: 'add_companion',
                            companion: companion,
                            reason: 'AI suggested companion plant'
                        });
                    }
                }
            }
        });
        
        console.log('📋 Parsed AI Recommendations:', recommendations);
        return recommendations;
    }
    
    /**
     * Confirm acceptance and apply changes to the succession plan
     */
    function confirmAcceptRecommendations() {
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('acceptRecommendationsModal')).hide();
        
        if (!currentSuccessionPlan || !currentSuccessionPlan.plantings) {
            showToast('No plan data to modify', 'error');
            return;
        }
        
        let changesApplied = 0;
        const changeLog = [];
        
        // Apply recommendations
        if (lastAIRecommendations && lastAIRecommendations.length > 0) {
            lastAIRecommendations.forEach(rec => {
                if (rec.action === 'remove') {
                    // Remove the succession from the plan
                    const originalLength = currentSuccessionPlan.plantings.length;
                    currentSuccessionPlan.plantings = currentSuccessionPlan.plantings.filter(p => {
                        return p.succession_number !== rec.successionNumber;
                    });
                    
                    if (currentSuccessionPlan.plantings.length < originalLength) {
                        changesApplied++;
                        changeLog.push(`Removed succession ${rec.successionNumber}`);
                        console.log(`✂️ Removed succession ${rec.successionNumber}`);
                        
                        // Renumber remaining successions
                        currentSuccessionPlan.plantings.forEach((p, index) => {
                            p.succession_number = index + 1;
                        });
                        
                        // Update total count
                        currentSuccessionPlan.total_successions = currentSuccessionPlan.plantings.length;
                    }
                }
                
                // Apply timing adjustments
                if (rec.action === 'adjust_timing' && rec.delayDays !== undefined) {
                    const planting = currentSuccessionPlan.plantings.find(p => p.succession_number === rec.successionNumber);
                    if (planting) {
                        const oldSeedingDate = planting.seeding_date;
                        const oldTransplantDate = planting.transplant_date;
                        const oldHarvestDate = planting.harvest_date;
                        
                        // Adjust seeding date
                        if (planting.seeding_date) {
                            const seedingDate = new Date(planting.seeding_date);
                            seedingDate.setDate(seedingDate.getDate() + rec.delayDays);
                            planting.seeding_date = seedingDate.toISOString().split('T')[0];
                        }
                        
                        // Adjust transplant date (if exists)
                        if (planting.transplant_date) {
                            const transplantDate = new Date(planting.transplant_date);
                            transplantDate.setDate(transplantDate.getDate() + rec.delayDays);
                            planting.transplant_date = transplantDate.toISOString().split('T')[0];
                        }
                        
                        // Adjust harvest dates
                        if (planting.harvest_date) {
                            const harvestDate = new Date(planting.harvest_date);
                            harvestDate.setDate(harvestDate.getDate() + rec.delayDays);
                            planting.harvest_date = harvestDate.toISOString().split('T')[0];
                        }
                        
                        if (planting.harvest_end_date) {
                            const harvestEndDate = new Date(planting.harvest_end_date);
                            harvestEndDate.setDate(harvestEndDate.getDate() + rec.delayDays);
                            planting.harvest_end_date = harvestEndDate.toISOString().split('T')[0];
                        }
                        
                        changesApplied++;
                        const direction = rec.delayDays > 0 ? 'delayed' : 'advanced';
                        const days = Math.abs(rec.delayDays);
                        changeLog.push(`${direction.charAt(0).toUpperCase() + direction.slice(1)} succession ${rec.successionNumber} by ${days} days (${oldSeedingDate} → ${planting.seeding_date})`);
                        console.log(`📅 ${direction.charAt(0).toUpperCase() + direction.slice(1)} succession ${rec.successionNumber}: ${oldSeedingDate} → ${planting.seeding_date}`);
                    }
                }
                
                // Flag spacing adjustments for manual review
                if (rec.action === 'adjust_spacing') {
                    changeLog.push(`Flagged succession ${rec.successionNumber} for spacing review`);
                    const planting = currentSuccessionPlan.plantings.find(p => p.succession_number === rec.successionNumber);
                    if (planting) {
                        planting.ai_spacing_flag = true;
                    }
                }
            });
        }
        
        // Mark plan as AI-approved
        currentSuccessionPlan.ai_approved = true;
        currentSuccessionPlan.ai_approved_at = new Date().toISOString();
        currentSuccessionPlan.ai_changes_applied = changesApplied;
        currentSuccessionPlan.ai_change_log = changeLog;
        
        // Redraw the plan table with updated data
        displaySuccessionPlan(currentSuccessionPlan);
        
        // Add visual indicator
        const responseArea = document.getElementById('aiResponseArea');
        if (responseArea) {
            let changesList = '';
            if (changeLog.length > 0) {
                changesList = '<ul class="mb-0 mt-2">' + changeLog.map(c => `<li>${c}</li>`).join('') + '</ul>';
            }
            
            const acceptedBadge = `
                <div class="alert alert-success mt-3" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <strong>Recommendations Applied!</strong>
                    ${changeLog.length > 0 ? `<br>${changesApplied} change(s) applied to your succession plan:` : 'Plan marked as AI-reviewed.'}
                    ${changesList}
                    <div class="mt-2">
                        <small class="text-muted">You can now proceed to submit plantings to FarmOS or make further adjustments.</small>
                    </div>
                </div>
            `;
            responseArea.insertAdjacentHTML('beforeend', acceptedBadge);
        }
        
        // Log acceptance
        console.log('✅ AI Recommendations applied to plan:', currentSuccessionPlan);
        console.log('📝 Change log:', changeLog);
        
        // Show success message
        if (changesApplied > 0) {
            showToast(`${changesApplied} change(s) applied successfully! Plan updated.`, 'success');
        } else {
            showToast('AI recommendations accepted. Plan marked as reviewed.', 'success');
        }
        
        // Highlight the plan table to show it's been updated
        const planTable = document.querySelector('#successionPlanDisplay table');
        if (planTable) {
            planTable.classList.add('table-success');
            setTimeout(() => {
                planTable.classList.remove('table-success');
            }, 2000);
        }
    }
    
    /**
     * Request modifications to AI recommendations
     */
    function modifyRecommendations() {
        const currentResponse = document.querySelector('#aiResponseArea .ai-message')?.textContent;
        
        const modifyPrompt = `Based on your previous recommendations:\n\n${currentResponse}\n\nI'd like to request the following modifications: `;
        
        // Focus on chat input and pre-fill with modification request
        const chatInput = document.getElementById('chatInput');
        if (chatInput) {
            chatInput.value = modifyPrompt;
            chatInput.focus();
            chatInput.setSelectionRange(modifyPrompt.length, modifyPrompt.length);
            
            // Scroll to chat section
            chatInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            // Fallback: ask for modifications directly
            const modification = prompt('What modifications would you like to the AI recommendations?');
            if (modification) {
                const fullPrompt = `${modifyPrompt}${modification}`;
                askHolisticAI(fullPrompt, 'modification_request');
            }
        }
    }
    
</script>
@endsection