<?php

namespace Geekbrains\Application1\Domain\Controllers;

use Geekbrains\Application1\Application\Application;
use Geekbrains\Application1\Application\Render;
use Geekbrains\Application1\Application\Auth;
use Geekbrains\Application1\Domain\Models\User;

class UserController extends AbstractController {

    protected array $actionsPermissions = [
        'actionHash' => ['admin', 'some'],
        'actionSave' => ['admin'],
        'actionEdit' => ['admin'],
        'actionUpdate' => ['admin'],
        'actionDelete' => ['admin']
    ];

    public function actionIndex(): string {
        
     $users = User::getAllUsersFromStorage();
    $render = new Render();

    $userAuthorized = isset($_SESSION['user']);
    $currentUserIsAdmin = Auth::isAdmin();

    var_dump($_SESSION['user'] ?? null); // Для отладки, потом можно убрать

    if (!$users) {
        return $render->renderPage(
            'user-empty.tpl', 
            [
                'title' => 'Список пользователей',
                'message' => "Список пуст или не найден",
                'current_user_is_admin' => $currentUserIsAdmin,
                'user_authorized' => $userAuthorized,
            ]
        );
    } else {
        return $render->renderPage(
            'user-index.tpl', 
            [
                'title' => 'Список пользователей',
                'users' => $users,
                'current_user_is_admin' => $currentUserIsAdmin,
                'user_authorized' => $userAuthorized,
            ]
        );
    }
}

    public function actionIndexRefresh(): string {
        $limit = null;
        
        if(isset($_POST['maxId']) && ($_POST['maxId'] > 0)){
            $limit = (int)$_POST['maxId'];
        }

        $users = User::getAllUsersFromStorage($limit);
        $usersData = [];

        if(count($users) > 0) {
            foreach($users as $user){
                $usersData[] = $user->getUserDataAsArray();
            }
        }

        return json_encode($usersData);
    }

    public function actionSave(): string {
        if(User::validateRequestData()) {
            $user = new User();
            $user->setParamsFromRequestData();
            $user->saveToStorage();

            $render = new Render();

            return $render->renderPage(
                'user-created.tpl', 
                [
                    'title' => 'Пользователь создан',
                    'message' => "Создан пользователь " . $user->getUserName() . " " . $user->getUserLastName()
                ]
            );
        } else {
            throw new \Exception("Переданные данные некорректны");
        }
    }

    public function actionEdit(): string {
        Auth::checkAdmin();
        
        $userId = $_GET['id'] ?? null;
        if (!$userId) {
            throw new \Exception("ID пользователя не указан");
        }

        $user = User::getUserById((int)$userId);
        if (!$user) {
            throw new \Exception("Пользователь не найден");
        }

        $render = new Render();
        return $render->renderPageWithForm(
            'user-edit.tpl',
            [
                'title' => 'Редактирование пользователя',
                'user' => $user
            ]
        );
    }

    public function actionUpdate(): string {
        Auth::checkAdmin();
        
        if (!User::validateUpdateData()) {
            throw new \Exception("Переданные данные некорректны");
        }

        $userId = $_POST['id'] ?? null;
        if (!$userId) {
            throw new \Exception("ID пользователя не указан");
        }

        $user = User::getUserById((int)$userId);
        if (!$user) {
            throw new \Exception("Пользователь не найден");
        }

        // Обновляем данные
        $user->setName(htmlspecialchars($_POST['name']));
        $user->setLastName(htmlspecialchars($_POST['lastname']));
        $user->setBirthdayFromString($_POST['birthday']);
        
        // Сохраняем изменения
        $user->updateInStorage();

        header('Location: /user/index/');
        return '';
    }

    public function actionDelete(): void {
    Auth::checkAdmin();

    header('Content-Type: application/json');

    $userId = $_POST['id'] ?? null;
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID пользователя не указан']);
        exit;
    }

    $userId = (int)$userId;

    // Например, запретить удалять себя (если нужно)
    $currentUserId = $_SESSION['user']['id'] ?? null;
    if ($currentUserId == $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Нельзя удалить самого себя']);
        exit;
    }

    $stmt = Application::$storage->get()->prepare("DELETE FROM users WHERE id_user = :id");
    $stmt->execute(['id' => $userId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Пользователь не найден']);
    }

    exit;
}
    public function actionAuth(): string {
        $render = new Render();
        
        return $render->renderPageWithForm(
            'user-auth.tpl',
            [
                'title' => 'Форма входа'
            ]
        );
    }

    public function actionLogin(): string {
        $result = false;

        if(isset($_POST['login']) && isset($_POST['password'])){
            $result = Application::$auth->proceedAuth($_POST['login'], $_POST['password']);
        }
        
        if(!$result){
            $render = new Render();

            return $render->renderPageWithForm(
                'user-auth.tpl', 
                [
                    'title' => 'Форма входа',
                    'auth-success' => false,
                    'auth-error' => 'Неверные логин или пароль'
                ]
            );
        } else {
            header('Location: /');
            return "";
        }
    }

    public function actionHash(): string {
        return Auth::getPasswordHash($_GET['pass_string']);
    }

    public function actionLogout(): void {
    session_start();
    // Очистить все данные сессии
    $_SESSION = [];
    // Уничтожить сессию и еще:
    session_destroy();
    // Перенаправить пользователя на главную страницу или страницу входа
    header('Location: /user/auth/');
    exit();
}


} 