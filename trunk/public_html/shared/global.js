function setDisplay(id, value)
{
    var div = document.getElementById(id);
    if(div != null)
    {
        div.style.display = value;
        return true;
    }

    return false;
}

function setToLocalTime(id)
{
    var field = document.getElementById(id);
    var date = new Date();
    field.value = parseInt(date.getTime()/1000);
}


function setNavDisplay(num, value, depth)
{
    var li = document.getElementById('nav-li-' + num);
    var link = document.getElementById('nav-link-' + num);
    if(li != null && link != null)
    {
        if(depth > 0)
        {
            li.style.backgroundColor = value ? '#ffff99' : 'transparent';
            //link.style.color = value ? '#003366' : null;
        }
        else
        {
            link.style.color = value ? '#ffff99' : '#ffffff';
        }
    }

    var div = document.getElementById('sub-nav-' + num);
    if(div != null)
    {
        div.style.display = value ? 'block' : 'none';
    }
}





function sendRequest(id, url, callback, postData)
{
    var req = getXmlHttpObject();
    if(!req)
        return;

    var method = (postData) ? "POST" : "GET";
    req.open(method,url,true);
    req.setRequestHeader('User-Agent','XMLHTTP/1.0');

    if(postData)
        req.setRequestHeader('Content-type','application/x-www-form-urlencoded');

    req.onreadystatechange = function()
    {
        if (req.readyState != 4)
            return;
        if (req.status != 200 && req.status != 304)
            return;

        callback(id, req);
    }

    if(req.readyState == 4)
        return;

    req.send(postData);
}

var XmlHttpFactories =
[
    function() {return new XMLHttpRequest()},
    function() {return new ActiveXObject("Msxml2.XMLHTTP")},
    function() {return new ActiveXObject("Msxml3.XMLHTTP")},
    function() {return new ActiveXObject("Microsoft.XMLHTTP")}
];

function getXmlHttpObject()
{
    var xmlHttp = null;

    for(var i = 0; i < XmlHttpFactories.length; i++)
    {
        try
        {
            xmlHttp = XmlHttpFactories[i]();
        }
        catch(e)
        {
            continue;
        }

        break;
    }

    return xmlHttp;
}

// url encode the string
function encode (str)
{
    str += "";
    return escape(str).replace(/ /g, "%20").replace(/\+/g, "%2b");
}

// url decode the string
function decode (str)
{
    return unescape(str);
}

function trim (str)
{
    return str.replace(/^\s+|\s+$/g, "");
}
