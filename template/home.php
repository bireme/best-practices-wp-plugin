<?php
/*
Template Name: Best Practices Home
*/
global $bp_service_url, $bp_plugin_slug, $bp_texts, $solr_service_url;

require_once(BP_PLUGIN_PATH . '/lib/Paginator.php');

$order = array(
        'RELEVANCE' => 'score desc',
        'YEAR_ASC'  => 'publication_year asc',
        'YEAR_DESC' => 'publication_year desc'
    );

$bp_config         = get_option('bp_config');
$bp_initial_filter = $bp_config['initial_filter'];
$bp_addthis_id     = $bp_config['addthis_profile_id'];
$alternative_links = (bool)$bp_config['alternative_links'];

$site_language = strtolower(get_bloginfo('language'));
$lang = substr($site_language,0,2);
$locale = array(
    'pt' => 'pt_BR',
    'es' => 'es_ES',
    'fr' => 'fr_FR',
    'en' => 'en'
);

// set query using default param q (query) or s (wordpress search) or newexpr (metaiah)
$query = $_GET['s'] . $_GET['q'];
$query = stripslashes( trim($query) );
$query = ( $query ) ? $query : '*:*';

$user_filter = stripslashes($_GET['filter']);
$page   = ( !empty($_GET['page']) ? $_GET['page'] : 1 );
$format = ( !empty($_GET['format']) ? $_GET['format'] : '' );
$sort   = ( !empty($_GET['sort']) ? $order[$_GET['sort']] : '');
$count  = ( !empty($_GET['count']) ? $_GET['count'] : 10 );
$total  = 0;
$filter = '';

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

$bp_service_request = $solr_service_url . '/solr/best-practices/select/?q=' . urlencode($query) . '&fq=' . urlencode($filter) . '&start=' . $start . '&count=' . $count . '&wt=json';

// $bp_service_request = $bp_service_url . '/api/bp?offset=' . $start . '&limit=' . $count . '&lang=' . $locale[$lang];;

$filter_list = explode(";", $bp_config['available_filter']);

foreach ($filter_list as $filter_field){
    $bp_service_request.= "&facet.field=" . urlencode($filter_field);
}

if ( $user_filter != '' ) {
    $user_filter_list = preg_split("/ AND /", $user_filter);
    $applied_filter_list = array();
    foreach($user_filter_list as $filter){
        preg_match('/([a-z_]+):(.+)/',$filter, $filter_parts);
        if ($filter_parts){
            // convert to internal format
            $applied_filter_list[$filter_parts[1]][] = str_replace('"', '', $filter_parts[2]);
        }
    }
}

// echo "<pre>"; print_r($bp_service_request); echo "</pre>"; die();

$response = @file_get_contents($bp_service_request);
if ($response){
    $response_json = json_decode($response);
    //echo "<pre>"; print_r($response_json); echo "</pre>";
    $total = $response_json->response->numFound;
    $start = $response_json->response->start;
    $docs_list = $response_json->response->docs;
    $facet_list = (array) $response_json->facet_counts->facet_fields;
}

/*
$response = @file_get_contents($bp_service_request);
if ($response){
    $response_json = json_decode($response);
    // echo "<pre>"; print_r($response_json); echo "</pre>"; die();
    $total = $response_json->total;
    $items = $response_json->items;
}
*/

$params  = !empty($format) ? '&format=' . $format : '';
$params .= $count != 2 ? '&count=' . $count : '';
$params .= !empty($_GET['sort']) ? '&sort=' . $_GET['sort'] : '';

$page_url_params = real_site_url($bp_plugin_slug) . '?q=' . urlencode($query) . '&filter=' . urlencode($user_filter) . $params;
$feed_url = real_site_url($bp_plugin_slug) . 'best-practices-feed?q=' . urlencode($query) . '&filter=' . urlencode($filter);

$pages = new Paginator($total, $start);
$pages->paginate($page_url_params);

$home_url = isset($bp_config['home_url_' . $lang]) ? $bp_config['home_url_' . $lang] : real_site_url();
$plugin_breadcrumb = isset($bp_config['plugin_title_' . $lang]) ? $bp_config['plugin_title_' . $lang] : $bp_config['plugin_title'];

?>

