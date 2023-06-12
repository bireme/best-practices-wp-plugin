<?php
/*
Plugin Name: Best Practices
Plugin URI: https://github.com/bireme/best-practices-wp-plugin
Description: Search good practices records.
Author: BIREME/OPAS/OMS
Version: 1.0
Author URI: http://reddes.bvsalud.org/
*/

define('BP_PLUGIN_VERSION', '1.4' );

define('BP_SYMBOLIC_LINK', false );
define('BP_PLUGIN_DIRNAME', 'best-practices' );
define('BP_PLUGIN_BASENAME', plugin_basename( __FILE__ ));

if(BP_SYMBOLIC_LINK == true) {
    define('BP_PLUGIN_PATH',  ABSPATH . 'wp-content/plugins/' . BP_PLUGIN_DIRNAME );
} else {
    define('BP_PLUGIN_PATH',  plugin_dir_path(__FILE__) );
}

define('BP_PLUGIN_DIR', plugin_basename( BP_PLUGIN_PATH ) );
define('BP_PLUGIN_URL', plugin_dir_url(__FILE__) );

require_once(BP_PLUGIN_PATH . '/settings.php');
require_once(BP_PLUGIN_PATH . '/template-functions.php');
require_once(BP_PLUGIN_PATH . '/widgets.php');

