<?php

namespace App\Repository;

use App\Entity\Idea;

class IdeaRepository
{
    private string $filePath;

    public function __construct(string $projectDir)
    {
        $this->filePath = $projectDir . '/var/data/ideas.json';
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
        $ideas = array_map(fn($ideaData) => Idea::fromArray($ideaData), $data);

        usort($ideas, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());

        return $ideas;
    }

    public function find(int $id): ?Idea
    {
        $data = $this->readData();
        foreach ($data as $ideaData) {
            if ($ideaData['id'] === $id) {
                return Idea::fromArray($ideaData);
            }
        }
        return null;
    }

    public function findBy(array $criteria): array
    {
        $data = $this->readData();
        $results = [];

        foreach ($data as $ideaData) {
            $match = true;
            foreach ($criteria as $key => $value) {
                if (!isset($ideaData[$key]) || $ideaData[$key] !== $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                $results[] = Idea::fromArray($ideaData);
            }
        }

        return $results;
    }

    public function save(Idea $idea): void
    {
        $data = $this->readData();

        if ($idea->getId() === null) {
            $newId = empty($data) ? 1 : max(array_column($data, 'id')) + 1;
            $idea->setId($newId);
        }

        $ideaData = $idea->toArray();
        $found = false;
        foreach ($data as $index => $existingIdea) {
            if ($existingIdea['id'] === $idea->getId()) {
                $data[$index] = $ideaData;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $data[] = $ideaData;
        }

        $this->writeData($data);
    }

    public function remove(Idea $idea): void
    {
        $data = $this->readData();
        $data = array_filter($data, fn($ideaData) => $ideaData['id'] !== $idea->getId());
        $this->writeData(array_values($data));
    }

    public function count(array $criteria = []): int
    {
        if (empty($criteria)) {
            return count($this->readData());
        }
        return count($this->findBy($criteria));
    }
}
