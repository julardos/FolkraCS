<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class InstagramTestController extends Controller
{
    /**
     * Show the test form.
     */
    public function showForm()
    {
        return view('instagram_test');
    }

    /**
     * Handle the test request.
     */
    public function runTest(Request $request)
    {
        $request->validate([
            'app_id'       => 'required|string',
            'app_secret'   => 'required|string',
            'redirect_uri' => 'required|url',
        ]);

        $appId     = $request->input('app_id');
        $appSecret = $request->input('app_secret');
        $redirect  = $request->input('redirect_uri');

        $steps = [];

        // Step 1 – Generate App Access Token (client_credentials)
        $url1 = 'https://graph.facebook.com/oauth/access_token';
        $params1 = [
            'client_id'     => $appId,
            'client_secret' => $appSecret,
            'grant_type'    => 'client_credentials',
        ];
        Log::info('InstagramTest: Requesting App Access Token', $params1);
        $res1 = Http::get($url1, $params1);
        $steps[] = [
            'description' => 'App Access Token request',
            'request' => $url1 . '?' . http_build_query($params1),
            'response' => $res1->json(),
            'status' => $res1->status(),
        ];
        Log::info('InstagramTest: App Access Token response', $res1->json());

        // Step 2 – Build OAuth URL for user login (to see if credentials are valid)
        $authUrl = 'https://www.facebook.com/v20.0/dialog/oauth?' . http_build_query([
            'client_id'     => $appId,
            'redirect_uri'  => $redirect,
            'scope'         => 'instagram_basic,pages_show_list',
            'response_type' => 'code',
            'state'         => csrf_token(),
        ]);
        $steps[] = [
            'description' => 'OAuth authorization URL (manual step)',
            'request' => $authUrl,
            'response' => 'Open this URL in a browser to start the Instagram OAuth flow.',
            'status' => null,
        ];
        Log::info('InstagramTest: Generated OAuth URL', ['url' => $authUrl]);

        // Step 3 – (Optional) Attempt to exchange a dummy code to see failure handling
        // We deliberately skip this because we don't have a code yet.

        return view('instagram_test', [
            'results' => $steps,
            'old' => $request->only(['app_id', 'app_secret', 'redirect_uri']),
        ]);
    }
}
