from picamera.array import PiRGBArray
from picamera import PiCamera
import numpy as np
import math
import time
from util import *

def awb():
	camera.awb_mode = 'auto'
	camera.exposure_mode = 'auto'
	time.sleep(2)
	camera.shutter_speed = camera.exposure_speed
	camera.exposure_mode = 'off'
	g = camera.awb_gains
	camera.awb_mode = 'off'
	camera.awb_gains = g
def getFrame():
	frame = np.empty((imgHeight,imgWidth,3),dtype=np.uint8)
	camera.capture_sequence([frame],'rgb',True,(64,48))
	return frame



def lum(r,g,b):
	cmax = max(r,max(g,b))
	cmin = min(r,min(g,b))
	return cmax


def makeUVMap():
	uvLedOff()
	dark = getFrame()
	uvLedOn()
	light = getFrame()
	uvLedOff()
	uvmap = np.empty((imgHeight,imgWidth,3),dtype=np.uint8)
	for y in range(0,imgHeight-1):
		for x in range(0,imgWidth-1):
			lumDif = lum(
				abs((int)(light[y][x][0])-(int)(dark[y][x][0])),
				abs((int)(light[y][x][1])-(int)(dark[y][x][1])),
				abs((int)(light[y][x][2])-(int)(dark[y][x][2])))
			uvmap[y][x]=[lumDif,lumDif,lumDif]
	return uvmap
def seek(map):
	global settings,status
	bins = [0,0,0,0,0]
	for y in range(0,imgHeight-1):
		for bin in range(0,4):
			for x in range(0,(int)(bin*(imgWidth-1)/5)):
				for i in range(0,2):
					bins[bin]+=map[y][x][i]
	for i in range(0,4):
		bins[i]=(int)(bins[i]/(imgHeight*imgWidth/5*3))
	moves = ""
	leftSrc = bins[0]+bins[1]
	centerSrc = bins[2]*2
	rightSrc = bins[3]+bins[4]
	if(leftSrc>=centerSrc and leftSrc>=rightSrc and leftSrc>=settings.get('srcThresh')):
		moves="l"
	if(rightSrc>=centerSrc and rightSrc>=leftSrc and rightSrc>=settings.get('srcThresh')):
                moves="r"
	if(centerSrc > leftSrc and centerSrc > rightSrc and centerSrc > settings.get('srcThresh')):
		moves="f"
	status['seek']=bins
	return moves
imgWidth=64
imgHeight=48
humanWidth=640
humanHeight=480
camera = PiCamera()
camera.resolution = (humanWidth, humanHeight)
camera.framerate = 4