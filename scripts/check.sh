#!/bin/sh

COMMAND='fping'
ISTHERE=`command -v $COMMAND > /dev/null >&1`

echo $ISTHERE
echo "To install, run install fping"
