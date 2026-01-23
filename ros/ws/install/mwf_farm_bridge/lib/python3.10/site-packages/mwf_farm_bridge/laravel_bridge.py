import os
import time
import json
import threading
from typing import Dict, Any

import rclpy
from rclpy.node import Node
from gazebo_msgs.msg import ModelStates
import requests


def getenv(name: str, default: str) -> str:
    return os.getenv(name, default)


def now_ms() -> int:
    return int(time.time() * 1000)


class LaravelBridge(Node):
    def __init__(self):
        super().__init__('laravel_bridge')

        self.base_url = getenv('LARAVEL_BASE_URL', 'https://admin.soilsync.shop')
        self.endpoint = getenv('LARAVEL_ROS_ENDPOINT', '/api/ros/telemetry')
        self.api_key = getenv('LARAVEL_API_KEY', '')
        self.robot_prefix = getenv('ROBOT_PREFIX', 'amiga_')
        self.min_interval = float(getenv('TELEMETRY_INTERVAL_SEC', '1.0'))

        self._last_sent = 0.0
        self._lock = threading.Lock()

        self.subscription = self.create_subscription(
            ModelStates,
            '/gazebo/model_states',
            self.model_states_callback,
            10
        )
        self.get_logger().info('Laravel bridge started')

    def model_states_callback(self, msg: ModelStates) -> None:
        now = time.time()
        with self._lock:
            if now - self._last_sent < self.min_interval:
                return
            self._last_sent = now

        payload = self._build_payload(msg)
        if not payload['robots']:
            return

        url = f"{self.base_url.rstrip('/')}{self.endpoint}"
        headers = {'Content-Type': 'application/json'}
        if self.api_key:
            headers['X-ROS-API-Key'] = self.api_key

        try:
            resp = requests.post(url, data=json.dumps(payload), headers=headers, timeout=3)
            if resp.status_code != 200:
                self.get_logger().warn(f'Unexpected response from Laravel: {resp.status_code} {resp.text}')
        except Exception as exc:
            self.get_logger().warn(f'Failed to post telemetry: {exc}')

    def _build_payload(self, msg: ModelStates) -> Dict[str, Any]:
        robots = []
        for name, pose, twist in zip(msg.name, msg.pose, msg.twist):
            if not name.startswith(self.robot_prefix):
                continue
            robots.append({
                'name': name,
                'position': {
                    'x': pose.position.x,
                    'y': pose.position.y,
                    'z': pose.position.z,
                },
                'orientation': {
                    'x': pose.orientation.x,
                    'y': pose.orientation.y,
                    'z': pose.orientation.z,
                    'w': pose.orientation.w,
                },
                'linear': {
                    'x': twist.linear.x,
                    'y': twist.linear.y,
                    'z': twist.linear.z,
                },
                'angular': {
                    'x': twist.angular.x,
                    'y': twist.angular.y,
                    'z': twist.angular.z,
                },
            })

        return {
            'timestamp_ms': now_ms(),
            'source': 'gazebo',
            'robots': robots,
        }


def main():
    rclpy.init()
    node = LaravelBridge()
    try:
        rclpy.spin(node)
    finally:
        node.destroy_node()
        rclpy.shutdown()


if __name__ == '__main__':
    main()
