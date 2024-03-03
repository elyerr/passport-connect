<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous">
    </script>

</head>

<body class="font-sans antialiased">

    <div class="container">
        <div class="card mt-5">
            <div class="card-head mb-5 text-center fw-bold h1 text-secondary">
                {{__('Login')}}
            </div>
            <div class="card-body text-center text-capitalize mt-5">
                <form action="/redirect" method="GET">
                    <button type="submit" class="btn btn-primary">{{__('Login with Oauth2 Server')}}</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>
