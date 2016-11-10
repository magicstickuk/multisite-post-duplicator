jQuery(document).ready(function(){
    jQuery('#mpdLogTable').DataTable({
        "language": {
            "emptyTable": mpd_dt_vars.no_dups,
            "search":  mpd_dt_vars.search,
            "lengthMenu": mpd_dt_vars.show + " _MENU_ " + mpd_dt_vars.entries,
            "paginate": {
                "first":      mpd_dt_vars.first,
                "last":       mpd_dt_vars.last,
                "next":       mpd_dt_vars.next,
                "previous":   mpd_dt_vars.pervious
            },
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
            "emptyTable": mpd_dt_vars.no_linked_dups,
            "search":  mpd_dt_vars.search,
            "lengthMenu": mpd_dt_vars.show + " _MENU_ " + mpd_dt_vars.entries,
            "paginate": {
                "first":      mpd_dt_vars.first,
                "last":       mpd_dt_vars.last,
                "next":       mpd_dt_vars.next,
                "previous":   mpd_dt_vars.pervious
            },
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
        if (window.confirm(mpd_dt_vars.delete_link_warning)) {
             location.href = this.href;
         }
    });
});