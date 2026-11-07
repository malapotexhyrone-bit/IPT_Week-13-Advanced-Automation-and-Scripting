<?php

declare(strict_types=1);

use App\Models\AcademicTerm;
use App\Models\Clearance;
use App\Models\User;
use App\Services\ClearanceInitiationService;
use App\Services\SecureExchangeSender;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;

$projectRoot = dirname(__DIR__);
require $projectRoot.'/backend/vendor/autoload.php';
$app = require $projectRoot.'/backend/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$student = User::query()->where('role', User::ROLE_STUDENT)->firstOrFail();
$term = AcademicTerm::query()->where('is_active', true)->firstOrFail();
$clearance = Clearance::query()
    ->where('student_id', $student->id)
    ->where('academic_term_id', $term->id)
    ->first();

if ($clearance === null) {
    $clearance = app(ClearanceInitiationService::class)->initiate(
        $student,
        $term->code,
        (string) Str::ulid(),
        'week14-controlled-https-demo',
    );
}

$result = app(SecureExchangeSender::class)->send(
    $clearance,
    (string) Str::ulid(),
);

fwrite(STDOUT, json_encode([
    'result' => 'accepted',
    'data' => $result,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
