var allowSubmit;
var counts = new Array();

function checkSubmit()
{
    return allowSubmit;
}

function addField(id, keyname, count, maxCount)
{
    if(counts[keyname] != undefined)
        count = counts[keyname];

    if(maxCount <= 0 || count < maxCount)
    {
        //var item = document.createElement('li');
        var template = document.getElementById('field-template-'+keyname);
        var list = document.getElementById('field-list-'+keyname);

        var item = template.cloneNode(true);
        item.id = 'field-item-' + keyname + '-' + count;
        item.style.display = 'block';

        var input = item.getElementsByTagName('INPUT')[0];
        if(input == null)
            input = item.getElementsByTagName('SELECT')[0];
        if(input != null)
        {
            input.id = keyname + '-' + count;
            input.name = keyname + '[]';

            list.appendChild(item);

            input.focus();

            //item.innerHTML = 'blah';

            counts[keyname] = count + 1;

            if(maxCount > 0 && counts[keyname] >= maxCount)
            {
                var addLink = document.getElementById('add-'+keyname);
                addLink.style.display = 'none';
            }
        }

        return item;
    }

    return null;
}

function addSmartTextField(id, keyname, count, maxCount)
{
    var item = addField(id, keyname, count, maxCount);

    if(item != null)
    {
        count = counts[keyname] - 1;

        var input = item.getElementsByTagName('INPUT')[0];
        var container = item.getElementsByTagName('DIV')[0];
        container.id = 'value-div-' + keyname + '-' + count;

        var search = createSearch(input.id, container.id, 'xml/smart-text-search.php');
        search.others = 'fieldId=' + id;

        input.blur();
        input.focus();
    }
}


function setValidator(fieldId, validator)
{
    var input = document.getElementById(fieldId);
    input.onkeyup = validator;
    input.validator = validator;
    input.validator(null);
}



var VALIDATE_BLANK = 0;
var VALIDATE_INVALID = 1;
var VALIDATE_BAD_CHECK = 2;
var VALIDATE_VALID = 3;

function validateField(input, validator)
{
    var code = validator(input.value);

    if(code == VALIDATE_BLANK)
        input.style.backgroundColor = '#ffffff';
    else if(code == VALIDATE_BAD_CHECK)
        input.style.backgroundColor = '#ffffcc';
    else if(code == VALIDATE_VALID)
        input.style.backgroundColor = '#ccffcc';
    else
        input.style.backgroundColor = '#ffcccc';
}


function removeBarcodeTrash(value)
{
    return value.toString().replace(/[ \-\.]/g, '');
}

function validateUPCField(e)
{
    return validateField(this, validateUPC);
}

function validateUPC(upc)
{
    upc = removeBarcodeTrash(upc);

    if(upc == '')
        return VALIDATE_BLANK;
    else
    {
        if(upc.length == 12)
        {
            var result = 0;
            var x;
            for(var i = 0; i < 11; i++)
            {
                if((i + 1) % 2 == 1)
                    x = upc.charAt(i) * 3;
                else
                    x = upc.charAt(i) * 1;

                if(isNaN(x))
                    return VALIDATE_INVALID;

                result += x;
            }
            result = result % 10;
            if(result != 0)
                result = 10 - result;

            check = upc.charAt(11) * 1;
            if(isNaN(check))
                return VALIDATE_INVALID;

            if(check == result)
                return VALIDATE_VALID;
            else
                return VALIDATE_BAD_CHECK;
        }
        else
            return VALIDATE_INVALID;
    }
}

function validateISBNField(e)
{
    return validateField(this, validateISBN);
}

function validateISBN(upc)
{
    upc = removeBarcodeTrash(upc);

    if(upc == '')
        return VALIDATE_BLANK;
    else
    {
        if(upc.length == 10)
        {
            var result = 0;
            var x;
            for(var i = 0; i < 9; i++)
            {
                x = upc.charAt(i) * (10 - i);

                if(isNaN(x))
                    return VALIDATE_INVALID;

                result += x;
            }
            result = 11 - (result % 11);
            if(result == 11)
                result = 0;

            check = upc.charAt(9);
            if(check == 'x' || check == 'X')
                check = 10;
            check = check * 1;
            if(isNaN(check))
                return VALIDATE_INVALID;

            if(check == result)
                return VALIDATE_VALID;
            else
                return VALIDATE_BAD_CHECK;
        }
        else if(upc.length == 13)
        {
            var result = 0;
            var x;
            for(var i = 0; i < 12; i++)
            {
                if((i + 1) % 2 == 1)
                    x = upc.charAt(i) * 1;
                else
                    x = upc.charAt(i) * 3;

                if(isNaN(x))
                    return VALIDATE_INVALID;

                result += x;
            }
            result = (10 - (result % 10)) % 10;

            check = upc.charAt(12) * 1;
            if(isNaN(check))
                return VALIDATE_INVALID;

            if(check == result)
                return VALIDATE_VALID;
            else
                return VALIDATE_BAD_CHECK;
        }
        else
            return VALIDATE_INVALID;
    }
}



