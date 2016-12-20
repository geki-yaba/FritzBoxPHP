<?php
declare(strict_types=1);


# FIXME
#
# 1. wait for jquery mobile 1.5.0 to reenable jquery-3.1.1


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
    <meta charset="utf-8" name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <title>Fritz!Box DECT</title>
    <!--
    <link rel="stylesheet" href="jquery.mobile.custom.structure.min.css" />
    <link rel="stylesheet" href="jquery.mobile.custom.theme.min.css" />
    <script src="jquery-3.1.1.min.js"></script>
    <script src="jquery.mobile.custom.min.js"></script>
    -->
    <link rel="stylesheet" href="//code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.css" />
    <script src="//code.jquery.com/jquery-2.2.4.min.js"></script>
    <script src="//code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.js"></script>
    <!-- debug -->
    <!-- <script src="phery-2.7.4.js"></script> -->
    <script src="phery.min-2.7.4.js"></script>
    <style id="dect-phone-flipswitch">
/* Custom indentations are needed because the length of custom labels differs from
   the length of the standard labels */
.ui-field-contain > .dect-phone-flipswitch.ui-flipswitch .ui-btn.ui-flipswitch-on
{
    text-indent: -2.8em;
}
.ui-field-contain > .dect-phone-flipswitch.ui-flipswitch .ui-flipswitch-off
{
    text-indent: 0.2em;
}
/* Custom widths are needed because the length of custom labels differs from
   the length of the standard labels */
.ui-field-contain > .dect-phone-flipswitch.ui-flipswitch.ui-flipswitch-active
{
    padding-left: 5.0em;
    width: 1.875em;
}
.ui-field-contain > .dect-phone-flipswitch.ui-flipswitch
{
    width: 6.875em;
}
    </style>
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
    <div data-role="page">
      <div data-role="header">
        <h1>FritzBoxPHP DECT</h1>
      </div>
      <div data-role="main" class="ui-content" name="dect_list" id="dect_list">
        <h1>Loading ...</h1>
      </div>
      <div data-role="footer">
        <h1>FritzBoxPHP DECT</h1>
      </div>
    </div>
  </body>
</html>
