<?php
/**
 * Database class to emulate Moodle 2.x style db queries, for backwards
 * compatibility of 2.x code running on a 1.x Moodle.
 *
 * @copyright &copy; 2009-2010 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */
class moodle2_db_emulator {

    // TODO systematically compare the API of both DBs and add ALL the necessary rules
    // TODO cases of $params in *_sql() functions need have their values parsed back in

    /**
     * A PHP magic method that matches on all Moodle 2.x style db method calls
     * and converts them back to the Moodle 1.x functional syntax before
     * executing them.
     *
     * @param string $method The name of the method being called.
     * @param array $params The array of parameters passed to the method.
     * @return mixed The result of the query.
     */
    function __call($method, $params) {

        // if this is a raw SQL query then we need to add the table prefixes
        if(preg_match('/_sql$/', $method)) {
            $params = array_map(array($this, 'addprefix'), $params);
        }

        // handle differences in function params
        switch($method) {
            case 'record_exists_sql' :
            case 'count_records_sql' :
            case 'get_records_sql' :
                //unset($params[1]);
                break;

            case 'get_field_sql' :
                unset($params[1]);
                unset($params[2]);
                break;

            case 'get_record_select' :
            case 'get_records_select' :
            case 'delete_records_select' :
            case 'update_record' :
                unset($params[2]);
                break;

            case 'insert_record' :
                unset($params[3]);
                break;

            case 'get_record' :
            case 'get_records' :
            case 'record_exists':
            case 'delete_records' :
            case 'set_field' :
            case 'get_field' :
            case 'count_records' :
                $params = $this->flatten($params);

        }
        
        // execute the query and return the sanatised result
        return ilp_db::encode(call_user_func_array($method, $params));
    }

    /**
     * Adds the tablename prefix needed by 1.x to all tables.
     *
     * @param string $query The sql to be executed.
     */
    private function addprefix($query) {
        global $CFG;
        // replace 2.x syntax {tablename} with 1.x syntax prefix_tablename.
        return preg_replace(array('/{/', '/}/'), array($CFG->prefix, ''), $query);
    }

    /**
     * Converts a named array into multiple sequential params
     *
     * @param string $query The sql to be executed.
     */
    private function flatten($params) {
        $flat = array();
        foreach($params as $param) {
            if(is_array($param)) {
                if(count($param) > 3) {
                    print_error('toomanykeyvalues', 'block_ilp');
                }
                foreach($param as $key => $value) {
                    $flat[] = $key;
                    $flat[] = $value;
                }
            } else {
                $flat[] = $param;
            }
        }
        return $flat;
    }

    /**
     * Fetch an instance of the database manager emulator so we can do upgrades
     * and install evidence resource plugins.
     *
     */
    public function get_manager() {
        return new moodle2_database_manager_emulator();
    }
    
    public function sql_like($fieldname,$param) {
    	global $CFG;
        $LIKE	= ($CFG->dbfamily == 'postgres') ? 'ILIKE'	:	'LIKE';
        
    	return " {$fieldname} {$LIKE} {$param} ";
    }
}


/*********
 * Class to emulate some of Moodle 2.x navbar functionality
 * 
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */
class navbar {
	
	public $navitems;
	public	$test;

	/*
	 * Constructor. simply sets navbar to be an array
	 */
	public function __construct() {
		$this->navitems  = array();
	}

	/*
	 * add. adds a new item to navitems
     *
     * @param string $text
     * @param moodle_url|action_link $action
     * @param int $type
     * @param string $shorttext
     * @param string|int $key
     * @param pix_icon $icon
     * @return navigation_node
     */
	function add($text, $action=null,$type= null, $shorttext=null, $key=null, $icon=null) {
		array_push($this->navitems, array('name' => $text, 'link' => $action, 'type' => $type));
	}
	
	/*
	 * get_items. returns navitems array
	 */
	function get_items() {
		return $this->navitems;
	}
}


/**
 * Renderer class to emulate Moodle 2.x style $OUTPUT functions
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */
class moodle2_renderer_emulator {

    public $requires;
    public $navbar;
    public	$context;

    /**
     * Constructor. Calls the $PAGE->requires replacement so that libraries for JS are available
     */
    public function __construct() {
       $this->requires 	= new moodle2_page_requires();
       $this->navbar	= new navbar();
       
    }

