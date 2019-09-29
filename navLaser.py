#! /usr/bin/python
from picamera.array import PiRGBArray
from picamera import PiCamera
import os
import numpy as np
import cv2
import time

width=640
height=480
maxY=225
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
	cleanImage = frame.array
	if laserOn>0:
		print("laseron")
		os.system('echo 1 > ./html/led')
		laserImage = frame.array[:,:,2]
	else:
		print("laseroff")
		os.system('echo 0 > ./html/led')
		darkImage = frame.array[:,:,2]
	if laserImage.size>0 and darkImage.size>0 and laserOn==0:
		sub = cv2.subtract(laserImage,darkImage)
		blur = cv2.GaussianBlur(sub,(5,5),0)
		blobs = cv2.inRange(blur,20,255)
		mask = cv2.multiply(blobs,1)
		contours,hierarchy = cv2.findContours(blobs, 1, 2)
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
		cv2.line(cleanImage,(0,lMaxY),(width/3,lMaxY),(0,0,255),8)
		cv2.line(cleanImage,(width/3,cMaxY),(2*width/3,cMaxY),(0,0,255),8)
		cv2.line(cleanImage,(2*width/3,rMaxY),(width,rMaxY),(0,0,255),8)
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
		time.sleep(1)
		cv2.imwrite('./html/ramdisk/robot.jpg',mask, [int(cv2.IMWRITE_JPEG_QUALITY), 25])
	rawCapture.truncate(0)
	cv2.imwrite('./html/ramdisk/frame.jpg',cleanImage, [int(cv2.IMWRITE_JPEG_QUALITY), 25])
	laserOn = 1-laserOn

