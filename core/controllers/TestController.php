<?php

namespace App\Controllers;

use \RedBeanPHP\R as R;

use App\Facades\Test;
use App\Facades\Auth;
use App\Facades\Discord;

use Rakit\Validation\Validator;

class TestController extends BaseController {
    public function renderStartTest($params) {

        if(!Test::canUserStartTest($params)) {
            $errc = new ErrorController();
            return $errc->renderError('NOACCESS', 'Вы не можете пройти этот тест');
        }

        $this->params['test'] = R::getRow('SELECT * FROM tests WHERE id = ?', [$params]);

        return $this->render('test_start.twig');
    }

    public function renderTest($id = null) {
        R::selectDatabase('main');

        if($id == null) $test = Test::getCurrentTest();
        else $test = R::getRow('SELECT * FROM ut_results WHERE id = ?', [$id]);

        $this->params['test'] = $test;

        $this->params['test_real'] = R::getRow('SELECT * FROM tests WHERE id = ?', [$test['test_id']]);

        $questions = explode(',', $this->params['test']['questions']);
        $question_id = $questions[(int)$this->params['test']['current_question']];

        $this->params['question'] = R::getRow('SELECT * FROM questions WHERE id = ?', [$question_id]);

        // $this->params['question']['text'] = htmlspecialchars_decode

        return responseJson(['status' => 'success', 'test_status' => $this->params['test']['status'], 'content' => $this->render('test_progress.twig')]);
    }

    public function apiSendAnswer() {

        $test = Test::getCurrentTest();
        if($test == false) {
            $errc = new ErrorController();
            return $errc->renderError('NOTEST', 'На данный момент вы не проходите тест');
        }
        
        $result_link = config['base_link'] . '/admin/result/' . $test['id'];

        $tmp_test = R::getRow('SELECT * FROM tests WHERE id = ?', [$test['test_id']]);
        if(time() - $test['start_time'] > $tmp_test['time']) {
            $embed = Test::generateEmbed($test, $tmp_test);

            $percentage = number_format((int)$test['valid_answers'] / (int)$test['total_questions'] * 100, 1);
            if($percentage >= (float)$tmp_test['pass_percent']) {
                Discord::sendMessage('Тестирование пройдено (' . $result_link . ')', $embed);
                Test::updateCurrentTestStatus(2);
            } else {
                Discord::sendMessage('Тестирование провалено (' . $result_link . ')', $embed);
                Test::updateCurrentTestStatus(3);
            }
            return $this->renderTest($test['id']);
        }

        // if(time() - (int)$test['last_keepalive'] > 5) {
        //     $embed = Test::generateEmbed($test, $tmp_test);

        //     Discord::sendMessage('Не отправлен keepalive запрос (возможно закрыл страницу обходным способом/пропал интернет) (' . $result_link . ')', $embed);
        //     Test::updateCurrentTestStatus(-1, 'Не отправлен KA запрос');
        //     return $this->renderTest($test['id']);
        // }

        $questions = explode(',', $test['questions']);
        $question_id = $questions[(int)$test['current_question']];

        $question = R::getRow('SELECT * FROM questions WHERE id = ?', [$question_id]);

        if($question['type'] == 1) {
            $input_type = 'integer';
        } else if($question['type'] == 2) {
            $input_type = 'array';
        } else if($question['type'] == 3) {
            $input_type = 'min:3';
        }

        $validator = new Validator;

        $validation = $validator->make($_POST, [
            'answer' => 'required|' . $input_type
        ]);

        $validation->setAliases([
            'answer' => 'Ответ'
        ]);

        $validation->validate();

        if ($validation->fails()) {
            return false;
        }

        $answer = R::xdispense('ut_answers');
        $answer->ut_result_id = $test['id'];
        $answer->question_id = $question['id'];
        if($question['type'] == 1) {
            $answer->answers = (int)$_POST['answer'];
            if($_POST['answer'] == $question['answers']) {
                $answer->is_valid = true;
            } else {
                $answer->is_valid = false;
            }
        } else if($question['type'] == 2) {
            $answer->answers = implode(',', $_POST['answer']);
            if(array_diff(explode(',', $question['answers']), $_POST['answer']) == [] && array_diff($_POST['answer'], explode(',', $question['answers'])                                         ) == []) {
                $answer->is_valid = true;
            } else {
                $answer->is_valid = false;
            }
        } else if($question['type'] == 3) {
            $answer->answers = $_POST['answer'];
            $answer->is_valid = false;
        }
        R::store($answer);

        if($answer['is_valid'] == true) {
            R::exec('UPDATE ut_results SET valid_answers = valid_answers + 1 WHERE id = ?', [$test['id']]);
            $test['valid_answers']++;
        }

        if(((int)$test['current_question'] + 1) >= (int)$test['total_questions']) {
            $embed = Test::generateEmbed($test, $tmp_test);

            $percentage = number_format((int)$test['valid_answers'] / (int)$test['total_questions'] * 100, 1);
            if($percentage >= (float)$tmp_test['pass_percent']) {
                Discord::sendMessage('Тестирование пройдено (' . $result_link . ')', $embed);
                Test::updateCurrentTestStatus(2);
                return $this->renderTest($test['id']);
            } else {
                Discord::sendMessage('Тестирование провалено (' . $result_link . ')', $embed);
                Test::updateCurrentTestStatus(3);
                return $this->renderTest($test['id']);
            }
        } else {
            R::exec('UPDATE ut_results SET current_question = current_question + 1 WHERE id = ?', [$test['id']]);
        }

        return $this->renderTest($test['id']);
    }