    /**
     * Start output by sending the HTTP headers, and printing the HTML <head>
     * and the start of the <body>.
     *
     * To control what is printed, you should set properties on $PAGE. If you
     * are familiar with the old {@link print_header()} function from Moodle 1.9
     * you will find that there are properties on $PAGE that correspond to most
     * of the old parameters to could be passed to print_header.
     *
     * Not that, in due course, the remaining $navigation, $menu parameters here
     * will be replaced by more properties of $PAGE, but that is still to do.
     *
     * @return string HTML that you must output this, preferably immediately.
     */
    function header() {
        global $PAGE;
        
        // render the page header
        return print_header_simple($PAGE->title, '', $this->navbar(), '', $this->requires->meta);
    }

    /**
     * Outputs a heading
     * @param string $text The text of the heading
     * @param int $level The level of importance of the heading. Defaulting to 2
     * @param string $classes A space-separated list of CSS classes
     * @return string the HTML to output.
     */
    function heading($text, $level=2, $classes='main') {
        return print_heading($text, '', $level, $classes);
    }

    /**
     * Outputs the page's footer
     * @return string HTML fragment
     */
    function footer($course=null, $usercourse=null, $return=true) {
        return print_footer($course, $usercourse, $return);
    }
    
    /********
     * Sets the tile for a page
     */
    function set_title($title) {
    	$this->title = $title;
    }
    
    /*******
     * Sets the 
     */
    function navbar() {
		$breadcrumbs	=	$this->navbar->get_items();
    	
    	// determine the total length of all the breadcrumbs
    	$length = 0;
    	foreach($breadcrumbs as $crumb) {
        	$length += strlen($crumb['name']);
    	}

	    // if it too long then we need to truncate
	    if($length > ILP_MAXLENGTH_BREADCRUMB) {
	        // calculate the per crumb limit
	        $limit = round(ILP_MAXLENGTH_BREADCRUMB/count($breadcrumbs));
	        // enforce it
	        foreach($breadcrumbs as $id => $crumb) {
	            $breadcrumbs[$id]['name'] = ilp_limit_length($crumb['name'], $limit);
	        }
	    }
	
	    return build_navigation($breadcrumbs);
    }
    

    /**
     * Generates a simple html link in a pop up window. Acts as wrapper so that
     * links will appear as popups rather than the usual action where they don't
     *
     * All parameters except $url are optional
     * @param string $url Web link. Either relative to $CFG->wwwroot, or a full URL.
     * @param string $name Name to be assigned to the popup window (this is used by
     *   client-side scripts to "talk" to the popup window)
     * @param string $linkname Text to be displayed as web link
     * @param int $height Height to assign to popup window
     * @param int $width Height to assign to popup window
     * @param string $title Text to be displayed as popup page title
     * @param string $options List of additional options for popup window
     * @param bool $return If true, return as a string, otherwise print
     */
//    function link($url, $name=null, $linkname=null, $height=400, $width=500, $title=null, $options=null, $return=true) {
//        return link_to_popup_window($url, $name, $linkname, $height, $width, $title, $options, $return);
//    }

    /**
     * Wrapper for print_textarea, which prints a basic textarea field.
     *
     * @param $usehtmleditor
     * @param $rows
     * @param $cols
     * @param $width
     * @param $height
     * @param $name
     * @param $value
     * @param $course_id
     * @param $return
     * @param $id
     */
    function textarea($usehtmleditor, $rows, $cols, $width, $height, $name, $value='', $course_id=0, $return=true, $id) {
        return print_textarea($usehtmleditor, $rows, $cols, $width, $height, $name, $value, $course_id, $return, $id);
    }

    /**
     *
     * @param string $heading the main heading that should be displayed at the top of the <body>.
     */
    function set_heading() {
        // do nothing :-)
    }
    
    
     /**
     *
     * @param string $heading the main heading that should be displayed at the top of the <body>.
     */
    function set_pagelayout() {
    	   // do nothing :-)
    }
    
    /**
     *
     * @param string $heading the main heading that should be displayed at the top of the <body>.
     */
    function set_pagetype() {
    	   // do nothing :-)
    }
    
    
    
