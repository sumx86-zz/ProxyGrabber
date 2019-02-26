<?php 

@define( "NUM_COLUMNS",             7 );
@define( "COLUMN_BOX_LEN",         21 );
@define( "COUNTRY_COLUMN_BOX_LEN", 35 );
@define( "ANON_COLUMN_BOX_LEN",    35 );

@define( "BOTTOM_INIT",    "└───────────────────" );
@define( "BOTTOM_MID",     "┼───────────────────" );
@define( "BOTTOM_END",     "┴───────────────────┘" );

@define( "COUNTRY_BOTTOM_MID", "┴─────────────────────────────────" );
@define( "ANON_BOTTOM_MID",    "┴─────────────────────────────────" );

@define( "PROXY_TABLE_TOP", "
┌───────────────────┬───────────────────┬─────────────────────────────────┬─────────────────────────────────┬───────────────────┬───────────────────┬───────────────────┐
│        ip         │        port       │         anonimity level         │             country             │       city        │    uptime(l/d)    │   response time   │
├───────────────────┼───────────────────┼─────────────────────────────────┼─────────────────────────────────┼───────────────────┼───────────────────┼───────────────────┤
");
@define( "PROXY_TABLE_BOTTOM", "
└───────────────────┴───────────────────┴─────────────────────────────────┴─────────────────────────────────┴───────────────────┴───────────────────┴───────────────────┘
");

function prompt_choice( $menu, $exit, $ch ) {
	$choice = 0;
	system( 'clear' );
	echo $menu . "\n    " . $exit . "\n\n" . $ch;
	fscanf( STDIN, "%d", $choice );
	return $choice;
}

function go_back() : string {
	$choice = '';
	echo GREEN . "Go back?" . NONE . " y/n -> ";
	fscanf( STDIN, "%s", $choice );
	return $choice;
}

function prompt_for_country( $menu, $exit, $ch_cntry, $ch ) {
	$choice = 0;
	system( 'clear' );
	echo $menu . "\n    " . $exit . "\n\n" . $ch_cntry;
	fscanf( STDIN, "%s", $choice );
	return $choice;
}

# formatting functions for correcting padding of text inside the row boxes
function format_ip_column( string $ip ) : string {
	$ip_box     = "│  " . $ip;
	$ip_length  = strlen( $ip );
	$num_spaces = (COLUMN_BOX_LEN - 2) - $ip_length;
	
	for ( $i = 0 ; $i < $num_spaces - 2 ; $i++ ) {
		$ip_box .= "\x20";
	}
	return $ip_box;
}

function format_port_column( string $port ) : string {
	$port_box     = "│       " . $port;
	$port_length  = strlen( $port );
	$num_spaces   = (COLUMN_BOX_LEN - 2) - $port_length;
	
	for ( $i = 0 ; $i < $num_spaces - 7 ; $i++ ) {
		$port_box .= "\x20";
	}
	return $port_box;
}

function format_anon_column( string $anon ) : string {
	$color = null;
	$end   = null;
	switch ( $anon ) {
		case 'Elite':
			$color = GREEN;
			$end   = NONE;
			break;
		case 'Anonymous':
			$color = BLUE;
			$end   = NONE;
			break;
		case 'Transparent':
			$color = ORANGE;
			$end   = NONE;
			break;
	}

	$anon_box     = "│   " . $color . $anon . $end;
	$anon_length  = strlen( $anon );
	$num_spaces   = (ANON_COLUMN_BOX_LEN - 5) - $anon_length;
	
	for ( $i = 0 ; $i < $num_spaces ; $i++ ) {
		$anon_box .= "\x20";
	}
	return $anon_box;
}

function format_country_column( string $country ) : string {
	$country_box     = "│ " . $country;
	$country_length  = strlen( $country );
	$num_spaces      = (COUNTRY_COLUMN_BOX_LEN - 3 ) - $country_length;
	
	for ( $i = 0 ; $i < $num_spaces ; $i++ ) {
		$country_box .= "\x20";
	}
	return $country_box;
	
}

function format_city_column( string $city ) : string {
	$city_box     = "│" . $city;
	$city_length  = strlen( $city );
	$num_spaces   = (COLUMN_BOX_LEN - 2) - $city_length;
	
	for ( $i = 0 ; $i < $num_spaces ; $i++ ) {
		$city_box .= "\x20";
	}
	return $city_box;
}

function format_uptime_column( string $uptime ) : string {
	$uptime1       = explode( '/', $uptime )[0];
	$uptime2       = explode( '/', $uptime )[1];

	$uptime_box    = "│  " . BLUE . $uptime1 . NONE . "/" . RED . $uptime2 . NONE;
	$uptime_length = strlen( $uptime );
	$num_spaces    = (COLUMN_BOX_LEN - 2) - $uptime_length;
	
	for ( $i = 0 ; $i < $num_spaces - 2 ; $i++ ) {
		$uptime_box .= "\x20";
	}
	return $uptime_box;
}

function format_resp_column( string $response ) : string {
	$color = null;
	$end   = null;
	if ( $response <= 350 ) {
		$color = GREEN;
		$end   = NONE;
	} 
	if ( $response > 350 && $response < 650  ) {
		$color = YELLOW;
		$end   = NONE;
	}
	if ( $response > 650 ) {
		$color = ORANGE;
		$end   = NONE;
	}
	
	$resp_box     = "│    " . $color . $response . "ms" . $end;
	$resp_length  = strlen( $response ) + 2;
	$num_spaces   = (COLUMN_BOX_LEN - 2) - $resp_length;
	
	for ( $i = 0 ; $i < $num_spaces - 4 ; $i++ ) {
		$resp_box .= "\x20";
	}
	return $resp_box;
}

# this function builds each row
function build_proxy_row( string $ip, string $port, string $anon, string $country, string $city, string $uptime, string $resp, int $num_columns, int $proxy_arr_size, int $i ) : string {
	if ( $i === ($proxy_arr_size - 1) ) {
		$row = $ip . $port . $anon . $country . $city . $uptime . $resp . "│";
	} else{
		$row = $ip . $port . $anon . $country . $city . $uptime . $resp . "│" . "\n";
	}
	return $row;
}

# use to convert the port from hex to decimal format
function hex_port_to_dec( string $hex_str ) : int {
	$i   = -1;
	$int =  0;
	$j   = strlen( $hex_str ) - 1;
	while ( ($i++) < strlen( $hex_str ) - 1 ) {
		if ( ord( $hex_str[$i] ) >= 65 && ord( $hex_str[$i] ) <= 90 ) {
			$num = (( ord( $hex_str[$i] ) - ord( 'A' ) ) + 10) * (16 ** $j);
		}	
		else if ( ord( $hex_str[$i] ) >= 97 && ord( $hex_str[$i] ) <= 122 ) {
			$num = (( ord( $hex_str[$i] ) - ord( 'a' ) ) + 10) * (16 ** $j);
		} 
		else if ( ord( $hex_str[$i] ) >= 48 && ord( $hex_str[$i] ) <= 57 ) {
			$num = (int) ((int) $hex_str[$i] * (16 ** $j));
		} 
		$int += $num;
		$j--;
	}
	return $int;
}


?>
