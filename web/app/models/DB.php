<?php

class DB {
    public static mysqli $conn;
    public static array $cache = [];
    public static bool $in_transaction = false;
	
	const WLOCK = "WRITE";
    const RLOCK = "READ";

    const ASSOC = MYSQLI_ASSOC;
    const NUM = MYSQLI_NUM;
    const BOTH = MYSQLI_BOTH;
	
	public static function init() {
		$server = UOJConfig::$data['database']['host'];
		$username = UOJConfig::$data['database']['username'];
		$password = UOJConfig::$data['database']['password'];
		$dbname = UOJConfig::$data['database']['database'];
		DB::$conn = new mysqli($server, $username, $password, $dbname);
		if (DB::$conn->connect_error) {
			UOJLog::error('database initialization failed: '. DB::$conn->connect_error);
			die('There is something wrong with the database >_<... Connection failed');
		}
		if (!DB::$conn->set_charset("utf8mb4")) {
		    UOJLog::error("database initialization failed: Error loading character set utf8. " . DB::$conn->error);
		    die('There is something wrong with the database >_<.... Charset utf8 not supported');
		}
	}
	
	// lc: local cache
	public static function lc() {
		return new DBUseLocalCache('');
	}
	
	public static function escape($str) {
        return DB::$conn->real_escape_string($str);
    }
    public static function raw($str) {
        return new DBRawString($str);
    }
    public static function rawbracket($q) {
		return DB::raw(DB::bracket($q));
    }
    public static function rawvalue($str) {
        return DB::raw(DB::value($str));
    }
    public static function rawtuple(array $vals) {
        return DB::raw(DB::tuple($vals));
    }
    public static function call($fun, ...$args) {
        return DB::raw("{$fun}(".implode(',', array_map('DB::value', $args)).')');
    }
    public static function now() {
        return DB::call('now');
    }
    public static function instr($str, $substr) {
        return DB::call('instr', $str, $substr);
    }
    public static function cast_as_json($value) {
        return DB::raw('cast('.DB::value($value).' as json)');
    } 
    public static function json_set($json_doc, ...$args) {
        return DB::call('json_set', DB::raw($json_doc), ...$args);
    }
    public static function json_insert($json_doc, ...$args) {
        return DB::call('json_insert', DB::raw($json_doc), ...$args);
    }
    public static function json_replace($json_doc, ...$args) {
        return DB::call('json_replace', DB::raw($json_doc), ...$args);
    }
    public static function json_remove($json_doc, ...$args) {
        return DB::call('json_remove', DB::raw($json_doc), ...$args);
    }
    public static function json_array_append($json_doc, ...$args) {
        return DB::call('json_array_append', DB::raw($json_doc), ...$args);
    }
    public static function json_array_insert($json_doc, ...$args) {
        return DB::call('json_array_insert', DB::raw($json_doc), ...$args);
    }
    public static function json_unquote($json_doc) {
        return DB::call('json_unquote', DB::raw($json_doc));
    }

	public static function table($table) {
        //return '`'.str_replace('`', '``', $table).'`';
        return $table;
	}
    public static function fields($fields) {
        if (is_assoc($fields)){
            $new_fields = [];
            foreach ($fields as $name => $val) {
                if (is_int($name)) {
                    $new_fields[] = $val;
                } else {
                    $new_fields[] = DB::field_as($val, $name);
                }
            }
            $fields = $new_fields;
        }
        return implode(',', $fields);
    }
    public static function bracketed_fields($fields) {
        return '('.DB::fields($fields).')';
    }
    public static function value($str) {
        if ($str === null) {
            return 'NULL';
        } else if ($str === true) {
            return 'true';
        } else if ($str === false) {
            return 'false';
        } else if (is_int($str)) {
            return $str;
        } else if (is_string($str)) {
            return '\''.DB::escape($str).'\'';
        } else if ($str instanceof DBRawString) {
            return $str->str;
        } else {
            return false;
        }
    }
    public static function field_as($field, $name) {
        return "{$field} as {$name}";
    }
    public static function value_as($value, $name) {
        return DB::value($value)." as {$name}";
    }

