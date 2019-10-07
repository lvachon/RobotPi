import Adafruit_ADS1x15
adc = Adafruit_ADS1x15.ADS1115()
raw = adc.read_adc(0,1)
volt = 10. * raw/8192.
print(volt)
