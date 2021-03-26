#!/usr/bin/env python3

from picamera.array import PiRGBArray
from picamera import PiCamera
import numpy as np
import math
import time
import json
import os
import board
import digitalio
import FaBo9Axis_MPU9250
from haversine import haversine, Unit
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
	camera.capture_sequence([frame],'rgb',True,(64,48))
	return frame
def getGPS():
	try:
		f = open('/home/pi/RobotPi/html/ramdisk/gpsdata')
		gpsData = json.load(f)
		f.close()
		if(gpsData['time']<time.time()-5):
			print("No fix/old fix")
			gpdData=false
	except:
		if(f):f.close()
		gpsData = False #{'lat':42,'lon':-71}
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
	return [lRange,rRange]

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

def readSettings():
	global settingsMod
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
	status['time']=time.strftime('%H:%M:%S')
	f = open('../html/ramdisk/telemetry','w')
	jstring = repr(status).replace("'","\"")
	f.write(jstring)
	f.close()
	os.system("printf \"AT+SEND=0,"+str(len(jstring))+","+jstring+"\\r\\n\" > /dev/serial0")


#18.221 62.345 48.369 90.085
minX=18.221
maxX=62.345
minY=48.369
maxY=90.085

def compass():
        global minX
        global minY
        global maxX
        global maxY
        mag = mpu9250.readMagnet()
        if(mag['y']>maxY):maxY=mag['y']
        if(mag['y']<minY):minY=mag['y']
        if(mag['x']>maxX):maxX=mag['x']
        if(mag['x']<minX):minX=mag['x']
        if(minX==maxX or minY==maxY): return 0
        x = 2*(mag['x']-minX)/(maxX-minX) - 1
        y = 2*(mag['y']-minY)/(maxY-minY) - 1
        hdn = 180.0 * math.atan2(x,y)/math.pi - 90
        if(hdn<0):hdn+=360
        return hdn

def bearingToPoint(srcLat,srcLon,destLat,destLon):
	srcLat*=math.pi/180.0
	srcLon*=math.pi/180
	destLat*=math.pi/180
	destLon*=math.pi/180
	x = math.cos(destLat)*math.sin(destLon-srcLon)
	y = math.cos(srcLat)*math.sin(destLat)-math.sin(srcLat)*math.cos(destLat)*math.cos(destLon-srcLon)
	b = 180 * math.atan2(x,y)/math.pi
	if(b<0):b+=360
	return b

waypoints = [(42.107582,-71.034714),(42.107684,-71.034672)] 
currentWaypoint = 0
def autopilot():
	global waypoints
	global currentWaypoint
	gps = getGPS()
	if(gps==False):
		print("No GPS")
		return "s"
	heading = compass()
	dist = haversine((gps['lat'],gps['lon']),waypoints[currentWaypoint],Unit.METERS)
	status['dist']=dist
	status['heading']=heading
	status['target']=currentWaypoint
	if(gps['hdop']<5):
		if(dist<5*gps['hdop']):
			print("At waypoint")
			if(currentWaypoint+1<len(waypoints)):
				print("Going to next one")
				currentWaypoint+=1
			else:
				currentWaypoint=0
		else:
			desiredBearing = bearingToPoint(gps['lat'],gps['lon'],waypoints[currentWaypoint][0],waypoints[currentWaypoint][1])
			bearingDiff = desiredBearing - heading
			if(bearingDiff>180):bearingDiff-=360
			if(bearingDiff<-180):bearingDiff+=360
			if(bearingDiff>30): return "fr"
			if(bearingDiff<-30): return "fl"
			return "f"
	else:
		print("Too innacurate to move")
		return "s"

print("Init params")
settings = {'srcThresh':32,'backLimit':3, 'minDist':750,'navMode':'GPS'}
status = {'backCount':0}
tofL=0
tofR=0
settingsMod=0

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
humanWidth=640
humanHeight=480
camera = PiCamera()
camera.resolution = (humanWidth, humanHeight)
camera.framerate = 4
awb()
initTof()
mpu9250 = FaBo9Axis_MPU9250.MPU9250()


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
	print(settings)
	print(status)
	camera.capture_sequence(['../html/ramdisk/frame.jpg'],'jpeg',True,None,0,False,thumbnail=None)
	writeTelemetry()
