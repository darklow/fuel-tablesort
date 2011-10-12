<?php
/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2011 Fuel Development Team
 * @link       http://fuelphp.com
 */


namespace TableSort;

/**
 * TableSort class which creates <head> section with all the columns and sorting options html for the <table> tag
 */
class TableSort {

	/**
	 * @var string Default/current sort field
	 */
	public static $sort_by 			= 'id';

	/**
	 * @var string Default/current sort direction
	 */
	public static $direction 		= 'asc';
	
	/**
	 * @var array or string Array of columns - attributes: (sort_key, [column_name], [array attributes])
	 * Use string for column with no sorting
	 */
	public static $columns = array(
		array('id', 'ID', array('class'=>'first')),
		array('name', 'Name', array('class'=>'second'))
	);

	/**
	 * @var array The HTML for the display
	 */
	public static $template = array(
		'wrapper_start'		=> '<thead>',
		'wrapper_end'		=> '</thead>',
		'col_tag'			=> 'th',
		'col_class_active'  => 'active',
		'link_start'  		=> '<a>',
		'link_end'    		=> '</a>',
		'nolink_start'		=> '<span>',
		'nolink_end'		=> '</span>',
	);

	/**
	 * @var string Separator for concatenating sorty_by and direction in URL
	 */
	protected static $uri_delimiter	= '-';
	
	/**
	 * @var	integer	The URI segment containg sorty_by and direction keys
	 */
	protected static $uri_segment = 3;
	
	/**
	 * @var	integer	Optional. The Pagination current page number. Is added at the end of the url, if specified
	 */
	protected static $current_page = 0;

	/**
	 * @var	string	Base url for sorter links
	 */
	protected static $base_url;
	
	/**
	 * @var	boolean	Use cookies to remember current sort_by and direction
	 */
	protected static $use_cookies = true;

	/**
	 * @var	integer	Default sort by order, stored for the reset() method
	 */	
	protected static $default_sort_by;
	
	/**
	 * @var	integer	Default sort direction, stored for the reset() method
	 */	
	protected static $default_direction;
	
	/**
	 * Init
	 *
	 * Loads in the config and sets the variables
	 *
	 * @access	public
	 * @return	void
	 */
	public static function _init()
	{
		$config = \Config::get('tablesort', array());

		static::set_config($config);
	}

	// --------------------------------------------------------------------

	/**
	 * Set Config
	 *
	 * Sets the configuration for tablesort
	 *
	 * @access public
	 * @param array   $config The configuration array
	 * @return void
	 */
	public static function set_config(array $config)
	{
		foreach ($config as $key => $value)
		{
			if ($key == 'template')
			{
				static::$template = array_merge(static::$template, $config['template']);
				continue;
			}
			static::${$key} = $value;
		}
		
		static::initialize();
	}

	// --------------------------------------------------------------------

	/**
	 * Reset variables
	 *
	 * Reset variables to default and delete cookies
	 *
	 * @access public
	 * @param array   $config The configuration array
	 * @return void
	 */
	public static function reset()
	{
		if(static::$use_cookies === true)
		{
			\Cookie::delete('fuel_ts_sort_by');
			\Cookie::delete('fuel_ts_direction');
		}
		
		static::$sort_by 	= static::$default_sort_by;
		static::$direction 	= static::$default_direction;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Prepares vars for creating table head elements
	 *
	 * @access protected
	 * @return void
	 */
	protected static function initialize()
	{
		
		// Set default sort order & so we can use it for reset() function
		static::$default_sort_by 	= static::$sort_by;
		static::$default_direction 	= static::$direction;
		
		$sort_uri = \URI::segment(static::$uri_segment);
		if($sort_uri && strpos($sort_uri, static::$uri_delimiter) !== false)
		{
			$sort_uri = explode(static::$uri_delimiter, $sort_uri);
			static::$sort_by 	= $sort_uri[0];
			static::$direction 	= $sort_uri[1];
			
			if(static::$use_cookies === true)
			{
				$cookie_url = static::$base_url;
				\Cookie::set('fuel_ts_sort_by', static::$sort_by, null, $cookie_url);
				\Cookie::set('fuel_ts_direction', static::$direction, null, $cookie_url);
			}
		}
		elseif (static::$use_cookies === true)
		{
			static::$sort_by 	= \Cookie::get('fuel_ts_sort_by', static::$sort_by);
			static::$direction 	= \Cookie::get('fuel_ts_direction', static::$direction);
		}

	}

	// --------------------------------------------------------------------

	/**
	 * Creates table sort head elements
	 *
	 * @access public
	 * @return mixed Table sort html elements
	 */
	public static function create_table_head()
	{
		
		if (!static::$columns)
		{
			return static::$template['wrapper_start'].'<tr><th>No columns config</th></tr>'.static::$template['wrapper_end'];
		}

		$table_head  = static::$template['wrapper_start'];
		$table_head .= '<tr>';
		
		foreach(static::$columns as $column)
		{
			$sort_key 	= (is_array($column) ? isset($column[1]) ? $column[1] : strtolower($column[0]) : $column);
			$col_attr	= (is_array($column) && isset($column[2]) ? $column[2] : array());
			
			$new_direction = static::$direction;
			
			if(static::$sort_by == $sort_key)
			{
				$active_class_name = static::$template['col_class_active'].' '.static::$template['col_class_active'].'_'.$new_direction;
				if(isset($col_attr['class']))
				{
					$col_attr['class'] .= ' '.$active_class_name;
				}
				else 
				{
					$col_attr['class'] = $active_class_name;
				}
				
				$new_direction = (static::$direction == 'asc' ? 'desc' : 'asc');
				
			}
			
			if(is_array($column) && (!isset($column[1]) || isset($column[1]) && $column[1] !== false)){
				
				$url 			= rtrim(static::$base_url, '/').(static::$current_page ? '/'.static::$current_page : '');
				$url 			.= '/'.$sort_key.static::$uri_delimiter.$new_direction;
				
				$cell_content 	= rtrim(static::$template['link_start'], '> ').' href="'.$url.'">';
				$cell_content 	.= $column[0];
				$cell_content 	.= static::$template['link_end'];
				
			}else{
				if(is_array($column))
				{
					$column = $column[0];
				}
				$cell_content = static::$template['nolink_start'].$column.static::$template['nolink_end'];	
			}
			
			$table_head .= html_tag(static::$template['col_tag'], $col_attr, $cell_content);
			
		}
		
		$table_head .= '</tr>';
		$table_head .= static::$template['wrapper_end'];

		return $table_head;
	}

}


