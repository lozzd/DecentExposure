<?php

function ParseStatus($file) {
    // Takes a filename and parses the status.dat into a usable array. 

    if (!is_readable($file)) {
        return array("success" => false, "error" => "Could not read status file {$file}!");
    }

    $nagiosStatus = file($file);

    $in = false;
    $type = "unknown";
    $status = array();
    $host = null;

    // Cunning and fairly efficient parsing of status.dat courtesy of saz (http://saz.sh/)
    $lineCount = count($nagiosStatus);
    for($i = 0; $i < $lineCount; $i++) {
        if(false === $in) {
            $pos = strpos($nagiosStatus[$i], "{");
            if (false !== $pos) {
                $in = true;
                $type = substr($nagiosStatus[$i], 0, $pos-1);
                if(!empty($status[$type])) {
                    $arrPos = count($status[$type]);
                } else {
                    $arrPos = 0;
                }
                continue;
            }
        } else {
            $pos = strpos($nagiosStatus[$i], "}");
            if(false !== $pos) {
                $in = false;
                $type = "unknown";
                continue;
            }

            // Line with data found
            list($key, $value) = explode("=", trim($nagiosStatus[$i]), 2);
            if("hoststatus" === $type) {
                if("host_name" === $key) {
                    $host = $value;
                }
                $status[$type][$host][$key] = $value;
            } else {
                $status[$type][$arrPos][$key] = $value;
            }
        }
    }
    return $status;

}

function calculateExposure(array $values) {
    // Do the maths to calculate the exposure given an array of a service from status.dat
    global $nagios_interval;

    $service_name = $values['service_description'];
    $hostname = $values['host_name'];

    // Protect against invalid entries in service.dat
    if ($values['check_interval'] == "") {
        return array("success" => false, "error" => "The service {$service_name} on {$hostname} was malformed in the status file. Skipping.");
    }

    // First, turn the data that is in Nagios 'intervals' into seconds. 
    $check_interval = $values['check_interval'] * $nagios_interval;
    $retry_interval = $values['retry_interval'] * $nagios_interval;

    $max_attempts = $values['max_attempts'];
    $latency = $values['check_latency'];
    $execution_time = $values['check_execution_time'];

    // The important part: 
    // Any service can take a maximum of the check interval
    // Then add the retry interval multiplied by the number of remaining attempts (e.g. max attempts minus 1)
    // Then add the current latency Nagios is experiencing checking the service, multiplied by the max attempts
    // Then add the time it takes to run the plugin, multiplied by the number of attempts. 
    $seconds = $check_interval + ($retry_interval * ($max_attempts - 1)) + ($latency * $max_attempts) + ($execution_time * $max_attempts);

    $simple_output = "Service '$service_name' on '$hostname' could take a maximum of $seconds seconds to notify on failure";
    $detailed_output = "$check_interval seconds check interval + ($retry_interval seconds retry interval * ($max_attempts max attempts - 1)) + " .
        "($latency seconds check latency * $max_attempts max attempts) + ($execution_time seconds execution time * $max_attempts max attempts) = $seconds";

    return array("success" => true, "simple" => $simple_output, "detailed" => $detailed_output);

}

function getExposureForService($host_name, $service_name, $config) {
    // Returns an array with the exposure information for a given service 
    // having been provided with the hostname and service name. 

    $status = ParseStatus($config);

    $found = false;
    foreach ($status['servicestatus'] as $k => $v) {
        if ($v['host_name'] == $host_name && $v['service_description'] == $service_name) {
            $exposure = calculateExposure($v);
            $found = true;
        }
    }
    if ($found) {
        if ($exposure['success']) {
            return $exposure;
        } else {
            return false;
        }
    } else {
        return array("success" => false, "error" => "Service name {$service_name} on {$host_name} was not found!");
    }
}

function getExposureForAll($config) {
    // Returns an array with the exposure information for all services
    // in service.dat. 

    $status = ParseStatus($config);

    foreach ($status['servicestatus'] as $k => $v) {
        $exposure[$k] = calculateExposure($v);
    }

    return $exposure;
}


function PrintUsage($error = "") {
    // Prints usage information. 

    if ($error != "") {
        echo "Error: {$error}\n\n";
    }
    echo "exposure.php - DecentExposure\n\n";
    echo "Calculates and displays your maximum exposure time for all services in Nagios or a given service\n";
    echo "The aim is to communicate to the user how long it could potentially take Nagios to page in the event a service breaks. (Your 'exposure')";
    echo "\n\n";
    echo "Usage:\n";
    echo "-a    - Calculate the exposure for all services\n";
    echo "-d    - Show extra detail about the calculation\n\n";
    echo "If you wish to check just one service, the following flags are relevant:\n";
    echo "-h    - Define a hostname to look for the service defined using -s on\n";
    echo "-s    - Define a service name to show the exposure for\n\n";
    exit(2);
}

