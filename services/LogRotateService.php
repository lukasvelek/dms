<?php

file_put_contents('__pid.txt', getmypid());

while(1 > 0) {
    file_put_contents('__test.txt', 'running' . "\r\n", FILE_APPEND);
    sleep(10);
}

exit;

?>