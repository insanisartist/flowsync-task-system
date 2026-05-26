<?php
/**
 * Экспорт задач в Excel и Word.
 *
 * Что делает:
 * - берет задачи с учетом активных фильтров;
 * - выгружает их в Excel или Word;
 * - отдает файл на скачивание в браузер.
 *
 * Как открыть:
 * - файл вызывается по кнопкам со страницы tasks.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/tasks.php';

requireAuth();

$autoloadPath = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoloadPath)) {
    exit('Не найден файл vendor/autoload.php. Сначала установите библиотеки через Composer.');
}

require_once $autoloadPath;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

$format = trim($_GET['format'] ?? '');
$filters = getTaskFilters();
$tasks = getTasks($pdo, $filters);
$reportDate = date('Y-m-d_H-i');

if ($format === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Отчет по задачам');

    $headers = ['ID', 'Задача', 'Проект', 'Статус', 'Приоритет', 'Создал', 'Исполнитель', 'Проверяющий', 'Дедлайн', 'Создана'];

    foreach ($headers as $index => $header) {
        $column = chr(65 + $index);
        $sheet->setCellValue($column . '1', $header);
        $sheet->getStyle($column . '1')->getFont()->setBold(true);
    }

    $rowNumber = 2;

    foreach ($tasks as $task) {
        $sheet->setCellValue('A' . $rowNumber, (int) $task['id']);
        $sheet->setCellValue('B' . $rowNumber, $task['title']);
        $sheet->setCellValue('C' . $rowNumber, $task['project_name']);
        $sheet->setCellValue('D' . $rowNumber, getStatusLabel($task['status']));
        $sheet->setCellValue('E' . $rowNumber, getPriorityLabel($task['priority']));
        $sheet->setCellValue('F' . $rowNumber, $task['creator_name']);
        $sheet->setCellValue('G' . $rowNumber, $task['executor_name'] ?? 'не назначен');
        $sheet->setCellValue('H' . $rowNumber, $task['reviewer_name'] ?? 'не назначен');
        $sheet->setCellValue('I' . $rowNumber, $task['due_date'] ?? '-');
        $sheet->setCellValue('J' . $rowNumber, $task['created_at']);
        $rowNumber++;
    }

    foreach (range('A', 'J') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="tasks_report_' . $reportDate . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

if ($format === 'word') {
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();

    $section->addText('Отчет по задачам', ['bold' => true, 'size' => 16]);
    $section->addText('Дата формирования: ' . date('d.m.Y H:i'));
    $section->addTextBreak(1);

    foreach ($tasks as $task) {
        $section->addText('Задача #' . $task['id'] . ': ' . $task['title'], ['bold' => true]);
        $section->addText('Проект: ' . $task['project_name']);
        $section->addText('Статус: ' . getStatusLabel($task['status']));
        $section->addText('Приоритет: ' . getPriorityLabel($task['priority']));
        $section->addText('Создал: ' . $task['creator_name']);
        $section->addText('Исполнитель: ' . ($task['executor_name'] ?? 'не назначен'));
        $section->addText('Проверяющий: ' . ($task['reviewer_name'] ?? 'не назначен'));
        $section->addText('Дедлайн: ' . ($task['due_date'] ?? 'не указан'));
        $section->addText('Описание: ' . ($task['description'] !== '' ? $task['description'] : 'нет описания'));
        $section->addTextBreak(1);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="tasks_report_' . $reportDate . '.docx"');
    header('Cache-Control: max-age=0');

    $writer = IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save('php://output');
    exit;
}

exit('Неверный формат экспорта.');
