<?php
declare(strict_types=1);

namespace FritzBoxDect;

require_once('Phery.php');
require_once('fritzbox.inc');

use Exception;

class Params
{
    # user and password
    # - user is optional for default setup; simply set any name
    # - otherwise use FritzBox-User with read/write access
    static $user = "admin";
    static $pass = "passw";

    # fritz!box soap server
    static $base_uri = "https://fritz.box:49443";

    # description of services
    static $desc = "tr64desc.xml";

    # function signatures, variables and its data types
    static $scpd = "x_dectSCPD.xml";
};

function uninit()
{
    session_start();

    while($_SESSION['busy'] !== false)
    {
        sleep(1);
    }

    foreach($_SESSION as $key => $val)
    {
        unset($_SESSION[$key]);
    }

    session_write_close();
}

function init($data, $params, $phery)
{
    session_start();

    $err = '';

    try
    {
        $_SESSION['busy'] = false;

        # receive service description
        $_SESSION['dect_service'] =
            \FritzBox\getServiceData(Params::$base_uri, Params::$desc, Params::$scpd);

        if ($_SESSION['dect_service'] === false)
        {
            unset($_SESSION['dect_service']);

            throw new Exception("no service found in ". $scpd);
        }
    }
    catch(Exception $e)
    {
        $err = nl2br(str_replace($_SERVER['DOCUMENT_ROOT'], '', $e->__toString()));
    }

    session_write_close();

    $response = \PheryResponse::factory('#dect_list');

    if ($err != '')
    {
        $response->html($err)->show('fast');

        $response->exception($err);
    }
    else
    {
        $body = 'found service... starting operation...';

        $response->html($body)->show('fast');
    }

    return $response;
}

function list_phones($data, $params, $phery)
{
    session_start();

    $err = '';
    $body = '';

    if (isset($_SESSION['dect_service']))
    {
        $_SESSION['busy'] = true;

        try
        {
            $service = $_SESSION['dect_service'];

            # set user and password
            $service['login'] = Params::$user;
            $service['password'] = Params::$pass;

            # create soap client
            $client = \FritzBox\soapClient($service);

            # function to execute
            $action = "GetNumberOfDectEntries";

            # execute action published by service
            $noOfTelephones = (int)\FritzBox\soapCall($client, $action);

            $body = "no of dect telephones: ". $noOfTelephones ."<br /><br />";

            $action = "GetGenericDectEntry";

            for($i = 0; $i < $noOfTelephones; $i++)
            {
                $result = \FritzBox\soapCall($client, $action,
                                new \SoapParam((int)$i, 'NewIndex'));

            	$line = ($result['NewActive'] != 0) ? "busy" : "open";

                $body .= "name(". $result['NewName']
                    .") id(". $result['NewID']
                    .") line(". $line .")<br />";
            }
        }
        catch(Exception $e)
        {
            $err = nl2br(str_replace($_SERVER['DOCUMENT_ROOT'], '', $e->__toString()));
        }

        $_SESSION['busy'] = false;
    }
    else
    {
        $err = 'no dect service available!';
    }

    session_write_close();

    $response = \PheryResponse::factory('#dect_list');

    if ($err != '')
    {
        $response->html($err)->show('fast');

        $response->exception($err);
    }
    else
    {
        $response->html($body)->show('fast');
    }

    return $response;
}
?>
