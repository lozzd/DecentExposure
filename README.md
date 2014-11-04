# DecentExposure
## What is DecentExposure?
DecentExposure is a tool to calculate and display your maximum exposure time for a given service (or all services) in Nagios.

The aim is to communicate to the user how long it could potentially take Nagios to send the notification in the event a service breaks. (Your 'exposure')

The idea of knowing your sensitivity to alerts for supervisory monitoring (e.g. supervisor sensitivity) has been discussed at length; for further reading, check out [these slides](http://qconlondon.com/dl/qcon-london-2012/slides/JohnAllspaw_FaultToleranceAnomalyDetectionAndAnticipationPatterns.pdf) from [John Allspaw](http://twitter.com/allspaw) on the subject. 

## Features

* The tool is very simple: Give it the path to your Nagios installation's status.dat and you can ask it your maximum exposure time for either a given hostname/service, or all of your services. 
* If you want, you can pass the '-d' flag to show how the exposure was calculated, allowing you to fine tune your exposure. 

## Examples

**Detailed exposure for all services**

    [nagios:~/DecentExposure] $ ./expose.php -a -d | head 
    Calculating exposure for all services...
    Service 'Ping_High_Latency' on 'Abv-ptp1' could take a maximum of 476.2 seconds to notify on failure
    Calculation: 
    30 seconds check interval + (60 seconds retry interval * (8 max attempts - 1)) + (2.377 seconds check latency * 8 max attempts) + (0.898 seconds execution time * 8 max attempts) = 476.2
    
    Service 'Ping_High_Latency' on 'Abv-ptp2' could take a maximum of 475.864 seconds to notify on failure
    Calculation: 
    30 seconds check interval + (60 seconds retry interval * (8 max attempts - 1)) + (2.348 seconds check latency * 8 max attempts) + (0.885 seconds execution time * 8 max attempts) = 475.864

**Example of simple output for a single service**

    [nagios:~/DecentExposure] $ ./expose.php -h dbshard01.ny4 -s "Disk Space"
    Service 'Disk Space' on 'dbshard01.ny4' could take a maximum of 421.953 seconds to notify on failure

**Detailed exposure for a single service**

    [nagios:~/DecentExposure] $ ./expose.php -h dbshard01.ny4 -s "Disk Space" -d
    Service 'Disk Space' on 'dbshard01.ny4' could take a maximum of 427.794 seconds to notify on failure
    
    Calculation: 
    300 seconds check interval + (60 seconds retry interval * (3 max attempts - 1)) + (2.569 seconds check latency * 3 max attempts) + (0.029 seconds execution time * 3 max attempts) = 427.794

## Prerequisites

* Nagios (any version should be OK, and probably Icinga as well)
* PHP 4 (or higher)

## Instalation/configuration

1. **Download/Clone** this repo onto your Nagios box (DecentExposure has to run on your Nagios box as it parses status.dat directly)
2. **Open expose.php** in your favourite text editor
3. **Set the path to your Nagios status.dat file** ($status\_file). You can find this in your Nagios config file under "status\_file" 
4. **Ensure the $nagios\_interval variable  matches your Nagios configuration**. This doesn't often change (default is 60, as in one 'interval' is a minute), but you can find it under "interval\_length" in your Nagios config file. 

## Running/Usage

DecentExposure has full usage information built in. Here is a copy:

    exposure.php - DecentExposure
    
    Calculates and displays your maximum exposure time for all services in Nagios or a given service
    The aim is to communicate to the user how long it could potentially take Nagios to page in the event a service breaks. (Your 'exposure')
    
    Usage:
    -a    - Calculate the exposure for all services
    -d    - Show extra detail about the calculation
    
    If you wish to check just one service, the following flags are relevant:
    -h    - Define a hostname to look for the service defined using -s on
    -s    - Define a service name to show the exposure for


See the "Examples" section above for examples of ways you can run DecentExposure.
