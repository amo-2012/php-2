<?php

/**
 * MySQL
 * DataBase Query Builder
 * Simple sql builder like in Kohana 3 (framework)
 * Version: 0.0.2
 * Author: me@rhrn.ru (send me bug, messages, your SQLs queries)
 *
 * Usage:

	require_once 'dbqb.php';
	
	$insert['firstname']	= 'Roman';
	$insert['middlename']	= 'rhrn';
	$insert['lastname']	= 'Nesterov';
	$insert['created']	= date('Y-m-d H:i:s');

	$sql[] = DBQB::Insert('users')
			->data($insert)
			->sql();

	#output: INSERT INTO `users` (`firstname`, `middlename`, `lastname`, `created`) VALUES ('Roman', 'rhrn', 'Nesterov', '2012-01-12 19:21:49'); 

	$sql[] = DBQB::Select('users')
			->fields('users.*', 'profiles.*')
			->fields(array('users.name' => 'user_name'))
			->where('id', '=', 10)
			->join('profiles', 'LEFT')
				->on('profiles.user_id', '=', 'users.id')
			->sql();

	#output: SELECT `users`.*, `profiles`.* FROM `users` LEFT JOIN `profiles` ON (`profiles`.`user_id` = `users`.`id`) WHERE `id` = '10';


	$update['twitter']	= 'rhrn';
	$update['updated']	= date('Y-m-d H:i:s');

	$sql[] = DBQB::Update('profiles')
			->set($update)
			->set('gender', 'male')
			->where('user_id', '=', 10)
			->limit(1)
			->sql();

	#output: UPDATE `profiles` SET `twitter` = 'rhrn', `updated` = '2012-01-13 17:25:13', `gender` = 'male' WHERE `user_id` = '10' LIMIT 1;


	$sql[] = DBQB::Delete('messages')
			->where('messages.id', '=', 10)
			->order('id', 'ASC')
			->limit(1)
			->sql();

	#output: DELETE FROM `messages` WHERE `messages`.`id` = '10' ORDER BY `id` ASC LIMIT 1;

	$sql[] = DBQB::Select('users')
		->fields('users.firstname')
		->fields(array('some' => 'thing', 'ololo' => 'realni'))
		->fields(DBQB::Pure('NOW() AS `now`'), DBQB::Pure('COUNT(*) AS `count`'))
		->sql();

	#output: SELECT `users`.`firstname`, `qwe` AS `sum`, `qweq` AS `ccc`, NOW() AS `now`, COUNT(*) AS `count` FROM `users`;

	$message1 = array('id' => 1, 'message' => 'hello');
	$message2 = array('id' => 2, 'message' => 'world');

	$sql[] = DBQB::Insert('messages')
			->data($message1)
			->data($message2)
			->sql();

	#output: INSERT INTO `messages` (`id`,`message`) VALUES ('1', 'hello'), ('2', 'world');

	echo implode("\n", $sql);

 *
 */

class DBQB {

	const INSERT = 1;
	const SELECT = 2;
	const UPDATE = 3;
	const DELETE = 4;

	public static $quoteName = '`';
	public static $quoteVar = '\'';

	protected $sql;
	protected $sqlQuery;

	private $type;
	private $collection = array();

	function __construct($type, $table) {
		$this->type			= $type;
		$this->collection['table']	= $table;
	}

	/**
	 * CRUD init functions
	 */

	public static function Insert($table = null) {
		return new self(self::INSERT, $table);
	}

	public static function Select($table = null) {
		return new self(self::SELECT, $table);
	}

	public static function Update($table = null) {
		return new self(self::UPDATE, $table);
	}

	public static function Delete($table = null) {
		return new self(self::DELETE, $table);
	}

	public static function Pure($pure) {
		return (object) $pure;
	}


	/**
	 * SET functions
	 */

	public function table($table) {
		$this->collection['table'][] = $table;
		return $this;
	}

	public function data($data) {
		$this->collection['data'][] = $data;
		return $this;
	}

	public function fields() {
                $this->collection['fields'][] = func_get_args();
		return $this;
	}

	public function where() {
		$args = func_get_args();

		if (!is_array($args[0]) && sizeof($args) === 3) {
			$this->collection['where'][]['AND'] = $args;
		} 

		return $this;
	}

