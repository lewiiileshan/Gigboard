<?php
namespace App\Controllers;

use App\Core\{Auth, FileStore};
use App\Models\{Application, ApplicationFile, Gig};

/**
 * Qualification files are private: only the freelancer who uploaded them and the CLIENT
 * of the gig they applied to can download them. Everyone else gets "not found".
 */
class QualificationController
{
    /** GET /qualifications/{id}/download */
    public function download(int $id): void
    {
        $user = Auth::requireLogin();
        $file = ApplicationFile::find($id) ?? abort(404, 'File not found');
        $app  = Application::find((int) $file['application_id']) ?? abort(404, 'File not found');
        $gig  = Gig::find((int) $app['gig_id']) ?? abort(404, 'File not found');

        $isClient   = (int) $gig['client_id'] === (int) $user['id'];
        $isUploader = (int) $file['freelancer_id'] === (int) $user['id'];
        if (!$isClient && !$isUploader) abort(404, 'File not found');

        FileStore::send($file);
    }
}
