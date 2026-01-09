<?php

namespace App\Repository;

use App\Entity\User;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserRepository implements PasswordUpgraderInterface
{
    private string $filePath;

    public function __construct(string $projectDir)
    {
        $this->filePath = $projectDir . '/var/data/users.json';
        $this->ensureFileExists();
    }

    private function ensureFileExists(): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, json_encode([]));
        }
    }

    private function readData(): array
    {
        $content = file_get_contents($this->filePath);
        return json_decode($content, true) ?? [];
    }

    private function writeData(array $data): void
    {
        file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function findAll(): array
    {
        $data = $this->readData();
        return array_map(fn($userData) => User::fromArray($userData), $data);
    }

    public function find(int $id): ?User
    {
        $data = $this->readData();
        foreach ($data as $userData) {
            if ($userData['id'] === $id) {
                return User::fromArray($userData);
            }
        }
        return null;
    }

    public function findOneBy(array $criteria): ?User
    {
        $data = $this->readData();
        foreach ($data as $userData) {
            $match = true;
            foreach ($criteria as $key => $value) {
                if (!isset($userData[$key]) || $userData[$key] !== $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return User::fromArray($userData);
            }
        }
        return null;
    }

    public function save(User $user): void
    {
        $data = $this->readData();

        if ($user->getId() === null) {
            $newId = empty($data) ? 1 : max(array_column($data, 'id')) + 1;
            $user->setId($newId);
        }

        $userData = $user->toArray();
        $found = false;
        foreach ($data as $index => $existingUser) {
            if ($existingUser['id'] === $user->getId()) {
                $data[$index] = $userData;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $data[] = $userData;
        }

        $this->writeData($data);
    }

    public function remove(User $user): void
    {
        $data = $this->readData();
        $data = array_filter($data, fn($userData) => $userData['id'] !== $user->getId());
        $this->writeData(array_values($data));
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            return;
        }

        $user->setPassword($newHashedPassword);
        $this->save($user);
    }
}