	public function where_and() {
		$args = func_get_args();

		if (empty($args)) {
			$this->collection['where'][] = 'AND';
		} 

		return $this;
	}

	public function where_or() {
		$args = func_get_args();

		if (!is_array($args[0]) && sizeof($args) === 3) {
			$this->collection['where'][]['OR'] = $args;
		} elseif (empty($args)) {
			$this->collection['where'][] = 'OR';
		}

		return $this;
	}

	public function where_open() {
		$this->collection['where'][] = '(';
		return $this;
	}

	public function where_close() {
		$this->collection['where'][] = ')';
		return $this;
	}

	public function limit($limit, $offset = null) {
                $this->collection['limit'] = $limit;
		$this->collection['offset'] = $offset;
		return $this;
	}

	public function offset($offset) {
		$this->collection['offset'] = $offset;
		return $this;
	}

	public function join() {
		$this->collection['join'][] = func_get_args();
		return $this;
	}

	public function on() {
		$this->collection['on'][] = func_get_args();
		return $this;
	}

	public function using() {
		$this->collection['using'][] = func_get_args();
		return $this;
	}

	public function set() {
		$this->collection['set'][] = func_get_args();
		return $this;
	}

	public function order() {
		$this->collection['order'][] = func_get_args();
		return $this;
	}

	public function group() {
		$this->collection['group'][] = func_get_args();
		return $this;
	}

	public function having() {
		$this->collection['having'][] = func_get_args();
		return $this;
	}

	/**
	 * Build main functions
	 */

	/**
		INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
		    [INTO] tbl_name [(col_name,...)]
		    {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
		    [ ON DUPLICATE KEY UPDATE
		      col_name=expr
			[, col_name=expr] ... ]
	*/

	public function buildInsert() {

		$this->sql[] = 'INSERT';

		# $this->sql[] = 'LOW_PRIORITY';
		# $this->sql[] = 'IGNORE';

		$this->sql[] = 'INTO';

		$this->sql[] = $this->buildTableName();

		$this->sql[] = $this->buildInsertData();

		# $this->sql[] = 'ON DUPLICATE KEY UPDATE';

		$this->sqlQuery = implode(' ', $this->sql);
	}


	/**
		SELECT
		    [ALL | DISTINCT | DISTINCTROW ]
		      [HIGH_PRIORITY]
		      [STRAIGHT_JOIN]
		      [SQL_SMALL_RESULT] [SQL_BIG_RESULT] [SQL_BUFFER_RESULT]
		      [SQL_CACHE | SQL_NO_CACHE] [SQL_CALC_FOUND_ROWS]
		    select_expr [, select_expr ...]
		    [FROM table_references
		    [WHERE where_condition]
		    [GROUP BY {col_name | expr | position}
		      [ASC | DESC], ... [WITH ROLLUP]]
		    [HAVING where_condition]
		    [ORDER BY {col_name | expr | position}
		      [ASC | DESC], ...]
		    [LIMIT {[offset,] row_count | row_count OFFSET offset}]
		    [PROCEDURE procedure_name(argument_list)]
		    [INTO OUTFILE 'file_name'
			[CHARACTER SET charset_name]
			export_options
		      | INTO DUMPFILE 'file_name'
		      | INTO var_name [, var_name]]
		    [FOR UPDATE | LOCK IN SHARE MODE]]
	*/

	public function buildSelect() {

		$this->sql[] = 'SELECT';

		# $this->sql[] = 'ALL';
		# $this->sql[] = 'HIGH_PRIORITY';
		# $this->sql[] = 'STRAIGHT_JOIN';

		$this->sql[] = $this->buildFields();
		$this->sql[] = 'FROM';
		$this->sql[] = $this->buildTableName();

		if (!empty($this->collection['join'])) {
			$this->sql[] = $this->buildJoin();	
		}

		if (!empty($this->collection['where'])) {
			$this->sql[] = $this->buildWhere();
		}

		if (!empty($this->collection['group'])) {
			$this->sql[] = $this->buildGroup();
		}

		if (!empty($this->collection['having'])) {
			$this->sql[] = $this->buildHaving();
		}
		
		if (!empty($this->collection['order'])) {
			$this->sql[] = $this->buildOrder();
		}

		if (!empty($this->collection['limit'])) {
			$this->sql[] = $this->buildLimit();
		}

		$this->sqlQuery = implode(' ', $this->sql);

	}

