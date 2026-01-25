<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Services\FarmOSApi;

/**
 * Service for generating Gazebo robot simulation worlds from farmOS land assets
 */
class GazeboWorldService
{
    protected $farmOSApi;

    public function __construct(FarmOSApi $farmOSApi)
    {
        $this->farmOSApi = $farmOSApi;
    }

    /**
     * Generate a Gazebo world file from farmOS geometry data
     *
     * @param array $options Configuration options
     * @return string Path to generated world file
     */
    public function generateWorldFromFarmOS(array $options = []): string
    {
        Log::info('Generating Gazebo world from farmOS geometry data');

        // Get geometry data from farmOS
        $geometryData = $this->farmOSApi->getGeometryAssets($options);

        if (empty($geometryData['features'])) {
            Log::warning('No geometry data found in farmOS');
            return $this->generateEmptyWorld();
        }

        // Convert farmOS coordinates to Gazebo coordinate system
        $gazeboFeatures = $this->convertToGazeboCoordinates($geometryData['features']);

        // Generate world SDF content
        $worldContent = $this->generateWorldSDF($gazeboFeatures, $options);

        // Save world file
        $filename = $options['filename'] ?? 'farmos_world.world';
        $path = 'gazebo/worlds/' . $filename;

        Storage::disk('public')->put($path, $worldContent);

        Log::info('Gazebo world generated successfully', ['path' => $path, 'features' => count($gazeboFeatures)]);

        return $path;
    }

    /**
     * Convert farmOS GeoJSON features to Gazebo coordinate system
     * farmOS uses WGS84 lat/lng, Gazebo uses ENU (East North Up) coordinates
     */
    protected function convertToGazeboCoordinates(array $features): array
    {
        $convertedFeatures = [];

        // Use first feature's centroid as origin for local ENU coordinates
        $origin = $this->calculateCentroid($features[0]['geometry']['coordinates'][0][0] ?? []);

        foreach ($features as $feature) {
            $gazeboFeature = $feature;

            if (isset($feature['geometry']['coordinates'])) {
                $coordinates = $feature['geometry']['coordinates'];

                // Handle different geometry types
                if ($feature['geometry']['type'] === 'Polygon') {
                    $gazeboFeature['geometry']['coordinates'] = [$this->convertPolygonCoordinates($coordinates[0], $origin)];
                    $gazeboFeature['geometry']['type'] = 'Polygon';
                } elseif ($feature['geometry']['type'] === 'MultiPolygon') {
                    $convertedCoords = [];
                    foreach ($coordinates as $polygon) {
                        $convertedCoords[] = [$this->convertPolygonCoordinates($polygon[0], $origin)];
                    }
                    $gazeboFeature['geometry']['coordinates'] = $convertedCoords;
                }

                // Add gazebo-specific properties
                $gazeboFeature['gazebo'] = [
                    'height' => 0.1, // Ground height in meters
                    'color' => $this->getLandTypeColor($feature['properties']['land_type'] ?? 'field'),
                    'name' => $feature['properties']['name'] ?? 'unnamed_area'
                ];
            }

            $convertedFeatures[] = $gazeboFeature;
        }

        return $convertedFeatures;
    }

    /**
     * Convert WGS84 coordinates to ENU (East North Up) relative to origin
     */
    protected function convertPolygonCoordinates(array $coordinates, array $origin): array
    {
        $converted = [];

        foreach ($coordinates as $coord) {
            // coord is [lng, lat] or [lng, lat, alt]
            $lng = $coord[0];
            $lat = $coord[1];

            // Convert to ENU coordinates (simplified - for small areas)
            // 1 degree lat/lng ≈ 111,000 meters
            $east = ($lng - $origin[0]) * 111000 * cos(deg2rad($origin[1]));
            $north = ($lat - $origin[1]) * 111000;

            $converted[] = [$east, $north, 0]; // [x, y, z] in gazebo coordinates
        }

        return $converted;
    }

    /**
     * Calculate centroid of a polygon for coordinate system origin
     */
    protected function calculateCentroid(array $coordinates): array
    {
        $sumLng = 0;
        $sumLat = 0;
        $count = count($coordinates);

        foreach ($coordinates as $coord) {
            $sumLng += $coord[0]; // longitude
            $sumLat += $coord[1]; // latitude
        }

        return [$sumLng / $count, $sumLat / $count];
    }

