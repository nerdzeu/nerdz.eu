<?php
use NERDZ\Core\Db;
if($user->isLogged())
    die(header('Location: /home.php'));
$token = isset($_GET['tok']) && is_string($_GET['tok']) && strlen($_GET['tok']) == 32 ? $_GET['tok'] : '';
$id    = isset($_GET['id'])  && is_numeric($_GET['id']) && $_GET['id'] > 0 ? $_GET['id'] : false;
if(!$token || !$id) {
    $user->getTPL()->draw('base/reset');
} else {
    if(!is_object($obj = Db::query(
        [
            'SELECT u.username FROM reset_requests r INNER JOIN users u ON u.counter = r."to" WHERE r."counter" = :id AND r.token = :token',
            [
                ':id'    => $id,
                ':token' => $token
            ]
        ], Db::FETCH_OBJ))) {
        echo 'Invalid request';
    } else {
        $vals =[];
        $vals['username_n'] = $obj->username;
        $vals['resettoken_n'] = $token;
        $vals['resetkey_n'] = $id;
        $user->getTPL()->assign($vals);
        $user->getTPL()->draw('base/reset-token');
    }
}
?>
