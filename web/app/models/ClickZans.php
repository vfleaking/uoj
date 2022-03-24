<?php

class ClickZans {
	
	public static function getTable($type) {
		switch ($type) {
			case 'B':
				return 'blogs';
			case 'BC':
				return 'blogs_comments';
			case 'P':
				return 'problems';
			case 'C':
				return 'contests';
		}
		return null;
	}

	public static function canClickZan($id, $type, $user) {
		switch ($type) {
			case 'P':
				return UOJProblem::query($id)->userCanClickZan($user);
			default:
				return true;
		}
	}
	
	public static function query($id, $type, $user) {
		if ($user == null) {
			return 0;
		}
		$row = DB::selectFirst([
			"select val from click_zans",
			"where", [
				'username' => $user['username'],
				'type' => $type,
				'target_id' => $id
			]
		]);
		if ($row == null) {
			return 0;
		}
		return $row['val'];
	}

	public static function click($id, $type, $user, $delta) {
        if (!DB::$in_transaction) {
            return DB::transaction(fn() => ClickZans::click($id, $type, $user, $delta));
        }

		$table_name = ClickZans::getTable($type);
		
		$cur = ClickZans::query($id, $type, $user);
		
		$row = DB::selectFirst([
			"select zan from", DB::table($table_name),
			"where", ['id' => $id], DB::for_update()
		]);
		if (!$row) {
			return '<div class="text-danger">failed</div>';
		}
		
		if ($cur != $delta) {
			$cur += $delta;
			if ($cur == 0) {
				DB::delete([
					"delete from click_zans",
					"where", [
						'username' => Auth::id(),
						'type' => $type,
						'target_id' => $id
                    ]
				]);
			} else if ($cur != $delta) {
				DB::update([
					"update click_zans", 
					"set", ['val' => $cur],
					"where", [
						'username' => Auth::id(),
						'type' => $type,
						'target_id' => $id
					]
				]);
			} else {
				DB::insert([
					"insert into click_zans",
					"(username, type, target_id, val)", "values",
					DB::tuple([Auth::id(), $type, $id, $cur])
				]);
			}
			$cnt = $row['zan'] + $delta;
			DB::update([
				"update", DB::table($table_name),
				"set", ['zan' => $cnt],
				"where", ['id' => $id]
			]);
		} else {
			$cnt = $row['zan'];
		}
		
		return ClickZans::getBlock($type, $id, $cnt, $cur);
	}
	
	public static function getBlock($type, $id, $cnt, $val = null) {
		if ($val === null) {
			$val = ClickZans::query($id, $type, Auth::user());
		}
		return '<div class="uoj-click-zan-block" data-id="'.$id.'" data-type="'.$type.'" data-val="'.$val.'" data-cnt="'.$cnt.'"></div>';
	}
	
	public static function getCntBlock($cnt) {
		$cls = 'uoj-click-zan-block-';
		if ($cnt > 0) {
			$cls .= 'positive';
		} else if ($cnt < 0) {
			$cls .= 'negative';
		} else {
			$cls .= 'neutral';
		}
		return '<span class="'.$cls.'"><span class="uoj-click-zan-cnt">[<strong>' . ($cnt > 0 ? '+' . $cnt : $cnt) . '</strong>]</span></span>';
	}
}
