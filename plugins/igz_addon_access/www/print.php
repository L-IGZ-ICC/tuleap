<html>
 <head>
  <title>IGZ Testportal Export</title>
  
		<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css"/>
		<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.2.1/css/buttons.dataTables.min.css"/>

		<script type="text/javascript" src="https://code.jquery.com/jquery-2.2.3.min.js"> </script>

		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
		<script type="text/javascript" src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js"></script>
		<script type="text/javascript" src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js"></script>
		<script type="text/javascript" src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
		
		<!--<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.2.1/js/buttons.html5.min.js"></script>-->
		
		<script type="text/javascript" src="buttons.html5.js"></script>
		
		<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.2.1/js/buttons.print.min.js"></script>

		<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.2.1/js/dataTables.buttons.min.js"></script>
  
 </head>
 <body>

 <div id="paste">
	<?php 
	
	$post = $_REQUEST['tableHtml'];
	$post = urldecode($post);
	$post = rawurldecode($post);
	
	echo($post);
	
	?>
 </div>
 
<script type="text/javascript">



$( ".tracker_report_table_aggregates" ).remove();

$("#tracker_report_table thead tr th").each(function() {
  $( this ).removeClass(  );
  
  $(this).html($(this).find('a').html());
});

//$("td").removeClass();
//$("th").removeClass();

$('#tracker_report_table').addClass("display");

var table = $('#tracker_report_table').DataTable({
    select: true,
	searching: true,
	"pageLength": 50,
    ordering:  true,
	info:false,
	"lengthChange": false,
	dom: 'Bfrtip',
	buttons: [
        "excel", "copy", "print",

            {
				filename: 'export',
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL'
            }

    ],
	"language": 
	{
		"lengthMenu": "_MENU_ Einträge pro Seite",
		"zeroRecords": "Keine Einträge gefunden",
		"info": "Seite _PAGE_ von _PAGES_",
		"infoEmpty": "Keine Einträge vorhanden",
		"infoFiltered": "(gefiltert von _MAX_ Einträgen gesamt)",
		"paginate": 
		{
			"first":      "Erste",
			"last":       "Letzte",
			"next":       "Nächste",
			"previous":   "Vorherige"
		},
		"search":         "Suche:",
		"aria": 
		{
			"sortAscending":  ": Klicken - Spalte aufsteigend sortieren",
			"sortDescending": ": Klicken - Spalte absteigend sortieren"
		}
    }
});
 </script>
 </body>
</html>