<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\User;
use NERDZ\Core\Db;

$core = new User();
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

if(!$core->isLogged())
    die($core->lang('REGISTER'));

if(!($o = Db::query(
                [
                    'SELECT "private" FROM "users" WHERE "counter" = :id',
                    [
                        ':id' => $_SESSION['id']
                    ]
                ],Db::FETCH_OBJ)))
    die($core->lang('ERROR'));

$vals['private_b'] = $o->private;
$vals['tok_n']     = $core->getCsrfToken('edit');

$core->getTPL()->assign($vals);
$core->getTPL()->draw('preferences/guests');
?>
