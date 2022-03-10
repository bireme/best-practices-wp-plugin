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
$page   = ( !empty($_GET['page'] ) ? $_GET['page'] : 1 );
$format = ( !empty($_GET['format'] ) ? $_GET['format'] : 'summary' );
$sort   = ( !empty($_GET['sort'] ) ? $order[$_GET['sort']] : '');
$count  = ( !empty($_GET['count'] ) ? $_GET['count'] : 10 );
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

$bp_service_request = $solr_service_url . '/solr/best-practices/select/?q=' . urlencode($query) . '&fq=' . urlencode($filter) . '&start=' . $start . '&rows=' . $count . '&wt=json';

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
$params .= !empty($count) ? '&count=' . $count : '';
$params .= !empty($_GET['sort']) ? '&sort=' . $_GET['sort'] : '';

$page_url_params = real_site_url($bp_plugin_slug) . '?q=' . urlencode($query) . '&filter=' . urlencode($user_filter) . $params;
$feed_url = real_site_url($bp_plugin_slug) . 'best-practices-feed?q=' . urlencode($query) . '&filter=' . urlencode($filter);

$pages = new Paginator($total, $start);
$pages->paginate($page_url_params);

$home_url = ( $bp_config['home_url_' . $lang] ) ? $bp_config['home_url_' . $lang] : real_site_url();
$plugin_breadcrumb = isset($bp_config['plugin_title_' . $lang]) ? $bp_config['plugin_title_' . $lang] : $bp_config['plugin_title'];
if ( empty($plugin_breadcrumb) ) $plugin_breadcrumb = get_bloginfo('name');

?>

<?php get_header('best-practices');?>

<section id="sectionSearch" class="padding2">
	<div class="container">
		<div class="col-md-12">
            <form role="search" method="get" name="searchForm" id="searchForm" action="<?php echo real_site_url($bp_plugin_slug); ?>">
                <div class="row g-3">
                    <div class="col-9 offset-1 text-right">
                        <input type="hidden" name="lang" id="lang" value="<?php echo $lang; ?>">
                        <input type="hidden" name="sort" id="sort" value="<?php echo $sort; ?>">
                        <input type="hidden" name="format" id="format" value="<?php echo $format; ?>">
                        <input type="hidden" name="count" id="count" value="<?php echo $count; ?>">
                        <input type="hidden" name="page" id="page" value="1">
                        <input value='<?php echo ( '*:*' == $query ) ? '' : $query; ?>' name="q" class="form-control input-search" id="fieldSearch" type="text" autocomplete="off" placeholder="<?php _e('Enter one or more words', 'bp'); ?>">
                        <a id="speakBtn" href="#"><i class="fas fa-microphone-alt"></i></a>
                    </div>
                    <div class="col-1 float-end">
                        <button type="submit" id="submitHome" class="btn btn-warning">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
		</div>
	</div>
</section>

<!-- Start sidebar best-practices-header -->
<div class="row-fluid">
    <?php dynamic_sidebar('best-practices-header');?>
</div>
<div class="spacer"></div>
<!-- end sidebar best-practices-header -->

