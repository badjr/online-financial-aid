0<?php
// $host = '10.250.40.227'; //host
$host = 'localhost'; //host
$port = '9000'; //port
$null = NULL; //null var

// $servername = "localhost";
// $username = "root";
// $password = "123";

//connection to the database
// $dbhandle = mysql_connect($servername, $username, $password)
//   or die("Unable to connect to MySQL");

// echo "Connected to MySQL<br>";

// // Create connection
// $conn = new mysqli($servername, $username, $password);

// // Check connection
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }
// echo "Connected successfully";

//Create TCP/IP sream socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//reuseable port
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

//bind socket to specified host
socket_bind($socket, 0, $port);

//listen to port
socket_listen($socket);

//create & add listning socket to the list
$clients = array($socket);

//the queue
$queue = new SplQueue();

//start endless loop, so that our script doesn't stop
while (true) {
	//manage multipal connections
	$changed = $clients;
	//returns the socket resources in $changed array
	socket_select($changed, $null, $null, 0, 10);
	
	//check for new socket
	if (in_array($socket, $changed)) {
		$socket_new = socket_accept($socket); //accpet new socket
		// echo $socket_new;
		$clients[] = $socket_new; //add socket to client array
		
		$header = socket_read($socket_new, 1024); //read data sent by the socket
		perform_handshaking($header, $socket_new, $host, $port); //perform websocket handshake
		// echo $header;
		
		socket_getpeername($socket_new, $ip); //get ip address of connected socket
		$response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' connected'))); //prepare json data
		// echo "length = " . count($clients) . "\n";
		// foreach($clients as $key=>$value) {
 	// 	   echo 'index is '.$key.' and value is '.$value . "\n";
		// }
		send_message($response); //notify all users about new connection
		
		//make room for new socket
		$found_socket = array_search($socket, $changed);
		unset($changed[$found_socket]);
	}
	
	//loop through all connected sockets
	foreach ($changed as $changed_socket) {	
		
		//check for any incomming data
		while(socket_recv($changed_socket, $buf, 1024, 0) >= 1)
		{
			$received_text = unmask($buf); //unmask data
			$tst_msg = json_decode($received_text); //json decode

			if (isset($tst_msg->next) || isset($tst_msg->finish)) { //when next button is pressed (or finish for students)
				$socket_to_remove = $queue->dequeue();
				// $clients[array_search ( mixed $needle , array $haystack)];
				// echo "socket_to_remove = " . $socket_to_remove . "\n";
				// echo "count(clients) = " . count($clients) . "\n";
				unset($clients[array_search ($socket_to_remove, $clients)]);
				// echo "count(clients) after = " . count($clients) . "\n";
				socket_close($socket_to_remove);
				$response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' disconnected')));
				send_message($response);

				$response_text = mask(json_encode(array('type'=>'queuedecrease', 'name'=>$user_name, 'message'=>$user_message, 'color'=>$user_color, 'usertype'=>$user_type, 'queuelength'=>count($queue))));
				send_message($response_text); //send data
				$response_text = mask(json_encode(array('type'=>'queuecountforcounselor', 'name'=>$user_name, 'message'=>$user_message, 'color'=>$user_color, 'usertype'=>$user_type, 'queuelength'=>count($queue)-1)));
				send_message($response_text);
				continue;
			}

			// echo "count(tst_msg) = " . count($tst_msg);
			$user_name = $tst_msg->name; //sender name
			$user_message = $tst_msg->message; //message text
			$user_color = $tst_msg->color; //color

			$user_type = NULL;
			if (isset($tst_msg->usertype)) {
				$user_type = $tst_msg->usertype;
				if ($user_type == "student") { //when putting a student in the queue
					$queue->enqueue($changed_socket);
					echo "just put " . $user_name . " in the queue. " . $changed_socket . "\n";
					$response_text = mask(json_encode(array('type'=>'initialplace', 'name'=>$user_name, 'message'=>$user_message, 'color'=>$user_color, 'usertype'=>$user_type, 'queuelength'=>count($queue))));
					send_message($response_text); //send data
					$response_text = mask(json_encode(array('type'=>'queuecountforcounselor', 'name'=>$user_name, 'message'=>$user_message, 'color'=>$user_color, 'usertype'=>$user_type, 'queuelength'=>count($queue))));
					send_message($response_text);
				}
				else {
					$response_text = mask(json_encode(array('type'=>'queuecountforcounselor', 'name'=>$user_name, 'message'=>$user_message, 'color'=>$user_color, 'usertype'=>$user_type, 'queuelength'=>count($queue))));
					send_message($response_text);
				}
			}
			// if (property_exists($user_type, $tst_msg->$usertype)) {
			// 	$user_type = $tst_msg->usertype;
			// }

			// echo $user_type;
			
			//prepare data to be sent to client
			if (is_null($user_type)) {
				$response_text = mask(json_encode(array('type'=>'usermsg', 'name'=>$user_name, 'message'=>$user_message, 'color'=>$user_color)));
				send_message($response_text); //send data
			}
			break 2; //exist this loop
		}
		
		$buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
		if ($buf === false) { // check disconnected client
			// remove client for $clients array
			$found_socket = array_search($changed_socket, $clients);
			socket_getpeername($changed_socket, $ip);
			unset($clients[$found_socket]);
			
			//notify all users about disconnected connection
			$response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' disconnected')));
			send_message($response);
		}
	}
}
// close the listening socket
socket_close($sock);

function send_message($msg)
{
	global $clients;
	foreach($clients as $changed_socket)
	{
		@socket_write($changed_socket,$msg,strlen($msg));
	}
	return true;
}


//Unmask incoming framed message
function unmask($text) {
	$length = ord($text[1]) & 127;
	if($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8);
	}
	elseif($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14);
	}
	else {
		$masks = substr($text, 2, 4);
		$data = substr($text, 6);
	}
	$text = "";
	for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i%4];
	}
	return $text;
}

//Encode message for transfer to client.
function mask($text)
{
	$b1 = 0x80 | (0x1 & 0x0f);
	$length = strlen($text);
	
	if($length <= 125)
		$header = pack('CC', $b1, $length);
	elseif($length > 125 && $length < 65536)
		$header = pack('CCn', $b1, 126, $length);
	elseif($length >= 65536)
		$header = pack('CCNN', $b1, 127, $length);
	return $header.$text;
}

//handshake new client.
function perform_handshaking($receved_header,$client_conn, $host, $port)
{
	$headers = array();
	$lines = preg_split("/\r\n/", $receved_header);
	foreach($lines as $line)
	{
		$line = chop($line);
		if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
		{
			$headers[$matches[1]] = $matches[2];
		}
	}

	$secKey = $headers['Sec-WebSocket-Key'];
	$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	//hand shaking header
	$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
	"Upgrade: websocket\r\n" .
	"Connection: Upgrade\r\n" .
	"WebSocket-Origin: $host\r\n" .
	"WebSocket-Location: ws://$host:$port/demo/shout.php\r\n".
	"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
	socket_write($client_conn,$upgrade,strlen($upgrade));
}