    /**
     * Get color for different land types
     */
    protected function getLandTypeColor(string $landType): string
    {
        $colors = [
            'bed' => '0.8 0.6 0.4 1.0',      // Brown for beds
            'field' => '0.4 0.8 0.4 1.0',    // Green for fields
            'greenhouse' => '0.6 0.8 0.8 1.0', // Light blue for greenhouse
            'orchard' => '0.2 0.6 0.2 1.0',   // Dark green for orchard
            'pasture' => '0.8 0.8 0.4 1.0',   // Yellow for pasture
        ];

        return $colors[$landType] ?? '0.6 0.6 0.6 1.0'; // Default gray
    }

    /**
     * Generate Gazebo SDF world file content
     */
    protected function generateWorldSDF(array $features, array $options = []): string
    {
        $worldName = $options['world_name'] ?? 'FarmOS_World';
        $includeRobot = $options['include_robot'] ?? true;

        $sdf = '<?xml version="1.0"?>' . "\n";
        $sdf .= '<sdf version="1.6">' . "\n";
        $sdf .= '<world name="' . $worldName . '">' . "\n";

        // Basic world properties
        $sdf .= $this->getWorldProperties();

        // Ground plane
        $sdf .= $this->getGroundPlane();

        // Add land features as visual elements
        foreach ($features as $feature) {
            $sdf .= $this->generateLandModel($feature);
        }

        // Add robot if requested
        if ($includeRobot) {
            $sdf .= $this->getRobotModel($options);
        }

        // Lighting
        $sdf .= $this->getLighting();

        $sdf .= '</world>' . "\n";
        $sdf .= '</sdf>' . "\n";

        return $sdf;
    }

    /**
     * Generate world properties
     */
    protected function getWorldProperties(): string
    {
        return '  <physics name="default_physics" default="0" type="ode">
    <gravity>0 0 -9.8066</gravity>
    <ode>
      <solver>
        <type>quick</type>
        <iters>10</iters>
        <sor>1.3</sor>
      </solver>
      <constraints>
        <cfm>0</cfm>
        <erp>0.2</erp>
        <contact_max_correcting_vel>100</contact_max_correcting_vel>
        <contact_surface_layer>0.001</contact_surface_layer>
      </constraints>
    </ode>
  </physics>

  <scene>
    <ambient>0.4 0.4 0.4 1</ambient>
    <background>0.7 0.7 0.7 1</background>
    <shadows>true</shadows>
  </scene>
';
    }

    /**
     * Generate ground plane
     */
    protected function getGroundPlane(): string
    {
        return '  <model name="ground_plane">
    <static>true</static>
    <link name="link">
      <collision name="collision">
        <geometry>
          <plane>
            <normal>0 0 1</normal>
            <size>1000 1000</size>
          </plane>
        </geometry>
        <surface>
          <friction>
            <ode>
              <mu>100</mu>
              <mu2>50</mu2>
            </ode>
          </friction>
        </surface>
      </collision>
      <visual name="visual">
        <geometry>
          <plane>
            <normal>0 0 1</normal>
            <size>1000 1000</size>
          </plane>
        </geometry>
        <material>
          <script>
            <uri>file://media/materials/scripts/gazebo.material</uri>
            <name>Gazebo/Green</name>
          </script>
        </material>
      </visual>
    </link>
  </model>
';
    }

    /**
     * Generate land model from geometry feature
     */
    protected function generateLandModel(array $feature): string
    {
        $name = $feature['gazebo']['name'];
        $color = $feature['gazebo']['color'];
        $height = $feature['gazebo']['height'];

        $model = '  <model name="' . $name . '">' . "\n";
        $model .= '    <static>true</static>' . "\n";
        $model .= '    <link name="link">' . "\n";

        // Generate collision and visual from geometry
        if (isset($feature['geometry']['coordinates'])) {
            $model .= $this->generateGeometryFromCoordinates($feature['geometry']['coordinates'], $height, $color);
        }

        $model .= '    </link>' . "\n";
        $model .= '  </model>' . "\n";

        return $model;
    }

