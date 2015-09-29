<?php

function duplicate_over_multisite($post_id_to_copy, $new_blog_id, $post_type, $post_author, $prefix, $post_status) {

    $mdp_post = get_post($post_id_to_copy);
 	
    $title =  get_the_title($mdp_post);

    if($prefix != ''){

    	$prefix = $prefix . ' ';

    }

    $mdp_post = array(

            'post_title' => $prefix . $title,
            'post_status' => $post_status,
            'post_type' => $post_type,
            'post_author' => $post_author,
 			'post_content'=> $mdp_post->post_content

    );
    
    $data = get_post_custom($mdp_post);
 
    $meta_values = get_post_meta($post_id_to_copy);

 
    switch_to_blog($new_blog_id);
 
    $post_id = wp_insert_post( $mdp_post);
 
       foreach ( $data as $key => $values) {

           foreach ($values as $value) {

               add_post_meta( $post_id, $key, $value );

            }

        }

  	 foreach ($meta_values as $key => $values) {

           foreach ($values as $value) {
 
                if(is_serialized($value)){
                 
                    add_post_meta( $post_id, $key, unserialize($value));

                }else{

                    add_post_meta( $post_id, $key, $value );

                }
               
            }

         }

     restore_current_blog();
     
     return $post_id;
 
}