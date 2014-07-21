#!/bin/env php
<?php

// DecentExposure - Calculates and displays your maximum exposure time for all services in Nagios
//                  or a given service. 
//                  The aim is to communicate to the user how long it could potentially take Nagios
//                  to page in the event a service breaks. (Your 'exposure')

// Config:
// Path to your Nagios status.dat file.
// This is the value of "status_file" in your nagios.cfg
$status_file = "/usr/nagios/var/status.dat";

// You need to set this to the same value as "interval_length" in your nagios.cfg. 
// This value is normally 60 (e.g. one nagios 'interval' is one minute)
$nagios_interval = 60;

// Depending on how large your status.dat is, you may need to increase this. 
ini_set('memory_limit', '512M');

// Reasonable level of PHP error reporting. 
error_reporting(E_ALL & ~E_NOTICE);

// End Config


require_once 'lib.php';

$short_opts = "ah:s:d";
$opts = getopt($short_opts);

if (!isset($opts['a']) && ( !isset($opts['h']) || !isset($opts['s']) ) ) {
    PrintUsage();
}
$detailed = (isset($opts['d'])) ? true : false;

if (isset($opts['a'])) {
    // User wants to print exposure for all services. 
    echo "Calculating exposure for all services...\n";
    $results = getExposureForAll($status_file); 
    foreach ($results as $result) {
        if ($result['success']) {
            echo $result['simple'] . "\n";
            if ($detailed) {
                echo "Calculation: \n{$result['detailed']}\n\n";
            }
        } else {
            echo $result['error'] . "\n";
        }
    }
} else {
    // User wants just one service
    $host_name = $opts['h'];
    $service_name = $opts['s'];

    $results = getExposureForService($host_name, $service_name, $status_file);
    if ($results['success']) {
        echo $results['simple'] . "\n";
        if ($detailed) {
            echo "\nCalculation: \n{$results['detailed']}\n";
        }
    } else {
        echo "The calculation of exposure for service {$service_name} on {$host_name} failed. Sorry.\n";
        echo "Error: {$results['error']}\n";
    }
}

