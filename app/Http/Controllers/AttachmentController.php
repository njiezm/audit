<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function store(Request $request, Audit $audit): RedirectResponse
    {
        $this->authorize('update', $audit);

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,pdf', 'max:8192'],
            'audit_category_id' => ['nullable', 'integer', 'exists:audit_categories,id'],
            'caption' => ['nullable', 'string', 'max:255'],
        ], [], ['file' => 'pièce jointe']);

        $file = $request->file('file');

        // Les preuves ne sont pas publiques : disque privé + téléchargement
        // servi par le contrôleur, jamais par une URL devinable.
        $path = $file->store('audits/'.$audit->id, 'local');

        $audit->attachments()->create([
            'audit_category_id' => $data['audit_category_id'] ?? null,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'caption' => $data['caption'] ?? null,
            'uploaded_by' => Auth::id(),
        ]);

        return back()->with('success', 'Pièce jointe ajoutée.');
    }

    public function download(Audit $audit, Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $audit);
        abort_unless($attachment->audit_id === $audit->id, 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function destroy(Audit $audit, Attachment $attachment): RedirectResponse
    {
        $this->authorize('update', $audit);
        abort_unless($attachment->audit_id === $audit->id, 404);

        $attachment->delete();

        return back()->with('success', 'Pièce jointe supprimée.');
    }
}
