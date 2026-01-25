<?php

namespace App\Services\Simulation;

class Robot
{
    public $x, $y, $theta; // position and orientation
    public $vx = 0, $vy = 0, $omega = 0; // velocities
    public $radius = 0.3; // meters
    public $commRadius = 5.0; // communication radius
    public $wheelBase = 0.4; // distance between wheels

    private $leftWheelSpeed = 0;
    private $rightWheelSpeed = 0;
    private $sensors = [];
    private $messages = [];

    public function __construct($x, $y, $theta = 0)
    {
        $this->x = $x;
        $this->y = $y;
        $this->theta = $theta;

        // Initialize sensors
        $this->sensors = [
            'front' => ['angle' => 0, 'range' => 5.0],
            'left' => ['angle' => M_PI/2, 'range' => 3.0],
            'right' => ['angle' => -M_PI/2, 'range' => 3.0],
            'back' => ['angle' => M_PI, 'range' => 2.0]
        ];
    }

    public function setWheelSpeeds($left, $right)
    {
        $this->leftWheelSpeed = $left;
        $this->rightWheelSpeed = $right;
    }

    public function setCmdVel($linear, $angular)
    {
        // Convert cmd_vel to wheel speeds
        $this->leftWheelSpeed = $linear - ($angular * $this->wheelBase / 2);
        $this->rightWheelSpeed = $linear + ($angular * $this->wheelBase / 2);
    }

    public function update($dt, $simulation)
    {
        // Differential drive kinematics
        $v = ($this->rightWheelSpeed + $this->leftWheelSpeed) / 2;
        $omega = ($this->rightWheelSpeed - $this->leftWheelSpeed) / $this->wheelBase;

        // Update pose
        $this->x += $v * cos($this->theta) * $dt;
        $this->y += $v * sin($this->theta) * $dt;
        $this->theta += $omega * $dt;

        // Normalize theta
        $this->theta = fmod($this->theta, 2 * M_PI);
        if ($this->theta < 0) $this->theta += 2 * M_PI;

        // Check collisions
        if ($simulation->checkCollision($this->x, $this->y, $this->radius)) {
            // Simple collision response - stop and back up slightly
            $this->x -= $v * cos($this->theta) * $dt * 0.5;
            $this->y -= $v * sin($this->theta) * $dt * 0.5;
            $this->leftWheelSpeed = 0;
            $this->rightWheelSpeed = 0;
        }

        // Update sensor readings
        $this->updateSensors($simulation);

        // Deposit pheromone
        $simulation->depositPheromone($this->x, $this->y, 0.1);
    }

    private function updateSensors($simulation)
    {
        foreach ($this->sensors as $name => &$sensor) {
            $globalAngle = $this->theta + $sensor['angle'];
            $sensor['reading'] = $simulation->raycast(
                $this->x, $this->y, $globalAngle, $sensor['range']
            );
        }
    }

    public function getSensorReading($sensorName)
    {
        return $this->sensors[$sensorName]['reading'] ?? null;
    }

    public function followPheromoneGradient($simulation, $strength = 1.0)
    {
        // Sample pheromone in different directions
        $angles = [0, M_PI/4, M_PI/2, 3*M_PI/4, M_PI, -3*M_PI/4, -M_PI/2, -M_PI/4];
        $maxPheromone = 0;
        $bestAngle = 0;

        foreach ($angles as $angle) {
            $globalAngle = $this->theta + $angle;
            $sampleX = $this->x + cos($globalAngle) * 1.0;
            $sampleY = $this->y + sin($globalAngle) * 1.0;
            $pheromone = $simulation->getPheromoneAt($sampleX, $sampleY);

            if ($pheromone > $maxPheromone) {
                $maxPheromone = $pheromone;
                $bestAngle = $angle;
            }
        }

        // Turn towards higher pheromone concentration
        if ($maxPheromone > 0.01) {
            $angularVel = $bestAngle * 2.0; // Proportional control
            $this->setCmdVel(0.5, $angularVel);
        } else {
            $this->setCmdVel(0.5, 0); // Move forward if no gradient
        }
    }

    public function broadcastMessage()
    {
        return [
            'robot_id' => spl_object_hash($this),
            'position' => ['x' => $this->x, 'y' => $this->y],
            'sensor_data' => $this->sensors,
            'timestamp' => microtime(true)
        ];
    }

    public function receiveMessage($message)
    {
        $this->messages[] = $message;

        // Keep only recent messages (last 10)
        if (count($this->messages) > 10) {
            array_shift($this->messages);
        }
    }

    public function getRecentMessages()
    {
        return $this->messages;
    }

    public function getState()
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
            'theta' => $this->theta,
            'radius' => $this->radius,
            'sensors' => $this->sensors,
            'wheel_speeds' => [
                'left' => $this->leftWheelSpeed,
                'right' => $this->rightWheelSpeed
            ]
        ];
    }
}