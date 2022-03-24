<?php

class UOJRanklist {
    public static function updateActiveUserList() {
        $latest_contest = DB::selectFirst([
            "select * from contests",
            "order by start_time desc",
            DB::limit(1)
        ]);
        if ($latest_contest === null) {
            return DB::update([
                "update user_info", "set", [
                    "extra" => DB::json_set('extra', '$.active_in_contest', false)
                ]
            ]);
        } else {
            $latest_contest = new UOJContest($latest_contest);
            $ddl = clone $latest_contest->info['end_time'];
            $duration_M = UOJContext::getMeta('active_duration_M');
            $ddl->modify("-{$duration_M} months");
            return DB::update([
                "update user_info", "set", [
                    "extra" => DB::json_set('extra', '$.active_in_contest',
                        DB::raw(DB::exists([
                            "select 1 from contests C join contests_registrants CR",
                            "on", [
                                ["C.start_time", ">=", UOJTime::time2str($ddl)],
                                "C.id = CR.contest_id",
                                "CR.has_participated" => true
                            ], "where", [
                                "CR.username = user_info.username",
                            ]
                        ]))
                    )
                ]
            ]);
        }
    }

    public static function printHTML($cfg = []) {
        $cfg += [
            'top10' => false,
            'active_users_only' => false
        ];

        if ($cfg['active_users_only']) {
            $conds = ['active_in_contest' => true];
        } else {
            $conds = '1';
        }

        $last_user = null;
        $print_row = function($user, $now_cnt) use(&$last_user, &$conds) {
            if ($last_user === null) {
                $rank = DB::selectCount([
                    "select count(*) from user_info",
                    "where", [
                        ["rating", ">", $user['rating']],
                        DB::conds($conds)
                    ]
                ]);
                $rank++;
            } else if ($user['rating'] == $last_user['rating']) {
                $rank = $last_user['rank'];
            } else {
                $rank = $now_cnt;
            }
            
            $user['rank'] = $rank;

            $userpro = HTML::url('/user/profile/'.$user['username']);
            $userlink = getUserLink($user['username'], $user['rating']);
		    $asrc = HTML::avatar_addr($user, 50);
            $esc_motto = HTML::escape($user['motto'], ['single_line' => true]);
            echo <<<EOD
            <div class="list-group-item">
                <div class="media">
                    <div class="media-left">
                        <a href="{$userpro}"><img class="media-object img-rounded" src="{$asrc}" /></a>
                    </div>
                    <div class="media-body">
                        <div class="row">
                            <h4 class="col-sm-8 media-heading">#{$user['rank']}: {$userlink}</h4>
                            <div class="col-sm-4 text-right"><strong>Rating: {$user['rating']}</strong></div>
                        </div>
                        <div class="uoj-text">{$esc_motto}</div>
                    </div>
                </div>
            </div>
            EOD;

            $last_user = $user;
        };


        $pag_config = [
            'get_row_index' => '',
            'table_name' => 'user_info',
            'col_names' => ['username', 'rating', 'email', 'motto', 'extra'],
            'cond' => $conds,
            'tail' => 'order by rating desc, username asc'
        ];
        
        if ($cfg['top10']) {
            $pag_config['tail'] .= ' limit 10';
            $pag_config['echo_full'] = '';
        } else {
            $pag_config['page_len'] = 100;
        }
        
        $pag = new Paginator($pag_config);

        echo '<div class="list-group">';
        foreach ($pag->get() as $idx => $row) {
            $print_row($row, $idx);
        }
        if ($pag->isEmpty()) {
            echo <<<EOD
            <div class="list-group-item">
            无
            </div>
            EOD;
        }
        echo '</div>';
        echo $pag->pagination();
    }

    /**
     * Old style of ranklist
     */
    public static function printTableHTML($cfg = []) {
        $cfg += [
            'top10' => false,
            'active_users_only' => false
        ];

        if ($cfg['active_users_only']) {
            $conds = ['active_in_contest' => true];
        } else {
            $conds = '1';
        }

        $header_row = '';
        $header_row .= '<tr>';
        $header_row .= '<th style="width: 5em;">#</th>';
        $header_row .= '<th style="width: 14em;">'.UOJLocale::get('username').'</th>';
        $header_row .= '<th style="width: 50em;">'.UOJLocale::get('motto').'</th>';
        $header_row .= '<th style="width: 5em;">'.UOJLocale::get('rating').'</th>';
        $header_row .= '</tr>';

        $last_user = null;
        $print_row = function($user, $now_cnt) use(&$last_user, &$conds) {
            if ($last_user === null) {
                $rank = DB::selectCount([
                    "select count(*) from user_info",
                    "where", [
                        ["rating", ">", $user['rating']],
                        DB::conds($conds)
                    ]
                ]);
                $rank++;
            } else if ($user['rating'] == $last_user['rating']) {
                $rank = $last_user['rank'];
            } else {
                $rank = $now_cnt;
            }
            
            $user['rank'] = $rank;
            
            echo '<tr>';
            echo '<td>' . $user['rank'] . '</td>';
            echo '<td>' . getUserLink($user['username']) . '</td>';
            echo '<td>' . HTML::escape($user['motto']) . '</td>';
            echo '<td>' . $user['rating'] . '</td>';
            echo '</tr>';
            
            $last_user = $user;
        };

        $col_names = ['username', 'rating', 'motto'];

        $tail = 'order by rating desc, username asc';

        $table_config = [
            'get_row_index' => ''
        ];
        
        if ($cfg['top10']) {
            $tail .= ' limit 10';
            $table_config['echo_full'] = '';
        } else {
            $table_config['page_len'] = 100;
        }
        
        echoLongTable($col_names, 'user_info', $conds, $tail, $header_row, $print_row, $table_config);
    }
}