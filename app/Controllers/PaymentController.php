<?php
namespace App\Controllers;

use App\Core\{Auth, Session, Url, View};
use App\Models\Payment;

/** Recording who has paid whom. No money moves through GigBoard. */
class PaymentController
{
    /** GET /payments */
    public function index(): void
    {
        $user = Auth::requireLogin();
        $rows = Payment::forUser((int) $user['id']);
        View::render('payments/index', [
            'title' => 'Payments', 'nav' => 'payments', 'user' => $user,
            'rows' => $rows, 'sum' => Payment::summary($rows),
        ]);
    }

    /** POST /payments/{id}/paid : the client records that they paid (optional M-Pesa code). */
    public function markPaid(int $id): void
    {
        [$user, $pay] = $this->load($id);
        Session::checkCsrf();
        if ((int) $pay['client_id'] !== (int) $user['id']) abort(403, 'Only the client can record a payment.');
        if ($pay['gig_status'] !== 'completed')            abort(403, 'Approve the work first. Payment is recorded after the gig is completed.');

        $ref = strtoupper(trim(is_string($_POST['reference'] ?? null) ? $_POST['reference'] : ''));
        if ($ref !== '' && !preg_match('/^[A-Z0-9]{10}$/', $ref)) {
            Session::flash('error', 'An M-Pesa code has 10 letters and numbers, for example QJK7X8Y9ZA. Leave it blank if you do not have one.');
            Url::redirect('/gigs/' . (int) $pay['gig_id']);
        }

        if (Payment::markPaid((int) $pay['id'], $ref !== '' ? $ref : null)) Session::flash('success', 'Payment recorded. ' . $pay['freelancer_name'] . ' can now confirm it.');
        else                                                                   Session::flash('error', 'This payment was already recorded.');
        Url::redirect('/gigs/' . (int) $pay['gig_id']);
    }

    /** POST /payments/{id}/confirm : the freelancer says it arrived. */
    public function confirm(int $id): void
    {
        [$user, $pay] = $this->load($id);
        Session::checkCsrf();
        if ((int) $pay['freelancer_id'] !== (int) $user['id']) abort(403, 'Only the freelancer can confirm a payment.');

        if (Payment::confirm((int) $pay['id'])) Session::flash('success', 'Thank you. The payment is confirmed.');
        else                                    Session::flash('error', 'There is nothing to confirm right now.');
        Url::redirect('/gigs/' . (int) $pay['gig_id']);
    }

    /** POST /payments/{id}/dispute : the freelancer says it did NOT arrive. */
    public function dispute(int $id): void
    {
        [$user, $pay] = $this->load($id);
        Session::checkCsrf();
        if ((int) $pay['freelancer_id'] !== (int) $user['id']) abort(403, 'Only the freelancer can report a missing payment.');

        if (Payment::dispute((int) $pay['id'])) Session::flash('success', 'Reported. The client will be asked to check and record the payment again.');
        else                                    Session::flash('error', 'There is nothing to report right now.');
        Url::redirect('/gigs/' . (int) $pay['gig_id']);
    }

    /** Only the two people on the gig may touch its payment; everyone else just gets "not found". */
    private function load(int $id): array
    {
        $user = Auth::requireLogin();
        $pay  = Payment::find($id) ?? abort(404, 'Payment not found');
        if ((int) $user['id'] !== (int) $pay['client_id'] && (int) $user['id'] !== (int) $pay['freelancer_id']) abort(404, 'Payment not found');
        return [$user, $pay];
    }
}
