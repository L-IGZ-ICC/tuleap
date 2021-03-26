function loadResultTable() {
    var link = document.getElementById("sidebar-plugin_tracker");
    var gid = link.href.substring(link.href.indexOf("group_id=",0)+"group_id=".length,link.href.length);
    document.getElementById("checkButton").disabled = true;
    document.getElementById("loading").style.display = "inline-block";
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function(){populateTable(xhttp);};
    xhttp.open("GET","/plugins/igz_addon_access/CheckTc.php?gid="+gid,true);
    xhttp.send();
}

function searchBarPressed(event)
{
    if(event.keyCode === 13) {
        loadSearchResultTable();
    }
}

function searchButtonPressed()
{
    loadSearchResultTable();
}

function loadSearchResultTable() {
    var link = document.getElementById("sidebar-plugin_tracker");
    var gid = link.href.substring(link.href.indexOf("group_id=",0)+"group_id=".length,link.href.length);
    var select =document.getElementById("searchOptions");
    var tid = select.options[select.selectedIndex].value;
    var searchstring = document.getElementById("search").value;
    if(searchstring.trim().length == 0)
    {
        hideSearchResultContainer();
        var table = document.getElementById("searchResultsTable");
        table.innerHTML = "";
        table.style.display = "none";
        document.getElementById("searchStatusLabel").style.display ="none";
        return;
    }

    document.getElementById("search").disabled = true;
    document.getElementById("search_button").disabled = true;
    document.getElementById("searchLoading").style.display = "inline-block";
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function(){populateSearchTable(xhttp);};
    xhttp.open("GET","/plugins/igz_addon_access/Search.php?gid="+gid+"&tid="+tid+"&st="+encodeURIComponent(searchstring),true);
    xhttp.send();
}

function populateSearchTable(request)
{
    if (request.readyState === 4 && request.status === 200)
    {
        document.getElementById("searchLoading").style.display = "none";
        var statusLabel = document.getElementById("searchStatusLabel");
        var table = document.getElementById("searchResultsTable");
        var response = request.responseText;

        if(response !== "Access Denied")
        {
            var array = JSON.parse(response);
            if(array.length > 0)
            {
                var oldTable = document.getElementById('searchResultsTable'),
                    newTable = document.createElement('table');
                newTable.setAttribute("id","searchResultsTable");
                newTable.setAttribute("class","hovertable");
                newTable.setAttribute("style","display:");

                for(i = 0;i<array.length;i++) {
                    var searchterm = document.getElementById("search").value;
                    var spans = array[i].Feldwert.split(new RegExp(searchterm, "i"))
                    var tr = document.createElement('tr');
                    var tdListe = document.createElement('td');
                    var tdItemId = document.createElement('td');
                    var tdBezeichnung = document.createElement('td');
                    var tdFeldname = document.createElement('td');
                    var tdFeldwert = document.createElement('td');
                    var link = document.createElement('a');
                    link.setAttribute("target", "_blank");
                    link.setAttribute("href", "/plugins/tracker/?aid=" + array[i].ItemId);
                    link.appendChild(document.createTextNode(array[i].ItemId));
                    tdListe.appendChild(document.createTextNode(array[i].Liste));
                    tdItemId.appendChild(link);
                    tdBezeichnung.appendChild(document.createTextNode(array[i].Bezeichnung));
                    tdFeldname.appendChild(document.createTextNode(array[i].Feldname));
                    var finspans = [];
                    for (index = 0; index < spans.length; index++) {
                        var curspan = document.createElement("span");
                        curspan.innerHTML = spans[index];
                        finspans.push(curspan);

                        if (index < spans.length - 1) {
                            var indexOfSearchTerm = array[i].Feldwert.indexOf(spans[index]) + spans[index].length;
                            var tempspan = document.createElement("span");
                            tempspan.setAttribute("style", "background: yellow");
                            tempspan.innerHTML = array[i].Feldwert.substring(indexOfSearchTerm, indexOfSearchTerm + searchterm.length);
                            finspans.push(tempspan);
                        }
                    }
                    for (index = 0; index < finspans.length; index++) {
                        tdFeldwert.appendChild(finspans[index]);
                    }

                    tr.appendChild(tdListe);
                    tr.appendChild(tdItemId);
                    tr.appendChild(tdBezeichnung);
                    tr.appendChild(tdFeldname);
                    tr.appendChild(tdFeldwert);
                    newTable.appendChild(tr);
                }

                oldTable.parentNode.replaceChild(newTable, oldTable);

                if(array.length === 1)
                {
                    statusLabel.innerHTML = "Es wurde "+array.length+" Eintrag gefunden.";
                }
                else
                {
                    statusLabel.innerHTML = "Es wurden "+array.length+" Einträge gefunden.";
                }
            }
            else
            {
                statusLabel.innerHTML = "Es wurden keine Einträge gefunden.";
                statusLabel.style.display = "inline-block";
                table.style.display = "none";
            }
        }
        document.getElementById("resultContainerSearch").style.display = "inline-block";
        document.getElementById("search").disabled = false;
        document.getElementById("search_button").disabled = false;
        statusLabel.style.display = "";
    }
}

