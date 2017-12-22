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

        if (!isset ($parameters["persistent"])) {
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
     * @param
     * @return int|boolean
     */
    public function put($data, array $options = null)
    {

        if(!is_array($options)){
            throw new Exception("Param is ");
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
        $length = strlen($serialized);
        $this->write("put " . $priority . " " . $delay . " " . $ttr . " " . $length . "\r\n" . $serialized);
        $response = $this->readStatus();
        $status = $response[0];

        if ($status != "INSERTED" && $status != "BURIED") {
            return false;
        }

        return (int)response[1];
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
     */
    public function choose(tube) -> boolean | string
	{
        var
        response;

        this->write("use " . tube);

		let response = this->readStatus();
		if response[0] != "USING" {
            return false;
        }

return response[1];
}

/**
 * The watch command adds the named tube to the watch list for the current connection.
 */
public
function watch(string!tube) -> boolean | int
	{
        var
        response;

        this->write("watch " . tube);

		let response = this->readStatus();
		if response[0] != "WATCHING" {
            return false;
        }

		return (int)response[1];
	}

	/**
     * It removes the named tube from the watch list for the current connection.
     */
	public function ignore(string!tube) -> boolean | int
	{
        var
        response;

        this->write("ignore " . tube);

		let response = this->readStatus();
		if response[0] != "WATCHING" {
            return false;
        }

		return (int)response[1];
	}

	/**
     * Can delay any new job being reserved for a given time.
     */
	public function pauseTube(string!tube, int delay) -> boolean
	{
        var
        response;

        this->write("pause-tube " . tube . " " . delay);

		let response = this->readStatus();
		if response[0] != "PAUSED" {
            return false;
        }

		return true;
	}

	/**
     * The kick command applies only to the currently used tube.
     */
	public function kick(int bound) -> boolean | int
	{
        var
        response;

        this->write("kick " . bound);

		let response = this->readStatus();
		if response[0] != "KICKED" {
            return false;
        }

		return (int)response[1];
	}

	/**
     * Gives statistical information about the system as a whole.
     */
	public function stats() -> boolean | array
	{
        var
        response;

        this->write("stats");

		let response = this->readYaml();
		if response[0] != "OK" {
            return false;
        }

		return response[2];
	}

	/**
     * Gives statistical information about the specified tube if it exists.
     */
	public function statsTube(string!tube) -> boolean | array
	{
        var
        response;

        this->write("stats-tube " . tube);

		let response = this->readYaml();
		if response[0] != "OK" {
            return false;
        }

		return response[2];
	}

	/**
     * Returns a list of all existing tubes.
     */
	public function listTubes() -> boolean | array
	{
        var
        response;

        this->write("list-tubes");

		let response = this->readYaml();
		if response[0] != "OK" {
            return false;
        }

		return response[2];
	}

	/**
     * Returns the tube currently being used by the client.
     */
	public function listTubeUsed() -> boolean | string
	{
        var
        response;

        this->write("list-tube-used");

		let response = this->readStatus();
		if response[0] != "USING" {
            return false;
        }

		return response[1];
	}

	/**
     * Returns a list tubes currently being watched by the client.
     */
	public function listTubesWatched() -> boolean | array
	{
        var
        response;

        this->write("list-tubes-watched");

		let response = this->readYaml();
		if response[0] != "OK" {
            return false;
        }

		return response[2];
	}

	/**
     * Inspect the next ready job.
     */
	public function peekReady() -> boolean |<Job >
	{
        var
        response;

        this->write("peek-ready");

		let response = this->readStatus();
		if response[0] != "FOUND" {
            return false;
        }

		return new Job(this, response[1], unserialize(this->read(response[2])));
	}

	/**
     * Return the next job in the list of buried jobs.
     */
	public function peekBuried() -> boolean |<Job >
	{
        var
        response;

        this->write("peek-buried");

		let response = this->readStatus();
		if response[0] != "FOUND" {
            return false;
        }

		return new Job(this, response[1], unserialize(this->read(response[2])));
	}

	/**
     * Return the next job in the list of buried jobs.
     */
	public function peekDelayed() -> boolean |<Job >
	{
        var
        response;

        if !this->write("peek-delayed"){
			return false;
		}

		let response = this->readStatus();
		if response[0] != "FOUND" {
            return false;
        }

		return new Job(this, response[1], unserialize(this->read(response[2])));
	}

	/**
     * The peek commands let the client inspect a job in the system.
     */
	public function jobPeek(int id) -> boolean |<Job >
	{
        var
        response;

        this->write("peek " . id);

		let response = this->readStatus();

		if response[0] != "FOUND" {
            return false;
        }

		return new Job(this, response[1], unserialize(this->read(response[2])));
	}

	/**
     * Reads the latest status from the Beanstalkd server
     */
	final public function readStatus() -> array
	{
        var
        status;
        let status = this->read();
		if status === false {
            return [];
        }
		return explode(" ", status);
	}

	/**
     * Fetch a YAML payload from the Beanstalkd server
     */
	final public function readYaml() -> array
	{
        var
        response, status, numberOfBytes, data;

		let response = this->readStatus();

		let status = response[0];

		if count(response) > 1 {
            let numberOfBytes = response[1];

			let response = this->read();

			let data = yaml_parse(response);
		} else {
            let numberOfBytes = 0;

			let data = [];
		}

		return [
            status,
            numberOfBytes,
            data
        ];
	}

	/**
     * Reads a packet from the socket. Prior to reading from the socket will
     * check for availability of the connection.
     */
	public function read(int length = 0) -> boolean | string
	{
        var
        connection, data;

		let connection = this->_connection;
		if typeof connection != "resource" {
        let connection = this->connect();
			if typeof connection != "resource" {
            return false;
        }
		}

		if length {

            if feof(connection){
				return false;
        }

            let data = rtrim(stream_get_line(connection, length + 2), "\r\n");
			if stream_get_meta_data(connection)["timed_out"]{
				throw new Exception("Connection timed out");
			}
		} else {
            let data = stream_get_line(connection, 16384, "\r\n");
		}


		if data === "UNKNOWN_COMMAND" {
            throw new Exception("UNKNOWN_COMMAND");
        }

		if data === "JOB_TOO_BIG" {
            throw new Exception("JOB_TOO_BIG");
        }

		if data === "BAD_FORMAT" {
            throw new Exception("BAD_FORMAT");
        }

		if data === "OUT_OF_MEMORY" {
            throw new Exception("OUT_OF_MEMORY");
        }

		return data;
	}

	/**
     * Writes data to the socket. Performs a connection if none is available
     */
	public function write(string data) -> boolean | int
	{
        var
        connection, packet;

		let connection = this->_connection;
		if typeof connection != "resource" {
        let connection = this->connect();
			if typeof connection != "resource" {
            return false;
        }
		}

		let packet = data . "\r\n";
		return fwrite(connection, packet, strlen(packet));
	}

	/**
     * Closes the connection to the beanstalk server.
     */
	public function disconnect() -> boolean
	{
        var
        connection;

        let connection = this->_connection;
		if typeof connection != "resource" {
        return false;
    }

		fclose(connection);
		let this->_connection = null;

		return true;
	}

	/**
     * Simply closes the connection.
     */
	public function quit() -> boolean
	{
        this->write("quit");
		this->disconnect();

		return typeof this->_connection != "resource";
	}
}
