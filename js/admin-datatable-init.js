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
        ]
    });
});