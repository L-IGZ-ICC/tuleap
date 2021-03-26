<?php

require_once('Widget.class.php');

/**
 * Widget_IgzAddons
 *
 * Widget_IgzAddons
 */
class Widget_IgzAddons extends Widget {
    public function __construct() {
        parent::__construct('igzaddons');
    }
    function getTitle() {
        return 'IGZ-Addons';
    }
    function getContent() {
        $content = '';
        $content .= '<script src="/plugins/igz_addon_access/AddonScripts.js"></script>';
        $content .= $this->appendCommonCSS();
        $content .= '<div id="General" style="display: none;font-weight: bold;font-size: medium;font-style: italic;color: rgba(0,51,255,0.98)">Momentan sind für Ihre Benutzergruppe keine IGZ-Addons verfügbar.</div>';
        $content .= '<script type="text/javascript">
        checkPermissions("General");
        </script>';
        $content .= '<div id="FullTextSearchAddon" style="display: none">';
        $content .= $this->appendStyleForFullTextSearch();
        $content .= '<script type="text/javascript">
        checkPermissions("FullTextSearchAddon");
        </script>';
        $content .= $this->appendContentForFullTextSearch();
        $content .= '</div>';
        $content .= '<div id="CheckTcAddon" style="display: none">';
        $content .= $this->appendStyleForTestCaseChecker();
        $content .= '<script type="text/javascript">
        checkPermissions("CheckTcAddon");
        </script>';
        $content .= $this->appendContentForTestCaseChecker();
        $content .= '</div>';

        return $content;
    }

    private function appendCommonCSS()
    {
        $content = '<style>
         table.hovertable {
	    font-family: verdana,arial,sans-serif;
	    font-size:11px;
	    color:#333333;
	    border-width: 1px;
	    border-color:  #D2D2D2;
	    border-collapse: collapse;
        }
        table.hovertable th {
	    background-color:#E4E4E4;
	    border-width: 1px;
	    padding: 8px;
	    color: #333333;
	    border-style: solid;
	    border-color: #D2D2D2;
        }
        table.hovertable tr {
	    background-color:#F9F9F9;
	    color: #333333;
        }
        
        table.hovertable p {
        font-family: verdana,arial,sans-serif  !important;
	    font-size:11px !important;
	    color:#333333  !important;
        }
        
        table.hovertable td {
	    border-width: 1px;
	    padding: 8px;
	    border-style: solid;
	    border-color: #D2D2D2;
        }
        
        </style>';
        return $content;
    }

    private function appendContentForFullTextSearch()
    {
        $content = '<div style="margin-bottom: 10px"><div style="text-decoration: underline;font-weight: bold;font-size: large">Suchfunktion</div>';
        $content .= '<div style="margin-top: 10px;height: 50px;">';
        $content .= '<input type="text" name="search" id="search" class="search" placeholder="Projekt durchsuchen..."  onkeydown="searchBarPressed(event)"><select id="searchOptions" class="searchOptions"></select><input type="button" name="search_button" id="search_button" onclick="searchButtonPressed()"><div id="searchLoading" class="loader" style="display: none;margin-left: 10px"></div></div>';
        $content .= '<script>
        var select = document.getElementById("searchOptions");
        var link = document.getElementById("sidebar-plugin_tracker");
        var gid = link.href.substring(link.href.indexOf("group_id=",0)+"group_id=".length,link.href.length);
        var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function(){
                if (xhttp.readyState === 4 && xhttp.status === 200) 
                {
                  var trackers = JSON.parse(xhttp.responseText);
                  if(trackers.length > 0)
                      {
                  var selecthtml = "<option value=\'"+-1+"\'>Alle</option>";
                  for(i = 0;i<trackers.length;i++)
                  {
                         selecthtml += "<option value=\'"+trackers[i].id+"\'>"+trackers[i].name+"</option>";
                  }
                   select.innerHTML = selecthtml;
                  }
                 }
            };
            xhttp.open("GET","/plugins/igz_addon_access/TrackersForProject.php?gid="+gid,true);
            xhttp.send();
        </script>';
        $content .= '<div id="resultContainerSearch" class="searchResultContainer"><div style="display: inline-block;width: 100%"><label id="searchStatusLabel" style="display: none;cursor: pointer;color: rgba(17,8,8,0.6);font-size: small;text-align: left;float: left;margin-right: 10px"></label><input type="button" id="search_close_button" class="close_button" onclick="hideSearchResultContainer()"/></div>';
        $content .= '<table id="searchResultsTable" class="hovertable" style="display: none;"></table></div></div>';
        return $content;
    }

