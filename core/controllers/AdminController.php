<?php

namespace App\Controllers;

use \RedBeanPHP\R as R;

use App\Facades\Auth;
use App\Facades\Test;

use Rakit\Validation\Validator;


class AdminController extends BaseController {
    
    public function renderHome() {
        return $this->render('admin/home.twig');
    }

    public function renderTestList() {
        R::selectDatabase('main');
        $this->params['tests'] = R::getAssocRow('SELECT * FROM tests');

        return $this->render('admin/test_list.twig');
    }
    
    public function renderTestEdit($params) {
        R::selectDatabase('main');
        $this->params['test'] = R::getRow('SELECT * FROM tests WHERE id = ?', [$params]);
        // var_dump($this->params['test'])

        if(!isset($this->params['test']['id'])) {
            $errc = new ErrorController();
            return $errc->renderError('INVTEST', 'Результат не существует');
        }

        $groups = explode(',', $this->params['test']['groups']);
        
        R::selectDatabase('forum');
        $this->params['groups'] = R::getAssocRow("SELECT g.g_id, l.word_custom FROM core_groups g INNER JOIN core_sys_lang_words l ON l.word_key = CONCAT('core_group_', g.g_id) AND l.lang_id = 1");

        foreach($this->params['groups'] as $k => &$v) {
            $v['selected'] = in_array($v['g_id'], $groups);
        }

        return $this->render('admin/test_edit.twig');
    }
    
    public function renderTestCreate() {
        R::selectDatabase('main');
        
        R::selectDatabase('forum');
        $this->params['groups'] = R::getAssocRow("SELECT g.g_id, l.word_custom FROM core_groups g INNER JOIN core_sys_lang_words l ON l.word_key = CONCAT('core_group_', g.g_id) AND l.lang_id = 1");

        return $this->render('admin/test_create.twig');
    }

    public function doTestDelete($params) {
        R::selectDatabase('main');
        R::exec('DELETE FROM tests WHERE id = ?', [$params]);
        R::exec('DELETE FROM questions WHERE test_id = ?', [$params]);
        R::exec('DELETE FROM ut_results WHERE test_id = ?', [$params]);

        header('Location: '. config['base_link'] . '/admin/test/list');
    }

    public function doTestEdit($params) {
        R::selectDatabase('main');
        $test = R::load('tests', (int)$params);

        if(!$test->id) {
            $errc = new ErrorController();
            return $errc->renderError('INVTEST', 'Результат не существует');
        }

        $validator = new Validator;

        $validation = $validator->make($_POST, [
            'test_name' => 'required|min:3|max:32',
            'test_time' => 'required|integer|min:1',
            'test_question_count' => 'required|integer|min:1',
            'test_pass_percent' => 'required|integer|min:0|max:100',
            'test_groups' => 'required|array',
            'test_groups.*' => 'integer',
            'test_is_open' => 'required|boolean'
        ]);

        $validation->setAliases([
            'test_name' => 'Название',
            'test_time' => 'Время на прохождение',
            'test_question_count' => 'Отображаемое количество вопросов',
            'test_pass_percent' => 'Проходной процент',
            'test_groups' => 'Доступ по группе',
            'test_groups.*' => 'Доступ по группе',
            'test_is_open' => 'Открыт'
        ]);

        $validation->validate();

        if ($validation->fails()) {
            $errors = $validation->errors()->firstOfAll();

            $errc = new ErrorController();
            return $errc->renderError('VALIDERROR', implode('\n', $errors));
        }

        $test->name = $_POST['test_name'];
        $test->time = $_POST['test_time'];
        $test->question_count = $_POST['test_question_count'];
        $test->pass_percent = $_POST['test_pass_percent'];
        $test->groups = implode(',', $_POST['test_groups']);
        $test->is_open = (bool)$_POST['test_is_open'];
        R::store($test);

        return responseJson(['status' => 'success']);
    }

    public function doTestCreate() {
        R::selectDatabase('main');

        $validator = new Validator;

        $validation = $validator->make($_POST, [
            'test_name' => 'required|min:3|max:32',
            'test_time' => 'required|integer|min:1',
            'test_question_count' => 'required|integer|min:1',
            'test_pass_percent' => 'required|integer|min:0|max:100',
            'test_groups' => 'required|array',
            'test_groups.*' => 'integer',
            'test_is_open' => 'required|boolean'
        ]);

        $validation->setAliases([
            'test_name' => 'Название',
            'test_time' => 'Время на прохождение',
            'test_question_count' => 'Отображаемое количество вопросов',
            'test_pass_percent' => 'Проходной процент',
            'test_groups' => 'Доступ по группе',
            'test_groups.*' => 'Доступ по группе',
            'test_is_open' => 'Открыт'
        ]);

        $validation->validate();

        if ($validation->fails()) {
            $errors = $validation->errors()->firstOfAll();

            $errc = new ErrorController();
            return $errc->renderError('VALIDERROR', implode('\n', $errors));
        }

        $test = R::dispense('tests');
        $test->name = $_POST['test_name'];
        $test->time = $_POST['test_time'];
        $test->question_count = $_POST['test_question_count'];
        $test->pass_percent = $_POST['test_pass_percent'];
        $test->groups = implode(',', $_POST['test_groups']);
        $test->groups = implode(',', $_POST['test_groups']);
        $test->is_open = (bool)$_POST['test_is_open'];
        R::store($test);

        return responseJson(['status' => 'success']);
    }

