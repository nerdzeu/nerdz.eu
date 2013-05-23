<?php
//First we load the Predis autoloader
// - since we are included by core.class AFTER the load of constants, we can
//   check if the old API is enabled or not
if (REDIS_USE_OLD_API)
    require_once 'Predis/autoload.php'; //si trova in /usr/share/pear
else
    require_once 'Predis/Autoloader.php';
//Registering Predis system
Predis\Autoloader::register();
 
/**
 * redisSessionHandler class
 * @class           redisSessionHandler
 * @file            redisSessionHandler.class.php
 * @brief           This class is used to store session data with redis, it store in json the session to be used more easily in Node.JS
 * @version         0.1
 * @date            2012-04-11
 * @author          deisss
 * @licence         LGPLv3
 *
 * This class is used to store session data with redis, it store in json the session to be used more easily in Node.JS
 */
class redisSessionHandler implements SessionHandlerInterface
{
    private $host = "127.0.0.1";
    private $port = 6379;
    private $lifetime = 0;
    private $redis = null;
 
    /**
     * Constructor
    */
    public function __construct(){
        $this->redis = new Predis\Client(array(
            "scheme" => "tcp",
            "host" => $this->host,
            "port" => $this->port
        ));
        session_set_save_handler($this,true);
		  session_start();
    }
 
    /**
     * Destructor
    */
    public function __destruct(){
        session_write_close();
        $this->redis->disconnect();
    }
 
    /**
     * Open the session handler, set the lifetime ot session.gc_maxlifetime
     * @return boolean True if everything succeed
    */
    public function open($savePath, $sessionName){ //variabili inutili ai nostri fini, ma indispensabili per implementare l'interfaccia
        $this->lifetime = ini_get('session.gc_maxlifetime');
        return true;
    }
 
    /**
     * Read the id
     * @param string $id The SESSID to search for
     * @return string The session saved previously
    */
    public function read($id){
        $tmp = $_SESSION;
        $_SESSION = json_decode($this->redis->get("sessions/{$id}"), true);
        if(isset($_SESSION) && !empty($_SESSION) && $_SESSION != null){
            $new_data = session_encode();
            $_SESSION = $tmp;
            return $new_data;
        }else{
            return "";
        }
    }
 
    /**
     * Write the session data, convert to json before storing
     * @param string $id The SESSID to save
     * @param string $data The data to store, already serialized by PHP
     * @return boolean True if redis was able to write the session data
    */
    public function write($id, $data){
        $tmp = $_SESSION;
        session_decode($data);
        $new_data = $_SESSION;
        $_SESSION = $tmp;
 
        $this->redis->set("sessions/{$id}", json_encode($new_data));
        $this->redis->expire("sessions/{$id}", $this->lifetime);
        return true;
    }
 
    /**
     * Delete object in session
     * @param string $id The SESSID to delete
     * @return boolean True if redis was able delete session data
    */
    public function destroy($id){
        return $this->redis->del("sessions/{$id}");
    }
 
    /**
     * Close gc
     * @return boolean Always true
    */
    public function gc($maxlifetime){ //parametro inutile, ma indispensabile per implementare l'interfaccia
        return true;
    }
 
    /**
     * Close session
     * @return boolean Always true
    */
    public function close(){
        return true;
    }
}
 
new redisSessionHandler();
?>
