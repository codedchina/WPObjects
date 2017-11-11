<?php
/**
 * Plugin Name:  LESS CSS
 * Plugin URI:   https://github.com/sanchothefat/wp-less/
 * Description:  Allows you to enqueue <code>.less</code> files and have them automatically compiled whenever a change is detected.
 * Author:       Robert O'Rourke
 * Contributors: Franz Josef Kaiser, Tom Willmot, Rarst
 * Version:      2.1
 * Author URI:   http://interconnectit.com
 * License:      MIT
 */

namespace WPObjects\LessCompiler;

! defined( 'ABSPATH' ) AND exit;

if (!function_exists("WP_Filesystem")) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
}

/**
 * Enables the use of LESS in WordPress
 *
 * See README.md for usage information
 * 
 * WP Filters:
 *      $this->namespace + less_vars
 *      $this->namespace + less_compression
 *      $this->namespace + less_preserve_comments
 *      $this->namespace + less_import_dirs
 *      $this->namespace + less_force_compile
 *      $this->namespace + less_save_css
 *      $this->namespace + wp_less_cache_path
 *      $this->namespace + wp_less_cache_url
 * 
 * WP Option:
 *      $this->namespace + wp_less_always_compile_less
 *      $this->namespace + wp_less_always_compile_less
 *      $this->namespace + wp_less_cached_files
 *
 * @author  Robert "sancho the fat" O'Rourke
 * @link    http://sanchothefat.com/
 * @package WP LESS
 * @license MIT
 * @version 2012-06-13.1701
 */
class WPless {
        /**
         * @static
         * @var    \mdl__wp_less Reusable object instance.
         */
        protected static $instance = null;
        
        protected $namespace = null;


        /**
         * Creates a new instance. Called on 'after_setup_theme'.
         * May be used to access class methods from outside.
         *
         * @see    __construct()
         * @static
         * @return \mdl__wp_less
         */
        public static function instance() {
                null === self:: $instance AND self:: $instance = new self;
                return self:: $instance;
        }


        /**
         * @var array Array store of callable functions used to extend the parser
         */
        public $registered_functions = array();


        /**
         * @var array Array store of function names to be removed from the compiler class
         */
        public $unregistered_functions = array();


        /**
         * @var array Variables to be passed into the compiler
         */
        public $vars = array();


        /**
         * @var string Compression class to use
         */
        public $compression = 'compressed';


        /**
         * @var bool Whether to preserve comments when compiling
         */
        public $preserve_comments = false;


        /**
         * @var array Default import directory paths for Lessc to scan
         */
        public $import_dirs = array();
        

        /**
         * Constructor
         */
        public function __construct($namespace = 'wpobjects_')
        {
                $this->namespace = $namespace;
            
                // every CSS file URL gets passed through this filter
                add_filter( 'style_loader_src', array( $this, 'parse_stylesheet' ), 1000, 2 );

                // editor stylesheet URLs are concatenated and run through this filter
                add_filter( 'mce_css', array( $this, 'parse_editor_stylesheets' ), 1000 );
        }

        /**
         * Lessify the stylesheet and return the href of the compiled file
         *
         * @param  string $src Source URL of the file to be parsed
         * @param  string $handle An identifier for the file used to create the file name in the cache
         * @return string         URL of the compiled stylesheet
         */
        public function parse_stylesheet( $src, $handle ) {

                if (!preg_match('/^'. $this->namespace . '.*/', $handle)) {
                    return $src;
                }
                
                // we only want to handle .less files
                if ( ! preg_match( '/\.less(\.php)?$/', preg_replace( '/\?.*$/', '', $src ) ) ) {
                        return $src;
                }

                // get file path from $src
                if ( ! strstr( $src, '?' ) ) {
                        $src .= '?';
                } // prevent non-existent index warning when using list() & explode()

                // Match the URL schemes between WP_CONTENT_URL and $src,
                // so the str_replace further down will work
                $src_scheme = parse_url( $src, PHP_URL_SCHEME );
                $wp_content_url_scheme = parse_url( WP_CONTENT_URL, PHP_URL_SCHEME );
                if ( $src_scheme != $wp_content_url_scheme ) {
                        $src = set_url_scheme( $src, $wp_content_url_scheme );
                }

                list( $less_path, $query_string ) = explode( '?', str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $src ) );

                $cache = $this->get_cached_file_data( $handle );
                // vars to pass into the compiler - default @themeurl var for image urls etc...
                $this->vars['themeurl'] = '~"' . get_stylesheet_directory_uri() . '"';
                $this->vars['lessurl']  = '~"' . dirname( $src ) . '"';
                $this->vars             = apply_filters( $this->namespace . 'less_vars', $this->vars, $handle );

