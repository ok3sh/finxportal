<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    public function index()
    {
        $user = Session::get('user');
        $roles = $user['roles'] ?? [];
        if (empty($roles)) {
            return response()->json([]);
        }

        // 1. Get tags for user roles
        $tags = DB::table('role_tag')
            ->whereIn('role', $roles)
            ->distinct()
            ->pluck('tag')
            ->toArray();

        if (empty($tags)) {
            return response()->json([]);
        }

        // 2. Get doc IDs for tags
        $docIds = DB::table('doc_tag')
            ->whereIn('tag', $tags)
            ->distinct()
            ->pluck('document_id')
            ->toArray();

        if (empty($docIds)) {
            return response()->json([]);
        }

        // 3. Fetch docs from Paperless
        $client = new Client();
        $resultDocs = [];
        $paperlessToken = env('PAPERLESS_TOKEN');
        foreach ($docIds as $docId) {
            try {
                $res = $client->get("http://localhost:8080/api/documents/$docId/", [
                    'headers' => [
                        'Authorization' => 'Token ' . $paperlessToken
                    ],
                    'http_errors' => false
                ]);
                if ($res->getStatusCode() === 200) {
                    $resultDocs[] = json_decode($res->getBody(), true);
                }
            } catch (\Exception $e) {
                // Optionally log error
            }
        }

        return response()->json($resultDocs);
    }
}