    public function apiKeepAlive() {
        R::selectDatabase('main');

        $test = Test::getCurrentTest();
        if($test == false) {
            $errc = new ErrorController();
            return $errc->renderError('NOTEST', 'На данный момент вы не проходите тест');
        }

        $tmp_test = R::getRow('SELECT * FROM tests WHERE id = ?', [$test['test_id']]);

        $percentage = number_format((int)$test['valid_answers'] / (int)$test['total_questions'] * 100, 1);

        $embed = Test::generateEmbed($test, $tmp_test);

        $result_link = config['base_link'] . '/admin/result/' . $test['id'];
        if(time() - $test['start_time'] > $tmp_test['time']) {
            
            $percentage = number_format((int)$test['valid_answers'] / (int)$test['total_questions'] * 100, 1);
            if($percentage >= (float)$tmp_test['pass_percent']) {
                Discord::sendMessage('Тестирование пройдено (' . $result_link . ')', $embed);
                Test::updateCurrentTestStatus(2);
            } else {
                Discord::sendMessage('Тестирование провалено (' . $result_link . ')', $embed);
                Test::updateCurrentTestStatus(3);
            }

            return $this->renderTest($test['id']);
        }

        // if(time() - (int)$test['last_keepalive'] > 5) {
        //     Discord::sendMessage('Не отправлен keepalive запрос (возможно закрыл страницу обходным способом/пропал интернет) (' . $result_link . ')', $embed);
        //     Test::updateCurrentTestStatus(-1, 'Не отправлен KA запрос');
        //     return $this->renderTest($test['id']);
        // }

        if(@$_POST['hidden'] == true) {
            Discord::sendMessage('Закрыл страницу (' . $result_link . ')', $embed);
            // Test::updateCurrentTestStatus(-1, 'Страница закрыта');
            Test::calcResult($test['id']);
            Test::updateEndTime($test['id']);
            return $this->renderTest($test['id']);
        }

        R::selectDatabase('main');
        R::exec('UPDATE ut_results SET last_keepalive = ? WHERE id = ?', [time(), $test['id']]);

        $time_left_ut = (int)$tmp_test['time'] - (time() - (int)$test['start_time']);
        return responseJson(['status' => 'keepalive_ok', 'time_left' => date('i:s', $time_left_ut)]);
    }

    public function apiStartTest($params) {
        $result = Test::startTest((int)$params);
        if($result == false) {
            return responseJson(['status' => 'error']);
        } else {
            return $this->renderTest();
        }
    }
}