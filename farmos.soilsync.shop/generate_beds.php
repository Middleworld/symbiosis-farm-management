<?php

/**
 * Bed Generator Script for farmOS 3.x
 * 
 * Usage: drush php:script generate_beds.php
 * 
 * This script generates bed land assets within a parent block.
 */

if (!class_exists('Drupal')) {
    fwrite(STDERR, "\n❌ ERROR: Run with: ./vendor/bin/drush php:script generate_beds.php\n\n");
    exit(1);
}

use Drupal\asset\Entity\Asset;
use Drupal\geofield\WktGenerator;

echo "🌱 farmOS Bed Generator\n";
echo "========================\n\n";

// Configuration - EDIT THESE VALUES
$parent_block_id = 630;  // Block A ID
$bed_prefix = "A";       // Bed naming prefix (e.g., "A" for A/1, A/2, etc.)
$bed_width = 0.75;       // Bed width in meters (75cm)
$bed_length = 10;        // Bed length in meters (will be overridden by block length)
$path_width = 0.45;      // Path width between beds in meters (45cm)
$beds_per_row = 1;       // Number of beds side-by-side (1 = single column)
$start_number = 1;       // Starting bed number
$auto_calculate = true;  // Auto-calculate bed count and length from block dimensions
$start_offset_x = 2.0;   // Offset from starting edge in meters
$start_offset_y = 2.0;   // Offset along perpendicular edge in meters

echo "Configuration:\n";
echo "  Parent Block ID: $parent_block_id\n";
echo "  Bed Prefix: $bed_prefix\n";
echo "  Count: $bed_count beds\n";
echo "  Size: {$bed_width}m × {$bed_length}m\n";
echo "  Path Width: {$path_width}m\n";
echo "  Layout: $beds_per_row bed(s) per row\n\n";

// Load parent block to get its geometry
$parent = Asset::load($parent_block_id);
if (!$parent) {
    echo "❌ Error: Parent block $parent_block_id not found!\n";
    exit(1);
}

// Get parent geometry to position beds within it
$parent_geometry = $parent->get('intrinsic_geometry')->value;
if (!$parent_geometry) {
    echo "⚠️  Warning: Parent block has no geometry - beds will be created without geometry\n";
    $create_geometry = false;
} else {
    echo "✅ Parent block geometry found\n";
    $create_geometry = true;
    
    // Parse WKT polygon to extract all coordinates
    if (preg_match('/POLYGON\s*\(\((.*?)\)\)/', $parent_geometry, $matches)) {
        $coords_string = $matches[1];
        $coord_pairs = explode(',', $coords_string);
        $coordinates = [];
        
        foreach ($coord_pairs as $pair) {
            $parts = preg_split('/\s+/', trim($pair));
            if (count($parts) >= 2) {
                $coordinates[] = ['lon' => floatval($parts[0]), 'lat' => floatval($parts[1])];
            }
        }
        
        if (count($coordinates) >= 2) {
            $start_lon = $coordinates[0]['lon'];
            $start_lat = $coordinates[0]['lat'];
            
            // Calculate angle from first edge (corner 0 to corner 1)
            $dx = $coordinates[1]['lon'] - $coordinates[0]['lon'];
            $dy = $coordinates[1]['lat'] - $coordinates[0]['lat'];
            $angle_rad = atan2($dy, $dx);
            
            // Rotate 90 degrees to make beds perpendicular to first edge
            $angle_rad += M_PI / 2;
            $angle_deg = rad2deg($angle_rad);
            
            // Calculate block dimensions
            $meters_per_degree_lat = 111320;
            $meters_per_degree_lon = 111320 * cos(deg2rad($start_lat));
            
            // Calculate width (distance between first edge: corner 0 to corner 1)
            $block_width = sqrt(
                pow(($coordinates[1]['lon'] - $coordinates[0]['lon']) * $meters_per_degree_lon, 2) +
                pow(($coordinates[1]['lat'] - $coordinates[0]['lat']) * $meters_per_degree_lat, 2)
            );
            
            // Calculate length (distance between perpendicular edge: corner 1 to corner 2)
            if (count($coordinates) >= 3) {
                $block_length = sqrt(
                    pow(($coordinates[2]['lon'] - $coordinates[1]['lon']) * $meters_per_degree_lon, 2) +
                    pow(($coordinates[2]['lat'] - $coordinates[1]['lat']) * $meters_per_degree_lat, 2)
                );
            } else {
                $block_length = $bed_length; // Fallback
            }
            
            echo "  Starting coordinates: $start_lon, $start_lat\n";
            echo "  Block orientation: " . round($angle_deg, 1) . "° from east\n";
            echo "  Block dimensions: " . round($block_width, 2) . "m × " . round($block_length, 2) . "m\n";
            
            // Auto-calculate bed count and length if enabled
            if ($auto_calculate) {
                // Calculate how many beds fit across the width
                $bed_count = floor($block_width / ($bed_width + $path_width));
                // Use full block length for bed length (minus small buffer)
                $bed_length = $block_length - 0.5; // 0.5m buffer at ends
                
                echo "  Auto-calculated: $bed_count beds of {$bed_width}m × " . round($bed_length, 2) . "m\n";
            }
        } else {
            echo "⚠️  Warning: Could not parse polygon coordinates\n";
            $start_lon = 0;
            $start_lat = 0;
            $angle_rad = 0;
            $bed_count = 20; // Fallback
            $create_geometry = false;
        }
    } else {
        echo "⚠️  Warning: Could not parse parent geometry - beds will be positioned at origin\n";
        $start_lon = 0;
        $start_lat = 0;
        $angle_rad = 0;
        $bed_count = 20; // Fallback
        $create_geometry = false;
    }
}

