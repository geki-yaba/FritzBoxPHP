<?php
declare(strict_types=1);

require_once('fritzbox.inc');



# user and password
# - user is optional for default setup; simply set any name
# - otherwise use FritzBox-User with read/write access
$user = "admin";
$pass = "passw";



# fritz!box soap server
$base_uri = "https://fritz.box:49443";

# description of services
$desc_igd = "igddesc.xml";
$desc_tr64 = "tr64desc.xml";

# function signatures, variables and its data types
$scpd_conn = "igdconnSCPD.xml";
$scpd_dect = "x_dectSCPD.xml";
$scpd_ddns = "x_remoteSCPD.xml";
$scpd_info = "deviceinfoSCPD.xml";

function fbSoapClient(string &$desc, string &$scpd)
{
    global $base_uri, $user, $pass;

    # receive service description
    $service = \FritzBox\getServiceData($base_uri, $desc, $scpd);

    if ($service === false)
    {
        throw new Exception("no service found in ". $scpd);
    }

    #print_r($service);

    # set user and password
    $service['login'] = $user;
    $service['password'] = $pass;

    # receive variables and its data types belonging to action
    #$action = "GetInfo";
    #$stateVars = \FritzBox\getStateVars($base_uri, $service, $action);

    #if ($stateVars === false)
    #{
    #    throw new Exception("no state variables belonging to $action");
    #}

    #print_r($stateVars);

    # create soap client
    return \FritzBox\soapClient($service);
}

function fbDectTelephones(&$client)
{
    # function to execute
    $action = "GetNumberOfDectEntries";

    # execute action published by service
    $noOfTelephones = (int)\FritzBox\soapCall($client, $action);

    echo "no of dect telephones: ". $noOfTelephones . PHP_EOL;

    $action = "GetGenericDectEntry";

    for($i = 0; $i < $noOfTelephones; $i++)
    {
        $params = array('NewIndex' => (int)$i);

        $result = \FritzBox\soapCall($client, $action, $params);

        $line = ($result['NewActive'] != 0) ? "busy" : "open";

        echo "name(". $result['NewName'] .") id(". $result['NewID'] .") line(". $line .")" . PHP_EOL;
    }
}

function fbDDNSProviders(&$client)
{
    # function to execute
    $action = "GetDDNSProviders";

    # execute action published by service
    $listOfProviders = \FritzBox\soapCall($client, $action);
    print_r($listOfProviders);
}

function fbDDNSConfigGet(&$client)
{
    # function to execute
    $action = "GetDDNSInfo";

    $infoDDNS = \FritzBox\soapCall($client, $action);
    print_r($infoDDNS);
}

function fbDDNSConfigSet(&$client)
{
    # function to execute
    $action = "SetDDNSConfig";

    $params = array(
        'NewEnabled' => (int)1,
        'NewProviderName' => 'Benutzerdefiniert',
        'NewUpdateURL' => '/nic/update?myip=<ipaddr>'
			.'&host=<domain>&textmodi=1&http_status=1',
        'NewServerIPv4' => 'carol.selfhost.de',
        'NewServerIPv6' => 'carol.selfhost.de',
        'NewDomain' => '<domain>',
        'NewUsername' => '<user>',
        'NewPassword' => '<pass>',
        'NewMode' => 'ddns_both');

    return \FritzBox\soapCall($client, $action, $params);
}

function fbDeviceLogGet(&$client)
{
    # function to execute
    $action = "GetDeviceLog";

    # execute action published by service
    $devLogs = \FritzBox\soapCall($client, $action);
    print_r($devLogs);
}

function fbWanIpAddress(&$client)
{
    # function to execute
    $action = "GetExternalIPAddress";

    # execute action published by service
    $ipAdress = \FritzBox\soapCall($client, $action);
    print_r($ipAdress . PHP_EOL);

    $action = "X_AVM_DE_GetExternalIPv6Address";

    $ipAdress = \FritzBox\soapCall($client, $action);
    print_r($ipAdress["NewExternalIPv6Address"] . PHP_EOL);
}

try
{
    #$client = fbSoapClient($desc_tr64, $scpd_dect);
    #fbDectTelephones($client);

    $client = fbSoapClient($desc_tr64, $scpd_ddns);
    #fbDDNSConfigSet($client);
    fbDDNSConfigGet($client);

    $client = fbSoapClient($desc_igd, $scpd_conn);
    fbWanIpAddress($client);

    #$client = fbSoapClient($desc_tr64, $scpd_info);
    #fbDeviceLogGet($client);
}
catch(Exception $e)
{
    echo $e->__toString() . PHP_EOL;
}
?>
