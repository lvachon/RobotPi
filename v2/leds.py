import board
import digitalio

led = digitalio.DigitalInOut(board.D5)
led.direction = digitalio.Direction.OUTPUT

uvLed = digitalio.DigitalInOut(board.D19)
uvLed.direction = digitalio.Direction.OUTPUT
def ledOn():
	led.value=True
def ledOff():
	led.value=False
def uvLedOn():
        uvLed.value=True
def uvLedOff():
        uvLed.value=False
