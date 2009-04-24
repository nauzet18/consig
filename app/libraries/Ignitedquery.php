<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * Created on 2008 Jun 26
 * by Martin Wernstahl <m4rw3r@gmail.com>
 */
/*
 * Copyright (c) 2008, Martin Wernstahl
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * The name of Martin Wernstahl may not be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY Martin Wernstahl ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL Martin Wernstahl BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * @package IgnitedRecord
 * @subpackage IgnitedQuery
 * @author Martin Wernstahl <m4rw3r@gmail.com>
 * @copyright Copyright (c) 2008, Martin Wernstahl <m4rw3r@gmail.com>
 */

/**
 * A SQL query builder / executor.
 * 
 * @package IgnitedRecord
 * @subpackage IgnitedQuery
 * @author Martin Wernstahl <m4rw3r@gmail.com>
 * @copyright Copyright (c) 2008, Martin Wernstahl <m4rw3r@gmail.com>
 */
class IgnitedQuery{
	
	// =============================
	// = ========== DATA ========= =
	// =============================
	
	/**
	 * @var string|array
	 */
	var $q_select = '*';
	/**
	 * @var bool
	 */
	var $q_distinct = false;
	/**
	 * @var array
	 */
	var $q_from = array();
	/**
	 * @var array
	 */
	var $q_join = array();
	/**
	 * @var array
	 */
	var $q_where = array();
	/**
	 * @var array
	 */
	var $q_having = array();
	/**
	 * @var array
	 */
	var $q_group_by = array();
	/**
	 * @var array
	 */
	var $q_order_by = array();
	/**
	 * @var int|false
	 */
	var $q_offset = false;
	/**
	 * @var int|false
	 */
	var $q_limit = false;
	
	/**
	 * The alias of this subquery (if this is one).
	 * 
	 * If set to false, the alias cannot be set
	 * (that setting is used for subqueries which shouldn't have aliases)
	 * 
	 * @access private
	 * @var string
	 */
	var $q_as = null;
	
	/**
	 * The data used by insert() and update().
	 * 
	 * @access private
	 * @var array
	 */
	var $q_set_data = array();
	
	/**
	 * Stores the data for the ActiveRecord compatible query cache.
	 * 
	 * @access private
	 * @var array
	 */
	var $q_ar_cache = array();
	
	/**
	 * The database prefix, fetched from the DB class.
	 * 
	 * @access private
	 * @var string
	 */
	var $q_prefix = '';
	
	/**
	 * Contains an array of all tablenames to prefix.
	 * 
	 * Used when building the query.
	 * 
	 * @access private
	 * @var array
	 */
	var $q_to_prefix = array();
	
	/**
	 * Stores the parent object, to be able to use end() in chaining
	 *
	 * @access private
	 * @var IgnitedQuery
	 */
	var $q_parent = null;
	
	// =============================
	// = ====== OBJECT STATE ===== =
	// =============================
	
	/**
	 * Determines if the status of the object has changed.
	 *
	 * @access private
	 * @var bool
	 */
	var $q_cached = false;
	
	/**
	 * Caching of built subqueries
	 * 
	 * @access private
	 * @var string
	 */
	var $q_cache = null;
	
	/**
	 * The AUTO INCREMENT id of the last insert.
	 * 
	 * @access private
	 * @var int
	 */
	var $q_insert_id = false;
	
	/**
	 * The number of affected rows of the last UPDATE/DELETE query.
	 * 
	 * @access private
	 * @var int
	 */
	var $q_affected_rows = false;
	
	// =============================
	// = ========= FLAGS ========= =
	// =============================
	
	/**
	 * Determines if only the WHERE part of the query should be retuned by _build_get_query().
	 * 
	 * @access private
	 * @var bool
	 */
	var $q_only_where = false;
	
	// --------------------------------------------------------------------
		
	function IgnitedQuery()
	{
		$CI =& get_instance();
		$this->q_prefix = $CI->db->dbprefix;
		$this->q_dbdriver = $CI->db->dbdriver;
		
		// reset the caching
		$this->flush_cache();
	}
	
	// =============================
	// = == OBJECT DATA METHODS == =
	// =============================
	
