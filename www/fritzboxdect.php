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

    # TODO wait until pending AJAX calls finished

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

        header('X-FritzBoxPHP-Exception: initialization');
        #$response->exception($err);
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
    $body = '<form>';

    try
    {
        if (isset($_SESSION['dect_service']))
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

            $action = "GetGenericDectEntry";

            for($i = 0; $i < $noOfTelephones; $i++)
            {
                $result = \FritzBox\soapCall($client, $action,
                                new \SoapParam((int)$i, 'NewIndex'));

                $id = $result['NewID'];
                $active = $result['NewActive'];
                $line = ($active != 0) ? "busy" : "open";

                $body .= '<div class="ui-field-contain">'
                    .'<label for="dect-phone-checkbox-'. $id .'">'. $result['NewName'] .' (id: '. $id .')</label>'
                    .'<input type="checkbox" name="dect-phone-checkbox-'. $id .'" id="dect-phone-checkbox-'. $id .'"'
                    .' data-role="flipswitch" data-mini="true" data-corners="false" data-disabled="true"'
                    .' data-on-text="busy" data-off-text="open" data-wrapper-class="dect-phone-flipswitch"';

                if ($active != 0)
                {
                    $body .= ' checked="checked"';
                }

                $body .= ' /></div>';
            }
        }
        else
        {
            throw new Exception('no dect service available!');
        }
    }
    catch(Exception $e)
    {
        $err = nl2br(str_replace($_SERVER['DOCUMENT_ROOT'], '', $e->__toString()));
    }

    $body .= '</form>';

    session_write_close();

    $response = \PheryResponse::factory('#dect_list');

    if ($err != '')
    {
        $response->html($err)->show('fast');

        header('X-FritzBoxPHP-Exception: list phones');
        #$response->exception($err);
    }
    else
    {
        $response->html($body)->enhanceWithin();
    }

    return $response;
}
?>
