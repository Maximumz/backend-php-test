<?php

namespace Models;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class Todo
{
    /** @var Connection */
    protected $connection;

    private function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public static function setup(Connection $connection): Todo
    {
        return new self($connection);
    }

    public function login(string $username, string $password): ?array
    {
        $sql = "SELECT * FROM users WHERE username = :username and password = :password";

        $params = [
            'username' => $username,
            'password' => $password
        ];

        $user = null;
        try {
            $user = $this->connection->fetchAssociative($sql, $params);
        } catch (\Exception $e) {
            // log exceptions here
        }

        return $user;
    }

    public function getTodoById(int $todoId, int $userId): array
    {
        $sql = "SELECT * FROM todos WHERE id = :todo_id AND user_id = :user_id";

        $params = [
            'user_id' => $userId,
            'todo_id' => $todoId
        ];

        $todo = false;
        try {
            $todo = $this->connection->fetchAssociative($sql, $params);
        } catch (\Exception $e) {
            // log exceptions here
        }

        return $todo ?: [];
    }

    public function getTodoCountByUserId(int $userId): int
    {
        $sql = "SELECT id FROM todos WHERE user_id = :user_id";

        $params = ['user_id' => $userId];

        $total_count = 0;
        try {
            $total_count = $this->connection->executeStatement($sql, $params);
        } catch (\Exception $e) {
            // log exceptions here
        }

        return $total_count;
    }

    public function getPaginatedTodosByUserId(int $userId, int $offset, int $rowCount): ?array
    {
        $sql = "SELECT * FROM todos WHERE user_id = :user_id LIMIT :offset, :row_count";

        $params = [
            'user_id' => $userId,
            'offset' => $offset,
            'row_count' => $rowCount
        ];

        $todos = null;
        try {
            $todos = $this->connection->fetchAllAssociative($sql, $params,
                [
                    'offset' => ParameterType::INTEGER,
                    'row_count' => ParameterType::INTEGER
                ]
            );
        }  catch (\Exception $e) {
            // log exceptions here
        }

        return $todos;
    }

    public function add(int $userId, string $description): bool
    {
        $sql = "INSERT INTO todos (user_id, description) VALUES (?, ?)";

        $params = [$userId, $description];

        $result = false;
        try {
            $result = $this->connection->executeStatement($sql, $params);
        } catch (\Exception $e) {
            // log exceptions here
        }

        return $result;
    }

    public function delete(int $userId, int $todoId): bool
    {
        $sql = "DELETE FROM todos WHERE id = :todo_id AND user_id = :user_id";

        $params = [
            'todo_id' => $todoId,
            'user_id' => $userId
        ];

        $result = false;
        try {
            $result = $this->connection->executeStatement($sql, $params);
        } catch (\Exception $e) {
            // log exceptions here
        }

        return $result;
    }

    public function complete(int $userId, int $todoId, int $completedStatus): bool
    {
        $sql = "UPDATE todos SET complete = :completed_status WHERE id = :todo_id AND user_id = :user_id";

        $params = [
            'completed_status' => $completedStatus,
            'todo_id' => $todoId,
            'user_id' => $userId
        ];

        $result = false;
        try {
            $result = $this->connection->executeStatement($sql, $params);
        } catch (\Exception $e) {
            // log exceptions here
        }

        return $result;
    }
}