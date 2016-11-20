# FritzBoxPHP
Access your Fritz!Box via PHP SOAP API

The helper functions and example(s) found in this repository are built upon:

* AVM resources:

https://avm.de/service/schnittstellen/

https://avm.de/fileadmin/user_upload/Global/Service/Schnittstellen/AVM_TR-064_first_steps.pdf

* My Fritz!Box 7560 FW 6.53 exposing SOAP API

https://fritz.box:49443/tr64desc.xml

* Online resources:

https://www.symcon.de/forum/threads/25745-FritzBox-mit-SOAP-auslesen-und-steuern

# CLI-based example
Query DECT status once and print on STDOUT.

# AJAX-based Website example
Using Phery.js as AJAX framework to poll and update DECT status and present on website.

There are some FIXMEs in 'www/index.php' that you may help me to solve, please.

If you see any chance to improve the code, like keeping the SOAP client session in PHP session so that no new connection needs to be established on every poll, let me know!
