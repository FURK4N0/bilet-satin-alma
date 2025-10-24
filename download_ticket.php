<?php
require_once 'config/init.php';

define('FPDF_FONTPATH', __DIR__ . '/lib/font/');
require_once 'lib/fpdf.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$ticket_id = $_GET['ticket_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$ticket_id) {
    die("Geçersiz bilet ID'si.");
}

$stmt = $pdo->prepare(
    "SELECT 
        T.status, T.total_price, T.created_at as purchase_time,
        TR.departure_city, TR.destination_city, TR.departure_time,
        BS.seat_number,
        U.full_name,
        C.name as company_name
     FROM Tickets T
     JOIN Trips TR ON T.trip_id = TR.id
     JOIN User U ON T.user_id = U.id
     JOIN Bus_Company C ON TR.company_id = C.id
     JOIN Booked_Seats BS ON BS.ticket_id = T.id
     WHERE T.id = ? AND T.user_id = ?"
);
$stmt->execute([$ticket_id, $user_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    die("Bilet bulunamadı veya bu bileti görüntüleme yetkiniz yok.");
}

// --- PDF OLUŞTURMA KISMI ---

// ÇÖZÜM: Türkçe karakterleri FPDF'in anlayacağı formata çeviren fonksiyon
function format_text($text) {
    return iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $text);
}

class PDF extends FPDF {
    public $ticket_status; 

    function Watermark() {
        if ($this->ticket_status === 'CANCELLED') {
            $this->SetFont('DejaVu-Bold', '', 36);
            $this->SetTextColor(255, 0, 0); 
            $this->SetXY(0, 140); 
            // ÇÖZÜM: Filigran metnini de fonksiyondan geçir
            $this->Cell(210, 10, format_text('IPTAL EDILMISTIR'), 0, 1, 'C');
        }
    }
}

$pdf = new PDF();
$pdf->ticket_status = $ticket['status'];

$pdf->AddFont('DejaVu', '', 'DejaVuSans.php');
$pdf->AddFont('DejaVu-Bold', '', 'DejaVuSans-Bold.php');

$pdf->AddPage();
$pdf->Watermark(); 
$pdf->SetTextColor(0, 0, 0); 

// --- Bilet İçeriğini PDF'e Yazdırma ---

$pdf->SetFont('DejaVu-Bold', '', 20);
$pdf->Cell(0, 10, format_text('YOLCU BILETI'), 0, 1, 'C');
$pdf->Ln(10); 

$pdf->SetFont('DejaVu-Bold', '', 16);
$pdf->Cell(0, 10, format_text($ticket['company_name']), 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('DejaVu-Bold', '', 12);
$pdf->Cell(40, 8, format_text('Güzergah:'));
$pdf->SetFont('DejaVu', '', 12);
// ÇÖZÜM: Tüm metinler artık format_text() fonksiyonundan geçiriliyor
$pdf->Cell(0, 8, format_text($ticket['departure_city'] . ' -> ' . $ticket['destination_city']));
$pdf->Ln();

$pdf->SetFont('DejaVu-Bold', '', 12);
$pdf->Cell(40, 8, format_text('Kalkış Tarihi:'));
$pdf->SetFont('DejaVu', '', 12);
$pdf->Cell(0, 8, date('d.m.Y H:i', strtotime($ticket['departure_time'])));
$pdf->Ln(10);

$pdf->SetFont('DejaVu-Bold', '', 12);
$pdf->Cell(40, 8, format_text('Yolcu Adı:'));
$pdf->SetFont('DejaVu', '', 12);
$pdf->Cell(0, 8, format_text($ticket['full_name']));
$pdf->Ln();

$pdf->SetFont('DejaVu-Bold', '', 12);
$pdf->Cell(40, 8, format_text('Koltuk No:'));
$pdf->SetFont('DejaVu-Bold', '', 14);
$pdf->Cell(0, 8, $ticket['seat_number']);
$pdf->Ln(10);

$pdf->SetFont('DejaVu', '', 10);
$pdf->Cell(0, 8, format_text('Ödenen Tutar: ' . $ticket['total_price'] . ' TL'), 0, 1, 'R');
$pdf->Cell(0, 5, format_text('Satın Alma Tarihi: ' . date('d.m.Y H:i', strtotime($ticket['purchase_time']))), 0, 1, 'R');

$pdf->Output('D', 'bilet.pdf');
?>