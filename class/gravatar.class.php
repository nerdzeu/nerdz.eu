<?php
namespace NERDZ\Core;
/*
 * Classe per la gestione di Gravatar
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';

class Gravatar extends Core
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getURL($id)
    {
        if(!($o = parent::query(array('SELECT "email" FROM "users","profiles" WHERE "users"."counter" = ? AND "profiles"."counter" = ?',array($id, $id)),Db::FETCH_OBJ)))
            return 'https://www.Gravatar.com/avatar/0';

        return 'https://www.Gravatar.com/avatar/'.md5(strtolower($o->email));
    }
}
?>
