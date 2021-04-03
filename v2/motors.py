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