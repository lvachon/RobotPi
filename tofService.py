#!/usr/bin/env python3
import time
import socket
import board
from digitalio import DigitalInOut
from adafruit_vl53l0x import VL53L0X

# Initialize I2C bus and sensor.
enablePin = DigitalInOut(board.D16)
enablePin.switch_to_output(value=False);
i2c = board.I2C()
#Turn off left tof (0x30)
enablePin.value = False
time.sleep(0.1)
#Grab right tof (0x31 or 0x29)
try:
  tofR = VL53L0X(i2c,0x29)
  tofR.set_address(0x31)
except:
  print("Already changed!\n")
  tofR = VL53L0X(i2c,0x31)
print(tofR)
#turn left tof back on
enablePin.value = True
time.sleep(0.1)
tofL = VL53L0X(i2c,0x29)
print(tofL)
server = socket.socket(socket.AF_INET, socket.SOCK_DGRAM, socket.IPPROTO_UDP)
server.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEPORT, 1)
server.settimeout(0.2)

while True:
    lRange = tofL.range
    rRange = tofR.range
    print("Range: {0}mm, {1}mm".format(lRange,rRange))
    server.sendto("{0},{1}".format(lRange,rRange).encode(),('127.0.0.1',8888))
    time.sleep(1)
