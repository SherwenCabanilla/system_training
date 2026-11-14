<?php

use App\AppointmentProposals;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('appointments:expire-proposals', function (AppointmentProposals $proposals) {
    $proposals->expire();
})->purpose('Release expired appointment proposals and return requests to staff review');

Schedule::command('appointments:expire-proposals')->everyMinute()->withoutOverlapping();
