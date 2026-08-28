<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\KnowledgeBase;
use App\Services\DocumentExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class KnowledgeBaseController extends Controller
{
    private function client(): Client
    {
        return Client::where('tenant_id', tenant('id'))->firstOrCreate(
            ['tenant_id' => tenant('id')],
            ['name' => tenant('id'), 'slug' => tenant('id'), 'status' => 'active', 'openrouter_model' => 'openai/gpt-4o-mini']
        );
    }

    public function index()
    {
        $client = $this->client();

        return Inertia::render('Tenant/KnowledgeBase', [
            'entries' => KnowledgeBase::where('client_id', $client->id)
                ->orderBy('sort_order')->orderBy('id')
                ->get(['id', 'title', 'type', 'file_name', 'content', 'is_active', 'sort_order']),
        ]);
    }

    public function store(Request $request)
    {
        $type = $request->input('type', 'text');

        if ($type === 'document') {
            return $this->storeDocument($request);
        }

        $data = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        KnowledgeBase::create([...$data, 'client_id' => $this->client()->id, 'type' => 'text', 'is_active' => true]);

        return back()->with('success', 'Entry added.');
    }

    private function storeDocument(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'file'  => 'required|file|mimes:pdf,docx,txt,md,xlsx,xls,csv|max:20480',
        ]);

        $file    = $request->file('file');
        $client  = $this->client();
        $dir     = "knowledge-base/{$client->id}";
        $path    = $file->store($dir, 'local');

        try {
            $extractor = new DocumentExtractor();
            $content   = $extractor->extract($file);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            return back()->withErrors(['file' => 'Could not extract text: ' . $e->getMessage()]);
        }

        KnowledgeBase::create([
            'client_id' => $client->id,
            'title'     => $request->input('title'),
            'type'      => 'document',
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'content'   => $content,
            'is_active' => true,
        ]);

        return back()->with('success', 'Document uploaded and text extracted.');
    }

    public function update(Request $request, KnowledgeBase $kb)
    {
        $this->authorizeKb($kb);

        $data = $request->validate([
            'title'     => 'required|string|max:255',
            'content'   => 'required|string',
            'is_active' => 'boolean',
        ]);

        $kb->update($data);

        return back()->with('success', 'Entry updated.');
    }

    public function destroy(KnowledgeBase $kb)
    {
        $this->authorizeKb($kb);

        if ($kb->file_path) {
            Storage::disk('local')->delete($kb->file_path);
        }

        $kb->delete();

        return back()->with('success', 'Entry deleted.');
    }

    private function authorizeKb(KnowledgeBase $kb): void
    {
        $client = $this->client();
        abort_if($kb->client_id !== $client->id, 403);
    }
}
