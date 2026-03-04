<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$user = App\Models\User::where('email','tejasgosavi@gmail.com')->first();
$path = $user?->profile_photo_path;
echo "URL: ".Illuminate\Support\Facades\Storage::disk('public')->url($path)."\n";
?>
