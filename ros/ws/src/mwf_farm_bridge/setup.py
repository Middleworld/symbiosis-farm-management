from setuptools import setup

package_name = 'mwf_farm_bridge'

setup(
    name=package_name,
    version='0.1.0',
    packages=[package_name],
    data_files=[
        ('share/ament_index/resource_index/packages', ['resource/' + package_name]),
        ('share/' + package_name, ['package.xml']),
        ('share/' + package_name + '/launch', ['launch/bridge.launch.py']),
    ],
    install_requires=['setuptools', 'requests'],
    zip_safe=True,
    maintainer='SoilSync',
    maintainer_email='ops@soilsync.shop',
    description='ROS2 to Laravel bridge for SoilSync robot telemetry.',
    license='Proprietary',
    entry_points={
        'console_scripts': [
            'laravel_bridge = mwf_farm_bridge.laravel_bridge:main',
        ],
    },
)
