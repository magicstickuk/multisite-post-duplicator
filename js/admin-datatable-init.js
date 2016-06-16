jQuery(document).ready(function(){
    jQuery('#mpdLogTable').DataTable({
    	 "order": [[ 7, "desc" ]],
    	 "columnDefs": [
    	 	{'orderData':[7], 'targets': [6]},
            {
                "targets": [ 7 ],
                "visible": false,
                "searchable": false
            }
        ],
        "initComplete": function( settings, json ) {
            jQuery('#mpdLogTable').fadeIn();
        },
        'iDisplayLength' : 25
    });
    jQuery('#mpdLinkedTable').DataTable({
         "order": [[ 7, "desc" ]],
         "columnDefs": [
            {
                "targets": [ 7 ],
                "visible": false,
                "searchable": false
            }
        ],
        "initComplete": function( settings, json ) {
            jQuery('#mpdLinkedTable').fadeIn();
        },
        'iDisplayLength' : 25
    });

    jQuery('.removeURL').click(function(e) {
        e.preventDefault();
        if (window.confirm("Are you sure you want to delete the link between the source and destination post?")) {
            location.href = this.href;
        }
    });
});