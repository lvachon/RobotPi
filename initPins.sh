#!/bin/sh
echo "Initializing pins..."
echo "4" > /sys/class/gpio/export
echo "17" > /sys/class/gpio/export
echo "27" > /sys/class/gpio/export
echo "22" > /sys/class/gpio/export
echo "6" > /sys/class/gpio/export
echo "13" > /sys/class/gpio/export
echo "Waiting for udev..."
udevadm settle
echo "Setting direction..."
echo "out" > /sys/class/gpio/gpio4/direction
echo "out" > /sys/class/gpio/gpio17/direction
echo "out" > /sys/class/gpio/gpio27/direction
echo "out" > /sys/class/gpio/gpio22/direction
echo "out" > /sys/class/gpio/gpio6/direction
echo "out" > /sys/class/gpio/gpio13/direction
echo "Setting value..."
echo "0" > /sys/class/gpio/gpio4/value
echo "0" > /sys/class/gpio/gpio17/value
echo "0" > /sys/class/gpio/gpio27/value
echo "0" > /sys/class/gpio/gpio22/value
echo "0" > /sys/class/gpio/gpio6/value
echo "0" > /sys/class/gpio/gpio13/value
echo "Making links..."
rm -f /home/pi/left_*
rm -f /home/pi/right_*
ln -s /sys/class/gpio/gpio4/value /home/pi/RobotPi/html/right_fwd
ln -s /sys/class/gpio/gpio17/value /home/pi/RobotPi/html/right_bwd
ln -s /sys/class/gpio/gpio27/value /home/pi/RobotPi/html/left_bwd
ln -s /sys/class/gpio/gpio22/value /home/pi/RobotPi/html/left_fwd
ln -s /sys/class/gpio/gpio6/value /home/pi/RobotPi/html/right_enable
ln -s /sys/class/gpio/gpio13/value /home/pi/RobotPi/html/left_enable
chmod a+rwx /home/pi/RobotPi/html/*_*
chown www-data:www-data /home/pi/RobotPi/html/*_*
echo "Done"


