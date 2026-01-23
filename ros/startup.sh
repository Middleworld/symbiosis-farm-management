#!/usr/bin/env bash
set -euo pipefail

# Startup script for Symbiosis ROS sim (runs inside container)
# Sources ROS and workspace install then launches sim and bridge

# Source ROS safely (allow unset vars in setup scripts)
set +u
if [ -f "/opt/ros/humble/setup.bash" ]; then
  # some ROS setup scripts reference unset vars; allow that here
  source /opt/ros/humble/setup.bash
fi

# Ensure required OS packages (CycloneDDS, Gazebo tools, requests) are installed
if ! ldconfig -p 2>/dev/null | grep -q librmw_cyclonedds_cpp; then
  echo "Required ROS packages missing; installing: rmw-cyclonedds, gazebo, state-publisher, python3-requests"
  apt-get update -y && apt-get install -y --no-install-recommends \
    ros-humble-rmw-cyclonedds-cpp \
    ros-humble-gazebo-ros-pkgs \
    ros-humble-robot-state-publisher \
    python3-requests python3-pip || true
fi

# Source workspace install overlay if present
if [ -f "/root/ws/install/setup.bash" ]; then
  source /root/ws/install/setup.bash
fi

# Headless + audio off by default for server sims (override with GAZEBO_HEADLESS=0)
HEADLESS="${GAZEBO_HEADLESS:-1}"
if [ "$HEADLESS" = "1" ]; then
  export GAZEBO_HEADLESS=1
  export GAZEBO_AUDIO=0
  export SDL_AUDIODRIVER=dummy
  export ALSA_CONFIG_PATH=/dev/null
  unset DISPLAY
fi

# Ensure workspace install prefix is visible to ROS runtime
# (force-add to AMENT_PREFIX_PATH / ROS_PACKAGE_PATH / PYTHONPATH to avoid package-not-found)
export AMENT_PREFIX_PATH="/root/ws/install${AMENT_PREFIX_PATH:+:$AMENT_PREFIX_PATH}"
export ROS_PACKAGE_PATH="/root/ws/install/mwf_farm_sim/share${ROS_PACKAGE_PATH:+:$ROS_PACKAGE_PATH}"
export PYTHONPATH="/root/ws/install/mwf_farm_bridge/lib/python3.10/site-packages${PYTHONPATH:+:$PYTHONPATH}"

set -u

# Ensure logs dir exists
mkdir -p /root/ws/logs

# Launch sim and bridge in background if not already running
start_if_missing() {
  name="$1"; shift
  pidfile="$1"; shift
  cmd="$@"

  if [ -f "$pidfile" ] && kill -0 "$(cat "$pidfile")" 2>/dev/null; then
    echo "$name already running (pid $(cat "$pidfile"))"
    return
  fi

  nohup bash -lc "$cmd" > /root/ws/logs/${name}.log 2>&1 &
  echo $! > "$pidfile"
  echo "Started $name (pid $(cat $pidfile))"
}

# Start sim (headless by default)
start_if_missing sim /root/ws/logs/sim.pid "export AMENT_PREFIX_PATH=/root/ws/install/mwf_farm_sim:\$AMENT_PREFIX_PATH && export ROS_PACKAGE_PATH=/root/ws/install/mwf_farm_sim/share:\$ROS_PACKAGE_PATH && export PYTHONPATH=/root/ws/install/mwf_farm_bridge/lib/python3.10/site-packages:\$PYTHONPATH && source /opt/ros/humble/setup.bash && source /root/ws/install/setup.bash && ros2 launch mwf_farm_sim sim.launch.py"

# Start bridge
start_if_missing bridge /root/ws/logs/bridge.pid "source /opt/ros/humble/setup.bash && source /root/ws/install/setup.bash && ros2 launch mwf_farm_bridge bridge.launch.py"

# Tail logs so the container stays alive and we can see output
exec tail -n +1 -F /root/ws/logs/*.log