    public static function if_func($conds, $val1, $val2) {
        return 'if('.DB::conds($conds).','.DB::value($val1).','.DB::value($val2).')';
    }
    
    public static function setValue($field, $val) {
		return $field.' = '.DB::value($val);
    }
	public static function setValues(array $arr) {
        $all = [];
        foreach ($arr as $key => $val) {
        	if (is_int($key)) {
        		$all[] = $val;
        	} else {
            	$all[] = DB::setValue($key, $val);
            }
        }
		return implode(', ', $all);
    }

	public static function cond($cond) {
		if (is_array($cond)) {
            if (count($cond) == 3) {
                $lhs = $cond[0] instanceof DBRawString ? $cond[0]->str : $cond[0];
                $op = $cond[1];
                $rhs = DB::value($cond[2]);
                return $lhs.' '.$op.' '.$rhs;
            } else {
                return false;
            }
        }
		return $cond;
    }
    public static function conds($conds) {
        return is_array($conds) ? DB::land($conds) : $conds;
    }
	public static function land(array $conds) {
        if (is_assoc($conds)) {
            $new_conds = [];
            foreach ($conds as $key => $val) {
                if (is_int($key)) {
                    $new_conds[] = $val;
                } else {
                    if ($val !== null) {
                        $new_conds[] = [$key, '=', $val];
                    } else {
                        $new_conds[] = [$key, 'is', $val];
                    }
                }
            }
            $conds = $new_conds;
        }
		return '('.implode(' and ', array_map('DB::cond', $conds)).')';
	}
	public static function lor(array $conds) {
        if (is_assoc($conds)) {
            $new_conds = [];
            foreach ($conds as $key => $val) {
                if (is_int($key)) {
                    $new_conds[] = $val;
                } else {
                    if ($val !== null) {
                        $new_conds[] = [$key, '=', $val];
                    } else {
                        $new_conds[] = [$key, 'is', $val];
                    }
                }
            }
            $conds = $new_conds;
        }
		return '('.implode(' or ', array_map('DB::cond', $conds)).')';
    }
	public static function tuple(array $vals) {
		$str = '(';
		$first = true;
		foreach ($vals as $val) {
			if (!$first) {
				$str .= ',';
			} else {
				$first = false;
			}
 			$str .= DB::value($val);
		}
		$str .= ')';
		return $str;
	}
    public static function tuples(array $tuples) {
        $all = [];
        foreach ($tuples as $vals) {
            $all[] = DB::tuple($vals);
        }
        return implode(', ', $all);
    }
	
	public static function fetch($res, $opt = DB::ASSOC) {
		return $res->fetch_array($opt);
	}

	public static function bracket($q) {
		return '('.DB::query_str($q).')';
    }
	public static function query_str($q) {
		if (is_array($q)) {
            $last = '';
            $qn = 0;
			$use_local_cache = false;
            foreach ($q as $val) {
                if (is_array($val)) {
                    if ($last !== 'set' && $last !== "on duplicate key update") {
                        $val = DB::land($val);
                    } else {
                        $val = DB::setValues($val);
                    }
                } elseif ($val instanceof DBUseLocalCache) {
					$use_local_cache = true;
					$val = $val->str;
				}
                $last = $val;
                if ($val !== '') {
                    $q[$qn++] = $val;
                }
            }
            array_splice($q, $qn);
			$q = implode(' ', $q);
			if ($use_local_cache) {
				$q = new DBUseLocalCache($q);
			}
			return $q;
		}
		return $q;
	}
	
	public static function exists($q) {
		return 'exists '.DB::bracket($q);
	}
	
	public static function query($q) {
		return DB::$conn->query(DB::query_str($q));
	}
	public static function update($q) {
		$ret = DB::$conn->query(DB::query_str($q));
		if ($ret === false) {
			UOJLog::error(DB::query_str($q));
			UOJLog::error('update failed: '.DB::$conn->error);
		}
		return $ret;
	}
	public static function insert($q) {
		$ret = DB::$conn->query(DB::query_str($q));
		if ($ret === false) {
			UOJLog::error(DB::query_str($q));
			UOJLog::error('insert failed: '.DB::$conn->error);
		}
		return $ret;
	}
	public static function insert_id() {
		return DB::$conn->insert_id;
	}
	