function hideSearchResultContainer()
{
    document.getElementById("resultContainerSearch").style.display = "none";
}

function hideSearchTestcaseContainer()
{
    document.getElementById("resultContainerTestCases").style.display = "none";
}

function checkPermissions(type)
{
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function (){
        if (xhttp.readyState === 4 && xhttp.status === 200)
        {
            if(JSON.parse(xhttp.response))
            {
                if(type !== "General") {
                    document.getElementById(type).style.display = "";
                }
                else{
                    document.getElementById(type).style.display = "none";
                }
            }
            else
            {
                if(type !== "General") {
                    document.getElementById(type).style.display = "none";
                }
                else {
                    document.getElementById(type).style.display = "";
                }
            }
        }
    };

    xhttp.open("GET","/plugins/igz_addon_access/CheckPermissions.php?type="+type,true);
    xhttp.send();

}

function populateTable(request)
{
    if (request.readyState === 4 && request.status === 200)
    {
        var statusLabel = document.getElementById("statusLabel");
        var table = document.getElementById("resultTable");
        var response = request.responseText;
        if(response != "Access Denied")
        {
            var array = JSON.parse(response);
            if(array.length > 0)
            {
                table.innerHTML = "<thead><tr><th>Projekt</th><th>Liste</th><th>Testfall ID</th><th>Testfallbezeichnung</th><th>Testfall Status</th><th>OP Status</th><th>OP Liste</th><th>OP ID</th><th>OP Bezeichnung</th></tr></thead>";
                for(i = 0;i<array.length;i++)
                {
                    table.innerHTML = table.innerHTML + "<tr><td>"+array[i].Projektname+"</td>"+"<td>"+array[i].TestfallTrackername+"<td><a target=\'_blank\' href=\'/plugins/tracker/?aid="+array[i].TestfallID+"\'>"+array[i].TestfallID+"</a></td>"+"<td>"+array[i].Testfallbezeichnung+"</td>"+"<td>"+array[i].TestfallStatus+"</td>"+"<td>"+array[i].OPStatus+"</td>"+"<td>"+array[i].OPTrackername+"<td><a target=\'_blank\' href=\'/plugins/tracker/?aid="+array[i].OpID+"\'>"+array[i].OpID+"</a></td>"+"<td>"+array[i].OPBezeichnung+"</td>"+"</tr>"
                }
                statusLabel.style.color = "#e73b34";
                if(array.length === 1)
                {
                    statusLabel.innerHTML = "Es wurde "+array.length+" Eintrag gefunden.";
                }
                else
                {
                    statusLabel.innerHTML = "Es wurden "+array.length+" Einträge gefunden.";
                }

                table.style.display = "";
                statusLabel.style.display = "";
            }
            else
            {
                table.style.display = "none";
                statusLabel.style.color = "#6c6c6c";
                statusLabel.innerHTML = "Es wurden keine Einträge gefunden.";
                statusLabel.style.display = "";
            }
        }
        document.getElementById("loading").style.display = "none";
        document.getElementById("checkButton").disabled = false;
        document.getElementById("resultContainerTestCases").style.display = "inline-block";
    }
}