    private function appendContentForTestCaseChecker()
    {
        $content = '<div style="text-decoration: underline;font-weight: bold;font-size: large">Prüfung Konsistenz Status verknüpfter Testfall und Offene Punkte</div>';
        $content .= '<div style="color: rgba(17,8,8,0.6);font-size: small">Suche Testfälle im Status „fehlerhaft“ mit verknüpften Offenen Punkten im Status "Retest offen","Retest erfolgreich" oder "erledigt"</div>';
        $content .= '<br><div><input id="checkButton" type="button" value="Prüfung durchführen" onclick="loadResultTable()" class="layout_manager_customize btn btn-small" /><div id="loading" class="loader" style="display: none;margin-left: 10px"></div></div>
        <div id="resultContainerTestCases" class="testCaseResultContainer"><div style="display: inline-block;width: 100%"><label id="statusLabel" style="display: none;cursor: pointer;float: left"></label>
        <input type="button" id="testCase_close_button" onclick="hideSearchTestcaseContainer()"/></div>';
        $content .= '<table id="resultTable" class="hovertable" style="display: none;padding-bottom: 15px;"></table>';
        $content .=  '</div></div>';
        return $content;
    }



    function getDescription() {
        return 'IGZ-Testportal Addons';
    }

    private function appendStyleForTestCaseChecker()
    {
        $content = '<style>
        .loader {
        border: 8px solid #f3f3f3; /* Light grey */
        border-top: 8px solid #3498db; /* Blue */
        border-radius: 50%;
        width: 8px;
        height: 8px;
        animation: spin 2s linear infinite;
        }
        
        #testCase_close_button {
	    border: 0 none;
	    background: gainsboro url(/plugins/igz_addon_access/resources/icon_close_black.png) center no-repeat ;
	    background-size: 16px;
	    width: 16px; 
	    padding: 0;
	    height: 16px;
	    cursor: pointer;
	    display: inline-block;
	    float: right;
	    }
	    
	    #checkButton {
	        display: inline;
	        background: #515b69;
	        border: #515b69;
	        color: white;
	        height: 30px;
	    }
	      
	    .testCaseResultContainer{
        background: gainsboro;
        display: none;
        border-radius: 10px;
        padding: 10px;
        }


        @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
}
        </style>';
        return $content;
    }

    private function appendStyleForFullTextSearch()
    {
        $content = '<style>
        ::-webkit-input-placeholder { /* Chrome/Opera/Safari */
        color: rgba(255,255,255,0.8);
        }
        ::-moz-placeholder { /* Firefox 19+ */
        color: rgba(255,255,255,0.8);
        }
        :-ms-input-placeholder { /* IE 10+ */
        color: rgba(255,255,255,0.8);
        }
        :-moz-placeholder { /* Firefox 18- */
        color: rgba(255,255,255,0.8);
        }


        #search_button {
	    border: 0 none;
	    background: #515b69 url(/plugins/igz_addon_access/resources/searchImg.png) center no-repeat;
	    width: 30px; 
	    padding: 0;
	    height: 30px;
	    vertical-align: middle;
	    cursor: pointer;
	    }
	    
	    #search_close_button {
	    border: 0 none;
	    background: gainsboro url(/plugins/igz_addon_access/resources/icon_close_black.png) center no-repeat ;
	    background-size: 16px;
	    width: 16px; 
	    padding: 0;
	    height: 16px;
	    cursor: pointer;
	    display: inline-block;
	    float: right;
	    }
	    
	    #searchOptions {
	    width: 15%;
	    display: inline;
	    height: 30px;
	    font-size: 14px;
	    background: #515b69;
	    vertical-align: middle;
	    color: white;
	    border-radius: 0px;
	    border: 0px;
	    }  
	      
	    .searchResultContainer{
        background: gainsboro;
        display: none;
        border-radius: 10px;
        padding: 10px;
        }
	    
	    select::-ms-expand {
        background:#515b69;
        color: white;
        border-width: 0px;
        }
	    

        input[type=text].search{
        padding: 5px 0 5px 20px;
	    font-size: 16px;
	    border-radius: 0px;
	    font-family: Montserrat, sans-serif;
	    border: 0 none;
	    height: 30px;
	    margin-right: 0;
	    vertical-align: middle;
	    color: white;
	    outline: none;
	    background: #515b69;
        transition: background 0.15s;
        }

        input[type=text].search:focus{
        background: rgb(108,108,108);
        }
        
        </style>';
        return $content;
    }

}
