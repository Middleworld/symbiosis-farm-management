from launch import LaunchDescription
from launch_ros.actions import Node


def generate_launch_description():
    bridge = Node(
        package='mwf_farm_bridge',
        executable='/usr/bin/python3',
        arguments=['-m', 'mwf_farm_bridge.laravel_bridge'],
        output='screen'
    )

    return LaunchDescription([bridge])
