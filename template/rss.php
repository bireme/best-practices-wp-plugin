<?php
echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";

/*
Template Name: Best Practices record RSS
*/

global $bp_service_url, $bp_plugin_slug;

$bp_config = get_option('bp_config');
$bp_initial_filter = $bp_config['initial_filter'];

$site_language = strtolower(get_bloginfo('language'));
$lang_dir = substr($site_language,0,2);

$query       = ( isset($_GET['s']) ? $_GET['s'] : $_GET['q'] );
$user_filter = stripslashes($_GET['filter']);
$page        = ( isset($_GET['page']) ? $_GET['page'] : 1 );
$total       = 0;
$count       = 10;
$filter      = '';

if ($bp_initial_filter != ''){
    if ($user_filter != ''){
        $filter = $bp_initial_filter . ' AND ' . $user_filter;
    }else{
        $filter = $bp_initial_filter;
    }
}else{
    $filter = $user_filter;
}
$start = ($page * $count) - $count;

$bp_service_request = $bp_service_url . 'api/bibliographic/search/?q=' . urlencode($query) . '&fq=' . urlencode($filter) . '&start=' . $start . '&lang=' . $lang_dir;

//print $bp_service_request;

$response = @file_get_contents($bp_service_request);
if ($response){
    $response_json = json_decode($response);
    //echo "<pre>"; print_r($response_json); echo "</pre>";
    $total = $response_json->diaServerResponse[0]->response->numFound;
    $start = $response_json->diaServerResponse[0]->response->start;
    $docs_list = $response_json->diaServerResponse[0]->response->docs;
}

$page_url_params = home_url($bp_plugin_slug) . '?q=' . urlencode($query) . '&filter=' . urlencode($filter);

?>
<rss version="2.0">
    <channel>
        <title><?php _e('Best Practices records', 'bp') ?> <?php echo ($query != '' ? '|' . $query : '') ?></title>
        <link><?php echo htmlspecialchars($page_url_params) ?></link>
        <description><?php echo $query ?></description>
        <lastBuildDate><?php echo date_format(date_create(), 'D, d M Y H:i:s T');?></lastBuildDate>
        <?php
            foreach ( $docs_list as $doc ) {
                echo "<item>\n";
                echo "   <title><![CDATA[" . htmlspecialchars(implode("/", $doc->reference_title)) . "]]></title>\n";
                if ( $doc->author ){
                    echo "   <author><![CDATA[" . implode(", ", $doc->author) . "]]></author>\n";
                }
                echo "   <link>" . home_url($bp_plugin_slug) .'/resource/?id=' . $doc->id . "</link>\n";
                if ( $doc->reference_abstract ) {
                    echo "   <description><![CDATA[" . implode("<br /><br />", $doc->reference_abstract) . "]]></description>\n";
                }
                echo "   <guid isPermaLink=\"false\">" . $doc->django_id . "</guid>\n";

                $pub_date = ($doc->updated_date != '' ? $doc->updated_date : $doc->created_date);
                echo "   <pubDate>";
                if ($pub_date != ''){
                    echo date_format(date_create($pub_date), 'D, d M Y H:i:s T');
                }
                echo "</pubDate>\n";
                echo "</item>\n";
            }
        ?>
    </channel>
</rss>
