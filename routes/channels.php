<?php

use Illuminate\Support\Facades\Broadcast;
Broadcast::routes(['middleware' => ['web','auth']]);
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Added: authorize agent task private channel
Broadcast::channel('agent.tasks.{userId}', function($user, $userId){
    return (string)$user->id === (string)$userId; // ensure user is same
});

Broadcast::channel('task.presence.{taskId}', function ($user, $taskId) {
    // You can add logic to check if the user is allowed to join the task
    return ['id' => $user->id, 'name' => $user->name, 'profile_picture' => $user->profile_picture];
});