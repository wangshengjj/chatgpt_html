<?php
function formatMsg($message = '')
{
    $message = trim($message);
    $message = str_replace(" ", "&nbsp;", $message);
    $message = explode("\n", $message);
    if (is_string($message)) {
        $message = [$message];
    }
    return $message;
}