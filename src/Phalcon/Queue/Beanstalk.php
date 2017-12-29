<?php

/**
 * Created by PhpStorm.
 * User: gaopu
 * Date: 2017/12/22
 * Time: 下午12:22
 */
/*
  +------------------------------------------------------------------------+
  | Phalcon Framework                                                      |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2017 Phalcon Team (http://www.phalconphp.com)       |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file LICENSE.txt.                             |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  |          Dmitry Korolev <chameleonweb2012@gmail.com>                   |
  +------------------------------------------------------------------------+
 */

namespace Phalcon\Queue;

use Phalcon\Queue\Beanstalk\Job;
use Phalcon\Queue\Beanstalk\Exception;

/**
 * Phalcon\Queue\Beanstalk
 *
 * Class to access the beanstalk queue service.
 * Partially implements the protocol version 1.2
 *
 * <code>
 * use Phalcon\Queue\Beanstalk;
 *
 * $queue = new Beanstalk(
 *     [
 *         "host"       => "127.0.0.1",
 *         "port"       => 11300,
 *         "persistent" => true,
 *     ]
 * );
 * </code>
 *
 */
class Beanstalk
{

    /**
     * Seconds to wait before putting the job in the ready queue.
     * The job will be in the "delayed" state during this time.
     *
     * @const integer
     */
    const DEFAULT_DELAY = 0;

    /**
     * Jobs with smaller priority values will be scheduled before jobs with larger priorities.
     * The most urgent priority is 0, the least urgent priority is 4294967295.
     *
     * @const integer
     */
    const DEFAULT_PRIORITY = 100;

    /**
     * Time to run - number of seconds to allow a worker to run this job.
     * The minimum ttr is 1.
     *
     * @const integer
     */
    const DEFAULT_TTR = 86400;

    /**
     * Default tube name
     * @const string
     */
    const DEFAULT_TUBE = "default";

    /**
     * Default connected host
     * @const string
     */
    const DEFAULT_HOST = "127.0.0.1";

    /**
     * Default connected port
     * @const integer
     */
    const DEFAULT_PORT = 11300;

    /**
     * Connection resource
     * @var resource
     */
    protected $_connection;

    /**
     * Connection options
     * @var array
     */
    protected $_parameters;

    /**
     * Phalcon\Queue\Beanstalk
     *
     * @param array $parameters
     */
    public function __construct(array $parameters = array())
    {
        if (!isset($parameters["host"])) {
            $parameters["host"] = self::DEFAULT_HOST;
        }

        if (!isset($parameters["port"])) {
            $parameters["port"] = self::DEFAULT_PORT;
        }

        if (!isset($parameters["persistent"])) {
            $parameters["persistent"] = false;
        }

        $this->_parameters = $parameters;
    }

    /**
     * Makes a connection to the Beanstalkd server
     *
     * @return resource
     */
    public function connect()
    {
        $connection = $this->_connection;
        if (is_resource($connection)) {
            $this->disconnect();
        }

        $parameters = $this->_parameters;

        /**
         * Check if the connection must be persistent
         */
        if ($parameters["persistent"]) {
            $connection = pfsockopen($parameters["host"], $parameters["port"]);
        } else {
            $connection = fsockopen($parameters["host"], $parameters["port"]);
        }

        if (is_resource($connection)) {
            throw new Exception("Can't connect to Beanstalk server");
        }

        stream_set_timeout($connection, -1, null);

        $this->_connection = $connection;

        return $connection;
    }

    /**
     * Puts a job on the queue using specified tube.
     *
     * @param $data
     * @param $options array|null
     * @return int|boolean
     */
    public function put($data, array $options = null)
    {

        if (!is_array($options)) {
            throw new Exception('Invalid parameter type.');
        }
        /**
         * Priority is 100 by default
         */
        if (!isset($options["priority"])) {
            $priority = self::DEFAULT_PRIORITY;
        }

        if (!isset($options["delay"])) {
            $delay = self::DEFAULT_DELAY;
        }

        if (!isset($options["ttr"])) {
            $ttr = self::DEFAULT_TTR;
        }

        /**
         * Data is automatically serialized before be sent to the server
         */
        $serialized = serialize($data);

        /**
         * Create the command
         */
        $length   = strlen($serialized);
        $this->write("put " . $priority . " " . $delay . " " . $ttr . " " . $length . "\r\n" . $serialized);
        $response = $this->readStatus();
        $status   = $response[0];

        if ($status != "INSERTED" && $status != "BURIED") {
            return false;
        }

        return (int) response[1];
    }

