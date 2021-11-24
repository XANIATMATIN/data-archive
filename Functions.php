<?php


function archiveProccess(string $connection, array $archivable, string  $messageHandler = '')
{
    return app('data-archive')->handle($connection, $archivable,  $messageHandler);
}
