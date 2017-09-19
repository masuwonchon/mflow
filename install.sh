#!/bin/sh
#################################################################
#
# install.sh [MFLOW]
# Author: masuwonchon@gmail.com
#
#################################################################

VERSION=1.0.0
NFSEN_CONF_OVERWRITE=""
INSTALL_APPLICATION=0
INSTALL_PLUGIN=0
INSTALL_NFSEN=0
ARGS=$@
MY_LOC=""
MFLOW_CONF=""

err () {
    printf "[ERROR] ${*}\n"
    exit 1
}

err_line () {
    echo "-----"
    printf "[ERROR] ${*}\n"
    exit 1
}

help () {
    echo "[Options]"
    echo "-h : help"
    echo "-a : Install application only"
    echo "-p : Install plugin only"
    echo "-n : Install nfsen"
    echo "-f : Install nfdump"
}

check_opts() {
    APPLICATION_ONLY=0
    PLUGIN_ONLY=0
    OPTS=0

    while getopts ":apdnsh:" opt; do
	case $opt in
	    a)
		OPTS=1
		INSTALL_APPLICATION=1
		;;
	    p)
		OPTS=1
		INSTALL_PLUGIN=1
		;;
	    s)
		OPTS=1
		INSTALL_NFSEN=1
		;;
	    h)
		help
		exit
		;;
	    \?)
		err "Invalid option: -$OPTARG"
		exit 1
		;;
	    :)
		err "Option -$OPTARG requires an argument."
		exit 1
		;;
	esac
    done

    return 0
}

Install_nfdump(){
    apt-get -yq install nfdump
    apt-get -yq install apache2 
    apt-get -yq install libapache2-mod-php
    apt-get -yq install php-common
    apt-get -yq install php-mbstring
    apt-get -yq install php-sqlite3
    apt-get -yq install php-curl
    apt-get -yq install php-xml
    apt-get -yq install libyaml-appconfig-perl
    apt-get -yq install libsocket6-perl
    apt-get -yq install libmailtools-perl 
    apt-get -yq install librrds-perl
}

Install_nfsen(){
    NFSEN=nfsen-1.3.7
    NFSEN_CONF=./$NFSEN/etc/nfsen.conf
    APACHE_DEFAULT_CONF=/etc/apache2/sites-available/000-default.conf
    APACHE_DIR_CONF=/etc/apache2/mods-available/dir.conf
    NFSEN_VARFILE=/tmp/nfsen-tmp.conf

    # install nfsen
    cd $NFSEN
    ./install.pl etc/nfsen.conf
    cd ..

    # Parse nfsen.conf file
    cat $NFSEN_CONF | grep -v \# | egrep '\$BASEDIR|\$BINDIR|\$LIBEXECDIR|\$HTMLDIR|\$FRONTEND_PLUGINDIR|\$BACKEND_PLUGINDIR|\$WWWGROUP|\$WWWUSER|\$USER' | tr -d ';' | tr -d ' ' | cut -c2- | sed 's,/",",g' > ${NFSEN_VARFILE}
    . ${NFSEN_VARFILE}
    rm -rf ${NFSEN_VARFILE}

    # Start nfsen
    $BINDIR/nfsen start

    # Site set file
    if grep "/var/www/nfsen" ${APACHE_DEFAULT_CONF} > /dev/null; then
	echo "Found '/var/www/nfsen' in ${APACHE_DEFAULT_CONF}; assuming it is already configured"
    else
	sed -i.tmp '/DocumentRoot/a \\tAlias /nfsen /var/www/nfsen' ${APACHE_DEFAULT_CONF}
    fi

    if grep "nfsen.php" ${APACHE_DIR_CONF} > /dev/null; then
	echo "Found '/var/www/nfsen' in ${APACHE_DIR_CONF}; assuming it is already configured"
    else
	sed -i.tmp '/DirectoryIndex/a \\tDirectoryIndex nfsen.php' ${APACHE_DIR_CONF}
    fi


    # Restart apache
    /etc/init.d/apache2 restart
}

