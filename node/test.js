var app = require("http").createServer(handler),
    fs = require("fs"),
	 redis = require("redis"),
    co = require("./cookie.js");
 
app.listen(7070);
 
//On client incomming, we send back index.html
function handler(req, res){
    fs.readFile(__dirname + "/index.html", function(err, data){
        if(err){
            res.writeHead(500);
            return res.end("Error loading index.html");
        }else{
            res.writeHead(200);
            res.end(data);
        }
    });
	 
	 var cookieManager = new co.cookie(req.headers.cookie);

     //Note : to specify host and port : new redis.createClient(HOST, PORT, options)
    //For default version, you don't need to specify host and port, it will use default one
    var clientSession = new redis.createClient();
 
    clientSession.get("sessions/"+cookieManager.get("SEXYID"), function(error, result){
        if(error){
            console.log("error : "+error);
        }
        if(result.toString() != ""){
            console.log("result exist");
            console.log(result.toString());
        }else{
            console.log("session does not exist");
        }
    });
}
