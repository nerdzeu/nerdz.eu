<?php
/*
 * Classe per la gestione dei PM, i nomi sono esplicativi.
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/class/messages.class.php';

final class pm extends messages
{

    public function __construct()
    {
        parent::__construct();
    }

    public function send($to,$message)
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/class/flood.class.php';
        if(!(new flood())->pm())
            return null;
        
        if(isset($message[65534]))
            return false;

        return db::NO_ERR == parent::query(array('INSERT INTO "pms" ("from","to","message","time","read") VALUES (:id,:to,:message,NOW(),TRUE)',array(':id' => $_SESSION['nerdz_id'],':to' => $to,':message' => $message)),db::FETCH_ERR);
    }

    public function getList()
    {
            if(!($rs = parent::query(array('SELECT DISTINCT EXTRACT(EPOCH FROM MAX(times)) as lasttime, otherid as "from" FROM ((SELECT MAX("time") AS times, "from" as otherid FROM pms WHERE "to" = ? GROUP BY "from") UNION (SELECT MAX("time") AS times, "to" as otherid FROM pms WHERE "from" = ? GROUP BY "to")) AS tmp GROUP BY otherid ORDER BY "lasttime" DESC',array($_SESSION['nerdz_id'],$_SESSION['nerdz_id'])),db::FETCH_STMT)))
                return false;
    
            $times = $res = array();
            $c = 0;
            while(($o = $rs->fetch(PDO::FETCH_OBJ)))
            {
                $from = $this->getUserName($o->from);
                $res[$c]['from4link_n'] = phpCore::userLink($from);
                $res[$c]['from_n'] = $from;
                $res[$c]['datetime_n'] = parent::getDateTime($o->lasttime);
                $res[$c]['timestamp_n'] = $o->lasttime;
                $res[$c]['fromid_n'] = $o->from;
                $res[$c]['toid_n'] = $_SESSION['nerdz_id'];
                $times[$c] = $o->lasttime;
                ++$c;
            }

            $res = array_unique($res,SORT_REGULAR); //fix for new duplicate pm
            $c = count($res);
            
        return $res;
    }
        
    public function read($fromid,$toid,$time,$pmid)
    {
        $ret = array();
            
        if(
                !is_numeric($fromid) || !is_numeric($toid) || !is_numeric ($pmid) || !in_array($_SESSION['nerdz_id'],array($fromid,$toid)) ||
                !($res = parent::query(array('SELECT "message","read" FROM "pms" WHERE "from" = :from AND "to" = :to AND "pmid" = :pmid',array(':from' => $fromid, ':to' => $toid, ':pmid' => $pmid)),db::FETCH_STMT))
          )
            return false;

        if(($o = $res->fetch(PDO::FETCH_OBJ)))
        {
            $from = $this->getUserName($fromid);
            $ret['from4link_n'] = phpCore::userLink($from);
            $ret['from_n'] = $from;
            $ret['datetime_n'] = parent::getDateTime($time);
            $ret['fromid_n'] = $fromid;
            $ret['toid_n'] = $toid;
            $ret['message_n'] = parent::bbcode($o->message);
            $ret['read_b'] = $o->read;
            $ret['pmid_n'] = $pmid;
            $ret['timestamp_n'] = $time;
            //$ret['realto_n'] = $fromid != $_SESSION['nerdz_id'] ? $from : $this->getUserName ($toid);
        }
        
        return $ret;
    }
    
    public function countNew()
    {
        if(!($o = parent::query(array('SELECT COUNT(DISTINCT "from") as cc FROM (SELECT "from" FROM "pms" WHERE "to" = :id AND "read" = TRUE) AS tmp1',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
            return -1;
        return $o->cc;
    }
        
    public function deleteConversation($from, $to)
   {
        return is_numeric($from) && is_numeric($to) && in_array($_SESSION['nerdz_id'],array($from,$to)) && 
            db::NO_ERR == parent::query(array('DELETE FROM "pms" WHERE ("from" = ? AND "to" = ?) OR ("from" = ? AND "to" = ?)',array($from,$to,$to,$from)),db::FETCH_ERR);
    }
    
    public function readConversation($from, $to, $afterPmId = null, $num = null, $start = 0)
    {
        $ret = array();
        
        if(!is_numeric($from) || !is_numeric($to) || (is_numeric ($num) && is_numeric ($start) && ($start < 0 || $start > 200 || $num < 0 || $num > 10)) /*|| !in_array($_SESSION['nerdz_id'],array($from,$to))*/)
            return $ret;
        $__enableLimit = is_numeric ($num) && is_numeric ($start);
        $query = $__enableLimit ?
                        //"SELECT q.from, q.to, q.time FROM (SELECT "from", "to", "time" FROM "pms" WHERE (("from" = ? AND "to" = ?) OR ("from" = ? AND "to" = ?)) ORDER BY "pmid""
                        'SELECT q.from, q.to, q.time, q.pmid FROM (SELECT "from", "to", EXTRACT(EPOCH FROM "time") AS time, "pmid" FROM "pms" WHERE (("from" = ? AND "to" = ?) OR ("from" = ? AND "to" = ?)) ORDER BY "pmid" DESC LIMIT ? OFFSET ?) AS q ORDER BY q.pmid ASC' :
                        'SELECT "from", "to", EXTRACT(EPOCH FROM "time") AS time, "pmid" FROM "pms" WHERE '.($afterPmId ? '"pmid" > ? AND ' : '').' (("from" = ? AND "to" = ?) OR ("from" = ? AND "to" = ?)) ORDER BY "pmid" ASC';
        if (!($res = parent::query (array ($query, ($__enableLimit ? array ($from, $to, $to, $from, $num, $start * $num) : ( $afterPmId ? array ($afterPmId, $from, $to, $to, $from) : array ($from, $to, $to, $from)))), db::FETCH_STMT)))
                  /*!($res = parent::query(
                    array('SELECT "from","to",EXTRACT(EPOCH FROM "time") AS time FROM "pms" WHERE '.($afterPmId ? '"pmid" > ? AND ' : '').' (("from" = ? AND "to" = ?) OR ("from" = ? AND "to" = ?)) ORDER BY "pmid" ASC',
                    $afterPmId ?
                        array($afterPmId,$from, $to,$to,$from) :
                        array($from, $to,$to,$from)
                    ),db::FETCH_STMT)))*/
            return $ret;

        $ret = $res->fetchAll(PDO::FETCH_FUNC,array($this,'read'));

        //se l'ultimo l'ho inviato io, mostro il nuovo commento se non ne sono stati aggiunti di nuovi dall'altro prima
        if($afterPmId && empty($ret))
        {
            if(!($res = parent::query(
                    array('SELECT "from","to",EXTRACT(EPOCH FROM "time") AS time,"pmid" FROM "pms" WHERE "pmid" = ? AND (("from" = ? AND "to" = ?) OR ("from" = ? AND "to" = ?)) ORDER BY "pmid" ASC',array($afterPmId,$from, $to,$to,$from)
                          ),db::FETCH_STMT)))
                return $ret;
            $ret = $res->fetchAll(PDO::FETCH_FUNC,array($this,'read'));
        }
        if(db::NO_ERR != parent::query(array('UPDATE "pms" SET "read" = FALSE WHERE "from" = :from AND "to" = :id',array(':from' => $from, ':id' => $_SESSION['nerdz_id'])),db::FETCH_ERR))
            return false;
        
        return $ret;
    }

    public function countPms ($from, $to)
    {
        if (!is_numeric ($from) || !is_numeric ($to) || !($res = parent::query (array ('SELECT COUNT("pmid") AS pc FROM "pms" WHERE (("from" = ? AND "to" = ?) OR ("from" = ? AND "to" = ?))', array ($from, $to, $to, $from)), db::FETCH_OBJ)))
            return 0;
        return $res->pc;
    }    

    public function getLastMessageForConversation($otherId) {
        
        $ret = false;

        if(is_numeric($otherId)) {
            
            $res = parent::query(
                array(
                    'WITH thisconv AS (SELECT "from",time,message FROM pms WHERE("from" = :me AND "to" = :other) OR  ("from" = :otheragain AND  "to" = :meagain)) SELECT "from" as last_sender,message FROM thisconv WHERE time = (SELECT MAX(time) FROM thisconv)',
                    array(
                        ':me' => $_SESSION['nerdz_id'],
                        ':meagain' => $_SESSION['nerdz_id'],
                        ':other' => $otherId,
                        ':otheragain' => $otherId
                    )                    
                ), db::FETCH_OBJ                
            );
            
            if (isset($res->message)) {
                $ret = $res;
            }

        }

        return $ret;

    }
}
?>
