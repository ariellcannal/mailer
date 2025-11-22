<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controlador para gerenciamento básico de arquivos do editor.
 */
class FileManagerController extends BaseController
{
    /**
     * Lista arquivos disponíveis para utilização no editor.
     *
     * @return ResponseInterface
     */
    public function list(): ResponseInterface
    {
        $files = $this->mapExistingFiles();

        return $this->response->setJSON([
            'success' => true,
            'files' => $files,
        ]);
    }

    /**
     * Realiza upload de um novo arquivo para o repositório público.
     *
     * @return ResponseInterface
     */
    public function upload(): ResponseInterface
    {
        $upload = $this->request->getFile('file');

        if ($upload === null || !$upload->isValid() || $upload->hasMoved()) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Arquivo inválido para upload.',
            ])->setStatusCode(400);
        }

        if ($upload->getSizeByUnit('mb') > 5) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Tamanho máximo permitido é 5MB.',
            ])->setStatusCode(400);
        }

        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ];

        if (!in_array($upload->getMimeType(), $allowedMimeTypes, true)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Tipo de arquivo não permitido. Envie apenas imagens.',
            ])->setStatusCode(400);
        }

        $targetDirectory = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads';

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Não foi possível preparar o diretório de uploads.',
            ])->setStatusCode(500);
        }

        $newName = $upload->getRandomName();
        $upload->move($targetDirectory, $newName);

        $filePath = 'uploads/' . $newName;

        return $this->response->setJSON([
            'success' => true,
            'file' => [
                'name' => $upload->getClientName(),
                'path' => $filePath,
                'url' => base_url($filePath),
            ],
        ]);
    }

    /**
     * Mapeia arquivos públicos disponíveis.
     *
     * @return array<int, array<string, string|int>>
     */
    private function mapExistingFiles(): array
    {
        $directory = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads';

        if (!is_dir($directory)) {
            return [];
        }

        $files = [];
        $entries = scandir($directory) ?: [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $directory . DIRECTORY_SEPARATOR . $entry;

            if (is_file($fullPath)) {
                $files[] = [
                    'name' => $entry,
                    'path' => 'uploads/' . $entry,
                    'url' => base_url('uploads/' . $entry),
                    'size' => filesize($fullPath) ?: 0,
                    'updated_at' => date('Y-m-d H:i:s', (int) filemtime($fullPath)),
                ];
            }
        }

        usort($files, static function (array $first, array $second): int {
            return strcmp((string) $second['updated_at'], (string) $first['updated_at']);
        });

        return $files;
    }
}
