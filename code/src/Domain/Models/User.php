<?php

namespace Geekbrains\Application1\Domain\Models;

use Geekbrains\Application1\Application\Application;
use Geekbrains\Application1\Application\Auth;
use Exception;

class User {
    private ?int $userId;
    private ?string $userName;
    private ?string $userLastName;
    private ?int $userBirthday;
    private ?string $userLogin;
    private ?string $userPassword;
    private ?string $userRole;

    public function __construct(?int $id = null, ?string $name = null, ?string $lastName = null, ?int $birthday = null) {
        $this->userId = $id;
        $this->userName = $name;
        $this->userLastName = $lastName;
        $this->userBirthday = $birthday;
    }

    // Setters
    public function setUserId(int $id): void {
        $this->userId = $id;
    }

    public function setName(string $name): void {
        $this->userName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    }

    public function setLastName(string $lastName): void {
        $this->userLastName = htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8');
    }

    public function setBirthdayFromString(string $birthdayString): void {
        $date = \DateTime::createFromFormat('d-m-Y', $birthdayString);
        if (!$date) {
            throw new Exception("Некорректный формат даты. Используйте ДД-ММ-ГГГГ");
        }
        $this->userBirthday = $date->getTimestamp();
    }

    public function setLogin(string $login): void {
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $login)) {
            throw new Exception("Логин должен содержать только буквы, цифры и подчеркивание (3-30 символов)");
        }
        $this->userLogin = $login;
    }

    public function setPassword(string $password): void {
        if (strlen($password) < 6) {
            throw new Exception("Пароль должен содержать минимум 6 символов");
        }
        $this->userPassword = Auth::getPasswordHash($password);
    }

    public function setRole(string $role): void {
        if (!in_array($role, ['user', 'admin'])) {
            throw new Exception("Некорректная роль пользователя");
        }
        $this->userRole = $role;
    }

    // Getters
    public function getUserId(): ?int {
        return $this->userId;
    }

    public function getUserName(): ?string {
        return $this->userName;
    }

    public function getUserLastName(): ?string {
        return $this->userLastName;
    }

    public function getUserBirthday(): ?int {
        return $this->userBirthday;
    }

    public function getUserLogin(): ?string {
        return $this->userLogin;
    }

    public function getUserRole(): ?string {
        return $this->userRole;
    }

    // Database methods
    public static function getUserById(int $id): ?User {
        $sql = "SELECT * FROM users WHERE id_user = :id";
        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute(['id' => $id]);
        $result = $handler->fetch();

        if (!$result) {
            return null;
        }

        $user = new User(
            $result['id_user'],
            $result['user_name'],
            $result['user_lastname'],
            $result['user_birthday_timestamp']
        );
        $user->userLogin = $result['login'];
        $user->userRole = $result['role'];

        return $user;
    }

    public static function getAllUsersFromStorage(?int $limit = null): array {
        $sql = "SELECT * FROM users";
        $params = [];

        if ($limit > 0) {
            $sql .= " WHERE id_user > :limit";
            $params['limit'] = $limit;
        }

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute($params);
        $result = $handler->fetchAll();

        $users = [];
        foreach ($result as $item) {
            $user = new User(
                $item['id_user'],
                $item['user_name'],
                $item['user_lastname'],
                $item['user_birthday_timestamp']
            );
            $user->userLogin = $item['login'];
            $user->userRole = $item['role'];
            $users[] = $user;
        }

        return $users;
    }

    public static function validateRequestData(): bool {
        if (!isset(
            $_POST['name'],
            $_POST['lastname'],
            $_POST['login'],
            $_POST['password'],
            $_POST['birthday'],
            $_POST['role'],
            $_POST['csrf_token']
        )) {
            return false;
        }

        if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $_POST['birthday'])) {
            return false;
        }

        if (!in_array($_POST['role'], ['user', 'admin'])) {
            return false;
        }

        if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
            return false;
        }

        return true;
    }

    public static function validateUpdateData(): bool {
        if (!isset(
            $_POST['id'],
            $_POST['name'],
            $_POST['lastname'],
            $_POST['birthday'],
            $_POST['csrf_token']
        )) {
            return false;
        }

        if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $_POST['birthday'])) {
            return false;
        }

        if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
            return false;
        }

        return true;
    }

    public function setParamsFromRequestData(): void {
        $this->setName($_POST['name']);
        $this->setLastName($_POST['lastname']);
        $this->setBirthdayFromString($_POST['birthday']);
        $this->setLogin($_POST['login']);
        $this->setPassword($_POST['password']);
        $this->setRole($_POST['role']);
    }

    public function saveToStorage(): void {
        $sql = "INSERT INTO users 
                (user_name, user_lastname, user_birthday_timestamp, login, password_hash, role) 
                VALUES (:name, :lastname, :birthday, :login, :password, :role)";

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute([
            'name' => $this->userName,
            'lastname' => $this->userLastName,
            'birthday' => $this->userBirthday,
            'login' => $this->userLogin,
            'password' => $this->userPassword,
            'role' => $this->userRole
        ]);

        $this->userId = (int)Application::$storage->get()->lastInsertId();
    }

    public function updateInStorage(): void {
        $sql = "UPDATE users SET 
                user_name = :name,
                user_lastname = :lastname,
                user_birthday_timestamp = :birthday
                WHERE id_user = :id";

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute([
            'name' => $this->userName,
            'lastname' => $this->userLastName,
            'birthday' => $this->userBirthday,
            'id' => $this->userId
        ]);
    }

    public function getUserDataAsArray(): array {
        return [
            'id' => $this->userId,
            'username' => $this->userName,
            'userlastname' => $this->userLastName,
            'userbirthday' => $this->userBirthday ? date('d.m.Y', $this->userBirthday) : null,
            'userlogin' => $this->userLogin,
            'userrole' => $this->userRole
        ];
    }
}