	/**
	UPDATE [LOW_PRIORITY] [IGNORE] table_reference
	    SET col_name1={expr1|DEFAULT} [, col_name2={expr2|DEFAULT}] ...
	    [WHERE where_condition]
	    [ORDER BY ...]
	    [LIMIT row_count]
	*/

	public function buildUpdate() {

		$this->sql[] = 'UPDATE';

		# $this->sql[] = 'LOW_PRIORITY';
		# $this->sql[] = 'IGNORE';

		$this->sql[] = $this->buildTableName();
		$this->sql[] = $this->buildSet();
		$this->sql[] = $this->buildWhere();

		if (!empty($this->collection['order'])) {
			$this->sql[] = $this->buildOrder();
		}

		if (!empty($this->collection['limit'])) {
			$this->sql[] = $this->buildLimit();
		}

		$this->sqlQuery = implode(' ', $this->sql);

	}

	/**
	DELETE [LOW_PRIORITY] [QUICK] [IGNORE] FROM tbl_name
	    [WHERE where_condition]
	    [ORDER BY ...]
	    [LIMIT row_count]
	*/


	public function buildDelete() {

		$this->sql[] = 'DELETE';
		
		# $this->sql[] = 'LOW_PRIORITY';
		# $this->sql[] = 'QUICK';
		# $this->sql[] = 'IGNORE';

		$this->sql[] = 'FROM';
		$this->sql[] = $this->buildTableName();
		$this->sql[] = $this->buildWhere();

		if (!empty($this->collection['order'])) {
			$this->sql[] = $this->buildOrder();
		}

		if (!empty($this->collection['limit'])) {
			$this->sql[] = $this->buildLimit();
		}

		$this->sqlQuery = implode(' ', $this->sql);
	}

	/**
	 * Sub builds
	 */

	public function buildTableName() {
		return self::$quoteName . $this->collection['table'] . self::$quoteName;
	}

	public function buildInsertData($data) {

		$insert = array();

		$fields = array_keys($this->collection['data'][0]);
		array_walk($fields, 'self::prepareFields');

		$insert[] = '(' . implode(',', $fields) . ')';
		$insert[] = 'VALUES';

		$count = sizeof($this->collection['data']);

		$data = array();
		for ($i = 0; $i < $count; $i++) {

			$values = array_values($this->collection['data'][$i]);
			array_walk($values, 'self::prepareValues');
			$data[] = '(' . implode(', ', $values) . ')';
		}

		$insert[] = implode(', ', $data);

		return implode(' ', $insert);
	}


	public function buildFields() {
		
		$num	= 0;
		$args	= array();
		
		if (!empty($this->collection['fields'])) {

			$num = sizeof ($this->collection['fields']);

			if ($num == 1) {
				$args = $this->collection['fields'][0];
			} elseif ($num > 1) {
				for ($i = 0; $i < $num; $i++) {
					$args = array_merge($args, $this->collection['fields'][$i]);
				}
			}
		
			$count = sizeof ($args);

			if ($count) {
				$fields = array();
				for ($i = 0; $i < $count; $i++) {
					if (is_string($args[$i])) {
						$fields[] = self::prepareFields($args[$i]);
					} elseif (is_array($args[$i])) {
						foreach ($args[$i] as $field => $as_name) {
							$fields[] = self::prepareFields($field) . ' AS ' . self::prepareFields($as_name);
						}
					} elseif (is_object($args[$i])) {
						$fields[] = $args[$i]->scalar;
					}
				}
				return implode(', ', $fields);
			}

		} else {
			return '*';
		}

	}

	public function buildJoin() {

		$count = sizeof ($this->collection['join']);

		if ($count) {
			$join = array();
			$args = $this->collection['join'];
			for ($i = 0; $i < $count; $i++) {

				$type = '';
				if (isset($args[$i][1])) {
					$type = $args[$i][1] . ' ';
				}

				$as = '';
				if (isset($args[$i][2])) {
					$as = ' AS ' . $args[$i][2] . ' ';
				}

				if (isset($this->collection['on'][$i])) {
					$on = $this->collection['on'][$i];

					if (!is_array($on[0])) {
						$on = array($on);
					}

					$where = array();
					foreach ($on as $_on) {
						$where[] = self::prepareFields($_on[0]) . ' ' . $_on[1] . ' ' . self::prepareFields($_on[2]);
					}

					$append = ' ON (' . implode(' AND ', $where) . ')';
				}

				$join[] = $type . 'JOIN ' . self::prepareFields($args[$i][0]) . $as . $append;
			}

			return implode(' ', $join);
		}
	}