    /**
     * Generate geometry from coordinates
     */
    protected function generateGeometryFromCoordinates(array $coordinates, float $height, string $color): string
    {
        // For simplicity, create a box approximation of the polygon
        // In a full implementation, you'd create proper mesh geometry

        $bounds = $this->calculateBounds($coordinates);
        $centerX = ($bounds['minX'] + $bounds['maxX']) / 2;
        $centerY = ($bounds['minY'] + $bounds['maxY']) / 2;
        $width = $bounds['maxX'] - $bounds['minX'];
        $length = $bounds['maxY'] - $bounds['minY'];

        $geometry = '      <collision name="collision">' . "\n";
        $geometry .= '        <pose>' . $centerX . ' ' . $centerY . ' ' . ($height/2) . ' 0 0 0</pose>' . "\n";
        $geometry .= '        <geometry>' . "\n";
        $geometry .= '          <box>' . "\n";
        $geometry .= '            <size>' . $width . ' ' . $length . ' ' . $height . '</size>' . "\n";
        $geometry .= '          </box>' . "\n";
        $geometry .= '        </geometry>' . "\n";
        $geometry .= '      </collision>' . "\n";

        $geometry .= '      <visual name="visual">' . "\n";
        $geometry .= '        <pose>' . $centerX . ' ' . $centerY . ' ' . ($height/2) . ' 0 0 0</pose>' . "\n";
        $geometry .= '        <geometry>' . "\n";
        $geometry .= '          <box>' . "\n";
        $geometry .= '            <size>' . $width . ' ' . $length . ' ' . $height . '</size>' . "\n";
        $geometry .= '          </box>' . "\n";
        $geometry .= '        </geometry>' . "\n";
        $geometry .= '        <material>' . "\n";
        $geometry .= '          <ambient>' . $color . '</ambient>' . "\n";
        $geometry .= '          <diffuse>' . $color . '</diffuse>' . "\n";
        $geometry .= '        </material>' . "\n";
        $geometry .= '      </visual>' . "\n";

        return $geometry;
    }

