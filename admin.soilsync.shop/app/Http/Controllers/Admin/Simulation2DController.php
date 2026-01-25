<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Simulation\Simulation2DService;
use App\Services\Simulation\Robot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class Simulation2DController extends Controller
{
    public function index()
    {
        return view('admin.simulation-2d.index');
    }

    public function initialize(Request $request): JsonResponse
    {
        $simulation = new Simulation2DService();

        // Load world from farmOS or create simple world
        if ($request->has('use_farmos') && $request->use_farmos) {
            $simulation->loadFarmOSWorld($request->farm_id);
            $mapCenter = $simulation->getMapCenter();
        } else {
            $simulation->createSimpleWorld();
            $mapCenter = ['lat' => 52.2053, 'lng' => 0.1218]; // Cambridge, UK default
        }

        // Add robots
        $robotCount = $request->robot_count ?? 3;
        for ($i = 0; $i < $robotCount; $i++) {
            $robot = new Robot(
                rand(5, 45), // x
                rand(5, 45), // y
                rand(0, 359) * M_PI / 180 // theta in radians
            );
            $simulation->addRobot($robot);
        }

        // Seed world with entities
        $seeds = $request->seeds ?? $this->getDefaultSeeds();
        $simulation->seedWorld($seeds);

        // Store simulation in session
        session(['simulation_2d' => serialize($simulation)]);

        return response()->json([
            'status' => 'initialized',
            'state' => $simulation->getState(),
            'map_center' => $mapCenter
        ]);
    }

    public function step(): JsonResponse
    {
        $simulation = $this->getSimulationFromSession();
        if (!$simulation) {
            return response()->json(['error' => 'No simulation initialized'], 400);
        }

        $simulation->step();

        // Update session
        session(['simulation_2d' => serialize($simulation)]);

        return response()->json([
            'state' => $simulation->getState()
        ]);
    }

    public function command(Request $request): JsonResponse
    {
        $simulation = $this->getSimulationFromSession();
        if (!$simulation) {
            return response()->json(['error' => 'No simulation initialized'], 400);
        }

        $robotIndex = $request->robot_index ?? 0;
        $command = $request->command;

        $robot = $simulation->getRobot($robotIndex);

        if (!$robot) {
            return response()->json(['error' => 'Robot not found'], 404);
        }

        switch ($command['type']) {
            case 'cmd_vel':
                $robot->setCmdVel($command['linear'], $command['angular']);
                break;
            case 'follow_pheromone':
                $robot->followPheromoneGradient($simulation);
                break;
            case 'stop':
                $robot->setWheelSpeeds(0, 0);
                break;
        }

        // Update session
        session(['simulation_2d' => serialize($simulation)]);

        return response()->json(['status' => 'command_applied']);
    }

    public function getState(): JsonResponse
    {
        $simulation = $this->getSimulationFromSession();
        if (!$simulation) {
            return response()->json(['error' => 'No simulation initialized'], 400);
        }

        return response()->json([
            'state' => $simulation->getState()
        ]);
    }

    private function getSimulationFromSession()
    {
        $serialized = session('simulation_2d');
        return $serialized ? unserialize($serialized) : null;
    }

    private function getDefaultSeeds()
    {
        return [
            ['type' => 'weed', 'x' => 10, 'y' => 10, 'attributes' => ['size' => 'small']],
            ['type' => 'weed', 'x' => 40, 'y' => 15, 'attributes' => ['size' => 'large']],
            ['type' => 'crop', 'x' => 25, 'y' => 25, 'attributes' => ['variety' => 'lettuce']],
            ['type' => 'crop', 'x' => 35, 'y' => 30, 'attributes' => ['variety' => 'carrot']],
            ['type' => 'target', 'x' => 20, 'y' => 40, 'attributes' => ['priority' => 'high']],
        ];
    }
}