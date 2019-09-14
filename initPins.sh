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
ln -s /sys/class/gpio/gpio4/value /var/www/html/left_fwd
ln -s /sys/class/gpio/gpio17/value /var/www/html/left_bwd
ln -s /sys/class/gpio/gpio27/value /var/www/html/right_bwd
ln -s /sys/class/gpio/gpio22/value /var/www/html/right_fwd
ln -s /sys/class/gpio/gpio6/value /var/www/html/left_enable
ln -s /sys/class/gpio/gpio13/value /var/www/html/right_enable
chmod a+rwx /var/www/html/*_*
chown www-data:www-data /var/www/html/*_*
echo "Done"


