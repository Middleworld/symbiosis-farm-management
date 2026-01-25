<?php

namespace App\Services\Simulation;

use App\Services\FarmOSApi;
use Illuminate\Support\Facades\DB;

class Simulation2DService
{
    private $environment;
    private $robots = [];
    private $pheromoneGrid = [];
    private $timeStep = 0.1; // seconds
    private $gridSize = 0.1; // meters per cell
    private $worldWidth = 50; // meters
    private $worldHeight = 50; // meters

    public function __construct()
    {
        $this->initializeEnvironment();
        $this->initializePheromoneGrid();
    }

    private function initializeEnvironment()
    {
        $this->environment = [
            'walls' => [],
            'obstacles' => [],
            'entities' => [] // weeds, crops, targets
        ];
    }

    private function initializePheromoneGrid()
    {
        $gridWidth = ceil($this->worldWidth / $this->gridSize);
        $gridHeight = ceil($this->worldHeight / $this->gridSize);

        $this->pheromoneGrid = array_fill(0, $gridHeight,
            array_fill(0, $gridWidth, 0.0)
        );
    }

    public function addRobot($robot)
    {
        $this->robots[] = $robot;
    }

    public function addWall($wall)
    {
        $this->environment['walls'][] = $wall;
    }

    public function addObstacle($obstacle)
    {
        $this->environment['obstacles'][] = $obstacle;
    }

    public function addEntity($entity)
    {
        $this->environment['entities'][] = $entity;
    }

    public function step()
    {
        // Update robots
        foreach ($this->robots as $robot) {
            $robot->update($this->timeStep, $this);
        }

        // Update pheromone grid (diffusion/decay)
        $this->updatePheromoneGrid();

        // Handle communications
        $this->processCommunications();
    }

    private function updatePheromoneGrid()
    {
        $decayRate = 0.99;
        $diffusionRate = 0.1;

        // Simple decay
        for ($y = 0; $y < count($this->pheromoneGrid); $y++) {
            for ($x = 0; $x < count($this->pheromoneGrid[0]); $x++) {
                $this->pheromoneGrid[$y][$x] *= $decayRate;
            }
        }

        // Simple diffusion (box blur)
        $newGrid = $this->pheromoneGrid;
        for ($y = 1; $y < count($this->pheromoneGrid) - 1; $y++) {
            for ($x = 1; $x < count($this->pheromoneGrid[0]) - 1; $x++) {
                $sum = 0;
                for ($dy = -1; $dy <= 1; $dy++) {
                    for ($dx = -1; $dx <= 1; $dx++) {
                        $sum += $this->pheromoneGrid[$y + $dy][$x + $dx];
                    }
                }
                $newGrid[$y][$x] = $sum / 9 * (1 - $diffusionRate) + $this->pheromoneGrid[$y][$x] * $diffusionRate;
            }
        }
        $this->pheromoneGrid = $newGrid;
    }

    private function processCommunications()
    {
        foreach ($this->robots as $robot1) {
            foreach ($this->robots as $robot2) {
                if ($robot1 === $robot2) continue;

                $distance = sqrt(
                    pow($robot1->x - $robot2->x, 2) +
                    pow($robot1->y - $robot2->y, 2)
                );

                if ($distance <= $robot1->commRadius) {
                    $robot1->receiveMessage($robot2->broadcastMessage());
                    $robot2->receiveMessage($robot1->broadcastMessage());
                }
            }
        }
    }

    public function depositPheromone($x, $y, $amount)
    {
        $gridX = floor($x / $this->gridSize);
        $gridY = floor($y / $this->gridSize);

        if ($gridX >= 0 && $gridX < count($this->pheromoneGrid[0]) &&
            $gridY >= 0 && $gridY < count($this->pheromoneGrid)) {
            $this->pheromoneGrid[$gridY][$gridX] += $amount;
        }
    }

    public function getPheromoneAt($x, $y)
    {
        $gridX = floor($x / $this->gridSize);
        $gridY = floor($y / $this->gridSize);

        if ($gridX >= 0 && $gridX < count($this->pheromoneGrid[0]) &&
            $gridY >= 0 && $gridY < count($this->pheromoneGrid)) {
            return $this->pheromoneGrid[$gridY][$gridX];
        }
        return 0.0;
    }

    public function checkCollision($x, $y, $radius)
    {
        // Check walls
        foreach ($this->environment['walls'] as $wall) {
            if ($this->circleLineCollision($x, $y, $radius, $wall)) {
                return true;
            }
        }

        // Check obstacles
        foreach ($this->environment['obstacles'] as $obstacle) {
            $distance = sqrt(pow($x - $obstacle['x'], 2) + pow($y - $obstacle['y'], 2));
            if ($distance < $radius + $obstacle['radius']) {
                return true;
            }
        }

        return false;
    }

