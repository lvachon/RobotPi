#!/bin/bash
echo "Starting webcam..."
touch /home/pi/RobotPi/html/ramdisk/frame.jpg
chmod a+rw /home/pi/RobotPi/html/ramdisk/frame.jpg
touch /home/pi/RobotPi/html/ramdisk/autocmd
chmod a+rw /home/pi/RobotPi/html/ramdisk/autocmd
touch /home/pi/RobotPi/html/ramdisk/motorCommand
chmod a+rw /home/pi/RobotPi/html/ramdisk/motorCommand
raspistill -o /home/pi/RobotPi/html/ramdisk/frame.jpg -tl 250 -t 0 -w 1024 -h 768 -q 10 -bm 

