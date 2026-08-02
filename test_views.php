<?php
foreach (glob('storage/framework/views/*.php') as $f) {
    exec('php -l ' . escapeshellarg($f), $output, $return_var);
    if ($return_var !== 0) {
        echo $f . "\n";
        print_r($output);
    }
}
