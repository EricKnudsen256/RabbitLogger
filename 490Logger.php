<?php


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

	private $machine;

	function __construct($machine = '')
	{
		    if($this->machine == '')
		    {
			$this->machine = gethostname();
		    }
		    else
		    {
			$this->machine = $machine;
		    }
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

	    $file = $dir.'/'.$this->machine.'.log';
	    file_put_contents($file,
	      $filename.':'.$linenum.':'.$reportLevel.': '.$mssg.PHP_EOL,
	      FILE_APPEND);
	}

	function log_rabbit()
	{

	}
}
?>
