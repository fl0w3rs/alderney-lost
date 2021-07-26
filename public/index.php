<?php
require __DIR__ . '/../core/main.php';

use App\Facades\Auth;

// unlink(config['base_dir'] . '/cache/route.cache');

// function clearDir($dir) {
//     $files = array_diff(scandir($dir), array('.','..'));
//     foreach ($files as $file) {
//         (is_dir("$dir/$file")) ? clearDir("$dir/$file") : unlink("$dir/$file");
//     }
//     return rmdir($dir);
// }

// clearDir(config['base_dir'] . '/cache/views');

$dispatcher = FastRoute\cachedDispatcher(function(FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/', ['\App\Controllers\AuthController@renderLogin', []]);
    $r->addRoute('GET', '/home', ['\App\Controllers\HomeController@renderHome', ['auth']]);
    $r->addRoute('GET', '/test/{id:\d+}', ['\App\Controllers\TestController@renderStartTest', ['auth']]);

    $r->addGroup('/admin', function(FastRoute\RouteCollector $r) {
        $r->addRoute('GET', '', ['\App\Controllers\AdminController@renderHome', ['auth', 'admin']]);
        $r->addRoute('GET', '/result/{id:\d+}', ['\App\Controllers\AdminController@renderResult', ['auth', 'adminORtrainer']]);
        $r->addRoute('GET', '/result/list', ['\App\Controllers\AdminController@renderResultList', ['auth', 'adminORtrainer']]);
        $r->addRoute('GET', '/test/list', ['\App\Controllers\AdminController@renderTestList', ['auth', 'admin']]);
        $r->addRoute('GET', '/test/create', ['\App\Controllers\AdminController@renderTestCreate', ['auth', 'admin']]);
        $r->addRoute('GET', '/test/{id:\d+}', ['\App\Controllers\AdminController@renderTestEdit', ['auth', 'admin']]);
        $r->addRoute('GET', '/test/{id:\d+}/questions', ['\App\Controllers\AdminController@renderQuestionList', ['auth', 'admin']]);
        $r->addRoute('GET', '/test/{id:\d+}/questions/create', ['\App\Controllers\AdminController@renderQuestionCreate', ['auth', 'admin']]);
        $r->addRoute('GET', '/question/{id:\d+}', ['\App\Controllers\AdminController@renderQuestionEdit', ['auth', 'admin']]);
    });

    $r->addGroup('/api', function(FastRoute\RouteCollector $r) {
        $r->addRoute('GET', '/access', ['\App\Controllers\AuthController@apiLogin', []]);
        $r->addRoute('POST', '/test/{id:\d+}/start', ['\App\Controllers\TestController@apiStartTest', ['auth']]);
        $r->addRoute('POST', '/test/answer', ['\App\Controllers\TestController@apiSendAnswer', ['auth']]);
        $r->addRoute('POST', '/test/keepalive', ['\App\Controllers\TestController@apiKeepAlive', ['auth']]);
        $r->addRoute('GET', '/admin/result/{id:\d+}/retest', ['\App\Controllers\AdminController@doRetest', ['auth', 'adminORtrainer']]);
        $r->addRoute('POST', '/admin/answer/change', ['\App\Controllers\AdminController@doChangeAnswerIsValid', ['auth', 'adminORtrainer']]);
        //
        $r->addRoute('GET', '/admin/test/{id:\d+}/delete', ['\App\Controllers\AdminController@doTestDelete', ['auth', 'admin']]);
        $r->addRoute('POST', '/admin/test/{id:\d+}/edit', ['\App\Controllers\AdminController@doTestEdit', ['auth', 'admin']]);
        $r->addRoute('POST', '/admin/test/create', ['\App\Controllers\AdminController@doTestCreate', ['auth', 'admin']]);
        
        $r->addRoute('POST', '/admin/question/{id:\d+}/edit', ['\App\Controllers\AdminController@doQuestionEdit', ['auth', 'admin']]);
        $r->addRoute('GET', '/admin/question/{id:\d+}/delete', ['\App\Controllers\AdminController@doQuestionDelete', ['auth', 'admin']]);
        $r->addRoute('POST', '/admin/test/{id:\d+}/question/create', ['\App\Controllers\AdminController@doQuestionCreate', ['auth', 'admin']]);
    });
}, [
    'cacheFile' => config['base_dir'] . '/cache/route.cache', 
    'cacheDisabled' => config['dev_mode'],    
]);

$httpMethod = $_SERVER['REQUEST_METHOD'];

$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], (strlen('/alderney')));
$uri = $_SERVER['REQUEST_URI'];

if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        $errc = new App\Controllers\ErrorController();
        echo $errc->renderError('404', 'Страница не найдена');
        
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];

        $errc = new App\Controllers\ErrorController();
        echo $errc->renderError('405', 'Метод не разрешён');

        break;
    case FastRoute\Dispatcher::FOUND:
        $middlewares = $routeInfo[1][1];

        if(in_array('auth', $middlewares)) {
            if(!Auth::haveAccessToAlderney()) {
                $errc = new App\Controllers\ErrorController();
                echo $errc->renderError('NOACCESS', 'Вы не авторизованы или не являетесь участником сообщества');

                return;
            }
        }

        if(in_array('admin', $middlewares)) {
            if(!Auth::isAdmin()) {
                $errc = new App\Controllers\ErrorController();
                echo $errc->renderError('NOACCESS', 'Вы не администратор');

                return;
            }
        }

        if(in_array('adminORtrainer', $middlewares)) {
            if(!Auth::isAdmin() && !Auth::isTrainer()) {
                $errc = new App\Controllers\ErrorController();
                echo $errc->renderError('NOACCESS', 'Вы не администратор/тренер');

                return;
            }
        }

        $handler = explode('@', $routeInfo[1][0]);
        $vars = $routeInfo[2];
        $controller = new $handler[0]();

        echo call_user_func_array(array($controller, $handler[1]), $vars);

        break;
}