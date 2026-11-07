<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

require __DIR__.'/../backend/vendor/autoload.php';

$app = require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$email = Str::lower(trim($argv[1] ?? ''));
$user = User::query()->where('email', $email)->first();

if ($user === null || ! str_ends_with($user->email, '@example.test')) {
    fwrite(STDERR, "Choose an existing fictional @example.test account.\n");
    exit(1);
}

$password = Str::password(20, letters: true, numbers: true, symbols: true, spaces: false);
$user->forceFill(['password' => Hash::make($password)])->save();
$user->tokens()->delete();

$credential = "Fictional account: {$user->email}\n"
    ."One-time displayed password: {$password}\n"
    ."Copy it now. It is stored only as a hash and will not be shown again.\n";
$outputName = trim($argv[2] ?? '');

if ($outputName === '') {
    fwrite(STDOUT, $credential);
    exit(0);
}

if (basename($outputName) !== $outputName || ! preg_match('/^[a-z0-9-]+\.txt$/', $outputName)) {
    fwrite(STDERR, "The optional credential filename must be a simple lowercase .txt name.\n");
    exit(1);
}

$privateDirectory = storage_path('app/private');
if (! is_dir($privateDirectory)) {
    mkdir($privateDirectory, 0700, true);
}

file_put_contents($privateDirectory.DIRECTORY_SEPARATOR.$outputName, $credential, LOCK_EX);
fwrite(STDOUT, "Credential written to an ignored local private file. Delete it after the browser check.\n");
