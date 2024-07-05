<?php

namespace App\Service;

class ExportService
{
    public function exportToCsv(array $posts, string $filename): void
    {
        $fp = fopen($filename, 'w');
        fputcsv($fp, ['Title', 'Content']);
        foreach ($posts as $post) {
            fputcsv($fp, [
                $this->escapeLiteralString($post['title']),
                $this->escapeLiteralString($post['content'])
            ]);
        }
        fclose($fp);
    }

    public function exportToXml(array $articles, string $filename): void
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('articles');
        $dom->appendChild($root);

        foreach ($articles as $article) {
            $articleElem = $dom->createElement('article');
            
            $titleElem = $dom->createElement('title');
            $titleElem->appendChild($dom->createTextNode($this->escapeLiteralString($article['title'])));
            $articleElem->appendChild($titleElem);

            $contentElem = $dom->createElement('content');
            $contentElem->appendChild($dom->createTextNode($this->escapeLiteralString($article['content'])));
            $articleElem->appendChild($contentElem);

            $root->appendChild($articleElem);
        }

        $dom->save($filename);
    }

    public function exportTotxt(array $posts, string $filename): void
    {
        $myfile = fopen($filename, "w");
        foreach ($posts as $post) {
            fwrite($myfile, $this->escapeLiteralString($post['title']));
            fwrite($myfile, $this->escapeLiteralString($post['content']));
        }
        fclose($myfile);
    }

    public function mergeFiles(array $files, string $outputFile): void
    {
        $fp = fopen($outputFile, 'w');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            fwrite($fp, $content);
        }
        fclose($fp);
    }

    private function escapeLiteralString($str)
    {
        return str_replace(
            ["\n", "\r", "\t", "\v", "\e", "\\"],
            ['\\n', '\\r', '\\t', '\\v', '\\e', '\\\\'],
            $str
        );
    }
}