<?php get_header('best-practices');?>

    <div id="content" class="row-fluid">
        <div class="ajusta2">
            <div class="row-fluid breadcrumb">
                <a href="<?php echo $home_url ?>"><?php _e('Home','bp'); ?></a> >
                <?php if ($query == '' && $filter == ''): ?>
                    <?php echo $plugin_breadcrumb; ?>
                <?php else: ?>
                    <a href="<?php echo real_site_url($bp_plugin_slug); ?>"><?php echo $plugin_breadcrumb; ?></a> >
                    <?php _e('Search result', 'bp'); ?>
                <?php endif; ?>
            </div>

            <!-- Start sidebar best-practices-header -->
            <div class="row-fluid">
                <?php dynamic_sidebar('best-practices-header');?>
            </div>
            <div class="spacer"></div>
            <!-- end sidebar best-practices-header -->

            <section class="header-search">
                <form role="search" method="get" name="searchForm" id="searchForm" action="<?php echo real_site_url($bp_plugin_slug); ?>">
                    <input type="hidden" name="lang" id="lang" value="<?php echo $lang; ?>">
                    <input type="hidden" name="sort" id="sort" value="<?php echo $_GET['sort']; ?>">
                    <input type="hidden" name="format" id="format" value="<?php echo $format ? $format : 'summary'; ?>">
                    <input type="hidden" name="count" id="count" value="<?php echo $count; ?>">
                    <input type="hidden" name="page" id="page" value="1">
                    <input value='<?php echo ( '*:*' == $query ) ? '' : $query; ?>' name="q" class="input-search" id="s" type="text" placeholder="<?php _e('Enter one or more words', 'bp'); ?>">
                    <input id="searchsubmit" value="<?php _e('Search', 'bp'); ?>" type="submit">
                    <a href="#" title="<?php _e('Tip! You can do your search using boolean operators.', 'bp'); ?>" class="help ketchup tooltip"><i class="fa fa-question-circle fa-2x"></i></a>
                </form>
                <div class="pull-right rss">
                    <!-- <a href="<?php echo $feed_url ?>" target="blank"><img src="<?php echo BP_PLUGIN_URL; ?>template/images/icon_rss.png" ></a> -->
                </div>
            </section>
            <div class="content-area result-list">
                <section id="conteudo">
                    <?php if ( isset($total) && strval($total) == 0 ) :?>
                        <header class="row-fluid border-bottom">
                            <h1 class="h1-header"><?php _e('No results found','bp'); ?></h1>
                        </header>
                    <?php else :?>
                        <header class="row-fluid border-bottom">
                            <?php if ( ( $query != '' || $user_filter != '' ) && strval($total) > 0) :?>
                                <h1 class="h1-header"><?php _e('Results', 'bp'); echo ': ' . $total ?></h1>
                            <?php else: ?>
                                <h1 class="h1-header"><?php _e('Total', 'bp'); echo ': ' . $total ?></h1>
                            <?php endif; ?>
                        </header>
                        <div class="row-fluid">

                            <?php foreach ( $docs_list as $doc ) : ?>
                                <article class="conteudo-loop">
                                    <h2 class="h2-loop-tit">
                                        <a href="<?php echo real_site_url($bp_plugin_slug); ?>resource/?id=<?php echo $doc->id; ?>"><?php echo $doc->title; ?></a>
                                    </h2>

                                    <?php if ( $doc->introduction ): ?>
                                        <div class="row-fluid">
                                            <?php echo wp_trim_words( $doc->introduction, 40, '...' ); ?>
                                        </div>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>

                        </div>
                        <div class="row-fluid">
                            <?php echo $pages->display_pages(); ?>
                        </div>
                    <?php endif; ?>
                </section>
                <aside id="sidebar">

                    <?php dynamic_sidebar('best-practices-home');?>

                    <?php if (strval($total) > 0) :?>
                        <div id="filter-link" style="display: none">
                            <div class="mobile-menu" onclick="animateMenu(this)">
                                <a href="javascript:showHideFilters()">
                                    <div class="menu-bar">
                                        <div class="bar1"></div>
                                        <div class="bar2"></div>
                                        <div class="bar3"></div>
                                    </div>
                                    <div class="menu-item">
                                        <?php _e('Filters','bp') ?>
                                    </div>
                                </a>
                           </div>
                        </div>
                        <div id="filters">

                            <?php if ($applied_filter_list) :?>
                                <section class="row-fluid widget_categories">
                                    <header class="row-fluid marginbottom15">
                                        <h1 class="h1-header"><?php echo _e('Selected filters', 'bp') ?></h1>
                                    </header>
                                    <form method="get" name="searchFilter" id="formFilters" action="<?php echo real_site_url($bp_plugin_slug); ?>">
                                        <input type="hidden" name="lang" id="lang" value="<?php echo $lang; ?>">
                                        <input type="hidden" name="sort" id="sort" value="<?php echo $sort; ?>">
                                        <input type="hidden" name="format" id="format" value="<?php echo $format; ?>">
                                        <input type="hidden" name="count" id="count" value="<?php echo $count; ?>">
                                        <input type="hidden" name="q" id="query" value="<?php echo $query; ?>" >
                                        <input type="hidden" name="filter" id="filter" value="" >

                                        <?php foreach ( $applied_filter_list as $filter => $filter_values ) :?>
                                            <ul>
                                            <strong>
                                                <?php
                                                    $filter_field = ($filter == 'mj' ? 'descriptor_filter' : $filter);
                                                    echo translate_label($bp_texts, $filter_field, 'filter')
                                                ?>
                                            </strong>

                                            <?php foreach ( $filter_values as $value ) :?>
                                                <input type="hidden" name="apply_filter" class="apply_filter"
                                                        id="<?php echo md5($value) ?>" value='<?php echo $filter . ':"' . $value . '"'; ?>' >
                                                <li>
                                                    <span class="filter-item">
                                                        <?php
                                                            if (strpos($value, '^') !== false){
                                                                echo print_lang_value($value, $site_language);
                                                            }elseif (array_key_exists($filter, $bp_texts)){
                                                                echo translate_label($bp_texts, $value, $filter);
                                                            }else{
                                                                echo $value;
                                                            }
                                                        ?>
                                                    </span>
                                                    <span class="filter-item-del">
                                                        <a href="javascript:remove_filter('<?php echo md5($value) ?>')">
                                                            <img src="<?php echo BP_PLUGIN_URL; ?>template/images/del.png">
                                                        </a>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                            </ul>
                                        <?php endforeach; ?>
                                    </form>
                                </section>
                            <?php endif; ?>

                            <?php
                                foreach($filter_list as $filter_field) {
                            ?>
                                <?php if ($facet_list[$filter_field] ): ?>
                                    <section class="row-fluid widget_categories">
                                        <header class="row-fluid border-bottom marginbottom15">
                                            <h1 class="h1-header"><?php echo $bp_texts['filter'][$filter_field]; ?></h1>
                                        </header>
                                        <ul>
                                            <?php foreach ( $facet_list[$filter_field] as $filter_item ) { ?>
                                                <li class="cat-item">
                                                    <?php
                                                        $filter_value = $filter_item[0];
                                                        $filter_count = $filter_item[1];
                                                        if ($filter_field == 'descriptor_filter'){
                                                            $filter_field = 'mj';
                                                        }

                                                        $filter_link = '?';
                                                        if ($query != ''){
                                                            $filter_link .= 'q=' . $query . '&';
                                                        }
                                                        $filter_link .= 'filter=' . $filter_field . ':"' . $filter_value . '"';
                                                        if ($user_filter != ''){
                                                            $filter_link .= ' AND ' . $user_filter ;
                                                        }
                                                    ?>
                                                    <?php if ( strpos($filter_value, '^') !== false ): ?>
                                                        <a href='<?php echo $filter_link; ?>'><?php print_lang_value($filter_value, $site_language); ?></a>
                                                    <?php elseif ( array_key_exists($filter_field, $bp_texts) ): ?>
                                                        <a href='<?php echo $filter_link; ?>'><?php  echo translate_label($bp_texts, $filter_value, $filter_field); ?></a>
                                                    <?php else: ?>
                                                        <a href='<?php echo $filter_link; ?>'><?php echo $filter_value; ?></a>
                                                    <?php endif; ?>
                                                    <span class="cat-item-count"><?php echo $filter_count; ?></span>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </section>
                                <?php endif; ?>
                            <?php } ?>

                        </div> <!-- close DIV.filters -->
                    <?php endif; ?>
                </aside>
                <div class="spacer"></div>
            </div> <!-- close DIV.result-area -->
        </div> <!-- close DIV.ajusta2 -->
    </div>
<?php get_footer(); ?>
