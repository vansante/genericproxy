<?php
class Snmp implements Plugin{
	/**
	 * Contains a reference to the configuration object
	 * 
	 * @var Config
	 * @access private
	 */
	private $config;
	
	/**
	 * Contains the runtype, either boot or webgui
	 * 
	 * @access private
	 * @var Integer
	 */
	private $runtype;
	
	/**
	 * Contains reference to the plugin framework
	 * 
	 * @access private
	 * @var PluginFramework
	 */
	private $framework;
	
	/**
	 * Returns true if module launches / manages a service
	 * 
	 * @access public
	 * @return Boolean
	 */
	public function isService() {
		return true;
	}
	
	/**
	 * Contains configuration data retrieved from $this->config
	 * 
	 * @access private
	 * @var SimpleXMLElement
	 */
	private $data;
	
	/**
	 * Location of the SNMPD config file
	 * 
	 * @var string
	 */
	const CONFIG_FILE = '/var/etc/snmpd.conf';
	
	/**
	 * Location of the snmpd service PID file
	 * 
	 * @var string
	 */
	const PID_PATH = '/var/run/snmpd.pid';
	
	/**
	 * Constructor
	 *
	 * @access public
	 * @param PluginFramework 	$framework 	plugin framework object reference
	 * @param Config 			$config 	Config object reference
	 * @param Integer			$runtype 	Run type, either boot or webgui
	 */
	public function __construct($framework, $config, $options, $runtype) {
		$this->config = $config;
		$this->runtype = $runtype;
		$this->framework = $framework;
		
		//      Get snmp XML configuration
		$this->data = $this->config->getElement ( 'snmp' );
	}
	
	/**
	 * 	Configure the SNMP daemon
	 * 
	 * 	Writes out /usr/local/share/snmp/snmpd.conf
	 * 
	 * 	@access public
	 */
	public function configure() {
		$config = "# snmpd.conf
			
			# First, map the community name (COMMUNITY) into a security name
			# (local and mynetwork, depending on where the request is coming
			# from):
			
			#	sec.name  source	community
			com2sec	local	  localhost	 public
			com2sec	mynetwork 172.16.0.0/12	 public
			com2sec	mynetwork 10.0.0.0/8	 public
			com2sec	mynetwork 192.168.0.0/16 public
				
			# Second, map the security names into group names:
			
			#		sec.model sec.name
			group MyRWGroup	v1	  local
			group MyRWGroup	v2c	  local
			group MyRWGroup	usm	  local
			group MyROGroup	v1	  mynetwork
			group MyROGroup	v2c	  mynetwork
			group MyROGroup	usm	  mynetwork
			
			# Third, create a view for us to let the groups have rights to:
			
			#	 incl/excl subtree mask
			view all included  .1	   80
			
			# Finally, grant the 2 groups access to the 1 view with different
			# write permissions:
			
			#		 context sec.model sec.level match read	write notif
			access MyROGroup \"\"	 any	   noauth    exact all	none  none
			access MyRWGroup \"\"	 any	   noauth    exact all	all   none
			
			# System contact information
			
			sysLocation Somewhere in or near Leiden
			sysContact Stichting Wireless Leiden <beheer@wirelessleiden.nl> / +31 71 5139817
			
			# Process checks.
			
			#    name	  max min
			proc sshd	  8   1
			proc syslogd	  1   1
			proc ntpd	  1   1
			proc snmpd	  1   1
			proc cron	  2   1
			
			# disk checks
			
			#    path min
			#disk /    90%
			#disk /var 80%
			#disk /usr 80%
			#disk /tmp 60%
			includeAllDisks 85%
			
			# load average checks
			
			#    1max 5max 15max
			load 12   14   14
			
			# Pass through control
			
			#    miboid		  exec-command
			#pass .1.3.6.1.4.1.2021.50 /usr/local/nagios/bin/processor
			
			#pass_persist .1.3.6.1.4.1.21695.1.2 /usr/local/sbin/dhcpd-snmp /usr/local/etc/dhcpd-snmp.conf";
		
		$fp = fopen(self::CONFIG_FILE,'w');
		
		if($fp !== false){
			fwrite($fp,$config);
			fclose($fp);
		}
		else{
			Logger::getRootLogger()->error('Could not open snmpd.conf');
		}
	}

	/**
	 * 	Get dependencies
	 * 
	 * 	@access public
	 */
	public function getDependency() {}

	/**
	 * 	Passthrough function to delegate AJAX front-end functionality
	 * 
	 * 	This function is empty because SNMP has no front-end modules
	 * 
	 * 	@access public
	 */
	public function getPage() {}

	/**
	 * 	Get SNMP status
	 * 
	 * 	@access public
	 * 	@returns Error|Started|Stopped
	 */
	public function getStatus() {
		$pid = file_exists ( self::PID_PATH ) ? Functions::shellCommand ( "pgrep -F " . self::PID_PATH ) : 0;
		if ($pid > 0) {
			return 'Started';
		}
		else{
			return 'Stopped';
		}
	}

	/**
	 * 	Commands to run during device boot
	 * 
	 * 	Called by PluginFramework during device boot
	 * 
	 * 	@access public
	 */
	public function runAtBoot() {
		Logger::getRootLogger()->info('Init SNMP');
		$this->configure();
		$this->start();
	}

	/**
	 * 	Commands to run during shutdown
	 */
	public function shutdown() {}

	/**
	 * 	Start the SNMP daemon
	 * 
	 * 	@access public
	 */
	public function start() {
		Logger::getRootLogger()->info('Starting snmpd');
		$pid = file_exists ( self::PID_PATH ) ? Functions::shellCommand ( "pgrep -F " . self::PID_PATH ) : 0;
		if ($pid > 0) {
			Logger::getRootLogger()->info('Snmpd already running?');
		}
		else{
			Functions::shellCommand('snmpd -c '.self::CONFIG_FILE);
		}
	}

	/**
	 * 	Stop the SNMP daemon
	 * 
	 * 	@access public
	 */
	public function stop() {
		Logger::getRootLogger ()->info ( "Stopping snmpd" );
		$pid = file_exists ( self::PID_PATH ) ? Functions::shellCommand ( "pgrep -F " . self::PID_PATH ) : 0;
		if ($pid > 0) {
			Functions::shellCommand ( "/bin/kill {$pid}" );
		}
	}

	
}
?>