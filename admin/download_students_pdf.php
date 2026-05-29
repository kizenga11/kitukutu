<?php
session_start();
include "../includes/config.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$form_level = isset($_GET['form_level']) ? mysqli_real_escape_string($conn, $_GET['form_level']) : '';
$stream     = isset($_GET['stream'])     ? mysqli_real_escape_string($conn, $_GET['stream'])     : '';

$where = "WHERE s.is_active=1";
if (!empty($form_level)) $where .= " AND s.form_level='$form_level'";
if (!empty($stream))     $where .= " AND s.stream='$stream'";

$students = mysqli_query($conn, "
    SELECT s.first_name, s.second_name, s.last_name, s.sex, s.form_level, s.stream, s.registration_no
    FROM students s
    $where
    ORDER BY s.form_level, s.stream, s.first_name, s.last_name
");

$count = $students ? mysqli_num_rows($students) : 0;

require_once "../vendor/fpdf.php";

class StudentListPdf extends FPDF {
    function Header() {
        $logoPath = __DIR__ . '/../assets/logo.png';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 8, 6, 20, 20);
        }
        $this->SetFont('Times', '', 10);
        $this->Cell(0, 5, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'HALMASHAURI YA WILAYA YA IRAMBA'), 0, 1, 'C');
        $this->SetFont('Times', 'B', 16);
        $this->Cell(0, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'SHULE YA AMALI KITUKUTU'), 0, 1, 'C');
        $this->SetFont('Times', '', 10);
        $this->Cell(0, 5, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'S.L.P 155, IRAMBA'), 0, 1, 'C');
        $this->Ln(2);
        $this->SetFont('Times', 'B', 13);
        $this->Cell(0, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'ORODHA YA WANAFUNZI NA NAMBA ZA USAJILI'), 0, 1, 'C');
        $this->SetFont('Times', 'I', 10);
        $this->Cell(0, 5, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Kwa ajili ya kuwaunganisha wazazi kwenye mfumo'), 0, 1, 'C');
        $this->Line(20, $this->GetY() + 2, 277, $this->GetY() + 2);
        $this->Ln(6);
    }

    function Footer() {
        $this->SetY(-14);
        $this->SetFont('Times', '', 9);
        $this->Cell(0, 5, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Orodha imetolewa na Ofisi ya Taaluma | Tarehe: ' . date('d/m/Y')), 0, 0, 'C');
    }
}

$pdf = new StudentListPdf('L', 'mm', 'A4');
$pdf->SetAutoPageBreak(true, 16);
$pdf->SetMargins(8, 8, 8);
$pdf->AddPage();

$col_w = [16, 100, 96];
$table_w = array_sum($col_w);
$center_x = (297 - $table_w) / 2;

$pdf->SetX($center_x);
$pdf->SetFont('Times', 'B', 14);
$pdf->SetFillColor(26, 43, 76);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell($col_w[0], 12, 'NA', 1, 0, 'C', true);
$pdf->Cell($col_w[1], 12, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'JINA LA MWANAFUNZI'), 1, 0, 'C', true);
$pdf->Cell($col_w[2], 12, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'NAMBARI YA USAJILI'), 1, 1, 'C', true);

$pdf->SetTextColor(0, 0, 0);
$i = 0;
while ($s = mysqli_fetch_assoc($students)) {
    $i++;
    $name = trim($s['first_name'] . ' ' . ($s['second_name'] ? $s['second_name'] . ' ' : '') . $s['last_name']);
    $reg = $s['registration_no'] ?: '-';

    $fill = ($i % 2 == 0);
    if ($fill) $pdf->SetFillColor(245, 247, 250);
    else $pdf->SetFillColor(255, 255, 255);

    $pdf->SetFont('Times', '', 12);
    $pdf->SetX($center_x);
    $pdf->Cell($col_w[0], 11, (string)$i, 1, 0, 'C', $fill);
    $pdf->Cell($col_w[1], 11, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $name), 1, 0, 'L', $fill);
    $pdf->Cell($col_w[2], 11, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $reg), 1, 1, 'C', $fill);
}

$pdf->Ln(6);
$pdf->SetFont('Times', 'B', 11);
$pdf->SetX($center_x);
$pdf->Cell($table_w, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', "Jumla ya wanafunzi: $count"), 1, 1, 'C');

$file_label = 'Wanafunzi_Wote';
if (!empty($form_level)) $file_label .= '_' . str_replace(' ', '', $form_level);
if (!empty($stream)) $file_label .= '_' . $stream;

$pdf->Output('D', $file_label . '_' . date('Ymd') . '.pdf');
exit;
