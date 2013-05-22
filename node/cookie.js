//Directly send cookie to system, if it's node.js handler, send :
//request.headers.cookie
//If it's socket.io cookie, send :
//client.request.headers.cookie
module.exports.cookie = function(co){
    this.cookies = {};
    co && co.split(';').forEach(function(cookie){
        var parts = cookie.split('=');
        this.cookies[parts[0].trim()] = (parts[1] || '').trim();
    }.bind(this));
 
    //Retrieve all cookies available
    this.list = function(){
        return this.cookies;
    };
 
    //Retrieve a key/value pair
    this.get = function(key){
        if(this.cookies[key]){
            return this.cookies[key];
        }else{
            return {};
        }
    };
 
    //Retrieve a list of key/value pair
    this.getList = function(map){
        var cookieRet = {};
        for(var i=0; i<map.length; i++){
            if(this.cookies[map[i]]){
                cookieRet[map[i]] = this.cookies[map[i]];
            }
        }
        return cookieRet;
    };
};