    public function renderResultList() {
        R::selectDatabase('main');
        $this->params['results'] = R::getAssocRow('SELECT ur.id, ur.status, ur.user_id, ur.fail_reason, t.name FROM ut_results ur INNER JOIN tests t ON ur.test_id = t.id ORDER BY ur.id DESC');

        R::selectDatabase('forum');                                 
        foreach($this->params['results'] as $k => &$v) {
            $row = R::getRow('SELECT name FROM core_members WHERE member_id = ?', [$v['user_id']]);
            $v['user_name'] = $row['name'];
        }

        return $this->render('admin/result_list.twig');
    }

    public function renderResult($params) {
        R::selectDatabase('main');
        $this->params['result'] = R::getRow('SELECT * FROM ut_results WHERE id = ?', [$params]);

        if(!isset($this->params['result']['id'])) {
            $errc = new ErrorController();
            return $errc->renderError('INVRES', 'Результат не существует');
        }

        R::selectDatabase('forum');
        $this->params['user'] = R::getRow('SELECT name FROM core_members WHERE member_id = ?', [$this->params['result']['user_id']]);
        R::selectDatabase('main');

        $this->params['test'] = R::getRow('SELECT * FROM tests WHERE id = ?', [$this->params['result']['test_id']]);

        $this->params['answers'] = R::getAssocRow('SELECT ua.answers as user_answers, ua.id as answer_id, ua.is_valid, q.text, q.options, q.type, q.answers as valid_answers FROM ut_answers ua INNER JOIN questions q ON ua.question_id = q.id WHERE ua.ut_result_id = ? ORDER BY ua.id', [$this->params['result']['id']]);
        
        foreach($this->params['answers'] as $k => &$v) {
            if($v['type'] == 2) {
                $v['user_answers'] = explode(',', $v['user_answers']);
                $v['valid_answers'] = explode(',', $v['valid_answers']);
            }

            $v['processed_options'] = [];

            foreach(explode('|ALDERNEY|', $v['options']) as $kk => $vv) {
                $text = $vv;
                if($v['type'] == 1) {
                    $selected = $kk == $v['user_answers'];
                    $valid = $kk == $v['valid_answers'];
                } elseif($v['type'] == 2) {
                    $selected = in_array(strval($kk), $v['user_answers']);
                    $valid = in_array(strval($kk), $v['valid_answers']);
                } elseif($v['type'] == 3) {
                    $selected = false;
                    $valid = false;
                }

                $v['processed_options'][] = ['text' => $text, 'selected' => $selected, 'valid' => $valid];
            }

        }

        return $this->render('admin/result_view.twig');
    }

    public function doRetest($params) {
        R::selectDatabase('main');
        $this->params['result'] = R::getRow('SELECT * FROM ut_results WHERE id = ?', [$params]);

        if(!isset($this->params['result']['id'])) {
            $errc = new ErrorController();
            return $errc->renderError('INVRES', 'Результат не существует');
        }

        R::exec('UPDATE ut_results SET retest = 1 WHERE id = ?', [$params]);

        header('Location: ' . config['base_link'] . '/admin/result/' . $params);
    }

    public function renderQuestionList($params) {
        R::selectDatabase('main');

        $this->params['test'] = R::getRow('SELECT * FROM tests WHERE id = ?', [$params]);
        if(!isset($this->params['test']['id'])) {
            $errc = new ErrorController();
            return $errc->renderError('INVTEST', 'Тест не существует');
        }

        $this->params['questions'] = R::getAssocRow('SELECT * FROM questions WHERE test_id = ?', [$this->params['test']['id']]);

        return $this->render('admin/question_list.twig');
    }

    public function renderQuestionCreate($params) {
        R::selectDatabase('main');

        $this->params['test'] = R::getRow('SELECT * FROM tests WHERE id = ?', [$params]);
        // var_dump($this->params);
        if(!isset($this->params['test']['id'])) {
            $errc = new ErrorController();
            return $errc->renderError('INVTEST', 'Тест не существует');
        }

        return $this->render('admin/question_create.twig');
    }

    public function renderQuestionEdit($params) {
        R::selectDatabase('main');

        $this->params['question'] = R::getRow('SELECT * FROM questions WHERE id = ?', [$params]);
        // var_dump($this->params);
        if(!isset($this->params['question']['id'])) {
            $errc = new ErrorController();
            return $errc->renderError('INVQSTN', 'Вопрос не существует');
        }

        return $this->render('admin/question_edit.twig');
    }

