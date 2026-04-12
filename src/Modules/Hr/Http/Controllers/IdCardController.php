<?php

namespace App\Modules\Hr\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Modules\Hr\Models\EmployeeProfile; // Make sure to import your EmployeeProfile model
use SimpleSoftwareIO\QrCode\Facades\QrCode; // We'll add this later for QR code generation
use Illuminate\Routing\Controller;


use Barryvdh\DomPDF\Facade\Pdf; // Import the PDF facade



class IdCardController extends Controller
{



    public function showEmployeeIdCard($id)
    {


        // Authorization check (very important)
        // $this->authorize('viewIdCard', User::find($userId)); // optional but recommended

        $employee = EmployeeProfile::with('user')
                                    ->where('user_id', $id)
                                    ->first();


        if (!$employee) {
            return redirect()->back()->with('error', 'Employee profile not found.');
        }

        /*$qrCodeSvg = QrCode::size(140)->generate(json_encode([
            'employee_id' => $employee->employee_id,
        ]));*/

        $qrCodeSvg = QrCode::size(140)->generate($id);

        return view('hr::id_card.show', compact('employee', 'qrCodeSvg'));
    }




    public function downloadEmployeeIdCard($id)
    {


        $employee = EmployeeProfile::with('user')
                                    ->where('user_id', $id)
                                    ->first();

        if (!$employee) {
            return redirect()->back()->with('error', 'Your employee profile could not be found for download.');
        }

      // --- Fallback: Generate QR Code as SVG, then Base64 Encode it ---
        /*$qrCodeData = json_encode([
            'employee_id' => $employee->employee_id,
            //'user_id' => $employee->user_id,
            //'timestamp' => now()->timestamp,
        ]);*/

        $qrCodeData = $id;

        // 1. Generate QR code as SVG (this doesn't require Imagick)
        $qrCodeSvgString = QrCode::size(140)->margin(1)->generate($qrCodeData); // Default format is SVG

        // 2. Base64 encode the SVG string
        $qrCodeBase64 = base64_encode($qrCodeSvgString);

        // 3. Create the data URI for embedding in an <img> tag
        $qrCodeImageSrc = 'data:image/svg+xml;base64,' . $qrCodeBase64;


        // Render the blade view into HTML
        $pdf = Pdf::loadView('hr::id_card.show_printable', compact('employee', 'qrCodeImageSrc'));

        // You might want to set paper size and orientation specific for ID cards.
        // ID card size is usually CR80 (85.60 × 53.98 mm). DomPDF works better with points.
        // 1mm = 2.83465 points
        // 85.60mm = 242.34 points
        // 53.98mm = 152.92 points
        // You might need to adjust CSS in show_printable.blade.php for these exact dimensions.
        // For simplicity, let's keep it letter/portrait and adjust the inner card div in CSS.
        // $pdf->setPaper([0, 0, 242.34, 152.92], 'landscape'); // Custom size for CR80 if entire PDF is just the card

        // Return the PDF for download
        return $pdf->download('ID_Card_' . ($employee->user->name ?? 'unknown') . '.pdf');


    }







}
