<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\TripayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Menerima callback notifikasi status dari Tripay. Rute ini
 * tidak memakai auth dan dikecualikan dari CSRF (lihat
 * bootstrap/app.php), karena dipanggil server ke server oleh
 * Tripay. Keamanannya adalah verifikasi signature dari raw
 * JSON body; tanpa signature yang sah, tidak ada data yang
 * diubah dan responsnya 403.
 *
 * Tripay mengulang callback sampai 3 kali dengan jeda 2 menit
 * bila tidak menerima {"success": true}, jadi pemrosesan yang
 * valid selalu menjawab sukses, termasuk data yang sengaja
 * diabaikan (nominal atau referensi tidak cocok, merchant ref
 * tidak dikenal, event bukan payment_status).
 */
class TripayCallbackController extends Controller
{
    public function __invoke(Request $request, TripayService $tripay): JsonResponse
    {
        $rawJson = $request->getContent();
        $signature = $request->header('X-Callback-Signature');

        if (! $tripay->verifyCallbackSignature($signature, $rawJson)) {
            Log::warning('Callback Tripay ditolak: signature tidak sah.');

            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 403);
        }

        $data = json_decode($rawJson, true);

        if (! is_array($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data sent by payment gateway',
            ]);
        }

        $event = $request->header('X-Callback-Event');

        if ($event !== null && $event !== 'payment_status') {
            Log::info('Callback Tripay diabaikan: event bukan payment_status.', ['event' => $event]);

            return response()->json(['success' => true]);
        }

        $payment = null;

        if (! empty($data['merchant_ref'])) {
            $payment = Payment::query()
                ->where('merchant_ref', $data['merchant_ref'])
                ->first();
        }

        if ($payment === null && ! empty($data['reference'])) {
            $payment = Payment::query()
                ->where('reference', $data['reference'])
                ->first();
        }

        if ($payment === null) {
            Log::warning('Callback Tripay diabaikan: pembayaran tidak ditemukan.', [
                'merchant_ref' => $data['merchant_ref'] ?? null,
                'reference' => $data['reference'] ?? null,
            ]);

            return response()->json(['success' => true]);
        }

        $result = $tripay->applyTransactionStatus(
            $payment,
            TripayService::normalizeCallback($data),
            $data,
            'callback'
        );

        Log::info('Callback Tripay diproses.', [
            'payment_id' => $payment->id,
            'outcome' => $result['outcome'],
        ]);

        return response()->json(['success' => true]);
    }
}