                // The overall "version" of the LESS file is all it's vars, src etc.
                $less_version           = md5( serialize( array( $this->vars, $src ) ) );

                /**
                 * Give the ability to disable always compiling the LESS with Lessc()
                 * and instead just use the $vars and $version of the LESS file to
                 * dictate whether the LESS should be (re)generated.
                 *
                 * This means we don't need to run everything through the Lessc() compiler
                 * on every page load. The tradeoff is making a change in a LESS file will not
                 * necessarily cause a (re)generation, one would need to bump the $ver param
                 * on wp_enqueue_script() to cause that.
                 */
                if ( ! get_option( $this->namespace . 'wp_less_always_compile_less', true ) ) {
                        if ( ( ! empty( $cache['version'] ) ) && $cache['version'] === $less_version ) {
                                // restore query string it had if any
                                $url = $cache['url'] . ( ! empty( $query_string ) ? "?{$query_string}" : '' );
                                $url = set_url_scheme( $url, $src_scheme );
                                return add_query_arg( 'ver', $less_version, $url );
                        }
                }
                // automatically regenerate files if source's modified time has changed or vars have changed
                try {

                        // initialise the parser
                        $less = new Lessc;

                        // If the cache or root path in it are invalid then regenerate
                        if ( empty( $cache ) || empty( $cache['less']['root'] ) || ! file_exists( $cache['less']['root'] ) ) {
                                $cache = array( 'vars' => $this->vars, 'less' => $less_path );
                        }

                        if ( empty( $cache['url'] ) ) {
                                $cache['url'] = trailingslashit( $this->get_cache_dir( false ) ) . "{$handle}.css";
                        }

                        // less config
                        $less->setFormatter( apply_filters( $this->namespace . 'less_compression', $this->compression ) );
                        $less->setPreserveComments( apply_filters( $this->namespace . 'less_preserve_comments', $this->preserve_comments ) );
                        $less->setVariables( $this->vars );

                        // add directories to scan for imports
                        $import_dirs = apply_filters( $this->namespace . 'less_import_dirs', $this->import_dirs );
                        if ( ! empty( $import_dirs ) ) {
                                foreach ( (array) $import_dirs as $dir ) {
                                        $less->addImportDir( $dir );
                                }
                        }

                        // register and unregister functions
                        foreach ( $this->registered_functions as $name => $callable ) {
                                $less->registerFunction( $name, $callable );
                        }

                        foreach ( $this->unregistered_functions as $name ) {
                                $less->unregisterFunction( $name );
                        }

                        // allow devs to mess around with the less object configuration
                        do_action_ref_array( 'Lessc', array( &$less ) );

                        // $less->cachedCompile only checks for changed file modification times
                        // if using the theme customiser (changed variables not files) then force a compile
                        if ( $this->vars !== $cache['vars'] ) {
                                $force = true;
                        } else {
                                $force = false;
                        }
                        $less_cache = $less->cachedCompile( $cache['less'], apply_filters( $this->namespace . 'less_force_compile', $force ) );

                        if ( empty( $cache ) || empty( $cache['less']['updated'] ) || md5( $less_cache['compiled'] ) !== md5( $cache['less']['compiled'] ) || $this->vars !== $cache['vars'] ) {

                                // output css file name
                                $css_path = trailingslashit( $this->get_cache_dir() ) . "{$handle}.css";

                                $cache = array(
                                        'vars'    => $this->vars,
                                        'url'     => trailingslashit( $this->get_cache_dir( false ) ) . "{$handle}.css",
                                        'version' => $less_version,
                                        'less'    => null
                                );

                                /**
                                 * If the option to not have LESS always compiled is set,
                                 * then we dont store the whole less_cache in the options table as it's
                                 * not needed because we only do a comparison based off $vars and $src
                                 * (which includes the $ver param).
                                 *
                                 * This saves space on the options table for high performance environments.
                                 */
                                if ( get_option( $this->namespace . 'wp_less_always_compile_less', true ) ) {
                                        $cache['less'] = $less_cache;
                                }

                                $this->save_parsed_css( $css_path, $less_cache['compiled'] );
                                $this->update_cached_file_data( $handle, $cache );
                        }
                } catch ( exception $ex ) {
                        wp_die( $ex->getMessage() );
                }

                // restore query string it had if any
                $url = $cache['url'] . ( ! empty( $query_string ) ? "?{$query_string}" : '' );

                // restore original url scheme
                $url = set_url_scheme( $url, $src_scheme );

