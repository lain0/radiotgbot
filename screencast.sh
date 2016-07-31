#!/bin/bash
DATE=`date +%Y-%m-%d`
echo $DATE
ffmpeg -f alsa -ac 2 -i hw:0,0 -acodec pcm_s16le -f x11grab -s 1366x768 -r 25 -i :0.0 -vcodec qtrle ./"$DATE"_screencast.mov
