<?php

require_once("../classes/WsdlDefinition.php");
require_once("../classes/WsdlWriter.php");

$def = new WsdlDefinition();
$def->setDefinitionName("Test");
$def->setClassFileName("Test.php");
$def->setWsdlFileName("Test.wsdl");
$def->setNameSpace("http://127.0.0.1/soap/myNameSpace");
$def->setEndPoint("http://127.0.0.1/test.php");

$wsdl = new WsdlWriter($def);
print $wsdl->classToWsdl();

?>
