from launch import LaunchDescription
from launch.actions import ExecuteProcess, SetEnvironmentVariable
from launch_ros.actions import Node
from ament_index_python.packages import get_package_share_directory
import os

def generate_launch_description():
    pkg_share = get_package_share_directory('mwf_farm_sim')
    world_path = os.path.join(pkg_share, 'worlds', 'mwf_farm.world')
    urdf_path = os.path.join(pkg_share, 'urdf', 'amiga_base.urdf')

    headless = os.environ.get('GAZEBO_HEADLESS', '1') == '1'
    has_display = bool(os.environ.get('DISPLAY'))

    server_cmd = [
        'gzserver', '--verbose', world_path,
        '-s', 'libgazebo_ros_factory.so',
    ]
    if headless:
        server_cmd.append('--headless-rendering')
    gazebo = ExecuteProcess(
        cmd=server_cmd,
        output='screen'
    )

    gui = None
    if not headless and has_display:
        gui = ExecuteProcess(
            cmd=['gzclient'],
            output='screen'
        )

    robot_state_publisher = Node(
        package='robot_state_publisher',
        executable='robot_state_publisher',
        arguments=[urdf_path],
        output='screen'
    )

    spawn_entity_1 = Node(
        package='gazebo_ros',
        executable='spawn_entity.py',
        arguments=['-entity', 'amiga_01', '-file', urdf_path, '-x', '0', '-y', '0'],
        output='screen'
    )

    spawn_entity_2 = Node(
        package='gazebo_ros',
        executable='spawn_entity.py',
        arguments=['-entity', 'amiga_02', '-file', urdf_path, '-x', '2', '-y', '0'],
        output='screen'
    )

    spawn_entity_3 = Node(
        package='gazebo_ros',
        executable='spawn_entity.py',
        arguments=['-entity', 'amiga_03', '-file', urdf_path, '-x', '-2', '-y', '0'],
        output='screen'
    )

    actions = []
    if headless:
        actions.extend([
            SetEnvironmentVariable('GAZEBO_AUDIO', '0'),
            SetEnvironmentVariable('SDL_AUDIODRIVER', 'dummy'),
        ])
    actions.extend([
        gazebo,
        robot_state_publisher,
        spawn_entity_1,
        spawn_entity_2,
        spawn_entity_3,
    ])
    if gui:
        actions.append(gui)

    return LaunchDescription(actions)
