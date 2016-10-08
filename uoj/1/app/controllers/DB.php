<?php

class DB {
	public static function init() {
		global $uojMySQL;
		@$uojMySQL = mysql_connect(UOJConfig::$data['database']['host'] . ':3306', UOJConfig::$data['database']['username'], UOJConfig::$data['database']['password']);
		if (!$uojMySQL) {
			echo 'There is something wrong with database >_<.... ' . mysql_error();
			die();
		}
		mysql_select_db(UOJConfig::$data['database']['database']);
	}
	public static function escape($str) {
		return mysql_real_escape_string($str);
	}
	public static function fetch($r, $opt = MYSQL_ASSOC) {
		return mysql_fetch_array($r, $opt);
	}
	
	public static function query($q) {
		return mysql_query($q);
	}
	public static function update($q) {
		return mysql_query($q);
	}
	public static function insert($q) {
		return mysql_query($q);
	}
	public static function insert_id() {
		return mysql_insert_id();
	}
		
	public static function delete($q) {
		return mysql_query($q);
	}
	public static function select($q) {
		return mysql_query($q);
	}
	public static function selectAll($q, $opt = MYSQL_ASSOC) {
		$res = array();
		$qr = mysql_query($q);
		while ($row = mysql_fetch_array($qr, $opt)) {
			$res[] = $row;
		}
		return $res;
	}
	public static function selectFirst($q, $opt = MYSQL_ASSOC) {
		return mysql_fetch_array(mysql_query($q), $opt);
	}
	public static function selectCount($q) {
		list($cnt) = mysql_fetch_array(mysql_query($q), MYSQL_NUM);
		return $cnt;
	}
	
	public static function checkTableExists($name) {
		return DB::query("select 1 from $name") !== false;
	}
	
	public static function num_rows() {
		return mysql_num_rows();
	}
	public static function affected_rows() {
		return mysql_affected_rows();
	}
}
