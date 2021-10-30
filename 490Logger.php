<?php

require_once("get_host_info.inc");



//when using this logger, make sure to add 'require_once()' the file
//in order to use the logger, create a new instance,
//and input the name of the machine to log for
//if you want to give it a new name, otherwise the default name
//will be the name of the machine you are logging from.
//All logs will be stored in /var/log/490Logger/<machine_name>.log


//You must create a new logger using $logger = new rabbitLogger();
//To create a log, use $logger->log_local($reportLevel, $mssg)
//to log onto the local machine, or
//$logger->log_rabbit($reportLevel, $mssg) to log on ever machine

//I suggest we use standardized our warning levels, so if we want
//to grep through the files it will be easier, I reccomend:
//Debug: Use this one when specifically writing code for debugs
//Info: Use for noncritical information that might be useful
//Warning: Use for info that could be a problem
//Error: Use for critical problems that will likely break systems
//Try to keep these error levels with the first letter capitalized

class rabbitLogger
{

	private $machine = "";
	private $machineName;
    public  $BROKER_HOST;
    private $BROKER_PORT;
    private $USER;
    private $PASSWORD;
    private $VHOST;
    private $exchange;
    private $queue;
    private $routing_key = '*';
    private $exchange_type = "topic";
    private $auto_delete = false;

        function __construct($machine, $server = "rabbitMQ")
        {
            $this->machineName = gethostname();
            $this->machine = getHostInfo(array($machine));
            $this->BROKER_HOST   = $this->machine[$server]["BROKER_HOST"];
            $this->BROKER_PORT   = $this->machine[$server]["BROKER_PORT"];
            $this->USER     = $this->machine[$server]["USER"];
            $this->PASSWORD = $this->machine[$server]["PASSWORD"];
            $this->VHOST = $this->machine[$server]["VHOST"];
            if (isset( $this->machine[$server]["EXCHANGE_TYPE"]))
            {
                $this->exchange_type = $this->machine[$server]["EXCHANGE_TYPE"];
            }
            if (isset( $this->machine[$server]["AUTO_DELETE"]))
            {
                $this->auto_delete = $this->machine[$server]["AUTO_DELETE"];
            }
            $this->exchange = $this->machine[$server]["EXCHANGE"];
            $this->queue = $this->machine[$server]["QUEUE"];
            
        }
	
	function log_local(string $reportLevel, string $mssg)
	{
	    $bt = debug_backtrace();
	    $caller = array_shift($bt);

	    $filename = $caller['file'];
	    $linenum = $caller['line'];

	    $dir = '/var/log/490Logger';
	    if(!file_exists($dir))
	    {
	        mkdir($dir);
	    }

	    $file = $dir.'/'.$this->machineName.'.log';
	    file_put_contents($file,
	      $filename.':'.$linenum.':'.$reportLevel.': '.$mssg.PHP_EOL,
	      FILE_APPEND);
	}
	
	//logs message once it is recieved
	function echo_success($msg)
	{
        echo "Log sent to server sucessfully".PHP_EOL;
	}
	
	//function sends log to rabbit server for all listeners to pick up
    function log_rabbit(string $reportLevel, string $mssg)
	{
        $bt = debug_backtrace();
	    $caller = array_shift($bt);

	    $filename = $caller['file'];
	    $linenum = $caller['line'];

	    $dir = '/var/log/490Logger';
	    
	    $message = $filename.':'.$linenum.':'.$reportLevel.': '.$mssg.PHP_EOL;

	    $uid = uniqid();
	    
	    //$file = $dir.'/'.$this->machine.'.log';
	    
	    $toEncode = array();
	    $toEncode['message'] = $message;
	    $toEncode['machine'] = $this->machineName;
	    //$toEncode['filename'] = $file;

		$json_message = json_encode($toEncode);
		
            $params = array();
            $params['host'] = $this->BROKER_HOST;
            $params['port'] = $this->BROKER_PORT;
            $params['login'] = $this->USER;
            $params['password'] = $this->PASSWORD;
            $params['vhost'] = $this->VHOST;

			$conn = new AMQPConnection($params);
			$conn->connect();

			$channel = new AMQPChannel($conn);

			$exchange = new AMQPExchange($channel);
            $exchange->setName($this->exchange);
            $exchange->setType($this->exchange_type);

            /*
            $callback_queue = new AMQPQueue($channel);
            $callback_queue->setName($this->queue."_response");
            $callback_queue->declare();
			$callback_queue->bind($exchange->getName(),$this->routing_key.".response");
			*/

			$this->conn_queue = new AMQPQueue($channel);
			$this->conn_queue->setName($this->queue);
			$this->conn_queue->bind($exchange->getName(),$this->routing_key);

			$exchange->publish($json_message,$this->routing_key,AMQP_NOPARAM);
			
			/*
            $this->response_queue[$uid] = "waiting";
			$callback_queue->consume(array($this,'echo_success'));

            $response = $this->response_queue[$uid];
			unset($this->response_queue[$uid]);
			return $response;
			*/
        
    }
}

