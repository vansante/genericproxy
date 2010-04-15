#!/bin/sh

echo ps ax | egrep '/usr/sbin/maradns' | awk '{print $1}'