    private function circleLineCollision($cx, $cy, $r, $line)
    {
        // Line segment collision with circle
        $x1 = $line['x1']; $y1 = $line['y1'];
        $x2 = $line['x2']; $y2 = $line['y2'];

        $dx = $x2 - $x1;
        $dy = $y2 - $y1;
        $length = sqrt($dx * $dx + $dy * $dy);

        if ($length == 0) return false;

        $ux = $dx / $length;
        $uy = $dy / $length;

        $vx = $cx - $x1;
        $vy = $cy - $y1;

        $t = max(0, min($length, $vx * $ux + $vy * $uy));

        $px = $x1 + $t * $ux;
        $py = $y1 + $t * $uy;

        $distance = sqrt(pow($cx - $px, 2) + pow($cy - $py, 2));

        return $distance <= $r;
    }

    public function raycast($startX, $startY, $angle, $maxDistance)
    {
        $endX = $startX + cos($angle) * $maxDistance;
        $endY = $startY + sin($angle) * $maxDistance;

        $minDistance = $maxDistance;

        // Check walls
        foreach ($this->environment['walls'] as $wall) {
            $intersection = $this->lineLineIntersection(
                $startX, $startY, $endX, $endY,
                $wall['x1'], $wall['y1'], $wall['x2'], $wall['y2']
            );

            if ($intersection) {
                $distance = sqrt(pow($intersection['x'] - $startX, 2) + pow($intersection['y'] - $startY, 2));
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                }
            }
        }

        // Check obstacles
        foreach ($this->environment['obstacles'] as $obstacle) {
            $intersection = $this->lineCircleIntersection(
                $startX, $startY, $endX, $endY,
                $obstacle['x'], $obstacle['y'], $obstacle['radius']
            );

            if ($intersection) {
                foreach ($intersection as $point) {
                    $distance = sqrt(pow($point['x'] - $startX, 2) + pow($point['y'] - $startY, 2));
                    if ($distance < $minDistance) {
                        $minDistance = $distance;
                    }
                }
            }
        }

