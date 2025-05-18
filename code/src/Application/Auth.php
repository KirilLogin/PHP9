<?php

namespace Geekbrains\Application1\Application;

class Auth {
    public static function getPasswordHash(string $rawPassword): string {
        return password_hash($rawPassword, PASSWORD_BCRYPT);
    }

    public function proceedAuth(string $login, string $password): bool{
        $sql = "SELECT id_user, user_name, user_lastname, password_hash, role FROM users WHERE login = :login";

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute(['login' => $login]);
        $result = $handler->fetchAll();

        if (!empty($result) && password_verify($password, $result[0]['password_hash'])) {
    $_SESSION['user'] = [
        'id' => $result[0]['id_user'],
        'name' => $result[0]['user_name'],
        'lastname' => $result[0]['user_lastname'],
        // Предположим, что в БД есть колонка role. Если нет — добавь.
        'role' => $result[0]['role'] ?? 'user' // По умолчанию 'user'
    ];
    return true;
}
    return false;
    }

     public static function isAdmin(): bool {
        return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
    }

    public static function checkAdmin(): void {
        if (!self::isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Доступ запрещен: только для админа']);
            exit;
        }
    }

}