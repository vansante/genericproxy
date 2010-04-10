#!/bin/sh

# Set some vars
ZONE=wleiden.net
SERVER=195.169.86.131
APP=/usr/local/bin/fetchzone
TMPFILE=/tmp/dns-${ZONE}.tmp
REALFILE=/var/etc/maradns/db.${ZONE}
LOGFILE=/var/log/fetchzone.log
PIDFILE=/var/run/fetchzone.pid
IDLE=3600

# Create logging service
log() {
  echo `date "+%b %e %T"`":" $* >> ${LOGFILE}
}

# Register PID
PID=$$
echo ${PID} > ${PIDFILE}

# No zone file to compare with, so lets make a dummy
touch ${REALFILE}

# Tell logfile that I am starting
log "[INFO] Fetchzone starting with PID: $PID"

# Make sure I never die
while [ true ]; do 

  # Execute Fetchzone
  ${APP} ${ZONE} ${SERVER} > ${TMPFILE}

  # Did Fetchzone exit unhappy
  if [ $? -eq 0 ]; then

    # Are there any changes?
    COMM=`comm -23 ${TMPFILE} ${REALFILE}`
    if [ -n "$COMM" ]; then

      # If there are any changes copy tmp to realfile
      log "[INFO] Changes found"
      cp ${TMPFILE} ${REALFILE}
    fi
  else

    # Something went wrong lets log it
    log "[ERROR] Errors found in fetchzone query"
  fi

  # Lets go to sleep
  sleep ${IDLE}
done
