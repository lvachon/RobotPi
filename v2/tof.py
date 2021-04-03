import board
import digitalio
import time
from adafruit_vl53l0x import VL53L0X
from blinky import status,settings

tofL=0
tofR=0
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
def avoid(tof):
	global status,settings
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