if(!class_exists('Best_Practices_Plugin')) {
    class Best_Practices_Plugin {

        private $plugin_slug = 'best-practices';
        private $service_url = 'https://admin.portalbp.bvsalud.org';
        private $similar_docs_url = 'http://similardocs.bireme.org/SDService';
        private $solr_service_url = 'http://plugins-idx.bvsalud.org:8983';

        /**
         * Construct the plugin object
         */
        public function __construct() {
            // register actions

            add_action('init', array(&$this, 'load_translation'));
            add_action('admin_menu', array(&$this, 'admin_menu'));
            add_action('plugins_loaded', array(&$this, 'plugin_init'));
            add_action('wp_head', array(&$this, 'google_analytics_code'));
            add_action('template_redirect', array(&$this, 'template_redirect'));
            add_action('widgets_init', array(&$this, 'register_sidebars'));
            add_action('after_setup_theme', array(&$this, 'title_tag_setup'));
            add_filter('get_search_form', array(&$this, 'search_form'));
            add_filter('document_title_separator', array(&$this, 'title_tag_sep'));
            add_filter('document_title_parts', array(&$this, 'theme_slug_render_title'));
            add_filter('wp_title', array(&$this, 'theme_slug_render_wp_title'));
            add_filter('plugin_action_links_'.BP_PLUGIN_BASENAME, array(&$this, 'settings_link'));

        } // END public function __construct

        /**
         * Activate the plugin
         */
        public static function activate()
        {
            // Do nothing
        } // END public static function activate

        /**
         * Deactivate the plugin
         */
        public static function deactivate()
        {
            // Do nothing
        } // END public static function deactivate


        function load_translation(){
            // Translations
            load_plugin_textdomain( 'bp', false,  BP_PLUGIN_DIR . '/languages' );
        }

        function plugin_init() {
            global $bp_texts;

            $bp_config = get_option('bp_config');
            $bp_config['use_translation'] = true;

            if ($bp_config && $bp_config['plugin_slug'] != ''){
                $this->plugin_slug = $bp_config['plugin_slug'];
            }
            if ($bp_config['use_translation']){
                $site_language = strtolower(get_bloginfo('language'));
                $lang = substr($site_language,0,2);

                $bp_texts = @parse_ini_file(BP_PLUGIN_PATH . "/languages/texts_".$lang.".ini", true);
                if ( !$bp_texts ) {
                    $bp_texts = @parse_ini_file(BP_PLUGIN_PATH . "/languages/texts_".$lang."-SAMPLE.ini", true);
                    if ( !$bp_texts ) {
                        $bp_texts = @parse_ini_file(BP_PLUGIN_PATH . "/languages/texts_en-SAMPLE.ini", true);
                    }
                }
            }

        }

        function admin_menu() {
            add_options_page(__('Good Practices settings', 'bp'), __('Good Practices', 'bp'),
                'manage_options', 'bp-settings', 'bp_page_admin');
            //call register settings function
            add_action( 'admin_init', array(&$this, 'register_settings'));
        }

        function settings_link( $links ) {
    		// Build and escape the URL.
    		$url = esc_url( add_query_arg(
    			'page',
    			'bp-settings',
    			get_admin_url() . 'admin.php'
    		) );

    		// Create the link.
    		$settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';

    		// Adds the link to the end of the array.
    		array_push(
    			$links,
    			$settings_link
    		);

    		return $links;
    	}

        function template_redirect() {
            global $wp, $bp_service_url, $bp_plugin_slug, $similar_docs_url, $solr_service_url;
            $pagename = '';

            // check if request contains plugin slug string
            $pos_slug = strpos($wp->request, $this->plugin_slug);
            if ( $pos_slug !== false ){
                $pagename = substr($wp->request, $pos_slug);
            }

            if ( is_404() && $pos_slug !== false ){
                $bp_service_url = $this->service_url;
                $bp_plugin_slug = $this->plugin_slug;
                $similar_docs_url = $this->similar_docs_url;
                $solr_service_url = $this->solr_service_url;

                if ($pagename == $this->plugin_slug ||
                    $pagename == $this->plugin_slug . '/resource' ||
                    $pagename == $this->plugin_slug . '/best-practices-feed') {

                    add_action( 'wp_enqueue_scripts', array(&$this, 'page_template_styles_scripts'), 999);
                    add_filter( 'pll_the_languages', array(&$this, 'bp_language_switcher'), 10, 2 );

                    if ($pagename == $this->plugin_slug) {
                        $template = BP_PLUGIN_PATH . '/template/home.php';
                    } elseif ($pagename == $this->plugin_slug . '/best-practices-feed') {
                        header("Content-Type: text/xml; charset=UTF-8");
                        $template = BP_PLUGIN_PATH . '/template/rss.php';
                    } else {
                        $template = BP_PLUGIN_PATH . '/template/resource.php';
                    }

                    // force status to 200 - OK
                    status_header(200);

                    // redirect to page and finish execution
                    include($template);
                    die();
                }
            }
        }

        function register_sidebars(){
            $args = array(
                'name' => __('Good Practices sidebar', 'bp'),
                'id'   => 'best-practices-home',
                'before_widget' => '<section id="%1$s" class="row-fluid marginbottom25 widget_categories">',
                'after_widget'  => '</section>',
                'before_title'  => '<header class="row-fluid border-bottom marginbottom15"><h1 class="h1-header">',
                'after_title'   => '</h1></header>',
            );
            register_sidebar( $args );

            $args2 = array(
                'name' => __('Good Practices header', 'bp'),
                'id'   => 'best-practices-header',
                'before_widget' => '<section id="%1$s" class="row-fluid widget %2$s">',
                'after_widget'  => '</section>',
                'before_title'  => '<header class="row-fluid border-bottom marginbottom15"><h1 class="h1-header">',
                'after_title'   => '</h1></header>',
            );
            register_sidebar( $args2 );

        }

        function title_tag_sep(){
            return '|';
        }

        function theme_slug_render_title($title) {
            global $wp, $bp_plugin_title;
            $pagename = '';

            // check if request contains plugin slug string
            $pos_slug = strpos($wp->request, $this->plugin_slug);
            if ( $pos_slug !== false ){
                $pagename = substr($wp->request, $pos_slug);
            }

            if ( is_404() && $pos_slug !== false ){
                $bp_config = get_option('bp_config');
                if ( function_exists( 'pll_the_languages' ) ) {
                    $current_lang = pll_current_language();
                    $bp_plugin_title = $bp_config['plugin_title_' . $current_lang];
                }else{
                    $bp_plugin_title = $bp_config['plugin_title'];
                }
                $title['title'] = $bp_plugin_title;
            }

            return $title;
        }

        function theme_slug_render_wp_title($title) {
            global $wp, $bp_plugin_title;
            $pagename = '';
            $sep = ' | ';

            // check if request contains plugin slug string
            $pos_slug = strpos($wp->request, $this->plugin_slug);
            if ( $pos_slug !== false ){
                $pagename = substr($wp->request, $pos_slug);
            }

            if ( is_404() && $pos_slug !== false ){
                $bp_config = get_option('bp_config');

                if ( function_exists( 'pll_the_languages' ) ) {
                    $current_lang = pll_current_language();
                    $bp_plugin_title = $bp_config['plugin_title_' . $current_lang];
                } else {
                    $bp_plugin_title = $bp_config['plugin_title'];
                }

                if ( $bp_plugin_title )
                    $title = $bp_plugin_title . ' | ';
                else
                    $title = '';
            }

            return $title;
        }

        function title_tag_setup() {
            add_theme_support( 'title-tag' );
        }

        function search_form( $form ) {
            global $wp;
            $pagename = $wp->query_vars["pagename"];

            if ($pagename == $this->plugin_slug || $pagename == $this->plugin_slug .'/resource') {
                $form = preg_replace('/action="([^"]*)"(.*)/','action="' . home_url($this->plugin_slug) . '"',$form);
            }

            return $form;
        }

        function page_template_styles_scripts(){
            wp_enqueue_script ('bp-tooltipster', BP_PLUGIN_URL . 'template/js/jquery.tooltipster.min.js');
            wp_enqueue_script ('slick-js', '//cdn.jsdelivr.net/gh/kenwheeler/slick@1.8.1/slick/slick.min.js');
            wp_enqueue_script ('bp', BP_PLUGIN_URL . 'template/js/functions.js', array(), BP_PLUGIN_VERSION);
            wp_enqueue_style ('fontawesome', BP_PLUGIN_URL . 'template/css/font-awesome/css/font-awesome.min.css');
            wp_enqueue_style ('bp-tooltipster', BP_PLUGIN_URL . 'template/css/tooltipster.css');
            wp_enqueue_style ('slick-css', '//cdn.jsdelivr.net/gh/kenwheeler/slick@1.8.1/slick/slick.css');
            wp_enqueue_style ('slick-theme-css', '//cdn.jsdelivr.net/gh/kenwheeler/slick@1.8.1/slick/slick-theme.css');
            wp_enqueue_style ('bp-styles',  BP_PLUGIN_URL . 'template/css/style.css', array(), BP_PLUGIN_VERSION);
        }

        function register_settings(){
            register_setting('bp-settings-group', 'bp_config');
            wp_enqueue_style('bp',  BP_PLUGIN_URL . 'template/css/admin.css');
            wp_enqueue_script('jquery-ui-sortable');
        }

        function google_analytics_code(){
            global $wp;

            $pagename = $wp->query_vars["pagename"];
            $bp_config = get_option('bp_config');

            // check if is defined GA code and pagename starts with plugin slug
            if ($bp_config['google_analytics_code'] != ''
                && strpos($pagename, $this->plugin_slug) === 0){
        ?>

        <script type="text/javascript">
          var _gaq = _gaq || [];
          _gaq.push(['_setAccount', '<?php echo $bp_config['google_analytics_code'] ?>']);
          _gaq.push(['_trackPageview']);

          (function() {
            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
          })();

        </script>

        <?php
            } //endif
        }

        function bp_language_switcher( $output, $args ) {
            if ( defined( 'POLYLANG_VERSION' ) ) {
                $current_language = strtolower(get_bloginfo('language'));
                $site_lang = substr($current_language, 0,2);
                $default_language = pll_default_language();
                $translations = pll_the_languages(array('raw'=>1));

                $output = "<ul>\n";
                foreach ($translations as $key => $value) :
                    if ($site_lang == $key) continue;
                    $search = ($site_lang != $default_language) ? $site_lang.'/'.$this->plugin_slug : $this->plugin_slug;
                    $replace = ($key != $default_language) ? $key.'/'.$this->plugin_slug : $this->plugin_slug;
                    $url = str_replace($search, $replace, $_SERVER['REQUEST_URI']);
                    $output .= "<li class=\"" . $value['classes'][2] . "\"><a href=\"" . $url . "\"><img src=\"" . $value['flag']. "\" title=\"" . $value['name'] . "\" alt=\"" . $value['name'] . "\" /> " . $value['name'] . "</a></li>\n";
                endforeach;
                $output .= "</ul>";
            }

            return $output;
        }

    } // END class Best_Practices_Plugin
} // END if(!class_exists('Best_Practices_Plugin'))

if(class_exists('Best_Practices_Plugin'))
{
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('Best_Practices_Plugin', 'activate'));
    register_deactivation_hook(__FILE__, array('Best_Practices_Plugin', 'deactivate'));

    // instantiate the plugin class
    $wp_plugin_template = new Best_Practices_Plugin();
}

?>
