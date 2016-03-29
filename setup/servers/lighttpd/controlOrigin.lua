if ( lighty.request["Origin"] == "http://local.nerdz.eu" or lighty.request["Origin"] == "http://mobile.local.nerdz.eu" ) then
	lighty.header["Access-Control-Allow-Origin"] = lighty.request["Origin"]

    if ( lighty.env["request.uri"] == "/pages/profile/login.json.php" ) then
        lighty.header["Access-Control-Allow-Credentials"] = "true"
    end

end
