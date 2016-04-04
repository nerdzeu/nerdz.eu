<?php

use NERDZ\Core\Db;

$client_id = isset($_POST['client_id']) && is_numeric($_POST['client_id']) ? $_POST['client_id']  : false;
$redirect_uri = isset($_POST['redirect_uri']) ? $_POST['redirect_uri']  : false;

if (!$client_id || !$redirect_uri) {
    echo $user->lang('MISSING'), 'client_id, redirect_uri';
} else {
    $vals = [];
    $vals['client_n'] = Db::Query('');
    $user->getTPL()->draw('oauth2/authorize');
}
