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
while camera.analog_gain <= 1:
	time.sleep(0.1)
# Now fix the values
camera.shutter_speed = camera.exposure_speed
camera.exposure_mode = 'off'
g = camera.awb_gains
camera.awb_mode = 'off'
camera.awb_gains = g
for frame in camera.capture_continuous(rawCapture, format="bgr", use_video_port=True):
	cleanImage = frame.array.copy()
	if laserOn>0:
		os.system('echo 1 > ./html/led')
		time.sleep(0.1)
		laserImage = frame.array[:,:,2]
	else:
		os.system('echo 0 > ./html/led')
		time.sleep(0.1)
		darkImage = frame.array[:,:,2]
	if laserImage.size>0 and darkImage.size>0 and laserOn==1:
		sub = cv2.subtract(laserImage,darkImage)
		blur = cv2.GaussianBlur(sub,(5,5),0)
		blobs = cv2.inRange(blur,20,255)
		#cv2.line(blobs,(width/3,0),(width/3,height),32)
		#cv2.line(blobs,(2*width/3,0),(2*width/3,height),32)
		mask = blobs.copy() #cv2.multiply(blobs,1)
		contours,hierarchy = cv2.findContours(blobs, 1, 2)
		lMaxY=0
		cMaxY=0
		rMaxY=0
		for cnt in contours:
			if cv2.contourArea(cnt)<4:
				continue
			hull = cv2.convexHull(cnt)
			for point in hull:
				x = point[0][0]
				y = point[0][1]
				cv2.drawContours(cleanImage, [cnt], 0, (0,255,0), 3)
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
		elif lMaxY<maxY and (rMaxY>maxY or cMaxY>maxY):
			os.system('cd html;./left.sh')
		elif rMaxY<maxY and (lMaxY>maxY or cMaxY>maxY):
			os.system('cd html;./right.sh')
		elif cMaxY>maxY or (lMaxY>maxY and rMaxY>maxY):
			os.system('cd html;./bwd.sh;./left.sh;./left.sh;./left.sh')
			
		time.sleep(0.1)
		cv2.imwrite('./html/ramdisk/robot.jpg',np.vstack( ( np.hstack((laserImage,darkImage)) , np.hstack((sub,mask)) ) ), [int(cv2.IMWRITE_JPEG_QUALITY), 25])
		cv2.imwrite('./html/ramdisk/frame.jpg',cleanImage, [int(cv2.IMWRITE_JPEG_QUALITY), 25])
	rawCapture.truncate(0)
	laserOn = 1-laserOn

