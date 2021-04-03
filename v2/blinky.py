#!/usr/bin/env python3
#from picamera.array import PiRGBArray
#from picamera import PiCamera
#import numpy as np
#import math
import time
import json
import os
#import board
#import digitalio
#import FaBo9Axis_MPU9250
#from haversine import haversine, Unit
#from adafruit_vl53l0x import VL53L0X

from gpsnav import *
from tof import *
from leds import *
from motors import *
from vision import *

def readSettings():
	global settingsMod, settings
	if(os.path.getmtime('../html/botSettings')<=settingsMod):
		return
	f = open('../html/botSettings')
	s = json.load(f)
	for key in s:
		if(s[key].isnumeric()):
			settings[key]=int(s[key])
		else:
			settings[key]=s[key]
	f.close()
	settingsMod=time.time()


def writeTelemetry():
	global lastTele, status
	status['time']=time.strftime('%H:%M:%S')
	f = open('../html/ramdisk/telemetry','w')
	jstring = repr(status)
	f.write(jstring.replace("'","\""))
	f.close()
	if(lastTele<time.time()-5):
		print("printf \"AT+SEND=0,"+str(len(jstring))+","+jstring+"\\r\\n\" > /dev/serial0")
		os.system("printf \"AT+SEND=0,"+str(len(jstring))+","+jstring+"\\r\\n\" > /dev/serial0")
		lastTele = time.time()


print("Init params")
settings = {'srcThresh':32,'backLimit':3, 'minDist':750,'navMode':'GPS'}
status = {'backCount':0}
settingsMod=0
lastTele = 0
print("Init camera")
awb()
print("Init ToF")
initTof()
print("Init LoRa")
os.system('stty -F /dev/serial0 115200')
os.system("printf 'AT+PARAMETER=9,7,1,6\r\n' > /dev/serial0")
print("Main Loop")
while True:
	with open('/home/pi/RobotPi/html/ramdisk/autocmd') as f:
		cmd = f.read()
	readSettings()
	tof=getTof()
	tofMoves = avoid(tof)
	seekMoves = ""
	if(tofMoves!="f" and settings['navMode']=="UV"):
		uv=makeUVMap()
		seekMoves = seek(uv)
	if(tofMoves=="f" and settings['navMode']=="GPS"):
		seekMoves = autopilot()
	moves = tofMoves
	if(tofMoves!="f"):
		status['phase']='avoid'
		if(settings['navMode']=="UV"):
			if(seekMoves=="f"):
				status['phase']='found'
				status['backCount']=0
				moves = ""
	else:
		status['phase']='travel'
		if(seekMoves):
			status['phase']='seek'
			moves=seekMoves
			if(moves=="s"):status['phase']="Seek Wait"
	if(tofMoves=="r" and seekMoves=="l" and settings['navMode']=="UV"):
		status['phase']='t-left'
		moves="l"
	if(tofMoves=="l" and seekMoves=="r" and settings['navMode']=="UV"):
		status['phase']='t-right'
		moves="r"
	status['moves']=moves
	status['tof']=tof
	if(cmd=="GO"):executeMoves(moves)
	print(status)
	os.system("mv ../html/ramdisk/buffer.jpg ../html/ramdisk/frame.jpg")
	camera.capture_sequence(['../html/ramdisk/buffer.jpg'],'jpeg',True,None,0,False,thumbnail=None)
	writeTelemetry()
