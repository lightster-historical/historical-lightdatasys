var searchInstancesById = new Array();

function attachIdToSearch(id, searchObj)
{
    searchInstancesById[id] = searchObj;
}

function getSearchById(id)
{
    return searchInstancesById[id];
}

function getSearchByElement(elem)
{
    var id = elem.id;
    return searchInstancesById[id];
}


function createSearch(searchField, suggestContainer, resultAddr, others)
{
    var searchObj = new Object();

    searchObj.searchField = searchField;
    searchObj.suggestContainer = suggestContainer;

    attachIdToSearch(searchField, searchObj);
    attachIdToSearch(suggestContainer, searchObj);

    searchObj.inputHasFocus = false;

    var fieldObj = document.getElementById(searchField);
    fieldObj.onfocus = focusSearchField;
    fieldObj.onblur = blurSearchField;
    fieldObj.onkeydown = navigateSearch;
    fieldObj.onkeyup = doSearch;

    searchObj.resultAddr = resultAddr;

    searchObj.suggestUL = null;

    searchObj.highlightPrevSearchSuggestion = highlightPrevSearchSuggestion;
    searchObj.highlightNextSearchSuggestion = highlightNextSearchSuggestion;
    searchObj.highlightSearchItem = highlightSearchItem;
    searchObj.selectSearchItem = selectSearchItem;
    searchObj.setSearchResultsVisible = setSearchResultsVisible;
    searchObj.sendSearchRequest = sendSearchRequest;

    searchObj.postMethod = true;
    searchObj.maxCountName = 'maxCount';
    searchObj.valueName = 'value';

    searchObj.maxCount = 5;
    if(others != undefined)
        searchObj.others = others;
    else
        searchObj.others = '';

    searchObj.isMouseOverSuggestion = false;
    searchObj.selectedSuggestion = null;

    searchObj.lastSearchStr = '';

    searchObj.searchTimer = null;
    searchObj.isSearching = false;

    return searchObj;
}



//37=left, 38=up, 39=right, 40=down
//13=enter, 32=space
function navigateSearch(e)
{
    var searchObj = getSearchByElement(this);

    var key;

    if(!e)
        e = window.event;

    if(e.keyCode)
        key = e.keyCode;
    else if(e.which)
        key = e.which;

    /*
    if(window.event)
    key = event.keyCode;
    else if(e.which)
    key = e.which;
    else
    key = e.keyCode;
    */

    var input = document.getElementById(searchObj.searchField);

    if(key == 38 || key == 40)
    {
        if(searchObj.suggestUL != undefined && searchObj.suggestUL != null)
        {
            if(key == 38)
                searchObj.highlightPrevSearchSuggestion();
            else if(key == 40)
                searchObj.highlightNextSearchSuggestion();

            if(searchObj.selectedSuggestion != undefined && searchObj.selectedSuggestion != null)
            {
                input.value = searchObj.selectedSuggestion.innerHTML;
            }
            else
            {
                input.value = searchObj.lastSearchStr;
            }
        }

        return false;
    }
    else if(key == 27)
    {
        input.value = searchObj.lastSearchStr;
        searchObj.setSearchResultsVisible(false);
        return false;
    }

    return true;
}

function highlightNextSearchSuggestion()
{
    var suggestion;
    if(this.selectedSuggestion == undefined || this.selectedSuggestion == null)
        suggestion = this.suggestUL.firstChild;
    else
        suggestion = this.selectedSuggestion.nextSibling;

    while(suggestion != undefined && suggestion != null && suggestion.nodeName != "LI")
        suggestion = suggestion.nextSibling;

    this.highlightSearchItem(suggestion);
}

function highlightPrevSearchSuggestion()
{
    var suggestion;
    if(this.selectedSuggestion == undefined || this.selectedSuggestion == null)
        suggestion = this.suggestUL.lastChild;
    else
        suggestion = this.selectedSuggestion.previousSibling;

    while(suggestion != undefined && suggestion != null && suggestion.nodeName != "LI")
        suggestion = suggestion.previousSibling;

    this.highlightSearchItem(suggestion);
}


function highlightSearchItem(suggestion)
{
    if(!(this.selectedSuggestion == undefined || this.selectedSuggestion == null))
        this.selectedSuggestion.style.backgroundColor = '#ffffff';

    this.selectedSuggestion = suggestion;
    if(!(suggestion == undefined || suggestion == null))
    {
        this.selectedSuggestion.style.backgroundColor = '#ffffcc';
    }
}

function mouseoverSuggestion(e)
{
    var searchObj = getSearchByElement(this.parentNode.parentNode);
    searchObj.highlightSearchItem(this);
    searchObj.isMouseOverSuggestion = true;
}

function mouseoutSuggestion(e)
{
    var searchObj = getSearchByElement(this.parentNode.parentNode);
    searchObj.isMouseOverSuggestion = false;
}