    /**
     * Return the moodle_url for an image.
     * The exact image location and extension is determined
     * automatically by searching for gif|png|jpg|jpeg, please
     * note there can not be diferent images with the different
     * extension. The imagename is for historical reasons
     * a relative path name, it may be changed later for core
     * images. It is recommended to not use subdirectories
     * in plugin and theme pix directories.
     *
     * There are three types of images:
     * 1/ theme images  - stored in theme/mytheme/pix/,
     *                    use component 'theme'
     * 2/ core images   - stored in /pix/,
     *                    overridden via theme/mytheme/pix_core/
     * 3/ plugin images - stored in mod/mymodule/pix,
     *                    overridden via theme/mytheme/pix_plugins/mod/mymodule/,
     *                    example: pix_url('comment', 'mod_glossary')
     * The above is the original 2.0 Docs (slightly altered)
     *
     * @param string $icon the name of the image
     * @return string the icon URL
     */
    function pix_url($icon) {
        global $CFG;

        return "{$CFG->pixpath}/{$icon}.gif";
    }

    /**
     * Returns a form with a single select widget.
     *
     * @param moodle_url $url form action target, includes hidden fields
     * @param string $name name of selection field - the changing parameter in url
     * @param array $options list of options
     * @param string $selected selected element
     * @param array $nothing
     * @param string $formid
     * @return string HTML fragment
     */
    function single_select($url, $name, array $options, $selected='', $nothing=array(''=>'choosedots'), $formid=null) {
        return popup_form("{$url}&amp;{$name}=", $options, $name, $selected, current($nothing), null, null, true);
    }

    /**
     * You should call this method from every page to set the cleaned-up URL
     * that should be used to return to this page. Used, for example, by the
     * blocks editing UI to know where to return the user after an action.
     * For example, course/view.php does:
     *      $id = optional_param('id', 0, PARAM_INT);
     *      $PAGE->set_url('/course/view.php', array('id' => $id));
     *
     * @param moodle_url|string $url URL relative to $CFG->wwwroot or {@link moodle_url} instance
     * @param array $params paramters to add to the URL
     * @return void
     */
    public function set_url($url, array $params = null) {
        // do nothing for now, in future we may want to simulate this functionality
        // so we can better handle page redirects
    }

    /**
     * This method is called in moodle 2.0 to set the context of the current page. We will save the context to
     * the class context var which we can access later to find out the current context 
     */
    public function set_context($object) {

    	$this->context	=	$object;
    }
    
    /**
     * This method emulates the 
     * @param user $user
     */
    public function user_picture($user,$options)	{
    	//if the course is not empty pass the course id
    	$course_id 	= (isset($options['courseid'])) ? $options['courseid']	 : null;
    	$size 		= (isset($options['size'])) ? $options['size']	 		 : 0;
    	$return 	= (isset($options['return'])) ? $options['return']	 	 : false;
    	$link 		= (isset($options['link'])) ? $options['link']	 		 : true;
    	$target		= (isset($options['target'])) ? $options['target']	 	 : '';
    	$alttext	= (isset($options['alttext'])) ? $options['alttext']	 : true;
    	
    	return print_user_picture($user, $course_id, null, $size, $return, $link, $target,$alttext);
    }
    
}


/**
 * Class to emulate Moodle 2.x style $DB functions
 *
 * @copyright &copy; 2009-2010 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */
class moodle2_database_manager_emulator {
    /**
     * A PHP magic method that converts new style OO function calls into the old
     * style ddllib procedural functions.
     *
     * @param string $method The name of the method being called.
     * @param array $params The array of parameters passed to the method.
     * @return mixed The result of the query.
     */
    function __call($method, $params) {
        global $CFG;

        // include the xmldb classes
        require_once($CFG->libdir.'/ddllib.php');
       
        // execute the query and return the result
        return call_user_func_array($method, $params);
    }
}

/**
 * Dynamic resource loading class. Emulates $PAGE->requires()
 *
 * @copyright &copy; 2009-2010 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */
class moodle2_page_requires {

    public $meta;

