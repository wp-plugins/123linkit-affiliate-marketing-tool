<?php
    $api_address = "174.143.204.12";


    $url = "http://$api_address/api/click";

    if(isset($_GET)){
        $post_id = $_GET['pid'];
        $blog_id = $_GET['bid'];
        $ad_id = $_GET['aid'];
        $link_id = $_GET['lid'];
        $key = $_GET['key'];        
    
        header("Location: $url?post_id=$post_id&blog_id=$blog_id&advertiser_id=$ad_id&link_id=$link_id&_pubkey=$key");
    }
    
?>
