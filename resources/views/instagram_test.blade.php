<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Instagram Connection Test</title>
    <style>
        body {font-family: Arial, sans-serif; margin: 2rem; background:#f9f9f9;}
        .container {max-width: 800px; margin:auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);}
        .form-group {margin-bottom:1rem;}
        label {display:block; margin-bottom:0.5rem; font-weight:bold;}
        input[type="text"], input[type="url"] {width:100%; padding:0.5rem; border:1px solid #ccc; border-radius:4px;}
        button {background:#0066ff; color:#fff; border:none; padding:0.6rem 1.2rem; border-radius:4px; cursor:pointer;}
        button:hover {background:#004acc;}
        table {width:100%; border-collapse:collapse; margin-top:2rem;}
        th, td {border:1px solid #ddd; padding:0.5rem; text-align:left;}
        th {background:#f0f0f0;}
        pre {background:#f4f4f4; padding:0.5rem; border-radius:4px; overflow:auto;}
    </style>
</head>
<body>
<div class="container">
    <h1>Instagram Connection Test</h1>
    <form method="POST" action="{{ route('instagram.test.run') }}">
        @csrf
        <div class="form-group">
            <label for="app_id">App ID</label>
            <input type="text" id="app_id" name="app_id" value="{{ old('app_id', $old['app_id'] ?? '') }}" required>
        </div>
        <div class="form-group">
            <label for="app_secret">App Secret</label>
            <input type="text" id="app_secret" name="app_secret" value="{{ old('app_secret', $old['app_secret'] ?? '') }}" required>
        </div>
        <div class="form-group">
            <label for="redirect_uri">Redirect URI</label>
            <input type="url" id="redirect_uri" name="redirect_uri" value="{{ old('redirect_uri', $old['redirect_uri'] ?? '') }}" required>
        </div>
        <button type="submit">Run Test</button>
    </form>

    @if(isset($results))
        <h2>Step Results</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>Request</th>
                    <th>Status</th>
                    <th>Response</th>
                </tr>
            </thead>
            <tbody>
                @foreach($results as $i => $step)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $step['description'] ?? '' }}</td>
                        <td><pre>{{ $step['request'] ?? '' }}</pre></td>
                        <td>{{ $step['status'] ?? 'N/A' }}</td>
                        <td><pre>{{ json_encode($step['response'] ?? $step['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
</body>
</html>
