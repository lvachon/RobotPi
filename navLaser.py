#! /usr/bin/python
import os
import numpy as np
import cv2
import time

frame = cv2.imread('./html/ramdisk/frame.jpg')
width = frame[0].size/3
height = frame.size / frame[0].size
#blurred = np.zeros((height,width,3), np.uint8)
#satRange = 10.
#hueRange = 15.
maxY=400
while(1):
    frame = cv2.imread('./html/ramdisk/frame.jpg')
    if frame.size>0:
	#b,g,r = cv2.split(frame)
	mask = cv2.subtract(frame[:,:,2],frame[:,:,1])
	mask = cv2.subtract(mask,frame[:,:,0])
	blur = cv2.GaussianBlur(mask,(5,5),0)
	ret,mask = cv2.threshold(blur,0,255,cv2.THRESH_BINARY+cv2.THRESH_OTSU)
	contours,hierarchy = cv2.findContours(mask, 1, 2)
	lMaxY=0
	cMaxY=0
	rMaxY=0
	for cnt in contours:
		x,y,w,h = cv2.boundingRect(cnt)
		if w<4 or h<4:
			continue
		if x<width/3 and y>lMaxY:
			lMaxY=y
		if x>width/3 and x<2*width/3 and y>cMaxY:
			cMaxY=y
		if x>2*width/3 and y>rMaxY:
			rMaxY=y
	cv2.line(frame,(0,lMaxY),(width/3,lMaxY),(0,0,255),8)
	cv2.line(frame,(width/3,cMaxY),(2*width/3,cMaxY),(0,0,255),8)
	cv2.line(frame,(2*width/3,rMaxY),(width,rMaxY),(0,0,255),8)
	if lMaxY<maxY and cMaxY<maxY and rMaxY<maxY:
		os.system('cd html;./fwd.sh')
	else:
		if lMaxY<maxY and (rMaxY>maxY or cMaxY>maxY):
			os.system('cd html;./left.sh')
		else:
			if rMaxY<maxY and (lMaxY>maxY or cMaxY>maxY):
                		os.system('cd html;./right.sh')
			else:
				if cMaxY>maxY:
					os.system('cd html;./bwd.sh')
	time.sleep(.500)

	#print(mask)
	#mask = cv2.inRange(mask,0,255)
	cv2.imwrite('./html/ramdisk/robot.jpg',mask, [int(cv2.IMWRITE_JPEG_QUALITY), 25])
    else:
        break

