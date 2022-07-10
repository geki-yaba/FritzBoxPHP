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
$desc = "tr64desc.xml";

# function signatures, variables and its data types
$scpd_dect = "x_dectSCPD.xml";
$scpd_ddns = "x_remoteSCPD.xml";

function fbSoapClient(string &$scpd)
{
    global $base_uri, $desc, $user, $pass;

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

function fbDDNSConfigGet(&$client)
{
    # function to execute
    $action = "GetDDNSProviders";

    # execute action published by service
    $listOfProviders = \FritzBox\soapCall($client, $action);
    print_r($listOfProviders);

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
        'NewUpdateURL' =>
            'https://carol.selfhost.de/nic/update?myip=<ipaddr>&textmodi=1&http_status=1',
            #.' https://carol.selfhost.de/nic/update?myip=<ip6addr>&textmodi=1&http_status=1',
        'NewServerIPv4' => 'carol.selfhost.de',
        'NewServerIPv6' => 'carol.selfhost.de',
        'NewDomain' => '<domain>',
        'NewUsername' => '<user>',
        'NewPassword' => '<pass>',
        'NewMode' => 'ddns_v4'); # ddns_both

    return \FritzBox\soapCall($client, $action, $params);
}

try
{
    $client = fbSoapClient($scpd_dect);
    fbDectTelephones($client);

    $client = fbSoapClient($scpd_ddns);
    #fbDDNSConfigSet($client);
    fbDDNSConfigGet($client);
}
catch(Exception $e)
{
    echo $e->__toString() . PHP_EOL;
}
?>
