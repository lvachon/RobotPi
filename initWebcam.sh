#!/bin/bash
echo "Starting webcam..."
raspistill -o /home/pi/RobotPi/html/ramdisk/frame.jpg -tl 250 -t 0 -w 1024 -h 768 -q 10

