<?php
namespace App\Controllers;

use App\Core\{Auth, FileStore, Session, Url};
use App\Models\{Application, Gig, Submission};

/**
 * Delivering work. The files are private:
 *   - only the HIRED freelancer can upload to a gig,
 *   - only that gig's CLIENT and the freelancer who uploaded can download,
 *   - everyone else gets a "not found" page, whether or not the file exists.
 */
class SubmissionController
{
    /** POST /gigs/{id}/submit */
    public function store(int $gigId): void
    {
        $user = Auth::requireRole('freelancer');
        $gig  = Gig::find($gigId) ?? abort(404, 'Gig not found');

        // A file bigger than the server limit arrives as an empty form; say so instead of "invalid token".
        if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            Session::flash('error', 'That file is too large. The limit is 10 MB.');
            Url::redirect('/gigs/' . $gigId);
        }
        Session::checkCsrf();

        $hired = Application::accepted($gigId);
        if (!$hired || (int) $hired['freelancer_id'] !== (int) $user['id']) abort(403, 'Only the hired freelancer can upload work for this gig.');
        if ($gig['status'] !== 'in_progress')                                abort(403, 'This gig is not in progress.');

        $note = is_string($_POST['note'] ?? null) ? trim($_POST['note']) : '';
        if (mb_strlen($note) > 1000) {
            Session::flash('error', 'Your message is too long (1000 characters at most).');
            Url::redirect('/gigs/' . $gigId);
        }

        [$file, $error] = FileStore::save($_FILES['work'] ?? null);
        if ($error) {
            Session::flash('error', $error);
            Url::redirect('/gigs/' . $gigId);
        }

        Submission::create($gigId, (int) $user['id'], $note, $file);
        Session::flash('success', 'Work uploaded. Only the client can download it.');
        Url::redirect('/gigs/' . $gigId);
    }

    /** GET /submissions/{id}/download */
    public function download(int $id): void
    {
        $user = Auth::requireLogin();
        $sub  = Submission::find($id) ?? abort(404, 'File not found');
        $gig  = Gig::find((int) $sub['gig_id']) ?? abort(404, 'File not found');

        $isClient     = (int) $gig['client_id'] === (int) $user['id'];
        $isUploader   = (int) $sub['freelancer_id'] === (int) $user['id'];
        if (!$isClient && !$isUploader) abort(404, 'File not found');

        FileStore::send($sub);
    }

    /** POST /submissions/{id}/approve */
    public function approve(int $id): void
    {
        [$sub, $gig] = $this->reviewable($id);
        Session::checkCsrf();

        if (Submission::approve($sub)) Session::flash('success', 'Work approved. The gig is now completed.');
        else                           Session::flash('error', 'This gig is not in progress, so nothing changed.');
        Url::redirect('/gigs/' . (int) $gig['id']);
    }

    /** POST /submissions/{id}/changes */
    public function requestChanges(int $id): void
    {
        [$sub, $gig] = $this->reviewable($id);
        Session::checkCsrf();

        $feedback = is_string($_POST['feedback'] ?? null) ? trim($_POST['feedback']) : '';
        if (mb_strlen($feedback) < 10 || mb_strlen($feedback) > 1000) {
            Session::flash('error', 'Say what should change, in 10 to 1000 characters.');
            Url::redirect('/gigs/' . (int) $gig['id']);
        }

        Submission::requestChanges((int) $sub['id'], $feedback);
        Session::flash('success', 'Changes requested. The freelancer can upload a new version.');
        Url::redirect('/gigs/' . (int) $gig['id']);
    }

    /** The submission must belong to one of the logged-in client's in-progress gigs and still be awaiting review. */
    private function reviewable(int $id): array
    {
        $user = Auth::requireRole('client');
        $sub  = Submission::find($id) ?? abort(404, 'File not found');
        $gig  = Gig::find((int) $sub['gig_id']) ?? abort(404, 'File not found');

        if ((int) $gig['client_id'] !== (int) $user['id']) abort(404, 'File not found');
        if ($sub['status'] !== 'submitted' || $gig['status'] !== 'in_progress') abort(403, 'This work has already been reviewed.');

        return [$sub, $gig];
    }
}
