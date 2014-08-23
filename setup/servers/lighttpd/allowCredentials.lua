if ( lighty.request["Origin"] == "http://local.nerdz.eu" or lighty.request["Origin"] == "http://mobile.local.nerdz.eu" ) then
	lighty.header["Access-Control-Allow-Credentials"] = true
end