                if ( get_option( $this->namespace . 'wp_less_always_compile_less', true ) ) {
                        return add_query_arg( 'ver', $less_cache['updated'], $url );
                } else {
                        return add_query_arg( 'ver', $less_version, $url );
                }

        }

        /**
         * Update parsed cache data for this file
         *
         * @param $path
         * @return bool
         */
        public function get_cached_file_data( $path ) {
                $caches = get_option( $this->namespace . 'wp_less_cached_files', array() );

                if ( isset( $caches[ $path ] ) ) {
                        return $caches[ $path ];
                }

                return null;
        }

        public function save_parsed_css( $css_path, $file_contents ) {
                if ( ! apply_filters( $this->namespace . 'less_save_css', $css_path, $file_contents ) ) {
                        return;
                }

                if (!WP_Filesystem()) {
                    return array();
                }

                global $wp_filesystem;
                $wp_filesystem->put_contents($css_path, $file_contents);
        }

        /**
         * Update parsed cache data for this file
         *
         * @param $path
         * @param $file_data
         */
        public function update_cached_file_data( $path, $file_data ) {
                $file_data['less']['compiled'] = '';

                $caches = get_option( $this->namespace . 'wp_less_cached_files', array() );

                $caches[ $path ] = $file_data;

                update_option( $this->namespace . 'wp_less_cached_files', $caches );
        }

        /**
         * Compile editor stylesheets registered via add_editor_style()
         *
         * @param  string $mce_css Comma separated list of CSS file URLs
         * @return string $mce_css New comma separated list of CSS file URLs
         */
        public function parse_editor_stylesheets( $mce_css ) {

                // extract CSS file URLs
                $style_sheets = explode( ",", $mce_css );

                if ( count( $style_sheets ) ) {
                        $compiled_css = array();

                        // loop through editor styles, any .less files will be compiled and the compiled URL returned
                        foreach ( $style_sheets as $style_sheet ) {
                                $compiled_css[] = $this->parse_stylesheet( $style_sheet, $this->url_to_handle( $style_sheet ) );
                        }

                        $mce_css = implode( ",", $compiled_css );
                }

                // return new URLs
                return $mce_css;
        }


        /**
         * Get a nice handle to use for the compiled CSS file name
         *
         * @param  string $url File URL to generate a handle from
         * @return string $url Sanitized string to use for handle
         */
        public function url_to_handle( $url ) {

                $url = parse_url( $url );
                $url = str_replace( '.less', '', basename( $url['path'] ) );
                $url = str_replace( '/', '-', $url );

                return sanitize_key( $url );
        }


        /**
         * Get (and create if unavailable) the compiled CSS cache directory
         *
         * @param  bool $path If true this method returns the cache's system path. Set to false to return the cache URL
         * @return string $dir  The system path or URL of the cache folder
         */
        public function get_cache_dir( $path = true ) {

                // get path and url info
                $upload_dir = wp_upload_dir();

                if ( $path ) {
                        $dir = apply_filters( $this->namespace . 'wp_less_cache_path', path_join( $upload_dir['basedir'], $this->namespace . 'less-cache' ) );
                        // create folder if it doesn't exist yet
                        if ( ! file_exists( $dir ) ) {
                                wp_mkdir_p( $dir );
                        }
                } else {
                        $dir = apply_filters( $this->namespace . 'wp_less_cache_url', path_join( $upload_dir['baseurl'], $this->namespace . 'less-cache' ) );
                }

                return rtrim( $dir, '/' );
        }


        /**
         * Escape a string that has non alpha numeric characters variable for use within .less stylesheets
         *
         * @param  string $str The string to escape
         * @return string $str String ready for passing into the compiler
         */
        public function sanitize_string( $str ) {

                return '~"' . $str . '"';
        }


        /**
         * Adds an interface to register Lessc functions. See the documentation
         * for details: http://leafo.net/lessphp/docs/#custom_functions
         *
         * @param  string $name The name for function used in the less file eg. 'makebluer'
         * @param  string $callable (callback) Callable method or function that returns a Lessc variable
         * @return void
         */
        public function register( $name, $callable ) {
                $this->registered_functions[ $name ] = $callable;
        }

        /**
         * Unregisters a function
         *
         * @param  string $name The function name to unregister
         * @return void
         */
        public function unregister( $name ) {
                $this->unregistered_functions[ $name ] = $name;
        }


        /**
         * Add less var prior to compiling
         *
         * @param  string $name The variable name
         * @param  string $value The value for the variable as a string
         * @return void
         */
        public function add_var( $name, $value ) {
                if ( is_string( $name ) ) {
                        $this->vars[ $name ] = $value;
                }
        }

        /**
         * Removes a less var
         *
         * @param  string $name Name of the variable to remove
         * @return void
         */
        public function remove_var( $name ) {
                if ( isset( $this->vars[ $name ] ) ) {
                        unset( $this->vars[ $name ] );
                }
        }
} // END class

