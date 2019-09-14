#!/bin/bash
echo "Starting webcam..."
raspistill -o /home/pi/RobotPi/html/frame.jpg -tl 2000 -t 0 -w 1024 -h 768