    /**
     * Import core YUI javascript files into the page header.
     * @param string $scripts The script name, according with http://developer.yahoo.com/yui/yuiloader/#modulenames
     */
    function yui2_lib($scripts) {
        // strip yui2- and yui3- (if any)
        $scripts = str_replace("yui2-", "", $scripts);
        $scripts = str_replace("yui3-", "", $scripts);

        // the "event" file is different in moodle 1
        if ($scripts == "event") {
            $scripts = "dom-event";
        }
        // the "stylesheet" file doesn't exist in moodle 1
        if ($scripts == "stylesheet") {
            // do not load (the style will loaded throught the style.php)
            return;
        } else {
        	
            // check if it contains the "yui_" prefix
            if (strpos($scripts, "yui_") === false) {
                $scripts = "yui_".$scripts;
            }

            // load
            require_js($scripts);
        }
    }

    /**
     * Create variable data for javascript scripts.
     *
     * THIS IS DEPRECATED and IT SHOULD NOT BE CALLED DIRECTLY:
     * USE js_init_call INSTEAD
     *
     * @param string $var_name The name of the js variable
     * @param string $var_data The content of the variable
     */
    function data_for_js($var_name, $var_data) {
        global $CFG;

        // remove break lines
        $var_data = str_replace("\r", "", $var_data);
        $var_data = str_replace("\n", "", $var_data);
        $var_data = str_replace("\t", "", $var_data);

        // add slashes were needed
        $var_data = str_replace("'", "\'", $var_data);
        echo "<script type='text/javascript'>\n//<![CDATA[\n";

            if (!strpos($var_name, '.')) {
                echo 'var ';
            }
        echo $var_name." = ".json_encode($var_data)."; \n//]]>\n</script>";

    }

    /**
     * Import custom javascript files into the page header.
     *
     * THIS IS DEPRECATED and IT SHOULD NOT BE CALLED DIRECTLY:
     * USE js_init_call INSTEAD
     *
     * TODO: add the "inhead" bool param
     *
     * @param  string $src The full file path to the javascript file source
     * @return void;
     */
    function js($src) {
        global $CFG;
        require_js($CFG->wwwroot.$src);
    }

    /**
     * This is the proper way to add a javascript module (object attached to a namespace), then call
     * some init function.
     *
     * @param string $function the name of the js function to call (no brackets)
     * @param array $jsarguments arguments to be passed to the function
     * @param bool $onDOMready
     * @param array $module details of the javascript module to create, (name, path, requires), i.e.
     * what name to use to extend M.assmgr(.xxxx), the path to the file, and any yui2 lib files required.
     * check ajax_get_lib() in /lib/ajaxlib/ to see a list of the shorthand names for the yui libs
     * The module namespace will be created even if it's not used in the file
     */
    function js_init_call($function , array $jsarguments = null, $onDOMready = true, array $module) {

        global $CFG;

        // yui dependencies
        if(array_key_exists('requires', $module)) {
            for($i = 0; $i < count($module['requires']); $i++){
                $this->yui2_lib($module['requires'][$i]);
            }
        }

        // function and namespaces
        $namespaces = explode(".", $function);

        // scripts
        if(array_key_exists('fullpath', $module)) {

            if (count($namespaces) > 1) {
                // initialise all parts of the function namespace e.g. M and
                // M.blocks_assmgr so that they exist as namespaces if they don't already
                echo "<script type='text/javascript'>
                        //<![CDATA[
                        ";

                echo "if(!{$namespaces[0]}) {
                        var {$namespaces[0]} = {};
                    }
                    if (!{$namespaces[0]}.{$namespaces[1]}) {
                        {$namespaces[0]}.{$namespaces[1]} = {};
                    }";

                if (count($namespaces) > 2) {
                   echo "if (!{$namespaces[0]}.{$namespaces[1]}.{$namespaces[2]}) {
                            {$namespaces[0]}.{$namespaces[1]}.{$namespaces[2]} = {};
                         }";

                }

                echo "//]]>
                  </script>";
            }

            // require script
            $this->js($module['fullpath']);

            // create script params
            if (is_array($jsarguments)) {
            	$params = array_map('json_encode', $jsarguments);
            	$params = implode(', ', $params);
            }
            
            $params	= (!empty($params)) ? ', '.$params: '';
            
