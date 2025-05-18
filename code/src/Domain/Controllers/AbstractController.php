<?php

namespace Geekbrains\Application1\Domain\Controllers;

use Geekbrains\Application1\Application\Application;

class AbstractController {

    protected array $actionsPermissions = [];

    /**
     * Получение ролей пользователя (если используется таблица user_roles)
     */
    public function getUserRoles(): array {
        $roles = [];

        if (isset($_SESSION['user']['id'])) {
            $rolesSql = "SELECT role FROM user_roles WHERE id_user = :id";

            $handler = Application::$storage->get()->prepare($rolesSql);
            $handler->execute(['id' => $_SESSION['user']['id']]);
            $result = $handler->fetchAll();

            if (!empty($result)) {
                foreach ($result as $row) {
                    $roles[] = $row['role'];
                }
            }
        }

        return $roles;
    }

    /**
     * Возвращает список ролей, которым разрешён доступ к указанному методу
     */
    public function getActionsPermissions(string $methodName): array {
        return $this->actionsPermissions[$methodName] ?? [];
    }
}