<?php
/*
 * Classe per la gestione di gravatar
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';

class gravatar extends phpCore
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getURL($id)
    {
        if(!($o = parent::query(array('SELECT "email", "photo" FROM "users","profiles" WHERE "users"."counter" = ? AND "profiles"."counter" = ?',array($id, $id)),db::FETCH_OBJ)))
            return 'https://www.gravatar.com/avatar/0';

        return 'https://www.gravatar.com/avatar/'.md5(strtolower($o->email)).'?d='.urlencode($o->photo);
    }
}
?>
