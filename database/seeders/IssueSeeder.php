<?php

namespace Database\Seeders;

use App\Models\Issue;
use Illuminate\Database\Seeder;

class IssueSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->issues() as $data) {
            $comments = $data['comments'];
            unset($data['comments']);

            $issue = Issue::create($data);
            $issue->comments()->createMany($comments);
        }
    }

    private function issues(): array
    {
        return [
            [
                'title' => 'Customer charged twice for March subscription',
                'description' => 'A customer reports two identical charges of $49 on their card for the March subscription. They want one of the charges refunded as soon as possible.',
                'priority' => 'high',
                'category' => 'billing',
                'status' => 'open',
                'comments' => [
                    ['author_name' => 'Anna Reyes', 'body' => 'I can see both charges in Stripe, same timestamp.'],
                    ['author_name' => 'Mark Santos', 'body' => 'Looks like the webhook retried. I will check the logs.'],
                ],
            ],
            [
                'title' => 'Cannot log in after password reset',
                'description' => 'User reset their password but the login page keeps saying the credentials are invalid. They tried two different browsers.',
                'priority' => 'medium',
                'category' => 'account',
                'status' => 'in_progress',
                'comments' => [
                    ['author_name' => 'Joy Cruz', 'body' => 'Asked the user to clear cookies and try again.'],
                ],
            ],
            [
                'title' => 'Dashboard loads very slowly',
                'description' => 'The main dashboard takes more than 20 seconds to load for accounts with a lot of orders. Smaller accounts are fine.',
                'priority' => 'high',
                'category' => 'technical',
                'status' => 'in_progress',
                'comments' => [
                    ['author_name' => 'Mark Santos', 'body' => 'The orders widget runs a query per order. Working on a fix.'],
                    ['author_name' => 'Anna Reyes', 'body' => 'Two more customers reported the same thing today.'],
                    ['author_name' => 'Leo Garcia', 'body' => 'Added an index on orders.account_id as a quick win.'],
                ],
            ],
            [
                'title' => 'Export invoices to CSV',
                'description' => 'Several customers asked for a way to export all their invoices to a CSV file for their accountant.',
                'priority' => 'low',
                'category' => 'feature_request',
                'status' => 'open',
                'comments' => [],
            ],
            [
                'title' => 'Invoice shows wrong company address',
                'description' => 'The PDF invoice still shows the old company address even after the customer updated it in their settings.',
                'priority' => 'medium',
                'category' => 'billing',
                'status' => 'resolved',
                'comments' => [
                    ['author_name' => 'Joy Cruz', 'body' => 'Invoices were cached. Cleared the cache and regenerated the PDF.'],
                    ['author_name' => 'Anna Reyes', 'body' => 'Customer confirmed the new address is showing.'],
                ],
            ],
            [
                'title' => 'Email notifications not being sent',
                'description' => 'No order confirmation emails have gone out since this morning. The mail queue seems to be stuck.',
                'priority' => 'high',
                'category' => 'technical',
                'status' => 'resolved',
                'comments' => [
                    ['author_name' => 'Leo Garcia', 'body' => 'SMTP credentials expired. Rotated them and restarted the workers.'],
                ],
            ],
            [
                'title' => 'Question about data retention policy',
                'description' => 'A customer wants to know how long we keep their data after they cancel their account.',
                'priority' => 'low',
                'category' => 'other',
                'status' => 'open',
                'comments' => [
                    ['author_name' => 'Joy Cruz', 'body' => 'Forwarded to legal for the exact wording.'],
                ],
            ],
        ];
    }
}
