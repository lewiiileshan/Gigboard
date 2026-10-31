<?php
namespace App\Controllers;

use App\Core\{Auth, Session, Url, View};
use App\Models\{Application, Gig};

/** Applying for gigs (freelancer) and hiring (client). */
class ApplicationController
{
    /** POST /gigs/{id}/apply */
    public function apply(int $gigId): void
    {
        $user = Auth::requireRole('freelancer');
        $gig  = Gig::find($gigId) ?? abort(404, 'Gig not found');
        Session::checkCsrf();

        if ($gig['status'] !== 'open') abort(403, 'This gig is no longer open.');

        $proposal = is_string($_POST['proposal'] ?? null) ? trim($_POST['proposal']) : '';
        $priceRaw = str_replace(',', '', is_string($_POST['price'] ?? null) ? trim($_POST['price']) : '');
        $back     = '/gigs/' . $gigId;

        $error = null;
        if (mb_strlen($proposal) < 20)                                    $error = 'Write at least 20 characters about how you will do the work.';
        elseif (mb_strlen($proposal) > 2000)                              $error = 'Your proposal is too long (2000 characters at most).';
        elseif (!is_numeric($priceRaw) || $priceRaw < 100 || $priceRaw > 1000000) $error = 'Your price must be between KES 100 and KES 1,000,000.';
        elseif (Application::mineFor($gigId, (int) $user['id']))          $error = 'You have already applied for this gig.';

        if ($error) {
            Session::keepOld(['proposal' => $proposal, 'price' => (string) ($_POST['price'] ?? '')]);
            Session::flash('error', $error);
            Url::redirect($back);
        }

        Application::create($gigId, (int) $user['id'], $proposal, round((float) $priceRaw, 2));
        Session::clearOld();
        Session::flash('success', 'Application sent. The client will see it.');
        Url::redirect($back);
    }

    /** POST /applications/{id}/hire */
    public function hire(int $id): void
    {
        $user = Auth::requireRole('client');
        $app  = Application::find($id) ?? abort(404, 'Application not found');
        $gig  = Gig::find((int) $app['gig_id']) ?? abort(404, 'Gig not found');
        Session::checkCsrf();

        if ((int) $gig['client_id'] !== (int) $user['id']) abort(403, 'You can only hire for your own gigs.');
        if ($app['status'] !== 'pending')                  abort(403, 'This application can no longer be accepted.');

        if (!Application::hire($app)) {
            Session::flash('error', 'This gig is no longer open, so nobody was hired.');
        } else {
            Session::flash('success', 'Freelancer hired. They can now upload the finished work here.');
        }
        Url::redirect('/gigs/' . (int) $gig['id']);
    }

    /** GET /my-applications */
    public function mine(): void
    {
        $user = Auth::requireRole('freelancer');
        View::render('applications/mine', [
            'title' => 'My applications', 'nav' => 'applied', 'applications' => Application::byFreelancer((int) $user['id']),
        ]);
    }
}
