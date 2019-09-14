#!/bin/bash
echo "Starting webcam..."
raspistill -o /var/www/html/frame.jpg -tl 2000 -t 0 -w 1024 -h 768

