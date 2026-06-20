<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome</title>
</head>
<body>
    <h1>Welcome, {{ $user->name }}!</h1>

    <p>Thank you for registering with {{ config('app.name') }}.</p>

    <p>Your account is ready. You can sign in anytime using <strong>{{ $user->email }}</strong>.</p>

    <p>If you did not create this account, please contact support.</p>
</body>
</html>
