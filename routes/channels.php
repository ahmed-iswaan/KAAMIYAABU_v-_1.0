<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Added: authorize agent task private channel
Broadcast::channel('agent.tasks.{userId}', function($user, $userId){
    return (string)$user->id === (string)$userId; // ensure user is same
});
