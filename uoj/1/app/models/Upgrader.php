<?php

class Upgrader {
	public static function upgraderRoot() {
		return UOJContext::documentRoot().'/app/upgrade';
	}
		
	public static function runSQL($filename) {
		passthru('mysql '.escapeshellarg(UOJConfig::$data['database']['database'])
		        .' -u '.escapeshellarg(UOJConfig::$data['database']['username'])
		        .' --password='.escapeshellarg(UOJConfig::$data['database']['password'])
		        .'<'.escapeshellarg($filename), $ret);
		if ($ret !== 0) {
			die("run sql failed: ".HTML::escape($filename)."\n");
		}
	}
	public static function runShell($cmd) {
		passthru("$cmd", $ret);
		if ($ret !== 0) {
			die("run shell failed: ".HTML::escape($cmd)."\n");
		}
	}
	
	public static function transaction($fun) {
		if (!DB::checkTableExists('upgrades')) {
			self::runSQL(self::upgraderRoot().'/create_table_upgrades.sql');
			echo "table upgrades created.\n";
		}
		DB::query('LOCK TABLES upgrades WRITE');
		
		$fun();
		
		DB::query('UNLOCK TABLES');
	}
	
	public static function getStatus($name) {
		$u = DB::selectFirst("select * from upgrades where name = '".DB::escape($name)."'");
		if ($u) {
			return $u['status'];
		} else {
			return 'down';
		}
	}
	
	public static function upgrade($name, $type) {
		if ($type != 'up' && $type != 'down') {
			die("invalid upgrade type\n");
		}
		
		echo $type.' '.HTML::escape($name).': ';
		
		$dir = self::upgraderRoot().'/'.$name;
		
		if (!is_dir($dir)) {
			die("invalid upgrade name\n");
		}
		
		if (self::getStatus($name) == $type) {
			echo "OK\n";
			return;
		}
		
		if (is_file($dir.'/upgrade.php')) {
			$fun = include $dir.'/upgrade.php';
			$fun($type);
		}
		if (is_file($dir.'/'.$type.'.sql')) {
			self::runSQL($dir.'/'.$type.'.sql');
		}
		if (is_file($dir.'/upgrade.sh')) {
			self::runShell('/bin/bash'.' '.escapeshellarg($dir.'/upgrade.sh').' '.$type);
		}
		
		DB::insert("insert into upgrades (name, status, updated_at) values ('".DB::escape($name)."', '$type', now()) on duplicate key update status = '$type', updated_at = now()");
		
		echo "DONE\n";
	}
	public static function up($name) {
		self::upgrade($name, 'up');
	}
	public static function down($name) {
		self::upgrade($name, 'down');
	}
	public static function refresh($name) {
		self::upgrade($name, 'down');
		self::upgrade($name, 'up');
	}
	public static function remove($name) {
		self::upgrade($name, 'down');
		DB::delete("delete from upgrades where name = '".DB::escape($name)."'");
	}
	public static function removeAll() {
		$names = [];
		foreach (DB::selectAll("select * from upgrades") as $u) {
			$names[] = $u['name'];
		}
		natsort($names);
		foreach (array_reverse($names) as $name) {
			self::upgrade($name, 'down');
		}
		DB::delete("delete from upgrades");
	}
	
	public static function upgradeToLatest() {
		$names = array_filter(scandir(self::upgraderRoot()), function ($name) {
			return is_dir(self::upgraderRoot().'/'.$name) && preg_match('/^\d+_[a-zA-Z_]+$/', $name);
		});
		
		natsort($names);
		
		$names_table = [];
		foreach ($names as $name) {
			$names_table[$name] = true;
		}
		
		$dres = DB::selectAll("select * from upgrades");
		foreach ($dres as $u) {
			if (!isset($names_table[$u['name']])) {
				die('missing: '.HTML::escape($name)."\n");
			}
		}
		
		foreach ($names as $name) {
			self::up($name);
		}
	}
}
