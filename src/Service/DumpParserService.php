<?php

namespace App\Service;

use Symfony\Component\Finder\Finder;

class DumpParseService {
    private $dumpDirectory;

    public function __construct(string $dumpDirectory)
    {
        $this->dumpDirectory = $dumpDirectory;
    }

    public function getFiles(): array {
        $finder = new Finder();
        $finder->files()->in($this->dumpDirectory)->name('*.sql');

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }

        return $files;
    }

    public function parseDump(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $posts = [];

        if (str_contains($content, 'wp_posts')) {
            $tableName = 'wp_posts';
        } elseif (str_contains($content, 'wp1of20_posts')) {
            $tableName = 'wp1of20_posts';
        }

        // витягуємо запис INSERT INTO "post" table
        preg_match('/INSERT INTO `' . $tableName . '` \([^)]+\) VALUES\s+(\([^`]+\)\;)/i', $content, $matches);

        // розбиваємо всі значення полів на компоненти по одинарним лапкам
        $components = preg_split("/[']/", $matches[1]);

        // 5-ий компонент - контент, 7-ий - назва статті ( +36 до наступного запису в таблиці)
        $titleIndex = 7;
        $contentIndex = 5;
        while (count($components) > $titleIndex) {
            
            $title = $this->cleanContent($components[$titleIndex]);
            $content = $this->cleanContent($components[$contentIndex]);
            
            $posts[] = [
                'title' => $title,
                'content' => $content
            ];

            $titleIndex += 36;
            $contentIndex += 36;
        }
               
        return $posts;
    }

    private function cleanContent(string $str): string {
        $patternURL = '/<a href=[^>]+>[^>]+\>/i';
        $str = preg_replace($patternURL, '', $str);
        $patternIMG = '/<img[^>]+\>/i';
        $str = preg_replace($patternIMG, '', $str);
        return $str;
    }
}