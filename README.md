PHP WSDL Writer
============

WSDL Writer generates WSDL XML documents from PHP 5 classes.

##History

The [original version](http://www.giffin.org/wsdlwriter.php)
of this library was created by David Giffin.

Corrections and updates were added later by
[Katy](http://katyscode.wordpress.com/2006/07/27/automatic-wsdl-generation-in-php-5/).

We've also made some minor corrections and updates.

Since this library does not appear to be maintained anymore,
we've decided to share the code on GitHub to enable others to
easily improve it.

Patches welcome!


##Install

After cloning the Git repository, you can create a symlink
to the wsdl bin script:
~~~
cd /usr/local/bin
ln -s /path/to/php-wsdl-writer/bin/wsdl wsdl
~~~

Now you should be able to run wsdl on your
favorite class to kick start you web service!


##Example

In this Example you have created a class named
'MyClassName' the class file must be named
'MyClassName.inc' or 'MyClassName.php' you must
provide a URL for the 'Soap Name Space' you
can optionally define the name of the generated
WSDL file and the name of the 'Soap End Point'.
The 'Soap End Point' should be a simple script
to envoke the SoapServer

wsdl MyClassName.php http://my.domain/soap/dir/ MyService.wsdl MyService.php
