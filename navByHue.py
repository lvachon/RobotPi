#! /usr/bin/python

import numpy as np
import cv2

frame = cv2.imread('./html/ramdisk/frame.jpg')
width = frame[0].size/3
height = frame.size / frame[0].size
blurred = np.zeros((height,width,3), np.uint8)
satRange = 10.
hueRange = 15.
while(1):
    frame = cv2.imread('./html/ramdisk/frame.jpg')
    if frame.size>0:
	blurred = cv2.blur(frame,(32,32))
        hsv =  cv2.cvtColor(blurred, cv2.COLOR_BGR2HSV)
	hueFloor = hsv[int(3*height/4)][int(width/2)][0]
	satFloor = hsv[int(3*height/4)][int(width/2)][1]
	#centerfloor
        mask = cv2.inRange(hsv, np.array((hueFloor-hueRange, satFloor-satRange,32.)), np.array((hueFloor+hueRange,satFloor+satRange,255.)))
	hueFloor = hsv[int(3*height/4)][int(width/4)][0]
        satFloor = hsv[int(3*height/4)][int(width/4)][1]
	#sidefloor
	mask2 = cv2.inRange(hsv, np.array((hueFloor-hueRange, satFloor-satRange,32.)), np.array((hueFloor+hueRange,satFloor+satRange,255.)))
	mask = cv2.bitwise_or(mask,mask2)
	#hueFloor = hsv[int(3*height/4)][int(3*width/4)][0]
        #satFloor = hsv[int(3*height/4)][int(3*width/4)][1]
        #mask3 = cv2.inRange(hsv, np.array((hueFloor-hueRange, satFloor-satRange,32.)), np.array((hueFloor+hueRange,satFloor+satRange,255.)))
        #mask = cv2.bitwise_or(mask,mask3)
        cv2.imwrite('./html/ramdisk/robot.jpg',mask, [int(cv2.IMWRITE_JPEG_QUALITY), 25])
    else:
        break

