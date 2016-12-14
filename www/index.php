<?php
declare(strict_types=1);

# FIXME
#
#   1) PheryResponse: return success or error versus 'phery:done' and 'phery:exception'

require_once('Phery.php');
require_once('fritzboxdect.php');

$phery = Phery::instance(array('exceptions' => true));

$phery->set(
    array(
        # uninit is executed via sendBeacon('logout.php')
        #'fritzbox_dect_uninit' => '\FritzBoxDect\uninit',
        'fritzbox_dect_init' => '\FritzBoxDect\init',
        'fritzbox_dect_list' => '\FritzBoxDect\list_phones'
    )
);

$phery->process();
?>

<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN'
'http://w3c.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>

<html>
  <head>
    <meta charset="utf-8" name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes"/>
    <title>Fritz!Box DECT</title>
    <script src="jquery-3.1.1.min.js"></script>
    <!-- debug -->
    <!-- <script src="phery-2.7.4.js"></script> -->
    <script src="phery.min-2.7.4.js"></script>
    <script type="text/javascript">
        var dect_list_call = null;
        var dect_list_timer = null;

        // debug
        //phery.config({'debug.enable': true, 'debug.display.config': true});

        // asnychronous loading of page
        $(document).ready(
            function()
            {
                // asynchronous ajax call
                // 3rd param: remove remote call DOM element after use:    {'temp': true}
                // 4th param: have temporary DOM element to bind events:   false
                phery.remote('fritzbox_dect_init', null, {'temp': true}, false)
                    .on({
                        'phery:done': function(event, data, text, xhr)
                        {
                            console.log('dect service initialization responded');

                            var header = xhr.getResponseHeader('X-FritzBoxPHP-Exception');

                            if (header != null)
                            {
                                console.log('exception: %o', header);
                            }
                            else
                            {
                                dect_list_call = phery.remote('fritzbox_dect_list', null, null, false)
                                    .on({
                                        'phery:done': function(event, data, text, xhr)
                                        {
                                            var header = xhr.getResponseHeader('X-FritzBoxPHP-Exception');

                                            if (header != null)
                                            {
                                                console.log('exception: %o', header);

                                                dect_list_timer.stop();
                                            }
                                        }
                                    //},
                                    //{
                                    //    'phery:exception': function(event, exception)
                                    //    {
                                    //        dect_list_timer.stop();

                                    //        console.log('dect service polling aborted!');
                                    //    }
                                    });

                                dect_list_timer = phery.timer(dect_list_call);
                                dect_list_timer.start(5000);
                            }
                        }
                    //},
                    //{
                    //    'phery:exception': function(event, exception)
                    //    {
                    //        dect_list_timer.stop();

                    //        console.log('dect service initialization failed!');
                    //    }
                    })
                    .phery('remote');
            }
        );

        // asynchronous unloading of page
        $(window).on(
            'beforeunload',
                function()
                {
                    if (dect_list_timer != null)
                    {
                        dect_list_timer.stop();
                    }

                    dect_list_timer = null;
                    dect_list_call = null;

                    // proper server-side cleanup
                    if (false === navigator.sendBeacon('<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . DIRECTORY_SEPARATOR .'logout.php'; ?>'))
                    {
                        console.log('could not send logout beacon to server!');
                    }
                }
        );
    </script>
  </head>
  <body>
    <div id='dect_list' name='dect_list'></div>
  </body>
</html>
