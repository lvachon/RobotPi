#!/usr/bin/env python3
import FaBo9Axis_MPU9250
from haversine import haversine, Unit
from util import *
import json

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

#18.221 62.345 48.369 90.085
minX=18.221
maxX=62.345
minY=48.369
maxY=90.085
mpu9250 = FaBo9Axis_MPU9250.MPU9250()

def compass():
        global minX, minY, maxX, maxY, mpu9250
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

def autopilot():
	global waypoints, currentWaypoint, status, settings
	gps = getGPS()
	if(gps==False):
		print("No GPS")
		return "s"
	heading = compass()
	if(len(waypoints)<1):
		print("No waypoints")
		return "s"
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
				print("Going to first one")
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

def readWaypoints():
	global waypointsMod, waypoints, status, currentWaypoint
	if(os.path.getmtime('../html/waypoints')<=waypointsMod):
		return
	f = open('../html/waypoints')
	try:
		waypoints = json.load(f)
		currentWapoint=0
		status['target']=currentWaypoint
	except Exception as e:
		print(e)

	f.close()
	waypointsMod=time.time()

waypoints = [(42.107582,-71.034714),(42.107684,-71.034672)] 
currentWaypoint = 0
waypointsMod = 0