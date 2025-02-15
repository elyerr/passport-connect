<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="font-sans bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-lg p-10 w-full max-w-lg">
        <div class="text-center">
            @if (isset($code))
                <h1 class="text-5xl font-extrabold text-gray-800 mb-4">{{ $code }}</h1>
            @endif

            @if (isset($message))
                <p class="text-gray-600 mb-6">{{ $message }}</p>
            @endif

            @if (isset($code) && $code == 401)
                <form action="/login" method="GET">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition-all duration-300">
                        {{ __('Go to the login') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</body>
</html>
