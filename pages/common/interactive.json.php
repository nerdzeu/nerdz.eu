<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\User;
use NERDZ\Core\Search;
$search = new Search();

if(!isset($searchMethod) || !method_exists($search, $searchMethod))
    die(NERDZ\Core\Utils::jsonResponse('error', 'No-sense error'));

$user = new User();

if(!$user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('LOGIN')));

$count = isset($_GET['count']) && is_numeric($_GET['count']) ? (int)$_GET['count'] : 10;
$q     = isset($_GET['q']) && is_string($_GET['q']) ? $_GET['q'] : '';
if($q === '')
    die(NERDZ\Core\Utils::jsonResponse('error', 'Invalid search'));

die(NERDZ\Core\Utils::jsonResponse($search->$searchMethod($q, $count)));
