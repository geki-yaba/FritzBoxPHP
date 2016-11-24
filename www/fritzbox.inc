<?php
declare(strict_types=1);

namespace FritzBox;

# debug
#class FBSoapClient extends \SoapClient
#{
#    public function __doRequest($request, $location, $action, $version, $one_way = NULL)
#    {
#        var_dump($request, $location, $action, $version, $one_way);
#        $result = parent::__doRequest($request, $location, $action, $version, $one_way);
#        return $result;
#    }
#}

function getHTTPSContent($url)
{
    $ctx = array(
        "ssl" => array(
            "allow_self_signed" => true,
            "verify_peer" => false,
            "verify_peer_name" => false
        )
    );

    return file_get_contents($url, false, stream_context_create($ctx));
}

function getServiceData($base_uri, $desc, $scpd)
{
    $content = getHTTPSContent($base_uri .'/'. $desc);
    $xml = @simplexml_load_string($content);

    if ($xml === false)
    {
        echo "service data not found: ". $desc ."<br />";

        return false;
    }

    $xml->registerXPathNamespace('fb', $xml->getNameSpaces(false)[""]);
    $xmlService = $xml->xpath("//fb:service[fb:SCPDURL='/". $scpd ."']");

    if (count($xmlService) == 0)
    {
        return false;
    }

    $service = array();
    $service['uri'] = (string)$xmlService[0]->serviceType;
    $service['location'] = $base_uri . (string)$xmlService[0]->controlURL;
    $service['SCPDURL'] = trim((string)$xmlService[0]->SCPDURL, '/');

    return $service;
}

function getStateVars($base_uri, $service, $action)
{
    $content = getHTTPSContent($base_uri .'/'. $service['SCPDURL']);
    $xml = @simplexml_load_string($content);

    if ($xml === false)
    {
        echo "state variables not found: ". $service['SCPDURL'] ."<br />";

        return false;
    }

    $xml->registerXPathNamespace('fb', $xml->getNameSpaces(false)[""]);
    $xmlArguments = $xml->xpath("//fb:actionList/fb:action[fb:name='". $action. "']/fb:argumentList/fb:argument");

    if ($xmlArguments === false)
    {
        return false;
    }

    $stateVariables = array();
    foreach($xmlArguments as $xmlArgument)
    {
        $xmlStateVariable = $xml->xpath("//fb:stateVariable[fb:name='". (string)$xmlArgument->relatedStateVariable ."']");
        $stateVariables[(string)$xmlArgument->name] = (string)$xmlStateVariable[0]->dataType;
    }

    if (count($stateVariables) == 0)
    {
        return false;
    }

    return $stateVariables;
}

function soapClient($service)
{
    $ctx = array(
        "ssl" => array(
            "allow_self_signed" => true,
            "verify_peer" => false,
            "verify_peer_name" => false
        )
    );

    # if FritzBox ever moves to version 1.2 or higher
    #$service['soap_version'] = SOAP_1_2;
    $service['ssl_method'] = SOAP_SSL_METHOD_TLS;

    $service['stream_context'] = stream_context_create($ctx);

    $service['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP;

    $service['trace'] = false;
    $service['exceptions'] = true;

    return new \SoapClient(null, $service);
}

function soapAction($client, $action, $param = null)
{
    if ($param === null)
    {
        $status = $client->$action();
    }
    else
    {
        $status = $client->$action($param);
    }

    if (is_soap_fault($status))
    {
       return false;
    }

    return $status;
}
?>