            // execute it
            if($onDOMready){
                echo "<script type='text/javascript'>
                        //<![CDATA[
                        function {$module['name']}_".str_replace(".", "_", $function)."() {
                            $function(M $params );
                        }
                        YAHOO.util.Event.onDOMReady({$module['name']}_".str_replace(".", "_", $function).");
                        //]]>
                    </script>";
            } else {
                 echo "<script type='text/javascript'>
                        //<![CDATA[
                        M.blocks_assmgr.init(M $params );
                        //]]>
                    </script>";
            }
        }
    }


    /**
     * Import a custom stylesheet into the page header.
     * @param moodle_url  $url The stylesheet url
     */
    function css_theme(moodle_url $url) {
        // create the string url
        $css_url = $url->scheme."://".$url->host.$url->path;

        // check if any parameters
        if(count($url->params) > 0){
            $first = true;
            foreach($url->params as $param => $value){
                if($first) {
                    $css_url .= "?".$param."=".$value;
                    $first = false;
                } else {
                    $css_url .= "&amp;".$param."=".$value;
                }
            }
        }

        // add meta (it will be inside HEAD tag)
        $this->meta .= '<link rel="stylesheet" type="text/css" href="'.$css_url.'" />';
    }

    /**
     * Import custom data (<style>, <script>, ...)into the page header.
     *
     * This is to add some functionality to Moodle 1.9x that have been developed only for Moodle 2.x
     * @param string $custom_header The custom header data
     */
    function ilp_add_custom_header($custom_header) {
        $this->meta .= $custom_header;
    }

    /**
     * Add short static javascript code fragment to page footer.
     * This is intended primarily for loading of js modules and initialising page layout.
     * Ideally the JS code fragment should be stored in plugin renderer so that themes
     * may override it.
     *
     * Note: 1.9 emulator mode (here) just outputs the code where it's called.
     *
     * @param string $jscode
     * @param bool $ondomready wait for dom ready (helps with some IE problems when modifying DOM)
     * @param array $module JS module specification array
     * @return void
     */
    public function js_init_code($jscode, $ondomready = false, array $module = null) {

        $jscode = trim($jscode, " ;\n"). ';';

//        if ($module) {
//            $this->js_module($module);
//            $modulename = $module['name'];
//            $jscode = "Y.use('$modulename', function(Y) { $jscode });";
//        }
        echo "<script type='text/javascript'>\n//<![CDATA[\n";

        if ($ondomready) {
            echo "YAHOO.util.Event.onDOMReady(function() { $jscode });";
        } else {
            echo $jscode;
        }

        echo "\n//]]>\n</script>";
    }
}

/**
 *
 * provides static functions
 */
class js_writer {

    /**
     * Returns code setting value to variable
     * @param string $name
     * @param mixed $value json serialised value
     * @param bool $usevar add var definition, ignored for nested properties
     * @return string JS code fragment
     */
    public function set_variable($name, $value, $usevar=true) {

        $output = '';

        if (!strpos($name, '.')) {
            if ($usevar) {
                $output .= 'var ';

            }
        } else {
            $bits = explode('.', $name);
            // initialise the start of the variable object if it's not there
            $output .= 'if (typeof('.$bits[0].') == \'undefined\') {'.
                            $bits[0].' = {};'.
                        '}';
            if (count($bits) > 2) {
                $output .= 'if (typeof('.$bits[0].'.'.$bits[1].') == \'undefined\') {'.
                                $bits[0].'.'.$bits[1].' = {};'.
                            '}';
            }


        }

        $output .= " $name = ".json_encode($value).";";

        return $output;
    }
}


global $DB, $OUTPUT, $PAGE;

// if this is empty then we're using Moodle 1.9.x, so we need the 2.0 emulator
if(empty($DB)) {
    $DB = new moodle2_db_emulator();
}

if (!$PAGE || !($PAGE->type == 'course-view')) {
    // Same for this one - we need these to make Moodle 1.9 look like Moodle 2.0
    if(empty($OUTPUT)) {
        $PAGE = $OUTPUT = new moodle2_renderer_emulator();
    }

    // in some function $PAGE is defined also in Moodle 1.9.x
    if(empty($PAGE)) {
        $PAGE =& $OUTPUT;
    }
}
?>