<?php
function formatMsg($message = '')
{
    $message = trim($message);
    $message = str_replace(" ", "&nbsp;", $message);
    $message = str_replace("\n", "<br>", $message);
    return $message;
}