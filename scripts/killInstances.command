#!/bin/sh

#Find the Process ID for syncapp running instance
for id in `ps -axcopid,command | grep -i -e $1 | awk '{ print $1 }'`; do
  kill -9 $id
done
