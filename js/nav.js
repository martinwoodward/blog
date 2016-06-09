<!--
//  <TITLE>Generic Navigation Bar JavaScript</TITLE>
//  Author:  Martin Woodward

function extractNames() {
	titleString = document.title;
	loc = document.location.toString();

	// work out server bit with protocol - ie http://localhost
	server = (loc.substring(0,loc.indexOf("/",8))).toLowerCase();
	
	// work out the path from the server root to page including /'s 
	// i.e. /test/mydir/
	path = (loc.substring(server.length,loc.lastIndexOf("/") + 1)).toLowerCase() ;
	
	// find the our root directory
	//root = (loc.substring(server.length,loc.indexOf("/") + 5)).toLowerCase() ;
	root = ""
	
	// work out page i.e. test.html
	if (loc.indexOf("#") > 0) {
		// we have an internal bookmrk - ignore
		page = (loc.substring((loc.lastIndexOf("/") + 1),loc.indexOf("#"))).toLowerCase();
	} else {
		page = (loc.substring(loc.lastIndexOf("/") + 1)).toLowerCase();
	}
	//alert ("page=" + page + "\nroot=" + root + "\npath=" + path + "\nserver=" + server + "\nloc=" + loc);

}

function writeItem(level, dir, name, href) {
	
	bgcolorOn = "#EEEEFF";                    bgcolorOff = "#EEEEEE"
	fgcolorOn = "#000000";                    fgcolorOff = "#666666"
	imgPointer = root + "/images/arrow.gif";  altPointer = "&lt;";
	imgSpacer = root + "/images/spacer.gif";    altSpacer = "";
	href = root + href;
	dir = root + dir;
	
	//alert ("dir = " + dir + "\nlevel = " + level + "\nroot = " + root);
	
	
	// Work out if the page we are viewing is in the section we are writing the
	// navigation bar for.
	if ((path.indexOf(dir) == 0) && (level > 0)  && (path != root) ) {
		bgcolor = bgcolorOn;
	} else {
		bgcolor = bgcolorOff;
	}
	
	//		alert ( "path=" + path + "\ndir=" + dir + "\nResult=" + path.indexOf(dir)  );
	alt = " ";
	// Now look to see if the page we are on is the one we are doing the mavigation for
	if ((bgcolor == bgcolorOn) && name == document.title ) {
		img = imgPointer;
		alt = altPointer;
		fgcolor = fgcolorOn;
	} else {
		img = imgSpacer;
		alt = altSpacer;
		fgcolor = fgcolorOff;
	}
	
	if (level <= 1) {
		document.writeln("<TR><TD COLSPAN=\"2\" ALIGN=\"left\"><A HREF=\"" + href + "\" CLASS=\"menu_l1\"><FONT COLOR=\"" + fgcolor + "\" SIZE=\"-1\"><B>" + name + "</B></FONT></A>&nbsp;</TD>");
		document.writeln("<TD ALIGN=\"center\" CLASS=\"menu_l1\"><img src=\"" + img + "\" WIDTH=\"5\" HEIGHT=\"10\" BORDER=\"0\" ALT=\"" + alt + "\"></TD></TR>");
	} else if (bgcolor == bgcolorOn){
		document.writeln("<TR><TD ALIGN=\"left\">&nbsp;&nbsp;<A HREF=\"" + href + "\" CLASS=\"menu_l2\"><FONT COLOR=\"" + fgcolor +"\" SIZE=\"-2\">" + name + "</FONT></A></TD>");
		document.writeln("<TD ALIGN=\"center\" COLSPAN=\"2\"><IMG SRC=\"" + img + "\" WIDTH=\"5\" HEIGHT=\"10\" ALT=\"" + alt +"\"></TD></TR>");
	} 
	
}

