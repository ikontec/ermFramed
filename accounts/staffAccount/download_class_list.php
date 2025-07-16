<?php
require_once __DIR__ . '/vendor/autoload.php';

include '../../classes/connect.php';

$subject = $_GET['subject'] ?? '';
$class = $_GET['class'] ?? '';

if (!$subject || !$class) {
    die("Missing subject or class.");
}

$stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM students WHERE class = ?");
$stmt->bind_param("s", $class);
$stmt->execute();
$result = $stmt->get_result();

$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue("A1", "First Name");
$sheet->setCellValue("B1", "Middle Name");
$sheet->setCellValue("C1", "Last Name");
$sheet->setCellValue("D1", "Marks");

$rowNum = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue("A$rowNum", $row['first_name']);
    $sheet->setCellValue("B$rowNum", $row['middle_name']);
    $sheet->setCellValue("C$rowNum", $row['last_name']);
    $sheet->setCellValue("D$rowNum", "");
    $rowNum++;
}

$filename = "class_list_{$subject}_{$class}_" . date('Y') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save("php://output");
exit;
?>
