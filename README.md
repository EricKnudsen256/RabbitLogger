# RabbitLogger
To add the logger submodule to your directory, use the command
git submodule update --init --recursive to install it
In order to update the logger, run the command
git submodule update --remote to get the latest version.
Additionally, you can pull the reposity itself from
https://github.com/EricKnudsen256/RabbitLogger
if that is more convinient for you.

When using this logger, make sure to add 'require_once()' the file.
In order to use the logger, create a new instance, and input the name of the machine to log for.
The logger will automatically use the default name of the host machine returned by gethostname().
All logs will be stored in /var/log/490Logger/<machine_name>.log


You must create a new logger using $logger = new rabbitLogger();
To create a log, use $logger->log_local($reportLevel, $mssg) 
to log onto the local machine, or
$logger->log_rabbit($reportLevel, $mssg) to log on every machine

In order to recieve logs from all machines, 

I suggest we use standardized our warning levels, so if we want
to grep through the files it will be easier, I reccomend:
Debug: Use this one when specifically writing code for debugs
Info: Use for noncritical information that might be useful
Warning: Use for info that could be a problem
Error: Use for critical problems that will likely break systems
Try to keep these error levels with the first letter capitalized

