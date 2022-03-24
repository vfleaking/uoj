<?php
	requirePHPLib('judger');
	requirePHPLib('svn');
	
	if (!authenticateJudger()) {
		UOJResponse::page404();
	}
    
    $res = DB::selectAll([
        "select * from judger_info",
        "where", [
            ["ip", "!=", ""],
            "enabled" => true
        ]
    ]);
	foreach ($res as $judger) {
		$socket = fsockopen($judger['ip'], UOJConfig::$data['judger']['socket']['port']);
		if ($socket === false) {
			die("judge client {$judger['ip']} lost.");
		}
		fwrite($socket, json_encode([
			'password' => UOJConfig::$data['judger']['socket']['password'],
			'cmd' => 'update'
		]));
		fclose($socket);
	}
	
	die("ok");
?>
