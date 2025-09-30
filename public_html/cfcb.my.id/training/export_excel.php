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

function sanitizeSheetTitle($title) {
    $invalidChars = ['\\', '/', '*', '[', ']', ':', '?'];
    $cleanTitle = str_replace($invalidChars, '', $title);
    return mb_substr($cleanTitle, 0, 31);
}

$mtd_month = isset($_GET['mtd_month']) ? $_GET['mtd_month'] : null;

$spreadsheet = new Spreadsheet();

if ($mtd_month) {
    $query = "SELECT u.name, u.id AS user_id, u.department, u.feedback, u.signature, 
                     tc.duration, tc.created_at, at.title , tc.userid
              FROM users u 
              JOIN training_codes tc ON u.id = tc.user_id 
              JOIN admin_titles at ON u.id = at.user_id 
              WHERE MONTH(tc.created_at) = ? 
              AND YEAR(tc.created_at) = YEAR(CURRENT_DATE) 
              AND u.department IS NOT NULL AND u.department <> ''";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $mtd_month);
} else {
    $_SESSION['error'] = "Invalid report parameters.";
    header("Location: admin_dashboard.php");
    exit();
}

$stmt->execute();
$result = $stmt->get_result();
$dataByTitle = [];
while ($data = $result->fetch_assoc()) {
    $dataByTitle[$data['title']][] = $data;
}

foreach ($dataByTitle as $title => $dataRows) {
    $sheet = $spreadsheet->createSheet();
    $judulSheet = sanitizeSheetTitle($title);
    $sheet->setTitle($judulSheet);

    $sheet->setCellValue('A1', 'LAPORAN TRAINING: ' . $title);
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

    $headers = ['Name', 'Employee Id', 'Department', 'Feedback', 'Signature', 'Duration', 'Created At', 'Status'];
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

    $row = 5; 
    foreach ($dataRows as $rowData) {
        $sheet->setCellValue('A' . $row, $rowData['name']);
        $sheet->setCellValue('B' . $row, $rowData['userid']);
        $sheet->setCellValue('C' . $row, $rowData['department']);
        $sheet->setCellValue('D' . $row, $rowData['feedback']);
        $sheet->setCellValue('F' . $row, $rowData['duration']);
        $sheet->setCellValue('G' . $row, $rowData['created_at']);
        
        $current_date = date('Y-m-d H:i:s');
        $status = ($current_date < $rowData['created_at']) ? "Active" : "Done";
        $sheet->setCellValue('H' . $row, $status);
        
        if (!empty($rowData['signature'])) {
            $signatureData = explode(',', $rowData['signature'])[1];
            $signatureImage = base64_decode($signatureData);
            $imageFileName = 'signature_' . $rowData['user_id'] . '.png';
            file_put_contents($imageFileName, $signatureImage); 

            $drawing = new Drawing();
            $drawing->setName('Signature');
            $drawing->setDescription('Signature');
            $drawing->setPath($imageFileName);
            $drawing->setHeight(50);
            $drawing->setCoordinates('E' . $row); 
            $drawing->setWorksheet($sheet); 

            $sheet->getRowDimension($row)->setRowHeight(50);
        } else {
            $sheet->setCellValue('D' . $row, 'Tidak Ada Signature');
        }

        $row++;
    }

    $sheet->getColumnDimension('A')->setAutoSize(true);
    $sheet->getColumnDimension('B')->setAutoSize(true);
    $sheet->getColumnDimension('C')->setAutoSize(true);
    $sheet->getColumnDimension('D')->setAutoSize(true);
    $sheet->getColumnDimension('E')->setWidth(20); 
    $sheet->getColumnDimension('F')->setAutoSize(true);
    $sheet->getColumnDimension('G')->setAutoSize(true);
    $sheet->getColumnDimension('H')->setAutoSize(true);
}

if ($spreadsheet->getSheetCount() > 1) {
    $spreadsheet->removeSheetByIndex(0);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="training_report_mtd.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

foreach ($dataByTitle as $dataRows) {
    foreach ($dataRows as $rowData) {
        if (!empty($rowData['signature'])) {
            $imageFileName = 'signature_' . $rowData['user_id'] . '.png';
            if (file_exists($imageFileName)) {
                unlink($imageFileName); 
            }
        }
    }
}

exit();
?>
