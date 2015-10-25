var app = require('express')();
var http = require('http').Server(app);

// var path = require('../../client/');
// var indexFile = require('../../client/index.html');
var path = require('path');
app.get('/', function(req, res){
	// res.sendFile(__dirname + '/index.html');
	// res.sendFile(__dirname + '../../client/index.html');
	// res.sendFile(indexFile);
	// res.sendFile(path.resolve('../../client/css/bootstrap.min.css'));
	res.sendFile(path.resolve('../../client/index.html'));
});

http.listen(3000, function() {
	console.log('listening on *:3000');
});