function checkLocationValue()
{
    if(this.value == -1)
    {
        allowSubmit = false;
        var foundNum = removeLocationFields(this);

        this.lastValue = 0;
        this.value = '0';

        var locations = this.parentNode;
        var locationFields = locations.getElementsByTagName('select');

        var newParentLocationId = document.getElementById('new-location-parent-id');
        var newParentLocation = document.getElementById('new-location-parent');

        if(foundNum == 0)
        {
            newParentLocationId.value = 0;
            newParentLocation.value = "";
        }
        else
        {
            newParentLocationId.value = locationFields[foundNum - 1].value;
            var opt = locationFields[foundNum - 1].firstChild;
            while(opt != null)
            {
                if(opt.value == newParentLocationId.value)
                {
                    newParentLocation.innerHTML = opt.firstChild.nodeValue;
                    opt = null;
                }
                else
                    opt = opt.nextSibling;
            }
        }

        var newLocation = document.getElementById('new-location');
        newLocation.style.display = 'block';

        window.onresize = resizeNewLocationBg;
        resizeNewLocationBg();

        var newLocationCode = document.getElementById('new-location-code');
        var newLocationDesc = document.getElementById('new-location-description');
        newLocationCode.value = '';
        newLocationDesc.value = '';
        newLocationCode.focus();
    }
    else
    {
        allowSubmit = true;

        if(!this.lastValue || this.lastValue != this.value)
        {
            var foundNum = removeLocationFields(this);
            this.lastValue = this.value;

            if(this.value != 0)
            {
                loadLocationField(this.value, foundNum);
            }
        }
    }
}

function resizeNewLocationBg()
{
    var height = "100%";
    var windowHeight = 0;
    if(window.innerHeight)
        windowHeight = window.innerHeight;
    else if(document.body.clientHeight)
        windowHeight = document.body.clientHeight;
    else if(document.documentElement.clientHeight)
        windowHeight = document.documentElement.clientHeight;

    var containerHeight = 0;
    var outerContainer = document.getElementById('outer-container');
    if(outerContainer.clientHeight)
        containerHeight = (outerContainer.clientHeight + 30);

    if(windowHeight > 0 && windowHeight > containerHeight)
        height = windowHeight + "px";
    else if(containerHeight > 0 && containerHeight > windowHeight)
        height = containerHeight + "px";

    var newLocationBg = document.getElementById('new-location-background');
    newLocationBg.style.height = height;
}

function loadLocationField(parentId, selectNum, func, funcArgs)
{
    var data = '';
    data += 'parent=' + encode(parentId);

    var args = new Array();
    args[0] = selectNum;
    args[1] = func ? func : null;
    args[2] = funcArgs ? funcArgs : null;
    sendRequest(args, 'xml/location.php', createLocationList, data);
}

function removeLocationFields(current)
{
    var locations = document.getElementById('locations');
    var locationFields = locations.getElementsByTagName('select');

    var found = false;
    var foundNum = -1;
    var i;
    for(i = 0; !found; i++)
    {
        if(current == locationFields[i])
        {
            found = true;
            foundNum = i;
        }
    }

    while(found && locationFields[foundNum + 1] != null)
    {
        locations.removeChild(locationFields[foundNum + 1]);
    }

    return foundNum;
}

function createLocationList(args, req)
{
    var id = args[0];
    var func = args[1];
    var locations = document.getElementById('locations');
    var locationFields = locations.getElementsByTagName('select');

    removeLocationFields(locationFields[id]);

    var select = document.createElement('select');
    select.name = 'location[]';
    select.onchange = checkLocationValue;
    select.onkeyup = checkLocationValue;

    var option = document.createElement('option');
    option.value = '0';
    option.innerHTML = '';
    select.appendChild(option);

    option = document.createElement('option');
    option.value = '-1';
    option.innerHTML = 'New Location';
    select.appendChild(option);

    var reqResults = req.responseXML;
    var results = reqResults.getElementsByTagName('results');
    var locs = reqResults.getElementsByTagName('location');

    select.id = 'location-' + results[0].getAttribute('parent-id');

    for(var i = 0; i < locs.length; i++)
    {
        var code = '';
        if(locs[i].getElementsByTagName('code')[0].childNodes.length > 0)
            code = locs[i].getElementsByTagName('code')[0].childNodes[0].nodeValue;
        var description = locs[i].getElementsByTagName('description')[0].childNodes[0].nodeValue;

        option = document.createElement('option');
        option.value = locs[i].getElementsByTagName('id')[0].childNodes[0].nodeValue;

        if(trim(code.toString()) != '')
            option.innerHTML = code + ' - ' + description;
        else
            option.innerHTML = description;

        select.appendChild(option);
    }

    locations.appendChild(select);

    if(func != null)
    {
        func(select.id, args[2]);
    }
}

