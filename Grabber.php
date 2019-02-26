<?php 

@define( "BLUE",   "\033[5;34m" );
@define( "RED",    "\033[5;31m" );
@define( "ORANGE", "\033[1;31m" );
@define( "GREEN",  "\033[5;32m" );
@define( "YELLOW", "\033[5;33m" );
@define( "NONE",   "\033[0m" );

include __DIR__."/utils.php";

$log = [
	"err"  => "[Error] - ",
	"crit" => "[Critical] - ",
	"info" => "[Info] - ",
];

$urls = [
	'main'    => 'http://www.gatherproxy.com/',
	'country' => 'http://www.gatherproxy.com/proxylist/country/?c='
];

$regex = "/((gp\.insertPrx\(\{)(.*)(\}\)\;))/";

$banner = RED . "
 _____                      _____           _     _               
|  __ \                    / ____|         | |   | |              
| |__) | __ _____  ___   _| |  __ _ __ __ _| |__ | |__   ___ _ __ 
|  ___/ '__/ _ \ \/ / | | | | |_ | '__/ _` | '_ \| '_ \ / _ \ '__|
| |   | | | (_) >  <| |_| | |__| | | | (_| | |_) | |_) |  __/ |   
|_|   |_|  \___/_/\_\\__, |\_____|_|  \__,_|_.__/|_.__/ \___|_|   
                      __/ |                                       
					 |___/

		        ┌──────────────┐
		        │  By: killua  │
		        └──────────────┘
" . NONE . "\n";

$options = "
".GREEN."[+]".NONE." Options
    ".GREEN."[1]".NONE." Display available proxy countries
    ".GREEN."[2]".NONE." Get proxies";

$ch       = GREEN. "[*]" .NONE." Choose option: ";
$ch_cntry = GREEN. "[*]" .NONE." Country: ";
$exit     = GREEN. "[3]" .NONE." Exit";

# Start of program
main( parse_countries( 'countries.dat' ), parse_user_agents( 'u-agents.dat' ), $banner, $options, $exit, $ch, $ch_cntry );

function parse_countries ( string $file_name ) : array {
	$file = __DIR__.'/'.$file_name;
	if ( is_file( $file ) ) {
		return @explode( "\x0a", @file_get_contents( $file ) );
	}
	return [];
}

function parse_user_agents ( string $file_name ) : array {
	$file = __DIR__.'/'.$file_name;
	if ( is_file( $file ) ) {
		return @explode( "\x0a", @file_get_contents( $file ) );
	}
	return [];
}

function init_request ( string $request_url, array $agents, array $countries ){
	if ( !empty( $agents ) && !empty( $countries ) ) {
		$options = [
			CURLOPT_URL            => $request_url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT      => $agents[ mt_rand( 0, sizeof( $agents ) - 2 ) ],
			CURLOPT_CONNECTTIMEOUT => 120,
			CURLOPT_TIMEOUT        => 120
		];

		$curl = curl_init();
		if ( !function_exists( 'curl_setopt_array' ) ) {
			function curl_setopt_array( &$ch, $curl_options ){
				foreach ( $curl_options as $option => $value ) {
					if ( !curl_setopt( $ch, $option, $value ) ) {
						return false;
					}
				}
				return true;
			}
		}
		if ( curl_setopt_array( $curl, $options ) ){
			return $curl;
		}
	}
}

function display_countries( array $countries ) : void {
	$i = -1;
	while ( ($i++) < sizeof( $countries ) - 2 ) {
		echo GREEN . "[$i] - " . NONE . $countries[$i] . "\n";
	}
	echo "\n";
}

function get_proxies( string $by_country, array $agents, array $countries ){
	$i       = -1;
	$proxies = [];
	$urls    = (object) $GLOBALS['urls'];
	$regex   = $GLOBALS['regex'];
	
	if ( strcmp( $by_country, '' ) !== 0 ) {
		$request = init_request( $urls->country . $by_country, $agents, $countries );
	} else{
		$request = init_request( $urls->main, $agents, $countries );
	}

	$response = curl_exec( $request );
	preg_match_all( $regex, $response, $matches );
	
	while ( ($i++) < sizeof( $matches[0] ) - 1 ) {
		$proxinfo = explode( ',', substr( $matches[0][$i], 15, -3 ) );
		$proxies[$i] = [
			'city'          => substr( explode( ':', $proxinfo[0] )[1], 1, -1 ),
			'country'       => substr( explode( ':', $proxinfo[1] )[1], 1, -1 ),
			'ip'            => substr( explode( ':', $proxinfo[2] )[1], 1, -1 ),
			'port'          => hex_port_to_dec( substr( explode( ':', $proxinfo[4] )[1], 1, -1 ) ),
			'response_time' => substr( explode( ':', $proxinfo[8] )[1], 1, -1 ),
			'anon'          => substr( explode( ':', $proxinfo[9] )[1], 1, -1 ),
			'uptime'        => substr( explode( ':', $proxinfo[11] )[1], 1, -1 )
		];
	}
	return $proxies;
}

function display_proxies( array $proxies ) : void {
	$i = -1;
	echo PROXY_TABLE_TOP;

	while ( ($i++) < sizeof( $proxies ) - 1 ) {
		$row = build_proxy_row( format_ip_column( $proxies[$i]['ip'] ), format_port_column( (string) $proxies[$i]['port'] ), format_anon_column( $proxies[$i]['anon'] ),
			   format_country_column( $proxies[$i]['country'] ), format_city_column( $proxies[$i]['city'] ), format_uptime_column( $proxies[$i]['uptime'] ),
			   format_resp_column( $proxies[$i]['response_time']),
			   NUM_COLUMNS,
			   sizeof( $proxies ),
			   $i
	   );
	   if ( $i === sizeof( $proxies ) - 1 ) {
		   $row .= PROXY_TABLE_BOTTOM;
	   }
	   echo $row;
	   save_to_file( $proxies[$i]['ip'] . ":" . $proxies[$i]['port'], 'proxies.dat' );
	}
}

function save_to_file( string $proxy, string $file_name ) : void {
	file_put_contents( $file_name, $proxy . "\n", FILE_APPEND | LOCK_EX );
}

function main( array $countries, array $agents, string $banner, string $options, string $exit, string $ch, string $ch_cntry ) : void {
	$log    = (object) $GLOBALS['log'];
	$menu   = $banner . $options;
	$option = (int) prompt_choice( $menu, $exit, $ch );
	
	while ( $option !== 3 ) {
		if ( $option <= 0 || $option > 3 ) {
			die( RED . $log->err . NONE . "Invalid option!\n" );
		}
		
		switch ( $option ) {
			case 1:
				display_countries( $countries );
				if ( strcmp( strtolower( go_back() ), 'y' ) === 0 ) {
					$option = (int) prompt_choice( $menu, $exit, $ch );
				} else{
					die( RED . "Good Bye!\n" . NONE );
				}
				break;

			case 2:
				$country = prompt_for_country( $menu, $exit, $ch_cntry, $ch );
				if ( strcmp( strtolower( $country ), 'all' ) !== 0 && !in_array( $country, $countries ) ) {
					die( RED . $log->err . NONE . "Country is not in the list!\n" );
				}
				
				switch ( $country ) {
					case 'all':
						$proxies = get_proxies( '', $agents, $countries );
						break;
					default:
						$proxies = get_proxies( $country, $agents, $countries );
						break;
				}

				# display the proxies
				display_proxies( $proxies );

				if ( strcmp( strtolower( go_back() ), 'y' ) === 0 ) {
					$option = (int) prompt_choice( $menu, $exit, $ch );
				} else{
					die( RED . "Good Bye!\n" . NONE );
				}
				break;
		}
	}
}
?>