<section class="padding1">
	<div class="container">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo $home_url ?>"><?php _e('Home','bp'); ?></a></li>
            <?php if ($query == '' && $filter == ''): ?>
                <li class="breadcrumb-item active" aria-current="page"><?php echo $plugin_breadcrumb; ?></li>
            <?php else: ?>
                <li class="breadcrumb-item"><a href="<?php echo real_site_url($bp_plugin_slug); ?>"><?php echo $plugin_breadcrumb; ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php _e('Search result', 'bp'); ?></li>
            <?php endif; ?>
          </ol>
        </nav>

        <?php if ( $total ) : ?>
            <?php if ( ( $query != '' || $user_filter != '' ) && strval($total) > 0) :?>
                <h3 class="title1"><?php _e('Results', 'bp'); echo ': ' . $total; ?></h3>
            <?php else: ?>
                <h3 class="title1"><?php _e('Total', 'bp'); echo ': ' . $total; ?></h3>
            <?php endif; ?>
        <?php endif; ?>

        <div class="row">
            <?php if ( isset($total) && strval($total) == 0 ) :?>
                <div class="col-md-9 text-center">
                    <div class="alert alert-secondary" role="alert">
                        <?php echo mb_strtoupper(__('No results found','bp')); ?>
                    </div>
                </div>
            <?php else :?>
                <div class="col-md-9">
                    <?php foreach ( $docs_list as $doc ) : ?>
                        <article>
                            <div class="destaqueBP">
                                <a href="<?php echo real_site_url($bp_plugin_slug); ?>resource/?id=<?php echo $doc->id; ?>"><b><?php echo $doc->title; ?></b></a>
                                <?php if ( $doc->introduction ): ?>
                                    <p><?php echo wp_trim_words( $doc->introduction, 60, '...' ); ?></p>
                                <?php endif; ?>
                                <?php if ( $doc->target ): ?>
                                    <b><?php _e('Goals','bp'); ?>:</b>
                                    <?php $targets = get_bp_targets($doc->target, $lang); ?>
                                    <?php foreach ( $targets as $target ) : ?>
                                        <a href="javascript:void(0)" class="aSpan" data-toggle="tooltip" data-placement="top"><?php echo $target; ?></a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <nav aria-label="Pagination">
                        <?php echo $pages->display_pages(); ?>
                    </nav>
                </div>
            <?php endif; ?>

            <div class="col-md-3 bp-filters">
                <?php if (strval($total) > 0) : ?>

                    <?php dynamic_sidebar('best-practices-home');?>

                    <?php if ($applied_filter_list) :?>
                        <h4><?php echo __('Selected Filters', 'bp'); ?></h4>

                        <form method="get" name="searchFilter" id="formFilters" action="<?php echo real_site_url($bp_plugin_slug); ?>">
                            <input type="hidden" name="lang" id="lang" value="<?php echo $lang; ?>">
                            <input type="hidden" name="sort" id="sort" value="<?php echo $sort; ?>">
                            <input type="hidden" name="format" id="format" value="<?php echo $format; ?>">
                            <input type="hidden" name="count" id="count" value="<?php echo $count; ?>">
                            <input type="hidden" name="q" id="query" value="<?php echo ( '*:*' == $query ) ? '' : $query; ?>" >
                            <input type="hidden" name="filter" id="filter" value="">

                            <div class="box1 title1 mb-4">
                                <table class="table table-sm">
                                    <?php foreach ( $applied_filter_list as $filter => $filter_values ) :?>
                                        <tr>
                                            <td colspan="2"><strong class="filter-item-active"><?php echo translate_label($bp_texts, $filter, 'filter'); ?></strong></td>
                                        </tr>
                                        <?php foreach ( $filter_values as $value ) :?>
                                            <tr>
                                                <td>
                                                    <input type="hidden" name="apply_filter" class="apply_filter" id="<?php echo md5($value) ?>" value='<?php echo $filter . ':"' . $value . '"'; ?>' >
                                                    <span>
                                                        <?php
                                                            if (strpos($value, '^') !== false){
                                                                echo print_lang_value($value, $lang);
                                                            }elseif (array_key_exists($filter, $bp_texts)){
                                                                echo translate_label($bp_texts, $value, $filter);
                                                            }else{
                                                                echo $value;
                                                            }
                                                        ?>
                                                    </span>
                                                </td>
                                                <td width="35">
                                                    <span class="filter-item-del">
                                                        <a href="javascript:remove_filter('<?php echo md5($value) ?>')">
                                                            <i class="fas fa-times fa-lg"></i>
                                                        </a>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        </form>
                    <?php endif; ?>

                    <h4><?php echo __('Filters', 'bp'); ?></h4>

                    <?php foreach($filter_list as $filter_field) : ?>
                        <?php if ($facet_list[$filter_field] ) : $count = 0; ?>
                            <div class="box1 title1">
                                <h4><?php echo mb_strtoupper($bp_texts['filter'][$filter_field]); ?></h4>
                                <table class="table table-sm">
                                    <?php
                                        $odd = array();
                                        $even = array();
                                        $both = array(&$even, &$odd);
                                        array_walk($facet_list[$filter_field], function($v, $k) use ($both) { $both[$k % 2][] = $v; });
                                        $filters = array_map(function($a, $b) { return $a . '@' . $b; }, $both[0], $both[1]);
                                    ?>
                                    <?php foreach ( $filters as $filter_item ) { $count++; ?>
                                        <?php
                                            $filter_data = explode('@', $filter_item);
                                            $filter_value = $filter_data[0];
                                            $filter_count = $filter_data[1];

                                            $applied_filter = $applied_filter_list[$filter_field];
                                            if ( $applied_filter && in_array($filter_value, $applied_filter) ) {
                                                $filter_link = 'javascript:void(0)';
                                            } else {
                                                $filter_link = '?';
                                                if ($query != ''){
                                                    $filter_link .= 'q=' . $query . '&';
                                                }
                                                $filter_link .= 'filter=' . $filter_field . ':"' . $filter_value . '"';
                                                if ($user_filter != ''){
                                                    $filter_link .= ' AND ' . $user_filter ;
                                                }
                                            }
                                        ?>
                                        <?php if ( strpos($filter_value, '^') !== false ): ?>
                                            <tr>
                                                <td><a href='<?php echo $filter_link; ?>'><?php print_lang_value($filter_value, $lang); ?></a></td>
                                                <td width="35"><span class="badge badge-primary"><?php echo $filter_count; ?></span></td>
                                            </tr>
                                        <?php elseif ( array_key_exists($filter_field, $bp_texts) ): ?>
                                            <tr>
                                                <td><a href='<?php echo $filter_link; ?>'><?php echo translate_label($bp_texts, $filter_value, $filter_field); ?></a></td>
                                                <td width="35"><span class="badge badge-primary"><?php echo $filter_count; ?></span></td>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td><a href='<?php echo $filter_link; ?>'><?php echo $filter_value; ?></a></td>
                                                <td width="35"><span class="badge badge-primary"><?php echo $filter_count; ?></span></td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php } ?>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                <?php endif; ?>
			</div>
        </div>
    </div>
</section>
<?php get_footer(); ?>
