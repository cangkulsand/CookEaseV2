<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DailyMealPlanReminder extends Notification
{
    use Queueable;

    protected $mealType;

    protected $userName;

    public function __construct($mealType, $userName)
    {
        $this->mealType = $mealType;
        $this->userName = $userName;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $mealTypeNames = [
            'breakfast' => 'Breakfast',
            'lunch' => 'Lunch',
            'dinner' => 'Dinner',
            'snack' => 'Snack',
            'snacks' => 'Snack',
            'other' => 'Other',
            'others' => 'Other',
        ];

        $normalizedType = strtolower(trim($this->mealType));
        $mealName = $mealTypeNames[$normalizedType] ?? ($normalizedType ?: 'a meal');

        return [
            'title' => 'Meal Plan Reminder',
            'message' => "Hey {$this->userName}, you’ve got a meal planned for {$mealName} today.",
            // Optional: Add route for clickable notification
            'route' => route('meal-plan.index', ['date' => now()->toDateString()]),
        ];
    }
}