function setup_Menu(pageTitle) {
	extractNames();
	bgcolor = "#FFFFFF";
	fgcolor = "#000000";
	
	//set up page
	//document.writeln("<HTML><HEAD><TITLE>"+pageTitle+"</TITLE></HEAD><BODY BGCOLOR=\"#FFFFFF\">");
	//begin navigation table
	document.writeln("<TABLE BORDER=\"0\" WIDTH=\"105\" CELLPADDING=\"2\" CELLSPACING=\"0\">");
	//write shim line
	document.writeln("<TR><TD><IMG SRC=\"/images/spacer.gif\" WIDTH=\"90\" HEIGHT=\"1\"></TD><TD><IMG SRC=\"/images/spacer.gif\" WIDTH=\"7\" HEIGHT=\"1\"></TD><TD><IMG SRC=\"/images/spacer.gif\" WIDTH=\"8\" HEIGHT=\"1\"></TD></TR>");

	// Write menu items
	writeItem(0,"/","Home","/index.html")
	writeItem(1,"/stuff/","Stuff","/stuff/index.html")
	writeItem(2,"/stuff/","Books","/stuff/books.html")
	writeItem(2,"/stuff/","Gadgets","/stuff/gadgets.html")
	writeItem(2,"/stuff/","Films","/stuff/films.html")
	writeItem(1,"/projects/","Projects","/projects/index.html")
	writeItem(2,"/projects/","Cybot","/projects/cybot/index.html")
	writeItem(2,"/projects/","HTML Edit","/projects/code/edit.html")
	writeItem(1,"/links/","Links","/links/index.html")
	writeItem(1,"/work/","Work","/work/index.html")
	writeItem(1,"/feedback/","Feedback","/feedback/index.html")
	// writeItem(2,"/library/","FAQ","/library/faq.htm#top")
		
	//close table
	document.writeln("</TABLE>");
	
}

function organiseMenu(navbarDiv)
{
	// hide menu items that are not in our section.
	var menuItems = navbarDiv.getElementsByTagName("LI");
	var parentItem = 0;
	var parentUrl = "/";
	var thisUrl = document.location.href;
	
	for (i=0;i<menuItems.length;i++)
	{
		if (menuItems[i].parentElement.className == "menu-level-2")
		{
		  // this item is a child
		  parentUrl = menuItems[parentItem].childNodes[0].href.substr(0,menuItems[parentItem].childNodes[0].href.lastIndexOf("/")) ;
		  if (thisUrl.indexOf(parentUrl) == 0)
		  {
			menuItems[i].style.display = "";
		  }
		  else
		  {
			menuItems[i].style.display = "none";
		  }
		}
		else
		{
		  parentItem = i;
		}		    
	}
	
}

function breadCrumbs(base, delStr, defp, cStyle, tStyle, dStyle, nl) {
    loc = window.location.toString();
    subs = loc.substr(loc.indexOf(base,7) + base.length ).split("/");

    if (subs.length > 1)
    {
      document.write("<a href=\"" + getLoc(subs.length - 1) + "index.html" + "\" class=\"" + cStyle + "\">home</a>  " + "<span class=\"" + dStyle + "\">" + delStr + "</span> ");
      a = (loc.indexOf(defp) == -1) ? 1 : 2;
  
      for (i = 0; i < (subs.length - a); i++) {
          // use the below line if you want breadcrum trail title case...
          // subs[i] = makeCaps(unescape(subs[i]));
          subs[i] = unescape(subs[i]);
          document.write("<a href=\"" + getLoc(subs.length - i - 2) + defp + "\" class=\"" + cStyle + "\">" + subs[i] + "</a>  " + "<span class=\"" + dStyle + "\">" + delStr + "</span> ");
      }
      if (nl == 1) {
          document.write("<br>");
      }
      document.write("<span class=\"" + tStyle + "\">" + document.title.toLowerCase() + "</span>");
    }
}

function makeCaps(a) {
    g = a.split(" ");
    for (l = 0; l < g.length; l++) {
        g[l] = g[l].toUpperCase().slice(0, 1) + g[l].slice(1);
    }
    return g.join(" ");
}
 
function getLoc(c) {
    var d = "";
    if (c > 0) {
        for (k = 0; k < c; k++) {
            d = d + "../";
        }
    }
    return d;
}




function MM_swapImgRestore() { //v3.0
  var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
}

function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}

function MM_findObj(n, d) { //v4.0
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && document.getElementById) x=document.getElementById(n); return x;
}

function MM_swapImage() { //v3.0
  var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
   if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}

function toggleSection(checkbox, section) {
  if (checkbox.checked)
    section.style.display = "";
  else
    section.style.display = "none";
}

function formhelp(url) {
	window.open(url,"tas1help","width=770,height=400,top=0,left=" + (screen.x-100) + ",toolbar=no,location=no,directories=no,status=no,scrollbars=yes,resizable=yes,copyhistory=no");
	return false;	
}

function getRadioValue(radiogroup) {
	for (var i = 0; i < radiogroup.length; i++) {
		if (radiogroup[i].checked) {
			return radiogroup[i].value;
		}
	}
   	return null;
}

function setVisibility(section, visible) {
  if (visible)
    section.style.display = "";
  else
    section.style.display = "none";
}

//-->