function selectSearchItem(suggestion)
{
    if(!(suggestion == undefined || suggestion == null))
    {
        var value = suggestion.innerHTML;

        var fieldObj = document.getElementById(this.searchField);
        this.lastSearchStr = value;
        fieldObj.value = value;

        this.setSearchResultsVisible(false);
        this.selectedSuggestion = null;
    }
}

function clickSearchItem(e)
{
    var searchObj = getSearchByElement(this.parentNode.parentNode);
    searchObj.selectSearchItem(this);

    var input = document.getElementById(searchObj.searchField);
}



function doSearch(e)
{
    var searchObj = getSearchByElement(this);

    var key;

    if(!e)
        e = window.event;

    if(e.keyCode)
        key = e.keyCode;
    else if(e.which)
        key = e.which;

    /*
    if(window.event)
    key = event.keyCode;
    else if(e.which)
    key = e.which;
    else
    key = e.keyCode;
    */

    if(!(key == 38 || key == 40))
    {
        var input = document.getElementById(searchObj.searchField);
        var div = document.getElementById(searchObj.suggestContainer);

        var newSearchStr = input.value;

        if(newSearchStr == '')
        {
            searchObj.lastSearchStr = '';
            searchObj.selectedSuggestion = null;

            searchObj.setSearchResultsVisible(false);

            searchObj.suggestUL = null;
            while(div.childNodes.length > 0)
                div.removeChild(div.childNodes[0]);
        }
        else if(newSearchStr != searchObj.lastSearchStr)
        {
            searchObj.lastSearchStr = newSearchStr;
            searchObj.selectedSuggestion = null;

            searchObj.setSearchResultsVisible(false);

            searchObj.suggestUL = null;
            while(div.childNodes.length > 0)
                div.removeChild(div.childNodes[0]);

            var timeoutFunc = function() {sendSearchRequest(searchObj.searchField);};
            if(searchObj.searchTimer == null)
            {
                searchObj.searchTimer = setTimeout(timeoutFunc, 500);
            }
            else
            {
                clearTimeout(searchObj.searchTimer);
                searchObj.searchTimer = setTimeout(timeoutFunc, 500);
            }
        }
    }
}

function setSearchResultsVisible(visible)
{
    var container = document.getElementById(this.suggestContainer);

    if(visible && this.inputHasFocus)
        container.style.display = 'block';
    else
    {
        this.highlightSearchItem(null);
        this.selectedSuggestion = null;

        container.style.display = 'none';
    }
}

function focusSearchField(e)
{
    var searchObj = getSearchByElement(this);

    searchObj.inputHasFocus = true;
}

function blurSearchField(e)
{
    var searchObj = getSearchByElement(this);

    searchObj.inputHasFocus = false;

    if(!searchObj.isMouseOverSuggestion)
    {
        if(!e)
            e = window.event;

        searchObj.setSearchResultsVisible(false);
    }
}

var searchCount = 0;
function sendSearchRequest(id)
{
    var searchObj = getSearchById(id);

    var input = document.getElementById(searchObj.searchField);
    var searchStr = input.value;

    clearTimeout(searchObj.searchTimer);
    searchObj.searchTimer = null;

    if(searchStr != '' && !searchObj.isSearching)
    {
        var data = '';
        data += searchObj.valueName + '=' + encode(searchStr);
        data += '&' + searchObj.maxCountName + '=' + encode(searchObj.maxCount);
        data += '&' + searchObj.others;

        searchObj.isSearching = true;

        //var blah = document.getElementById('search-count');
        searchCount++;
        //blah.innerHTML = searchCount;

        var url;
        if(searchObj.postMethod)
        {
            url = searchObj.resultAddr;
            sendRequest(searchObj.suggestContainer, url, postResults, data);
        }
        else
        {
            url = searchObj.resultAddr + "?" + data;
            sendRequest(searchObj.suggestContainer, url, postResults);
        }
    }
}

function postResults(id, req)
{
    var searchObj = getSearchById(id);

    searchObj.isSearching = false;

    var reqResults = req.responseXML;
    var results = reqResults.getElementsByTagName('item');

    if(results.length > 0)
    {
        var input = document.getElementById(searchObj.searchField);
        var div = document.getElementById(searchObj.suggestContainer);

        var list = document.createElement('ul');
        searchObj.suggestUL = list;

        for(var i = 0; i < results.length; i++)
        {
            var item = document.createElement('li');
            item.onmouseover = mouseoverSuggestion;
            item.onmouseout = mouseoutSuggestion;
            item.onclick = clickSearchItem;
            item.innerHTML = results[i].childNodes[0].nodeValue;
            list.appendChild(item);
        }
        div.appendChild(list);

        searchObj.setSearchResultsVisible(true);
    }
}
