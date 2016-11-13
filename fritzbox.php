<?php
declare(strict_types=1);

require_once('./fb_util.inc');



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
$scpd = "x_dectSCPD.xml";

# function to execute
$action = "GetNumberOfDectEntries";



try
{
    # receive service description
    $service = \FritzBox\getServiceData($base_uri, $desc, $scpd);

    if ($service === false)
    {
        throw Exception("no service found in ". $scpd);
    }

    #print_r($service);

    # set user and password
    $service['login'] = $user;
    $service['password'] = $pass;

    # receive variables and its data types belonging to action
    #$stateVars = \FritzBox\getStateVars($base_uri, $service, $action);

    #if ($stateVars === false)
    #{
    #    throw Exception("no state variables belonging to $action");
    #}

    #print_r($stateVars);

    # create soap client
    $client = \FritzBox\soapClient($service);

    # execute action published by service
    $noOfTelephones = (int)\FritzBox\soapAction($client, $action);

    if ($noOfTelephones === false)
    {
        throw Exception("soap fault encountered executing ". $action);
    }

    echo "no of dect telephones: ". $noOfTelephones . PHP_EOL;

    $action = "GetGenericDectEntry";

    for($i = 0; $i < $noOfTelephones; $i++)
    {
        $result = \FritzBox\soapAction($client, $action, new SoapParam($i, 'NewIndex'));

        if ($noOfTelephones === false)
        {
            throw Exception("soap fault encountered executing ". $action);
        }

	$line = ($result['NewActive'] != 0) ? "busy" : "open";

        echo "name(". $result['NewName'] .") id(". $result['NewID'] .") line(". $line .")" . PHP_EOL;
    }
}
catch(Exception $e)
{
    echo $e->__toString() . PHP_EOL;
}
?>
