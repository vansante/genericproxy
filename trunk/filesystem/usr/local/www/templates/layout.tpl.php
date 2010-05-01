<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
        <title>Generic Proxy</title>

        <style type="text/css" media="screen">
            @import url(css/main.css);
        </style>
        
        <? //Bouw de javascript namespace en methode structuur op ?>
        <script type="text/javascript">
            //Bouw js namespace structuur op basis van menu,
            //gp.firewall.nat, gp.status.dhcp etc.
            gp = <?=$this->namespace ?>;
            gp.debug = <?=$this->debug ? 'true' : 'false'?>;
        </script>

        <script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
        <script type="text/javascript" src="js/bundle.js"></script>
        <script type="text/javascript" src="js/init.js"></script>
    </head>
    <body>
        <div id="layout_header">
            <a href="index.php"><img src="./images/genericproxy.png" alt="logo" /></a>
        </div>
        <div id="layout_left">
            <? include $this->template('menu.tpl.php'); ?>
        </div>
        <div id="layout_right">
            <? include $this->template('content.tpl.php')?>
        </div>

        <div id="help_hover_pool" class="help_pool"></div>
    </body>
</html>
