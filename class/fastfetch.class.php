<?php
namespace NERDZ\Core;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

/*
 * FastFetch is a class for fast pm fetching - and maybe more, someday.
 */

final class FastFetch {

    private $mPm;
    private $user;

    public function __construct() {
        $this->mPm = new Pms();
        $this->user = new User();
    }


    /**
     * Returns true if current session is associated with a logged user.
     * @return boolean 
     */
    public function isLogged() {
        return $this->user->isLogged();
    }

    /**
     * Returns all conversations.
     * 
     * @return array an object containing the list of conversations.
     * @throws FFException if something wrong happens
     */
    public function fetchConversations() {

        $ret = new \stdClass();

        $list = $this->mPm->getList();

        if($list !== NULL) {

            $ret = [];

            foreach($list as $conversation) {

                $result = $this->mPm->getLastMessageForConversation((int) $conversation['fromid_n']);

                if ($result === false) {
                    throw new FFException(FFErrCode::SERVER_FAILURE);
                }

                $element = [
                            'name' => html_entity_decode($conversation['from_n'], ENT_QUOTES, 'UTF-8'),
                            'last_timestamp' => intval($conversation['timestamp_n']),
                            'id' => $conversation['fromid_n'],
                            'last_message' => $result->message,
                            'last_sender' => $result->last_sender,
                            'new_messages' => ($result->to_read) && ((int)$result->last_sender !== (int)$_SESSION['id'])
                        ];
                $ret[] = $element;
            }
        } else {
            throw new FFException(FFErrCode::SERVER_FAILURE);
        }

        return $ret;

    }

    /**
     * Fetches $limit Messages starting with the $start-th in the conversation with user $otherId.
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

        $me = $this->user->getId();

        $list = Db::query (
            [
                'SELECT ("from" = :me) AS sent, EXTRACT(EPOCH FROM time) AS timestamp, message, to_read AS read FROM "pms" WHERE ("from" = :other AND "to" = :me) OR ("to" = :other AND "from" = :me) ORDER BY TIME DESC LIMIT '.$limit.' OFFSET '.$start, 
                [
                    ':me' => $me,
                    ':other' => $otherId
                ]
            ],
            Db::FETCH_OBJ,
            true 
        );

        if ($list === NULL) {
            throw new FFException(FFErrCode::SERVER_FAILURE);
        }

        foreach ($list as $row) {
            $row->message = $row->message;
            $row->timestamp = intval($row->timestamp);
        }

        if(Db::NO_ERRNO != 
            Db::query(
                [
                    'UPDATE "pms" SET "to_read" = FALSE WHERE "from" = :from AND "to" = :id',
                    [
                        ':from' => $otherId, 
                        ':id' => $me
                    ]
                ],
                Db::FETCH_ERRNO
            )
        ) {
            throw new FFException(FFErrCode::SERVER_FAILURE);
        }

        return $list;

    }

    public function getIdFromUsername($userName) {

        $userName = htmlspecialchars($userName,ENT_QUOTES,'UTF-8');

        $idObj = Db::query(
            [
                'SELECT counter FROM users WHERE LOWER(username) = LOWER(:user)',
                    [ ':user' => $userName ]
                ],
                Db::FETCH_OBJ
            );

        if (!is_object($idObj)) {
            throw new FFException(FFErrCode::USER_NOT_FOUND);
        }

        return ['id' => $idObj->counter];
    }

}

/**
 * Error codes to be returned by FastFetch API.
 */
final class FFErrCode {    
    /**
     * Unknown error code.
     */
    const UNKNOWN = -0x1;

    /**
     * Everything is good. Pretty useless.
     */
    const NOTHING_WRONG = 0x0;

    /**
     * The user is not logged in.
     */
    const NOT_LOGGED = 0x1;

    /**
     * The user has not provided an action.
     */
    const NO_ACTION = 0x2;

    /**
     * The user has provided an invalid or unknown action.
     */
    const INVALID_ACTION = 0x3;

    /**
     * The server is not passing a good moment. Please, leave him alone.
     */
    const SERVER_FAILURE = 0x4;

    /**
     * The request is malformed.
     */
    const WRONG_REQUEST = 0x5;

    /**
     * The user has not provided an user id to be used with the the given action.
     */
    const NO_OTHER_ID = 0x6;

    /**
     * The user has provided a limit which is higher than the max.
     */
    const LIMIT_EXCEEDED = 0x7;

    /**
     * The given username has not been found.
     */
    const USER_NOT_FOUND = 0x8;
}

/**
 * Exception returned from FastFetch.
 */
final class FFException extends \Exception {

    /**
     * A FFErrCode.
     * @var int
     */
    public $code;

    public function __construct($code) {

        parent::__construct("FFError with code $code", $code, NULL);
        $this->mCode = $code;

    }

}

