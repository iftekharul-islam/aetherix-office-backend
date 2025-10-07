<?php

use App\Models\Department;
use App\Models\Division;
use App\Models\SlackCredentials;
use App\Models\User;
use Illuminate\Support\Facades\Http;

if (!function_exists('send_slack')) {
    function send_slack($model)
    {
        $user = User::with('department.division')->find($model->user_id);

        $modelConditions = [
            [User::class, $user->id],
            [Department::class, optional($user->department)->id],
            [Division::class, optional(optional($user->department)->division)->id],
        ];

        // Filter out null model_ids
        $modelConditions = array_filter($modelConditions, fn($pair) => !is_null($pair[1]));

        $slackList = SlackCredentials::where(function ($query) use ($modelConditions) {
            foreach ($modelConditions as [$type, $id]) {
                $query->orWhere(function ($q) use ($type, $id) {
                    $q->where('model_type', $type)
                        ->where('model_id', $id);
                });
            }
        })->pluck('slack_webhook_url')->unique()->values()->all();

        foreach ($slackList as $slackWebhookUrl) {
            info("Sending Slack notification to: $slackWebhookUrl");
            Http::post($slackWebhookUrl, [
                'blocks' => [
                    [
                        'type' => 'section',
                        'fields' => [
                            [
                                'type' => 'mrkdwn',
                                'text' => "*User:*\n" . ($user->name ?? 'Unknown User')
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Department:*\n" . ($user->department->name ?? 'No Department')
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Division:*\n" . ($user->department->division->name ?? 'No Division')
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Date/Time:*\n" . ($model->datetime ?? 'Unknown Date/Time')
                            ]
                        ]
                    ],
                    [
                        'type' => 'divider'
                    ],
                ]
            ]);
        }
    }
}
