<?php
$tid = $_FANWE['request']['tid'];
FS('Topic')->deleteTopic($tid);
?>
