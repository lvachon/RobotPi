#! /usr/bin/python
from picamera.array import PiRGBArray
from picamera import PiCamera
import os
import numpy as np
import cv2
import time

width=640
height=480
maxY=250
contours = ()
camera = PiCamera()
camera.resolution = (640, 480)
camera.framerate = 10
rawCapture = PiRGBArray(camera, size=(640, 480))
laserOn = 0
time.sleep(0.1)
laserImage = np.array(())
darkImage = np.array(())
for frame in camera.capture_continuous(rawCapture, format="bgr", use_video_port=True):
    print(frame)
    if laserOn>0:
        os.system('echo 1 > ./html/led')
        laserImage = frame.array
    else:
        os.system('echo 0> ./html/led')
        darkImage = frame.array
    laserOn = 1-laserOn
    if laserImage.size>0 and darkImage.size>0:
	#red = cv2.multiply(image[:,:,2],1)
	#green = cv2.multiply(image[:,:,1],1)
	#blue = cv2.multiply(image[:,:,0],1)
	mask = cv2.subtract(laserImage,darkImage)
	blur = cv2.GaussianBlur(mask,(5,5),0)
	ret,mask = cv2.threshold(blur,0,255,cv2.THRESH_BINARY + +cv2.THRESH_OTSU)
	blobs = cv2.multiply(mask,1)
	contours,hierarchy = cv2.findContours(mask, 1, 2)
	lMaxY=0
	cMaxY=0
	rMaxY=0
	for cnt in contours:
		x,y,w,h = cv2.boundingRect(cnt)
		if w<2 or h<2:
			continue
		if x<width/3 and y>lMaxY:
			lMaxY=y
		if x>width/3 and x<2*width/3 and y>cMaxY:
			cMaxY=y
		if x>2*width/3 and y>rMaxY:
			rMaxY=y
	cv2.line(image,(0,lMaxY),(width/3,lMaxY),(0,0,255),8)
	cv2.line(image,(width/3,cMaxY),(2*width/3,cMaxY),(0,0,255),8)
	cv2.line(image,(2*width/3,rMaxY),(width,rMaxY),(0,0,255),8)
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

	cv2.imwrite('./html/ramdisk/robot.jpg',blobs, [int(cv2.IMWRITE_JPEG_QUALITY), 25])
    else:
        break
    rawCapture.truncate(0)

