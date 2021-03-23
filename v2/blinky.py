#!/usr/bin/env python3

from picamera.array import PiRGBArray
from picamera import PiCamera
import numpy as np
import time
import json
import os
import board
import digitalio
from adafruit_vl53l0x import VL53L0X

def awb():
	camera.awb_mode = 'auto'
	camera.exposure_mode = 'auto'
	time.sleep(1)
	camera.shutter_speed = camera.exposure_speed
	camera.exposure_mode = 'off'
	g = camera.awb_gains
	camera.awb_mode = 'off'
	camera.awb_gains = g
def getFrame():
	frame = np.empty((imgHeight,imgWidth,3),dtype=np.uint8)
	camera.capture_sequence([frame],'rgb',True)
	return frame
def getGPS():
	gpsData = json.load(open('/home/pi/RobotPi/html/ramdisk/gpsdata'))
	return gpsData
def ledOn():
	led.value=True
def ledOff():
	led.value=False
def uvLedOn():
        uvLed.value=True
def uvLedOff():
        uvLed.value=False

def initTof():
	global tofR
	global tofL
	enablePin = digitalio.DigitalInOut(board.D16)
	enablePin.switch_to_output(value=False);
	i2c = board.I2C()
	#Turn off left tof (0x30)
	enablePin.value = False
	time.sleep(0.1)
	#Grab right tof (0x31 or 0x29)
	try:
		tofR = VL53L0X(i2c,0x29,1)
		tofR.set_address(0x31)
	except:
		print("Already changed!\n")
		tofR = VL53L0X(i2c,0x31)
	print(tofR)
	#turn left tof back on
	enablePin.value = True
	time.sleep(0.1)
	tofL = VL53L0X(i2c,0x29,1)
	print(tofL)
	tofR.measurement_timing_budget = 66000
	tofL.measurement_timing_budget = 66000
def getTof():
	global tofL
	global tofR
	try:
		lRange = tofL.range
		rRange = tofR.range
	except Exception as e:
		print(e)
		lRange = -1
		rRange = -1
	return (lRange,rRange)

def fwd(secs):
	rightB.value=False
	leftB.value=False
	rightF.value=True
	leftF.value=True
	rightE.value=True
	leftE.value=True
	time.sleep(secs)
	rightE.value=False
	leftE.value=False
	rightF.value=False
	leftF.value=False
def bwd(secs):
        rightF.value=False
        leftF.value=False
        rightB.value=True
        leftB.value=True
        rightE.value=True
        leftE.value=True
        time.sleep(secs)
        rightE.value=False
        leftE.value=False
        rightB.value=False
        leftB.value=False
def left(secs):
	rightB.value=False
	leftF.value=False
	rightF.value=True
	leftB.value=True
	leftE.value=True
	rightE.value=True
	time.sleep(secs)
	rightF.value=False
	leftB.value=False
	rightE.value=False
	leftE.value=False
def right(secs):
        rightF.value=False
        leftB.value=False
        rightB.value=True
        leftF.value=True
        leftE.value=True
        rightE.value=True
        time.sleep(secs)
        rightB.value=False
        leftF.value=False
        rightE.value=False
        leftE.value=False

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
	bins = [0,0,0,0,0]
	for y in range(0,imgHeight-1):
		for bin in range(0,4):
			for x in range(0,(int)(bin*(imgWidth-1)/5)):
				for i in range(0,2):
					bins[bin]+=map[y][x][i]
	moves = ""
	leftSrc = bins[0]+bins[1]
	centerSrc = bins[2]*2
	rightSrc = bins[3]+bins[4]
	if(leftSrc>=centerSrc and leftSrc>=rightSrc and leftSrc>=settings.get('srcThresh')*imgHeight*imgWidth/5*3):
		moves="l"
	if(rightSrc>=centerSrc and rightSrc>=leftSrc and rightSrc>=settings.get('srcThresh')*imgHeight*imgWidth/5*3):
                moves="r"
	if(centerSrc > leftSrc and centerSrc > rightSrc and centerSrc > settings.get('srcThresh')*imgHeight*imgWidth/5*3):
		moves="f"
	status['seek']=bins
	return moves

def avoid(tof):
	moves = "f"
	if(tof[0]<tof[1] and tof[0]<settings['minDist']):
		if(tof[0]<settings.get('minDist')/2):
			moves="br"
			status['backCount']=status['backCount']+1
		else:
			moves="r"
			status['backCount']=status['backCount']+0.5
	if(tof[1]<tof[0] and tof[1]<settings['minDist']):
		if(tof[1]<settings['minDist']/2):
			moves="bl"
			status['backCount']=status['backCount']+1
		else:
			moves="l"
			status['backCount']=status['backCount']+0.5
	if(moves=="f"):
		status['backCount']=max(0,status['backCount']-1)
	else:
		if(status['backCount']>=settings['backLimit']):
			moves = status['backMove']
		else:
			status['backMove']=moves.replace("b","")
	return moves


def executeMoves(moves):
	for a in moves:
		if(a=="l"):
			left(1)
		if(a=="r"):
			right(1)
		if(a=="f"):
			fwd(1)
		if(a=="b"):
			bwd(1)

print("Init params")
settings = {'srcThresh':32,'backLimit':3, 'minDist':750,'mode':'UV'}
status = {'backCount':0}
tofL=0
tofR=0

print("Init GPIO")
led = digitalio.DigitalInOut(board.D5)
led.direction = digitalio.Direction.OUTPUT

uvLed = digitalio.DigitalInOut(board.D19)
uvLed.direction = digitalio.Direction.OUTPUT

rightF = digitalio.DigitalInOut(board.D4)
rightF.direction = digitalio.Direction.OUTPUT
rightF.value = False

rightB = digitalio.DigitalInOut(board.D17)
rightB.direction = digitalio.Direction.OUTPUT
rightB.value = False

rightE = digitalio.DigitalInOut(board.D6)
rightE.direction = digitalio.Direction.OUTPUT
rightE.value = False

leftF = digitalio.DigitalInOut(board.D22)
leftF.direction = digitalio.Direction.OUTPUT
leftF.value = False

leftB = digitalio.DigitalInOut(board.D27)
leftB.direction = digitalio.Direction.OUTPUT
leftB.value = False

leftE = digitalio.DigitalInOut(board.D13)
leftE.direction = digitalio.Direction.OUTPUT
leftE.value = False

print("Init camera")
imgWidth=64
imgHeight=48
camera = PiCamera()
camera.resolution = (imgWidth, imgHeight)
camera.framerate = 24
awb()
initTof()


while True:
	tof=getTof()
	tofMoves = avoid(tof)

	if(tofMoves!="f" and settings['mode']=="UV"):
		uv=makeUVMap()
		seekMoves = seek(uv)
	else:
		seekMoves = ""
	moves = tofMoves
	if(tofMoves!="f"):
		status['phase']='avoid'
		if(seekMoves=="f"):
			status['phase']='target found'
			moves = ""
	else:
		status['phase']='travel'
		if(len(seekMoves)):
			status['phase']='seek'
			moves=seekMoves
	if(tofMoves=="r" and seekMoves=="l"):
		status['phase']='target left'
		moves="l"
	if(tofMoves=="l" and seekMoves=="r"):
		status['phase']='target right'
		moves="r"
	status['moves']=moves
	status['tofMoves']=tofMoves
	status['seekMoves']=seekMoves
	status['tof']=tof
	executeMoves(moves)
	print(status)
