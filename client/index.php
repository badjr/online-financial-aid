<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>PantherChat</title>

    <!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link type="text/css" rel="stylesheet" href="css/style.css" />

    <!-- Custom CSS -->
    <style>
    body {
        padding-top: 70px;
        /* Required padding for .navbar-fixed-top. Remove if using .navbar-static-top. Change if height of navigation changes. */
    }
    </style>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>
<style type="text/css">
<!--
.chat_wrapper {
    width: 500px;
    margin-right: auto;
    margin-left: auto;
    background: #CCCCCC;
    border: 1px solid #999999;
    padding: 10px;
    font: 12px 'lucida grande',tahoma,verdana,arial,sans-serif;
}
.chat_wrapper .message_box {
    background: #FFFFFF;
    height: 150px;
    overflow: auto;
    padding: 10px;
    border: 1px solid #999999;
}
.chat_wrapper .panel input{
    padding: 2px 2px 2px 5px;
}
.system_msg{color: #BDBDBD;font-style: italic;}
.user_name{font-weight:bold;}
.user_message{color: #88B6E0;}
-->
</style>
<?php 
    $colours = array('007AFF','FF7000','FF7000','15E25F','CFC700','CFC700','CF1100','CF00BE','F00');
    $user_colour = array_rand($colours);

    // echo $_POST["campusid"];

    $servername = "localhost";
    $username = "root";
    $password = "123";

    //connection to the database
    // $dbhandle = mysql_connect($servername, $username, $password)
    //     or die("Unable to connect to MySQL");


    // $query = "select * from counselors";
    // $result = mysql_query($query);

    // if (!$result) {
    //     $message  = 'Invalid query: ' . mysql_error() . "\n";
    //     $message .= 'Whole query: ' . $query;
    //     die($message);
    // }

    // while ($row = mysql_fetch_assoc($result)) {
    //     echo $row['campusid'];
    //     echo $row['name'];
    // }

    // echo $query;

    // if ($_POST["campusid"] == "jcounselor" && $_POST["password"] == "123") {

    // }

    function generateChatBox() {
        echo "<div class=\"chat_wrapper\">
        <div class=\"message_box\" id=\"message_box\"></div>
        <div class=\"panel\" id=\"chatpanel\">
        <input type=\"text\" name=\"message\" id=\"message\" placeholder=\"Message\" maxlength=\"80\" style=\"width:60%\" />
        <button id=\"send-btn\">Send</button>
        </div>
        </div>";
    }

?>

<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>

<script language="javascript" type="text/javascript">


$(document).ready(function() {
    //create a new WebSocket object.
    // var wsUri = "ws://localhost:9000/demo/server.php";
    var wsUri = "ws://10.250.40.227:9000/demo/server.php";
    websocket = new WebSocket(wsUri);
    var placeInLine = null;
    
    websocket.onopen = function(ev) { // connection is open 
        //$('#message_box').append("<div class=\"system_msg\">Connected!</div>"); //notify user
        //prepare json data
        var msg = {
        message: "type",
        name: document.getElementById('namediv').innerHTML,
        usertype: document.getElementById('usertype').innerHTML,
        color : '<?php echo $colours[$user_colour]; ?>'
        };
        //convert and send data to server
        websocket.send(JSON.stringify(msg));
        // console.log(document.getElementById('usertype').innerHTML);
    }

    $('#send-btn').click(function() { //use clicks message send button
        if (placeInLine == 0 || document.getElementById('usertype').innerHTML == 'counselor') { //only those whose turn it is.
            var mymessage = $('#message').val(); //get message text
            // var myname = $('#name').val(); //get user name
            // var myname = '<?php echo $name; ?>';
            var myname = document.getElementById('namediv').innerHTML;
            
            // if(myname == "") { //empty name?
            //     console.log("Enter your Name please! name = " + myname);
            //     return;
            // }
            if(mymessage == ""){ //emtpy message?
                alert("Enter Some message Please!");
                return;
            }
            
            //prepare json data
            var msg = {
            message: mymessage,
            name: myname,
            color : '<?php echo $colours[$user_colour]; ?>'
            };
            //convert and send data to server
            websocket.send(JSON.stringify(msg));
        }
        else {
            $('#message_box').append("<div class=\"system_msg\">You are not connected with a counselor.</div>");
        }
    });

    $('#next-btn').click(function() { 
        //prepare json data
        var msg = {
            next: "next"
        };
        //convert and send data to server
        websocket.send(JSON.stringify(msg));
    });

    $('#finish-btn').click(function() {
        if (placeInLine == 0 && document.getElementById('usertype').innerHTML == 'student') {
            placeInLine--;
            $('#message_box').append("<div class=\"system_msg\">Connection Closed</div>"); //notify user
            //prepare json data
            var msg = {
                finish: "finish"
            };
            //convert and send data to server
            websocket.send(JSON.stringify(msg));
        }
    });
    
    //#### Message received from server?
    websocket.onmessage = function(ev) {
        var msg = JSON.parse(ev.data); //PHP sends Json data
        var type = msg.type; //message type
        var umsg = msg.message; //message text
        var uname = msg.name; //user name
        var ucolor = msg.color; //color

        if (placeInLine == 0 || document.getElementById('usertype').innerHTML == 'counselor') { //Prevents users in line from seeing conversation.
            if(type == 'usermsg') {
                $('#message_box').append("<div><span class=\"user_name\" style=\"color:#"+ucolor+"\">"+uname+"</span> : <span class=\"user_message\">"+umsg+"</span></div>");
            }
            if(type == 'system') {
                $('#message_box').append("<div class=\"system_msg\">"+umsg+"</div>");
            }
        }
        if (type == 'initialplace' && document.getElementById('usertype').innerHTML == 'student') {
            if (placeInLine == null) { //when they first log in
                placeInLine = msg.queuelength - 1;
                document.getElementById('placeinline').innerHTML = "Your place in line is " + (placeInLine);
                if (placeInLine > 0) {
                    // console.log("their message box should be disabled.");
                    // document.getElementById("message_box").disabled = true;
                    // $(".chat_wrapper").hide();
                    $('#message_box').append("<div class=\"system_msg\">A financial aid counselor will be available to chat. Please wait...</div>");
                }
                else if (placeInLine == 0) {
                    $('#message_box').append("<div class=\"system_msg\">Connected!</div>");
                }
            }
        }
        if (type == 'queuedecrease' && document.getElementById('usertype').innerHTML == 'student') {
            placeInLine--;
            document.getElementById('placeinline').innerHTML = "Your place in line is " + (placeInLine);
            if (placeInLine == 0) {
                // document.getElementById('chat_wrapper').innerHTML = "";
                // $(".message_box").empty();
                // $(".chat_wrapper").show();
                $('#message_box').append("<div class=\"system_msg\">Connected!</div>"); //notify user
            }

        }
        if (type == 'queuecountforcounselor' && document.getElementById('usertype').innerHTML == 'counselor') {
            var studentsInLine = msg.queuelength;
            if (studentsInLine == 0 || studentsInLine == 1) {
                document.getElementById('studentsinline').innerHTML = "There are " + 0 + " students in line.";
            }
            else {
                document.getElementById('studentsinline').innerHTML = "There are " + (studentsInLine) + " students in line.";
            }
        }
        
        $('#message').val(''); //reset text
    };
    
    websocket.onerror   = function(ev){$('#message_box').append("<div class=\"system_error\">Error Occurred - "+ev.data+"</div>");}; 
    websocket.onclose   = function(ev){$('#message_box').append("<div class=\"system_msg\">Connection Closed</div>");};
});

</script>

<body>
    <div id="namediv" style="display: none"></div>
    <div id="usertype" style="display: none"></div>
    <!-- Navigation -->
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="login.php">Home</a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    <li>
                        <a href="about.html">About</a>
                    </li>
                    
                </ul>
            </div>
            <!-- /.navbar-collapse -->
        </div>
        <!-- /.container -->
    </nav>

    <!-- Page Content -->
    <div class="container">

        <div class="row">
            <div class="col-lg-12 text-center">
                    <?php
                        $name = "";
                        if ($_POST["campusid"] == "jcounselor" && $_POST["password"] == "123") {
                            $name = "Joe Counselor";
                            echo "<h1>Welcome, $name</h1>";
                            // echo "<script> document.getElementById('namediv').innerHTML = $name </script>;"
                            echo "<h2 id=\"studentsinline\">There are 0 students in line.</h2>";
                            echo "<script>document.getElementById('namediv').innerHTML = \"$name\";</script>";
                            echo "<script>document.getElementById('usertype').innerHTML = \"counselor\";</script>";
                            generateChatBox();
                            echo "<script>var btn = document.createElement('button'); btn.id=\"next-btn\"; btn.innerHTML=\"Next Student\"; document.getElementById('chatpanel').appendChild(btn)</script>";
                            
                        }
                        else if ($_POST["campusid"] == "jstudent" && $_POST["password"] == "123") {
                            $name = "John Student";
                            echo "<h1>Welcome, $name</h1>";
                            echo "<h2 id=\"placeinline\">Your place in line is 0</h2>";
                            echo "<script>document.getElementById('namediv').innerHTML = \"$name\";</script>";
                            echo "<script>document.getElementById('usertype').innerHTML = \"student\";</script>";
                            generateChatBox();
                            echo "<script>var btn = document.createElement('button'); btn.id=\"finish-btn\"; btn.innerHTML=\"Finish\"; document.getElementById('chatpanel').appendChild(btn)</script>";
                        }
                        else if (empty($_POST["campusid"]) || empty($_POST["password"])) {
                            echo "You must enter both your campusid and password.";
                            die();
                        }
                        else {
                            $name = $_POST["campusid"];
                            echo "<h1>Welcome, $name</h1>";
                            echo "<h2 id=\"placeinline\">Your place in line is 0</h2>";
                            echo "<script>document.getElementById('namediv').innerHTML = \"$name\";</script>";
                            echo "<script>document.getElementById('usertype').innerHTML = \"student\";</script>";
                            generateChatBox();
                            echo "<script>var btn = document.createElement('button'); btn.id=\"finish-btn\"; btn.innerHTML=\"Finish\"; document.getElementById('chatpanel').appendChild(btn)</script>";
                            // echo "Username or password not found.";
                            // die();
                        }
                    ?>
                
                <p class="lead">Thanks for choosing PantherChat!</p>
                
            </div>
        </div>
        <!-- /.row -->

    </div>
    <!-- /.container -->

    <!-- Chat Box -->
    <!-- <div class="chat_wrapper">
        <div class="message_box" id="message_box"></div>
            <div class="panel" id="chatpanel">
                <input type="text" name="message" id="message" placeholder="Message" maxlength="80" style="width:60%" />
                <button id="send-btn">Send</button>
                <button id="send-btn" onClick="send()">Send</button>
            </div>
    </div> -->
    <!-- End chat box -->

</body>

</html>

