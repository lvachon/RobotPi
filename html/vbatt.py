import RPi.GPIO as GPIO
import time
GPIO.setmode(GPIO.BCM)
GPIO.setup(26, GPIO.IN)
GPIO.wait_for_edge(26, GPIO.FALLING)
ot = time.time()
GPIO.wait_for_edge(26, GPIO.RISING)
GPIO.wait_for_edge(26, GPIO.FALLING)
dt = (time.time()-ot);
#5 = 0.011
#0 = 0.008
#print (dt)
print ((dt-0.008)/0.0006)

