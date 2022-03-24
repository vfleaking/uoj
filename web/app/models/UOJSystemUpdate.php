<?php

class UOJSystemUpdate {

    public static function updateProblem(UOJProblem $problem, $message, $time = null) {
        if ($time === null) {
            $time = DB::now();
        }

        DB::insert([
            'insert into system_updates',
            '(time, type, target_id, message)',
            'values', DB::tuple([
                $time, 'problem', $problem->info['id'], json_encode($message)
            ])
        ]);
    }
    
    public static function updateProblemInternally(UOJProblem $problem, $message, $time = null) {
        if ($time === null) {
            $time = DB::now();
        }

        DB::insert([
            'insert into system_updates',
            '(time, type, target_id, message)',
            'values', DB::tuple([
                $time, 'problem_internally', $problem->info['id'], json_encode($message)
            ])
        ]);
    }
}