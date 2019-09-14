#!/bin/bash
echo "Installing lighttpd config"
cp /etc/lighttpd/lighttpd.conf /etc/lighttpd/lighttpd_old.conf
cp ./config/lighttpd.conf /etc/lighttpd/lighttpd.conf
echo "Installing services"
cp ./services/robot.service /lib/systemd/system/robot.service
cp ./services/webcam.service /lib/systemd/system/webcam.service
systemctl enable robot.service
systemctl enable webcam.service
systemctl daemon-reload
echo "Done"