	/**
	 * Resets the state of this object.
	 *
	 * Also loads the ActiveRecord compatible cache if it is switched on.
	 *
	 * @access public
	 * @return void
	 */
	function reset()
	{
		if($this->q_ar_cache['on'] == true)
		{
			// merge with what we have and save it to the cache
			$this->q_ar_cache['end'] = array_merge($this->q_ar_cache['end'], $this->save_cache());
		}
		
		unset($this->q_to_prefix);
		
		$this->q_select = '*';
		$this->q_distinct = false;
		$this->q_from = array();
		$this->q_join = array();
		$this->q_where = array();
		$this->q_having = array();
		$this->q_group_by = array();
		$this->q_order_by = array();
		$this->q_offset = false;
		$this->q_limit = false;
		$this->q_as = null;
		$this->q_only_where = false;
		$this->q_set_data = array();
		$this->q_to_prefix = array();
		
		// update cache
		if($this->q_ar_cache['on'] == true)
		{
			$this->q_ar_cache['end'] = $this->array_diff_assoc_values($this->q_ar_cache['end'], $this->q_ar_cache['start']);
			$this->q_ar_cache['start'] = array();
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Saves the state of this object.
	 * 
	 * @access public
	 * @return array
	 */
	function save_cache()
	{
		$cache = array();
		
		foreach(array('q_select', 'q_distinct', 'q_from', 'q_join', 'q_where', 'q_group_by', 'q_having', 'q_order_by', 'q_offset', 'q_limit', 'q_as') as $prop)
		{
			$cache[$prop] = $this->$prop;
		}
		
		return $cache;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Loads the state of this object.
	 * 
	 * @access public
	 * @param array
	 * @return void
	 */
	function load_cache($data)
	{
		$this->reset();
		
		foreach(array('q_select', 'q_distinct', 'q_from', 'q_join', 'q_where', 'q_group_by', 'q_having', 'q_order_by', 'q_offset', 'q_limit', 'q_as') as $prop)
		{
			if(isset($data[$prop]))
				$this->$prop = $data[$prop];
		}
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Start Cache
	 *
	 * Starts ActiveRecord compatible caching
	 *
	 * @access	public
	 * @return	$this
	 */
	function &start_cache()
	{
		$this->q_ar_cache['start'] = $this->save_cache();
		$this->q_ar_cache['on'] = true;
		
		return $this;
	}
	
	
	// --------------------------------------------------------------------
		
	/**
	 * Stop Cache
	 *
	 * Stops ActiveRecord compatible caching
	 *
	 * @access	public
	 * @return	$this
	 */
	function &stop_cache()
	{
		$this->q_ar_cache['end'] = $this->save_cache();
		$this->q_ar_cache['on'] = false;
		
		return $this;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Flush Cache
	 *
	 * Empties the ActiveRecord compatible cache
	 *
	 * @access	public
	 * @return	$this
	 */
	function &flush_cache()
	{
		$this->q_ar_cache['start'] = array();
		$this->q_ar_cache['end'] = array();
		$this->q_ar_cache['on'] = false;
		
		return $this;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Loads the ActiveRecord compatible cache, if caching is on.
	 * 
	 * @access private
	 * @return void
	 */
	function load_ar_cache()
	{
		if(empty($this->q_ar_cache['end']))
		{
			return;
		}
		$ar_cache = $this->array_diff_assoc_values($this->q_ar_cache['end'], $this->save_cache());
		
		foreach($ar_cache as $prop => $value)
		{
			if(is_array($value) && is_array($this->$prop))
				$this->$prop = array_merge($value, $this->$prop);
			elseif(empty($this->$prop) OR $this->$prop == '*')
				$this->$prop = $value;
		}
	}
	
	// =============================
	// = ========= SELECT ======== =
	// =============================
	
	/**
	 * Specifies the SELECT part of the query.
	 * 
	 * @access public
	 * @param mixed		The column(s) to be selected, can be an IgnitedQuery
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param bool		If to protect identifiers
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &select($cols = false, $protect_identifiers = true)
	{
		$this->q_cached = false;
		
		if($cols === false)
		{
			$obj = new IgnitedQuery();
			$obj->q_parent =& $this;
			$this->q_select[] =& $obj;
			return $obj;
		}
		
		// reset select if it is the default "*"
		if(is_string($this->q_select))
		{
			$this->q_select = array();
		}
		
		if(is_array($cols))
		{
			// many columns in array
			if($protect_identifiers)
				$cols = array_map(array(&$this, '_protect_identifiers'), $cols);
			
			$this->q_select = array_merge($this->q_select, $cols);
		}
		elseif(is_object($cols) && is_a($cols,'IgnitedQuery'))
		{
			// subquery
			$this->q_select[] =& $cols;
		}
		else
		{
			if($protect_identifiers)
				$cols = $this->_protect_identifiers($cols);
			
			$this->q_select[] = $cols;
		}
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies a MAX select function.
	 * 
	 * @access public
	 * @param string	The column to be selected, can be an IgnitedQuery
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param string	The alias of the query, if empty, alias will be $col
	 * @param bool		If to protect identifiers
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &select_max($col = false, $alias = '', $protect_identifiers = true)
	{
		return $this->_select_func($col, $alias, 'MAX', $protect_identifiers);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies a MIN select function.
	 * 
	 * @access public
	 * @param string	The column to be selected, can be an IgnitedQuery
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param string	The alias of the query, if empty, alias will be $col
	 * @param bool		 If to protect identifiers
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &select_min($col = false,$alias = '', $protect_identifiers = true)
	{
		return $this->_select_func($col, $alias, 'MIN', $protect_identifiers);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies an AVG select function.
	 * 
	 * @access public
	 * @param string	The column to be selected, can be an IgnitedQuery
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param string	The alias of the query, if empty, alias will be $col
	 * @param bool		If to protect identifiers
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &select_avg($col = false,$alias = '', $protect_identifiers = true)
	{
		return $this->_select_func($col, $alias, 'AVG', $protect_identifiers);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies a SUM select function.
	 * 
	 * @access public
	 * @param string	The column to be selected, can be an IgnitedQuery
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param string	The alias of the query, if empty, alias will be $col
	 * @param bool		If to protect identifiers
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &select_sum($col = false,$alias = '', $protect_identifiers = true)
	{
		return $this->_select_func($col, $alias, 'SUM', $protect_identifiers);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies a COUNT select function.
	 * 
	 * @access public
	 * @param string	The column to be selected, can be an IgnitedQuery
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param string 	The alias of the query, if empty, alias will be $col
	 * @param bool		If to protect identifiers
	 * 
	 * @return $this or a new IgnitedQuery
	 */	
	function &select_count($col = false, $alias = '', $protect_identifiers = true)
	{
		return $this->_select_func($col, $alias, 'COUNT', $protect_identifiers);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies a select function (eg. MIN, MAX, AVG).
	 * 
	 * @access private
	 * @param string	The column to be selected, can be an IgnitedQuery
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param string 	The alias of the query, if empty, alias will be $col
	 * @param string 	The function to use
	 * @param bool		If to protect identifiers
	 * 
	 * @return $this or a new IgnitedQuery
	 * 
	 * @todo Check the subquery for SELECT FUNC(foo) AS bar
	 */
	function &_select_func($col, $alias, $func, $protect_identifiers)
	{
		$this->q_cached = false;
		
		if(is_string($this->q_select))
		{
			$this->q_select = array();
		}
		
		$this->q_select[] = $func;
		
		if($col == false)
		{
			$obj = new IgnitedQuery();
			$obj->q_as = is_empty($alias) ? false : $alias;
			$obj->q_parent =& $this;
			$this->q_select[] =& $obj;
			return $obj;
		}
		
		if(is_object($col) && is_a($col, 'IgnitedQuery'))
		{
			$col->q_as = is_empty($alias) ? false : $alias;
			$this->q_select[] =& $col;
			
			return $this;
		}
		
		if($protect_identifiers)
			$col = $this->_protect_identifiers($col);
		
		$this->q_select[] = '('.$col.') AS '.
						  ($alias != ''
						  	? $this->_protect_identifiers($alias)
						  	: $this->_protect_identifiers($col)
						  );
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Sets the flag for the use of the DISTINCT modifier.
	 * 
	 * @param bool The state of the flag
	 *
	 * @return $this
	 */
	function &distinct($val = true)
	{
		$this->q_cached = false;
		
		$this->q_distinct = $val;
		return $this;
	}
	
	// =============================
	// = ========== FROM ========= =
	// =============================
	
	/**
	 * Specifies the FROM part of the query.
	 * 
	 * @access public
	 * @param mixed The table(s) to be selected, can be an IgnitedQuery
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &from($tables = false)
	{
		$this->q_cached = false;
		
		if($tables === false)
		{
			$obj = new IgnitedQuery();
			$obj->q_parent =& $this;
			$this->q_from[] =& $obj;
			return $obj;
		}
		
		if(is_array($tables))
		{
			// list of tables, or table => alias
			$this->q_from = array_merge($this->q_from, $tables);
		}
		elseif(is_object($tables) && is_a($tables,'IgnitedQuery'))
		{
			// subquery
			$this->q_from[] =& $tables;
		}
		elseif(strpos($tables, ','))
		{
			$this->from(array_map('trim', explode(',', $tables)));
		}
		elseif(strpos($tables, ' AS ') != false)
		{
			$tables = explode(' AS ', $tables);
			
			$this->q_from[array_shift($tables)] = array_shift($tables);
		}
		else
		{
			$this->q_from[] = trim($tables);
		}
		
		return $this;
	}
	
	// =============================
	// = ========== JOIN ========= =
	// =============================
	
	/**
	 * Specifies a JOIN.
	 * 
	 * @access public
	 * @param string|IgnitedQuery	The table / subquery to join with
	 * @param string|IgnitedQuery	The join condition (Can be an IgnitedQuery for multiple where statements)
	 * 				If set to false, a new IgnitedQuery is returned which acts like a multiple where
	 * @param string				The type of join
	 * 
	 * @return $this or a new IgniedQuery
	 */
	function &join($table, $cond = false, $type = '')
	{
		$this->q_cached = false;
		
		if($cond == false)
		{
			$obj = new IgnitedQuery();
			$obj->q_parent =& $this;
			$obj->q_only_where = true;
			
			$this->q_join[$table] = array('type' => $type, 'cond' => &$obj);
			return $obj;
		}
		elseif(is_a($cond,'IgnitedQuery'))
		{
			$cond->q_only_where = true;
			
			$data = array('type' => $type, 'cond' => &$cond);
		}
		else
		{
			$data = array('type' => $type, 'cond' => $cond);
		}
		
		$data['table'] =& $table;
		$this->q_join[] =& $data;
		
		return $this;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Specifies a LEFT JOIN.
	 * 
	 * @access public
	 * @param string				The table to join with
	 * @param string|IgnitedQuery	The join condition (Could be an IgnitedQuery (for multiple where))
	 * If set to false, a new IgnitedQuery is returned which acts like a multiple where
	 * 
	 * @return $this or a new IgniedQuery
	 */
	function &left_join($table, $cond = false)
	{
		return $this->join($table, $cond, 'LEFT');
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Specifies a LEFT OUTER JOIN.
	 * 
	 * @access public
	 * @param string				The table to join with
	 * @param string|IgnitedQuery	The join condition (Could be an IgnitedQuery (for multiple where))
	 * If set to false, a new IgnitedQuery is returned which acts like a multiple where
	 * 
	 * @return $this or a new IgniedQuery
	 */
	function &left_outer_join($table, $cond = false)
	{
		return $this->join($table, $cond, 'LEFT OUTER');
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Specifies a RIGHT JOIN.
	 * 
	 * @access public
	 * @param string				The table to join with
	 * @param string|IgnitedQuery	The join condition (Could be an IgnitedQuery (for multiple where))
	 * If set to false, a new IgnitedQuery is returned which acts like a multiple where
	 * 
	 * @return $this or a new IgniedQuery
	 */
	function &right_join($table, $cond = false)
	{
		return $this->join($table, $cond, 'RIGHT');
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Specifies a RIGHT OUTER JOIN.
	 * 
	 * @access public
	 * @param string				The table to join with
	 * @param string|IgnitedQuery	The join condition (Could be an IgnitedQuery (for multiple where))
	 * If set to false, a new IgnitedQuery is returned which acts like a multiple where
	 * 
	 * @return $this or a new IgniedQuery
	 */
	function &right_outer_join($table, $cond = false)
	{
		return $this->join($table, $cond, 'RIGHT OUTER');
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Specifies an INNER JOIN.
	 * 
	 * @access public
	 * @param string				The table to join with
	 * @param string|IgnitedQuery	The join condition (Could be an IgnitedQuery (for multiple where))
	 * If set to false, a new IgnitedQuery is returned which acts like a multiple where
	 * 
	 * @return $this or a new IgniedQuery
	 */
	function &inner_join($table, $cond = false)
	{
		return $this->join($table, $cond, 'INNER');
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Specifies an OUTER JOIN.
	 * 
	 * @access public
	 * @param string				The table to join with
	 * @param string|IgnitedQuery	The join condition (Could be an IgnitedQuery (for multiple where))
	 * If set to false, a new IgnitedQuery is returned which acts like a multiple where
	 *
	 * @return $this or a new IgniedQuery
	 */
	function &outer_join($table, $cond = false)
	{
		return $this->join($table, $cond, 'OUTER');
	}
	
	// =============================
	// = ========= WHERE ========= =
	// =============================
	
	/**
	 * Specifies the WHERE part of the query.
	 * 
	 * @access public
	 * @param mixed		The where data, can be an IgnitedQuery
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param mixed		The value to match with (can also be an array, then it generates key IN (val1, val2))
	 * @param bool		If to escape, default: true
	 * @param bool		If to add an OR before this where statement
	 * @param bool		If to prepend all predicates in this call with NOT
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &where($where = false, $value = null, $escape = true, $or = false, $not = false)
	{
		$this->q_cached = false;
		
		if($where === false)
		{
			if($or && ! empty($this->q_where))
				$this->q_where[] = 'OR';
			
			if($not)
				$this->q_where[] = 'NOT';
			
			$obj = new IgnitedQuery();
			$obj->q_parent =& $this;
			$obj->q_only_where = true;
			
			$this->q_where[] =& $obj;
			return $obj;
		}
		
		if(is_object($where) && is_a($where, 'IgnitedQuery'))
		{
			// sub where
			
			if($or && ! empty($this->q_where))
				$this->q_where[] = 'OR';
			
			if($not)
				$this->q_where[] = 'NOT';
			
			$where->q_only_where = true;
			
			$this->q_where[] =& $where;
			return $this;
		}
		
		if( ! is_array($where) && ($escape OR ! is_null($value)))
		{
			$where = array($where => $value);
		}
		
		foreach((Array)$where as $k => $val)
		{
			if($or && ! empty($this->q_where))
				$this->q_where[] = 'OR';
			
			// if we have an array, do a where in
			if(is_array($val))
			{
				$this->where_in($k, $val, $not);
				
				continue;
			}
			
			if($not)
				$this->q_where[] = 'NOT';
			
			// numeric tells us that we have a finished where statement
			if(is_numeric($k))
			{
				$this->q_where[] = $escape == true
									? $this->_protect_identifiers($val)
									: $val;
			}
			elseif(is_null($val))
			{
				// only protect the first, the rest is probably "IS (NOT) NULL"
				$this->q_where[] = $this->_protect_identifiers($k);
			}
			else
			{
				// process key => value where
				$k = $this->_protect_identifiers($k);
				
				if(is_object($val) && is_a($val,'IgnitedQuery'))
				{
					// subquery
					$val->q_as = false;
				}
				elseif($escape)
				{
					$val = $this->escape($val);
				}
				
				if(isset($this->q_where[$k]))
				{
					// we already have a where for that key, add it as an array
					// this makes it a bit slower, but allows for more than one comparision to the same column
					$this->q_where[] = array($k => $val);
				}
				else
				{
					$this->q_where[$k] = $val;
				}
			}
		}
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies an OR part in the WHERE part of the query.
	 * 
	 * Works like where()
	 * 
	 * @access public
	 * @param mixed		The where data
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param mixed		The value to (NOT) match with
	 * @param bool		If to escape, default: true
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &or_where($where = false, $value = null, $escape = true)
	{
		return $this->where($where, $value, $escape, true);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Produces a nested where with the keyword NOT in front of it.
	 * 
	 * @access public
	 * @param mixed 	The where data
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param mixed 	The value to match with
	 * @param bool	 	If to escape, default: true
	 * @param bool		If to add an OR before this where statement
	 * 
	 * @return $this or an IgnitedQuery
	 */
	function &not_where($where = false, $value = null, $escape = true, $or = false)
	{
		return $this->where($where, $value, $escape, $or, true);
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Produces a nested where with the keywords OR NOT in front of it.
	 * 
	 * @access public
	 * @param mixed 	The where data
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param mixed 	The value to match with
	 * @param bool	 	If to escape, default: true
	 * 
	 * @return $this or an IgnitedQuery
	 */
	function &or_not_where($where = false, $value = null, $escape = true)
	{
		return $this->not_where($where, $value, $escape, true);
	}
	
	// =============================
	// = ======= WHERE IN ======== =
	// =============================
	
	/**
	 * Generates a WHERE IN in the WHERE part of the query.
	 * 
	 * @access public
	 * @param string 		The column to check
	 * @param mixed 	The values to check against
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param bool 		If this should be a WHERE NOT IN query
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &where_in($key, $values = false, $not = false)
	{
		$this->q_cached = false;
		
		$key = $this->_protect_identifiers($key);
		
		if($not)
			$key .= ' NOT';
		
		$this->q_where[] = $key;
		$this->q_where[] = 'IN';
		
		if($values === false)
		{
			$obj = new IgnitedQuery();
			$obj->q_parent =& $this;
			$obj->q_as = false;
			
			$this->q_where[] =& $obj;
			
			return $obj;
		}
		
		if(is_object($values) && is_a($values,'IgnitedQuery'))
		{
			$values->q_as = false;
			
			$this->q_where[] =& $values;
			
			return $this;
		}
		
		$str = implode(', ', array_map(array(&$this, 'escape'), (Array) $values));
		
		$this->q_where[] = '('.$str.')';
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies an OR WHERE IN in the WHERE part of the query.
	 * 
	 * Works like where_in()
	 * 
	 * @access public
	 * @param string 	The column to check
	 * @param mixed 	The values to check against
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &or_where_in($key, $values = false)
	{
		if( ! empty($this->q_where))
			$this->q_where[] = 'OR';
		
		return $this->where_in($key,$values);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies an WHERE NOT IN in the WHERE part of the query.
	 * 
	 * @access public
	 * @param string 	The column to check
	 * @param mixed 	The values to check against
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &where_not_in($key, $values = false)
	{
		return $this->where_in($key,$values,true);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies an OR WHERE NOT IN in the WHERE part of the query.
	 * 
	 * Works like where_not_in()
	 * 
	 * @access public
	 * @param $key 		The column to check
	 * @param $values 	The values to check against
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &or_where_not_in($key, $values = false)
	{
		if( ! empty($this->q_where))
			$this->q_where[] = 'OR';
		
		return $this->where_in($key,$values,true);
	}
	
	// =============================
	// = ========== LIKE ========= =
	// =============================
	
	/**
	 * Produces a LIKE clause in the WHERE part of the query.
	 * 
	 * @access public
	 * @param mixed		The column to match (or array with column => match)
	 * @param string 	The match string
	 * @param string 	Which side the '%' sign should be on,
	 * 					Accepts 'both', 'before' and 'after' (default: 'both')
	 * @param bool		If to add OR to the statement (default: false)
	 * @param bool 		If to add NOT to the statement (default: false)
	 * 
	 * @return $this
	 */
	function &like($col, $match = '', $side = 'both', $or = false, $not = false)
	{
		$this->q_cached = false;
		
		if( ! is_array($col))
			$col = array($col => $match);
		
		foreach($col as $k => $v)
		{
			if($or && ! empty($this->q_where))
				$this->q_where[] = 'OR';
			
			if($not)
				$this->q_where[] = 'NOT';
			
			$key = $this->_protect_identifiers($k).' LIKE';
			
			$v = $this->escape_str($v);
			
			if($side == 'before')
				$v = "'%$v'";
			elseif($side == 'after')
				$v = "'$v%'";
			else
				$v = "'%$v%'";
			
			if(isset($this->q_where[$key]))
			{
				$this->q_where[] = array($key => $v);
			}
			else
			{
				$this->q_where[$key] = $v;
			}
		}
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Produces a NOT LIKE sql statement.
	 * 
	 * @access public
	 * @param mixed 	The column to match
	 * @param string 	The match string
	 * @param string 	Which side the '%' sign should be on,
	 * 					Accepts 'both', 'before' and 'after' (default: 'both')
	 * 
	 * @return $this
	 */
	function not_like($col, $match = '', $side = 'both')
	{
		return $this->like($col, $match, $side, true);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Produces an OR LIKE sql statement.
	 * 
	 * @access public
	 * @param mixed 	The column to match
	 * @param string 	The match string
	 * @param string 	Which side the '%' sign should be on,
	 * 					Accepts 'both', 'before' and 'after' (default: 'both')
	 * @param bool 		If to add NOT to the statement (default: false)
	 * 
	 * @return $this
	 */
	function or_like($col, $match = '', $side = 'both', $not = false)
	{
		return $this->like($col, $match, $side, true, $not);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Produces an OR NOT LIKE sql statement.
	 * 
	 * @access public
	 * @param mixed		The column to match
	 * @param string 	The match string
	 * @param string 	Which side the '%' sign should be on,
	 * 					Accepts 'both', 'before' and 'after' (default: 'both')
	 * 
	 * @return $this
	 */
	function or_not_like($col, $match = '', $side = 'both')
	{
		return $this->or_like($col, $match, $side, true);
	}
	
	// =============================
	// = ========= HAVING ======== =
	// =============================
	
	/**
	 * Specifies the data to go into the HAVING clause of the query.
	 * 
	 * @access public
	 * @param mixed					The column (or array with column => value, or IgnitedQuery with a nested where)
	 * @param string|IgnitedQuery	The value to match,
	 * 								if set to false (default: null) a subquery is returned
	 * @param bool					If to escape the data
	 * @param bool					If to prefix it with OR
	 * 
	 * @return $this|IgnitedQuery
	 */
	function &having($having = false, $value = null, $escape = true, $or = false)
	{
		$this->q_cached = false;
		
		// nested
		if($having === false)
		{
			if($or && ! empty($this->q_having))
				$this->q_having[] = 'OR';
			
			$obj = new IgnitedQuery();
			$obj->q_parent =& $this;
			$obj->q_only_where = true;
			
			$this->q_having[] =& $obj;
			return $obj;
		}
		
		// nested which is passed
		if(is_object($having) && is_a($having, 'IgnitedQuery'))
		{
			// subquery
			if($or && ! empty($this->q_having))
				$this->q_having[] = 'OR';
			
			$having->q_only_where = true;
			
			$this->q_having[] =& $having;
			return $this;
		}
		
		// subquery
		if($value === false)
		{
			if($or && ! empty($this->q_having))
				$this->q_having[] = 'OR';
			
			$obj = new IgnitedQuery();
			$obj->q_parent =& $this;
			$obj->q_as = false; // no alias
			
			if(isset($this->q_having[$k]))
			{
				$this->q_having = array($k => &$obj);
			}
			else
			{
				$this->q_having[$k] = &$obj;
			}
			
			return $obj;
		}
		
		if( ! is_array($having) && ($escape OR $value != null))
		{
			$having = array($having => $value);
		}
		
		foreach((Array)$having as $k => $val)
		{
			if($or && ! empty($this->q_having))
				$this->q_having[] = 'OR';
			
			// numeric tells us that we have a finished having statement
			if(is_numeric($k) OR is_null($val))
			{
				$this->q_having[] = $escape == true
									? $this->_protect_identifiers($val)
									: $val;
			}
			else
			{
				// process key => value having
				$k = $this->_protect_identifiers($k);
				
				if(is_object($val) && is_a($val,'IgnitedQuery'))
				{
					// subquery
					$val->q_as = false;
				}
				elseif($escape)
				{
					$val = $this->escape($val);
				}
				
				if(isset($this->q_having[$k]))
				{
					$this->q_having = array($k => $val);
				}
				else
				{
					$this->q_having[$k] = $val;
				}
			}
		}
		
		return $this;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Specifies the data to go into the HAVING clause of the query, prefixes wth OR.
	 * 
	 * @access public
	 * @param mixed					The column (or array with column => value, or IgnitedQuery with a nested where)
	 * @param string|IgnitedQuery	The value to match,
	 * 								if set to false (default: null) a subquery is returned
	 * @param bool					If to escape the data
	 * 
	 * @return $this or a subquery
	 */
	function &or_having($having = false, $value = null, $escape = true)
	{
		return $this->having($having, $value, $escape, true);
	}
	
	// =============================
	// = ===== SQL MODIFIERS ===== =
	// =============================
	
	/**
	 * Specifies the GROUP BY part of the query.
	 * 
	 * @access public
	 * @param mixed The column(s) to group by (string or array)
	 * 
	 * @return $this
	 */
	function &group_by($by)
	{
		$this->q_cached = false;
		
		if(is_string($by))
		{
			$by = explode(',',$by);
		}
		
		foreach((Array)$by as $val)
		{
			$val = trim($val);
			$this->q_group_by[] = $this->_protect_identifiers($val);
		}
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies the ORDER BY part of the query.
	 * 
	 * @access public
	 * @param mixed The column(s) to order by
	 * @param string The direction to order by (false to just add the $by as an order by criteria)
	 * 
	 * @return $this
	 */
	function &order_by($by, $direction = '')
	{
		$this->q_cached = false;
		
		if($direction === false)
		{
			$this->q_order_by[] = $by;
		}
		else
		{
			if(strtolower($direction) == 'random')
			{
				$CI =& get_instance();
				$this->q_order_by = $CI->db->_random_keyword;
				
				return $this;
			}
			elseif(trim($direction) != '')
			{
				$direction = (in_array(strtoupper(trim($direction)), array('ASC', 'DESC'), true)) ? ' '.$direction : ' ASC';
			}
		
			if(is_string($by))
			{
				$by = explode(',', $by);
			}
		
			foreach((Array)$by as $val)
			{
				$val = trim($val);
				$this->q_order_by[] = $this->_protect_identifiers($val). ' '.$direction;
			}
		}
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies if and what the LIMIT part of the query should be.
	 *
	 * @access public
	 * @param int The number of rows to limit to, false to reset
	 * @param int Optional offset (works exactly like offset())
	 * 
	 * @return $this
	 */
	function &limit($val, $offset = null)
	{
		$this->q_cached = false;
		
		if($offset !== null)
		{
			$this->offset($offset);
		}
		
		if(is_numeric($val) OR $val === false)
		{
			$this->q_limit = $val;
		}
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies if and what the OFFSET part of the query should be.
	 *
	 * @access public
	 * @param int The number, false to reset
	 * 
	 * @return $this
	 */
	function &offset($val)
	{
		$this->q_cached = false;
		
		if(is_numeric($val) OR $val === false)
			$this->q_offset = $val;
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies an alias for this subquery.
	 * 
	 * Note: This only applies if this object is a subquery
	 * 
	 * @access public
	 * @param string The alias
	 * 
	 * @return $this
	 */
	function &alias($alias)
	{
		if($this->q_as !== false)
			$this->q_as = $this->_protect_identifiers($alias);
		
		return $this;
	}
	
	// =============================
	// = ========== SET ========== =
	// =============================
	
	/**
	 * This function saves the data to be INSERTed/UPDATEd.
	 * 
	 * @access public
	 * @param mixed		The name of the field to set, or an asociative array / object with field => value
	 * @param mixed		The value to assign to $key, can be a subquery returning a SINGLE value (automatically added)
	 * @param bool		If to escape the values (default: true), subqueries are not escaped
	 * 
	 * @return $this
	 */
	function set($key, $value = '', $escape = true)
	{
		if( ! is_array($key) && ! is_object($key))
		{
			$key = array($key => $value);
		}
		
		foreach((Array)$key as $key => $value)
		{
			if($escape === true && ! is_object($value))
			{
				$value = $this->escape($value);
			}
			
			if(is_object($value) && is_a($value,'IgnitedQuery'))
			{
				$value->q_as = false; // no aliases
				$value->limit(1); // set limit to 1, because only one row can be returned and assigned
			}
			
			$this->q_set_data[$key] = $value;
		}
		
		return $this;
	}
	
	// =============================
	// = === END QUERY METHODS === =
	// =============================
	
	/**
	 * Ends a subquery block, returning the parent query object.
	 * 
	 * If this is the root query, null is returned.
	 * 
	 * @access public
	 * @return IgnitedQuery parent object
	 */
	function &end()
	{
		return $this->q_parent;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Runs the SELECT query.
	 * 
	 * Resets this object after the query has been assembled.
	 * 
	 * @access public
	 * @param string	A table which is forwarded to from()
	 * @param int		The limit
	 * @param int		The offset
	 * 
	 * @return DB_result
	 */
	function get($table = '', $limit = null, $offset = null)
	{
		$this->limit($limit);
		$this->offset($offset);
		
		if($table != '')
			$this->from($table);
		
		$CI =& get_instance();
		return $CI->db->query($this->_build_get_query());
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Runs a SELECT query, but returns only the first match.
	 * 
	 * Resets this object after the query has been assembled.
	 * Limit is always 1
	 * 
	 * @access public
	 * @param string	A table which is forwarded to from()
	 * 
	 * @return std_class result, false if nothing was found
	 */
	function get_one($table = '')
	{
		$this->limit(1);
		
		if($table != '')
			$this->from($table);
		
		$CI =& get_instance();
		$q = $CI->db->query($this->_build_get_query());
		
		if( ! $q->num_rows())
		{
			$q->free_result();
			return false;
		}
		else
		{
			// get the row
			$data = $q->row();
			$q->free_result();
			return $data;
		}
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Like get_one(), with the exception that it returns arrays.
	 * 
	 * @access public
	 * @param string	A table which is forwarded to from()
	 * 
	 * @return array array, false if nothing was found
	 */
	function get_one_array($table = '')
	{
		$this->limit(1);
		
		if($table != '')
			$this->from($table);
		
		$CI =& get_instance();
		$q = $CI->db->query($this->_build_get_query());
		
		if( ! $q->num_rows())
		{
			$q->free_result();
			return false;
		}
		else
		{
			// get the row
			$data = $q->row_array();
			$q->free_result();
			return $data;
		}
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Returns a single variable from a SELECT query.
	 * 
	 * Useful if you have a select like SELECT count(1) FROM ...
	 * 
	 * LIMIT is always 1
	 * Always returns the first value in the SELECT
	 * 
	 * @access public
	 * @param string A table which is forwarded to from()
	 * 
	 * @return string|int the value from the query, false if nothing was found
	 */
	function get_var($table = '')
	{
		$this->limit(1);
		
		if($table != '')
			$this->from($table);
		
		$CI =& get_instance();
		$q = $CI->db->query($this->_build_get_query());
		
		if( ! $q->num_rows())
		{
			$q->free_result();
			return false;
		}
		else
		{
			// get the row
			$data = $q->row_array();
			$q->free_result();
			
			// get the first value
			return array_shift($data);
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Runs the SELECT query, and specifies a where clause.
	 * 
	 * Resets this object after the query has been assembled.
	 *
	 * @access public
	 * @param string	A table which is forwarded to from()
	 * @param mixed		Forwarded to where()
	 * @param int		The limit
	 * @param int		The offset
	 * 
	 * @return DB_result
	 */
	function get_where($table, $where, $limit = null, $offset = null)
	{
		$this->where($where);
		$this->limit($limit);
		$this->offset($offset);
		
		if($table != '')
			$this->from($table);
		
		$CI =& get_instance();
		return $CI->db->query($this->_build_get_query());
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Performs an INSERT query
	 * 
	 * @access public
	 * @param string The table
	 * @param array	 The data to insert
	 * 
	 * @return bool
	 */
	function insert($table = '', $set = null)
	{
		$this->load_ar_cache();
		
		if( ! is_null($set))
			$this->set($set);
		
		if($table == '')
		{
			if( ! isset($this->q_from[0]))
				return false;
			
			$table = $this->q_from[0];
		}
		
		$set = array();
		
		$keys = array_keys($this->q_set_data);
		
		foreach($keys as $key)
		{
			if(is_a($this->q_set_data[$key], 'IgnitedQuery'))
			{
				$set[] = $this->q_set_data[$key]->get_select_sql();
			}
			else{
				$set[] = $this->q_set_data[$key];
			}
		}
		
		$CI =& get_instance();
		$sql = $CI->db->_insert($this->_protect_identifiers($this->q_prefix.$table), array_map(array(&$this, '_protect_identifiers'), $keys), $set);
		
		$this->reset();
		
		$ret = $CI->db->query($sql);
		
		$this->q_insert_id = $CI->db->insert_id();
		$this->q_affected_rows = $CI->db->affected_rows();
		
		return $ret;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Returns the id assigned to the AUTO INCREMENT column in the last insert call.
	 * 
	 * false if no connection / query has been made, 0 if no AUTO INCREMENTal
	 * column exists.
	 * 
	 * @access public
	 * @return int
	 */
	function insert_id()
	{
		return $this->q_insert_id;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Performs an UPDATE query
	 * 
	 * @access public
	 * @param string	The table
	 * @param array		The data to update to
	 * @param mixed		The where part of the query
	 * @param int		A LIMIT for the query
	 * 
	 * @return bool
	 */
	function update($table = '', $set = null, $where = null, $limit = null)
	{
		$this->load_ar_cache();
		
		if( ! is_null($set))
			$this->set($set);
		
		if(empty($this->q_set_data))
			return false;
		
		if($table == '')
		{
			if( ! isset($this->q_from[0]))
				return false;
			
			$table = $this->q_from[0];
		}
		
		if ($where != null)
		{
			$this->where($where);
		}

		if ($limit != null)
		{
			$this->limit($limit);
		}
		
		$set = array();
		
		foreach(array_keys($this->q_set_data) as $key)
		{
			if(is_a($this->q_set_data[$key], 'IgnitedQuery'))
			{
				$set[$this->_protect_identifiers($key)] = $this->q_set_data[$key]->get_select_sql();
			}
			else{
				$set[$this->_protect_identifiers($key)] = $this->q_set_data[$key];
			}
		}
		
		$CI =& get_instance();
		$sql = $CI->db->_update($this->_protect_identifiers($this->q_prefix.$table), $set, array($this->_build_where($this->q_where)), $this->q_order_by, $this->q_limit);
		
		$this->reset();
		
		$ret = $CI->db->query($sql);
		
		$this->q_affected_rows = $CI->db->affected_rows();
		
		return $ret;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Performs a DELETE query.
	 * 
	 * @access public
	 * @param string	The table to delete rows from (may be multiple separated with commas, fist is set as FROM)
	 * @param mixed		The WHERE part of the query
	 * @param int		The LIMIT of the query
	 * @param bool		If to reset this object after delete, default: true
	 * 
	 * @return bool
	 */
	function delete($table = '', $where = null, $limit = null, $reset_data = true)
	{
		$this->load_ar_cache();
		
		if($table == '')
		{
			if( ! isset($this->q_from[0]))
				return false;
			
			$table = $this->q_from[0];
		}
		
		if($where != null)
		{
			$this->where($where);
		}
		
		if(empty($this->q_where) && empty($this->q_like))
		{
			return false;
		}

		if($limit != null)
		{
			$this->limit($limit);
		}
		
		$CI =& get_instance();
		
		// prefix and protect identifiers
		$table = implode(', ', array_map(array(&$this, '_protect_identifiers'), array_map(array(&$this, '_add_prefix'), array_map('trim', explode(',', $table)))));
		
		
		$join = ( ! empty($this->q_join)) ? "\n".$this->_build_join($this->q_join) : '';
		
		$conditions = ( ! empty($this->q_where)) ? "\nWHERE " . $this->_build_where($this->q_where) : '';
		
		$limit = empty($this->q_limit) ? '' : ' LIMIT ' . $this->q_limit;
		
		// if we have joins, add the tables to delete after "DELETE"
		$sql = 'DELETE '.(( ! empty($join)) ? $table.' ' : '').'FROM ' . reset(explode(',', $table)) . $join . $conditions . $limit;

		if($reset_data)
		{
			$this->reset();
		}
		
		$ret = $CI->db->query($sql);
		
		$this->q_affected_rows = $CI->db->affected_rows();
		
		return $ret;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Returns the number of rows affected by the last UPDATE, DELETE or INSERT query.
	 * 
	 * @access public
	 * @return int (false if no query has been run)
	 */
	function affected_rows()
	{
		return $this->q_affected_rows;
	}
	
	// =============================
	// = ==== PRIVATE METHODS ==== =
	// =============================
	
	/**
	 * Builds a SELECT query from the internal variables.
	 *
	 * Resets the internal variables before returning the query.
	 * 
	 * (Called by parent queries)
	 * 
	 * @access private
	 * @return string
	 */
	function _build_get_query()
	{
		$query = $this->get_select_sql(false);
		
		if(empty($query))
			show_error('IgnitedQuery: Cannot generate a SQL-query: the query object is empty.');
		
		$this->reset();
		
		return $query;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Previews the generated SQL.
	 * 
	 * Builds the SELECT as query, but does not clear internals.
	 * 
	 * @access public
	 * @param bool If it should be wrapped in paranteses and also aliased
	 * 
	 * @return string
	 */
	function get_select_sql($parent = true)
	{
		if($this->q_cached == true)
		{
			// use cached query
			$query = $this->q_cache;
			
			if($this->q_only_where)
				return '('.$this->_build_where($this->q_where).')';
			
			log_message('debug', "Using cached query '$query'<br />");
		}
		else
		{
			$this->load_ar_cache();
			
			if($this->q_only_where)
				return '('.$this->_build_where($this->q_where).')';
			
			if(empty($this->q_from))
				show_error('IgnitedQuery: Cannot generate SQL-query - the from part of the query object is empty.');
			
			$query = 'SELECT ' . ($this->q_distinct
								 ? 'DISTINCT '
								 : '');
			
			// Wait, we need to do this first...
			// (because of determining what to prefix)
			$from_clause = $this->_build_from($this->q_from);
			
			// And this...
			$join_clause = ( ! empty($this->q_join))
						? $this->_build_join($this->q_join)
						: '';
			
			// Then we'll do everything else
			$query .= $this->_build_select($this->q_select);
			
			$query .= "\nFROM ".$from_clause;
			
			if( ! empty($join_clause))
			{
				$query .= "\n".$join_clause;
			}
			
			if( ! empty($this->q_where))
			{
				$query .= "\nWHERE ".$this->_build_where($this->q_where);
			}
			
			if( ! empty($this->q_group_by))
			{
				$query .= "\nGROUP BY ".implode($this->q_group_by, ', ');
			}
			
			if( ! empty($this->q_having))
			{
				$query .= "\n HAVING ".$this->_build_having($this->q_having);
			}
			
			if( ! empty($this->q_order_by))
			{
				$query .= "\nORDER BY ".implode($this->q_order_by, ', ');
			}
			
			if(is_numeric($this->q_limit))
			{
				// use CI's database specific limit
				$CI =& get_instance();
				$query .= "\n";
				$query = $CI->db->_limit($query, $this->q_limit, $this->q_offset);
			}
			
			$this->q_cached = true;
			$this->q_cache = $query;
		}
		
		if($parent == true)
		{
			// this is a subquery
			$query = '('.$query.')'.
				(isset($this->q_as) && $this->q_as != false
					? ' AS '.$this->_protect_identifiers($this->q_as).' '
					: ''
				);
		}
		
		return $query;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Builds the SELECT part of the query.
	 * 
	 * @access private
	 * @param mixed The select data
	 * 
	 * @return string
	 */
	function _build_select($select)
	{
		if(is_object($select))
		{
			$select->q_to_prefix = $this->q_to_prefix;
			
			return $select->get_select_sql();
		}
		elseif(is_array($select))
		{
			$str = '';
			$i = 0;
			
			foreach($select as $key => $col)
			{
				// add commas if needed
				$str .= $i++ > 0 ? ', ' : '';
				
				// these are db functions, just echo them
				if(in_array($col, array('MIN','MAX','AVG','SUM','COUNT')))
				{
					$str .= $col;
					$i = 0;
					continue;
				}
				
				if( ! is_numeric($key))
					$str .= $this->_build_select($key) . ' AS ' . $this->_protect_identifiers($col);
				else
					$str .= $this->_build_select($col);
			}
			
			return $str;
		}
		
		return $this->_prefix($select);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Builds the FROM part of the query.
	 * 
	 * @access private
	 * @param mixed The from data
	 * 
	 * @return string
	 */
	function _build_from($from)
	{
		if(is_object($from))
		{
			return $from->get_select_sql();
		}
		elseif(is_array($from))
		{
			$str = array();
			
			foreach($from as $key => $col)
			{
				// not numeric tells us that we have "tablename => alias"
				if( ! is_numeric($key))
					$str[] = $this->_build_from($key) . ' AS ' . $this->_protect_identifiers($col);
				else
					$str[] = $this->_build_from($col);
			}
			
			return implode(', ', $str);
		}
		
		return $this->_protect_identifiers($this->_add_prefix($from));
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Builds the join part of the query,
	 * 
	 * @access private
	 * @param mixed The data to utilize when building the join
	 * 
	 * @return string
	 */
	function _build_join($join_data)
	{
		$ret = array();
		
		foreach($join_data as $opts)
		{
			$str = '';
			
			if($opts['type'] != '')
			{
				$type = strtoupper(trim($opts['type']));

				if ( ! in_array($type, array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER'), true))
				{
					$type = '';
				}
				else
				{
					$str .= "$type ";
				}
			}
			
			$str .= 'JOIN ';
			
			if(is_a($opts['table'], 'IgnitedQuery'))
			{
				$str .= $opts['table']->get_select_sql(true);
			}
			else
			{
				$str .= $this->_protect_identifiers($this->_add_prefix($opts['table']));
			}
			
			if(isset($opts['cond']))
			{
				$str .= ' ON ';
				
				if(is_object($opts['cond']))
				{
					$opts['cond']->q_to_prefix = $this->q_to_prefix;
					
					$str .= $opts['cond']->get_select_sql();
				}
				else
					$str .= $this->_prefix($opts['cond']);
			}
			
			$ret[] = $str;
		}
		
		return implode("\n", $ret);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Builds the WHERE part of the query.
	 * 
	 * @access private
	 * @param mixed The where data
	 * 
	 * @return string
	 */
	function _build_where($where)
	{
		if(is_object($where))
		{
			$where->q_to_prefix = $this->q_to_prefix;
			
			return $where->get_select_sql();
		}
		elseif(is_array($where))
		{
			$str = '';
			$i = 0;
			
			foreach($where as $key => $col)
			{
				// replacements for AND
				if(in_array($col, array('OR', 'IN'), true))
				{
					$str .= " $col ";
					$i = 0;
					continue;
				}
				
				// add AND:s if needed
				$str .= $i++ > 0 ? ' AND ' : '';
				
				// not
				if($col === 'NOT')
				{
					$str .= " $col ";
					$i = 0;
					continue;
				}
				
				// not numeric tells us that we have a column name
				if( ! is_numeric($key))
				{
					// do we need to add an equal sign?
					if( ! $this->_has_operator($key))
					{
						if(is_null($col))
							$str .= $this->_prefix($key) . ' IS NULL';
						else
							$str .= $this->_prefix($key) . ' = ' . $this->_build_where($col);
					}
					else
					{
						$str .= $this->_prefix($key) . ' ' . $this->_build_where($col);
					}
				}
				else
				{
					$str .= $this->_build_where($col);
				}
			} // endforeach
			
			return $str;
		}
		
		return $this->_prefix($where);
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Builds the HAVING part of the query.
	 * 
	 * @access private
	 * @param mixed The where data
	 * 
	 * @return string
	 */
	function _build_having($having)
	{
		if(is_object($having))
		{
			$having->q_to_prefix = $this->q_to_prefix;
			
			return $having->get_select_sql();
		}
		elseif(is_array($having))
		{
			$str = '';
			$i = 0;
			
			foreach($having as $key => $col)
			{
				if($col == 'OR')
				{
					$str .= " $col ";
					$i = 0;
					continue;
				}
				
				// add AND:s if needed
				$str .= $i++ > 0 ? ' AND ' : '';
				
				// not numeric tells us that we have a column name
				if( ! is_numeric($key))
				{
					// do we need to add an equal sign?
					if( ! $this->_has_operator($key))
					{
						if(is_null($col))
							$str .= $this->_prefix($key);
						else
							$str .= $this->_prefix($key) . ' = ' . $this->_build_having($col);
					}
					else
					{
						$str .= $this->_prefix($key) . ' ' . $this->_build_having($col);
					}
				}
				else
				{
					$str .= $this->_build_having($col);
				}
			} // endforeach
			
			return $str;
		}
		
		return $this->_prefix($having);
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Prefixes all tables in the supplied string,
	 * 
	 * @access private
	 * @param string The string containing the tablenames
	 * 
	 * @return string
	 */
	function _prefix($str)
	{
		if(empty($this->q_prefix))
		{
			return $str;
		}
		elseif(strpos($str, '.')) // cannot start with a dot, just ignore false and zero
		{
			foreach($this->q_to_prefix as $table)
			{
				if(strpos($str, $table) !== false)
				{
					$str = preg_replace('@(?<=^|\s|\.|`|")'.$table.'(?=$|\s|\.|`|")@i', $this->q_prefix . $table, $str);
				}
			}
		}
		
		return $str;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Prefixes the string supplied, and adds the table name to the to prefix list.
	 * 
	 * @access private
	 * @param string The string to prefix
	 * 
	 * @return string
	 */
	function _add_prefix($str)
	{
		$this->q_to_prefix[] = $str;
		
		return $this->q_prefix . $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Determines if a string has an operator.
	 * 
	 * From CI_active_rec.
	 * 
	 * @static
	 * @access private
	 * @param string The string to be filtered
	 * 
	 * @return string
	 */
	function _has_operator($str)
	{
		$str = trim($str);
		if ( ! preg_match("/(\s|<|>|!|=|is null|is not null)/i", $str))
		{
			return false;
		}
		
		return true;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Escapes a string.
	 * 
	 * @static
	 * @param string
	 * @return string
	 */
	function escape($str)
	{
		switch (gettype($str))
		{
			case 'string'	:	$str = "'".$this->escape_str($str)."'";
				break;
			case 'boolean'	:	$str = ($str === FALSE) ? 0 : 1;
				break;
			default			:	$str = ($str === NULL) ? 'NULL' : $str;
				break;
		}

		return $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Escapes a string.
	 * 
	 * @static
	 * @param string
	 * @return string
	 */
	function escape_str($str)
	{
		$CI =& get_instance();
		return $CI->db->escape_str($str);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Own implementation of the _protect identifiers.
	 * 
	 * The CI _protect_identifiers is too tangled with the db prefixing to use.
	 * 
	 * @access private
	 * @param string The string to protect
	 * 
	 * @return string
	 */
	function _protect_identifiers($item)
	{
		switch($this->q_dbdriver)
		{
			case 'mssql':
				// fixes error with escaped tablename
				return $item; // TODO: How does this affect other identifiers?
			
			case 'oc8':
			case 'postgre':
				$quot = '"';
				break;
			
			case 'sqlite':
				$quot = ''; // hmmm, strange
				break;
			
			default:
				$quot = '`';
				break;
		}
		
		if(strpos($item, ' '))
		{
			// spaces, maybe we have to prevent escaping of operators
			
			$no_escape = array('AS', '+', '-', '/', '*', '%', '=', '<', '>', '<=', '>=', '<>', '!=', '!', ',', 'IS', 'NOT', 'NULL');
			$items = explode(' ', $item);
			$result = array();
			
			foreach($items as $i)
			{
				if(strpos($i, '.'))
				{
					$result[] = $quot.str_replace('.', $quot.'.'.$quot, $i).$quot;
				}
				elseif(in_array($i, $no_escape))
				{
					$result[] = $i;
				}
				else
				{
					$result[] = $quot.$i.$quot;
				}
			}
			
			$item = implode(' ', $result);
		}
		else
		{
			if(strpos($item, '.'))
			{
				$item = $quot.str_replace('.', $quot.'.'.$quot, $item).$quot;
			}
			else
			{
				$item = $quot.$item.$quot;
			}
		}
		
		if(strpos($item, $quot.'*'.$quot))
		{
			return str_replace($quot.'*'.$quot, '*', $item);
		}
		else
		{
			return $item;
		}
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * A version of array_diff_assoc which also performs a full comparison
	 * of the values.
	 * 
	 * @static
	 * @access private
	 * @param array The first array
	 * @param array The second array
	 * 
	 * @return array
	 */
	function array_diff_assoc_values($array1, $array2)
	{
		$r = array();
		foreach($array1 as $k => $v)
		{
			if(isset($array2[$k]))
			{
				if(serialize($v) != serialize($array2[$k]))
				{
					$r[$k] = $v;
				}
			}
			else
			{
				$r[$k] = $v;
			}
		}
		
		return $r;
	}
}

/**
 * Some sugar for all those who are lazy.
 * 
 * @package IgnitedRecord
 * @subpackage IgnitedQuery
 */
class Query extends IgnitedQuery{}

/* End of file ignitedquery.php */
/* Location: ./application/libraries/ignitedquery.php */