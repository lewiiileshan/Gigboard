<?php
namespace App\Controllers;

use App\Core\{Auth, Session, Url};
use App\Models\{Application, Gig, Review};

/** Leaving a review (FR: ratings). Only the client and the hired freelancer of a completed gig, once each. */
class ReviewController
{
    /** POST /gigs/{id}/review */
    public function store(int $gigId): void
    {
        $user  = Auth::requireLogin();
        $gig   = Gig::find($gigId) ?? abort(404, 'Gig not found');
        $hired = Application::accepted($gigId);
        Session::checkCsrf();

        $isOwner = (int) $gig['client_id'] === (int) $user['id'];
        $isHired = $hired && (int) $hired['freelancer_id'] === (int) $user['id'];
        if (!$isOwner && !$isHired)         abort(404, 'Gig not found');
        if ($gig['status'] !== 'completed') abort(403, 'Reviews open once the gig is completed.');

        $back      = '/gigs/' . $gigId;
        $revieweeId = $isOwner ? (int) $hired['freelancer_id'] : (int) $gig['client_id'];
        $rating    = is_string($_POST['rating'] ?? null) ? (int) $_POST['rating'] : 0;
        $comment   = is_string($_POST['comment'] ?? null) ? trim($_POST['comment']) : '';

        $error = null;
        if ($rating < 1 || $rating > 5)       $error = 'Choose a rating from 1 to 5.';
        elseif (mb_strlen($comment) > 500)    $error = 'The comment is too long. Shorten it to 500 characters or fewer.';
        elseif (Review::exists($gigId, (int) $user['id'])) $error = 'This gig already has your review.';

        if ($error) {
            Session::keepOld(['rating' => (string) $rating, 'comment' => $comment]);
            Session::flash('error', $error);
            Url::redirect($back);
        }

        Review::create($gigId, (int) $user['id'], $revieweeId, $rating, $comment !== '' ? $comment : null);
        Session::clearOld();
        Session::flash('success', 'Review saved. Thank you.');
        Url::redirect($back);
    }
}
