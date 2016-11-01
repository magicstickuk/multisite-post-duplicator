jQuery(document).ready(function(){
    jQuery('#mpdLogTable').DataTable({
        "language": {
            "emptyTable": "No multisite duplications."
        },
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
            jQuery('.mpd-loading').hide();
            jQuery('#mpdLogTable').fadeIn();
        },
        'iDisplayLength' : 25
    });
    jQuery('#mpdLinkedTable').DataTable({
        "language": {
            "emptyTable": "There are no linked duplications yet."
        },
         "order": [[ 8, "desc" ]],
         "columnDefs": [
            {
                "targets": [ 8 ],
                "visible": false,
                "searchable": false
            }
        ],
        "initComplete": function( settings, json ) {
            jQuery('.mpd-loading').hide();
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