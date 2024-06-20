<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Events\UserSessionChange;

class BroadcastUserLoginNotification
{
    public function __construct()
    {
        //
    }
    public function handle(Login $event): void
    {
        broadcast(new UserSessionChange("{$event->user->name} is online", "success"))->toOthers();
    }
}