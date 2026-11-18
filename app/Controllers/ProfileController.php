<?php
namespace App\Controllers;

use App\Core\{Auth, Session, Url, View};
use App\Models\{Application, FreelancerProfile, Review, User};

/** Editing your own profile, and viewing a freelancer's profile. */
class ProfileController
{
    /** GET /account/edit */
    public function edit(): void
    {
        $user = Auth::requireLogin();
        View::render('account/edit', [
            'title' => 'Edit profile', 'nav' => 'account', 'user' => $user,
            'profile' => $user['role'] === 'freelancer' ? (FreelancerProfile::find((int) $user['id']) ?? []) : [],
        ]);
    }

    /** POST /account/edit */
    public function update(): void
    {
        $user = Auth::requireLogin();
        Session::checkCsrf();
        $isFreelancer = $user['role'] === 'freelancer';

        $t = fn(string $k) => is_string($_POST[$k] ?? null) ? trim($_POST[$k]) : '';
        $name = $t('name'); $phone = $t('phone');

        $fail = function (string $msg) use ($isFreelancer) {
            Session::keepOld(array_map(fn($v) => is_string($v) ? $v : '', $_POST));
            Session::flash('error', $msg);
            Url::redirect('/account/edit');
        };

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100)                     $fail('Enter your full name.');
        if ($phone !== '' && !preg_match('/^\+?[0-9 ]{9,15}$/', $phone))        $fail('Enter a valid phone number or leave it blank.');

        $profile = null;
        if ($isFreelancer) {
            $headline = $t('headline'); $town = $t('town'); $bio = $t('bio');
            $rateRaw  = str_replace(',', '', $t('hourly_rate')); $url = $t('portfolio_url');

            if (mb_strlen($headline) > 80)                                      $fail('The headline is too long (80 characters at most).');
            if (mb_strlen($town) > 100)                                         $fail('The town is too long.');
            if (mb_strlen($bio) > 1000)                                         $fail('The bio is too long. Shorten it to 1000 characters or fewer.');

            // Skills: comma-separated, tidy up, no duplicates, at most 8 of up to 30 characters.
            $skills = [];
            foreach (explode(',', $t('skills')) as $s) {
                $s = trim($s);
                if ($s === '') continue;
                if (mb_strlen($s) > 30)                                         $fail('Each skill must be 30 characters or fewer.');
                $skills[mb_strtolower($s)] ??= $s;      // keep the first spelling of a repeated skill
            }
            if (count($skills) > 8)                                             $fail('List at most 8 skills.');

            if ($rateRaw !== '' && (!is_numeric($rateRaw) || $rateRaw < 0 || $rateRaw > 100000)) $fail('The hourly rate must be between KES 0 and KES 100,000.');
            if ($url !== '') {
                $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
                if (mb_strlen($url) > 255 || !filter_var($url, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
                    $fail('The portfolio link must be a full web address starting with http:// or https://');
                }
            }

            $profile = [
                'headline' => $headline ?: null, 'town' => $town ?: null, 'bio' => $bio ?: null,
                'skills' => $skills ? implode(', ', $skills) : null,
                'hourly_rate' => $rateRaw !== '' ? round((float) $rateRaw, 2) : null,
                'portfolio_url' => $url ?: null,
            ];
        }

        User::updateContact((int) $user['id'], $name, $phone);
        if ($profile !== null) FreelancerProfile::save((int) $user['id'], $profile);

        Session::clearOld();
        Session::flash('success', 'Profile saved.');
        Url::redirect('/account');
    }

    /** GET /freelancers/{id}: only the freelancer themself, or a client who has received an application from them. */
    public function show(int $id): void
    {
        $viewer     = Auth::requireLogin();
        $freelancer = User::find($id);

        $allowed = $freelancer && $freelancer['role'] === 'freelancer' && (
            (int) $viewer['id'] === $id ||
            ($viewer['role'] === 'client' && Application::clientSees((int) $viewer['id'], $id))
        );
        if (!$allowed) abort(404, 'Profile not found');

        View::render('freelancers/show', [
            'title' => $freelancer['name'], 'nav' => (int) $viewer['id'] === $id ? 'account' : 'mine',
            'f' => $freelancer, 'profile' => FreelancerProfile::find($id) ?? [], 'isSelf' => (int) $viewer['id'] === $id,
            'rating' => Review::summary($id), 'reviews' => Review::received($id),
        ]);
    }
}
