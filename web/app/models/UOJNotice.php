<?php

class UOJNotice {
    public static function getActiveContestConditions() {
        $conds = [
            ["C.status", "!=", "finished"],
            ["C.start_time", "<=", DB::now()]
        ];
        if (!isSuperUser(Auth::user())) {
            $conds[] = DB::lor([
                DB::exists([
                    "select 1 from contests_registrants",
                    "where", [
                        "contest_id = C.id",
                        "username" => Auth::id(),
                        "has_participated" => 1
                    ]
                ]),
                DB::exists([
                    "select 1 from contests_permissions",
                    "where", [
                        "contest_id = C.id",
                        "username" => Auth::id()
                    ]
                ])
            ]);
        }
        return $conds;
    }

    public static function shouldConstantlyCheckNotice() {
        if (!Auth::check()) {
            return false;
        }
        return DB::selectFirst([
            "select 1 from contests C",
            "where", static::getActiveContestConditions()
        ]) != null;
    }

    public static function fetch(DateTime $last_time = null) {
        if ($last_time === null)  {
            return null;
        }
        if (!UOJNotice::shouldConstantlyCheckNotice()) {
            return null;
        }
        $last_time_str = UOJTime::time2str($last_time);

        $active_ids = DB::rawbracket([
            "select C.id from contests C",
            "where", static::getActiveContestConditions()
        ]);
		$res = DB::selectAll([
            "select title, content from contests_notice",
            "where", [
                ["contest_id", "in", $active_ids],
                ["time", ">", $last_time_str]
            ],
            "order by time desc limit 10"
        ]);
		$ch = [];
        foreach ($res as $row) {
            $ch[] = $row['title'].': '.$row['content'];
        }
        $res = DB::selectAll([
            "select * from contests_asks",
            "where", [
                ["contest_id", "in", $active_ids],
                "username" => Auth::id(),
                ["reply_time", ">", $last_time_str]
            ],
            "order by reply_time desc limit 10"
        ]);
        foreach ($res as $row) {
            $ch[] = $row['question'].': '.$row['answer'];
        }
        return ['msg' => $ch, 'time' => UOJTime::$time_now_str];
    }

    public static function printJS() {
        echo '$(document).ready(function() {checkNotice("', UOJTime::$time_now_str, '")});';
    }
}