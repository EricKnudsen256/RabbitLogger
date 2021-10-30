#!/usr/bin/bash

sudo cp 490Logger.service /lib/systemd/system/490Logger.service

sudo systemctl daemon-reload

sudo systemctl enable 490Logger.service 
sudo systemctl start 490Logger.service 

sudo systemctl status 490Logger.service