echo "\nGenerating beds...\n\n";

$created_beds = [];

for ($i = 0; $i < $bed_count; $i++) {
    $bed_number = $start_number + $i;
    $bed_name = "$bed_prefix/$bed_number";
    
    // Calculate position (configurable rows)
    $row = intdiv($i, $beds_per_row);
    $col = $i % $beds_per_row;
    
    if ($create_geometry) {
        // Calculate bed position offset from parent's starting point
        // Using approximate meters to degrees conversion (1 degree ≈ 111km at equator)
        $meters_per_degree_lat = 111320;
        $meters_per_degree_lon = 111320 * cos(deg2rad($start_lat));
        
        // Layout beds side-by-side (beds parallel to each other)
        // Move along width for each new bed (row), length stays constant direction
        $offset_x = $row * ($bed_width + $path_width);  // Each bed moves sideways by width
        $offset_y = $col * ($bed_length + $path_width); // Columns would shift lengthwise (but col=0 for single column)
        
        // Rotate offset by block's angle
        $offset_x_rotated = $offset_x * cos($angle_rad) - $offset_y * sin($angle_rad);
        $offset_y_rotated = $offset_x * sin($angle_rad) + $offset_y * cos($angle_rad);
        
        // Add starting offsets
        $start_offset_x_rotated = $start_offset_x * cos($angle_rad) - $start_offset_y * sin($angle_rad);
        $start_offset_y_rotated = $start_offset_x * sin($angle_rad) + $start_offset_y * cos($angle_rad);
        
        $bed_lon = $start_lon + (($offset_x_rotated + $start_offset_x_rotated) / $meters_per_degree_lon);
        $bed_lat = $start_lat + (($offset_y_rotated + $start_offset_y_rotated) / $meters_per_degree_lat);
        
        $width_deg = $bed_width / $meters_per_degree_lon;
        $length_deg = $bed_length / $meters_per_degree_lat;
        
        // Create bed corners, rotated to match block orientation
        // Start at bed origin, create rectangle, then rotate each corner
        $corners = [
            ['x' => 0, 'y' => 0],
            ['x' => $width_deg, 'y' => 0],
            ['x' => $width_deg, 'y' => $length_deg],
            ['x' => 0, 'y' => $length_deg],
            ['x' => 0, 'y' => 0], // Close polygon
        ];
        
        $rotated_corners = [];
        foreach ($corners as $corner) {
            $x_rot = $corner['x'] * cos($angle_rad) - $corner['y'] * sin($angle_rad);
            $y_rot = $corner['x'] * sin($angle_rad) + $corner['y'] * cos($angle_rad);
            $rotated_corners[] = sprintf("%.8f %.8f", $bed_lon + $x_rot, $bed_lat + $y_rot);
        }
        
        // Create rotated rectangle WKT for bed
        $wkt = "POLYGON((" . implode(',', $rotated_corners) . "))";
    } else {
        $wkt = null;
    }
    
    // Create bed asset
    $bed = Asset::create([
        'type' => 'land',
        'land_type' => 'bed',
        'name' => $bed_name,
        'status' => 'active',
        'parent' => ['target_id' => $parent_block_id],
        'intrinsic_geometry' => $wkt,
        'is_location' => true,
        'is_fixed' => true,
    ]);
    
    $bed->save();
    $created_beds[] = $bed_name;
    
    echo "  ✅ Created bed: $bed_name (ID: {$bed->id()})\n";
}

echo "\n🎯 Success! Created " . count($created_beds) . " beds in Block $bed_prefix\n";
echo "\nBeds created:\n";
foreach ($created_beds as $bed_name) {
    echo "  - $bed_name\n";
}

echo "\n💡 To generate beds for Block B, edit the script and change:\n";
echo "   \$parent_block_id = 631\n";
echo "   \$bed_prefix = \"B\"\n";
echo "   \$bed_count = [number of beds for Block B]\n\n";