    /**
     * Calculate bounds of coordinates
     */
    protected function calculateBounds(array $coordinates): array
    {
        $minX = $minY = PHP_FLOAT_MAX;
        $maxX = $maxY = PHP_FLOAT_MIN;

        foreach ($coordinates as $polygon) {
            foreach ($polygon as $coord) {
                $x = $coord[0];
                $y = $coord[1];

                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }

        return ['minX' => $minX, 'minY' => $minY, 'maxX' => $maxX, 'maxY' => $maxY];
    }

    /**
     * Generate robot model
     */
    protected function getRobotModel(array $options = []): string
    {
        $spawnX = $options['robot_x'] ?? 0;
        $spawnY = $options['robot_y'] ?? 0;
        $spawnZ = $options['robot_z'] ?? 0.5;
        $robotModel = $options['robot_model'] ?? 'turtlebot3_burger';

        return '  <include>
    <uri>model://' . $robotModel . '</uri>
    <name>' . $robotModel . '</name>
    <pose>' . $spawnX . ' ' . $spawnY . ' ' . $spawnZ . ' 0 0 0</pose>
  </include>
';
    }

    /**
     * Generate lighting
     */
    protected function getLighting(): string
    {
        return '  <light name="sun" type="directional">
    <cast_shadows>true</cast_shadows>
    <pose>0 0 10 0 0 0</pose>
    <diffuse>0.8 0.8 0.8 1</diffuse>
    <specular>0.2 0.2 0.2 1</specular>
    <attenuation>
      <range>1000</range>
      <constant>0.9</constant>
      <linear>0.01</linear>
      <quadratic>0.001</quadratic>
    </attenuation>
    <direction>-0.5 0.1 -0.9</direction>
  </light>
';
    }

    /**
     * Generate empty world when no geometry data is available
     */
    protected function generateEmptyWorld(): string
    {
        $worldContent = '<?xml version="1.0"?>
<sdf version="1.6">
  <world name="Empty_FarmOS_World">
    <physics name="default_physics" default="0" type="ode">
      <gravity>0 0 -9.8066</gravity>
    </physics>
    <scene>
      <ambient>0.4 0.4 0.4 1</ambient>
      <background>0.7 0.7 0.7 1</background>
    </scene>
    ' . $this->getGroundPlane() . $this->getLighting() . '
  </world>
</sdf>';

        $path = 'gazebo/worlds/empty_world.world';
        Storage::disk('public')->put($path, $worldContent);

        return $path;
    }

    /**
     * Generate robot navigation waypoints from land assets
     */
    public function generateNavigationWaypoints(array $options = []): array
    {
        $geometryData = $this->farmOSApi->getGeometryAssets($options);

        if (empty($geometryData['features'])) {
            return [];
        }

        $waypoints = [];
        $features = $this->convertToGazeboCoordinates($geometryData['features']);

        foreach ($features as $feature) {
            if (isset($feature['geometry']['coordinates'])) {
                $centroid = $this->calculateCentroid($feature['geometry']['coordinates'][0]);

                $waypoints[] = [
                    'name' => $feature['properties']['name'] ?? 'waypoint_' . count($waypoints),
                    'position' => [$centroid[0], $centroid[1], 0.5],
                    'land_type' => $feature['properties']['land_type'] ?? 'field',
                    'description' => 'Center of ' . ($feature['properties']['name'] ?? 'unnamed area')
                ];
            }
        }

        return $waypoints;
    }

    /**
     * Convert WKT geometry to GeoJSON
     */
    private function convertWktToGeoJson(string $wkt): ?array
    {
        $wkt = trim($wkt);
        if (preg_match('/^POLYGON\s*\(\((.*)\)\)$/i', $wkt, $matches)) {
            $coordinateString = $matches[1];
            $coordinates = $this->parseCoordinateString($coordinateString);

            return [
                'type' => 'Polygon',
                'coordinates' => [$coordinates]
            ];
        }
        return null;
    }

    /**
     * Parse coordinate string into array of [lon, lat] pairs
     */
    private function parseCoordinateString(string $coordinateString): array
    {
        $coordinates = [];
        $pairs = explode(',', $coordinateString);

        foreach ($pairs as $pair) {
            $coords = preg_split('/\s+/', trim($pair));
            if (count($coords) >= 2) {
                $coordinates[] = [(float)$coords[0], (float)$coords[1]];
            }
        }

        return $coordinates;
    }

    /**
     * Get all land assets from farmOS
     */
    public function getAllLandAssets(): array
    {
        try {
            // Try API first
            $assets = $this->farmOSApi->getGeometryAssets(['type' => 'land']);
            if (!isset($assets['error'])) {
                return $assets['features'] ?? [];
            }

            // Fallback to direct database access if API fails
            Log::warning('FarmOS API failed, falling back to direct database access', ['error' => $assets['error']]);
            return $this->getAllLandAssetsFromDatabase();

        } catch (\Exception $e) {
            Log::error('Failed to fetch land assets from farmOS API, falling back to database', ['error' => $e->getMessage()]);
            return $this->getAllLandAssetsFromDatabase();
        }
    }

    /**
     * Get all land assets directly from farmOS database
     */
    private function getAllLandAssetsFromDatabase(): array
    {
        try {
            $landAssets = DB::connection('farmos')
                ->table('asset')
                ->where('type', 'land')
                ->where('status', 'active')
                ->get();

            $features = [];
            foreach ($landAssets as $asset) {
                // Get geometry data
                $geometryData = DB::connection('farmos')
                    ->table('asset__geometry')
                    ->where('entity_id', $asset->id)
                    ->where('deleted', 0)
                    ->first();

                if ($geometryData && $geometryData->geometry_value) {
                    $geometry = $this->convertWktToGeoJson($geometryData->geometry_value);
                    if ($geometry) {
                        $features[] = [
                            'type' => 'Feature',
                            'properties' => [
                                'name' => $asset->name ?? 'Unnamed Area',
                                'id' => $asset->id,
                                'status' => $asset->status ?? 'unknown',
                                'land_type' => $asset->land_type ?? 'field',
                            ],
                            'geometry' => $geometry
                        ];
                    }
                }
            }

            return $features;

        } catch (\Exception $e) {
            Log::error('Failed to fetch land assets from database', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get a specific land asset by ID from farmOS
     */
    public function getLandAssetById(string $id): ?array
    {
        try {
            // Try API first
            $assets = $this->farmOSApi->getGeometryAssets(['type' => 'land', 'id' => $id]);
            if (!isset($assets['error']) && !empty($assets['features'])) {
                return $assets['features'][0];
            }

            // Fallback to direct database access
            Log::warning('FarmOS API failed for specific asset, falling back to database', ['id' => $id, 'error' => $assets['error'] ?? 'unknown']);
            return $this->getLandAssetByIdFromDatabase($id);

        } catch (\Exception $e) {
            Log::error('Failed to fetch land asset from farmOS API, falling back to database', ['id' => $id, 'error' => $e->getMessage()]);
            return $this->getLandAssetByIdFromDatabase($id);
        }
    }

    /**
     * Get a specific land asset by ID from farmOS database
     */
    private function getLandAssetByIdFromDatabase(string $id): ?array
    {
        try {
            $asset = DB::connection('farmos')
                ->table('asset')
                ->where('id', $id)
                ->where('type', 'land')
                ->where('status', 'active')
                ->first();

            if (!$asset) {
                return null;
            }

            // Get geometry data
            $geometryData = DB::connection('farmos')
                ->table('asset__geometry')
                ->where('entity_id', $asset->id)
                ->where('deleted', 0)
                ->first();

            if ($geometryData && $geometryData->geometry_value) {
                $geometry = $this->convertWktToGeoJson($geometryData->geometry_value);
                if ($geometry) {
                    return [
                        'type' => 'Feature',
                        'properties' => [
                            'name' => $asset->name ?? 'Unnamed Area',
                            'id' => $asset->id,
                            'status' => $asset->status ?? 'unknown',
                            'land_type' => $asset->land_type ?? 'field',
                        ],
                        'geometry' => $geometry
                    ];
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Failed to fetch land asset from database', ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate a Gazebo world file from a specific land asset
     */
    public function generateWorldFromLandAsset(array $landAsset, string $outputDir, string $robotModel = 'turtlebot3_waffle', bool $includeNavigation = false): ?string
    {
        try {
            // Convert single asset to features array
            $features = [$landAsset];

            // Convert coordinates
            $gazeboFeatures = $this->convertToGazeboCoordinates($features);

            // Generate world options
            $options = [
                'world_name' => 'Land_' . ($landAsset['properties']['name'] ?? $landAsset['id'] ?? 'Unknown'),
                'include_robot' => true,
                'robot_model' => $robotModel,
                'include_navigation' => $includeNavigation
            ];

            // Generate SDF content
            $worldContent = $this->generateWorldSDF($gazeboFeatures, $options);

            // Generate filename
            $safeName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $landAsset['properties']['name'] ?? $landAsset['id'] ?? 'land');
            $filename = $safeName . '.world';
            $filePath = $outputDir . '/' . $filename;

            // Save file
            if (file_put_contents($filePath, $worldContent) === false) {
                Log::error('Failed to write world file', ['path' => $filePath]);
                return null;
            }

            Log::info('Gazebo world generated for land asset', [
                'asset_id' => $landAsset['id'] ?? 'unknown',
                'asset_name' => $landAsset['properties']['name'] ?? 'unknown',
                'file_path' => $filePath
            ]);

            return $filePath;

        } catch (\Exception $e) {
            Log::error('Failed to generate world from land asset', [
                'asset_id' => $landAsset['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generate a sample world with mock farmOS data for testing
     */
    public function generateSampleWorld(string $outputDir = null): string
    {
        $outputDir = $outputDir ?: storage_path('app/gazebo-worlds');

        // Create mock land assets data
        $mockFeatures = [
            [
                'type' => 'Feature',
                'properties' => [
                    'name' => 'North Field',
                    'id' => 'sample_1',
                    'status' => 'active',
                    'land_type' => 'field',
                ],
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [-10, -10, 0],
                        [10, -10, 0],
                        [10, 10, 0],
                        [-10, 10, 0],
                        [-10, -10, 0]
                    ]]
                ]
            ],
            [
                'type' => 'Feature',
                'properties' => [
                    'name' => 'Greenhouse Area',
                    'id' => 'sample_2',
                    'status' => 'active',
                    'land_type' => 'greenhouse',
                ],
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [15, -5, 0],
                        [25, -5, 0],
                        [25, 5, 0],
                        [15, 5, 0],
                        [15, -5, 0]
                    ]]
                ]
            ],
            [
                'type' => 'Feature',
                'properties' => [
                    'name' => 'Orchard Block',
                    'id' => 'sample_3',
                    'status' => 'active',
                    'land_type' => 'orchard',
                ],
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [-5, 15, 0],
                        [5, 15, 0],
                        [5, 25, 0],
                        [-5, 25, 0],
                        [-5, 15, 0]
                    ]]
                ]
            ]
        ];

        // Convert to gazebo coordinates (mock features are already in ENU)
        $gazeboFeatures = [];
        foreach ($mockFeatures as $feature) {
            $gazeboFeature = $feature;
            $gazeboFeature['gazebo'] = [
                'height' => 0.1,
                'color' => $this->getLandTypeColor($feature['properties']['land_type']),
                'name' => $feature['properties']['name']
            ];
            $gazeboFeatures[] = $gazeboFeature;
        }

        // Generate world
        $worldContent = $this->generateWorldSDF($gazeboFeatures, [
            'world_name' => 'Sample_FarmOS_World',
            'include_robot' => true,
            'robot_model' => 'turtlebot3_waffle'
        ]);

        $filename = 'sample_farm_world.world';
        $filePath = $outputDir . '/' . $filename;

        if (file_put_contents($filePath, $worldContent) === false) {
            throw new \Exception('Failed to write sample world file');
        }

        return $filePath;
    }
}