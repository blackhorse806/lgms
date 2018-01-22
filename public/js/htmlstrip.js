function htmlstrip(s) {
	if (s == null) {
		return "";
	}

	var tags = s.split("<");
	var output = "";
	var del = 0;
	var inTable = 0;
	var inLatex = false;

	for (var i = 0; i < tags.length; i++) {
		var t = tags[i];
		var cls = t.search(">");
		var space = t.search(" ");
		var pos = 0;

		// Find the end of the tag name
		if (cls > space && space != -1) {
			pos = space;
		} else {
			pos = cls;
		}

		// get the tag name
		var tagname = t.substring(0,pos);
		// get the data outside tag
		var data = t.substring(cls + 1,t.length);

		// Check for end latex tag
		if (tagname == "/latex") {
			inLatex = false;
		}

		// Check if inside latex tag
		if (!inLatex) {
			// process tags
			switch (tagname) {
				case "style":
				case "!--[if":
					del++;
					break;
				case "/style":
				case "![endif]--":
					del--;
					break;
				case "table":
					tagname += " border='1'";
					inTable += 2;
				case "/table":
					inTable--;
				case "b":
				case "/b":
				case "i":
				case "/i":
				case "u":
				case "/u":
				case "tr":
				case "/tr":
				case "td":
				case "/td":
				case "ol":
				case "/ol":
				case "li":
				case "/li":
				case "ul":
				case "/ul":
				case "em":
				case "/em":
				case "br":
				case "/br":
				case "strong":
				case "/strong":	
				case "div":
				case "/div":
					if (del < 1) {
						output += "<" + tagname + ">" + data;	
					}
					break;
				case "p":
				case "/p":
					if (inTable < 1 && del < 1) {
						output += "<" + tagname + ">" + data;
					} else {
						output += data.trim();
					}
					break;
				case "latex":
					inLatex = true;
				case "/latex":
					output += "<" + tagname + ">" + data;
					break
				default:
					if (del < 1) {
						if ((data != "\n") && (data != "")) {
							output += data;	
						}
					}		
			}			
		} else {
			output += "<" + t;
		}

	}
	return output;
}