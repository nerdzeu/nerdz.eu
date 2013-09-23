<?php

/**
    Error codes to be returned by FastFetch API.
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

}

/**
 * Exception returned from FastFetch.
 */
final class FFException extends Exception {
    
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

?>
