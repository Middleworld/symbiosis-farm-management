# SoilSync ROS Simulation Starter

This starter gives you a local ROS 2 + Gazebo simulation scaffold to test multi‑robot coordination before hardware arrives.

## What’s included
- Docker-based ROS 2 Humble environment
- Minimal farm world and simple “Amiga-like” base URDF
- Launch file to open Gazebo and spawn a robot

## Quick start (Linux)
1) Allow X11 for Docker:
```
xhost +local:
```

2) Start the ROS sim container:
```
cd /var/www/vhosts/soilsync.shop/ros
sudo docker compose up -d
sudo docker exec -it symbiosis-ros-sim bash
```

3) Inside container, install sim deps (one-time):
```
apt update
apt install -y ros-humble-gazebo-ros-pkgs ros-humble-robot-state-publisher
```

4) Build the workspace:
```
cd /root/ws
source /opt/ros/humble/setup.bash
colcon build
source /root/ws/install/setup.bash
```

5) Launch simulation (3-bot swarm):
```
ros2 launch mwf_farm_sim sim.launch.py
```

## Laravel bridge
This node posts Gazebo model states to Laravel.

### Build/install
```
cd /root/ws
source /opt/ros/humble/setup.bash
colcon build
source /root/ws/install/setup.bash
```

### Run
```
export LARAVEL_BASE_URL="https://admin.soilsync.shop"
export LARAVEL_ROS_ENDPOINT="/api/ros/telemetry"
export LARAVEL_API_KEY=""
ros2 launch mwf_farm_bridge bridge.launch.py
```

The bridge posts telemetry for models whose name starts with `amiga_`.

## Files
- Package: [ros/ws/src/mwf_farm_sim](ros/ws/src/mwf_farm_sim)
- World: [ros/ws/src/mwf_farm_sim/worlds/mwf_farm.world](ros/ws/src/mwf_farm_sim/worlds/mwf_farm.world)
- Robot: [ros/ws/src/mwf_farm_sim/urdf/amiga_base.urdf](ros/ws/src/mwf_farm_sim/urdf/amiga_base.urdf)

## Next steps
- Add additional robots by duplicating the spawn in `sim.launch.py`
- Add obstacles/terrain to the world file
- Add a ROS node to publish robot state to the Laravel backend (REST/WebSocket)
- Integrate task assignment topics (e.g., `/swarm/assign_task`)