check_php(){
    # Check PHP dependencies
    PHP_CURL=$(php -m | grep 'curl' 2> /dev/null)
    PHP_JSON=$(php -m | grep 'json' 2> /dev/null)
    PHP_MBSTRING=$(php -m 2> /dev/null | grep 'mbstring')
    PHP_PDOSQLITE=$(php -m 2> /dev/null | grep 'pdo_sqlite$') # The dollar-sign ($) makes sure that 'pdo_sqlite2' is not accepted
    PHP_SOCKETS=$(php -m 2> /dev/null | grep '^sockets$')
    PHP_XML=$(php -m 2> /dev/null | grep '^xml$')

    if [ "$PHP_CURL" != "curl" ]; then
	err "The PHP 'CURL' module is missing.\nDon't forget to restart your Web server after installing the package."
    elif [ "$PHP_JSON" != "json" ]; then
	err "The PHP 'JSON' module is missing.\nDon't forget to restart your Web server after installing the package."
    elif [ "$PHP_MBSTRING" != "mbstring" ]; then
	err "The PHP 'mbstring' module is missing.\nDon't forget to restart your Web server after installing the package."
    elif [ "$PHP_PDOSQLITE" != "pdo_sqlite" ]; then
	err "The PHP PDO SQLite v3 module is missing.\nDon't forget to restart your Web server after installing the package."
    elif [ "$PHP_SOCKETS" != "sockets" ]; then
	err "The PHP 'sockets' module is missing.\nDon't forget to restart your Web server after installing the package."
    elif [ "$PHP_XML" != "xml" ]; then
	err "The PHP 'xml' module is missing.\nDon't forget to restart your Web server after installing the package."
    fi
}

Get_nfsen_config(){
    NFSEN_TMP=/tmp/nfsen-tmp.conf
    if [ ! -n "$(ps axo command | grep [n]fsend | grep -v nfsend-comm)" ]; then
	err "NfSen - nfsend not running; cannot detect nfsen.conf location. Exiting..."
    fi

    NFSEN_LIBEXECDIR=$(cat $(ps axo command= | grep -vE "(nfsend-comm|grep)" | grep -Eo "[^ ]+nfsend") | grep libexec | cut -d'"' -f2 | head -n 1)

    if [ -z "${NFSEN_CONF_OVERWRITE}" ]; then
	NFSEN_CONF=$(cat ${NFSEN_LIBEXECDIR}/NfConf.pm | grep \/nfsen.conf | cut -d'"' -f2)
    else
	NFSEN_CONF=$NFSEN_CONF_OVERWRITE
    fi

    # Parse nfsen.conf file
    cat ${NFSEN_CONF} | grep -v \# | egrep '\$BASEDIR|\$BINDIR|\$LIBEXECDIR|\$HTMLDIR|\$FRONTEND_PLUGINDIR|\$BACKEND_PLUGINDIR|\$WWWGROUP|\$WWWUSER|\$USER' | tr -d ';' | tr -d ' ' | cut -c2- | sed 's,/",",g' > ${NFSEN_TMP}
    . ${NFSEN_TMP}
    rm -rf ${NFSEN_TMP}
    MFLOW_CONF=${FRONTEND_PLUGINDIR}/mflow/config.php

}

