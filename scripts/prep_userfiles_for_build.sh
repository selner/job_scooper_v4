#!/usr/bin/env bash
########################################################
###
### Add any user files to the image
###
########################################################
echo ""
echo ""
echo "************************************************"
echo "*** Configuring scoop_docker.sh file setup. *** "
echo "************************************************"
echo ""

USERFILES=`pwd`/userfiles

echo "Listing directory to check if $USERFILES/scoop_docker.sh exists to copy to image..."
ls -al $USERFILES
echo ""
[ -f $USERFILES/scoop_docker.sh ] && echo "Using user-specific version of scoop_docker.sh"
echo ""

MISSINGMSG="Missing $USERFILES/scoop_docker.sh script file to run."
[ ! -f $USERFILES/scoop_docker.sh ] && echo $MISSINGMSG > $USERFILES/scoop_docker.sh && echo $MISSINGMSG
echo ""

chmod +x $USERFILES/*.sh
echo "Listing directory to visually verify $USERFILES/scoop_docker.sh now exists to be copied to image..."
ls -al $USERFILES

echo ""
echo "************************************************"
echo "*** scoop_docker.sh file setup completed. ***"
echo "************************************************"
echo ""
echo ""


