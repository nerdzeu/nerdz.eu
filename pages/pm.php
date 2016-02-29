<?php
use NERDZ\Core\Pms;
$pms  = new Pms();

$vals = [];
$vals['list_a'] = $pms->getList();

$user->getTPL()->assign($vals);
$user->getTPL()->draw('pm/main');
