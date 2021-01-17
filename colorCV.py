#! /usr/bin/python

import numpy as np
import cv2

frame = cv2.imread('./html/ramdisk/frame.jpg')
width = frame[0].size/3
height = frame.size / frame[0].size
tw = 64
th = 64
tx = width/2 - tw/2
ty = height/2 - th/2
track_window = (tx, ty, tw, th)

# set up the ROI for tracking
roi = frame[ty:ty+th, tx:tx+tw]
hsv_roi =  cv2.cvtColor(frame, cv2.COLOR_BGR2HSV)
mask = cv2.inRange(hsv_roi, np.array((45., 60.,64.)), np.array((135.,255.,255.)))
roi_hist = cv2.calcHist([hsv_roi],[0],mask,[180],[0,180])
cv2.normalize(roi_hist,roi_hist,0,255,cv2.NORM_MINMAX)

# Setup the termination criteria, either 10 iteration or move by atleast 1 pt
term_crit = ( cv2.TERM_CRITERIA_EPS | cv2.TERM_CRITERIA_COUNT, 10, 1 )

while(1):
    frame = cv2.imread('./html/ramdisk/frame.jpg')
    if frame.size>0:
        hsv = cv2.cvtColor(frame, cv2.COLOR_BGR2HSV)
        dst = cv2.calcBackProject([hsv],[0],roi_hist,[0,180],1)
        # apply meanshift to get the new location
        ret, new_track_window = cv2.meanShift(dst, track_window, term_crit)
        if new_track_window[2]>0 and new_track_window[3]>0:
            # Draw it on image
            x,y,w,h = track_window
            img2 = cv2.rectangle(frame, (x,y), (x+w,y+h), 255,2)
            track_window=new_track_window;
        else:
            print("NO BLUE")
        cv2.imwrite('./html/ramdisk/tracking.jpg',frame,  [int(cv2.IMWRITE_JPEG_QUALITY), 25])
    else:
        break


