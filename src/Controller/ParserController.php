<?php

namespace App\Controller;

use App\Service\DumpParseService;
use App\Service\ExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ParserController extends AbstractController
{
    private $sqlDumpParserService;
    private $exportService;

    public function __construct(DumpParseService $sqlDumpParserService, ExportService $exportService)
    {
        $this->sqlDumpParserService = $sqlDumpParserService;
        $this->exportService = $exportService;
    }

    #[Route('/', name: 'parser_index')]
    public function index(): Response
    {
        $dumpFiles = $this->sqlDumpParserService->getFiles();
        return $this->render('parser/index.html.twig', ['dumpFiles' => $dumpFiles]);
    }

    #[Route('/upload', name: 'parser_upload', methods: ['POST'])]
    public function upload(Request $request): Response
    {
        $file = $request->files->get('dump_file');
        if ($file) {
            $fileName = $file->getClientOriginalName();
            $file->move($this->getParameter('dumps_directory'), $fileName);
        }
        
        return $this->redirectToRoute('parser_index');
    }

    #[Route('/parse', name: 'parser_parse', methods: ['POST'])]
    public function parse(Request $request): Response
    {
        $selectedFiles = $request->request->all('dump_files');
        if (!is_array($selectedFiles)) {
            //Якщо selectedFiles має значення - одноелементний масив, якщо ні - порожній масив.
            $selectedFiles = $selectedFiles ? [$selectedFiles] : [];
        }

        $results = [];

        foreach ($selectedFiles as $file) {
            $posts = $this->sqlDumpParserService->parseDump($file);
            $results[basename($file)] = $posts;
        }

        $selectedFormat = $request->request->get('format');
        $exportFiles = [];
        $exportFileNames = [];
        foreach ($results as $fileName => $posts) {
            if ($selectedFormat == "csv") {
                $exportFileName = $this->getParameter('kernel.project_dir') . '/public/exports/' . $fileName . '.csv';
                $this->exportService->exportToCsv($posts, $exportFileName);
                $exportFiles[] = $exportFileName;
                $exportFileNames[] = $fileName . '.csv';
            } elseif ($selectedFormat == "xml") {
                $exportFileName = $this->getParameter('kernel.project_dir') . '/public/exports/' . $fileName . '.xml';
                $this->exportService->exportToXml($posts, $exportFileName);
                $exportFiles[] = $exportFileName;
                $exportFileNames[] = $fileName . '.xml';
            } elseif ($selectedFormat == "txt") {
                $exportFileName = $this->getParameter('kernel.project_dir') . '/public/exports/' . $fileName . '.txt';
                $this->exportService->exportTotxt($posts, $exportFileName);
                $exportFiles[] = $exportFileName;
                $exportFileNames[] = $fileName . '.txt';
            } 
        }

        $mergedFile = $this->getParameter('kernel.project_dir') . '/public/exports/merged.' . $selectedFormat;
        $this->exportService->mergeFiles($exportFiles, $mergedFile);
        
        $session = $request->getSession();
        $session->set('export_files', $exportFileNames);
        $session->set('merged_format', $selectedFormat);

        return $this->redirectToRoute('parser_index');
    }
}