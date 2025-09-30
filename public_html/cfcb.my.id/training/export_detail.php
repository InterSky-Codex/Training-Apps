<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['title'])) {
    $_SESSION['error'] = "Parameter title tidak ditemukan.";
    header("Location: index");
    exit();
}

$title = urldecode($_GET['title']);
$titleLike = "%" . $title . "%"; // untuk pencocokan LIKE

$stmt = $conn->prepare("
    SELECT u.name, u.department, u.feedback, u.signature, u.created_at, 
           at.title, tc.userid, tc.duration 
    FROM users u
    LEFT JOIN admin_titles at ON u.id = at.user_id
    LEFT JOIN training_codes tc ON u.id = tc.user_id
    WHERE at.title LIKE ? 
      AND u.department IS NOT NULL 
      AND u.department <> ''
");

$stmt->bind_param("s", $titleLike);
$stmt->execute();
$result = $stmt->get_result();

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'LAPORAN TRAINING: ' . $title);
$sheet->setCellValue('A2', 'Tanggal: ' . date('d-m-Y'));

$headerReportStyle = [
    'font' => [
        'bold' => true,
        'size' => 14,
        'name' => 'Arial'
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
    ],
];

$sheet->getStyle('A1:A2')->applyFromArray($headerReportStyle);
$sheet->mergeCells('A1:G1');
$sheet->mergeCells('A2:G2');

$headers = ['Name', 'Department', 'Feedback', 'Signature', 'Employee Id', 'Duration', 'Created At'];
$sheet->fromArray($headers, NULL, 'A4');

$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['argb' => 'FFFFFFFF'],
        'size' => 12,
        'name' => 'Arial'
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FFFFA500'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => '000000'],
        ],
    ],
];

$sheet->getStyle('A4:G4')->applyFromArray($headerStyle);

$rowNumber = 5;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $rowNumber, $row['name']);
    $sheet->setCellValue('B' . $rowNumber, $row['department']);
    $sheet->setCellValue('C' . $rowNumber, $row['feedback']);

    // Tangani signature
    if (!empty($row['signature'])) {
        if (strpos($row['signature'], 'data:image') === 0) {
            $signature_data = preg_replace('/^data:image\/\w+;base64,/', '', $row['signature']);
            $signature_data = base64_decode($signature_data);

            $temp_file = tempnam(sys_get_temp_dir(), 'signature_');
            file_put_contents($temp_file, $signature_data);

            $drawing = new Drawing();
            $drawing->setName('Signature')
                ->setDescription('Signature')
                ->setPath($temp_file)
                ->setCoordinates('D' . $rowNumber)
                ->setHeight(50)
                ->setWorksheet($sheet);
        } else {
            $sheet->setCellValue('D' . $rowNumber, 'Signature tidak valid');
        }
    } else {
        $sheet->setCellValue('D' . $rowNumber, 'Tidak Ada Signature');
    }

    $sheet->setCellValue('E' . $rowNumber, $row['userid']);
    $sheet->setCellValue('F' . $rowNumber, $row['duration'] ?? 'N/A');
    $sheet->setCellValue('G' . $rowNumber, $row['created_at']);

    $sheet->getRowDimension($rowNumber)->setRowHeight(50);
    $rowNumber++;
}

foreach (range('A', 'C') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}
$sheet->getColumnDimension('D')->setWidth(20);
foreach (range('E', 'G') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Detail_Training_' . str_replace('%', '', $title) . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