        return $minDistance;
    }

    private function lineLineIntersection($x1, $y1, $x2, $y2, $x3, $y3, $x4, $y4)
    {
        $denom = ($x1 - $x2) * ($y3 - $y4) - ($y1 - $y2) * ($x3 - $x4);
        if (abs($denom) < 1e-6) return null;

        $t = (($x1 - $x3) * ($y3 - $y4) - ($y1 - $y3) * ($x3 - $x4)) / $denom;
        $u = (($x1 - $x3) * ($y1 - $y2) - ($y1 - $y3) * ($x1 - $x2)) / $denom;

        if ($t >= 0 && $t <= 1 && $u >= 0 && $u <= 1) {
            return [
                'x' => $x1 + $t * ($x2 - $x1),
                'y' => $y1 + $t * ($y2 - $y1)
            ];
        }

        return null;
    }

    private function lineCircleIntersection($x1, $y1, $x2, $y2, $cx, $cy, $r)
    {
        $dx = $x2 - $x1;
        $dy = $y2 - $y1;
        $fx = $x1 - $cx;
        $fy = $y1 - $cy;

        $a = $dx * $dx + $dy * $dy;
        $b = 2 * ($fx * $dx + $fy * $dy);
        $c = $fx * $fx + $fy * $fy - $r * $r;

        $discriminant = $b * $b - 4 * $a * $c;
        if ($discriminant < 0) return null;

        $discriminant = sqrt($discriminant);
        $t1 = (-$b - $discriminant) / (2 * $a);
        $t2 = (-$b + $discriminant) / (2 * $a);

        $intersections = [];
        if ($t1 >= 0 && $t1 <= 1) {
            $intersections[] = [
                'x' => $x1 + $t1 * $dx,
                'y' => $y1 + $t1 * $dy
            ];
        }
        if ($t2 >= 0 && $t2 <= 1) {
            $intersections[] = [
                'x' => $x1 + $t2 * $dx,
                'y' => $y1 + $t2 * $dy
            ];
        }

        return $intersections;
    }

    public function getState()
    {
        return [
            'robots' => array_map(fn($robot) => $robot->getState(), $this->robots),
            'environment' => $this->environment,
            'pheromoneGrid' => $this->pheromoneGrid,
            'worldWidth' => $this->worldWidth,
            'worldHeight' => $this->worldHeight
        ];
    }

    public function loadFarmOSWorld($farmId = null)
    {
        // Load land assets from farmOS as walls/obstacles
        try {
            $assets = DB::connection('farmos')->table('asset')
                ->where('type', 'land')
                ->get();

            $allCoords = [];

            foreach ($assets as $asset) {
                // Parse geometry if available
                $geometry = DB::connection('farmos')
                    ->table('asset__intrinsic_geometry')
                    ->where('entity_id', $asset->id)
                    ->first();

                if ($geometry && $geometry->intrinsic_geometry_value) {
                    $coords = $this->parseGeometry($geometry->intrinsic_geometry_value);
                    $allCoords = array_merge($allCoords, $coords);
                }
            }

            // Calculate bounds and create coordinate transformation
            if (!empty($allCoords)) {
                $this->createCoordinateTransform($allCoords);
            }

        } catch (\Exception $e) {
            // Fallback to simple world
            $this->createSimpleWorld();
        }
    }

    private function createCoordinateTransform($coords)
    {
        // Find bounds
        $minLng = min(array_column($coords, 'lng'));
        $maxLng = max(array_column($coords, 'lng'));
        $minLat = min(array_column($coords, 'lat'));
        $maxLat = max(array_column($coords, 'lat'));

        // Store transform parameters
        $this->geoBounds = [
            'minLng' => $minLng,
            'maxLng' => $maxLng,
            'minLat' => $minLat,
            'maxLat' => $maxLat,
            'centerLng' => ($minLng + $maxLng) / 2,
            'centerLat' => ($minLat + $maxLat) / 2
        ];

        // Approximate meters per degree (rough estimate)
        $this->metersPerLng = 111320 * cos(deg2rad($this->geoBounds['centerLat']));
        $this->metersPerLat = 111320;
    }

    private function geoToSimulation($lat, $lng)
    {
        if (!isset($this->geoBounds)) {
            // Fallback: treat as meters
            return ['x' => $lng, 'y' => $lat];
        }

        // Convert to meters from center
        $x = ($lng - $this->geoBounds['centerLng']) * $this->metersPerLng;
        $y = ($lat - $this->geoBounds['centerLat']) * $this->metersPerLat;

        // Scale to fit simulation world (0-50 meters)
        $scale = 50 / max(
            abs($this->geoBounds['maxLng'] - $this->geoBounds['minLng']) * $this->metersPerLng,
            abs($this->geoBounds['maxLat'] - $this->geoBounds['minLat']) * $this->metersPerLat
        );

        return [
            'x' => $x * $scale + 25, // Center in 50x50 world
            'y' => $y * $scale + 25
        ];
    }

    private function parseGeometry($wkt)
    {
        // Parse WKT geometry and convert to simulation objects
        $coords = [];

        if (preg_match('/POLYGON\s*\(\(([^)]+)\)\)/i', $wkt, $matches)) {
            $coordStrings = explode(',', $matches[1]);
            $points = [];

            foreach ($coordStrings as $coordString) {
                $parts = explode(' ', trim($coordString));
                if (count($parts) >= 2) {
                    $lng = floatval($parts[0]);
                    $lat = floatval($parts[1]);
                    $points[] = ['lat' => $lat, 'lng' => $lng];
                    $coords[] = ['lat' => $lat, 'lng' => $lng];
                }
            }

            // Convert to walls
            for ($i = 0; $i < count($points) - 1; $i++) {
                $p1 = $this->geoToSimulation($points[$i]['lat'], $points[$i]['lng']);
                $p2 = $this->geoToSimulation($points[$i + 1]['lat'], $points[$i + 1]['lng']);

                $this->addWall([
                    'x1' => $p1['x'],
                    'y1' => $p1['y'],
                    'x2' => $p2['x'],
                    'y2' => $p2['y']
                ]);
            }
        }

        return $coords;
    }

    public function createSimpleWorld()
    {
        // Create a simple rectangular world with some obstacles
        $this->addWall(['x1' => 0, 'y1' => 0, 'x2' => $this->worldWidth, 'y2' => 0]);
        $this->addWall(['x1' => $this->worldWidth, 'y1' => 0, 'x2' => $this->worldWidth, 'y2' => $this->worldHeight]);
        $this->addWall(['x1' => $this->worldWidth, 'y1' => $this->worldHeight, 'x2' => 0, 'y2' => $this->worldHeight]);
        $this->addWall(['x1' => 0, 'y1' => $this->worldHeight, 'x2' => 0, 'y2' => 0]);

        // Add some obstacles
        $this->addObstacle(['x' => 10, 'y' => 10, 'radius' => 2]);
        $this->addObstacle(['x' => 30, 'y' => 20, 'radius' => 1.5]);
        $this->addObstacle(['x' => 15, 'y' => 35, 'radius' => 3]);
    }

    public function seedWorld($seeds)
    {
        foreach ($seeds as $seed) {
            $this->addEntity($seed);
        }
    }

    public function getMapCenter()
    {
        if (isset($this->geoBounds)) {
            return [
                'lat' => $this->geoBounds['centerLat'],
                'lng' => $this->geoBounds['centerLng']
            ];
        }
        return ['lat' => 52.2053, 'lng' => 0.1218]; // Default Cambridge
    }

    public function getRobots()
    {
        return $this->robots;
    }

    public function getRobot($index)
    {
        return $this->robots[$index] ?? null;
    }
}