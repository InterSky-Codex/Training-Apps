<?php
session_start();
include 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'vendor/autoload.php'; 
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

$ytd_start_date = isset($_GET['ytd_start_date']) ? $_GET['ytd_start_date'] : null;
$ytd_end_date = isset($_GET['ytd_end_date']) ? $_GET['ytd_end_date'] : null;

if (!$ytd_start_date || !$ytd_end_date) {
    $_SESSION['error'] = "Invalid report parameters.";
    header("Location: admin_dashboard.php");
    exit();
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('YTD Report');

$sheet->setCellValue('A1', 'LAPORAN TRAINING: Year-To-Date');
$sheet->setCellValue('A2', 'Tanggal: ' . date('d-m-Y'));
$sheet->mergeCells('A1:H1');
$sheet->mergeCells('A2:H2');

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

$headers = ['Employee Id', 'Name', 'Department', 'Feedback', 'Duration', 'Tanggal', 'Title', 'Signature'];
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
        'startColor' => [
            'argb' => 'FFFFA500', 
        ],
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

$sheet->getStyle('A4:H4')->applyFromArray($headerStyle);

$query = "SELECT tc.userid, u.name, u.department, u.feedback, tc.duration, tc.created_at, at.title, u.signature 
          FROM users u 
          JOIN training_codes tc ON u.id = tc.user_id 
          LEFT JOIN admin_titles at ON u.id = at.user_id 
          WHERE tc.created_at BETWEEN ? AND ? 
          AND u.department IS NOT NULL AND u.department <> ''"; 
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $ytd_start_date, $ytd_end_date);
$stmt->execute();
$result = $stmt->get_result();

$row = 5;
while ($rowData = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $row, $rowData['userid']); 
    $sheet->setCellValue('B' . $row, $rowData['name']);
    $sheet->setCellValue('C' . $row, $rowData['department']);
    $sheet->setCellValue('D' . $row, $rowData['feedback']);
    $sheet->setCellValue('E' . $row, $rowData['duration']);
    $sheet->setCellValue('F' . $row, $rowData['created_at']);
    $sheet->setCellValue('G' . $row, $rowData['title']);

    // Signature
    if (!empty($rowData['signature'])) {
        $signatureData = explode(',', $rowData['signature'])[1]; 
        $signatureImage = base64_decode($signatureData);
        $imageFileName = 'signature_' . $rowData['userid'] . '.png'; 
        file_put_contents($imageFileName, $signatureImage); 

        $drawing = new Drawing();
        $drawing->setName('Signature');
        $drawing->setDescription('Signature');
        $drawing->setPath($imageFileName); 
        $drawing->setHeight(50);
        $drawing->setCoordinates('H' . $row);
        $drawing->setWorksheet($sheet); 
        $sheet->getRowDimension($row)->setRowHeight(50);
    } else {
        $sheet->setCellValue('H' . $row, 'Tidak Ada Signature');
    }

    $row++;
}

foreach (range('A', 'H') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="ytd_report.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

foreach ($result as $rowData) {
    if (!empty($rowData['signature'])) {
        $imageFileName = 'signature_' . $rowData['userid'] . '.png'; 
        if (file_exists($imageFileName)) {
            unlink($imageFileName); 
        }
    }
}

exit();
?>