Install_plugin(){
    echo "[DEBUG] Installing Mflow plugin ${VERSION} to ${BACKEND_PLUGINDIR}/plugin/mflow"

    # Check permissions to install mflow - you must be ${USER} or root
    if [ "$(id -u)" != "$(id -u ${USER})" ] && [ "$(id -u)" != "0" ]; then
	err "You do not have sufficient permissions to install mflow on this machine!"
    fi

    if [ "$(id -u)" = "$(id -u ${USER})" ]; then
	WWWUSER=${USER}     # we are installing as normal user
    fi

    cp -r ./plugin/* ${BACKEND_PLUGINDIR}
    echo "[INFO] Install plugin successfully"

    chown -R ${USER}:${WWWGROUP} ${BACKEND_PLUGINDIR}/mflow*
}

Install_application(){
    echo "Installing Mflow application ${VERSION} to ${FRONTEND_PLUGINDIR}/mflow"
    cp -r ./application/* ${FRONTEND_PLUGINDIR}
}

Configure_mflow(){
    # Set permissions - owner and group
    echo "Setting plugin file permissions - user \"${USER}\" and group \"${WWWGROUP}\""
    chown -R ${USER}:${WWWGROUP} ${FRONTEND_PLUGINDIR}/mflow*
    chmod -R g+w ${FRONTEND_PLUGINDIR}/mflow/db

    # Update plugin configuration file - config.php. We use ',' as sed delimiter instead of escaping all '/' to '\/'.
    echo "Updating plugin configuration file ${MFLOW_CONF}"

    # Check for proxy and update config.php accordingly
    PROXY=$(env | grep -i http_proxy | awk '{ START=index($0,"=")+1; print substr($0,START) }')
    if [ "$PROXY" != "" ]; then
	echo "HTTP proxy detected"
	sed -i.tmp "s,config\['use_proxy'\] = 0,config\['use_proxy'\] = 1,g" ${MFLOW_CONF}
	
	PROXY_IP_PORT=$(echo ${PROXY} | awk '{ FROM=index($0,"//")+2; print substr($0,FROM) }')
	PROXY_IP=$(echo ${PROXY_IP_PORT} | awk '{ TO=index($0,":")-1; print substr($0,0,TO) }')
	PROXY_PORT=$(echo ${PROXY_IP_PORT} | awk '{ FROM=index($0,":")+1; print substr($0,FROM) }')
	
	OLD_PROXY_IP=$(grep "$config\['proxy_ip'\]" ${MFLOW_CONF} | cut -d'"' -f2)
	OLD_PROXY_PORT=$(grep "$config\['proxy_port'\]" ${MFLOW_CONF} | awk '{ FROM=index($0,"=")+2; TO=index($0,";"); print substr($0,FROM,TO-FROM) }')
	
	sed -i.tmp "s,${OLD_PROXY_IP},${PROXY_IP},g" ${MFLOW_CONF}
	sed -i.tmp "s,${OLD_PROXY_PORT},${PROXY_PORT},g" ${MFLOW_CONF}
    fi

    # Get my location information
    cd ${FRONTEND_PLUGINDIR}/mflow
    MY_LOC=$(php getmyipaddress.php | grep config_data | cut -d'>' -f2 | cut -d'<' -f1)
    echo "Geocoding plugin location - ${MY_LOC}"
    cd - > /dev/null

    # Fill my location in plugin configuration file
    if [ "${MY_LOC}" != "(UNKNOWN),(UNKNOWN),(UNKNOWN),(UNKNOWN),(UNKNOWN)" ]; then
	OLDENTRY=$(grep internal_domains ${MFLOW_CONF} | cut -d'"' -f 6)
	sed -i.tmp "s/${OLDENTRY}/$(echo ${MY_LOC} | cut -d',' -f1)/g" ${MFLOW_CONF}

	OLDENTRY=$(grep internal_domains ${MFLOW_CONF} | cut -d'"' -f 10)
	NEWENTRY=$(echo ${MY_LOC} | cut -d',' -f2)
	if [ "${NEWENTRY}" = "(UNKNOWN)" ]; then
	    NEWENTRY=""
	fi
	sed -i.tmp "s/${OLDENTRY}/${NEWENTRY}/g" ${MFLOW_CONF}

	OLDENTRY=$(grep internal_domains ${MFLOW_CONF} | cut -d'"' -f 14)
	NEWENTRY=$(echo ${MY_LOC} | cut -d',' -f3)
	if [ "${NEWENTRY}" = "(UNKNOWN)" ]; then
	    NEWENTRY=""
	fi
	sed -i.tmp "s/${OLDENTRY}/${NEWENTRY}/g" ${MFLOW_CONF}

	OLDENTRY=$(grep "$config\['map_center'\]" ${MFLOW_CONF} | cut -d'"' -f2)
	NEWENTRY=$(echo ${MY_LOC} | cut -d',' -f4,5)
	if [ "${NEWENTRY}" != "(UNKNOWN),(UNKNOWN)" ]; then
	    sed -i.tmp "s/${OLDENTRY}/${NEWENTRY}/g" ${MFLOW_CONF}
	fi
    fi

    # Enable plugin
    OLDENTRY=$(grep \@plugins ${NFSEN_CONF})

    if grep "mflow" ${NFSEN_CONF} > /dev/null; then
	echo "Found 'mflow' in ${NFSEN_CONF}; assuming it is already configured"
    else
	echo "Updating NfSen configuration file ${NFSEN_CONF}"
	
	# Check whether we are running Linux of BSD (BSD sed does not support inserting new lines (\n))
	if [ $(uname) = "Linux" ]; then
	    sed -i.tmp "/mflow/d" ${NFSEN_CONF}
	    sed -i.tmp "s/${OLDENTRY}/${OLDENTRY}\n    \[ 'live', 'mflow' ],/g" ${NFSEN_CONF}
	else # Something else (we assume *BSD)
	    sed -i.tmp "s/${OLDENTRY}/${OLDENTRY}\ \[ 'live', 'mflow' ],/g" ${NFSEN_CONF}
	fi
    fi
}

Restart_services(){
    /usr/share/nfsen/bin/nfsen reload
}


Main(){

    check_opts $ARGS
    echo "[DEBUG] INSTALL_NFSEN: $INSTALL_NFSEN"
    echo "[DEBUG] INSTALL_APPLICATION: $INSTALL_APPLICATION"
    echo "[DEBUG] INSTALL_PLUGIN: $INSTALL_PLUGIN"

    if [ $INSTALL_NFSEN = 1 ]; then
	Install_nfdump
	Install_nfsen
    fi

    if [ $INSTALL_PLUGIN = 1 ]; then
	Get_nfsen_config
	Install_plugin
        Restart_services
    fi

    if [ $INSTALL_APPLICATION = 1 ]; then
        check_php
	Get_nfsen_config
	Install_application
        Configure_mflow
    fi

}

Main