    /**
     * Reserves/locks a ready job from the specified tube.
     *
     * @param $timeout mixed|null
     * @return bool|Job
     */
    public function reserve($timeout = null)
    {
        if (is_null($timeout)) {
            $command = "reserve-with-timeout " . $timeout;
        } else {
            $command = "reserve";
        }
        $this->write($command);
        $response = $this->readStatus();
        if ($response[0] != "RESERVED") {
            return false;
        }
        /**
         * The job is in the first position
         * Next is the job length
         * The body is serialized
         * Create a beanstalk job abstraction
         */
        return new Job($this, $response[1], unserialize($this->read($response[2])));
    }

    /**
     * Change the active tube. By default the tube is "default".
     *
     * @param $tube mixed
     * @return bool|string
     */
    public function choose($tube)
    {
        $this->write("use " . $tube);

        $response = $this->readStatus();
        if ($response[0] != "USING") {
            return false;
        }

        return $response[1];
    }

    /**
     * The watch command adds the named tube to the watch list
     * for the current connection.
     *
     * @param $tube string
     * @return boolean | int
     */
    public function watch($tube)
    {
        if (!is_string($tube)) {
            throw new Exception('Invalid parameter type.');
        }
        $this->write("watch " . $tube);
        $response = $this->readStatus();
        if ($response[0] != "WATCHING") {
            return false;
        }

        return (int) $response[1];
    }

    /**
     * It removes the named tube from the watch list for the current connection.
     * @param $tube string
     * @return boolean | int
     */
    public function ignore($tube)
    {
        $this->write("ignore " . $tube);
        $response = $this->readStatus();
        if (($response[0] != "WATCHING")) {
            return false;
        }

        return (int) $response[1];
    }

    /**
     * Can delay any new job being reserved for a given time.
     *
     * @param $tube string
     * @param $delay int
     * @return bool
     */
    public function pauseTube($tube, $delay)
    {
        if (!is_string($tube) && !is_int($delay)) {
            throw new Exception('Invalid parameter type.');
        }
        $this->write("pause-tube " . $tube . " " . $delay);

        $response = $this->readStatus();
        if ($response[0] != "PAUSED") {
            return false;
        }

        return true;
    }

    /**
     * The kick command applies only to the currently used tube.
     *
     * @param $bound int
     * @return bool|int
     */
    public function kick($bound)
    {
        if (!is_int($bound)) {
            throw new Exception('Invalid parameter type.');
        }
        $this->write("kick " . $bound);

        $response = $this->readStatus();
        if ($response[0] != "KICKED") {
            return false;
        }

        return (int) $response[1];
    }

    /**
     * Gives statistical information about the system as a whole.
     *
     * @return bool
     */
    public function stats()
    {
        $this->write("stats");

        $response = $this->readYaml();
        if ($response[0] != "OK") {
            return false;
        }

        return $response[2];
    }

    /**
     * Gives statistical information about the specified tube if it exists.
     *
     * @param $tube string
     * @return bool|array
     */
    public function statsTube($tube)
    {
        $this->write("stats-tube " . $tube);

        $response = $this->readYaml();
        if ($response[0] != "OK") {
            return false;
        }

        return $response[2];
    }

    /**
     * Returns a list of all existing tubes.
     *
     * @return bool|array
     */
    public function listTubes()
    {
        $this->write("list-tubes");

        $response = $this->readYaml();
        if ($response[0] != "OK") {
            return false;
        }

        return $response[2];
    }

    /**
     * Returns the tube currently being used by the client.
     *
     * @return  boolean | string
     */
    public function listTubeUsed()
    {
        $this->write("list-tube-used");

        $response = $this->readStatus();
        if ($response[0] != "USING") {
            return false;
        }

        return $response[1];
    }

    /**
     * Returns a list tubes currently being watched by the client.
     * @return  boolean | array
     */
    public function listTubesWatched()
    {
        $this->write("list-tubes-watched");

        $response = $this->readYaml();
        if ($response[0] != "OK") {
            return false;
        }

        return $response[2];
    }

