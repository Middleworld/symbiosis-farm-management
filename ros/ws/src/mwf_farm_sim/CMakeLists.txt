cmake_minimum_required(VERSION 3.8)
project(mwf_farm_sim)

find_package(ament_cmake REQUIRED)

install(DIRECTORY launch worlds urdf
  DESTINATION share/${PROJECT_NAME}
)

ament_package()