	public function buildSet() {

		$set[] = 'SET';
		if (!empty($this->collection['set'])) {
			foreach ($this->collection['set'] as $values) {
				if (!is_array($values[0])) {
					$values[0] = array($values[0] => $values[1]);
				}

				foreach ($values[0] as $field => $value) {
					$sets[] = self::prepareFields($field) . ' = ' . self::prepareValues($value);
				}
			}

			$set[] = implode(', ', $sets);
		}

		return implode(' ', $set);
	}

	public function buildWhere() {
		
		$where[] = 'WHERE';

		$i = 0;
		foreach ($this->collection['where'] as $where_) {

			$sub = array();

			if (is_array($where_)) {
				foreach ($where_ as $key => $w) {

					if ($w[1] !== null) {
						self::prepareValues($w[2]);
					}

					if ($i > 0) {
						$key = $key . ' ';
					} else {
						$key = '';
					}

					$sub[] = $key . self::prepareFields($w[0]) . ' ' . $w[1] . ' ' . $w[2];
				}
				$i++;
			} else {
				$sub[] = $where_;
				$i = 0;
			}

			$where[] = implode(' ', $sub);

		}

		return implode(' ', $where);
	}

	public function buildHaving() {
		
		$having[] = 'HAVING';

		foreach ($this->collection['having'] as $h) {
			$having_[] = self::prepareFields($h[0]) . ' ' . $h[1] . ' ' . self::prepareValues($h[2]);
		}

		if (!empty($having_)) {
			$having[] = implode(' AND ', $having_);
		}

		return implode(' ', $having);
	}

	public function buildGroup() {

		$group[] = 'GROUP BY';

		if (!empty($this->collection['group'])) {
			$group_ = array();
			foreach ($this->collection['group'] as $g) {
				$group_[] = self::prepareFields($g[0]) . ((isset($g[1]))? ' '. $g[1] : '');
			}
			$group[] = implode(', ', $group_);
		}

		return implode(' ', $group);
	}

	public function buildOrder() {

		$order[] = 'ORDER BY';

		if (!empty($this->collection['order'])) {
			$orders = array();
			foreach ($this->collection['order'] as $o) {
				$orders[] = self::prepareFields($o[0]) . ((isset($o[1]))? ' '. $o[1] : '');
			}
			$order[] = implode(', ', $orders);
		}

		return implode(' ', $order);
	}

	public function buildLimit() {
		$limit[] = 'LIMIT';
		$limit[] = (int) $this->collection['limit'];

		if ($this->collection['offset']) {
			$limit[] = 'OFFSET';
			$limit[] = (int) $this->collection['offset'];
		}

		return implode(' ', $limit);
	}



	public function collection($key) {
		if (!empty($this->collection[$key])) {
			return $this->collection[$key];
		}
	}

	public function build() {

		if ($this->type === self::INSERT) {
			$this->buildInsert();
		} elseif ($this->type === self::SELECT) {
			$this->buildSelect();
		} elseif ($this->type === self::UPDATE) {
			$this->buildUpdate();
		} elseif ($this->type === self::DELETE) {
			$this->buildDelete();
		}

		return $this->sqlQuery . ';';
	}


	public function sql() {
		return $this->build();
	}

        private static function prepareFields(&$data) {
		$parts = explode ('.', $data);
		if (sizeof ($parts) > 1) {
			$data  = self::$quoteName . $parts[0] . self::$quoteName . '.';
			if ($parts[1] !== '*') {
				$data .= self::$quoteName . $parts[1] . self::$quoteName;
			} else {
				$data .= '*';
			}
		} else {
			$data  = self::$quoteName . $data . self::$quoteName;
		}
                return $data;
        }

        private static function prepareValues(&$data) {
                $data = self::$quoteVar . $data . self::$quoteVar;
                return $data;
        }
}