class rabbitLogListener
{
    private $machine = "";
	public  $BROKER_HOST;
	private $BROKER_PORT;
	private $USER;
	private $PASSWORD;
	private $VHOST;
	private $exchange;
	private $queue;
	private $routing_key = '*';
	private $exchange_type = "topic";
	private $auto_delete = false;

	function __construct($machine, $server = "rabbitMQ")
	{
		$this->machine = getHostInfo(array($machine));
		$this->BROKER_HOST   = $this->machine[$server]["BROKER_HOST"];
		$this->BROKER_PORT   = $this->machine[$server]["BROKER_PORT"];
		$this->USER     = $this->machine[$server]["USER"];
		$this->PASSWORD = $this->machine[$server]["PASSWORD"];
		$this->VHOST = $this->machine[$server]["VHOST"];
		if (isset( $this->machine[$server]["EXCHANGE_TYPE"]))
		{
			$this->exchange_type = $this->machine[$server]["EXCHANGE_TYPE"];
		}
		if (isset( $this->machine[$server]["AUTO_DELETE"]))
		{
			$this->auto_delete = $this->machine[$server]["AUTO_DELETE"];
		}
		$this->exchange = $this->machine[$server]["EXCHANGE"];
		$this->queue = $this->machine[$server]["QUEUE"];
	}
	
	function log_from_queue($msg)
	{
		//echo "found message".PHP_EOL;
		//var_dump($msg);
		$dir = '/var/log/490Logger';
		
		$machineName = $msg['machine'];
	    $file = $dir.'/'.$machineName.'.log';
	    file_put_contents($file,
	      $msg['message'],
	      FILE_APPEND);
        
		
	}
	
	function process_log($msg)
	{
		// send the ack to clear the item from the queue
		if ($msg->getRoutingKey() !== "*")
        {
            return;
        }
        $this->conn_queue->nack($msg->getDeliveryTag());
		try
		{
			
            $body = $msg->getBody();
            $payload = json_decode($body, true);
				
				
            $this->log_from_queue($payload);

            return;
				

		}
		catch(Exception $e)
		{
			// ampq throws exception if get fails...
            echo "error: rabbitMQServer: process_message: exception caught: ".$e;
		}
		// message does not require a response, send ack immediately
		
	}

	//function sets up a log listener to wait for a log in its queue, then locally logs it
	function listen_for_logs()
	{
		try
		{
			//$this->callback = $callback;
			
            $params = array();
            $params['host'] = $this->BROKER_HOST;
            $params['port'] = $this->BROKER_PORT;
            $params['login'] = $this->USER;
            $params['password'] = $this->PASSWORD;
            $params['vhost'] = $this->VHOST;
      
			$conn = new AMQPConnection($params);
			$conn->connect();

			$channel = new AMQPChannel($conn);

			$exchange = new AMQPExchange($channel);
            $exchange->setName($this->exchange);
            $exchange->setType($this->exchange_type);

			$this->conn_queue = new AMQPQueue($channel);
			$this->conn_queue->setName($this->queue);
			$this->conn_queue->bind($exchange->getName(),$this->routing_key);

			//list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);
			
			//$channel->queue_bind($queue_name, $this->exchange);
			
			$this->conn_queue->consume(array($this,'process_log'));

			// Loop as long as the channel has callbacks registered
			while (count($channel->callbacks))
			{
				$channel->wait();
			}
		}
		catch (Exception $e)
		{
			trigger_error("Failed to start request processor: ".$e,E_USER_ERROR); 
		}
	}
}

?>
