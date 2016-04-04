<?php
/*
Copyright (C) 2016 Paolo Galeone <nessuno@nerdz.eu>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace NERDZ\Core;

//First we load the Predis autoloader
require_once __DIR__.'/vendor/autoload.php';

use Predis;

Predis\Autoloader::register();

/**
 * RedisSessionHandler class.
 *
 * @class           RedisSessionHandler
 * @file            RedisSessionHandler.class.php
 * @brief           This class is used to store session data with redis, it store in json the session to be used more easily in Node.JS/Golang
 *
 * @version         0.1
 * @date            2012-04-11
 *
 * @author          deisss
 * @licence         LGPLv3
 * 
 * @other           Improved version to support namespace and included in NERDZ\Core namespace (Paolo Galeone [ nessuno@nerdz.eu ] )
 * This class is used to store session data with redis, it store in json the session to be used more easily in Node.JS
 */
class RedisSessionHandler implements \SessionHandlerInterface
{
    private $host = '127.0.0.1';
    private $port = 6379;
    private $lifetime = 0;
    private $redis = null;

    /**
     * Constructor.
     */
    public function __construct($host = null, $port = null)
    {
        if ($host !== null) {
            $this->host = $host;
        }
        if ($post !== null) {
            $this->port = $port;
        }
        $this->redis = new Predis\Client(array(
            'scheme' => 'tcp',
            'host' => $this->host,
            'port' => $this->port,
        ));
        session_set_save_handler($this, true);
        session_start();
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        session_write_close();
        $this->redis->disconnect();
    }

    /**
     * Open the session handler, set the lifetime ot session.gc_maxlifetime.
     *
     * @return bool True if everything succeed
     */
    public function open($savePath, $sessionName) //parameters required by implemented interface
    {
        $this->lifetime = ini_get('session.gc_maxlifetime');

        return true;
    }

    /**
     * Read the id.
     *
     * @param string $id The SESSID to search for
     *
     * @return string The session saved previously
     */
    public function read($id)
    {
        $tmp = $_SESSION;
        $_SESSION = json_decode($this->redis->get("sessions/{$id}"), true);

        if (isset($_SESSION) && !empty($_SESSION) && $_SESSION != null) {
            $new_data = session_encode();
            $_SESSION = $tmp;

            return $new_data;
        } else {
            return '';
        }
    }

    /**
     * Write the session data, convert to json before storing.
     *
     * @param string $id   The SESSID to save
     * @param string $data The data to store, already serialized by PHP
     *
     * @return bool True if redis was able to write the session data
     */
    public function write($id, $data)
    {
        $tmp = $_SESSION;
        session_decode($data);
        $new_data = $_SESSION;
        $_SESSION = $tmp;

        $this->redis->set("sessions/{$id}", json_encode($new_data));
        $this->redis->expire("sessions/{$id}", $this->lifetime);

        return true;
    }

    /**
     * Delete object in session.
     *
     * @param string $id The SESSID to delete
     *
     * @return bool True if redis was able delete session data
     */
    public function destroy($id)
    {
        return $this->redis->del("sessions/{$id}");
    }

    /**
     * Close gc.
     *
     * @return bool Always true
     */
    public function gc($maxlifetime) //parameters required by implemented interface
    {
        return true;
    }

    /**
     * Close session.
     *
     * @return bool Always true
     */
    public function close()
    {
        return true;
    }
}
