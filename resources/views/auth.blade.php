<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-r from-blue-500 to-indigo-600 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-lg p-10 w-full max-w-md text-center">
        <h1 class="text-4xl font-extrabold text-gray-800 mb-6">{{ __('Welcome') }}</h1>
        <p class="text-gray-600 mb-8">{{ __('Login to access your account and explore our features.') }}</p>

        <form action="/redirect" method="GET">
            <button type="submit"
                class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition-all duration-300">
                {{ __('Login with OAuth2 Server') }}
            </button>
        </form>
    </div>
</body>

</html>
