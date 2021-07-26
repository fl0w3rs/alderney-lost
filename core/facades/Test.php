<?php

namespace App\Facades;

use \RedBeanPHP\R as R;

use App\Facades\Auth;

class Test {
    public static function haveUserAccessToTest($id, $row = null, $user_groups = null) {
        R::selectDatabase('main');

        if($row == null) {
            $row = R::getRow('SELECT * FROM tests WHERE id = ?', [$id]);
        }

        if(!isset($row['id'])) return false;

        if(Auth::isAdmin()) return true;

        if($row['is_open'] == 0) return false;

        if($user_groups == null) {
            $user_groups = Auth::getForumUserGroups();
        }

        $test_groups = explode(',', $row['groups']);
        if(count(array_intersect($test_groups, $user_groups)) == 0) {
            return false;
        }


        return true;
    }

    public static function canUserStartTest($id) {
        if(!self::haveUserAccessToTest($id)) return false;

        $user = Auth::getForumUser();

        R::selectDatabase('main');
        $utr = R::getRow('SELECT COUNT(*) as cnt FROM ut_results WHERE retest = 0 AND test_id = ? AND user_id = ?', [$id, $user['member_id']]);
        if($utr['cnt'] > 0) return false;

        return true;
    }

    public static function getCurrentTest() {
        $user = Auth::getForumUser();

        R::selectDatabase('main');
        $row = R::getRow('SELECT * FROM ut_results WHERE status = 1 AND user_id = ?', [$user['member_id']]);

        if(!isset($row['id'])) {
            return false;
        }

        return $row;
    }

    public static function generateEmbed($result, $test) {
        $percentage = number_format((int)$result['valid_answers'] / (int)$result['total_questions'] * 100, 1);
        $user = Auth::getForumUser();
        return [
            [
                "name" => "Участник",
                "value" => $user['name'],
                "inline" => true
            ],
            [
                "name" => "Тест",
                "value" => $test['name'],
                "inline" => true
            ],
            [
                "name" => "IP",
                "value" => $_SERVER['REMOTE_ADDR'],
                "inline" => true
            ],
            [
                "name" => "Правильных ответов",
                "value" => $result['valid_answers']. " из " . $result['total_questions'],
                "inline" => true
            ],
            [
                "name" => "Набранный процент",
                "value" => $percentage."%",
                "inline" => true
            ],
            [
                "name" => "Проходной процент",
                "value" => $test['pass_percent']."%",
                "inline" => true
            ]
        ];
    }

    public static function updateCurrentTestStatus($status, $fail_reason = '') {
        $test = self::getCurrentTest();

        R::selectDatabase('main');
        R::exec('UPDATE ut_results SET end_time = ?, status = ?, fail_reason = ? WHERE id = ?', [time(), $status, $fail_reason, $test['id']]);

        return true;
    }

    public static function updateEndTime($id, $time = null) {
        if($time == null) $time = time();
        
        R::selectDatabase('main');
        
        $result = R::load('ut_results', $id);

        if (!$result->id) return false;

        $result->end_time = $time;

        R::store($result);
    }

    public static function calcResult($id) {
        R::selectDatabase('main');

        $result = R::load('ut_results', $id);

        if (!$result->id) return false;

        $test = R::getRow('SELECT * FROM tests WHERE id = ?', [$result->test_id]);

        $percentage = number_format((int)$result->valid_answers / (int)$result->total_questions * 100, 1);
        if($percentage >= (float)$test['pass_percent']) {
            $result->status = 2;
        } else {
            $result->status = 3;
        }

        R::store($result);
    }

    public static function startTest($id) {
        if(!self::canUserStartTest($id)) return false;


        $user = Auth::getForumUser();

        R::selectDatabase('main');

        $test = R::getRow('SELECT * FROM tests WHERE id = ?', [$id]);

        $questions = R::getAssocRow('SELECT * FROM questions WHERE test_id = ? ORDER BY RAND() LIMIT ' . $test['question_count'], [$id]);

        $question_array = [];
        foreach($questions as $v) {
            $question_array[] = $v['id'];
        }

        if(count($question_array) == 0) return false;

        $result = R::xdispense('ut_results');

        $result->test_id = $test['id'];
        $result->user_id = $user['member_id'];
        $result->valid_answers = 0;
        $result->questions = implode(',', $question_array);
        $result->total_questions = count($question_array);
        $result->last_keepalive = time() + 15;
        $result->current_question = 0;
        $result->start_time = time();
        $result->end_time = -1;
        $result->status = 1;
        $result->retest = 0;
        
        $id = R::store($result);


        return $id;
    }
}