	public static function delete($q) {
		$ret = DB::$conn->query(DB::query_str($q));
		if ($ret === false) {
			UOJLog::error(DB::query_str($q));
			UOJLog::error('delete failed: '.DB::$conn->error);
		}
		return $ret;
	}
	public static function select($q) {
		$q = DB::query_str($q);
		if ($q instanceof DBUseLocalCache) {
			$q = $q->str;
			$use_local_cache = true;
		} else {
			$use_local_cache = false;
		}
		if ($use_local_cache && isset(DB::$cache[$q])) {
            $res = DB::$cache[$q];
            $res->data_seek(0);
            $res->field_seek(0);
			return $res;
		}
		$res = DB::$conn->query($q);
		if ($use_local_cache) {
			DB::$cache[$q] = $res;
		}
		if ($res === false) {
			UOJLog::error($q);
			UOJLog::error(DB::$conn->error);
        }
		return $res;
	}
	public static function selectAll($q, $opt = DB::ASSOC) {
        $qres = DB::select($q);
        if ($qres === false) {
            return false;
        }
		// return $qres->fetch_all($opt);   not supported
        
		$res = [];
		while ($row = $qres->fetch_array($opt)) {
			$res[] = $row;
		}
		return $res;
	}
	public static function selectFirst($q, $opt = DB::ASSOC) {
        $res = DB::select($q);
        if ($res === false) {
            return false;
        }
		return $res->fetch_array($opt);
    }
    public static function selectSingle($q) {
        $res = DB::select($q);
        if ($res === false) {
            return false;
        }
        $row = $res->fetch_row();
        if (!$row) {
            return false;
        }
        return $row[0];
    }

    /**
     * perform SQL query $q in the form of select count(*) from XXX where XXX;
    */
	public static function selectCount($q) {
        $res = DB::select($q);
        if ($res === false) {
            return false;
        }
		list($cnt) = $res->fetch_row();
		return $cnt;
    }

    /**
     * perform SQL query: select exists ($q);
     * 
     * on success, returns 0 or 1
     * on failure, returns false
     * 
     * @return int|false
    */
    public static function selectExists($q) {
        $res = DB::select(["select", DB::exists($q)]);
        if ($res === false) {
            return false;
        }
        return (int)($res->fetch_row()[0]);
    }
    
    public static function limit() {
        $num = func_get_args();
        if (count($num) == 1) {
        	return "limit ".((int)$num[0]);
        } else if (count($num) == 2) {
            return "limit ".((int)$num[0]).",".((int)$num[1]);
        } else {
            return false;
        }
    }

    public static function for_share() {
        return "for share";
    }
    public static function for_update() {
        return "for update";
    }

    public static function startTransaction() {
        return DB::$conn->begin_transaction();
    }

    public static function rollback() {
        return DB::$conn->rollback();
    }

    public static function commit() {
        return DB::$conn->commit();
    }

    public static function transaction($func) {
        if (DB::$in_transaction) {
            $ret = $func();
        } else {
            DB::$in_transaction = true;
            DB::startTransaction();
            $ret = $func();
            DB::commit();
            DB::$in_transaction = false;
        }
        return $ret;
    }
	
	public static function lock($tables, $func) {
		$q = [];
		foreach ($tables as $table => $type) {
			if ($type != DB::WLOCK && $type != DB::RLOCK) {
				UOJLog::error('Unknown type: '.$type);
				return false;
			}
			$q[] = $table.' '.$type;
		}
		$q = 'lock tables '.implode(',', $q);
		DB::query($q);
		
		$ret = $func();
		
		DB::query("unlock tables");
		
		return $ret;
	}
	public static function checkTableExists($name) {
		return DB::select(["select 1 from", DB::table($name)]) !== false;
	}
	
	public static function num_rows($res) {
		return $res->num_rows;
	}
	public static function affected_rows() {
		return DB::$conn->affected_rows;
    }
}
