<?php
function formatMsg($message = '')
{
    $message = trim($message);
    $message = str_replace("\n", "<br>", $message);
    $message = str_replace(" ", "&nbsp;", $message);
    return $message;
}