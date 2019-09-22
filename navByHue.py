#! /usr/bin/python

import numpy as np
import cv2

frame = cv2.imread('./html/ramdisk/frame.jpg')
width = frame[0].size/3
height = frame.size / frame[0].size



while(1):
    frame = cv2.imread('./html/ramdisk/frame.jpg')
    if frame.size>0:
        hsv =  cv2.cvtColor(frame, cv2.COLOR_BGR2HSV)
	hueFloor = hsv[int(3*height/4)][int(width/2)][0]
        hueRange = 5.;
        mask = cv2.inRange(hsv, np.array((hueFloor-hueRange, 32.,32.)), np.array((hueFloor+hueRange,255.,255.)))

        cv2.imwrite('./html/ramdisk/robot.jpg',mask, [int(cv2.IMWRITE_JPEG_QUALITY), 25])
    else:
        break