    /**
     * Inspect the next ready job.
     *
     * @return bool|Job
     */
    public function peekReady()
    {
        $this->write("peek-ready");
        $response = $this->readStatus();
        if ($response[0] != "FOUND") {
            return false;
        }
        return new Job($this, $response[1], unserialize($this->read($response[2])));
    }

    /**
     * Return the next job in the list of buried jobs.
     *
     * @return bool|Job
     */
    public function peekBuried()
    {
        $this->write("peek-buried");

        $response = $this->readStatus();
        if ($response[0] != "FOUND") {
            return false;
        }
        return new Job($this, $response[1], unserialize($this->read($response[2])));
    }

    /**
     * Return the next job in the list of buried jobs.
     *
     * @return bool|Job
     */
    public function peekDelayed()
    {
        if (!$this->write("peek-delayed")) {
            return false;
        }
        $response = $this->readStatus();
        if ($response[0] != "FOUND") {
            return false;
        }
        return new Job($this, $response[1], unserialize($this->read($response[2])));
    }

    /**
     * The peek commands let the client inspect a job in the system.
     *
     * @param $id int
     * @return bool|Job
     */
    public function jobPeek($id)
    {
        if (!is_int($id)) {
            throw new Exception('Invalid parameter type.');
        }
        $this->write("peek " . $id);
        $response = $this->readStatus();
        if ($response[0] != "FOUND") {
            return false;
        }
        return new Job($this, $response[1], unserialize($this->read($response[2])));
    }

    /**
     * Reads the latest status from the Beanstalkd server
     *
     * @return array
     */
    final public function readStatus()
    {
        $status = $this->read();
        if ($status === false) {
            return array();
        }
        return explode(" ", $status);
    }

    /**
     * Fetch a YAML payload from the Beanstalkd server
     *
     * @return  array
     */
    final public function readYaml()
    {
        $response = $this->readStatus();
        $status   = $response[0];
        if (count($response) > 1) {
            $umberOfBytes = $response[1];
            $response     = $this->read();
            $data         = yaml_parse($response);
        } else {
            $numberOfBytes = 0;

            $data = array();
        }
        return array(
            status,
            numberOfBytes,
            data
        );
    }

    /**
     * Reads a packet from the socket. Prior to reading from the socket will
     * check for availability of the connection.
     *
     * @param $length int
     * @return boolean | string
     */
    public function read($length = 0)
    {
        if (!is_int($length)) {
            throw new Exception('Invalid parameter type.');
        }
        $connection = $this->_connection;
        if (!is_resource($connection)) {
            $connection = $this->connect();
            if (!is_resource($connection)) {
                return false;
            }
        }
        if ($length) {
            if (feof($connection)) {
                return false;
            }
            $data = rtrim(stream_get_line($connection, $length + 2), "\r\n");
            if ((stream_get_meta_data($connection)["timed_out"])) {
                throw new Exception("Connection timed out");
            }
        } else {
            $data = stream_get_line($connection, 16384, "\r\n");
        }


        if ($data === "UNKNOWN_COMMAND") {
            throw new Exception("UNKNOWN_COMMAND");
        }

        if ($data === "JOB_TOO_BIG") {
            throw new Exception("JOB_TOO_BIG");
        }

        if ($data === "BAD_FORMAT") {
            throw new Exception("BAD_FORMAT");
        }

        if ($data === "OUT_OF_MEMORY") {
            throw new Exception("OUT_OF_MEMORY");
        }

        return $data;
    }

    /**
     * Writes data to the socket. Performs a connection if none is available
     *
     * @param $data string
     * @return  boolean | int
     */
    public function write($data)
    {
        if (!is_string($data)) {
            throw new Exception('Invalid parameter type.');
        }
        $connection = $this->_connection;
        if (!is_resource($connection)) {
            $connection = $this->connect();
            if (!is_resource($connection)) {
                return false;
            }
        }
        $packet = $data . "\r\n";
        return fwrite($connection, $packet, strlen($packet));
    }

    /**
     * Closes the connection to the beanstalk server.
     *
     * @return bool
     */
    public function disconnect()
    {
        $connection = $this->_connection;
        if (!is_resource($connection)) {
            return false;
        }
        fclose($connection);
        $this->_connection = null;

        return true;
    }

    /**
     * Simply closes the connection.
     *
     * @return bool
     */
    public function quit()
    {
        $this->write("quit");
        $this->disconnect();

        return !is_resource($this->_connection);
    }

}
