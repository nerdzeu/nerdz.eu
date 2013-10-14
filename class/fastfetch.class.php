<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/class/pm.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/fastfetcherror.class.php';

/**
    FastFetch is a class for fast pm fetching - and maybe more, someday.
*/
final class FastFetch {

    private $mPm;

    public function __construct() {

        $this->mPm = new pm();

    }

    /**
     * Returns true if current session is associated with a logged user.
     * @return boolean 
     */
    public function isLogged() {
        return $this->mPm->isLogged();
    }

    /**
     * Returns all conversations.
     * 
     * @return array an object containing the list of conversations.
     * @throws FFException if something wrong happens
     */
    public function fetchConversations() {

        $ret = new stdClass();

        $list = $this->mPm->getList();
        
        if($list !== NULL) {
            
            $ret = [];

            foreach($list as $conversation) {
                
                $result = $this->mPm->getLastMessageForConversation((int) $conversation['fromid_n']);
                
                if ($result === false) {
                    throw new FFException(FFErrCode::SERVER_FAILURE);
                }
                
                $element = [
                            'name' => html_entity_decode($conversation['from_n'], ENT_QUOTES),
                            'last_timestamp' => intval($conversation['timestamp_n']),
                            'id' => $conversation['fromid_n'],
                            'last_message' => $result->message,
                            'last_sender' => $result->last_sender
                           ];

                $ret[] = $element;

            }

        } else {
            throw new FFException(FFErrCode::SERVER_FAILURE);
        }

        return $ret;

    }

    /**
     * Fetches $limit messages starting with the $start-th in the conversation with user $otherId.
     * 
     * @param int $otherId 
     * @param int $start
     * @param int $limit
     * @return array an array of objects representing 
     * @throws FFException if something wrong happens
     */
    public function fetchMessages($otherId, $start = 0, $limit = 10) {
    
        if ($limit > 30) {
            throw new FFException(FFErrCode::LIMIT_EXCEEDED);
        }
        
        $me = $this->mPm->getUserId();
        
        $list = $this->mPm->query (
            [
                'SELECT ("from" = :me) AS SENT, EXTRACT(EPOCH FROM time) AS timestamp, message, read FROM PMS WHERE ("from" = :from1 AND "to" = :to1) OR ("to" = :to2 AND "from" = :from2) ORDER BY TIME DESC LIMIT '.$limit.' OFFSET '.$start, 
                [
                    ':me' => $me,
                    ':from1' => $otherId,
                    ':to1' => $me,
                    ':from2' => $me,
                    ':to2' => $otherId
                ]
            ],
            db::FETCH_OBJ,
            true 
        );
        
        if ($list === NULL) {
           throw new FFException(FFErrCode::SERVER_FAILURE);
        }
        
        foreach ($list as $row) {
            $row->message = $row->message;
            $row->timestamp = intval($row->timestamp);
        }
        
        return $list;

    }

}

?>
