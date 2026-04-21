<?php
header("Access-Control-Allow-Origin: *");
if (file_exists(__DIR__ . '/test.txt')) {
    return false;
}
