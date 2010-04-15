<?php
echo system('ps ax');
echo '<br />';
echo system("ps ax | egrep '/usr/sbin/maradns' | awk '{print $1}'");
echo '<br />';
echo system('whoami');
?>