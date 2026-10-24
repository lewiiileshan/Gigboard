<?php
namespace App\Controllers;

use App\Core\{Auth, Session, Url, View};
use App\Models\{Category, Gig};

/**
 * Gigs: browse and search (UC05), view, post (UC04), edit and cancel (FR08).
 * Applying for a gig comes in step 3.
 */
class GigController
{
    private const PER_PAGE = 10;

    /** GET /gigs: browse and search open gigs */
    public function index(): void
    {
        Auth::requireLogin();

        $filters = [
            'q'        => $this->text($_GET, 'q'),
            'category' => (int) $this->text($_GET, 'category'),
            'min'      => $this->text($_GET, 'min'),
            'max'      => $this->text($_GET, 'max'),
            'location' => $this->text($_GET, 'location'),
            'sort'     => $this->text($_GET, 'sort') ?: 'newest',
        ];
        $page = max(1, (int) $this->text($_GET, 'page'));

        [$gigs, $total] = Gig::search($filters, $page, self::PER_PAGE);

        View::render('gigs/index', [
            'title'      => 'Browse gigs',
            'nav'        => 'gigs',
            'filters'    => $filters,
            'gigs'       => $gigs,
            'total'      => $total,
            'page'       => $page,
            'pages'      => max(1, (int) ceil($total / self::PER_PAGE)),
            'categories' => Category::all(),
            'sorts'      => Gig::sortNames(),
        ]);
    }

    /** GET /gigs/{id}: one gig's details */
    public function show(int $id): void
    {
        $user = Auth::requireLogin();
        $gig  = Gig::find($id) ?? abort(404, 'Gig not found');

        // Only the owner may see their own gig once it is no longer open to the public.
        if ($gig['status'] !== 'open' && (int) $gig['client_id'] !== (int) $user['id']) {
            abort(404, 'Gig not found');
        }

        View::render('gigs/show', [
            'title'   => $gig['title'],
            'nav'     => 'gigs',
            'gig'     => $gig,
            'isOwner' => (int) $gig['client_id'] === (int) $user['id'],
            'user'    => $user,
        ]);
    }

    /** GET /gigs/create */
    public function create(): void
    {
        Auth::requireRole('client');
        View::render('gigs/form', [
            'title' => 'Post a gig', 'nav' => 'post', 'gig' => null,
            'categories' => Category::all(), 'action' => url('/gigs/create'),
        ]);
    }

    /** POST /gigs/create */
    public function store(): void
    {
        $user = Auth::requireRole('client');
        Session::checkCsrf();

        [$data, $error] = $this->validate($_POST);
        if ($error) {
            Session::keepOld($this->formInput($_POST));
            Session::flash('error', $error);
            Url::redirect('/gigs/create');
        }

        $id = Gig::create((int) $user['id'], $data);
        Session::clearOld();
        Session::flash('success', 'Your gig is live.');
        Url::redirect('/gigs/' . $id);
    }

    /** GET /gigs/{id}/edit */
    public function edit(int $id): void
    {
        $gig = $this->ownedOpenGig($id);
        View::render('gigs/form', [
            'title' => 'Edit gig', 'nav' => 'mine', 'gig' => $gig,
            'categories' => Category::all(), 'action' => url('/gigs/' . $id . '/edit'),
        ]);
    }

    /** POST /gigs/{id}/edit */
    public function update(int $id): void
    {
        $this->ownedOpenGig($id);
        Session::checkCsrf();

        [$data, $error] = $this->validate($_POST);
        if ($error) {
            Session::keepOld($this->formInput($_POST));
            Session::flash('error', $error);
            Url::redirect('/gigs/' . $id . '/edit');
        }

        Gig::update($id, $data);
        Session::clearOld();
        Session::flash('success', 'Gig updated.');
        Url::redirect('/gigs/' . $id);
    }

    /** POST /gigs/{id}/cancel */
    public function cancel(int $id): void
    {
        $this->ownedOpenGig($id);
        Session::checkCsrf();

        Gig::cancel($id);
        Session::flash('success', 'Gig cancelled.');
        Url::redirect('/my-gigs');
    }

    /** GET /my-gigs: the client's own gigs, whatever their status */
    public function mine(): void
    {
        $user = Auth::requireRole('client');
        View::render('gigs/mine', [
            'title' => 'My gigs', 'nav' => 'mine', 'gigs' => Gig::byClient((int) $user['id']),
        ]);
    }

    // ------------------------------------------------------------------ helpers

    /** The gig must exist, belong to the logged-in client and still be open. */
    private function ownedOpenGig(int $id): array
    {
        $user = Auth::requireRole('client');
        $gig  = Gig::find($id) ?? abort(404, 'Gig not found');

        if ((int) $gig['client_id'] !== (int) $user['id']) abort(403, 'You can only change your own gigs.');
        if ($gig['status'] !== 'open')                      abort(403, 'Only open gigs can be changed.');

        return $gig;
    }

    /** Read a form/query value as trimmed text (ignores things like ?q[]=x). */
    private function text(array $source, string $key): string
    {
        $v = $source[$key] ?? '';
        return is_string($v) ? trim($v) : '';
    }

    private function formInput(array $in): array
    {
        $out = [];
        foreach (['title', 'description', 'category_id', 'budget', 'location', 'deadline'] as $k) {
            $out[$k] = $this->text($in, $k);
        }
        return $out;
    }

    /** Server-side validation (FR07). Returns [cleanData, errorMessage|null]. */
    private function validate(array $in): array
    {
        $title       = $this->text($in, 'title');
        $description = $this->text($in, 'description');
        $categoryId  = (int) $this->text($in, 'category_id');
        $budgetRaw   = str_replace(',', '', $this->text($in, 'budget'));
        $location    = $this->text($in, 'location') ?: 'Remote';
        $deadline    = $this->text($in, 'deadline');

        $fail = fn(string $m) => [null, $m];

        if (mb_strlen($title) < 5 || mb_strlen($title) > 150)           return $fail('The title must be 5 to 150 characters.');
        if (mb_strlen($description) < 20)                                return $fail('Describe the gig in at least 20 characters so freelancers know what to do.');
        if (mb_strlen($description) > 5000)                              return $fail('The description is too long (5000 characters at most).');
        if (!Category::exists($categoryId))                              return $fail('Choose a category.');
        if (!is_numeric($budgetRaw) || $budgetRaw < 100 || $budgetRaw > 1000000) return $fail('The budget must be between KES 100 and KES 1,000,000.');
        if (mb_strlen($location) > 100)                                  return $fail('The location is too long.');

        if ($deadline === '') {
            $deadline = null;
        } else {
            $d = \DateTime::createFromFormat('Y-m-d', $deadline);
            if (!$d || $d->format('Y-m-d') !== $deadline)                return $fail('Enter a valid deadline date.');
            if ($deadline < date('Y-m-d'))                               return $fail('The deadline cannot be in the past.');
        }

        return [[
            'title' => $title, 'description' => $description, 'category_id' => $categoryId,
            'budget' => round((float) $budgetRaw, 2), 'location' => $location, 'deadline' => $deadline,
        ], null];
    }
}