function createNewLocation()
{
    var parent = document.getElementById('new-location-parent-id');
    var code = document.getElementById('new-location-code');
    var description = document.getElementById('new-location-description');
    var create = document.getElementById('new-location-create');
    var cancel = document.getElementById('new-location-cancel');

    if(description.value != '')
    {
        var data = '';
        data += 'parent=' + encode(parent.value) + '&code=' + encode(code.value)
            + '&description=' + encode(description.value);

        create.disabled = true;
        cancel.disabled = true;

        //finalizeNewLocation(0, null);
        //return false;
        sendRequest(0, 'xml/new-location.php', finalizeNewLocation, data);
    }
    else
    {
        alert('A description is required.');
    }

    return false;
}

function selectLocation(selectId, locationId)
{
    var select = document.getElementById(selectId);
    if(select != null)
    {
        select.value = locationId;
        select.onchange();
        select.focus();
    }
}

function finalizeNewLocation(id, req)
{
    var locations = document.getElementById('locations');
    var locationFields = locations.getElementsByTagName('select');

    var parent = document.getElementById('new-location-parent-id');
    var code = document.getElementById('new-location-code');
    var description = document.getElementById('new-location-description');
    var create = document.getElementById('new-location-create');
    var cancel = document.getElementById('new-location-cancel');

    var reqResults = req.responseXML;
    var results = reqResults.getElementsByTagName('new-location');

    var newId = 0;
    if(results[0].getElementsByTagName('id').length > 0
        && results[0].getElementsByTagName('id')[0].childNodes.length > 0)
    {
        newId = results[0].getElementsByTagName('id')[0].childNodes[0].nodeValue;
    }

    if(locationFields.length > 1)
    {
        var penultimate = locationFields[locationFields.length-2];
        loadLocationField(penultimate.value, locationFields.length-2, selectLocation, newId);
    }
    else if(locationFields.length > 0)
    {
        locations.removeChild(locationFields[0]);
        loadLocationField(0, 0, selectLocation, newId);
    }

    var newLocation = document.getElementById('new-location');
    newLocation.style.display = 'none';

    create.disabled = false;
    cancel.disabled = false;


    /*removeLocationFields(locationFields[foundNum]);

    var select = document.createElement('select');
    select.name = 'location[]';
    select.id = 'location-' + id;
    select.onchange = checkLocationValue;
    select.onkeyup = checkLocationValue;

    var option = document.createElement('option');
    option.value = '0';
    option.innerHTML = '';
    select.appendChild(option);

    option = document.createElement('option');
    option.value = '-1';
    option.innerHTML = 'New Location';
    select.appendChild(option);

    var reqResults = req.responseXML;
    var results = reqResults.getElementsByTagName('location');

    for(var i = 0; i < results.length; i++)
    {
        var code = results[i].getElementsByTagName('code')[0].childNodes[0].nodeValue;
        var description = results[i].getElementsByTagName('description')[0].childNodes[0].nodeValue;

        option = document.createElement('option');
        option.value = results[i].getElementsByTagName('id')[0].childNodes[0].nodeValue;

        if(trim(code.toString()) != '')
            option.innerHTML = code + ' - ' + description;
        else
            option.innerHTML = description;

        select.appendChild(option);
    }

    locations.appendChild(select);*/
}

function cancelNewLocation()
{
    var newLocation = document.getElementById('new-location');
    newLocation.style.display = 'none';

    allowSubmit = true;

    return false;
}


function activateLocations()
{
    var locations = document.getElementById('locations');
    var locationFields = locations.getElementsByTagName('select');

    for(var i = 0; i < locationFields.length; i++)
    {
        locationFields[i].onchange = checkLocationValue;
        locationFields[i].onkeyup = checkLocationValue;

        if(locationFields[i].value > 0)
            locationFields[i].lastValue = locationFields[i].value;
        locationFields[i].onchange();
    }
}



function focusForm(id)
{
    var form = document.getElementById(id);

    for(var i = 0; i < form.elements.length; i++)
    {
        var element = form.elements[i];

        if(element.nodeName == 'INPUT' || element.nodeName == 'SELECT')
        {
            var type = element.getAttribute('type');
            if(type == null || type.toLowerCase() != 'hidden')
            {
                element.focus();
                break;
            }
        }
    }
}