    public function doQuestionEdit($params) {
        R::selectDatabase('main');

        $validator = new Validator;

        $validation = $validator->make($_POST, [
            'question_text' => 'required|min:3|max:256',
            'question_type' => 'required|integer|in:1,2,3',
            'options' => 'required|array|min:1|max:10',
            'question_answers' => 'required|min:1|max:32'
        ]);

        $validation->setAliases([
            'question_text' => 'Текст',
            'question_type' => 'Тип вопроса',
            'options' => 'Варианты ответа',
            'question_answers' => 'Правильные ответы'
        ]);

        $validation->validate();

        if ($validation->fails()) {
            $errors = $validation->errors()->firstOfAll();

            $errc = new ErrorController();
            return $errc->renderError('VALIDERROR', implode('\n', $errors));
        }

        foreach($_POST['options'] as $k => &$v) {
            if(empty($v)) { unset($_POST['options'][$k]); continue; }
            // $v = htmlspecialchars($v);
        }

        if(($_POST['question_type'] == 1 || $_POST['question_type'] == 2) && count($_POST['options']) == 0) {
            $errc = new ErrorController();
            return $errc->renderError('VALIDERROR', 'Должен быть хотя бы один вариант');
        }

        // $_POST['question_text'] = htmlspecialchars($_POST['question_text']);

        $qstn = R::load('questions', $params);

        if(!$qstn->id) {
            $errc = new ErrorController();
            return $errc->renderError('INVQSTN', 'Вопрос не существует');
        }

        $qstn->text = $_POST['question_text'];
        $qstn->options = implode('|ALDERNEY|', $_POST['options']);
        $qstn->answers = $_POST['question_answers'];
        $qstn->type = (int)$_POST['question_type'];

        R::store($qstn);

        return responseJson(['status' => 'success']);
    }

    public function doQuestionCreate($params) {
        R::selectDatabase('main');

        $validator = new Validator;

        $validation = $validator->make($_POST, [
            'question_text' => 'required|min:3|max:256',
            'question_type' => 'required|integer|in:1,2,3',
            'options' => 'required|array|min:1|max:10',
            'question_answers' => 'required|min:1|max:32'
        ]);

        $validation->setAliases([
            'question_text' => 'Текст',
            'question_type' => 'Тип вопроса',
            'options' => 'Варианты ответа',
            'question_answers' => 'Правильные ответы'
        ]);

        $validation->validate();

        if ($validation->fails()) {
            $errors = $validation->errors()->firstOfAll();

            $errc = new ErrorController();
            return $errc->renderError('VALIDERROR', implode('\n', $errors));
        }

        foreach($_POST['options'] as $k => &$v) {
            if(empty($v)) { unset($_POST['options'][$k]); continue; }
            // $v = htmlspecialchars($v);
        }

        if(($_POST['question_type'] == 1 || $_POST['question_type'] == 2) && count($_POST['options']) == 0) {
            $errc = new ErrorController();
            return $errc->renderError('VALIDERROR', 'Должен быть хотя бы один вариант');
        }

        $test = R::load('tests', $params);

        if(!$test->id) {
            $errc = new ErrorController();
            return $errc->renderError('INVQSTN', 'Вопрос не существует');
        }

        $qstn = R::dispense('questions');
        $qstn->test_id = $params;
        $qstn->text = $_POST['question_text'];
        $qstn->options = implode('|ALDERNEY|', $_POST['options']);
        $qstn->answers = $_POST['question_answers'];
        $qstn->type = (int)$_POST['question_type'];

        R::store($qstn);

        return responseJson(['status' => 'success']);
    }

    public function doChangeAnswerIsValid() {
        $validator = new Validator;

        $validation = $validator->make($_POST, [
            'id' => 'required|integer',
            'state' => 'required|integer|in:0,1',
        ]);

        $validation->validate();

        if ($validation->fails()) {
            $errors = $validation->errors()->firstOfAll();

            $errc = new ErrorController();
            return $errc->renderError('VALIDERROR', implode('\n', $errors));
        }

        R::selectDatabase('main');

        $answer = R::load('ut_answers', $_POST['id']);
        if (!$answer->id) return;

        $was = $answer->is_valid;

        $answer->is_valid = $_POST['state'];

        R::store($answer);

        $result = R::load('ut_results', $answer->ut_result_id);

        if($was && !$_POST['state']) $result->valid_answers -= 1;
        if(!$was && $_POST['state']) $result->valid_answers += 1;

        R::store($result);

        Test::calcResult($answer->ut_result_id);
    }

    public function doQuestionDelete($params) {
        R::selectDatabase('main');

        $qstn = R::getRow('SELECT * FROM questions WHERE id = ?', [$params]);

        R::exec('DELETE FROM questions WHERE id = ?', [$params]);
        R::exec('DELETE FROM ut_answers WHERE question_id = ?', [$params]);

        header('Location: '. config['base_link'] . '/admin/test/' . $qstn['test_id'] . '/questions');
    }
}